-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Апр 27 2025 г., 07:44
-- Версия сервера: 8.0.34-26-beget-1-1
-- Версия PHP: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `kosfaton_nft`
--

-- --------------------------------------------------------

--
-- Структура таблицы `active_offers`
--
-- Создание: Янв 20 2025 г., 09:07
--

DROP TABLE IF EXISTS `active_offers`;
CREATE TABLE `active_offers` (
  `id` int NOT NULL,
  `buyer` bigint NOT NULL,
  `seller` bigint NOT NULL,
  `status` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Структура таблицы `market`
--
-- Создание: Фев 16 2025 г., 12:16
--

DROP TABLE IF EXISTS `market`;
CREATE TABLE `market` (
  `id` int NOT NULL,
  `owner` bigint NOT NULL,
  `category` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `nameNumber` double NOT NULL,
  `price` int NOT NULL,
  `number` int NOT NULL,
  `patternName` varchar(25) NOT NULL,
  `patternNumber` double NOT NULL,
  `backName` varchar(25) NOT NULL,
  `backNumber` double NOT NULL,
  `startDate` bigint NOT NULL,
  `endDate` bigint NOT NULL,
  `rarity` varchar(25) NOT NULL,
  `status` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Структура таблицы `msgsForSend`
--
-- Создание: Янв 20 2025 г., 09:17
--

DROP TABLE IF EXISTS `msgsForSend`;
CREATE TABLE `msgsForSend` (
  `id` int NOT NULL,
  `target` bigint NOT NULL,
  `message` longtext NOT NULL,
  `additional` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Структура таблицы `userdata`
--
-- Создание: Янв 20 2025 г., 09:06
--

DROP TABLE IF EXISTS `userdata`;
CREATE TABLE `userdata` (
  `id` int NOT NULL,
  `tg_id` bigint NOT NULL,
  `lastIP` varchar(255) NOT NULL,
  `balance` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Структура таблицы `withdraw_requests`
--
-- Создание: Янв 22 2025 г., 12:32
--

DROP TABLE IF EXISTS `withdraw_requests`;
CREATE TABLE `withdraw_requests` (
  `id` int NOT NULL,
  `user` bigint NOT NULL,
  `sum` int NOT NULL,
  `type` varchar(25) NOT NULL,
  `wallet` varchar(255) NOT NULL,
  `status` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `active_offers`
--
ALTER TABLE `active_offers`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `market`
--
ALTER TABLE `market`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `msgsForSend`
--
ALTER TABLE `msgsForSend`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `userdata`
--
ALTER TABLE `userdata`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `withdraw_requests`
--
ALTER TABLE `withdraw_requests`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `active_offers`
--
ALTER TABLE `active_offers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `market`
--
ALTER TABLE `market`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `msgsForSend`
--
ALTER TABLE `msgsForSend`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `userdata`
--
ALTER TABLE `userdata`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `withdraw_requests`
--
ALTER TABLE `withdraw_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
