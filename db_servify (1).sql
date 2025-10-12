-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 12, 2025 at 05:49 PM
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
-- Database: `db_servify`
--

-- --------------------------------------------------------

--
-- Table structure for table `barangay_announcements`
--

CREATE TABLE `barangay_announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `date_posted` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangay_announcements`
--

INSERT INTO `barangay_announcements` (`announcement_id`, `title`, `content`, `date_posted`, `image_path`) VALUES
(1, 'Libreng Tuli Operation', 'Magdala lamang ng ID ng magulang', '2025-10-09 07:00:02', '../uploads/announcements/1759993202_tuli.jpg'),
(2, 'Job hiring', 'Nutri Asia', '2025-10-09 07:32:23', '../uploads/announcements/1759995143_f.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `hires`
--

CREATE TABLE `hires` (
  `id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `laborer_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `meeting_location` varchar(255) DEFAULT NULL,
  `status` enum('pending','accepted','declined') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hires`
--

INSERT INTO `hires` (`id`, `employer_id`, `laborer_id`, `message`, `meeting_location`, `status`, `created_at`) VALUES
(1, 1, 2, '500 per hour', 'sta rita guigtuinto', 'accepted', '2025-09-22 18:10:00'),
(2, 1, 2, '200 magbubuhat', 'sta rita guigtuinto', 'declined', '2025-09-22 18:37:14'),
(3, 2, 1, 'asd', 'asd', 'accepted', '2025-09-22 19:39:52');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `job_id` int(11) NOT NULL,
  `job_name` varchar(255) NOT NULL,
  `job_description` varchar(255) NOT NULL,
  `job_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`job_id`, `job_name`, `job_description`, `job_image`) VALUES
(1, 'Electrician', 'Handles electrical wiring and repairs.', NULL),
(2, 'Mechanic', 'Fixes and maintains vehicles.', NULL),
(3, 'Plumber', 'Installs and repairs pipes and fixtures.', NULL),
(4, 'Carpentry', 'Constructs and repairs wooden structures.', NULL),
(5, 'Welder', 'Joins metal parts together.', NULL),
(6, 'Handyman', 'Provides general home maintenance and repair services.', NULL),
(7, 'Personal Assistant', 'Assists with errands, scheduling, and personal tasks.', NULL),
(8, 'Gaming Coach', 'Provides coaching and guidance for improving video game skills.', NULL),
(9, 'Tutor', 'Teaches academic subjects or skills.', NULL),
(10, 'Cook', 'Prepares and cooks meals.', NULL),
(11, 'Driver', 'Provides transportation services.', NULL),
(12, 'Cleaning Service', 'Cleans homes and offices.', NULL),
(13, 'Pest Control', 'Eliminates household pests.', NULL),
(14, 'Personal Shopper', 'Helps with shopping needs.', NULL),
(15, 'Babysitter', 'Takes care of children.', NULL),
(16, 'Caretaker', 'Assists in personal care.', NULL),
(17, 'Massage', 'Provides relaxation and therapy.', NULL),
(18, 'Beauty Care', 'Offers beauty services.', NULL),
(19, 'Labor', 'Handles general labor tasks.', NULL),
(20, 'Arts', 'Creates artistic works.', NULL),
(21, 'Photography', 'Captures professional photos.', NULL),
(22, 'Videography', 'Records and edits videos.', NULL),
(23, 'Performer', 'Entertains audiences.', NULL),
(24, 'Seamstress', 'Repairs and makes clothes.', NULL),
(25, 'Graphic Designer', 'Creates visual designs for print, digital, and branding purposes.', 'electrician.jpg'),
(26, 'IT Support', 'Fixes tech issues.', NULL),
(27, 'Event Organizer', 'Plans and manages events.', NULL),
(28, 'DJ & Audio Services', 'Provides music entertainment.', NULL),
(29, 'Writing & Editing', 'Creates and edits content.', NULL),
(30, 'Pet Care', 'Takes care of pets.', NULL),
(31, 'Dog Walker', 'Walks and exercises dogs.', NULL),
(32, 'Companion Service', 'Offers companionship and support for social and personal needs.', NULL),
(33, 'Party Performer', 'Performs in costumes and provides entertainment for events and parties.', NULL),
(34, 'Street Performer', 'Performs live entertainment acts in public or community spaces.', NULL),
(35, 'Delivery Service', 'Delivers packages, food, or important items safely.', NULL),
(36, 'Fitness Trainer', 'Provides personal training, exercise guidance, and fitness motivation.', NULL),
(37, 'Furniture Assembler', 'Assembles furniture.', NULL),
(38, 'Personal Stylist', 'Advises on fashion.', NULL),
(39, 'Gardener', 'Maintains gardens, plants, and landscaping.', NULL),
(40, 'Laundry Service', 'Provides laundry, washing, ironing, and folding clothes.', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `laborer_ratings`
--

CREATE TABLE `laborer_ratings` (
  `id` int(11) NOT NULL,
  `laborer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laborer_ratings`
--

INSERT INTO `laborer_ratings` (`id`, `laborer_id`, `user_id`, `rating`, `review`, `created_at`) VALUES
(13, 1, 2, 5, '', '2025-09-29 14:52:27');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `location_id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`location_id`, `location_name`, `barangay`, `city`, `province`, `created_at`) VALUES
(1, 'Recoleto I', 'Sta. Rita', 'Guiguinto', 'Bulacan', '2025-09-01 06:09:14'),
(2, 'Recoleto II', 'Sta. Rita', 'Guiguinto', 'Bulacan', '2025-09-01 06:09:14'),
(3, 'Hangga', 'Sta. Rita', 'Guiguinto', 'Bulacan', '2025-09-01 06:09:14'),
(4, 'Encanto', 'Sta. Rita', 'Guiguinto', 'Bulacan', '2025-09-01 06:09:14');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `message`, `timestamp`) VALUES
(1, 1, 2, 'asdasd', '2025-08-29 10:50:07'),
(2, 2, 1, 'hello', '2025-08-29 10:52:25'),
(3, 1, 2, 'kamusta ka', '2025-08-29 10:54:44'),
(4, 1, 2, 'hoy', '2025-08-29 11:02:00'),
(5, 2, 1, 'ayos lang ako', '2025-08-29 11:02:21'),
(6, 1, 2, 'mabuti naman', '2025-08-29 11:05:04'),
(7, 2, 1, 'kailangan ko ng tubero', '2025-08-29 11:05:14'),
(8, 2, 12, 'hi', '2025-08-29 14:12:27'),
(9, 2, 1, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '2025-08-30 10:22:30'),
(10, 2, 1, 'asd', '2025-08-30 10:32:33'),
(11, 1, 2, 'musta', '2025-08-30 10:34:36'),
(12, 2, 1, 'ayos lang', '2025-08-30 10:34:43'),
(13, 2, 20, 'hi', '2025-08-30 11:59:06'),
(14, 2, 5, 'hi', '2025-09-22 05:19:50'),
(16, 52, 2, 'are you available this afternoon?', '2025-09-29 12:35:18'),
(17, 2, 52, 'yes I am', '2025-09-29 12:41:01');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `additional_details` text DEFAULT NULL,
  `report_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `user_id`, `reason`, `additional_details`, `report_date`, `status`) VALUES
(1, 6, 'false_information', '', '2025-04-01 09:30:52', 'confirmed'),
(2, 6, 'nudity', '', '2025-04-01 09:30:52', 'confirmed'),
(3, 6, 'harassment', '', '2025-04-01 09:30:52', 'confirmed'),
(4, 6, 'false_information', '', '2025-04-01 09:32:17', 'confirmed'),
(5, 6, 'nudity', '', '2025-04-01 09:32:17', 'confirmed'),
(6, 6, 'harassment', '', '2025-04-01 09:32:17', 'confirmed'),
(7, 6, 'false_information', '', '2025-04-01 09:32:30', 'confirmed'),
(8, 6, 'nudity', '', '2025-04-01 09:32:30', 'confirmed'),
(9, 6, 'harassment', '', '2025-04-01 09:32:30', 'confirmed'),
(10, 6, 'false_information', '', '2025-04-01 09:37:44', 'confirmed'),
(11, 6, 'nudity', '', '2025-04-01 09:37:44', 'confirmed'),
(12, 6, 'harassment', '', '2025-04-01 09:37:44', 'confirmed'),
(13, 6, 'false_information', '', '2025-04-01 09:40:11', 'rejected'),
(14, 6, 'nudity', '', '2025-04-01 09:40:11', 'confirmed'),
(15, 6, 'harassment', '', '2025-04-01 09:40:11', 'rejected'),
(16, 6, 'false_information', '', '2025-04-01 10:18:10', 'confirmed'),
(17, 6, 'nudity', '', '2025-04-01 10:18:10', 'rejected'),
(18, 6, 'harassment', '', '2025-04-01 10:18:10', 'rejected'),
(19, 6, 'hate_speech', '', '2025-04-01 10:18:34', 'confirmed'),
(20, 6, 'scam', '', '2025-04-01 10:18:34', 'confirmed'),
(21, 6, 'hate_speech', '', '2025-04-01 10:19:57', 'confirmed'),
(22, 6, 'scam', '', '2025-04-01 10:19:57', 'confirmed'),
(23, 6, 'nudity', 'edi wow', '2025-04-01 10:45:46', 'rejected'),
(24, 6, 'other', 'edi wow', '2025-04-01 10:45:46', 'rejected'),
(25, 6, 'false_information', '', '2025-04-01 11:02:02', 'confirmed'),
(26, 6, 'nudity', '', '2025-04-01 11:02:02', 'confirmed'),
(27, 6, 'false_information', '', '2025-04-01 11:26:16', 'rejected'),
(28, 5, 'false_information', '', '2025-04-01 11:28:39', 'rejected'),
(29, 1, 'Inappropriate behavior', '', '2025-04-01 11:35:38', 'rejected'),
(30, 2, 'Fraudulent activity', '', '2025-04-01 11:37:52', 'rejected'),
(31, 5, 'false_information', '', '2025-04-01 11:44:08', 'rejected'),
(32, 2, 'false_information', '', '2025-04-01 11:51:27', 'rejected'),
(33, 2, 'nudity', '', '2025-04-01 11:51:27', 'rejected'),
(34, 1, 'false_information', '', '2025-04-01 11:51:55', 'confirmed'),
(35, 1, 'hate_speech', '', '2025-04-02 05:14:52', 'confirmed'),
(36, 1, 'false_information', '', '2025-04-02 06:31:39', 'confirmed'),
(37, 1, 'other', '', '2025-04-02 06:42:32', 'confirmed'),
(38, 5, 'nudity', 'asdsassssssssssssss', '2025-04-02 06:51:09', 'confirmed'),
(39, 1, 'false_information', '', '2025-04-06 02:44:11', 'confirmed'),
(40, 1, 'nudity', '', '2025-04-06 02:44:11', 'confirmed'),
(42, 17, 'spam', '', '2025-04-06 02:56:50', 'pending'),
(46, 30, 'false_information', '', '2025-09-23 12:12:55', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `middlename` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `fb_link` varchar(255) NOT NULL,
  `location` varchar(100) NOT NULL,
  `date_created` date NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contact` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `rating` int(11) NOT NULL,
  `credit_score` int(11) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `profile_picture` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `firstname`, `middlename`, `lastname`, `fb_link`, `location`, `date_created`, `email`, `password`, `contact`, `role`, `rating`, `credit_score`, `is_verified`, `profile_picture`) VALUES
(1, 'Michael', 'Tan', 'Santiago', 'https://www.facebook.com/profile.php?id=61554848759960', 'Encanto', '2025-03-28', 'shane@gmail.com', 'asd', '09163148584', 'laborer', 0, 81, 1, ''),
(2, 'Junior', '', 'Landayan', 'fb', 'Recoleto I', '2025-03-28', 'asd@gmail.com', 'asd', '099999999', 'laborer', 0, 91, 1, 'uploads/profile_pics/Screenshot 2025-04-12 062623.png'),
(5, 'Renato', 'C', 'Nicolas', 'https://www.youtube.com/watch?v=lXcX5llJeko', 'Encanto', '2025-03-28', 'q@gmail.com', 'q', '09888984757', 'laborer', 0, 91, 1, 'uploads/profile_pics/Screenshot 2025-04-12 064541.png'),
(6, 'Wilbert', 'w', 'Cruz', 'https://www.youtube.com/watch?v=lXcX5llJeko', 'Hangga', '2025-03-28', 'w@gmail.com', 'w', 'w', 'laborer', 0, 59, 0, ''),
(7, 'admin', 'admin', 'admin', 'admin', 'Hangga', '0000-00-00', 'admin@gmail.com', 'admin', '09163148584', 'admin', 0, 0, 0, ''),
(12, 'Fhely', 'f', 'Cruz', '', 'Hangga', '2025-04-04', 'f', 'f', 'f', 'laborer', 0, 100, 1, ''),
(14, 'Arvilyn Kate', 'N', 'Nicerio', '', 'Encanto', '2025-04-05', 'arvi@gmail.com', 'arvi', '09163148584', 'laborer', 0, 100, 0, 'uploads/profile_pics/384121770_875286400608272_2476302774052827257_n.jpg'),
(17, 'John', '', 'Doe', 'https://www.facebook.com/ralfh.rolan.co.herrera', 'Recoleto II', '2025-04-10', 'ralfh@gmail.com', 'ralfh', '09999999899', 'laborer', 0, 100, 0, 'uploads/profile_pics/ralfh.png'),
(19, 'Arvin', 'M.', 'Dela Cruz', 'https://facebook.com/arvin', 'Recoleto I', '2025-04-12', 'arvin1@example.com', 'password1', '09170000001', 'laborer', 4, 82, 1, 'uploads/profile_pics/default.jpg'),
(20, 'Bea', 'L.', 'Reyes', 'https://facebook.com/bea', 'Recoleto I', '2025-04-12', 'bea2@example.com', 'password2', '09170000002', 'laborer', 5, 90, 1, 'uploads/profile_pics/default.jpg'),
(21, 'Carl', 'T.', 'Santos', 'https://facebook.com/carl', 'Recoleto II', '2025-04-12', 'carl3@example.com', 'password3', '09170000003', 'laborer', 3, 70, 0, 'uploads/profile_pics/default.jpg'),
(22, 'Dina', 'G.', 'Lopez', 'https://facebook.com/dina', 'Recoleto I', '2025-04-12', 'dina4@example.com', 'password4', '09170000004', 'laborer', 4, 85, 1, 'uploads/profile_pics/default.jpg'),
(23, 'Erwin', 'A.', 'Torres', 'https://facebook.com/erwin', 'Recoleto I', '2025-04-12', 'erwin5@example.com', 'password5', '09170000005', 'laborer', 5, 95, 0, 'uploads/profile_pics/default.jpg'),
(24, 'Faith', 'D.', 'Garcia', 'https://facebook.com/faith', 'Recoleto II', '2025-04-12', 'faith6@example.com', 'password6', '09170000006', 'laborer', 4, 88, 1, 'uploads/profile_pics/default.jpg'),
(25, 'Gino', 'P.', 'Velasquez', 'https://facebook.com/gino', 'Recoleto II', '2025-04-12', 'gino7@example.com', 'password7', '09170000007', 'laborer', 2, 60, 0, 'uploads/profile_pics/default.jpg'),
(26, 'Hannah', 'C.', 'Marquez', 'https://facebook.com/hannah', 'Recoleto II', '2025-04-12', 'hannah8@example.com', 'password8', '09170000008', 'laborer', 3, 78, 0, 'uploads/profile_pics/default.jpg'),
(27, 'Ian', 'F.', 'Rivera', 'https://facebook.com/ian', 'Recoleto II', '2025-04-12', 'ian9@example.com', 'password9', '09170000009', 'laborer', 4, 84, 1, 'uploads/profile_pics/default.jpg'),
(28, 'Jane', 'V.', 'Cruz', 'https://facebook.com/jane', 'Hangga', '2025-04-12', 'jane10@example.com', 'password10', '09170000010', 'laborer', 5, 92, 1, 'uploads/profile_pics/default.jpg'),
(29, 'Karl', 'Z.', 'Domingo', 'https://facebook.com/karl', 'Recoleto II', '2025-04-12', 'karl11@example.com', 'password11', '09170000011', 'laborer', 3, 76, 1, 'uploads/profile_pics/default.jpg'),
(30, 'Liza', 'K.', 'Bautista', 'https://facebook.com/liza', 'Recoleto I', '2025-04-12', 'liza12@example.com', 'password12', '09170000012', 'laborer', 4, 80, 0, 'uploads/profile_pics/default.jpg'),
(31, 'Mark', 'J.', 'Flores', 'https://facebook.com/mark', 'Encanto', '2025-04-12', 'mark13@example.com', 'password13', '09170000013', 'laborer', 5, 89, 1, 'uploads/profile_pics/default.jpg'),
(32, 'Nina', 'E.', 'Aguilar', 'https://facebook.com/nina', 'Hangga', '2025-04-12', 'nina14@example.com', 'password14', '09170000014', 'laborer', 3, 65, 0, 'uploads/profile_pics/default.jpg'),
(33, 'Owen', 'L.', 'Aquino', 'https://facebook.com/owen', 'Recoleto I', '2025-04-12', 'owen15@example.com', 'password15', '09170000015', 'laborer', 4, 85, 1, 'uploads/profile_pics/default.jpg'),
(34, 'Paula', 'S.', 'Delos Reyes', 'https://facebook.com/paula', 'Recoleto II', '2025-04-12', 'paula16@example.com', 'password16', '09170000016', 'laborer', 2, 59, 0, 'uploads/profile_pics/default.jpg'),
(35, 'Quinn', 'T.', 'Navarro', 'https://facebook.com/quinn', 'Encanto', '2025-04-12', 'quinn17@example.com', 'password17', '09170000017', 'laborer', 3, 73, 1, 'uploads/profile_pics/default.jpg'),
(36, 'Ruth', 'H.', 'Fernandez', 'https://facebook.com/ruth', 'Recoleto II', '2025-04-12', 'ruth18@example.com', 'password18', '09170000018', 'laborer', 4, 81, 0, 'uploads/profile_pics/default.jpg'),
(37, 'Sam', 'Q.', 'Padilla', 'https://facebook.com/sam', 'Hangga', '2025-04-12', 'sam19@example.com', 'password19', '09170000019', 'laborer', 5, 94, 1, 'uploads/profile_pics/default.jpg'),
(38, 'Tina', 'R.', 'Galang', 'https://facebook.com/tina', 'Encanto', '2025-04-12', 'tina20@example.com', 'password20', '09170000020', 'laborer', 3, 72, 0, 'uploads/profile_pics/default.jpg'),
(39, 'Ulysses', 'M.', 'Castro', 'https://facebook.com/ulysses', 'Hangga', '2025-04-12', 'ulysses21@example.com', 'password21', '09170000021', 'laborer', 4, 83, 1, 'uploads/profile_pics/default.jpg'),
(40, 'Vina', 'N.', 'Escobar', 'https://facebook.com/vina', 'Recoleto II', '2025-04-12', 'vina22@example.com', 'password22', '09170000022', 'laborer', 5, 90, 1, 'uploads/profile_pics/default.jpg'),
(41, 'Warren', 'B.', 'Medina', 'https://facebook.com/warren', 'Recoleto II', '2025-04-12', 'warren23@example.com', 'password23', '09170000023', 'laborer', 3, 67, 0, 'uploads/profile_pics/default.jpg'),
(42, 'Xandra', 'I.', 'Soriano', 'https://facebook.com/xandra', 'Recoleto II', '2025-04-12', 'xandra24@example.com', 'password24', '09170000024', 'laborer', 4, 88, 1, 'uploads/profile_pics/default.jpg'),
(43, 'Yves', 'U.', 'Ocampo', 'https://facebook.com/yves', 'Recoleto II', '2025-04-12', 'yves25@example.com', 'password25', '09170000025', 'laborer', 5, 91, 0, 'uploads/profile_pics/default.jpg'),
(44, 'Zara', 'C.', 'Salvador', 'https://facebook.com/zara', 'Recoleto II', '2025-04-12', 'zara26@example.com', 'password26', '09170000026', 'laborer', 2, 63, 1, 'uploads/profile_pics/default.jpg'),
(45, 'Allen', 'D.', 'Vergara', 'https://facebook.com/allen', 'Hangga', '2025-04-12', 'allen27@example.com', 'password27', '09170000027', 'laborer', 4, 80, 0, 'uploads/profile_pics/default.jpg'),
(46, 'Bianca', 'E.', 'Ramos', 'https://facebook.com/bianca', 'Hangga', '2025-04-12', 'bianca28@example.com', 'password28', '09170000028', 'laborer', 3, 74, 0, 'uploads/profile_pics/default.jpg'),
(47, 'Cyrus', 'G.', 'Yap', 'https://facebook.com/cyrus', 'Recoleto II', '2025-04-12', 'cyrus29@example.com', 'password29', '09170000029', 'laborer', 5, 96, 1, 'uploads/profile_pics/default.jpg'),
(48, 'Daisy', 'H.', 'Baluyot', 'https://facebook.com/daisy', 'Recoleto II', '2025-04-12', 'daisy30@example.com', 'password30', '09170000030', 'laborer', 4, 87, 1, 'uploads/profile_pics/default.jpg'),
(51, 'tyu', 'tyu', 'tyu', 'tyu', 'Hangga', '2025-09-15', 'asd', 'asd', '09876554332', 'laborer', 0, 100, 0, ''),
(52, 'Shane', 'Kherby N.', 'Sahagun', 'https://www.com/shane.sahagun.493460/', 'Recoleto I', '2025-09-27', 'shane1@gmail.com', 'Qwertyuiop1?', '09163148584', 'client', 0, 100, 0, 'uploads/profile_pics/a6b64405-20a1-46d7-a42c-6d372fd43853.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `user_jobs`
--

CREATE TABLE `user_jobs` (
  `user_job_id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `job_id` int(10) UNSIGNED NOT NULL,
  `job_description` varchar(255) NOT NULL,
  `job_image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_jobs`
--

INSERT INTO `user_jobs` (`user_job_id`, `user_id`, `job_id`, `job_description`, `job_image`) VALUES
(1, 1, 8, '', ''),
(2, 1, 10, '', ''),
(3, 1, 11, '', ''),
(4, 1, 1, '', ''),
(6, 2, 2, '', ''),
(7, 1, 9, '', ''),
(11, 6, 25, 'uiiiai\r\n', ''),
(12, 2, 11, 'expert at driving', ''),
(13, 5, 1, 'Can fix electrical wiring', ''),
(15, 12, 7, '---ABOUT ME---\r\nako po ay tambay na wala work\r\n\r\n---CONTACT---\r\nFacebook: \r\nMobile\r\n', ''),
(18, 2, 3, 'expert at plumbing', '../uploads/1744193721_plumber.png'),
(26, 2, 12, 'qqq', '../uploads/1744199004_electrician.jpg'),
(27, 6, 1, 'electrician akoooo', '../uploads/1744199301_plumber.png'),
(29, 16, 25, 'Logo Maker and Photo Editor', '../uploads/1744282759_1.jpg'),
(33, 17, 3, 'Call center / part time tubero sa bakanteng araw at oras', 'job_67f7b47c487766.11837505.png'),
(34, 14, 30, 'Animal lover  /  Furmommy', '../uploads/profile_pics/default.jpg'),
(35, 1, 1, 'Licensed Electrician available for home service', ''),
(37, 3, 3, 'Reliable and experienced plumber', ''),
(39, 5, 5, 'Welding and fabrication specialist', ''),
(40, 6, 6, 'Home repairs and maintenance', ''),
(41, 7, 7, 'Queueing service for government/private', ''),
(42, 8, 8, 'Gaming account boosting and coaching', ''),
(43, 9, 9, 'Math and English tutor for kids', ''),
(44, 10, 10, 'Home-based and private cook', ''),
(45, 11, 11, 'Driver with license and experience', ''),
(46, 12, 12, 'House and office cleaning service', ''),
(47, 13, 13, 'Pest control for home and garden', ''),
(48, 14, 14, 'Personal shopper for groceries or clothes', ''),
(49, 15, 15, 'Babysitting services available on call', ''),
(50, 16, 16, 'Caretaker for elderly or sick', ''),
(51, 17, 17, 'Licensed massage therapist', ''),
(52, 18, 18, 'Beauty care services (home service)', ''),
(53, 19, 19, 'Heavy and light laborer', ''),
(54, 20, 20, 'Visual artist for custom designs', ''),
(55, 21, 21, 'Event and studio photographer', ''),
(56, 22, 22, 'Video shooting and editing expert', ''),
(57, 23, 23, 'Performer for parties and shows', ''),
(58, 24, 24, 'Skilled seamstress for custom fits', ''),
(59, 25, 25, 'Graphics designer and layout artist', ''),
(60, 26, 26, 'IT support for homes and offices', ''),
(61, 27, 27, 'Event organizing and coordination', ''),
(62, 28, 28, 'DJ and audio system setup', ''),
(63, 29, 29, 'Writer/editor for blog and academic', ''),
(64, 30, 30, 'Pet sitting and grooming', ''),
(65, 1, 31, 'Experienced dog walker daily', ''),
(67, 3, 33, 'Costume performer for events', ''),
(68, 4, 34, 'Street performer for gigs and acts', ''),
(70, 6, 36, 'Gym buddy and fitness motivator', ''),
(71, 7, 37, 'Assembling furniture (IKEA etc.)', ''),
(72, 8, 38, 'Styling for events and shoots', ''),
(73, 9, 39, 'Plant care and gardening help', ''),
(74, 10, 40, 'Content creation and scheduling', ''),
(75, 2, 26, 'good in troubleshooting', '../uploads/1744418641_2792e8e0-9f52-4611-abfd-49995d04eab2.jpg'),
(76, 52, 1, '', '');

-- --------------------------------------------------------

--
-- Table structure for table `verification_requests`
--

CREATE TABLE `verification_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id_proof` varchar(255) NOT NULL,
  `supporting_doc` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verification_requests`
--

INSERT INTO `verification_requests` (`request_id`, `user_id`, `id_proof`, `supporting_doc`, `status`) VALUES
(1, 1, '69a2cfba-58e0-410e-aef6-dadc2ee1e3d1.jpg', 'adb9894f-794c-4355-8285-d4d50db2f6da.jpg', 'approved'),
(2, 2, '69a2cfba-58e0-410e-aef6-dadc2ee1e3d1.jpg', '69a2cfba-58e0-410e-aef6-dadc2ee1e3d1.jpg', 'approved'),
(3, 5, 'adb9894f-794c-4355-8285-d4d50db2f6da.jpg', '69a2cfba-58e0-410e-aef6-dadc2ee1e3d1.jpg', 'rejected'),
(4, 5, 'adb9894f-794c-4355-8285-d4d50db2f6da.jpg', '69a2cfba-58e0-410e-aef6-dadc2ee1e3d1.jpg', 'rejected'),
(5, 5, 'adb9894f-794c-4355-8285-d4d50db2f6da.jpg', 'aee282d7-3bb8-4f8a-9b41-758945247a75.jpg', 'rejected'),
(6, 5, '69a2cfba-58e0-410e-aef6-dadc2ee1e3d1.jpg', 'aee282d7-3bb8-4f8a-9b41-758945247a75.jpg', 'approved'),
(7, 6, '69a2cfba-58e0-410e-aef6-dadc2ee1e3d1.jpg', 'adb9894f-794c-4355-8285-d4d50db2f6da.jpg', 'rejected'),
(8, 6, '69a2cfba-58e0-410e-aef6-dadc2ee1e3d1.jpg', 'aee282d7-3bb8-4f8a-9b41-758945247a75.jpg', 'rejected'),
(9, 12, 'aee282d7-3bb8-4f8a-9b41-758945247a75.jpg', '69a2cfba-58e0-410e-aef6-dadc2ee1e3d1.jpg', 'rejected'),
(10, 12, 'aee282d7-3bb8-4f8a-9b41-758945247a75.jpg', '69a2cfba-58e0-410e-aef6-dadc2ee1e3d1.jpg', 'approved'),
(11, 12, 'aee282d7-3bb8-4f8a-9b41-758945247a75.jpg', '69a2cfba-58e0-410e-aef6-dadc2ee1e3d1.jpg', 'rejected'),
(12, 12, 'aee282d7-3bb8-4f8a-9b41-758945247a75.jpg', '69a2cfba-58e0-410e-aef6-dadc2ee1e3d1.jpg', 'rejected'),
(13, 12, 'aee282d7-3bb8-4f8a-9b41-758945247a75.jpg', '69a2cfba-58e0-410e-aef6-dadc2ee1e3d1.jpg', 'rejected'),
(14, 14, '69a2cfba-58e0-410e-aef6-dadc2ee1e3d1.jpg', 'adb9894f-794c-4355-8285-d4d50db2f6da.jpg', 'approved'),
(15, 17, '1744288819_id_bg2.png', '1744288819_proof_bg2.png', 'rejected');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barangay_announcements`
--
ALTER TABLE `barangay_announcements`
  ADD PRIMARY KEY (`announcement_id`);

--
-- Indexes for table `hires`
--
ALTER TABLE `hires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employer_id` (`employer_id`),
  ADD KEY `laborer_id` (`laborer_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_id`);

--
-- Indexes for table `laborer_ratings`
--
ALTER TABLE `laborer_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laborer_id` (`laborer_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_jobs`
--
ALTER TABLE `user_jobs`
  ADD PRIMARY KEY (`user_job_id`);

--
-- Indexes for table `verification_requests`
--
ALTER TABLE `verification_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barangay_announcements`
--
ALTER TABLE `barangay_announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hires`
--
ALTER TABLE `hires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `laborer_ratings`
--
ALTER TABLE `laborer_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `user_jobs`
--
ALTER TABLE `user_jobs`
  MODIFY `user_job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `verification_requests`
--
ALTER TABLE `verification_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `hires`
--
ALTER TABLE `hires`
  ADD CONSTRAINT `hires_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `hires_ibfk_2` FOREIGN KEY (`laborer_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `laborer_ratings`
--
ALTER TABLE `laborer_ratings`
  ADD CONSTRAINT `laborer_ratings_ibfk_1` FOREIGN KEY (`laborer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `laborer_ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `verification_requests`
--
ALTER TABLE `verification_requests`
  ADD CONSTRAINT `verification_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
