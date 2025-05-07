<?php
// Сначала подключаем бд, если нет, то ошибка
try {
    $dsn = 'mysql:host=localhost;dbname=books;charset=utf8mb4';
    $username = 'root';
    $password = 'bc15cefD!';/* Укажите ваш пароль */

    // Создаем экземпляр PDO
    $pdo = new PDO($dsn, $username, $password);

    // Устанавливаем режим обработки ошибок
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

   // echo "Подключение успешно!";
} catch (PDOException $e) {
    // Обработка ошибок подключения
    logMessage(date("d-m-Y H:i:s")." - Ошибка подключения: " . $e->getMessage());
}

// Отдельная функция для извлечения обложки из FB2
function extractCoverFromFB2($fb2Content) {
    $xml = new SimpleXMLElement($fb2Content);

    // Ищем секцию `<binary>` с обложкой
    foreach ($xml->binary as $binary) {
        $attributes = $binary->attributes();
        if (isset($attributes['id']) && strpos((string)$attributes['id'], 'cover') !== false) {
            $imageData = (string)$binary; // Получаем данные изображения в виде строки
            return $imageData; // Возвращаем данные изображения вместо пути
        }
    }
    return ''; // Если обложка не найдена
}

// Функция для добавления книги в базу данных
function addBook($title, $annotation, $lang, $date, $cover,$path) {
    global $pdo; // Используем глобальную переменную $pdo

    // Проверка наличия книги с таким же названием
    $checkStmt = $pdo->prepare("SELECT id FROM books WHERE title = :title");
    $checkStmt->bindParam(':title', $title);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        // Если книга существует, возвращаем её ID
        $existingBook = $checkStmt->fetch(PDO::FETCH_ASSOC);
        return "OLD_".$existingBook['id'];
    }

    // Подготовка SQL-запроса для добавления книги
    $stmt = $pdo->prepare("INSERT INTO books (title, annotation, lang, date, cover, path) VALUES (:title, :annotation, :lang, :date, :cover, :path)");

    
    // Привязка параметров
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':annotation', $annotation);
    $stmt->bindParam(':lang', $lang);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':cover', $cover);
    $stmt->bindParam(':path', $path); // Привязываем путь к файлу

    // Выполнение запроса
    if ($stmt->execute()) {
        return $pdo->lastInsertId(); // Возвращаем ID последней вставленной записи
    } else {
        return false; // Если произошла ошибка при выполнении запроса
    }
}


use Tizis\FB2\FB2Controller;

function processBookData($file, $pdo, $filePath) {
    try{
        $item = new FB2Controller($file);
        $item->withNotes();
        $item->startParse();
    } catch (Exception $e) {
        logMessage(date("d-m-Y H:i:s")." - Ошибка: не удалось распарсить файл $file: " . $e->getMessage());
        $result = addBadBook($filePath, basename($file), $e->getMessage(), $pdo);
        if ($result == "EXISTS") {
            logMessage(date("d-m-Y H:i:s")." - Книга уже существует в таблице badbooks: $fileName");
        } else {
            logMessage(date("d-m-Y H:i:s")." - Книга добавлена в таблицу badbooks: $fileName");
        }
        //Обработка бед книги
        return false; // Возвращаем false в случае ошибки
    }
    

    // Извлекаем данные книги
    $cover = extractCoverFromFB2($file);
    $title = $item->getBook()->getInfo()->getTitle();
    $annotation = $item->getBook()->getInfo()->getAnnotation();
    $lang = $item->getBook()->getInfo()->getLang()['lang'];
    $publishDate = $item->getBook()->getInfo()->getPublishDate();
    $date = $publishDate ?: '';

    // Добавляем книгу в базу с указанием пути
    $book_id = addBook($title, $annotation, $lang, $date, $cover, $filePath);
    if(substr($book_id, 0, 4) == "OLD_"){
        return $book_id; // Возвращаем ID существующей книги
    }

    // Обработка серий
    $sequences = $item->getBook()->getInfo()->getSequences();
    foreach ($sequences as $sequence) {
        $ser_name = $sequence['name'];
        $ser_number = $sequence['number'];
        $stmt = $pdo->prepare("INSERT INTO series (book_id, name, number) VALUES (:book_id, :name, :number)");
        $stmt->bindParam(':book_id', $book_id);
        $stmt->bindParam(':name', $ser_name);
        $stmt->bindParam(':number', $ser_number);
        $stmt->execute();
    }

    // Обработка авторов
    foreach ($item->getBook()->getAuthors() as $author) {
        // Проверяем, существует ли автор в базе
        $stmt = $pdo->prepare("SELECT id FROM authors WHERE first_name = :first_name AND last_name = :last_name AND middle_name = :middle_name");
        $stmt->bindParam(':first_name', $author->getFirstName());
        $stmt->bindParam(':last_name', $author->getLastName());
        $stmt->bindParam(':middle_name', $author->getMiddleName());
        $stmt->execute();
        $existingAuthor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingAuthor) {
            $author_id = $existingAuthor['id']; // Автор уже существует
        } else {
            // Добавляем нового автора
            $stmt = $pdo->prepare("INSERT INTO authors (first_name, last_name, middle_name) VALUES (:first_name, :last_name, :middle_name)");
            $stmt->bindParam(':first_name', $author->getFirstName());
            $stmt->bindParam(':last_name', $author->getLastName());
            $stmt->bindParam(':middle_name', $author->getMiddleName());
            $stmt->execute();
            $author_id = $pdo->lastInsertId();
        }

        // Добавляем связь между книгой и автором
        $stmt = $pdo->prepare("INSERT INTO bookauthors (book_id, author_id) VALUES (:book_id, :author_id)");
        $stmt->bindParam(':book_id', $book_id);
        $stmt->bindParam(':author_id', $author_id);
        $stmt->execute();
    }

    // Обработка переводчиков
    foreach ($item->getBook()->getTranslators() as $translator) {
        // Проверяем, существует ли переводчик в базе
        $stmt = $pdo->prepare("SELECT id FROM translators WHERE first_name = :first_name AND last_name = :last_name AND middle_name = :middle_name");
        $stmt->bindParam(':first_name', $translator->getFirstName());
        $stmt->bindParam(':last_name', $translator->getLastName());
        $stmt->bindParam(':middle_name', $translator->getMiddleName());
        $stmt->execute();
        $existingTranslator = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingTranslator) {
            $translator_id = $existingTranslator['id']; // Переводчик уже существует
        } else {
            // Добавляем нового переводчика
            $stmt = $pdo->prepare("INSERT INTO translators (first_name, last_name, middle_name) VALUES (:first_name, :last_name, :middle_name)");
            $stmt->bindParam(':first_name', $translator->getFirstName());
            $stmt->bindParam(':last_name', $translator->getLastName());
            $stmt->bindParam(':middle_name', $translator->getMiddleName());
            $stmt->execute();
            $translator_id = $pdo->lastInsertId();
        }

        // Добавляем связь между книгой и переводчиком
        $stmt = $pdo->prepare("INSERT INTO booktranslators (book_id, translator_id) VALUES (:book_id, :translator_id)");
        $stmt->bindParam(':book_id', $book_id);
        $stmt->bindParam(':translator_id', $translator_id);
        $stmt->execute();
    }

    // Обработка жанров
    foreach ($item->getBook()->getInfo()->getGenres() as $genre) {
        $stmt = $pdo->prepare("INSERT INTO genres (name) VALUES (:name)");
        $stmt->bindParam(':name', $genre);
        $stmt->execute();
        $genre_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO bookgenres (book_id, genre_id) VALUES (:book_id, :genre_id)");
        $stmt->bindParam(':book_id', $book_id);
        $stmt->bindParam(':genre_id', $genre_id);
        $stmt->execute();
    }

    return $book_id; // Возвращаем ID книги
}

function addBadBook($filePath, $fileName, $errorMessage, $pdo) {
    // Проверяем, есть ли книга в таблице badbooks
    $sqlCheck = "SELECT * FROM badbooks WHERE path = ? OR title = ?";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$filePath, $fileName]);
    $check = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($check) {
        return "EXISTS"; // Книга уже есть в базе
    } else {
        // Добавляем книгу в таблицу badbooks
        $sql = "INSERT INTO badbooks (title, path, error) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fileName, $filePath, $errorMessage]);
        return "ADDED"; // Книга добавлена в базу
    }
}

function scanDirectory($directory, $pdo) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

    foreach ($iterator as $file) {
        // Пропускаем директории
        if ($file->isDir()) {
            continue;
        }

        $filePath = $file->getPathname();

        // Если файл - архив, распаковываем его и ищем .fb2 файлы
        if (preg_match('/\.(zip|rar)$/i', $filePath)) {
            processArchive($filePath, $pdo);
            continue;
        }

        // Если файл - .fb2, обрабатываем его
        if (preg_match('/\.fb2$/i', $filePath)) {
            updateFictionBookTag($filePath);
            processFB2File($filePath, $pdo);
        }
    }
}

function processArchive($archivePath, $pdo) {
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('fb2_extract_');
    mkdir($tempDir);

    // Распаковываем архив
    $zip = new ZipArchive();
    if ($zip->open($archivePath) === true) {
        $zip->extractTo($tempDir);
        $zip->close();

        // Сканируем распакованную директорию
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir));
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.fb2$/i', $file->getFilename())) {
                $relativePath = $archivePath . '/' . $file->getFilename();
                updateFictionBookTag($file->getPathname());
                processFB2File($relativePath, $pdo);
            }
        }

        // Удаляем временные файлы
        $tempFiles = glob("$tempDir/*");
        array_map('unlink', $tempFiles);
        rmdir($tempDir);
    } else {
        logMessage(date("d-m-Y H:i:s")." - Ошибка: не удалось открыть архив $archivePath");
    }
}

function processFB2File($filePath, $pdo) {
    $fileName = basename($filePath);
    $fileContent = file_get_contents($filePath);

    if ($fileContent === false || empty($fileContent)) {
        logMessage(date("d-m-Y H:i:s")." - Ошибка: не удалось загрузить файл $filePath");
        return;
    }

    try {
        // Проверяем, является ли файл валидным XML   
        error_reporting(0);
        $xml = new SimpleXMLElement($fileContent);
        error_reporting(E_ERROR | E_WARNING | E_PARSE);

        // Если файл валиден, добавляем его в базу
        $book_id = processBookData($fileContent, $pdo, $filePath);

        if ($book_id) {
            if (substr($book_id, 0, 4) == "OLD_") {
                logMessage(date("d-m-Y H:i:s")." - Книга уже существует в базе с ID: ".substr($book_id, 4));
            } 
        } else {
            logMessage(date("d-m-Y H:i:s")." - Произошла ошибка при обработке книги: $filePath");
        }
    } catch (Exception $e) {
        // Если файл не валиден, добавляем его в таблицу badbooks
        $result = addBadBook($filePath, $fileName, $e->getMessage(), $pdo);
        if ($result == "EXISTS") {
            logMessage(date("d-m-Y H:i:s")." - Книга уже существует в таблице badbooks: $fileName");
        } else {
            logMessage(date("d-m-Y H:i:s")." - Книга добавлена в таблицу badbooks: $fileName");
        }
    }
}

function updateFictionBookTag($filePath) {
    // Загружаем содержимое файла
    $fileContent = file_get_contents($filePath);

    if ($fileContent === false) {
        logMessage(date("d-m-Y H:i:s")." - Ошибка: UFB - не удалось загрузить файл $filePath");
        return false;
    }

    // Регулярное выражение для поиска тега <FictionBook>
    $pattern = '/<FictionBook[^>]*>/';

    // Новый тег <FictionBook>
    $replacement = '<FictionBook xmlns="http://www.gribuser.ru/xml/fictionbook/2.0" xmlns:xlink="http://www.w3.org/1999/xlink">';

    // Замена тега
    $updatedContent = preg_replace($pattern, $replacement, $fileContent, 1);

    if ($updatedContent === null) {
        logMessage(date("d-m-Y H:i:s")." - Ошибка: UFB - не удалось обновить тег <FictionBook> в файле $filePath");
        return false;
    }

    // Сохраняем обновлённое содержимое обратно в файл
    if (file_put_contents($filePath, $updatedContent) === false) {
        logMessage(date("d-m-Y H:i:s")." - Ошибка: UFB - не удалось сохранить файл $filePath");
        return false;
    }

    return true;
}

function logMessage($message) {
    file_put_contents('scanner.log', $message . PHP_EOL, FILE_APPEND);
}
?>