-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 04, 2025 at 05:00 PM
-- Server version: 8.2.0
-- PHP Version: 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `librow_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
CREATE TABLE IF NOT EXISTS `books` (
  `book_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `description` text,
  `category` varchar(150) NOT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` year DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `available` int NOT NULL DEFAULT '1',
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`book_id`)
) ENGINE=MyISAM AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `author`, `description`, `category`, `publisher`, `publication_year`, `quantity`, `available`, `date_added`, `date_updated`) VALUES
(6, '1984', 'George Orwell', 'A dystopian story about totalitarian surveillance and control.', 'Dystopian', 'Secker & Warburg', '1949', 8, 8, '2025-12-04 09:02:44', '2025-12-04 09:02:44'),
(5, 'To Kill a Mockingbird', 'Harper Lee', 'A novel about justice and racism in the American South.', 'Fiction', 'J.B. Lippincott & Co.', '1960', 5, 3, '2025-12-04 09:02:44', '2025-12-04 09:02:44'),
(4, 'The Great Gatsby', 'F. Scott Fitzgerald', 'A classic novel set during the Jazz Age.', 'Fiction', 'Scribner', '1925', 10, 5, '2025-12-04 09:02:44', '2025-12-04 16:06:17'),
(7, 'The Catcher in the Rye', 'J.D. Salinger', 'A coming-of-age story about teenage alienation.', 'Fiction', 'Little, Brown and Company', '1951', 12, 10, '2025-12-04 09:02:44', '2025-12-04 09:02:44'),
(8, 'Pride and Prejudice', 'Jane Austen', 'A romantic novel about manners, marriage, and social standing.', 'Romance', 'T. Egerton', '0000', 6, 4, '2025-12-04 09:02:44', '2025-12-04 16:37:51'),
(9, 'Brave New World', 'Aldous Huxley', 'A futuristic novel exploring technology and social order.', 'Science Fiction', 'Chatto & Windus', '1932', 9, 5, '2025-12-04 09:02:44', '2025-12-04 16:07:57'),
(10, 'Moby-Dick', 'Herman Melville', 'A sailor recounts the obsessive quest to hunt a white whale.', 'Adventure', 'Harper & Brothers', '0000', 4, 2, '2025-12-04 09:02:44', '2025-12-04 09:02:44'),
(11, 'The Hobbit', 'J.R.R. Tolkien', 'A fantasy adventure following Bilbo Baggins.', 'Fantasy', 'George Allen & Unwin', '1937', 15, 14, '2025-12-04 09:02:44', '2025-12-04 16:51:27'),
(12, 'Fahrenheit 451', 'Ray Bradbury', 'A future where books are banned and burned.', 'Dystopian', 'Ballantine Books', '1953', 7, 7, '2025-12-04 09:02:44', '2025-12-04 09:02:44'),
(13, 'The Lord of the Rings', 'J.R.R. Tolkien', 'An epic fantasy saga set in Middle-earth.', 'Fantasy', 'George Allen & Unwin', '1954', 20, 19, '2025-12-04 09:02:44', '2025-12-04 09:02:44'),
(14, 'The Alchemist', 'Paulo Coelho', 'A novel about personal destiny and spiritual growth.', 'Fiction', 'HarperOne', '1988', 14, 13, '2025-12-04 09:02:44', '2025-12-04 09:02:44'),
(15, 'The Da Vinci Code', 'Dan Brown', 'A thriller involving secret societies and religious mysteries.', 'Thriller', 'Doubleday', '2003', 18, 17, '2025-12-04 09:02:44', '2025-12-04 09:02:44'),
(16, 'The Hunger Games', 'Suzanne Collins', 'A dystopian survival competition between young tributes.', 'Dystopian', 'Scholastic Press', '2008', 22, 20, '2025-12-04 09:02:44', '2025-12-04 09:02:44'),
(17, 'Harry Potter and the Philosopher\'s Stone', 'J.K. Rowling', 'A boy discovers he is a wizard and attends Hogwarts.', 'Fantasy', 'Bloomsbury', '1997', 25, 25, '2025-12-04 09:02:44', '2025-12-04 09:02:44'),
(18, 'The Fault in Our Stars', 'John Green', 'A love story between two teens dealing with illness.', 'Romance', 'Dutton Books', '2012', 11, 3, '2025-12-04 09:02:44', '2025-12-04 16:05:41'),
(19, 'The Outsiders', 'S.E. Hinton', 'A story about class conflict between the Greasers and Socs.', 'Young Adult', 'Viking Press', '1967', 9, 5, '2025-12-04 09:02:44', '2025-12-04 09:02:44'),
(20, 'The Girl with the Dragon Tattoo', 'Stieg Larsson', 'A crime mystery involving investigative journalism.', 'Mystery', 'Norstedts Förlag', '2005', 13, 8, '2025-12-04 09:02:44', '2025-12-04 16:52:21'),
(21, 'The Book Thief', 'Markus Zusak', 'A young girl lives through World War II in Germany.', 'Historical Fiction', 'Picador', '2005', 6, 3, '2025-12-04 09:02:44', '2025-12-04 15:37:18'),
(22, 'The Shining', 'Stephen King', 'A psychological horror story set in an isolated hotel.', 'Horror', 'Doubleday', '1977', 13, 11, '2025-12-04 09:02:44', '2025-12-04 15:37:00'),
(23, 'Life of Pi', 'Yann Martel', 'A boy is stranded at sea with a Bengal tiger.', 'Adventure', 'Knopf Canada', '2001', 10, 10, '2025-12-04 09:02:44', '2025-12-04 09:02:44'),
(32, 'The Name of the Wind', 'Patrick Rothfuss', 'A fantasy novel following young Kvothe as he recounts his journey from gifted child to legendary figure.', 'Fantasy', 'DAW Books', '2007', 2, 0, '2025-12-04 15:18:26', '2025-12-04 15:19:13'),
(35, 'The Martian', 'Andy Weir', 'A stranded astronaut fights for survival on Mars using science, engineering, and resourcefulness.', 'Science Fiction', 'Crown Publishing', '2011', 3, 0, '2025-12-04 15:18:26', '2025-12-04 15:19:13');

-- --------------------------------------------------------

--
-- Table structure for table `borrow`
--

DROP TABLE IF EXISTS `borrow`;
CREATE TABLE IF NOT EXISTS `borrow` (
  `borrow_id` int NOT NULL AUTO_INCREMENT,
  `book_id` int NOT NULL,
  `user_id` int NOT NULL,
  `date_borrowed` date NOT NULL,
  `date_due` date NOT NULL,
  `date_returned` date DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  PRIMARY KEY (`borrow_id`),
  KEY `book_id` (`book_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `borrow`
--

INSERT INTO `borrow` (`borrow_id`, `book_id`, `user_id`, `date_borrowed`, `date_due`, `date_returned`, `status`) VALUES
(14, 20, 1, '2025-12-05', '2026-01-02', NULL, 'borrowed'),
(13, 20, 1, '2025-12-05', '2026-01-02', NULL, 'borrowed'),
(12, 11, 1, '2025-12-05', '2025-12-26', NULL, 'borrowed'),
(11, 9, 1, '2025-12-05', '2025-12-19', NULL, 'borrowed'),
(10, 8, 1, '2025-12-05', '2025-12-12', '2025-12-05', 'returned'),
(9, 4, 1, '2025-12-05', '2025-12-12', NULL, 'borrowed');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_picture` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_number` (`id_number`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `id_number`, `password`, `id_picture`, `status`, `created_at`) VALUES
(1, 'Rafael Miguel Sales', '2225072', '$2y$10$8FVC8w9GKauEaKN3kBn6XO4vVT6OFbtXmC0a8CYbRN0IcH5naQOku', '1764837160_paeng 2x2.jpg', 'Pending', '2025-12-04 08:32:40');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
