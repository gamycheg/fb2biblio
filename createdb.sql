-- --------------------------------------------------------
-- Хост:                         127.0.0.1
-- Версия сервера:               9.1.0 - MySQL Community Server - GPL
-- Операционная система:         Win64
-- HeidiSQL Версия:              12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Дамп структуры базы данных books
CREATE DATABASE IF NOT EXISTS `books` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `books`;

-- Дамп структуры для таблица books.authors
CREATE TABLE IF NOT EXISTS `authors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` text COLLATE utf8mb4_general_ci,
  `last_name` text COLLATE utf8mb4_general_ci,
  `middle_name` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7918 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица books.badbooks
CREATE TABLE IF NOT EXISTS `badbooks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` text COLLATE utf8mb4_general_ci NOT NULL,
  `path` text COLLATE utf8mb4_general_ci NOT NULL,
  `error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица books.bookauthors
CREATE TABLE IF NOT EXISTS `bookauthors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `book_id` int NOT NULL DEFAULT '0',
  `author_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=21368 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица books.bookgenres
CREATE TABLE IF NOT EXISTS `bookgenres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `book_id` int NOT NULL DEFAULT '0',
  `genre_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=21334 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица books.books
CREATE TABLE IF NOT EXISTS `books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` text COLLATE utf8mb4_general_ci NOT NULL,
  `cover` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `annotation` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `lang` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `translators` text COLLATE utf8mb4_general_ci,
  `path` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `date` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9801 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица books.booktranslators
CREATE TABLE IF NOT EXISTS `booktranslators` (
  `id` int NOT NULL AUTO_INCREMENT,
  `book_id` int NOT NULL DEFAULT '0',
  `translator_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=7521 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=FIXED;

-- Экспортируемые данные не выделены.

-- Дамп структуры для представление books.fullbookauthor
-- Создание временной таблицы для обработки ошибок зависимостей представлений
CREATE TABLE `fullbookauthor` (
	`id` INT NOT NULL,
	`title` TEXT NOT NULL COLLATE 'utf8mb4_general_ci',
	`cover` MEDIUMTEXT NOT NULL COLLATE 'utf8mb4_general_ci',
	`annotation` MEDIUMTEXT NOT NULL COLLATE 'utf8mb4_general_ci',
	`lang` TINYTEXT NULL COLLATE 'utf8mb4_general_ci',
	`path` TEXT NOT NULL COLLATE 'utf8mb4_general_ci',
	`date` TEXT NULL COLLATE 'utf8mb4_general_ci',
	`author_id` INT NOT NULL,
	`author_name` MEDIUMTEXT NULL COLLATE 'utf8mb4_general_ci'
) ENGINE=MyISAM;

-- Дамп структуры для представление books.fullbookgenres
-- Создание временной таблицы для обработки ошибок зависимостей представлений
CREATE TABLE `fullbookgenres` (
	`id` INT NULL,
	`title` TEXT NULL COLLATE 'utf8mb4_general_ci',
	`cover` MEDIUMTEXT NULL COLLATE 'utf8mb4_general_ci',
	`annotation` MEDIUMTEXT NULL COLLATE 'utf8mb4_general_ci',
	`lang` TINYTEXT NULL COLLATE 'utf8mb4_general_ci',
	`path` TEXT NULL COLLATE 'utf8mb4_general_ci',
	`date` TEXT NULL COLLATE 'utf8mb4_general_ci',
	`genre_id` INT NOT NULL,
	`genre_name` TEXT NULL COLLATE 'utf8mb4_general_ci',
	`author_name` TEXT NULL COLLATE 'utf8mb4_general_ci'
) ENGINE=MyISAM;

-- Дамп структуры для представление books.fullbookseries
-- Создание временной таблицы для обработки ошибок зависимостей представлений
CREATE TABLE `fullbookseries` (
	`id` INT NULL,
	`title` TEXT NULL COLLATE 'utf8mb4_general_ci',
	`cover` MEDIUMTEXT NULL COLLATE 'utf8mb4_general_ci',
	`annotation` MEDIUMTEXT NULL COLLATE 'utf8mb4_general_ci',
	`lang` TINYTEXT NULL COLLATE 'utf8mb4_general_ci',
	`path` TEXT NULL COLLATE 'utf8mb4_general_ci',
	`date` TEXT NULL COLLATE 'utf8mb4_general_ci',
	`series_id` INT NOT NULL,
	`series_name` TEXT NOT NULL COLLATE 'utf8mb4_general_ci',
	`series_number` TEXT NULL COLLATE 'utf8mb4_general_ci',
	`author_name` TEXT NULL COLLATE 'utf8mb4_general_ci'
) ENGINE=MyISAM;

-- Дамп структуры для таблица books.genres
CREATE TABLE IF NOT EXISTS `genres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_general_ci,
  `section` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=21334 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица books.series
CREATE TABLE IF NOT EXISTS `series` (
  `id` int NOT NULL AUTO_INCREMENT,
  `book_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` text COLLATE utf8mb4_general_ci NOT NULL,
  `number` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8755 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица books.translators
CREATE TABLE IF NOT EXISTS `translators` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `last_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `middle_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=4607 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- Экспортируемые данные не выделены.

-- Удаление временной таблицы и создание окончательной структуры представления
DROP TABLE IF EXISTS `fullbookauthor`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `fullbookauthor` AS select `b`.`id` AS `id`,`b`.`title` AS `title`,`b`.`cover` AS `cover`,`b`.`annotation` AS `annotation`,`b`.`lang` AS `lang`,`b`.`path` AS `path`,`b`.`date` AS `date`,`a`.`id` AS `author_id`,concat(`a`.`last_name`,' ',`a`.`first_name`,' ',`a`.`middle_name`) AS `author_name` from ((`books` `b` join `bookauthors` `ba` on((`ba`.`book_id` = `b`.`id`))) join `authors` `a` on((`ba`.`author_id` = `a`.`id`)))
;

-- Удаление временной таблицы и создание окончательной структуры представления
DROP TABLE IF EXISTS `fullbookgenres`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `fullbookgenres` AS select `b`.`id` AS `id`,`b`.`title` AS `title`,`b`.`cover` AS `cover`,`b`.`annotation` AS `annotation`,`b`.`lang` AS `lang`,`b`.`path` AS `path`,`b`.`date` AS `date`,`g`.`id` AS `genre_id`,`g`.`name` AS `genre_name`,(select group_concat(`fullbookauthor`.`author_name` separator ',') AS `author_name` from `fullbookauthor` where (`fullbookauthor`.`id` = `b`.`id`)) AS `author_name` from ((`books` `b` join `bookgenres` `bg` on((`bg`.`genre_id` = `b`.`id`))) join `genres` `g` on((`g`.`id` = `bg`.`genre_id`)))
;

-- Удаление временной таблицы и создание окончательной структуры представления
DROP TABLE IF EXISTS `fullbookseries`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `fullbookseries` AS select `b`.`id` AS `id`,`b`.`title` AS `title`,`b`.`cover` AS `cover`,`b`.`annotation` AS `annotation`,`b`.`lang` AS `lang`,`b`.`path` AS `path`,`b`.`date` AS `date`,`s`.`id` AS `series_id`,`s`.`name` AS `series_name`,`s`.`number` AS `series_number`,(select group_concat(`fullbookauthor`.`author_name` separator ',') AS `author_name` from `fullbookauthor` where (`fullbookauthor`.`id` = `b`.`id`)) AS `author_name` from (`books` `b` join `series` `s` on((`s`.`book_id` = `b`.`id`)))
;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
