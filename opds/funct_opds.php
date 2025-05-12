<?php
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
//Получить книгу по id
function getBook($pdo, $book) {
    // Устанавливаем заголовки для XML
    header('Content-Type: application/fb2; charset=utf-8');

    // Запрос к базе данных для получения книги по id
    $query = "SELECT * FROM books WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $book]);
    $bookData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($bookData) {
        // Устанавливаем заголовок Content-Disposition с названием книги
        $fileName = htmlspecialchars($bookData['title']) . '.fb2';
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        // Выводим содержимое книги
        echo file_get_contents($bookData['path']);
    } else {
        echo "Книга не найдена.";
    }
}

//Книги по автору
function generateOPDSAuthor($pdo, $author) {
    // Устанавливаем заголовки для XML
    header('Content-Type: application/atom+xml; charset=utf-8');

    // Начало XML-документа
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><feed xmlns="http://www.w3.org/2005/Atom" xmlns:opds="http://opds-spec.org/2010/catalog"></feed>');

    // Основная информация о каталоге
    $xml->addChild('title', 'MyBooks OPDS Catalog');
    $xml->addChild('id', 'urn:uuid:' . uniqid());
    $xml->addChild('updated', date(DATE_ATOM));

    // Ссылка на себя
    $selfLink = $xml->addChild('link');
    $selfLink->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php?author='.$author);
    $selfLink->addAttribute('rel', 'self');
    $selfLink->addAttribute('type', 'application/atom+xml');

    // Ссылка на начальную страницу
    $startLink = $xml->addChild('link');
    $startLink->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php');
    $startLink->addAttribute('rel', 'start');
    $startLink->addAttribute('type', 'application/atom+xml');

    //Иконка
    $xml->addChild('icon', 'http://nd.alabuzya.ru/opds/img/books.png');

    // Запрос к базе данных для получения книг по автору
    $query = "SELECT * FROM fullbookauthor WHERE author_id = :author ORDER BY title ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':author' => "$author"]);
    while ($book = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Добавляем запись о книге
        $entry = $xml->addChild('entry');
        $entry->addChild('title', htmlspecialchars($book['title']));
        $author = $entry->addChild('author');
        $author->addChild('name', htmlspecialchars($book['author_name']));
        $author->addChild('uri', 'urn:uuid:' . 'http://nd.alabuzya.ru/opds/opds.php?author=' . $book['author_id']);
        $entry->addChild('dc:language', $book['lang']);
        $entry->addChild('dc:issued', $book['date']);
        $entry->addChild('id', 'urn:uuid:' . $book['id']);
        $entry->addChild('updated', date(DATE_ATOM, strtotime($book['date'])));
        $entry->addChild('content', htmlspecialchars($book['annotation']))->addAttribute('type', 'text');

        $thm = $entry->addChild('link');
        $thm->addAttribute('rel', 'http://opds-spec.org/image/thumbnail');
        $thm->addAttribute('href', 'http://nd.alabuzya.ru/' . $book['cover']);
        $thm->addAttribute('type', 'image/jpg');
        $img = $entry->addChild('link');
        $img->addAttribute('rel', 'http://opds-spec.org/image');
        $img->addAttribute('href', 'http://nd.alabuzya.ru/' . $book['cover']);
        $img->addAttribute('type', 'image/jpg');
        
        // Ссылка на файл книги
        $link = $entry->addChild('link');
        $link->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php?getbook=' . $book['id']);
        $link->addAttribute('rel', 'http://opds-spec.org/acquisition');
        $link->addAttribute('type', 'application/fb2+xml');
    }

    // Вывод XML-документа
    $output = $xml->asXML();
    //$output = str_replace('&amp;', '&', $output);
    echo $output;
}


//Книги по серии
function generateOPDSSeries($pdo, $series) {
    // Устанавливаем заголовки для XML
    header('Content-Type: application/atom+xml; charset=utf-8');

    // Начало XML-документа
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><feed xmlns="http://www.w3.org/2005/Atom" xmlns:opds="http://opds-spec.org/2010/catalog"></feed>');

    // Основная информация о каталоге
    $xml->addChild('title', 'MyBooks OPDS Catalog');
    $xml->addChild('id', 'urn:uuid:' . uniqid());
    $xml->addChild('updated', date(DATE_ATOM));

    // Ссылка на себя
    $selfLink = $xml->addChild('link');
    $selfLink->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php?series='.$series);
    $selfLink->addAttribute('rel', 'self');
    $selfLink->addAttribute('type', 'application/atom+xml');

    // Ссылка на начальную страницу
    $startLink = $xml->addChild('link');
    $startLink->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php');
    $startLink->addAttribute('rel', 'start');
    $startLink->addAttribute('type', 'application/atom+xml');

    //Иконка
    $xml->addChild('icon', 'http://nd.alabuzya.ru/opds/img/books.png');

    // Запрос к базе данных для получения книг по автору
    $query = "SELECT * FROM fullbookseries  WHERE series_name = :series ORDER BY title ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':series' => htmlspecialchars($series)]);
    while ($book = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Добавляем запись о книге
        $entry = $xml->addChild('entry');
        $entry->addChild('title', htmlspecialchars($book['title']));
        $author = $entry->addChild('author');
        $author->addChild('name', htmlspecialchars($book['author_name']));
        $entry->addChild('dc:language', $book['lang']);
        $entry->addChild('dc:issued', $book['date']);
        $entry->addChild('id', 'urn:uuid:' . $book['id']);
        $entry->addChild('updated', date(DATE_ATOM, strtotime($book['date'])));
        $entry->addChild('content', htmlspecialchars($book['annotation']))->addAttribute('type', 'text');

        $thm = $entry->addChild('link');
        $thm->addAttribute('rel', 'http://opds-spec.org/image/thumbnail');
        $thm->addAttribute('href', 'http://nd.alabuzya.ru/' . $book['cover']);
        $thm->addAttribute('type', 'image/jpg');
        $img = $entry->addChild('link');
        $img->addAttribute('rel', 'http://opds-spec.org/image');
        $img->addAttribute('href', 'http://nd.alabuzya.ru/' . $book['cover']);
        $img->addAttribute('type', 'image/jpg');

        // Ссылка на файл книги
        $link = $entry->addChild('link');
        $link->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php?getbook=' . $book['id']);
        $link->addAttribute('rel', 'http://opds-spec.org/acquisition');
        $link->addAttribute('type', 'application/fb2+xml');
    }

    // Вывод XML-документа
    $output = $xml->asXML();
    //$output = str_replace('&amp;', '&', $output);
    echo $output;
}

//Книги по серии
function generateOPDSGenres($pdo, $genres) {
    $genres = explode('---', $genres);
    $genre = $genres[0];
    $letter = $genres[1];
    // Устанавливаем заголовки для XML
    header('Content-Type: application/atom+xml; charset=utf-8');

    // Начало XML-документа
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><feed xmlns="http://www.w3.org/2005/Atom" xmlns:opds="http://opds-spec.org/2010/catalog"></feed>');

    // Основная информация о каталоге
    $xml->addChild('title', 'MyBooks OPDS Catalog');
    $xml->addChild('id', 'urn:uuid:' . uniqid());
    $xml->addChild('updated', date(DATE_ATOM));

    // Ссылка на себя
    $selfLink = $xml->addChild('link');
    $selfLink->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php?genres='.$genre);
    $selfLink->addAttribute('rel', 'self');
    $selfLink->addAttribute('type', 'application/atom+xml');

    // Ссылка на начальную страницу

    $startLink = $xml->addChild('link');
    $startLink->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php');
    $startLink->addAttribute('rel', 'start');
    $startLink->addAttribute('type', 'application/atom+xml');

    //Иконка
    $xml->addChild('icon', 'http://nd.alabuzya.ru/opds/img/books.png');

    // Запрос к базе данных для получения книг по автору
    $query = "SELECT * FROM books WHERE id IN(SELECT book_id FROM bookgenres WHERE genre_id IN(SELECT id FROM genres WHERE `name`=:genre)) AND LEFT(title, 1) = :letter ORDER BY title ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':genre' => htmlspecialchars($genre), ':letter' => htmlspecialchars($letter)]);
    while ($book = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Добавляем запись о книге
        $entry = $xml->addChild('entry');
        $entry->addChild('title', htmlspecialchars($book['title']));
        $queryA = "SELECT * FROM authors WHERE id IN(SELECT author_id FROM bookauthors WHERE book_id = :book_id)";
        $stmtA = $pdo->prepare($queryA);
        $stmtA->execute([':book_id' => $book['id']]);
        while ($authors = $stmtA->fetch(PDO::FETCH_ASSOC)) {
            $author = $entry->addChild('author');
            $author->addChild('name', htmlspecialchars($authors['last_name'] . ' ' . $authors['first_name'] . ' ' . $authors['middle_name']));
        }
        $entry->addChild('dc:language', $book['lang']);
        $entry->addChild('dc:issued', $book['date']);
        $entry->addChild('id', 'urn:uuid:' . $book['id']);
        $entry->addChild('updated', date(DATE_ATOM, strtotime($book['date'])));
        $entry->addChild('content', htmlspecialchars($book['annotation']))->addAttribute('type', 'text');
        
        $thm = $entry->addChild('link');
        $thm->addAttribute('rel', 'http://opds-spec.org/image/thumbnail');
        $thm->addAttribute('href', 'http://nd.alabuzya.ru/' . $book['cover']);
        $thm->addAttribute('type', 'image/jpg');
        $img = $entry->addChild('link');
        $img->addAttribute('rel', 'http://opds-spec.org/image');
        $img->addAttribute('href', 'http://nd.alabuzya.ru/' . $book['cover']);
        $img->addAttribute('type', 'image/jpg');
        
        // Ссылка на файл книги
        $link = $entry->addChild('link');
        $link->addAttribute('href', 'http://nd.alabuzya.ru/opds.php?getbook=' . $book['id']);
        $link->addAttribute('rel', 'http://opds-spec.org/acquisition');
        $link->addAttribute('type', 'application/fb2+xml');
    }

    // Вывод XML-документа
    $output = $xml->asXML();
    //$output = str_replace('&amp;', '&', $output);
    echo $output;
}


//Функция каталога OPDS(тут только фильтрация по директориям)
function generateOPDS($pdo, $page) {
    if ($page == '') {
        $page = null;
    }
    else{
        $keys=explode('---', $page);
        $section = $keys[0];
        $letter = $keys[1];
        if ($letter == '000') {
            $letter = null;
        }
    }
    // Устанавливаем заголовки для XML
    header('Content-Type: application/atom+xml; charset=utf-8');

    // Начало XML-документа
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><feed xmlns="http://www.w3.org/2005/Atom" xmlns:opds="http://opds-spec.org/2010/catalog"></feed>');

    // Основная информация о каталоге
    $xml->addChild('title', 'MyBooks OPDS Catalog');
    $xml->addChild('id', 'urn:uuid:' . uniqid());
    $xml->addChild('updated', date(DATE_ATOM));

    // Ссылка на себя
    $selfLink = $xml->addChild('link');
    $selfLink->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php');
    $selfLink->addAttribute('rel', 'self');
    $selfLink->addAttribute('type', 'application/atom+xml');

    // Ссылка на начальную страницу
    $startLink = $xml->addChild('link');
    $startLink->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php');
    $startLink->addAttribute('rel', 'start');
    $startLink->addAttribute('type', 'application/atom+xml');

    //Иконка
    $xml->addChild('icon', 'http://nd.alabuzya.ru/opds/img/books.png');

    // Если раздел не выбран, показываем главную страницу
    if ($page === null ) {
        // Разделы каталога
        $genresLink = $xml->addChild('entry');
        $genresLink->addChild('title', 'По жанрам');
        $genresLink->addChild('id', 'urn:uuid:genres');
        $genresLink->addChild('updated', date(DATE_ATOM));
        $link = $genresLink->addChild('link');
        $link->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php?page=genres---000');
        $link->addAttribute('rel', 'subsection');
        $link->addAttribute('type', 'application/atom+xml');

        $authorsLink = $xml->addChild('entry');
        $authorsLink->addChild('title', 'По авторам');
        $authorsLink->addChild('id', 'urn:uuid:authors');
        $authorsLink->addChild('updated', date(DATE_ATOM));
        $link = $authorsLink->addChild('link');
        $link->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php?page=authors---000');
        $link->addAttribute('rel', 'subsection');
        $link->addAttribute('type', 'application/atom+xml');

        $seriesLink = $xml->addChild('entry');
        $seriesLink->addChild('title', 'По сериям');
        $seriesLink->addChild('id', 'urn:uuid:series');
        $seriesLink->addChild('updated', date(DATE_ATOM));
        $link = $seriesLink->addChild('link');
        $link->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php?page=series---000');
        $link->addAttribute('rel', 'subsection');
        $link->addAttribute('type', 'application/atom+xml');
    } else {
    if($letter == null){


        // Если выбран раздел, показываем первые символы из базы (или жанры в зависимости от раздела)
        $query = '';
        switch ($section) {
            case 'genres':
                $query = "SELECT DISTINCT name FROM genres ORDER BY name ASC";
                $stmt = $pdo->query($query);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $name = $row['name'];
                    $entry = $xml->addChild('entry');
                    $entry->addChild('title', $name);
                    $entry->addChild('id', 'urn:uuid:' . $section . '-' . $name);
                    $entry->addChild('updated', date(DATE_ATOM));
                    $link = $entry->addChild('link');
                    $link->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php?page=genres---' . $name);
                    $link->addAttribute('rel', 'subsection');
                    $link->addAttribute('type', 'application/atom+xml');
                }
                break;
            case 'authors':
                $query = "SELECT DISTINCT LEFT(last_name, 1) AS letter FROM `authors` WHERE last_name IS NOT NULL AND last_name <>'' ORDER BY letter ASC";
                $stmt = $pdo->query($query);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $letter = $row['letter'];
                    $entry = $xml->addChild('entry');
                    $entry->addChild('title', $letter);
                    $entry->addChild('id', 'urn:uuid:' . $section . '-' . $letter);
                    $entry->addChild('updated', date(DATE_ATOM));
                    $link = $entry->addChild('link');
                    $link->addAttribute('rel', 'subsection');
                    $link->addAttribute('type', 'application/atom+xml');
                    $link->addAttribute('href', "http://nd.alabuzya.ru/opds/opds.php?page=authors---$letter");
                }
                break;
            case 'series':
                $query = "SELECT DISTINCT LEFT(name, 1) AS letter FROM series WHERE `name` IS NOT NULL ORDER BY letter ASC";
                $stmt = $pdo->query($query);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $letter = $row['letter'];
                    $entry = $xml->addChild('entry');
                    $entry->addChild('title', $letter);
                    $entry->addChild('id', 'urn:uuid:' . $section . '-' . $letter);
                    $entry->addChild('updated', date(DATE_ATOM));
                    $link = $entry->addChild('link');
                    $link->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php?page=series---'.$letter);
                    $link->addAttribute('rel', 'subsection');
                    $link->addAttribute('type', 'application/atom+xml');
                }
                break;
        }
    }
        // Если выбрана буква, показываем данные
        if ($letter != null) {
            $query = '';
            switch ($section) {
                case 'genres':
                    $query = "SELECT DISTINCT LEFT(title, 1) AS letter FROM books WHERE id IN(SELECT book_id FROM bookgenres WHERE genre_id IN(SELECT id FROM genres WHERE `name`=:genre_name)) ORDER BY letter ASC";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([':genre_name' => $letter]);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $entry = $xml->addChild('entry');
                        $entry->addChild('title', htmlspecialchars($row['letter']));
                        $entry->addChild('id', 'urn:uuid:' . $section . '-' . $row['letter']);
                        $entry->addChild('updated', date(DATE_ATOM));
                        $link = $entry->addChild('link');
                        $link->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php?genre='.$letter.'---'. $row['letter']);
                        $link->addAttribute('rel', 'subsection');
                        $link->addAttribute('type', 'application/atom+xml');
                    }
                    break;
                case 'authors':
                    $query = "SELECT CONCAT(last_name,' ',first_name,' ',middle_name) AS author, id FROM authors WHERE last_name LIKE :letter ORDER BY last_name ASC";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([':letter' => $letter . '%']);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $entry = $xml->addChild('entry');
                        $entry->addChild('title', htmlspecialchars($row['author']));
                        $entry->addChild('id', 'urn:uuid:' . $section . '-' . $row['author']);
                        $entry->addChild('updated', date(DATE_ATOM));
                        $link = $entry->addChild('link');
                        $link->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php?author=' . $row['id']);
                        $link->addAttribute('rel', 'subsection');
                        $link->addAttribute('type', 'application/atom+xml');
                    }
                    break;
                case 'series':
                    $query = "SELECT DISTINCT `name` FROM series WHERE `name` LIKE :letter ORDER BY name ASC";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([':letter' => $letter . '%']);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $entry = $xml->addChild('entry');
                        $entry->addChild('title', htmlspecialchars($row['name']));
                        $entry->addChild('id', 'urn:uuid:' . $section . '-' . $row['name']);
                        $entry->addChild('updated', date(DATE_ATOM));
                        $link = $entry->addChild('link');
                        $link->addAttribute('href', 'http://nd.alabuzya.ru/opds/opds.php?series=' . $row['name']);
                        $link->addAttribute('rel', 'subsection');
                        $link->addAttribute('type', 'application/atom+xml');
                    }
                    break;
            }

        }
    }

    // Вывод XML-документа
    $output = $xml->asXML();
    //$output = str_replace('&amp;', '&', $output);
    echo $output;
}