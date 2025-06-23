-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 23, 2025 at 05:57 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `library_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `author` varchar(100) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `total_copies` int(11) DEFAULT 1,
  `book_fine` int(11) NOT NULL,
  `available_copies` int(11) DEFAULT 1,
  `published_year` year(4) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `is_ebook` tinyint(1) DEFAULT 0,
  `ebook_file` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `published_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `author`, `category`, `isbn`, `total_copies`, `book_fine`, `available_copies`, `published_year`, `cover_image`, `is_ebook`, `ebook_file`, `description`, `published_date`) VALUES
(1, 'The Great Gatsby', 'F. Scott Fitzgerald', 'Classic', '9780743273565', 5, 50, 3, NULL, 'gatsby.jpg', 0, NULL, 'A story of decadence, excess, and the American Dream in the 1920s.', '1925-04-10'),
(2, 'To Kill a Mockingbird', 'Harper Lee', 'Fiction', '9780061120084', 3, 50, 2, NULL, 'mockingbird.jpg', 0, NULL, 'A powerful story of racial injustice and the loss of innocence in the American South.', '1960-07-11'),
(3, '1984', 'George Orwell', 'Dystopian', '9780451524935', 4, 50, 2, NULL, '1984.jpg', 0, NULL, 'A dystopian novel about totalitarianism, surveillance, and government control.', '1949-06-08'),
(4, 'Pride and Prejudice', 'Jane Austen', 'Romance', '9780141439518', 6, 50, 4, NULL, 'pride-and-prejudice.jpg', 0, NULL, 'A romantic novel about the emotional development of Elizabeth Bennet.', '1813-01-28'),
(5, 'The Hobbit', 'J.R.R. Tolkien', 'Fantasy', '9780547928227', 4, 50, 4, NULL, 'the-hobbit.jpg', 0, NULL, 'A fantasy novel about the adventures of Bilbo Baggins.', '1937-09-21'),
(6, 'The Catcher in the Rye', 'J.D. Salinger', 'Fiction', '9780316769488', 3, 50, 0, NULL, 'the-catcher-in-the-rye.png', 0, NULL, 'A story about alienation and teenage angst in post-war America.', '1951-07-16'),
(7, 'The Lord of the Rings', 'J.R.R. Tolkien', 'Fantasy', '9780544003415', 5, 50, 5, NULL, 'lord-of-the-rings.jpg', 0, NULL, 'An epic high fantasy novel about the quest to destroy the One Ring.', '1954-07-29'),
(8, 'The Alchemist', 'Paulo Coelho', 'Fiction', '9780062315007', 4, 50, 4, NULL, 'the-alchemist.jpg', 0, NULL, 'A philosophical book about a young shepherd on a journey to find his personal legend.', '1988-01-01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
