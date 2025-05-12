<?php
// Подключаем библиотеки и файлы
require_once '../vendor/autoload.php';
include_once 'fb2ToText.php';
// Сначала подключаем бд, если нет, то ошибка
try {
    $dsn = 'mysql:host=localhost;dbname=books;charset=utf8mb4';
    $username = 'root';
    $password = '';/* Укажите ваш пароль */

    // Создаем экземпляр PDO
    $pdo = new PDO($dsn, $username, $password);

    // Устанавливаем режим обработки ошибок
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

   // echo "Подключение успешно!";
} catch (PDOException $e) {
    // Обработка ошибок подключения
    logMessage(date("d-m-Y H:i:s")." - Ошибка подключения: " . $e->getMessage());
}

// Функция для добавления книги в базу данных
function addBook($book) {
    global $pdo; // Используем глобальную переменную $pdo

    // Проверка наличия книги с таким же названием
    $checkStmt = $pdo->prepare("SELECT id FROM books WHERE title = :title");
    $checkStmt->bindParam(':title', $book['title']);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        // Если книга существует, возвращаем её ID
        $existingBook = $checkStmt->fetch(PDO::FETCH_ASSOC);
        return "OLD_".$existingBook['id'];
    }

    // Подготовка SQL-запроса для добавления книги
    $stmt = $pdo->prepare("INSERT INTO books (title, annotation, lang, date, cover, path) VALUES (:title, :annotation, :lang, :date, :cover, :path)");

    
    // Привязка параметров
    $stmt->bindParam(':title', $book['title']);
    $stmt->bindParam(':annotation', $book['annotation']);
    $stmt->bindParam(':lang', $book['lang']);
    $stmt->bindParam(':date', $book['date']);
    $stmt->bindParam(':cover', $book['cover']);
    $stmt->bindParam(':path', $book['path']); // Привязываем путь к файлу

    // Выполнение запроса
    if ($stmt->execute()) {
        return $pdo->lastInsertId(); // Возвращаем ID последней вставленной записи
    } else {
        return false; // Если произошла ошибка при выполнении запроса
    }
}

function processBookData($filePath,$pdo) {
   // Извлекаем данные книги
    $book = getBookInfo($filePath);
    if ($book === false) {
        addBadBook($filePath, basename($filePath), "Ошибка при извлечении данных книги", $pdo);
        return false;
    }
    // Добавляем книгу в базу с указанием пути
    $book_id = addBook($book);
    if(substr($book_id, 0, 4) == "OLD_"){
        return $book_id; // Возвращаем ID существующей книги
    }

    // Обработка серий
    if($book['series'][0]!=''){
        foreach ($book['series'] as $sequence) {
            $ser_name = $sequence['name'];
            $ser_number = $sequence['number'];
            $stmt = $pdo->prepare("INSERT INTO series (book_id, name, number) VALUES (:book_id, :name, :number)");
            $stmt->bindParam(':book_id', $book_id);
            $stmt->bindParam(':name', $ser_name);
            $stmt->bindParam(':number', $ser_number);
            $stmt->execute();
        }
    }

    // Обработка авторов
    if($book['author'][0] != ''){
        foreach ($book['author'] as $author) {

            // Проверяем, существует ли автор в базе
            $stmt = $pdo->prepare("SELECT id FROM authors WHERE first_name = :first_name AND last_name = :last_name AND middle_name = :middle_name");
            $stmt->bindParam(':first_name', $author['first-name']);
            $stmt->bindParam(':last_name', $author['last-name']);
            $stmt->bindParam(':middle_name', $author['middle-name']);
            $stmt->execute();
            $existingAuthor = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingAuthor) {
                $author_id = $existingAuthor['id']; // Автор уже существует
            } else {
                // Добавляем нового автора
                $stmt = $pdo->prepare("INSERT INTO authors (first_name, last_name, middle_name) VALUES (:first_name, :last_name, :middle_name)");
                $stmt->bindParam(':first_name', $author['first-name']);
                $stmt->bindParam(':last_name', $author['last-name']);
                $stmt->bindParam(':middle_name', $author['middle-name']);
                $stmt->execute();
                $author_id = $pdo->lastInsertId();
            }

            // Добавляем связь между книгой и автором
            $stmt = $pdo->prepare("INSERT INTO bookauthors (book_id, author_id) VALUES (:book_id, :author_id)");
            $stmt->bindParam(':book_id', $book_id);
            $stmt->bindParam(':author_id', $author_id);
            $stmt->execute();
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO bookauthors (book_id, author_id) VALUES (:book_id, 1)");
        $stmt->bindParam(':book_id', $book_id);
        $stmt->execute();    
    }

    // Обработка переводчиков
    //foreach ($item->getBook()->getTranslators() as $translator) {
        // Проверяем, существует ли переводчик в базе
        //$stmt = $pdo->prepare("SELECT id FROM translators WHERE first_name = :first_name AND last_name = :last_name AND middle_name = :middle_name");
        //$stmt->bindParam(':first_name', $translator->getFirstName());
        //$stmt->bindParam(':last_name', $translator->getLastName());
        //$stmt->bindParam(':middle_name', $translator->getMiddleName());
        //$stmt->execute();
        //$existingTranslator = $stmt->fetch(PDO::FETCH_ASSOC);

        //if ($existingTranslator) {
            //$translator_id = $existingTranslator['id']; // Переводчик уже существует
        //} else {
            // Добавляем нового переводчика
            //$stmt = $pdo->prepare("INSERT INTO translators (first_name, last_name, middle_name) VALUES (:first_name, :last_name, :middle_name)");
            //$stmt->bindParam(':first_name', $translator->getFirstName());
            //$stmt->bindParam(':last_name', $translator->getLastName());
            //$stmt->bindParam(':middle_name', $translator->getMiddleName());
            //$stmt->execute();
            //$translator_id = $pdo->lastInsertId();
        //}

        // Добавляем связь между книгой и переводчиком
        //$stmt = $pdo->prepare("INSERT INTO booktranslators (book_id, translator_id) VALUES (:book_id, :translator_id)");
        //$stmt->bindParam(':book_id', $book_id);
        //$stmt->bindParam(':translator_id', $translator_id);
        //$stmt->execute();
    //}

    // Обработка жанров
    foreach ($book['genre'] as $genre) {
        // Проверяем, существует ли жанр в базе
        $stmt = $pdo->prepare("SELECT id FROM genres WHERE name = :name");
        $stmt->bindParam(':name', $genre);
        $stmt->execute();
        $existingGenre = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existingGenre) {
            $genre_id = $existingGenre['id']; // Автор уже существует
        } else {
            $stmt = $pdo->prepare("INSERT INTO genres (name) VALUES (:name)");
            $stmt->bindParam(':name', $genre);
            $stmt->execute();
            $genre_id = $pdo->lastInsertId();
        }
        // Добавляем связь между книгой и жанром
        $stmt = $pdo->prepare("INSERT INTO bookgenres (book_id, genre_id) VALUES (:book_id, :genre_id)");
        $stmt->bindParam(':book_id', $book_id);
        $stmt->bindParam(':genre_id', $genre_id);
        $stmt->execute();
    }
    unset($book); // Освобождаем память, удаляя переменную $book
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

        // Если файл - архив, пропускаем его
        if (preg_match('/\.(zip|rar)$/i', $filePath)) {
            continue;
        }

        // Если файл - .fb2, обрабатываем его
        if (preg_match('/\.fb2$/i', $filePath)) {
            //updateFictionBookTag($filePath);
            processBookData($filePath,$pdo);
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
    file_put_contents('scanner_error.log', $message . PHP_EOL, FILE_APPEND);
}
?>