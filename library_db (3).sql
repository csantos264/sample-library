-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2025 at 11:46 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
(1, 'The Great Gatsby', 'F. Scott Fitzgerald', 'Classic', '9780743273565', 5, 50, 4, NULL, 'gatsby.jpg', 0, NULL, 'A story of decadence, excess, and the American Dream in the 1920s.', '1925-04-10'),
(2, 'To Kill a Mockingbird', 'Harper Lee', 'Fiction', '9780061120084', 3, 50, 3, NULL, 'mockingbird.jpg', 0, NULL, 'A powerful story of racial injustice and the loss of innocence in the American South.', '1960-07-11'),
(3, '1984', 'George Orwell', 'Dystopian', '9780451524935', 4, 50, 4, NULL, '1984.jpg', 0, NULL, 'A dystopian novel about totalitarianism, surveillance, and government control.', '1949-06-08'),
(4, 'Pride and Prejudice', 'Jane Austen', 'Romance', '9780141439518', 6, 50, 5, NULL, 'pride-and-prejudice.jpg', 0, NULL, 'A romantic novel about the emotional development of Elizabeth Bennet.', '1813-01-28'),
(5, 'The Hobbit', 'J.R.R. Tolkien', 'Fantasy', '9780547928227', 4, 50, 4, NULL, 'the-hobbit.jpg', 0, NULL, 'A fantasy novel about the adventures of Bilbo Baggins.', '1937-09-21'),
(6, 'The Catcher in the Rye', 'J.D. Salinger', 'Fiction', '9780316769488', 3, 50, 1, NULL, 'the-catcher-in-the-rye.png', 0, NULL, 'A story about alienation and teenage angst in post-war America.', '1951-07-16'),
(7, 'The Lord of the Rings', 'J.R.R. Tolkien', 'Fantasy', '9780544003415', 5, 50, 5, NULL, 'lord-of-the-rings.jpg\r\n', 0, NULL, 'An epic high fantasy novel about the quest to destroy the One Ring.', '1954-07-29'),
(8, 'The Alchemist', 'Paulo Coelho', 'Fiction', '9780062315007', 4, 50, 4, NULL, 'the-alchemist.jpg', 0, NULL, 'A philosophical book about a young shepherd on a journey to find his personal legend.', '1988-01-01');

-- --------------------------------------------------------

--
-- Table structure for table `borrow_records`
--

CREATE TABLE `borrow_records` (
  `borrow_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `book_id` int(11) DEFAULT NULL,
  `borrow_date` date DEFAULT curdate(),
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  `fine_paid` float DEFAULT 0,
  `fine_paid_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrow_records`
--

INSERT INTO `borrow_records` (`borrow_id`, `user_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `status`, `fine_paid`, `fine_paid_date`) VALUES
(1, 6, 3, '2025-06-22', '2025-07-06', '2025-06-22', 'borrowed', 0, NULL),
(2, 6, 3, '2025-06-22', '2025-06-21', '2025-06-23', 'borrowed', 0, NULL),
(3, 6, 6, '2025-06-22', '2025-06-21', '2025-06-22', 'returned', 1, '2025-06-23 01:03:27'),
(4, 6, 3, '2025-06-22', '2025-06-21', '2025-06-23', 'borrowed', 0, NULL),
(5, 6, 4, '2025-06-22', '2025-06-21', '2025-06-22', 'returned', 1, '2025-06-23 01:34:38'),
(6, 8, 6, '2025-06-22', '2025-07-06', '2025-06-22', 'returned', 0, NULL),
(7, 8, 1, '2025-06-22', '2025-06-20', NULL, 'borrowed', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `extension_requests`
--

CREATE TABLE `extension_requests` (
  `request_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_date` datetime DEFAULT current_timestamp(),
  `new_return_date` date NOT NULL,
  `status` enum('pending','approved','denied') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fines`
--

CREATE TABLE `fines` (
  `fine_id` int(11) NOT NULL,
  `borrow_id` int(11) DEFAULT NULL,
  `amount` decimal(6,2) NOT NULL,
  `paid` tinyint(1) DEFAULT 0,
  `payment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fines`
--

INSERT INTO `fines` (`fine_id`, `borrow_id`, `amount`, `paid`, `payment_id`) VALUES
(1, 5, 50.00, 1, NULL),
(2, 5, 50.00, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` enum('fine','reservation','ebook') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','completed','failed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `user_id`, `amount`, `payment_type`, `reference_id`, `payment_method`, `payment_date`, `status`) VALUES
(1, 6, 50.00, '', 2, 'cash', '2025-06-23 01:34:38', '');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `book_id` int(11) DEFAULT NULL,
  `reservation_date` date DEFAULT curdate(),
  `status` enum('active','cancelled','fulfilled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('user','admin') DEFAULT 'user',
  `registered_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `user_type`, `registered_at`) VALUES
(2, 'Kyrie Earl Gabriel P. Amper', 'kyrie@gmail.com', '$2y$10$6ccniTsNW.EEMFVyOf6ISO5fPR/Do3j4qvABEl7ENoWmYJSpS2V8m', 'user', '2025-06-19 04:42:31'),
(3, 'Admin', 'admin@library.com', 'admin1234', 'admin', '2025-06-19 04:44:23'),
(4, 'Lance Robert Macorol', 'lancerobertmacorol8@gmail.com', 'lance123\r\n', 'admin', '2025-06-19 09:54:13'),
(5, 'Cielo Santos', 'santos.c.bsinfotech@gmail.com', 'cielo1234', 'admin', '2025-06-22 15:18:50'),
(6, 'Cielo Santos', 'cielo@gmail.com', '$2y$10$bVIdwMY6UbS4/Msl/J6RB.YEBQhi6KgoxBtXbjMQthaWGx4fXfcSe', 'user', '2025-06-22 15:31:37'),
(7, 'Greg Lazarte', 'greg@gmail.com', 'greg12345', 'admin', '2025-06-22 23:26:18'),
(8, 'Cielo', 'cielo1@gmail.com', '$2y$10$MyEXrXWNGU4NKWIHUPSjSuTxb8d1ZN6mb7SBQL1JiITZzB9UoJE4W', 'admin', '2025-06-23 02:37:48');

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
-- Indexes for table `borrow_records`
--
ALTER TABLE `borrow_records`
  ADD PRIMARY KEY (`borrow_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `extension_requests`
--
ALTER TABLE `extension_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `fines`
--
ALTER TABLE `fines`
  ADD PRIMARY KEY (`fine_id`),
  ADD KEY `borrow_id` (`borrow_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `borrow_records`
--
ALTER TABLE `borrow_records`
  MODIFY `borrow_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `extension_requests`
--
ALTER TABLE `extension_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `fine_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrow_records`
--
ALTER TABLE `borrow_records`
  ADD CONSTRAINT `borrow_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `borrow_records_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`);

--
-- Constraints for table `extension_requests`
--
ALTER TABLE `extension_requests`
  ADD CONSTRAINT `extension_requests_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `extension_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `fines`
--
ALTER TABLE `fines`
  ADD CONSTRAINT `fines_ibfk_1` FOREIGN KEY (`borrow_id`) REFERENCES `borrow_records` (`borrow_id`),
  ADD CONSTRAINT `fines_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
