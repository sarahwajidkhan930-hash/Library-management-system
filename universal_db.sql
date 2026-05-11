-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2026 at 06:00 AM
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
-- Database: `universal_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `activity`, `notes`, `ip_address`, `created_at`) VALUES
(1, 1, 'SETTLE_FINE', 'Manual fine settlement for student ID: 6 by super_admin', '::1', '2026-02-25 04:24:09'),
(2, 1, 'RETURN', 'Returned with fine settled: 150 (Student ID: 1)', '::1', '2026-02-25 16:13:55'),
(3, 1, 'BOOK_STATUS_TOGGLED', 'Book ID 2 (\"A Game of Thrones\") set to Non-Issueable', '::1', '2026-02-25 16:31:07'),
(4, 1, 'CATEGORY_ADDED', 'Category added: \"Magazine\"', '::1', '2026-02-25 16:42:19'),
(5, 1, 'CATEGORY_UPDATED', 'Category ID 4 renamed to \"Programming\"', '::1', '2026-02-26 16:41:44'),
(6, 1, 'BOOK_STATUS_TOGGLED', 'Book ID 2 (\"A Game of Thrones\") set to Issueable', '::1', '2026-02-26 16:55:16'),
(7, 1, 'BOOK_ADDED_MODAL', 'Added \"The Hobbit\" (Stock: 1) to Category ID 3', '::1', '2026-03-04 15:28:55'),
(8, 1, 'BOOK_ADDED_MODAL', 'Added \"The Hobbit\" (Stock: 1) to Category ID 3', '::1', '2026-03-04 15:34:03'),
(9, 1, 'STOCK_SYNCED', 'Performed global inventory reconciliation', '::1', '2026-03-04 15:34:10'),
(10, 1, 'STOCK_SYNCED', 'Performed global inventory reconciliation', '::1', '2026-03-04 15:42:26'),
(11, 1, 'BOOK_DELETED', 'Book ID 8 (\"The Hobbit\") deleted.', '::1', '2026-03-04 15:42:56'),
(12, 1, 'STOCK_SYNCED', 'Performed global inventory reconciliation', '::1', '2026-03-04 15:43:11'),
(13, 1, 'NEW_AUTHOR_ADDED', 'Curated new author: Hector Garcia,Francesc Miralles', '::1', '2026-03-04 16:36:50'),
(14, 1, 'NEW_CATEGORY_ADDED', 'Curated new category: Personal Growth', '::1', '2026-03-04 16:36:50'),
(15, 1, 'REGISTER_BOOK_PREMIUM', 'Registered \'Ikigai\' (Volume ID: 10, Initial Stock: 2)', '::1', '2026-03-04 16:36:50'),
(16, 1, 'SETTLE_FINE', 'Manual fine settlement for student ID: 6 by super_admin', '::1', '2026-03-04 16:38:48'),
(17, 1, 'STOCK_REPLENISHMENT', 'Replenished \'A Game of Thrones\' (ID: 2) by 1 units. Metadata synchronized.', '::1', '2026-03-05 04:29:39'),
(18, 1, 'STOCK_SYNCED', 'Performed global inventory reconciliation', '::1', '2026-03-05 04:33:39'),
(19, 1, 'STOCK_SYNCED', 'Performed global inventory reconciliation', '::1', '2026-03-11 08:14:32'),
(20, 1, 'VIEW_AUDIT', 'User accessed the Digital Audit Trail', '::1', '2026-03-11 09:23:13'),
(21, 1, 'VIEW_AUDIT', 'User accessed the Digital Audit Trail', '::1', '2026-03-11 09:23:28'),
(22, 1, 'VIEW_AUDIT', 'User accessed the Digital Audit Trail', '::1', '2026-03-11 18:43:49'),
(23, 1, 'ISSUE', 'Book ID 10 issued to Student ID 13. Due: 2026-04-09', '::1', '2026-03-26 12:56:09'),
(24, 1, 'VIEW_AUDIT', 'User accessed the Digital Audit Trail', '::1', '2026-03-30 04:03:52'),
(25, 1, 'BOOK_STATUS_TOGGLED', 'Book ID 2 (\"A Game of Thrones\") set to Non-Issueable', '::1', '2026-03-30 04:04:51'),
(26, 1, 'RETURN', 'Returned with fine settled: 320 (Student ID: 1)', '::1', '2026-03-30 05:12:01'),
(27, 1, 'RETURN', 'Returned with fine settled: 220 (Student ID: 2)', '::1', '2026-03-30 05:12:38'),
(28, 1, 'RETURN', 'Returned with fine settled: 250 (Student ID: 7)', '::1', '2026-03-30 05:12:42'),
(29, 1, 'VIEW_AUDIT', 'User accessed the Digital Audit Trail', '::1', '2026-03-30 05:15:27'),
(30, 1, 'VIEW_AUDIT', 'User accessed the Digital Audit Trail', '::1', '2026-04-15 19:19:34'),
(31, 1, 'FINE_SETTLED', 'All fines settled for User ID 1 (Simulated Payment)', '::1', '2026-04-17 19:32:21'),
(32, 1, 'BOOK_STATUS_TOGGLED', 'Book ID 6 (\"The psychology of Money\") set to Non-Issueable', '::1', '2026-04-17 19:39:22'),
(33, 1, 'BOOK_STATUS_TOGGLED', 'Book ID 2 (\"A Game of Thrones\") set to Issueable', '::1', '2026-04-21 04:10:12'),
(34, 1, 'BOOK_STATUS_TOGGLED', 'Book ID 4 (\"The Hobbit\") set to Non-Issueable', '::1', '2026-04-21 04:10:27'),
(35, 1, 'REMINDER_SENT', 'Sent overdue reminder to Ahmad for \'Harry Potter and the Sorcerer\'s Stone\'', '::1', '2026-04-29 08:16:13'),
(36, 1, 'REMINDER_SENT', 'Sent overdue reminder to Ahmad for \'Harry Potter and the Sorcerer\'s Stone\'', '::1', '2026-04-29 08:16:13'),
(37, 1, 'REMINDER_SENT', 'Sent overdue reminder to Root Admin for \'Harry Potter and the Sorcerer\'s Stone\'', '::1', '2026-04-29 08:16:13'),
(38, 1, 'REMINDER_SENT', 'Sent overdue reminder to Sara for \'A Game of Thrones\'', '::1', '2026-04-29 08:16:13'),
(39, 1, 'REMINDER_SENT', 'Sent overdue reminder to M. Hunayn for \'Ikigai\'', '::1', '2026-04-29 08:16:13'),
(40, 1, 'REMINDER_SENT', 'Sent overdue reminder to Ahmad for \'Harry Potter and the Sorcerer\'s Stone\'', '::1', '2026-05-03 14:40:32'),
(41, 1, 'REMINDER_SENT', 'Sent overdue reminder to Ahmad for \'Harry Potter and the Sorcerer\'s Stone\'', '::1', '2026-05-03 14:40:32'),
(42, 1, 'REMINDER_SENT', 'Sent overdue reminder to Ahmad for \'Harry Potter and the Sorcerer\'s Stone\'', '::1', '2026-05-03 14:40:32'),
(43, 1, 'REMINDER_SENT', 'Sent overdue reminder to Sara for \'Harry Potter and the Sorcerer\'s Stone\'', '::1', '2026-05-03 14:40:32'),
(44, 1, 'REMINDER_SENT', 'Sent overdue reminder to Root Admin for \'Harry Potter and the Sorcerer\'s Stone\'', '::1', '2026-05-03 14:40:32'),
(45, 1, 'REMINDER_SENT', 'Sent overdue reminder to Sara for \'A Game of Thrones\'', '::1', '2026-05-03 14:40:32'),
(46, 1, 'REMINDER_SENT', 'Sent overdue reminder to Ahmad for \'A Game of Thrones\'', '::1', '2026-05-03 14:40:32'),
(47, 1, 'REMINDER_SENT', 'Sent overdue reminder to Sara for \'A Game of Thrones\'', '::1', '2026-05-03 14:40:32'),
(48, 1, 'REMINDER_SENT', 'Sent overdue reminder to Root Admin for \'A Game of Thrones\'', '::1', '2026-05-03 14:40:32'),
(49, 1, 'REMINDER_SENT', 'Sent overdue reminder to Ahmad for \'Murder on the Orient Express\'', '::1', '2026-05-03 14:40:32'),
(50, 1, 'REMINDER_SENT', 'Sent overdue reminder to Sara for \'Murder on the Orient Express\'', '::1', '2026-05-03 14:40:32'),
(51, 1, 'REMINDER_SENT', 'Sent overdue reminder to M. Hunayn for \'Ikigai\'', '::1', '2026-05-03 14:40:32'),
(52, 5, 'LOGIN', 'User (Librarian) logged in successfully', '::1', '2026-05-04 03:32:18'),
(53, 5, 'LOGIN', 'User (Librarian) logged in successfully', '::1', '2026-05-04 03:32:54'),
(56, 1, 'VIEW_AUDIT', 'User accessed the Digital Audit Trail', '::1', '2026-05-06 04:01:25'),
(57, 1, 'NEW_AUTHOR_ADDED', 'Curated new author: Alex Michaelides', '::1', '2026-05-08 16:54:20'),
(58, 1, 'REGISTER_BOOK_PREMIUM', 'Registered \'The Silent Pateint\' (Volume ID: 12, Initial Stock: 5)', '::1', '2026-05-08 16:54:20');

-- --------------------------------------------------------

--
-- Table structure for table `book_requests`
--

CREATE TABLE `book_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `priority` enum('low','normal','high') NOT NULL DEFAULT 'normal',
  `status` enum('pending','approved','rejected','purchased','cancelled') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `book_requests`
--

INSERT INTO `book_requests` (`id`, `user_id`, `book_title`, `author`, `isbn`, `genre`, `reason`, `priority`, `status`, `admin_notes`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 22, 'The Hobbit', 'Morgan Housel', '9780857197689', 'ggg', 'emergency', 'normal', 'approved', '', 1, '2026-05-06 09:37:20', '2026-05-06 03:20:10', '2026-05-06 04:37:20'),
(2, 22, 'The Hobbit', 'Stephen Covey', '9780857197689', 'ggg', 'fff', 'normal', 'approved', '', 1, '2026-05-07 10:20:53', '2026-05-07 05:20:32', '2026-05-07 05:20:53'),
(3, 22, 'The psychology of Money', 'Morgan Housel', '', '', 'for reading', 'normal', 'rejected', '', 1, '2026-05-08 21:48:34', '2026-05-07 06:11:29', '2026-05-08 16:48:34');

-- --------------------------------------------------------

--
-- Table structure for table `lib_authors`
--

CREATE TABLE `lib_authors` (
  `id` int(11) NOT NULL,
  `author_name` varchar(100) NOT NULL,
  `biography` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lib_authors`
--

INSERT INTO `lib_authors` (`id`, `author_name`, `biography`, `created_at`) VALUES
(1, 'J.K. Rowling', 'British author, best known for the Harry Potter series.', '2026-02-16 03:45:18'),
(2, 'George R.R. Martin', 'American novelist and short story writer in the fantasy genre.', '2026-02-16 03:45:18'),
(3, 'Agatha Christie', 'English writer known for her sixty-six detective novels.', '2026-02-16 03:45:18'),
(4, 'J.R.R. Tolkien', 'English writer, poet, philologist, and academic.', '2026-02-16 03:45:18'),
(5, 'Morgan Housel', NULL, '2026-02-16 11:41:56'),
(6, 'Stephen Covey', NULL, '2026-02-21 10:20:25'),
(7, 'J.R.R Tolkein', NULL, '2026-03-04 15:28:55'),
(8, 'Hector Garcia,Francesc Miralles', NULL, '2026-03-04 16:36:50'),
(9, 'Alex Michaelides', NULL, '2026-05-08 16:54:20');

-- --------------------------------------------------------

--
-- Table structure for table `lib_books`
--

CREATE TABLE `lib_books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `total_copies` int(11) DEFAULT 1,
  `available_copies` int(11) DEFAULT 1,
  `cover_image` varchar(255) DEFAULT NULL,
  `status` enum('available','low_stock','out_of_stock') DEFAULT 'available',
  `is_issueable` tinyint(1) DEFAULT 1,
  `fine_per_day` decimal(10,2) NOT NULL DEFAULT 10.00,
  `book_type` varchar(50) DEFAULT 'Textbook',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lib_books`
--

INSERT INTO `lib_books` (`id`, `title`, `author_id`, `category_id`, `isbn`, `total_copies`, `available_copies`, `cover_image`, `status`, `is_issueable`, `fine_per_day`, `book_type`, `created_at`) VALUES
(1, 'Harry Potter and the Sorcerer\'s Stone', 1, 1, '9780439708180', 5, 1, NULL, 'available', 1, 10.00, 'Textbook', '2026-02-16 03:45:18'),
(2, 'A Game of Thrones', 2, 1, '9780553103540', 4, 1, NULL, 'available', 1, 10.00, 'Textbook', '2026-02-16 03:45:18'),
(3, 'Murder on the Orient Express', 3, 2, '9780007119318', 4, 2, NULL, 'available', 1, 10.00, 'Textbook', '2026-02-16 03:45:18'),
(4, 'The Hobbit', 4, 3, '9780618260300', 8, 8, NULL, 'available', 0, 10.00, 'Textbook', '2026-02-16 03:45:18'),
(6, 'The psychology of Money', 5, 5, '9780857197696', 2, 2, NULL, 'available', 0, 10.00, 'Textbook', '2026-02-16 11:41:56'),
(7, 'The 7 Habits of Highly Effective People', 6, 5, '9780857197689', 3, 3, NULL, 'available', 1, 10.00, 'Textbook', '2026-02-21 10:20:25'),
(10, 'Ikigai', 8, 7, '978-0143130727', 2, 1, NULL, 'available', 1, 10.00, 'Textbook', '2026-03-04 16:36:50'),
(12, 'The Silent Pateint', 9, 2, '9781250301697', 4, 5, NULL, 'available', 1, 10.00, 'Textbook', '2026-05-08 16:54:20');

-- --------------------------------------------------------

--
-- Table structure for table `lib_borrowings`
--

CREATE TABLE `lib_borrowings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  `last_reminded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lib_borrowings`
--

INSERT INTO `lib_borrowings` (`id`, `user_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `fine_amount`, `status`, `last_reminded_at`, `created_at`) VALUES
(1, 1, 1, '2026-01-27', '2026-02-10', '2026-02-25', 0.00, 'returned', NULL, '2026-02-16 03:45:18'),
(2, 1, 2, '2026-02-11', '2026-02-25', '2026-03-30', 0.00, 'returned', NULL, '2026-02-16 03:45:18'),
(3, 1, 3, '2026-01-17', '2026-01-31', '2026-01-30', 0.00, 'returned', NULL, '2026-02-16 03:45:18'),
(4, 1, 4, '2026-01-07', '2026-01-21', '2026-01-27', 0.00, 'returned', NULL, '2026-02-16 03:45:18'),
(10, 6, 1, '2026-01-24', '2026-02-08', NULL, 910.00, 'overdue', '2026-05-03 14:40:32', '2026-02-23 18:31:21'),
(11, 7, 2, '2026-02-18', '2026-03-04', NULL, 670.00, 'overdue', '2026-05-03 14:40:32', '2026-02-23 18:31:21'),
(12, 6, 1, '2026-01-24', '2026-02-08', NULL, 910.00, 'overdue', '2026-05-03 14:40:32', '2026-02-23 18:33:08'),
(13, 7, 2, '2026-02-18', '2026-03-04', '2026-03-30', 250.00, 'returned', NULL, '2026-02-23 18:33:08'),
(14, 13, 10, '2026-03-26', '2026-04-09', NULL, 310.00, 'overdue', '2026-05-03 14:40:32', '2026-03-26 12:56:09'),
(15, 6, 1, '2026-04-17', '2026-05-01', NULL, 90.00, 'overdue', '2026-05-03 14:40:32', '2026-04-17 18:40:49'),
(16, 6, 2, '2026-04-17', '2026-05-01', NULL, 90.00, 'overdue', '2026-05-03 14:40:32', '2026-04-17 18:40:49'),
(17, 6, 3, '2026-04-17', '2026-05-01', NULL, 90.00, 'overdue', '2026-05-03 14:40:32', '2026-04-17 18:40:49'),
(18, 7, 1, '2026-04-17', '2026-05-01', NULL, 90.00, 'overdue', '2026-05-03 14:40:32', '2026-04-17 18:40:49'),
(19, 7, 2, '2026-04-17', '2026-05-01', NULL, 90.00, 'overdue', '2026-05-03 14:40:32', '2026-04-17 18:40:49'),
(20, 7, 3, '2026-04-17', '2026-05-01', NULL, 90.00, 'overdue', '2026-05-03 14:40:32', '2026-04-17 18:40:49'),
(21, 1, 1, '2026-04-17', '2026-04-20', NULL, 200.00, 'overdue', '2026-05-03 14:40:32', '2026-04-17 18:49:18'),
(22, 1, 2, '2026-04-17', '2026-05-01', NULL, 90.00, 'overdue', '2026-05-03 14:40:32', '2026-04-17 18:49:18');

-- --------------------------------------------------------

--
-- Table structure for table `lib_categories`
--

CREATE TABLE `lib_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lib_categories`
--

INSERT INTO `lib_categories` (`id`, `category_name`, `description`, `created_at`) VALUES
(1, 'Fantasy', 'Fantasy is a genre of speculative fiction.', '2026-02-16 03:45:18'),
(2, 'Mystery', 'A type of fiction in which a detective solves a crime.', '2026-02-16 03:45:18'),
(3, 'Adventure', 'Fiction that usually presents danger, or gives the reader a sense of excitement.', '2026-02-16 03:45:18'),
(4, 'Programming', 'Books about software development and computer science.', '2026-02-16 03:45:18'),
(5, 'Personal Finance,Business and Economics', NULL, '2026-02-16 11:41:56'),
(6, 'Magazine', 'Harvard Business Review(HBR) is a leading global magazine that provides insights and ideas on management and leadership.', '2026-02-25 16:42:19'),
(7, 'Personal Growth', NULL, '2026-03-04 16:36:50');

-- --------------------------------------------------------

--
-- Table structure for table `lib_notifications`
--

CREATE TABLE `lib_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('success','info','warning','danger') DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `unique_key` varchar(100) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lib_notifications`
--

INSERT INTO `lib_notifications` (`id`, `user_id`, `type`, `title`, `message`, `unique_key`, `is_read`, `created_at`) VALUES
(1, 1, 'warning', 'Due Soon', 'Your book \'Harry Potter and the Sorcerer\'s Stone\' is due in 3 days. Please return or renew it soon.', 'DUE_REMIND_21_3D', 1, '2026-04-17 19:29:50'),
(33, 1, 'danger', 'Due Tomorrow', 'Urgent: \'Harry Potter and the Sorcerer\'s Stone\' is due tomorrow. Return it to avoid fines.', 'DUE_REMIND_21_1D', 1, '2026-04-19 18:05:59'),
(42, 1, 'danger', 'OVERDUE ALERT', 'Warning: \'Harry Potter and the Sorcerer\'s Stone\' is past its due date. Fines are accumulating.', 'OVERDUE_ALERT_21', 1, '2026-04-21 03:20:15'),
(91, 1, 'warning', 'Due Soon', 'Your book \'A Game of Thrones\' is due in 3 days. Please return or renew it soon.', 'DUE_REMIND_22_3D', 1, '2026-04-28 03:13:15'),
(117, 1, 'danger', 'OVERDUE ALERT', 'Warning: \'A Game of Thrones\' is past its due date. Fines are accumulating.', 'OVERDUE_ALERT_22', 1, '2026-05-03 14:41:06'),
(146, 26, 'success', 'Hold Ready!', 'Your book is ready.', NULL, 0, '2026-05-04 15:36:40'),
(147, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-04 16:42:10'),
(148, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-04 16:42:10'),
(149, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-05 03:50:06'),
(150, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-05 03:50:06'),
(151, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-06 03:44:48'),
(152, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-06 03:44:48'),
(153, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:36:42'),
(154, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:36:42'),
(155, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:36:50'),
(156, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:36:50'),
(157, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:38:05'),
(158, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:38:05'),
(159, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:38:37'),
(160, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:38:37'),
(161, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:38:56'),
(162, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:38:56'),
(163, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:39:27'),
(164, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:39:27'),
(165, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:48:47'),
(166, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:48:47'),
(167, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:55:17'),
(168, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:55:17'),
(169, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:55:39'),
(170, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:55:39'),
(171, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:55:39'),
(172, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:55:39'),
(173, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:55:42'),
(174, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:55:42'),
(175, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:55:47'),
(176, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 16:55:47'),
(177, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 18:14:55'),
(178, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 18:14:55'),
(179, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 18:16:05'),
(180, 1, 'danger', 'OVERDUE', 'Overdue!', NULL, 0, '2026-05-08 18:16:05');

-- --------------------------------------------------------

--
-- Table structure for table `lib_reservations`
--

CREATE TABLE `lib_reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `reserved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','fulfilled','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lib_reservations`
--

INSERT INTO `lib_reservations` (`id`, `user_id`, `book_id`, `reserved_at`, `status`) VALUES
(1, 1, 6, '2026-04-17 18:53:51', 'pending'),
(2, 1, 7, '2026-04-17 18:53:51', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `lib_reviews`
--

CREATE TABLE `lib_reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lib_transactions`
--

CREATE TABLE `lib_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `book_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lib_transactions`
--

INSERT INTO `lib_transactions` (`id`, `user_id`, `book_id`, `action`, `transaction_date`, `notes`) VALUES
(1, 13, 4, 'ISSUE', '2026-02-02 12:36:23', 'Self-help book issued for personal development.'),
(2, 15, 1, 'RETURN', '2026-01-31 20:36:23', 'Returned with minor delay. Fine settled.'),
(3, 12, 1, 'REGISTER_BOOK', '2026-02-16 07:36:23', 'New engineering textbook added to collection.'),
(4, 10, 1, 'SETTLE_FINE', '2026-01-24 18:36:23', 'Overdue fine of Rs. 50 paid at the counter.'),
(5, 7, 1, 'ISSUE', '2026-02-05 15:36:23', 'Self-help book issued for personal development.'),
(6, 14, 6, 'UPDATE_STOCK', '2026-02-16 00:36:23', 'Added 5 new copies to inventory.'),
(7, 10, 3, 'UPDATE_STOCK', '2026-02-18 02:36:23', 'Added 5 new copies to inventory.'),
(8, 10, 1, 'ISSUE', '2026-01-29 20:36:23', 'Book issued for 14 days semester loan.'),
(9, 6, 1, 'UPDATE_STOCK', '2026-02-11 07:36:23', 'Added 5 new copies to inventory.'),
(10, 12, 3, 'ISSUE', '2026-02-22 00:36:23', 'Core curriculum book issued for exam preparation.'),
(11, 13, 1, 'ISSUE', '2026-02-03 16:36:23', 'Self-help book issued for personal development.'),
(12, 9, 6, 'RETURN', '2026-02-07 12:36:23', 'Book returned on time. Condition: Excellent.'),
(13, 13, 3, 'REGISTER_BOOK', '2026-02-02 05:36:23', 'New engineering textbook added to collection.'),
(14, 8, 7, 'RETURN', '2026-02-13 13:36:23', 'Book returned on time. Condition: Excellent.'),
(15, 8, 4, 'ISSUE', '2026-01-26 17:36:23', 'Self-help book issued for personal development.'),
(16, 13, 3, 'ISSUE', '2026-02-03 01:36:23', 'Self-help book issued for personal development.'),
(17, 10, 6, 'REGISTER_BOOK', '2026-01-29 14:36:23', 'New engineering textbook added to collection.'),
(18, 8, 7, 'RETURN', '2026-01-26 14:36:23', 'Returned with minor delay. Fine settled.'),
(19, 12, 1, 'RETURN', '2026-02-23 02:36:23', 'Returned with minor delay. Fine settled.'),
(20, 13, 2, 'REGISTER_BOOK', '2026-01-29 17:36:23', 'New engineering textbook added to collection.'),
(21, 9, 1, 'ISSUE', '2026-02-17 00:36:23', 'Book issued for 14 days semester loan.'),
(22, 12, 4, 'RETURN', '2026-02-12 04:36:23', 'Returned with minor delay. Fine settled.'),
(23, 13, 3, 'SETTLE_FINE', '2026-02-11 03:36:23', 'Overdue fine of Rs. 50 paid at the counter.'),
(24, 8, 3, 'ISSUE', '2026-02-21 00:36:23', 'Book issued for 14 days semester loan.'),
(25, 10, 4, 'RETURN', '2026-02-16 18:36:23', 'Returned with minor delay. Fine settled.'),
(26, 11, 7, 'ISSUE', '2026-02-09 11:38:13', 'Book issued for 14 days semester loan.'),
(27, 8, 2, 'ISSUE', '2026-02-18 07:38:13', 'Self-help book issued for personal development.'),
(28, 14, 6, 'RETURN', '2026-02-13 12:38:13', 'Book returned on time. Condition: Excellent.'),
(29, 14, 4, 'ISSUE', '2026-02-08 11:38:13', 'Self-help book issued for personal development.'),
(30, 6, 1, 'UPDATE_STOCK', '2026-02-14 08:38:13', 'Added 5 new copies to inventory.'),
(31, 15, 1, 'RETURN', '2026-01-29 22:38:13', 'Returned with minor delay. Fine settled.'),
(32, 6, 6, 'ISSUE', '2026-02-02 18:38:13', 'Core curriculum book issued for exam preparation.'),
(33, 11, 3, 'ISSUE', '2026-01-31 11:38:13', 'Core curriculum book issued for exam preparation.'),
(34, 8, 1, 'RETURN', '2026-02-04 23:38:13', 'Book returned on time. Condition: Excellent.'),
(35, 13, 1, 'ISSUE', '2026-02-08 09:38:13', 'Book issued for 14 days semester loan.'),
(36, 13, 1, 'RETURN', '2026-02-05 00:38:13', 'Returned with minor delay. Fine settled.'),
(37, 15, 6, 'RETURN', '2026-02-21 09:38:13', 'Book returned on time. Condition: Excellent.'),
(38, 9, 7, 'ISSUE', '2026-02-17 04:38:13', 'Core curriculum book issued for exam preparation.'),
(39, 11, 7, 'REGISTER_BOOK', '2026-02-11 19:38:13', 'New engineering textbook added to collection.'),
(40, 6, 4, 'REGISTER_BOOK', '2026-02-14 08:38:13', 'New engineering textbook added to collection.'),
(41, 9, 6, 'ISSUE', '2026-02-09 22:38:13', 'Self-help book issued for personal development.'),
(42, 10, 3, 'RETURN', '2026-02-02 15:38:13', 'Returned with minor delay. Fine settled.'),
(43, 11, 2, 'REGISTER_BOOK', '2026-02-05 21:38:13', 'New engineering textbook added to collection.'),
(44, 13, 3, 'ISSUE', '2026-02-02 16:38:13', 'Core curriculum book issued for exam preparation.'),
(45, 14, 7, 'UPDATE_STOCK', '2026-02-16 06:38:13', 'Added 5 new copies to inventory.'),
(46, 13, 3, 'ISSUE', '2026-01-31 02:38:13', 'Self-help book issued for personal development.'),
(47, 13, 3, 'ISSUE', '2026-02-15 05:38:13', 'Self-help book issued for personal development.'),
(48, 9, 3, 'ISSUE', '2026-02-06 11:38:13', 'Self-help book issued for personal development.'),
(49, 13, 1, 'ISSUE', '2026-02-15 02:38:13', 'Core curriculum book issued for exam preparation.'),
(50, 10, 3, 'RETURN', '2026-02-05 18:38:13', 'Book returned on time. Condition: Excellent.'),
(51, 12, 3, 'SETTLE_FINE', '2025-12-07 18:30:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(52, 8, 6, 'RETURN', '2025-12-22 21:15:34', 'Book returned early. Condition: Good.'),
(53, 9, 1, 'SETTLE_FINE', '2025-11-26 11:00:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(54, 12, 7, 'SETTLE_FINE', '2025-12-06 16:39:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(55, 14, 7, 'RETURN', '2025-12-24 16:46:34', 'Returned with minor delay. Fine settled.'),
(56, 8, 6, 'SETTLE_FINE', '2026-01-21 22:35:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(57, 12, 7, 'RETURN', '2025-12-10 21:04:34', 'Book returned on time. Condition: Excellent.'),
(58, 9, 2, 'UPDATE_STOCK', '2026-01-08 02:14:34', 'Added 5 new copies to inventory.'),
(59, 11, 2, 'REGISTER_BOOK', '2026-02-19 20:38:34', 'New engineering textbook added to collection.'),
(60, 7, 1, 'UPDATE_STOCK', '2026-01-17 12:00:34', 'Removed damaged copies from inventory.'),
(61, 12, 2, 'ISSUE', '2025-12-17 17:25:34', 'Research material borrowed for project work.'),
(62, 9, 2, 'ISSUE', '2025-12-22 04:57:34', 'Self-help book issued for personal development.'),
(63, 16, 7, 'REGISTER_BOOK', '2026-01-15 05:18:34', 'New engineering textbook added to collection.'),
(64, 14, 2, 'REGISTER_BOOK', '2026-01-22 10:14:34', 'New engineering textbook added to collection.'),
(65, 15, 2, 'ISSUE', '2025-12-02 03:59:34', 'Research material borrowed for project work.'),
(66, 8, 1, 'UPDATE_STOCK', '2025-12-04 07:47:34', 'Removed damaged copies from inventory.'),
(67, 14, 3, 'REGISTER_BOOK', '2025-12-27 05:20:34', 'New engineering textbook added to collection.'),
(68, 9, 1, 'RETURN', '2026-01-29 13:32:34', 'Book returned early. Condition: Good.'),
(69, 9, 2, 'RETURN', '2026-01-17 12:01:34', 'Book returned on time. Condition: Excellent.'),
(70, 10, 7, 'ISSUE', '2025-12-27 23:24:34', 'Research material borrowed for project work.'),
(71, 17, 1, 'RETURN', '2026-01-30 07:47:34', 'Returned with minor delay. Fine settled.'),
(72, 9, 6, 'ISSUE', '2026-01-16 09:32:34', 'Core curriculum book issued for exam preparation.'),
(73, 10, 7, 'RETURN', '2026-01-31 12:46:34', 'Book returned early. Condition: Good.'),
(74, 9, 4, 'RETURN', '2026-01-22 21:09:34', 'Book returned on time. Condition: Excellent.'),
(75, 9, 7, 'RETURN', '2026-01-28 14:21:34', 'Book returned early. Condition: Good.'),
(76, 17, 3, 'RETURN', '2025-12-01 05:12:34', 'Book returned on time. Condition: Excellent.'),
(77, 10, 3, 'RETURN', '2025-12-20 01:52:34', 'Returned with minor delay. Fine settled.'),
(78, 12, 6, 'ISSUE', '2026-01-22 13:09:34', 'Self-help book issued for personal development.'),
(79, 13, 4, 'SETTLE_FINE', '2025-11-28 14:00:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(80, 6, 6, 'RETURN', '2026-02-08 07:28:34', 'Returned with minor delay. Fine settled.'),
(81, 13, 4, 'ISSUE', '2025-12-15 15:17:34', 'Book issued for 14 days semester loan.'),
(82, 9, 3, 'RETURN', '2025-12-25 08:33:34', 'Returned with minor delay. Fine settled.'),
(83, 6, 3, 'SETTLE_FINE', '2025-12-13 00:28:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(84, 13, 1, 'ISSUE', '2025-12-18 01:06:34', 'Self-help book issued for personal development.'),
(85, 8, 1, 'RETURN', '2025-12-22 01:48:34', 'Book returned on time. Condition: Excellent.'),
(86, 15, 6, 'ISSUE', '2025-12-11 18:41:34', 'Self-help book issued for personal development.'),
(87, 6, 2, 'SETTLE_FINE', '2026-01-14 23:47:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(88, 13, 1, 'RETURN', '2026-01-14 17:19:34', 'Book returned on time. Condition: Excellent.'),
(89, 10, 7, 'UPDATE_STOCK', '2025-12-13 22:54:34', 'Removed damaged copies from inventory.'),
(90, 14, 4, 'ISSUE', '2026-02-07 13:47:34', 'Book issued for 14 days semester loan.'),
(91, 10, 4, 'RETURN', '2026-01-29 20:51:34', 'Book returned on time. Condition: Excellent.'),
(92, 17, 1, 'RETURN', '2026-01-26 03:51:34', 'Book returned early. Condition: Good.'),
(93, 6, 4, 'ISSUE', '2026-02-17 00:22:34', 'Core curriculum book issued for exam preparation.'),
(94, 6, 4, 'UPDATE_STOCK', '2026-01-19 01:05:34', 'Removed damaged copies from inventory.'),
(95, 6, 3, 'UPDATE_STOCK', '2026-01-16 22:08:34', 'Removed damaged copies from inventory.'),
(96, 16, 3, 'SETTLE_FINE', '2026-02-15 17:11:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(97, 7, 3, 'ISSUE', '2026-01-13 17:25:34', 'Self-help book issued for personal development.'),
(98, 11, 6, 'RETURN', '2025-12-18 12:56:34', 'Book returned on time. Condition: Excellent.'),
(99, 8, 3, 'ISSUE', '2025-12-25 05:18:34', 'Research material borrowed for project work.'),
(100, 16, 2, 'ISSUE', '2026-01-02 17:39:34', 'Research material borrowed for project work.'),
(101, 8, 3, 'ISSUE', '2025-12-09 07:16:34', 'Self-help book issued for personal development.'),
(102, 15, 2, 'UPDATE_STOCK', '2025-12-20 15:38:34', 'Removed damaged copies from inventory.'),
(103, 8, 7, 'RETURN', '2025-12-07 09:38:34', 'Returned with minor delay. Fine settled.'),
(104, 11, 2, 'RETURN', '2025-12-17 13:03:34', 'Book returned on time. Condition: Excellent.'),
(105, 14, 2, 'ISSUE', '2025-11-28 15:59:34', 'Core curriculum book issued for exam preparation.'),
(106, 11, 4, 'RETURN', '2026-01-13 02:37:34', 'Book returned on time. Condition: Excellent.'),
(107, 6, 1, 'UPDATE_STOCK', '2025-12-06 19:59:34', 'Removed damaged copies from inventory.'),
(108, 17, 4, 'SETTLE_FINE', '2025-12-17 09:18:34', 'Damage fine of Rs. 100 paid for cover tear.'),
(109, 6, 3, 'ISSUE', '2026-01-01 05:43:34', 'Book issued for 14 days semester loan.'),
(110, 7, 6, 'RETURN', '2026-02-15 07:35:34', 'Book returned on time. Condition: Excellent.'),
(111, 6, 7, 'RETURN', '2025-12-30 10:52:34', 'Returned with minor delay. Fine settled.'),
(112, 6, 3, 'RETURN', '2026-01-29 18:49:34', 'Returned with minor delay. Fine settled.'),
(113, 14, 3, 'ISSUE', '2026-02-18 04:35:34', 'Research material borrowed for project work.'),
(114, 11, 6, 'REGISTER_BOOK', '2026-01-27 21:54:34', 'New engineering textbook added to collection.'),
(115, 7, 6, 'ISSUE', '2026-01-02 11:32:34', 'Research material borrowed for project work.'),
(116, 16, 1, 'ISSUE', '2025-12-16 01:14:34', 'Book issued for 14 days semester loan.'),
(117, 6, 7, 'UPDATE_STOCK', '2025-12-04 08:23:34', 'Removed damaged copies from inventory.'),
(118, 6, 2, 'ISSUE', '2025-11-29 16:18:34', 'Self-help book issued for personal development.'),
(119, 15, 1, 'UPDATE_STOCK', '2026-02-09 07:27:34', 'Removed damaged copies from inventory.'),
(120, 17, 4, 'UPDATE_STOCK', '2026-02-09 16:25:34', 'Added 5 new copies to inventory.'),
(121, 8, 1, 'SETTLE_FINE', '2025-12-31 07:11:34', 'Damage fine of Rs. 100 paid for cover tear.'),
(122, 6, 7, 'RETURN', '2026-01-29 06:53:34', 'Book returned early. Condition: Good.'),
(123, 8, 6, 'UPDATE_STOCK', '2026-02-19 07:58:34', 'Added 5 new copies to inventory.'),
(124, 6, 7, 'UPDATE_STOCK', '2026-01-22 02:03:34', 'Added 5 new copies to inventory.'),
(125, 7, 6, 'SETTLE_FINE', '2025-12-15 01:07:34', 'Damage fine of Rs. 100 paid for cover tear.'),
(126, 7, 6, 'ISSUE', '2026-02-13 20:11:34', 'Core curriculum book issued for exam preparation.'),
(127, 17, 6, 'SETTLE_FINE', '2026-02-05 04:00:34', 'Damage fine of Rs. 100 paid for cover tear.'),
(128, 17, 1, 'ISSUE', '2025-12-24 09:00:34', 'Core curriculum book issued for exam preparation.'),
(129, 7, 7, 'UPDATE_STOCK', '2025-12-13 01:57:34', 'Added 5 new copies to inventory.'),
(130, 13, 3, 'UPDATE_STOCK', '2026-01-16 09:46:34', 'Removed damaged copies from inventory.'),
(131, 13, 1, 'ISSUE', '2026-02-03 04:23:34', 'Self-help book issued for personal development.'),
(132, 9, 1, 'REGISTER_BOOK', '2026-01-19 10:53:34', 'New engineering textbook added to collection.'),
(133, 16, 3, 'UPDATE_STOCK', '2026-01-19 16:29:34', 'Added 5 new copies to inventory.'),
(134, 10, 4, 'ISSUE', '2026-01-08 18:26:34', 'Self-help book issued for personal development.'),
(135, 6, 1, 'ISSUE', '2025-12-18 07:05:34', 'Core curriculum book issued for exam preparation.'),
(136, 7, 2, 'UPDATE_STOCK', '2026-02-08 03:31:34', 'Added 5 new copies to inventory.'),
(137, 11, 4, 'ISSUE', '2025-12-03 01:04:34', 'Self-help book issued for personal development.'),
(138, 10, 1, 'RETURN', '2025-12-24 06:48:34', 'Book returned early. Condition: Good.'),
(139, 17, 4, 'RETURN', '2026-01-15 09:35:34', 'Book returned on time. Condition: Excellent.'),
(140, 9, 1, 'RETURN', '2025-12-01 00:18:34', 'Returned with minor delay. Fine settled.'),
(141, 16, 6, 'RETURN', '2026-01-08 00:21:34', 'Book returned on time. Condition: Excellent.'),
(142, 11, 4, 'UPDATE_STOCK', '2025-11-29 22:40:34', 'Removed damaged copies from inventory.'),
(143, 15, 7, 'SETTLE_FINE', '2025-12-20 07:17:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(144, 17, 7, 'RETURN', '2026-01-16 07:53:34', 'Book returned early. Condition: Good.'),
(145, 15, 1, 'SETTLE_FINE', '2026-02-21 05:09:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(146, 12, 3, 'ISSUE', '2026-02-12 03:39:34', 'Core curriculum book issued for exam preparation.'),
(147, 15, 1, 'UPDATE_STOCK', '2026-01-06 18:01:34', 'Removed damaged copies from inventory.'),
(148, 7, 3, 'SETTLE_FINE', '2026-01-24 19:05:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(149, 10, 4, 'UPDATE_STOCK', '2025-12-10 18:12:34', 'Removed damaged copies from inventory.'),
(150, 6, 4, 'REGISTER_BOOK', '2025-11-29 23:18:34', 'New engineering textbook added to collection.'),
(151, 13, 2, 'ISSUE', '2025-12-20 20:32:34', 'Core curriculum book issued for exam preparation.'),
(152, 9, 6, 'REGISTER_BOOK', '2025-11-27 13:19:34', 'New engineering textbook added to collection.'),
(153, 8, 4, 'REGISTER_BOOK', '2025-12-19 13:16:34', 'New engineering textbook added to collection.'),
(154, 12, 1, 'ISSUE', '2026-01-23 17:13:34', 'Core curriculum book issued for exam preparation.'),
(155, 13, 1, 'ISSUE', '2026-01-08 06:10:34', 'Self-help book issued for personal development.'),
(156, 11, 6, 'ISSUE', '2026-02-20 20:57:34', 'Core curriculum book issued for exam preparation.'),
(157, 17, 7, 'ISSUE', '2026-01-06 11:48:34', 'Self-help book issued for personal development.'),
(158, 10, 6, 'SETTLE_FINE', '2025-12-02 04:37:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(159, 13, 7, 'ISSUE', '2026-02-14 03:27:34', 'Book issued for 14 days semester loan.'),
(160, 10, 7, 'RETURN', '2026-02-03 07:13:34', 'Book returned on time. Condition: Excellent.'),
(161, 12, 2, 'REGISTER_BOOK', '2025-12-14 16:08:34', 'New engineering textbook added to collection.'),
(162, 14, 2, 'RETURN', '2026-01-12 18:57:34', 'Book returned on time. Condition: Excellent.'),
(163, 7, 3, 'SETTLE_FINE', '2026-01-02 02:50:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(164, 6, 3, 'SETTLE_FINE', '2026-02-01 16:34:34', 'Damage fine of Rs. 100 paid for cover tear.'),
(165, 15, 1, 'UPDATE_STOCK', '2026-01-07 22:09:34', 'Added 5 new copies to inventory.'),
(166, 12, 3, 'SETTLE_FINE', '2025-11-24 22:59:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(167, 12, 6, 'SETTLE_FINE', '2025-12-30 15:35:34', 'Damage fine of Rs. 100 paid for cover tear.'),
(168, 15, 7, 'ISSUE', '2026-01-16 19:35:34', 'Self-help book issued for personal development.'),
(169, 10, 4, 'RETURN', '2026-01-21 02:08:34', 'Book returned early. Condition: Good.'),
(170, 14, 7, 'RETURN', '2025-12-04 02:54:34', 'Book returned early. Condition: Good.'),
(171, 10, 4, 'ISSUE', '2026-02-21 20:12:34', 'Core curriculum book issued for exam preparation.'),
(172, 7, 2, 'ISSUE', '2026-01-20 08:40:34', 'Book issued for 14 days semester loan.'),
(173, 6, 6, 'UPDATE_STOCK', '2026-01-20 06:43:34', 'Removed damaged copies from inventory.'),
(174, 12, 1, 'RETURN', '2025-12-06 11:29:34', 'Returned with minor delay. Fine settled.'),
(175, 9, 1, 'RETURN', '2026-02-08 15:02:34', 'Book returned early. Condition: Good.'),
(176, 13, 2, 'RETURN', '2026-02-20 07:59:34', 'Book returned on time. Condition: Excellent.'),
(177, 8, 7, 'UPDATE_STOCK', '2025-12-02 07:05:34', 'Removed damaged copies from inventory.'),
(178, 6, 4, 'REGISTER_BOOK', '2025-12-17 11:20:34', 'New engineering textbook added to collection.'),
(179, 17, 6, 'RETURN', '2025-11-24 21:19:34', 'Book returned early. Condition: Good.'),
(180, 14, 1, 'UPDATE_STOCK', '2025-12-09 13:46:34', 'Added 5 new copies to inventory.'),
(181, 14, 3, 'ISSUE', '2025-12-07 00:53:34', 'Core curriculum book issued for exam preparation.'),
(182, 6, 4, 'ISSUE', '2026-01-26 19:22:34', 'Book issued for 14 days semester loan.'),
(183, 7, 6, 'ISSUE', '2026-02-09 02:17:34', 'Self-help book issued for personal development.'),
(184, 14, 3, 'ISSUE', '2025-12-19 18:53:34', 'Research material borrowed for project work.'),
(185, 15, 2, 'ISSUE', '2025-12-08 11:19:34', 'Book issued for 14 days semester loan.'),
(186, 15, 2, 'RETURN', '2025-12-23 18:24:34', 'Returned with minor delay. Fine settled.'),
(187, 12, 6, 'ISSUE', '2026-01-07 22:00:34', 'Research material borrowed for project work.'),
(188, 8, 2, 'ISSUE', '2026-01-18 04:09:34', 'Research material borrowed for project work.'),
(189, 8, 3, 'UPDATE_STOCK', '2025-12-25 18:51:34', 'Added 5 new copies to inventory.'),
(190, 9, 2, 'SETTLE_FINE', '2026-01-14 03:20:34', 'Damage fine of Rs. 100 paid for cover tear.'),
(191, 17, 6, 'ISSUE', '2025-12-31 14:48:34', 'Self-help book issued for personal development.'),
(192, 14, 2, 'ISSUE', '2025-12-13 12:03:34', 'Self-help book issued for personal development.'),
(193, 9, 6, 'REGISTER_BOOK', '2025-12-02 23:03:34', 'New engineering textbook added to collection.'),
(194, 8, 6, 'ISSUE', '2026-01-23 14:40:34', 'Research material borrowed for project work.'),
(195, 13, 7, 'UPDATE_STOCK', '2026-02-18 13:47:34', 'Removed damaged copies from inventory.'),
(196, 16, 7, 'ISSUE', '2025-12-17 06:44:34', 'Research material borrowed for project work.'),
(197, 10, 1, 'RETURN', '2026-01-18 13:35:34', 'Book returned early. Condition: Good.'),
(198, 13, 4, 'RETURN', '2026-01-21 22:05:34', 'Book returned on time. Condition: Excellent.'),
(199, 14, 3, 'UPDATE_STOCK', '2025-12-14 03:18:34', 'Removed damaged copies from inventory.'),
(200, 11, 1, 'RETURN', '2026-01-27 15:21:34', 'Book returned early. Condition: Good.'),
(201, 7, 2, 'RETURN', '2025-11-26 16:11:34', 'Book returned on time. Condition: Excellent.'),
(202, 9, 2, 'UPDATE_STOCK', '2026-01-02 10:31:34', 'Added 5 new copies to inventory.'),
(203, 13, 2, 'UPDATE_STOCK', '2026-01-30 06:26:34', 'Added 5 new copies to inventory.'),
(204, 16, 7, 'SETTLE_FINE', '2025-12-05 15:25:34', 'Damage fine of Rs. 100 paid for cover tear.'),
(205, 7, 2, 'RETURN', '2025-12-09 20:00:34', 'Book returned early. Condition: Good.'),
(206, 11, 7, 'ISSUE', '2026-01-23 01:06:34', 'Self-help book issued for personal development.'),
(207, 8, 4, 'RETURN', '2025-12-03 09:15:34', 'Returned with minor delay. Fine settled.'),
(208, 16, 6, 'ISSUE', '2026-02-19 20:29:34', 'Book issued for 14 days semester loan.'),
(209, 9, 2, 'ISSUE', '2025-11-25 06:18:34', 'Research material borrowed for project work.'),
(210, 16, 7, 'ISSUE', '2025-12-14 09:40:34', 'Self-help book issued for personal development.'),
(211, 16, 3, 'ISSUE', '2025-12-06 19:56:34', 'Self-help book issued for personal development.'),
(212, 7, 2, 'SETTLE_FINE', '2025-11-29 03:03:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(213, 12, 7, 'SETTLE_FINE', '2025-12-17 01:52:34', 'Damage fine of Rs. 100 paid for cover tear.'),
(214, 12, 6, 'SETTLE_FINE', '2025-12-30 02:21:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(215, 16, 4, 'ISSUE', '2026-01-09 04:38:34', 'Core curriculum book issued for exam preparation.'),
(216, 6, 6, 'RETURN', '2026-02-02 08:31:34', 'Book returned early. Condition: Good.'),
(217, 13, 3, 'ISSUE', '2026-01-10 14:15:34', 'Core curriculum book issued for exam preparation.'),
(218, 16, 2, 'RETURN', '2026-02-09 21:33:34', 'Book returned early. Condition: Good.'),
(219, 14, 4, 'RETURN', '2026-01-21 23:25:34', 'Returned with minor delay. Fine settled.'),
(220, 17, 4, 'ISSUE', '2025-12-30 11:58:34', 'Self-help book issued for personal development.'),
(221, 17, 1, 'ISSUE', '2026-01-10 06:15:34', 'Core curriculum book issued for exam preparation.'),
(222, 8, 7, 'REGISTER_BOOK', '2025-12-28 01:49:34', 'New engineering textbook added to collection.'),
(223, 12, 1, 'RETURN', '2026-01-18 04:03:34', 'Returned with minor delay. Fine settled.'),
(224, 14, 4, 'SETTLE_FINE', '2025-11-30 08:19:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(225, 17, 4, 'ISSUE', '2026-01-20 03:46:34', 'Self-help book issued for personal development.'),
(226, 6, 4, 'REGISTER_BOOK', '2026-02-09 11:05:34', 'New engineering textbook added to collection.'),
(227, 8, 6, 'ISSUE', '2025-12-16 11:21:34', 'Self-help book issued for personal development.'),
(228, 13, 3, 'REGISTER_BOOK', '2025-12-22 23:15:34', 'New engineering textbook added to collection.'),
(229, 8, 1, 'RETURN', '2026-02-10 23:38:34', 'Book returned on time. Condition: Excellent.'),
(230, 8, 2, 'REGISTER_BOOK', '2026-01-09 05:44:34', 'New engineering textbook added to collection.'),
(231, 17, 6, 'RETURN', '2025-12-23 15:34:34', 'Book returned on time. Condition: Excellent.'),
(232, 13, 4, 'RETURN', '2026-02-15 21:54:34', 'Book returned on time. Condition: Excellent.'),
(233, 10, 2, 'ISSUE', '2026-02-18 00:12:34', 'Self-help book issued for personal development.'),
(234, 16, 4, 'ISSUE', '2026-02-16 05:42:34', 'Self-help book issued for personal development.'),
(235, 9, 4, 'UPDATE_STOCK', '2025-11-28 11:46:34', 'Removed damaged copies from inventory.'),
(236, 12, 1, 'UPDATE_STOCK', '2026-02-15 16:46:34', 'Removed damaged copies from inventory.'),
(237, 13, 7, 'UPDATE_STOCK', '2026-01-02 23:28:34', 'Removed damaged copies from inventory.'),
(238, 9, 1, 'ISSUE', '2026-02-04 20:01:34', 'Core curriculum book issued for exam preparation.'),
(239, 14, 3, 'ISSUE', '2026-01-01 18:59:34', 'Research material borrowed for project work.'),
(240, 15, 6, 'ISSUE', '2025-12-23 17:25:34', 'Core curriculum book issued for exam preparation.'),
(241, 8, 4, 'ISSUE', '2026-02-02 17:03:34', 'Book issued for 14 days semester loan.'),
(242, 12, 4, 'SETTLE_FINE', '2026-02-11 00:56:34', 'Overdue fine of Rs. 50 paid at the counter.'),
(243, 13, 1, 'ISSUE', '2026-01-25 14:58:34', 'Book issued for 14 days semester loan.'),
(244, 11, 2, 'RETURN', '2026-02-09 07:41:34', 'Book returned on time. Condition: Excellent.'),
(245, 12, 3, 'ISSUE', '2025-12-20 11:11:34', 'Research material borrowed for project work.'),
(246, 17, 1, 'UPDATE_STOCK', '2025-12-12 10:02:34', 'Added 5 new copies to inventory.'),
(247, 14, 2, 'REGISTER_BOOK', '2026-01-11 19:47:34', 'New engineering textbook added to collection.'),
(248, 16, 6, 'ISSUE', '2026-01-11 15:40:34', 'Core curriculum book issued for exam preparation.'),
(249, 7, 6, 'REGISTER_BOOK', '2025-12-15 20:37:34', 'New engineering textbook added to collection.'),
(250, 8, 2, 'RETURN', '2025-12-09 08:57:34', 'Book returned early. Condition: Good.'),
(251, 1, 6, 'PASSWORD_RESET', '2026-02-24 03:35:55', 'Password reset for student: Ahmad'),
(252, 1, 7, 'PASSWORD_RESET', '2026-02-24 03:40:28', 'Password reset for student: Sara'),
(254, 1, NULL, 'PASSWORD_RESET', '2026-02-24 03:47:35', 'Password reset for student (ID: 13): M. Hunayn'),
(255, 1, NULL, 'PASSWORD_RESET', '2026-02-24 03:47:53', 'Password reset for student (ID: 7): Sara'),
(256, 1, NULL, 'PASSWORD_RESET', '2026-02-24 03:56:59', 'Password reset for student (ID: 8): Zainab'),
(257, 1, NULL, 'PASSWORD_RESET', '2026-02-24 04:02:03', 'Password reset for student (ID: 6): Ahmad'),
(258, 1, 1, 'AUTOMATED_OVERDUE', '2026-04-21 03:20:15', 'System flagged Loan #21 as OVERDUE for User #1');

-- --------------------------------------------------------

--
-- Table structure for table `role_access`
--

CREATE TABLE `role_access` (
  `role_key` varchar(50) NOT NULL,
  `page_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_access`
--

INSERT INTO `role_access` (`role_key`, `page_id`) VALUES
('assistant_manager', 6),
('assistant_manager', 12),
('assistant_manager', 20),
('assistant_manager', 21),
('assistant_manager', 28),
('assistant_manager', 36),
('librarian', 12),
('librarian', 14),
('librarian', 18),
('librarian', 20),
('librarian', 21),
('librarian', 22),
('librarian', 27),
('librarian', 28),
('librarian', 35),
('librarian', 36),
('librarian', 37),
('librarian', 39),
('librarian', 40),
('librarian', 41),
('student', 6),
('student', 29),
('student', 30),
('student', 31),
('student', 35),
('student', 39),
('student', 40),
('super_admin', 2),
('super_admin', 3),
('super_admin', 4),
('super_admin', 5),
('super_admin', 6),
('super_admin', 7),
('super_admin', 9),
('super_admin', 10),
('super_admin', 11),
('super_admin', 12),
('super_admin', 13),
('super_admin', 14),
('super_admin', 15),
('super_admin', 18),
('super_admin', 20),
('super_admin', 21),
('super_admin', 22),
('super_admin', 27),
('super_admin', 28),
('super_admin', 29),
('super_admin', 30),
('super_admin', 31),
('super_admin', 35),
('super_admin', 36),
('super_admin', 37),
('super_admin', 39),
('super_admin', 40),
('super_admin', 41),
('suspended', 11),
('suspended', 13),
('suspended', 14),
('suspended', 15);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('footer_text', '© 2026 Universal Systems. All rights reserved.'),
('system_logo', 'https://cdn-icons-png.flaticon.com/512/906/906343.png'),
('system_name', 'The Library Management System');

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages`
--

CREATE TABLE `sys_pages` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `page_name` varchar(100) NOT NULL,
  `page_url` varchar(255) DEFAULT '#',
  `icon_class` varchar(50) DEFAULT 'bi bi-circle',
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sys_pages`
--

INSERT INTO `sys_pages` (`id`, `parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) VALUES
(2, 0, 'System Management', '#', 'bi bi-gear-fill', 10),
(3, 2, 'Manage Users', 'dashboards/super_admin/manage_users.php', 'bi bi-people', 1),
(4, 2, 'Manage Roles', 'dashboards/super_admin/manage_roles.php', 'bi bi-shield-lock', 2),
(5, 2, 'Manage Pages', 'dashboards/super_admin/manage_pages.php', 'bi bi-file-earmark-text', 3),
(6, 35, 'Student Dashboard', 'dashboards/student/student_dashboard.php', 'bi bi-speedometer2', 1),
(12, 0, 'Library Dashboard', 'dashboards/librarian/librarian_dashboard.php', 'bi bi-speedometer2', 1),
(18, 35, 'Student Directory', 'dashboards/librarian/student_directory.php', 'bi bi-people-fill', 2),
(20, 36, 'Register Book', 'dashboards/librarian/register_book.php', 'bi bi-journal-plus', 1),
(21, 36, 'Inventory Management', 'dashboards/librarian/inventory_management.php', 'bi bi-stack', 2),
(22, 37, 'Circulation Logs', 'dashboards/librarian/circulation_logs.php', 'bi bi-arrow-left-right', 1),
(27, 0, 'Digital Audit Trail', 'dashboards/librarian/digital_audit_trail.php', 'bi bi-shield-check', 50),
(28, 36, 'Book Categories', 'dashboards/librarian/book_categories.php', 'bi bi-tags-fill', 3),
(29, 35, 'My Reservations', 'dashboards/student/reservations.php', 'bi bi-calendar-check', 3),
(30, 35, 'Fine Payments', 'dashboards/student/fines.php', 'bi bi-credit-card', 4),
(31, 35, 'Reading History', 'dashboards/student/history.php', 'bi bi-journal-text', 5),
(35, 0, 'Student', '#', 'bi bi-person-badge', 40),
(36, 0, 'Library Operations', '#', 'bi bi-book-fill', 20),
(37, 0, 'Circulation', '#', 'bi bi-arrow-left-right', 30),
(39, 0, 'Requested Portal', '#', 'bi bi-bag-plus-fill', 45),
(40, 39, 'Student Request', 'dashboards/student/book_requests.php', 'bi bi-journal-plus', 1),
(41, 39, 'Manage Requests', 'dashboards/librarian/manage_requests.php', 'bi bi-clipboard2-check-fill', 2);

-- --------------------------------------------------------

--
-- Table structure for table `sys_roles`
--

CREATE TABLE `sys_roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_key` varchar(50) NOT NULL,
  `is_system_role` tinyint(1) DEFAULT 0 COMMENT '1=Cannot Delete'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sys_roles`
--

INSERT INTO `sys_roles` (`id`, `role_name`, `role_key`, `is_system_role`) VALUES
(1, 'Super Admin', 'super_admin', 1),
(4, 'Suspended', 'suspended', 1),
(9, 'Librarian', 'librarian', 0),
(10, 'student', 'student', 0),
(12, 'assistant manager', 'assistant_manager', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT 'General',
  `identity_no` varchar(50) DEFAULT NULL,
  `fines` decimal(10,2) DEFAULT 0.00,
  `borrow_limit` int(11) DEFAULT 5,
  `registration_no` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `avatar`, `phone`, `password`, `role`, `department`, `identity_no`, `fines`, `borrow_limit`, `registration_no`, `is_active`, `created_at`) VALUES
(1, 'Root Admin', 'admin@sys.com', NULL, NULL, '$2y$10$hHoMDaR/SnCFUWIn3OIt/ewfahZLQdk1QB/wD7x3IedOSC82p55nC', 'super_admin', 'General', '12345-1234567-1', 0.00, 5, 'ADM-001', 1, '2026-03-11 08:23:17'),
(3, 'Librarian', 'librarian@gmail.com', NULL, NULL, '$2y$10$hHoMDaR/SnCFUWIn3OIt/ewfahZLQdk1QB/wD7x3IedOSC82p55nC', 'suspended', 'General', NULL, 0.00, 5, NULL, 1, '2026-03-11 08:23:17'),
(5, 'Librarian', 'librarian1@gmail.com', NULL, NULL, '$2y$10$COxiOYoJ1MHONFEmZT9lJe6QEVGZyQZqZ73w9Isv52NsAeS48CuEu', 'librarian', 'General', NULL, 0.00, 5, NULL, 1, '2026-03-11 08:23:17'),
(6, 'Ahmad', 'ahmad.hassan@university.edu', NULL, NULL, '$2y$10$hHoMDaR/SnCFUWIn3OIt/ewfahZLQdk1QB/wD7x3IedOSC82p55nC', 'student', 'General', 'CNIC-42101-1234567-1', 0.00, 5, 'S-1001', 1, '2026-03-11 08:23:17'),
(7, 'Sara', 'sara.khan@university.edu', NULL, NULL, '$2y$10$hHoMDaR/SnCFUWIn3OIt/ewfahZLQdk1QB/wD7x3IedOSC82p55nC', 'student', 'General', 'CNIC-42101-2234567-2', 250.00, 5, 'S-1002', 1, '2026-03-11 08:23:17'),
(8, 'Zainab', 'zainab.ali@university.edu', NULL, '', '$2y$10$lY7as33mX43IEYA2US8PPOE58cPTP0dLomaP6S8gje4D3m2qpdWA2', 'student', 'General', 'CNIC-42101-3234567-3', 0.00, 5, 'S-1003', 1, '2026-03-11 08:23:17'),
(9, 'Usman', 'usman.sheikh@university.edu', NULL, NULL, '$2y$10$hHoMDaR/SnCFUWIn3OIt/ewfahZLQdk1QB/wD7x3IedOSC82p55nC', 'student', 'General', 'CNIC-42101-4234567-4', 0.00, 5, 'S-1004', 1, '2026-03-11 08:23:17'),
(10, 'Fatima', 'fatima.zahra@university.edu', NULL, NULL, '$2y$10$hHoMDaR/SnCFUWIn3OIt/ewfahZLQdk1QB/wD7x3IedOSC82p55nC', 'student', 'General', 'CNIC-42101-5234567-5', 0.00, 5, 'S-1005', 1, '2026-03-11 08:23:17'),
(11, 'Bilal', 'bilal.ahmed@university.edu', NULL, NULL, '$2y$10$hHoMDaR/SnCFUWIn3OIt/ewfahZLQdk1QB/wD7x3IedOSC82p55nC', 'student', 'General', 'CNIC-42101-6234567-6', 0.00, 5, 'S-1006', 1, '2026-03-11 08:23:17'),
(12, 'Arham', 'arham.khan@university.edu', NULL, NULL, '$2y$10$hHoMDaR/SnCFUWIn3OIt/ewfahZLQdk1QB/wD7x3IedOSC82p55nC', 'student', 'General', 'CNIC-42101-7234567-7', 0.00, 5, 'S-1007', 1, '2026-03-11 08:23:17'),
(13, 'M. Hunayn', 'mhunayn@university.edu', NULL, '03004833643', '$2y$10$llZpoAyNptnwN4M4dBTsRuBS9vRK/575yxijsvp23ObKVI/toi7AS', 'student', 'General', 'CNIC-42101-8234567-8', 0.00, 5, 'S-1008', 1, '2026-03-11 08:23:17'),
(14, 'Hamza', 'hamza.lodhi@university.edu', NULL, NULL, '$2y$10$hHoMDaR/SnCFUWIn3OIt/ewfahZLQdk1QB/wD7x3IedOSC82p55nC', 'student', 'General', 'CNIC-42101-9234567-9', 0.00, 5, 'S-1009', 1, '2026-03-11 08:23:17'),
(15, 'Ayesha', 'ayesha.malik@university.edu', NULL, NULL, '$2y$10$hHoMDaR/SnCFUWIn3OIt/ewfahZLQdk1QB/wD7x3IedOSC82p55nC', 'student', 'General', 'CNIC-42101-0234567-0', 0.00, 5, 'S-1010', 1, '2026-03-11 08:23:17'),
(16, 'Ibrahim', 'ibrahim.qureshi@university.edu', NULL, '', '$2y$10$fJGay5k.KQLF8v/./38Su.jPXq3d3fOSSDN3PqZ5GZU17QcbPngw2', 'student', 'General', 'CNIC-42101-1122334-1', 0.00, 5, 'S-1011', 1, '2026-03-11 08:23:17'),
(17, 'Maryam', 'maryam.siddiqui@university.edu', NULL, NULL, '$2y$10$hHoMDaR/SnCFUWIn3OIt/ewfahZLQdk1QB/wD7x3IedOSC82p55nC', 'student', 'General', 'CNIC-42101-5566778-2', 0.00, 5, 'S-1012', 1, '2026-03-11 08:23:17'),
(18, 'Professional Librarian', 'librarian@university.edu', NULL, NULL, '$2y$10$hHoMDaR/SnCFUWIn3OIt/ewfahZLQdk1QB/wD7x3IedOSC82p55nC', 'librarian', 'General', NULL, 0.00, 5, 'LIB-001', 1, '2026-03-11 08:23:17'),
(21, 'manager1@gmail.com', 'manager1@gmail.com', NULL, NULL, '$2y$10$hHoMDaR/SnCFUWIn3OIt/ewfahZLQdk1QB/wD7x3IedOSC82p55nC', 'assistant_manager', 'General', 'manager1@gmail.com', 0.00, 5, 'manager1@gmail.com', 1, '2026-04-28 03:31:58'),
(22, 'Sarahwajid', 'sarah@sys.com', NULL, '03004833643', '$2y$10$bu013k.fNV9/13aNZ2lOpe5ZnRDCRZZgDYO4aXEYNRmDxBljCvOZK', 'student', '', '12345', 0.00, 5, '11111111', 1, '2026-04-28 03:53:46'),
(23, 'Librarian Test', 'lib@test.com', NULL, NULL, '$2y$10$ystfV4RanM4wqaa.hs6YRe0EXhnDwuMb0rV9zmvQPRPOnbOXbhGN.', 'librarian', 'General', '1111111111111', 0.00, 5, '11111', 0, '2026-05-03 14:58:07'),
(24, 'Test User', 'test@example.com', NULL, NULL, '$2y$10$VaQnNiGQ8aFOdUsJOcfSRewEbnT.B7w5M4g1XTvDxzdiVOL6wB4TC', 'librarian', 'General', '1234567890123', 0.00, 5, '12345', 0, '2026-05-03 18:04:43');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_overdue_alerts`
-- (See below for the actual view)
--
CREATE TABLE `v_overdue_alerts` (
`borrowing_id` int(11)
,`student_id` int(11)
,`student_name` varchar(100)
,`student_email` varchar(100)
,`book_id` int(11)
,`book_title` varchar(255)
,`fine_per_day` decimal(10,2)
,`borrow_date` date
,`due_date` date
,`days_overdue` int(7)
,`projected_fine` decimal(16,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_overdue_alerts`
--
DROP TABLE IF EXISTS `v_overdue_alerts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_overdue_alerts`  AS SELECT `br`.`id` AS `borrowing_id`, `u`.`id` AS `student_id`, `u`.`name` AS `student_name`, `u`.`email` AS `student_email`, `b`.`id` AS `book_id`, `b`.`title` AS `book_title`, `b`.`fine_per_day` AS `fine_per_day`, `br`.`borrow_date` AS `borrow_date`, `br`.`due_date` AS `due_date`, to_days(curdate()) - to_days(`br`.`due_date`) AS `days_overdue`, round((to_days(curdate()) - to_days(`br`.`due_date`)) * `b`.`fine_per_day`,2) AS `projected_fine` FROM ((`lib_borrowings` `br` join `users` `u` on(`br`.`user_id` = `u`.`id`)) join `lib_books` `b` on(`br`.`book_id` = `b`.`id`)) WHERE `br`.`status` in ('borrowed','overdue') AND `br`.`due_date` < curdate() ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `book_requests`
--
ALTER TABLE `book_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `lib_authors`
--
ALTER TABLE `lib_authors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lib_books`
--
ALTER TABLE `lib_books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `lib_borrowings`
--
ALTER TABLE `lib_borrowings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `lib_categories`
--
ALTER TABLE `lib_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lib_notifications`
--
ALTER TABLE `lib_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_key` (`unique_key`);

--
-- Indexes for table `lib_reservations`
--
ALTER TABLE `lib_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `lib_reviews`
--
ALTER TABLE `lib_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`user_id`,`book_id`);

--
-- Indexes for table `lib_transactions`
--
ALTER TABLE `lib_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `role_access`
--
ALTER TABLE `role_access`
  ADD PRIMARY KEY (`role_key`,`page_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `sys_pages`
--
ALTER TABLE `sys_pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_roles`
--
ALTER TABLE `sys_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_key` (`role_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `idx_email` (`email`),
  ADD UNIQUE KEY `idx_identity` (`identity_no`),
  ADD UNIQUE KEY `idx_reg_no` (`registration_no`),
  ADD KEY `role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `book_requests`
--
ALTER TABLE `book_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lib_authors`
--
ALTER TABLE `lib_authors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `lib_books`
--
ALTER TABLE `lib_books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `lib_borrowings`
--
ALTER TABLE `lib_borrowings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `lib_categories`
--
ALTER TABLE `lib_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `lib_notifications`
--
ALTER TABLE `lib_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT for table `lib_reservations`
--
ALTER TABLE `lib_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lib_reviews`
--
ALTER TABLE `lib_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lib_transactions`
--
ALTER TABLE `lib_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=259;

--
-- AUTO_INCREMENT for table `sys_pages`
--
ALTER TABLE `sys_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `sys_roles`
--
ALTER TABLE `sys_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `book_requests`
--
ALTER TABLE `book_requests`
  ADD CONSTRAINT `book_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lib_books`
--
ALTER TABLE `lib_books`
  ADD CONSTRAINT `lib_books_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `lib_authors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lib_books_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `lib_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lib_borrowings`
--
ALTER TABLE `lib_borrowings`
  ADD CONSTRAINT `lib_borrowings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lib_borrowings_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `lib_books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lib_reservations`
--
ALTER TABLE `lib_reservations`
  ADD CONSTRAINT `lib_reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lib_reservations_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `lib_books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lib_transactions`
--
ALTER TABLE `lib_transactions`
  ADD CONSTRAINT `lib_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `lib_transactions_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `lib_books` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
