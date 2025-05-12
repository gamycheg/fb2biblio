<?php
/** Функции парсинга fb2 */
//для теста
//function logMessage($message) {
//    file_put_contents('scanner.log', $message . PHP_EOL, FILE_APPEND);
//}


//Собираем основные данные о книге
function getBookInfo($filePath) {
    $book = array();   // Массив для хранения данных книги
    // Положим путь к файлу в переменную book
    $book['path'] = $filePath;
    // Проверяем, существует ли файл
    if (!file_exists($filePath)) {
        logMessage(date("d-m-Y H:i:s") . " - Файл $filePath не найден.");
        return false;
    }
    
    $fileContent = file_get_contents($filePath);
    if ($fileContent === false || empty($fileContent)) {
        logMessage(date("d-m-Y H:i:s") . " - Файл $filePath пустой или не может быть прочитан.");
        return false;
    }
    //Объявим книгу как объект XML и проверим файл на наличие ошибок
    try {
        $xml = new SimpleXMLElement($fileContent, LIBXML_NOWARNING | LIBXML_NOERROR);
    } catch (Exception $e) {
        logMessage(date("d-m-Y H:i:s")." - Ошибка при загрузке XML в файле $filePath: " . $e->getMessage());
        // Если возникла ошибка, возвращаем пустой массив
        return false;
    }

    // Проверяем наличие элемента <book-title>
    $title1 = $xml->description->{'title-info'}->{'book-title'};
    $title = ''; // Инициализируем переменную $title
    if (empty($title1)) {
        // Если название нет то возвращаем имя файла без расширения
        $fileName = basename($filePath);
        $title =  $fileName;
    }
    else{
        foreach($title1 as $node){
            $title .= trim(strip_tags($node->asXML())); // Получаем XML-строку для каждого узла 
        }
    }
    if($title == ''){
        // Если название нет то возвращаем имя файла без расширения
        $fileName = basename($filePath);
        $title =  $fileName;
    }
    $book['title'] = $title;

    // Проверяем наличие элемента <annotation>
    $annotation1 = $xml->description->{'title-info'}->annotation;
    // Убираем сам тег <annotation> из результата
    $annotation = '';
    foreach($annotation1 as $node){
        $annotation .= strip_tags($node->asXML()); // Получаем XML-строку для каждого узла 
    }
    if (!empty($annotation)) {
        $book['annotation'] = $annotation;
    } else {
        $book['annotation'] = ''; // Если аннотация отсутствует, устанавливаем пустую строку
    }

    // Проверяем наличие элемента <lang>
    $lang = $xml->description->{'title-info'}->lang;
    if (!empty($lang)) {
        $book['lang'] = (string)$lang;
    } else {
        $book['lang'] = ''; // Если язык отсутствует, устанавливаем пустую строку
    }
    // Проверяем наличие элемента <date>
    $date = $xml->description->{'title-info'}->{'date'};
    if (!empty($date)) {
        $book['date'] = (string)$date;
    } else {
        $book['date'] = ''; // Если дата отсутствует, устанавливаем пустую строку
    }
    // Проверяем наличие элемента <author>
    $author1 = $xml->description->{'title-info'}->author;
    $author = array(); // Инициализируем переменную $author
    if (!empty($author1)) {
        $i=0; // Счетчик для авторов
        foreach($author1 as $node){
            $author[$i]['first-name']= (isset($node->{'first-name'}))?strip_tags($node->{'first-name'}):''; 
            $author[$i]['last-name']= (isset($node->{'last-name'}))?strip_tags($node->{'last-name'}):''; 
            $author[$i]['middle-name']= (isset($node->{'middle-name'}))?strip_tags($node->{'middle-name'}):'';
            $i++; // Увеличиваем счетчик авторов
        }
    } else {
        $author[0] = ''; // Если автор отсутствует, устанавливаем пустую строку''
    }
    $book['author'] = $author;
    // Проверяем наличие элемента <genre>
    $genre1 = $xml->description->{'title-info'}->genre;
    $genre = array(); // Инициализируем переменную $genre
    if (!empty($genre1)) {
        foreach($genre1 as $node){
            $genre[]= trim(strip_tags($node->asXML())); // Получаем XML-строку для каждого узла 
        }
    } else {
        $genre[0] = "unknown"; // Если жанр отсутствует, устанавливаем пустую строку
    }
    if($genre[0] == ''){
        $genre[0] = "unknown"; // Если жанр отсутствует, устанавливаем пустую строку
    }
    $book['genre'] = $genre;

    // Ищем серию если она есть
    $series1 = $xml->description->{'title-info'}->sequence;
    $series = array(); // Инициализируем переменную $series
    if (!empty($series1)) {
        $i=0; // Счетчик для серий
        foreach($series1 as $node){
            $series = $node->attributes(); // Получаем атрибуты элемента <sequence>
            if($series['name']==''){
                continue; // Если атрибут name пустой, пропускаем итерацию
            }
            $book['series'][$i]['name'] = (string)$series['name']; // Получаем значение атрибута name
            $book['series'][$i]['number'] = (string)$series['number']; // Получаем значение атрибута number
            $i++; // Увеличиваем счетчик серий
        }
    } else {
        $book['series'][0] = ''; // Если серия отсутствует, устанавливаем пустую строку
    }

    //Проверим и сохраним обложку
    $cover1 = extractCoverFromFB2($filePath); // Извлекаем обложку из FB2
    if (!empty($cover1)) {
        $coverPath = 'img/' . uniqid() . '.jpg'; // Генерируем уникальное имя для обложки
        $fullCoverPath = '../' . $coverPath;
    
        // Сохраняем обложку с минимальным размером
        if (saveCoverImage($cover1, $fullCoverPath)) {
            $book['cover'] = $coverPath; // Сохраняем путь к обложке в массив книги
        } else {
            $book['cover'] = ''; // Если сохранение не удалось
        }
    } else {
        $book['cover'] = ''; // Если обложка отсутствует
    }


    return $book; // Возвращаем массив с данными книги
}

// Отдельная функция для извлечения обложки из FB2
function extractCoverFromFB2($fb2Content) {
    $fileContent = file_get_contents($fb2Content);
    $xml = new SimpleXMLElement($fileContent, LIBXML_NOWARNING | LIBXML_NOERROR);

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

function saveCoverImage($base64Image, $outputPath, $maxWidth = 200, $maxHeight = 300) {
    // Декодируем изображение из Base64
    $imageData = base64_decode($base64Image);
    if ($imageData === false) {
        return false; // Если декодирование не удалось
    }

    // Создаём изображение из строки
    $sourceImage = imagecreatefromstring($imageData);
    if ($sourceImage === false) {
        return false; // Если создание изображения не удалось
    }

    // Получаем размеры исходного изображения
    $originalWidth = imagesx($sourceImage);
    $originalHeight = imagesy($sourceImage);

    // Вычисляем новые размеры, сохраняя пропорции
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    $newWidth = (int)($originalWidth * $ratio);
    $newHeight = (int)($originalHeight * $ratio);

    // Создаём новое изображение с уменьшенными размерами
    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

    // Сохраняем изображение в файл с минимальным качеством
    imagejpeg($resizedImage, $outputPath, 75); // 75 — качество JPEG (можно уменьшить для ещё меньшего размера)

    // Освобождаем память
    imagedestroy($sourceImage);
    imagedestroy($resizedImage);

    return true;
}


?>