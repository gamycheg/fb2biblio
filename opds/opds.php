<?php
include_once 'funct_opds.php';
set_time_limit(0); // Отключает ограничение времени выполнения
ini_set('memory_limit', '-1'); // Убирает ограничение на использование памяти
//Проверка переменных и генерация OPDS
if(isset($_GET['author'])){
    $author = $_GET['author'];
    generateOPDSAuthor($pdo, $author);
}
elseif(isset($_GET['genre'])){
    $genres = $_GET['genre'];
    generateOPDSGenres($pdo, $genres);
}
elseif(isset($_GET['series'])){
    $series = $_GET['series'];
    generateOPDSSeries($pdo, $series);
}
elseif(isset($_GET['getbook'])){
    $book = $_GET['getbook'];
    getBook($pdo, $book);
}
else{
    $page = isset($_GET['page']) ? $_GET['page'] : ''; 
    generateOPDS($pdo, $page);
}
