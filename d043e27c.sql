-- phpMyAdmin SQL Dump
-- version 4.9.11
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Erstellungszeit: 03. Jun 2025 um 12:03
-- Server-Version: 10.6.21-MariaDB-0ubuntu0.22.04.2-log
-- PHP-Version: 7.4.33-nmm7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Tabellenstruktur für Tabelle `buzz_status`
-- --------------------------------------------------------

CREATE TABLE `buzz_status` (
  `room_id` int(11) NOT NULL,
  `locked` tinyint(1) DEFAULT 0,
  `buzzer_user_id` int(11) DEFAULT NULL,
  `buzz_time` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabellenstruktur für Tabelle `rooms`
-- --------------------------------------------------------

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabellenstruktur für Tabelle `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Indizes der Tabellen
-- --------------------------------------------------------

ALTER TABLE `buzz_status`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `buzzer_user_id` (`buzzer_user_id`);

ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

-- --------------------------------------------------------
-- AUTO_INCREMENT-Werte
-- --------------------------------------------------------

ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Fremdschlüssel-Constraints
-- --------------------------------------------------------

ALTER TABLE `buzz_status`
  ADD CONSTRAINT `buzz_status_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `buzz_status_ibfk_2` FOREIGN KEY (`buzzer_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

COMMIT;

-- --------------------------------------------------------
-- Event zum automatischen Löschen alter Räume
-- --------------------------------------------------------

DELIMITER //

CREATE EVENT IF NOT EXISTS `delete_old_rooms`
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
  DELETE FROM `rooms`
  WHERE `created_at` < NOW() - INTERVAL 24 HOUR;
END//

DELIMITER ;

-- --------------------------------------------------------
-- Optional: Event Scheduler aktivieren (falls erlaubt)
-- --------------------------------------------------------
-- SET GLOBAL event_scheduler = ON;

-- Wiederherstellen vorheriger Einstellungen
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;