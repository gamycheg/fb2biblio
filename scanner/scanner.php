<?php
require_once '../vendor/autoload.php';
include_once 'funct_back.php';
error_reporting(E_ERROR | E_WARNING | E_PARSE);
set_time_limit(0); // Отключает ограничение времени выполнения
ini_set('memory_limit', '-1'); // Убирает ограничение на использование памяти

// Укажите директорию для сканирования
$directoryToScan = 'books'; // Пусть к директории, которую нужно сканировать
scanDirectory($directoryToScan, $pdo);
?>