-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 12, 2026 at 09:51 AM
-- Server version: 8.0.40
-- PHP Version: 8.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sanbongda`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `tournament_id` bigint UNSIGNED DEFAULT NULL,
  `team_id` bigint UNSIGNED DEFAULT NULL,
  `booking_type` enum('regular','tournament') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular' COMMENT 'Phân loại booking',
  `status` enum('pending','confirmed','cancelled','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái booking: pending = chờ xác nhận, confirmed = đã xác nhận, cancelled = đã hủy, completed = đã hoàn thành',
  `field_id` bigint UNSIGNED NOT NULL,
  `day_book` date NOT NULL COMMENT 'Ngày đặt sân',
  `start_time` time NOT NULL COMMENT 'Giờ bắt đầu',
  `end_time` time NOT NULL COMMENT 'Giờ kết thúc',
  `total_price` decimal(10,2) NOT NULL COMMENT 'Tổng giá tiền',
  `payment_status` enum('pending','paid','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái thanh toán: pending = chờ thanh toán, paid = đã thanh toán, failed = từ chối',
  `payment_method` enum('cash','transfer') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Phương thức thanh toán: cash = tiền mặt, transfer = chuyển khoản',
  `payment_proof` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đường dẫn ảnh bill chuyển khoản',
  `payment_confirmed_at` timestamp NULL DEFAULT NULL COMMENT 'Thời gian owner xác nhận thanh toán',
  `confirmed_by_user_id` bigint UNSIGNED DEFAULT NULL COMMENT 'ID của user (owner/admin) xác nhận thanh toán',
  `payment_note` text COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú của owner về thanh toán',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `user_id`, `tournament_id`, `team_id`, `booking_type`, `status`, `field_id`, `day_book`, `start_time`, `end_time`, `total_price`, `payment_status`, `payment_method`, `payment_proof`, `payment_confirmed_at`, `confirmed_by_user_id`, `payment_note`, `created_at`, `updated_at`) VALUES
(4, 8, 1, NULL, 'tournament', 'pending', 7, '2026-04-09', '06:00:00', '12:00:00', 500000.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(5, 8, 1, NULL, 'tournament', 'pending', 7, '2026-04-09', '12:00:00', '18:00:00', 500000.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(6, 8, 1, NULL, 'tournament', 'pending', 8, '2026-04-09', '06:00:00', '12:00:00', 500000.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(7, 8, 1, NULL, 'tournament', 'pending', 8, '2026-04-09', '12:00:00', '18:00:00', 500000.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(8, 8, 1, NULL, 'tournament', 'pending', 7, '2026-04-10', '06:00:00', '12:00:00', 500000.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(9, 8, 1, NULL, 'tournament', 'pending', 7, '2026-04-10', '12:00:00', '18:00:00', 500000.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(10, 8, 1, NULL, 'tournament', 'pending', 8, '2026-04-10', '06:00:00', '12:00:00', 500000.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(11, 8, 1, NULL, 'tournament', 'pending', 8, '2026-04-10', '12:00:00', '18:00:00', 500000.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(24, 23, NULL, NULL, 'regular', 'pending', 12, '2026-05-07', '16:30:00', '17:30:00', 450000.00, 'failed', 'cash', NULL, NULL, NULL, 'tao chưa nhận được tiền', '2026-05-07 01:26:08', '2026-05-07 03:27:41'),
(45, 23, NULL, NULL, 'regular', 'pending', 1, '2026-05-08', '18:30:00', '19:00:00', 150000.00, 'failed', 'cash', NULL, NULL, NULL, 'test', '2026-05-08 03:27:37', '2026-05-08 08:28:30'),
(129, 31, NULL, NULL, 'regular', 'confirmed', 1, '2026-05-11', '20:00:00', '21:00:00', 300000.00, 'paid', 'transfer', 'https://kramslcflmycdwngcpcg.supabase.co/storage/v1/object/public/image/payment-proofs/1778500910254-0tt5j1q.jpg', '2026-05-11 05:04:51', 2, 'Đã nhận chuyển khoản đầy đủ', '2026-05-11 04:54:24', '2026-05-11 05:04:51'),
(130, 23, NULL, NULL, 'regular', 'pending', 1, '2026-05-17', '06:00:00', '23:00:00', 6460000.00, 'failed', 'transfer', NULL, NULL, NULL, 'test', '2026-05-11 05:21:48', '2026-05-11 05:26:25'),
(137, 23, NULL, NULL, 'regular', 'pending', 14, '2026-05-11', '22:00:00', '23:00:00', 0.00, 'failed', 'cash', NULL, NULL, NULL, 'test', '2026-05-11 07:00:51', '2026-05-11 07:01:09');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-07gN3upVJRoxhhjr', 'a:1:{s:11:\"valid_until\";i:1778504184;}', 1779711984),
('laravel-cache-0SziEWg8WoktRr48', 'a:1:{s:11:\"valid_until\";i:1778234937;}', 1779442497),
('laravel-cache-0wwSGnIznBRbtraO', 'a:1:{s:11:\"valid_until\";i:1778501865;}', 1779711465),
('laravel-cache-1AVfUx10y93qXiwO', 'a:1:{s:11:\"valid_until\";i:1778037687;}', 1779247347),
('laravel-cache-1mPc9Ph8xiUZHmZE', 'a:1:{s:11:\"valid_until\";i:1778501688;}', 1779710088),
('laravel-cache-1XA1lBKFaTyDMonU', 'a:1:{s:11:\"valid_until\";i:1778562150;}', 1779771210),
('laravel-cache-2UFl4mGX111EePh4', 'a:1:{s:11:\"valid_until\";i:1778036698;}', 1779246358),
('laravel-cache-3qM3GjEWlASUo54b', 'a:1:{s:11:\"valid_until\";i:1778039791;}', 1779249451),
('laravel-cache-4R9PIJTZG903TLt3', 'a:1:{s:11:\"valid_until\";i:1778040868;}', 1779250408),
('laravel-cache-aDjcKgGZBTilngzl', 'a:1:{s:11:\"valid_until\";i:1778502303;}', 1779711843),
('laravel-cache-B5CYT2E4gpjM8421', 'a:1:{s:11:\"valid_until\";i:1778504321;}', 1779713981),
('laravel-cache-bXzgZhGWrojnfMZX', 'a:1:{s:11:\"valid_until\";i:1778500020;}', 1779709560),
('laravel-cache-CCUfBZLC3j8fBj7x', 'a:1:{s:11:\"valid_until\";i:1778038390;}', 1779247570),
('laravel-cache-CQFqT0CBU7MZuaeH', 'a:1:{s:11:\"valid_until\";i:1778040007;}', 1779249667),
('laravel-cache-cvwVzpNO3Y6185pN', 'a:1:{s:11:\"valid_until\";i:1778038479;}', 1779248139),
('laravel-cache-DX2CnFLVYe0BsFzG', 'a:1:{s:11:\"valid_until\";i:1778502155;}', 1779710735),
('laravel-cache-EvZ5DRVZd3ejM5rT', 'a:1:{s:11:\"valid_until\";i:1778037619;}', 1779247279),
('laravel-cache-geEDMEltwHGpta2N', 'a:1:{s:11:\"valid_until\";i:1778405164;}', 1779613324),
('laravel-cache-hzaaqfE6lq4Mk1s5', 'a:1:{s:11:\"valid_until\";i:1778505852;}', 1779714192),
('laravel-cache-i0fbD14L7TZi00mO', 'a:1:{s:11:\"valid_until\";i:1778232766;}', 1779442426),
('laravel-cache-iXgRiMzIVMJ6pcUv', 'a:1:{s:11:\"valid_until\";i:1778080020;}', 1779289680),
('laravel-cache-j6nAIgAEHhnAwXPk', 'a:1:{s:11:\"valid_until\";i:1778406301;}', 1779614941),
('laravel-cache-L7aijDUArU6hQfpG', 'a:1:{s:11:\"valid_until\";i:1778507344;}', 1779716164),
('laravel-cache-LJxoxOdNLjb8wNLh', 'a:1:{s:11:\"valid_until\";i:1778037791;}', 1779247451),
('laravel-cache-LN0aD1W0t02Q8vJA', 'a:1:{s:11:\"valid_until\";i:1778034993;}', 1779244653),
('laravel-cache-MxpyW3IrV8tSlTi3', 'a:1:{s:11:\"valid_until\";i:1778040237;}', 1779249837),
('laravel-cache-MYEiMrszVSrhdTXC', 'a:1:{s:11:\"valid_until\";i:1778038430;}', 1779248090),
('laravel-cache-nE2w44tdmbHwBZyk', 'a:1:{s:11:\"valid_until\";i:1778561515;}', 1779771175),
('laravel-cache-PiEESvwwJ16d0PIO', 'a:1:{s:11:\"valid_until\";i:1778505918;}', 1779715578),
('laravel-cache-rPG3J1zu1yLLt1VS', 'a:1:{s:11:\"valid_until\";i:1778265760;}', 1779475420),
('laravel-cache-RrlbnMT2f00QGgsu', 'a:1:{s:11:\"valid_until\";i:1778040715;}', 1779250375),
('laravel-cache-s1t7y9lnoZuCFukH', 'a:1:{s:11:\"valid_until\";i:1778469368;}', 1779678248),
('laravel-cache-t0eqPAJA6DzrrFUF', 'a:1:{s:11:\"valid_until\";i:1778504416;}', 1779714076),
('laravel-cache-ufTwB8A7zHeCAY16', 'a:1:{s:11:\"valid_until\";i:1778504492;}', 1779714092),
('laravel-cache-UL7Dxlnh80zqbeFp', 'a:1:{s:11:\"valid_until\";i:1778232738;}', 1779440478),
('laravel-cache-UwJmizb82PSfPMUk', 'a:1:{s:11:\"valid_until\";i:1778507834;}', 1779717074),
('laravel-cache-WGuVbKH7CQHRjWrt', 'a:1:{s:11:\"valid_until\";i:1778504355;}', 1779714015),
('laravel-cache-X6u1Nc6tl5D6k3Vs', 'a:1:{s:11:\"valid_until\";i:1778080057;}', 1779287437),
('laravel-cache-xXVFCX5cHl6niiif', 'a:1:{s:11:\"valid_until\";i:1778264779;}', 1779474439),
('laravel-cache-yxFUqFBphNfD9W2L', 'a:1:{s:11:\"valid_until\";i:1778035394;}', 1779245054),
('laravel-cache-ZGBiWO8SHrEdNflS', 'a:1:{s:11:\"valid_until\";i:1778040108;}', 1779249768);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fields`
--

CREATE TABLE `fields` (
  `id` bigint UNSIGNED NOT NULL,
  `field_cluster_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tên sân: Sân 1, Sân 2, Sân 3...',
  `field_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Loại sân: 5v5/7v7/11v11',
  `status` enum('available','maintenance','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fields`
--

INSERT INTO `fields` (`id`, `field_cluster_id`, `name`, `field_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, '5v5', 'available', '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(3, 1, NULL, '11v11', 'available', '2026-04-03 23:21:22', '2026-05-06 08:30:57'),
(4, 2, NULL, '5v5', 'available', '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(5, 2, NULL, '7v7', 'available', '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(6, 2, NULL, '11v11', 'maintenance', '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(7, 3, 'Sân 5 (1)', '5v5', 'available', '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(8, 3, 'Sân 5 (2)', '5v5', 'available', '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(9, 3, 'Sân 7 (1)', '7v7', 'available', '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(10, 3, 'Sân 11 (1)', '11v11', 'available', '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(12, 1, NULL, '7v7', 'available', '2026-04-03 23:58:11', '2026-05-07 01:25:11'),
(14, 4, NULL, '7v7', 'available', '2026-05-11 06:52:15', '2026-05-11 06:52:15'),
(15, 4, NULL, '11v11', 'available', '2026-05-11 06:52:19', '2026-05-11 06:52:19');

-- --------------------------------------------------------

--
-- Table structure for table `field_clusters`
--

CREATE TABLE `field_clusters` (
  `id` bigint UNSIGNED NOT NULL,
  `owner_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `open_time` time NOT NULL,
  `close_time` time NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `field_clusters`
--

INSERT INTO `field_clusters` (`id`, `owner_id`, `name`, `address`, `phone`, `description`, `image`, `open_time`, `close_time`, `created_at`, `updated_at`) VALUES
(1, 1, 'Cụm sân Thể Thao Hùng - Cơ sở 1', '123 Nguyễn Văn Linh, Quận 7, TP.HCM', '0901234567', 'Cụm sân cỏ nhân tạo tiêu chuẩn, có bãi xe.', 'https://kramslcflmycdwngcpcg.supabase.co/storage/v1/object/public/image/field-clusters/1778082691529-y3ykfze.jpeg', '05:00:00', '23:00:00', '2026-04-03 23:21:22', '2026-05-06 08:51:35'),
(2, 2, 'Cụm sân Mai Sport - Cơ sở 1', '456 Võ Văn Tần, Quận 3, TP.HCM', '0907654321', 'Cụm sân trung tâm, thuận tiện di chuyển.', 'cluster_mai_cs1.jpg', '06:00:00', '22:30:00', '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(3, 3, 'Cụm Sân Test', '123 Đường Test, Quận Test', '0900000003', 'Cụm sân test cho giải đấu', NULL, '06:00:00', '23:00:00', '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(4, 6, 'ABC', 'Quan 1', '0827746039', 'I\'m the best', 'https://kramslcflmycdwngcpcg.supabase.co/storage/v1/object/public/image/field-clusters/1778507509402-1uc36xn.jpg', '06:00:00', '23:00:00', '2026-05-11 06:51:55', '2026-05-11 06:51:55');

-- --------------------------------------------------------

--
-- Table structure for table `field_hourly_prices`
--

CREATE TABLE `field_hourly_prices` (
  `id` bigint UNSIGNED NOT NULL,
  `field_cluster_id` bigint UNSIGNED NOT NULL,
  `day_type` enum('weekday','weekend') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Loại ngày: ngày thường hoặc cuối tuần',
  `field_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Loại sân: 5v5, 7v7, 11v11',
  `price_hour` decimal(12,2) NOT NULL COMMENT 'Giá theo giờ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `field_hourly_prices`
--

INSERT INTO `field_hourly_prices` (`id`, `field_cluster_id`, `day_type`, `field_type`, `price_hour`, `created_at`, `updated_at`) VALUES
(1, 1, 'weekday', '5v5', 300000.00, '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(2, 1, 'weekday', '7v7', 450000.00, '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(3, 1, 'weekday', '11v11', 800000.00, '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(4, 1, 'weekend', '5v5', 380000.00, '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(5, 1, 'weekend', '7v7', 550000.00, '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(6, 1, 'weekend', '11v11', 950000.00, '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(7, 2, 'weekday', '5v5', 300000.00, '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(8, 2, 'weekday', '7v7', 450000.00, '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(9, 2, 'weekday', '11v11', 800000.00, '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(10, 2, 'weekend', '5v5', 380000.00, '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(11, 2, 'weekend', '7v7', 550000.00, '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(12, 2, 'weekend', '11v11', 950000.00, '2026-04-03 23:21:22', '2026-04-03 23:21:22'),
(13, 4, 'weekday', '5v5', 600000.00, '2026-05-11 06:52:37', '2026-05-11 06:58:27'),
(15, 4, 'weekend', '5v5', 350000.00, '2026-05-11 06:56:15', '2026-05-11 06:56:15');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `id` bigint UNSIGNED NOT NULL,
  `tournament_id` bigint UNSIGNED NOT NULL,
  `tournament_round_id` bigint UNSIGNED NOT NULL,
  `tournament_group_id` bigint UNSIGNED DEFAULT NULL,
  `home_team_id` bigint UNSIGNED DEFAULT NULL,
  `away_team_id` bigint UNSIGNED DEFAULT NULL,
  `home_from_match_id` bigint UNSIGNED DEFAULT NULL,
  `away_from_match_id` bigint UNSIGNED DEFAULT NULL,
  `home_from_position` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'VD: Nhất bảng A',
  `away_from_position` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field_id` bigint UNSIGNED NOT NULL,
  `match_date` date NOT NULL,
  `match_time` time NOT NULL,
  `match_number` int DEFAULT NULL COMMENT 'Số thứ tự trận',
  `home_score` int DEFAULT NULL,
  `away_score` int DEFAULT NULL,
  `home_penalty_score` int DEFAULT NULL,
  `away_penalty_score` int DEFAULT NULL,
  `winner_team_id` bigint UNSIGNED DEFAULT NULL,
  `status` enum('scheduled','ongoing','completed','postponed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `referee_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`id`, `tournament_id`, `tournament_round_id`, `tournament_group_id`, `home_team_id`, `away_team_id`, `home_from_match_id`, `away_from_match_id`, `home_from_position`, `away_from_position`, `field_id`, `match_date`, `match_time`, `match_number`, `home_score`, `away_score`, `home_penalty_score`, `away_penalty_score`, `winner_team_id`, `status`, `referee_name`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 3, 1, NULL, NULL, NULL, NULL, 7, '2026-04-09', '06:00:00', 1, NULL, NULL, NULL, NULL, NULL, 'scheduled', NULL, NULL, '2026-04-03 23:26:15', '2026-04-03 23:26:15'),
(2, 1, 1, 1, 2, 4, NULL, NULL, NULL, NULL, 8, '2026-04-09', '07:15:00', 2, NULL, NULL, NULL, NULL, NULL, 'scheduled', NULL, NULL, '2026-04-03 23:26:15', '2026-04-03 23:26:15'),
(3, 1, 1, 1, 3, 2, NULL, NULL, NULL, NULL, 7, '2026-04-09', '08:30:00', 3, NULL, NULL, NULL, NULL, NULL, 'scheduled', NULL, NULL, '2026-04-03 23:26:15', '2026-04-03 23:26:15'),
(4, 1, 1, 1, 4, 1, NULL, NULL, NULL, NULL, 8, '2026-04-09', '09:45:00', 4, NULL, NULL, NULL, NULL, NULL, 'scheduled', NULL, NULL, '2026-04-03 23:26:15', '2026-04-03 23:26:15'),
(5, 1, 1, 1, 3, 4, NULL, NULL, NULL, NULL, 7, '2026-04-09', '11:00:00', 5, NULL, NULL, NULL, NULL, NULL, 'scheduled', NULL, NULL, '2026-04-03 23:26:15', '2026-04-03 23:26:15'),
(6, 1, 1, 1, 1, 2, NULL, NULL, NULL, NULL, 8, '2026-04-09', '12:00:00', 6, NULL, NULL, NULL, NULL, NULL, 'scheduled', NULL, NULL, '2026-04-03 23:26:15', '2026-04-03 23:26:15');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000001_create_cache_table', 1),
(2, '0001_01_01_000002_create_jobs_table', 1),
(3, '2026_02_07_063920_create_roles_table', 1),
(4, '2026_02_07_063923_user', 1),
(5, '2026_02_07_075009_create_owner_profiles_table', 1),
(6, '2026_02_07_075059_create_staffs_table', 1),
(7, '2026_02_07_083023_create_owner_registrations_table', 1),
(8, '2026_02_23_085730_create_field_clusters_table', 1),
(9, '2026_02_23_085744_create_fields_table', 1),
(10, '2026_02_23_093915_create_field_hourly_prices_table', 1),
(11, '2026_02_26_000000_create_books_table', 1),
(12, '2026_03_11_092716_create_tournament_session_prices_table', 1),
(13, '2026_03_11_092914_create_tournaments_table', 1),
(14, '2026_03_11_092917_create_tournament_schedules_table', 1),
(15, '2026_03_11_092919_create_teams_table', 1),
(16, '2026_03_11_092929_create_tournament_registrations_table', 1),
(17, '2026_03_11_092930_create_tournament_groups_table', 1),
(18, '2026_03_11_092931_create_tournament_group_standings_table', 1),
(19, '2026_03_11_092938_create_tournament_rounds_table', 1),
(20, '2026_03_11_092939_create_matches_table', 1),
(21, '2026_03_11_165014_add_tournament_id_to_books_table', 1),
(22, '2026_03_17_000001_add_field_type_to_tournaments_table', 1),
(23, '2026_03_24_100000_create_team_members_table', 1),
(24, '2026_03_28_090922_add_name_to_fields_table', 1),
(25, '2026_03_31_000001_add_code_id_to_users_table', 1),
(26, '2026_03_31_000002_add_unique_captain_to_teams_table', 1),
(27, '2026_03_31_000003_drop_group_order_from_tournament_groups', 1),
(28, '2026_04_04_030043_add_schedule_published_to_tournaments_table', 1),
(29, '2026_04_06_075614_add_qr_code_and_payment_fields_to_owner_profiles_and_tournaments', 2),
(30, '2026_04_10_083055_create_notifications_table', 3),
(31, '2026_04_13_073050_add_rules_file_to_tournaments_table', 4),
(32, '2026_04_25_074748_add_permissions_and_status_to_staffs_table', 5),
(35, '2026_04_27_000001_add_payment_fields_to_books_table', 6),
(36, '2026_04_27_000002_add_status_to_books_table', 7),
(37, '2026_04_27_000003_update_tournament_registration_payment_fields', 8),
(38, '2026_05_06_015838_add_email_verification_to_users_table', 9),
(39, '2026_05_08_061054_add_unique_constraint_to_books_table', 10),
(40, '2026_05_11_000001_add_team_id_to_books_table', 11),
(41, '2026_05_11_000001_add_confirmed_by_to_books_table', 12);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `data`, `is_read`, `read_at`, `created_at`, `updated_at`) VALUES
(3, 10, 'tournament_registration_approved', '✅ Đăng ký giải đấu đã được duyệt', 'Chúc mừng! Đội Đội Test 1 đã được duyệt tham gia giải Giải ABC.', '{\"team_id\": 1, \"team_name\": \"Đội Test 1\", \"start_date\": \"2026-04-13\", \"tournament_id\": 5, \"registration_id\": 7, \"tournament_name\": \"Giải ABC\"}', 1, '2026-04-10 02:24:09', '2026-04-10 02:22:24', '2026-04-10 02:24:09'),
(17, 10, 'tournament_registration_approved', '✅ Đăng ký giải đấu đã được duyệt', 'Chúc mừng! Đội Đội Test 1 đã được duyệt tham gia giải ABC.', '{\"team_id\": 1, \"team_name\": \"Đội Test 1\", \"start_date\": \"2026-04-29\", \"tournament_id\": 8, \"registration_id\": 8, \"tournament_name\": \"ABC\"}', 1, '2026-04-28 02:37:35', '2026-04-28 02:37:04', '2026-04-28 02:37:35'),
(142, 31, 'booking_created', '⚠️ Vui lòng thanh toán để hoàn tất đặt sân', 'Bạn đã đặt sân Cụm sân Thể Thao Hùng - Cơ sở 1 vào ngày 11/05/2026 (20:00:00 - 21:00:00). Vui lòng thanh toán chuyển khoản và upload bill để hoàn tất booking.', '{\"day_book\": \"2026-05-11T00:00:00.000000Z\", \"end_time\": \"21:00:00\", \"booking_id\": 129, \"field_name\": \"\", \"start_time\": \"20:00:00\", \"total_price\": 300000, \"payment_method\": \"transfer\", \"payment_status\": \"pending\", \"action_required\": \"payment\", \"field_cluster_name\": \"Cụm sân Thể Thao Hùng - Cơ sở 1\"}', 0, NULL, '2026-05-11 04:54:24', '2026-05-11 04:54:24'),
(143, 31, 'booking_bill_uploaded', '✅ Đã upload bill chuyển khoản', 'Bạn đã upload bill chuyển khoản cho đặt sân tại Cụm sân Thể Thao Hùng - Cơ sở 1 vào ngày 11/05/2026. Vui lòng chờ chủ sân xác nhận thanh toán.', '{\"status\": \"waiting_confirmation\", \"booking_id\": 129, \"payment_proof_url\": \"https://kramslcflmycdwngcpcg.supabase.co/storage/v1/object/public/image/payment-proofs/1778500910254-0tt5j1q.jpg\", \"field_cluster_name\": \"Cụm sân Thể Thao Hùng - Cơ sở 1\"}', 0, NULL, '2026-05-11 05:01:51', '2026-05-11 05:01:51'),
(145, 31, 'booking_payment_approved', '✅ Thanh toán đặt sân đã được xác nhận', 'Thanh toán cho đặt sân tại Cụm sân Thể Thao Hùng - Cơ sở 1 vào ngày 11/05/2026 (20:00:00 - 21:00:00) đã được chủ sân xác nhận.', '{\"day_book\": \"2026-05-11T00:00:00.000000Z\", \"end_time\": \"21:00:00\", \"booking_id\": 129, \"field_name\": \"\", \"start_time\": \"20:00:00\", \"total_price\": \"300000.00\", \"payment_method\": \"transfer\", \"payment_status\": \"paid\", \"field_cluster_name\": \"Cụm sân Thể Thao Hùng - Cơ sở 1\"}', 1, '2026-05-11 05:05:06', '2026-05-11 05:04:51', '2026-05-11 05:05:06'),
(152, 10, 'tournament_registration_rejected', '❌ Đăng ký giải đấu bị từ chối', 'Rất tiếc, đăng ký của đội Đội Test 1 tham gia giải ABC đã bị từ chối. Lý do: test', '{\"team_id\": 1, \"team_name\": \"Đội Test 1\", \"tournament_id\": 38, \"registration_id\": 9, \"rejected_reason\": \"test\", \"tournament_name\": \"ABC\"}', 1, '2026-05-11 06:01:47', '2026-05-11 06:01:21', '2026-05-11 06:01:47'),
(153, 11, 'tournament_registration_approved', '✅ Đăng ký giải đấu đã được duyệt', 'Chúc mừng! Đội Đội Test 2 đã được duyệt tham gia giải ABC.', '{\"team_id\": 2, \"team_name\": \"Đội Test 2\", \"start_date\": \"2026-05-14\", \"tournament_id\": 38, \"registration_id\": 10, \"tournament_name\": \"ABC\"}', 0, NULL, '2026-05-11 06:11:12', '2026-05-11 06:11:12'),
(154, 12, 'tournament_registration_approved', '✅ Đăng ký giải đấu đã được duyệt', 'Chúc mừng! Đội Đội Test 3 đã được duyệt tham gia giải ABC.', '{\"team_id\": 3, \"team_name\": \"Đội Test 3\", \"start_date\": \"2026-05-14\", \"tournament_id\": 38, \"registration_id\": 11, \"tournament_name\": \"ABC\"}', 0, NULL, '2026-05-11 06:11:34', '2026-05-11 06:11:34'),
(155, 13, 'tournament_registration_approved', '✅ Đăng ký giải đấu đã được duyệt', 'Chúc mừng! Đội Đội Test 4 đã được duyệt tham gia giải ABC.', '{\"team_id\": 4, \"team_name\": \"Đội Test 4\", \"start_date\": \"2026-05-14\", \"tournament_id\": 38, \"registration_id\": 12, \"tournament_name\": \"ABC\"}', 0, NULL, '2026-05-11 06:11:40', '2026-05-11 06:11:40'),
(156, 31, 'tournament_registration_approved', '✅ Đăng ký giải đấu đã được duyệt', 'Chúc mừng! Đội Đồng Tâm Long An đã được duyệt tham gia giải ABC.', '{\"team_id\": 9, \"team_name\": \"Đồng Tâm Long An\", \"start_date\": \"2026-05-14\", \"tournament_id\": 38, \"registration_id\": 13, \"tournament_name\": \"ABC\"}', 0, NULL, '2026-05-11 06:11:52', '2026-05-11 06:11:52'),
(157, 33, 'booking_created', '⚠️ Vui lòng thanh toán để hoàn tất đặt sân', 'Bạn đã đặt sân ABC vào ngày 11/05/2026 (22:00:00 - 23:00:00). Vui lòng thanh toán tiền mặt tại sân để hoàn tất booking.', '{\"day_book\": \"2026-05-11T00:00:00.000000Z\", \"end_time\": \"23:00:00\", \"booking_id\": 135, \"field_name\": \"\", \"start_time\": \"22:00:00\", \"total_price\": 300000, \"payment_method\": \"cash\", \"payment_status\": \"pending\", \"action_required\": \"payment\", \"field_cluster_name\": \"ABC\"}', 0, NULL, '2026-05-11 06:56:39', '2026-05-11 06:56:39'),
(162, 2, 'new_tournament_created', '🏆 Có giải đấu mới tại cụm sân của bạn', 'Giải đấu \"ABC\" vừa được tạo tại Cụm sân Thể Thao Hùng - Cơ sở 1. Phương thức thanh toán: tiền mặt tại sân. Tổng tiền: 10,000,000 VNĐ.', '{\"cluster_name\": \"Cụm sân Thể Thao Hùng - Cơ sở 1\", \"total_amount\": 10000000, \"tournament_id\": 40, \"organizer_name\": \"Thành\", \"payment_method\": \"cash\", \"organizer_phone\": \"0827746039\", \"tournament_name\": \"ABC\", \"field_cluster_id\": 1}', 0, NULL, '2026-05-11 22:04:14', '2026-05-11 22:04:14'),
(164, 2, 'new_tournament_created', '🏆 Có giải đấu mới tại cụm sân của bạn', 'Giải đấu \"ABC\" vừa được tạo tại Cụm sân Thể Thao Hùng - Cơ sở 1. Phương thức thanh toán: tiền mặt tại sân. Tổng tiền: 5,000,000 VNĐ.', '{\"cluster_name\": \"Cụm sân Thể Thao Hùng - Cơ sở 1\", \"total_amount\": 5000000, \"tournament_id\": 41, \"organizer_name\": \"Thành\", \"payment_method\": \"cash\", \"organizer_phone\": \"0827746039\", \"tournament_name\": \"ABC\", \"field_cluster_id\": 1}', 0, NULL, '2026-05-11 22:08:01', '2026-05-11 22:08:01'),
(166, 2, 'new_tournament_created', '🏆 Có giải đấu mới tại cụm sân của bạn', 'Giải đấu \"ABC\" vừa được tạo tại Cụm sân Thể Thao Hùng - Cơ sở 1. Phương thức thanh toán: tiền mặt tại sân. Tổng tiền: 10,000,000 VNĐ.', '{\"cluster_name\": \"Cụm sân Thể Thao Hùng - Cơ sở 1\", \"total_amount\": 10000000, \"tournament_id\": 42, \"organizer_name\": \"Thành\", \"payment_method\": \"cash\", \"organizer_phone\": \"0827746039\", \"tournament_name\": \"ABC\", \"field_cluster_id\": 1}', 0, NULL, '2026-05-11 22:35:04', '2026-05-11 22:35:04'),
(167, 23, 'tournament_payment_rejected', '❌ Giải đấu bị hủy do từ chối thanh toán', 'Giải đấu \"ABC\" đã bị hủy vì thanh toán bị từ chối. Lý do: test. Bạn có thể tạo giải đấu mới.', '{\"rejected_reason\": \"test\", \"tournament_name\": \"ABC\", \"action_available\": \"Bạn có thể tạo giải đấu mới ngay bây giờ\"}', 1, '2026-05-11 22:37:55', '2026-05-11 22:37:42', '2026-05-11 22:37:55'),
(168, 2, 'new_tournament_created', '🏆 Có giải đấu mới tại cụm sân của bạn', 'Giải đấu \"ABC\" vừa được tạo tại Cụm sân Thể Thao Hùng - Cơ sở 1. Phương thức thanh toán: tiền mặt tại sân. Tổng tiền: 5,000,000 VNĐ.', '{\"cluster_name\": \"Cụm sân Thể Thao Hùng - Cơ sở 1\", \"total_amount\": 5000000, \"tournament_id\": 43, \"organizer_name\": \"Thành\", \"payment_method\": \"cash\", \"organizer_phone\": \"0827746039\", \"tournament_name\": \"ABC\", \"field_cluster_id\": 1}', 0, NULL, '2026-05-11 23:40:36', '2026-05-11 23:40:36'),
(169, 23, 'tournament_payment_rejected', '❌ Giải đấu bị hủy do từ chối thanh toán', 'Giải đấu \"ABC\" đã bị hủy vì thanh toán bị từ chối. Lý do: test. Bạn có thể tạo giải đấu mới.', '{\"rejected_reason\": \"test\", \"tournament_name\": \"ABC\", \"action_available\": \"Bạn có thể tạo giải đấu mới ngay bây giờ\"}', 0, NULL, '2026-05-11 23:43:01', '2026-05-11 23:43:01');

-- --------------------------------------------------------

--
-- Table structure for table `owner_profiles`
--

CREATE TABLE `owner_profiles` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `business_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tên doanh nghiệp/sân',
  `address` text COLLATE utf8mb4_unicode_ci COMMENT 'Địa chỉ kinh doanh',
  `image_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Giấy phép kinh doanh',
  `code_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Căn cước công dân',
  `qr_code_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đường dẫn ảnh QR code ngân hàng của chủ sân',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `owner_profiles`
--

INSERT INTO `owner_profiles` (`id`, `user_id`, `business_name`, `address`, `image_code`, `code_id`, `qr_code_image`, `created_at`, `updated_at`) VALUES
(1, 2, 'Sân bóng Thể Thao Hùng', '123 Đường Nguyễn Văn Linh, Quận 7, TP.HCM', 'gpkd_001.jpg', '079201001234', 'https://kramslcflmycdwngcpcg.supabase.co/storage/v1/object/public/image/owner-qr-codes/1777114321021-x6ymi1z.jpg', '2026-04-03 23:21:21', '2026-04-25 03:52:02'),
(2, 3, 'Sân bóng Mai Sport', '456 Đường Võ Văn Tần, Quận 3, TP.HCM', 'gpkd_002.jpg', '079199002345', NULL, '2026-04-03 23:21:21', '2026-04-03 23:21:21'),
(3, 9, 'Cụm Sân Test', '123 Đường Test, Quận Test', NULL, '001234567890', NULL, '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(6, 32, 'Sân bóng Long an', 's', 'https://kramslcflmycdwngcpcg.supabase.co/storage/v1/object/public/image/field-owners/1778506127625-espkc8u.jpg', '999000000001', NULL, '2026-05-11 06:35:00', '2026-05-11 06:35:00'),
(7, 34, 'Sân Gò vấp', 'quan 1', 'https://kramslcflmycdwngcpcg.supabase.co/storage/v1/object/public/image/field-owners/1778561463930-ui97som.jpg', '999000000004', NULL, '2026-05-11 21:52:19', '2026-05-11 21:52:19');

-- --------------------------------------------------------

--
-- Table structure for table `owner_registrations`
--

CREATE TABLE `owner_registrations` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Giấy phép kinh doanh',
  `code_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Căn cước công dân',
  `business_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tên doanh nghiệp/sân',
  `address` text COLLATE utf8mb4_unicode_ci COMMENT 'Địa chỉ kinh doanh',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejected_reason` text COLLATE utf8mb4_unicode_ci,
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `owner_registrations`
--

INSERT INTO `owner_registrations` (`id`, `name`, `email`, `phone`, `password`, `image_code`, `code_id`, `business_name`, `address`, `status`, `rejected_reason`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 'Lương Tấn Thành', 'thanhluong30122k3@gmail.com', '0827746039', '$2y$12$ZLLLFU.BWVfDvNYhTiNuhuATehh3/bnk12G73R2eBfdDwtr1fRiAi', 'https://kramslcflmycdwngcpcg.supabase.co/storage/v1/object/public/image/field-owners/1775284132409-8wudeln.png', '999000000008', 'Sân bóng Long an', 'Tây Ninh, phường Long An', 'rejected', 'Giấy phép kinh doanh chưa đúng', 1, '2026-04-03 23:30:47', '2026-04-03 23:28:56', '2026-04-03 23:30:47'),
(4, 'Thành Lương', 'natteam1998@gmail.com', '0827746039', '$2y$12$Ez0MZbUdao3k3HzrEW7UceRN2o5qO47fnsxI.KxFjuCaJE9tvqX7C', 'https://kramslcflmycdwngcpcg.supabase.co/storage/v1/object/public/image/field-owners/1778506127625-espkc8u.jpg', '999000000001', 'Sân bóng Long an', 's', 'approved', NULL, 1, '2026-05-11 06:35:00', '2026-05-11 06:32:34', '2026-05-11 06:35:00'),
(5, 'Linh', 'luongtidai@gmail.com', '0827746039', '123456', 'https://kramslcflmycdwngcpcg.supabase.co/storage/v1/object/public/image/field-owners/1778561463930-ui97som.jpg', '999000000004', 'Sân Gò vấp', 'quan 1', 'approved', NULL, 1, '2026-05-11 21:52:19', '2026-05-11 21:51:07', '2026-05-11 21:52:19');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name_role` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name_role`, `description`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Quản lý toàn bộ hệ thống', '2026-04-04 06:21:20', '2026-04-03 23:21:20'),
(2, 'owner', 'Chủ sở hữu của một khu vực sân bóng', '2026-04-04 06:21:20', '2026-04-03 23:21:20'),
(3, 'staff', 'Nhân viên thuộc khu vực sân bóng', '2026-04-04 06:21:20', '2026-04-03 23:21:20'),
(4, 'member', 'Người dùng đã đăng nhập vào hệ thống', '2026-04-04 06:21:20', '2026-04-03 23:21:20');

-- --------------------------------------------------------

--
-- Table structure for table `staffs`
--

CREATE TABLE `staffs` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `owner_id` bigint UNSIGNED NOT NULL,
  `code_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Căn cước công dân',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ảnh đại diện',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staffs`
--

INSERT INTO `staffs` (`id`, `user_id`, `owner_id`, `code_id`, `avatar`, `created_at`, `updated_at`) VALUES
(2, 5, 1, '079199006789', 'avatar_staff2.jpg', '2026-04-03 23:21:21', '2026-04-03 23:21:21'),
(3, 6, 2, '079201007890', 'avatar_staff3.jpg', '2026-04-03 23:21:21', '2026-04-03 23:21:21'),
(6, 33, 6, '124636893575', NULL, '2026-05-11 06:47:31', '2026-05-11 06:47:31');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` bigint UNSIGNED NOT NULL,
  `captain_user_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `captain_user_id`, `name`, `short_name`, `logo`, `contact_phone`, `contact_email`, `created_at`, `updated_at`) VALUES
(1, 10, 'Đội Test 1', 'T1', NULL, '0900000101', 'captain1@test.com', '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(2, 11, 'Đội Test 2', 'T2', NULL, '0900000102', 'captain2@test.com', '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(3, 12, 'Đội Test 3', 'T3', NULL, '0900000103', 'captain3@test.com', '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(4, 13, 'Đội Test 4', 'T4', NULL, '0900000104', 'captain4@test.com', '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(9, 31, 'Đồng Tâm Long An', 'DFC', 'https://kramslcflmycdwngcpcg.supabase.co/storage/v1/object/public/image/teams/1778504701543-odiwvd8.jpg', '0827746039', 'luongtidai@gmail.com', '2026-05-11 06:05:04', '2026-05-11 06:05:04');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` bigint UNSIGNED NOT NULL,
  `team_id` bigint UNSIGNED NOT NULL,
  `player_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên cầu thủ',
  `code_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'CCCD/CMND',
  `jersey_number` int DEFAULT NULL COMMENT 'Số áo: 1-99',
  `position` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Vị trí: Thủ môn, Hậu vệ, Tiền vệ, Tiền đạo',
  `total_goals` int NOT NULL DEFAULT '0' COMMENT 'Tổng bàn thắng',
  `yellow_cards` int NOT NULL DEFAULT '0' COMMENT 'Thẻ vàng',
  `red_cards` int NOT NULL DEFAULT '0' COMMENT 'Thẻ đỏ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `team_id`, `player_name`, `code_id`, `jersey_number`, `position`, `total_goals`, `yellow_cards`, `red_cards`, `created_at`, `updated_at`) VALUES
(1, 1, 'Cầu Thủ 1 - Đội 1', '0012345611', 1, 'Thủ môn', 0, 0, 0, '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(2, 1, 'Cầu Thủ 2 - Đội 1', '0012345612', 2, 'Hậu vệ', 0, 0, 0, '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(3, 1, 'Cầu Thủ 3 - Đội 1', '0012345613', 3, 'Hậu vệ', 0, 0, 0, '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(4, 1, 'Cầu Thủ 4 - Đội 1', '0012345614', 4, 'Tiền vệ', 0, 0, 0, '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(5, 1, 'Cầu Thủ 5 - Đội 1', '0012345615', 5, 'Tiền đạo', 0, 0, 0, '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(6, 2, 'Cầu Thủ 1 - Đội 2', '0012345621', 1, 'Thủ môn', 0, 0, 0, '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(7, 2, 'Cầu Thủ 2 - Đội 2', '0012345622', 2, 'Hậu vệ', 0, 0, 0, '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(8, 2, 'Cầu Thủ 3 - Đội 2', '0012345623', 3, 'Hậu vệ', 0, 0, 0, '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(9, 2, 'Cầu Thủ 4 - Đội 2', '0012345624', 4, 'Tiền vệ', 0, 0, 0, '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(10, 2, 'Cầu Thủ 5 - Đội 2', '0012345625', 5, 'Tiền đạo', 0, 0, 0, '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(11, 3, 'Cầu Thủ 1 - Đội 3', '0012345631', 1, 'Thủ môn', 0, 0, 0, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(12, 3, 'Cầu Thủ 2 - Đội 3', '0012345632', 2, 'Hậu vệ', 0, 0, 0, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(13, 3, 'Cầu Thủ 3 - Đội 3', '0012345633', 3, 'Hậu vệ', 0, 0, 0, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(14, 3, 'Cầu Thủ 4 - Đội 3', '0012345634', 4, 'Tiền vệ', 0, 0, 0, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(15, 3, 'Cầu Thủ 5 - Đội 3', '0012345635', 5, 'Tiền đạo', 0, 0, 0, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(16, 4, 'Cầu Thủ 1 - Đội 4', '0012345641', 1, 'Thủ môn', 0, 0, 0, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(17, 4, 'Cầu Thủ 2 - Đội 4', '0012345642', 2, 'Hậu vệ', 0, 0, 0, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(18, 4, 'Cầu Thủ 3 - Đội 4', '0012345643', 3, 'Hậu vệ', 0, 0, 0, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(19, 4, 'Cầu Thủ 4 - Đội 4', '0012345644', 4, 'Tiền vệ', 0, 0, 0, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(20, 4, 'Cầu Thủ 5 - Đội 4', '0012345645', 5, 'Tiền đạo', 0, 0, 0, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(31, 9, 'b', '090229147392', 4, 'Hậu vệ', 0, 0, 0, '2026-05-11 06:05:31', '2026-05-11 06:05:31'),
(32, 9, 'C', '090229147392', 7, 'Tiền đạo', 0, 0, 0, '2026-05-11 06:05:51', '2026-05-11 06:05:51'),
(33, 9, 'C', '090229147392', 10, 'Thủ môn', 0, 0, 0, '2026-05-11 06:06:49', '2026-05-11 06:06:49'),
(34, 9, 'E', '090229147392', 55, 'Hậu vệ', 0, 0, 0, '2026-05-11 06:07:07', '2026-05-11 06:07:07'),
(35, 9, 'Thành Lương', '090229147392', 34, 'Tiền đạo', 0, 0, 0, '2026-05-11 06:09:58', '2026-05-11 06:09:58');

-- --------------------------------------------------------

--
-- Table structure for table `tournaments`
--

CREATE TABLE `tournaments` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `tournament_rules_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File thể lệ giải đấu (.pdf, .doc, .docx)',
  `format` enum('round_robin','knockout','group_knockout') COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '5v5' COMMENT 'Loại sân: 5v5, 7v7, 11v11',
  `max_teams` int NOT NULL,
  `min_teams` int NOT NULL,
  `registration_start` datetime NOT NULL,
  `registration_end` datetime NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('draft','open','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `schedule_published` tinyint(1) NOT NULL DEFAULT '0',
  `field_cluster_id` bigint UNSIGNED NOT NULL,
  `organizer_id` bigint UNSIGNED NOT NULL,
  `payment_method` enum('cash','transfer') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Phương thức thanh toán: tiền mặt hoặc chuyển khoản',
  `payment_status` enum('pending','paid','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái thanh toán',
  `payment_date` datetime DEFAULT NULL COMMENT 'Ngày thanh toán',
  `payment_proof` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ảnh bill chuyển khoản (nếu có)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tournaments`
--

INSERT INTO `tournaments` (`id`, `name`, `description`, `tournament_rules_file`, `format`, `field_type`, `max_teams`, `min_teams`, `registration_start`, `registration_end`, `start_date`, `end_date`, `status`, `schedule_published`, `field_cluster_id`, `organizer_id`, `payment_method`, `payment_status`, `payment_date`, `payment_proof`, `created_at`, `updated_at`) VALUES
(1, 'Giải Test 5v5 Mùa Xuân 2026', 'Giải đấu test cho hệ thống', NULL, 'round_robin', '5v5', 16, 4, '2026-03-25 06:21:50', '2026-03-30 06:21:50', '2026-04-09', '2026-04-14', 'in_progress', 1, 3, 8, NULL, 'pending', NULL, NULL, '2026-04-03 23:21:50', '2026-04-10 01:42:31');

-- --------------------------------------------------------

--
-- Table structure for table `tournament_groups`
--

CREATE TABLE `tournament_groups` (
  `id` bigint UNSIGNED NOT NULL,
  `tournament_id` bigint UNSIGNED NOT NULL,
  `group_name` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A, B, C, D...',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tournament_groups`
--

INSERT INTO `tournament_groups` (`id`, `tournament_id`, `group_name`, `created_at`, `updated_at`) VALUES
(1, 1, 'Vòng bảng', '2026-04-03 23:26:15', '2026-04-03 23:26:15');

-- --------------------------------------------------------

--
-- Table structure for table `tournament_group_standings`
--

CREATE TABLE `tournament_group_standings` (
  `id` bigint UNSIGNED NOT NULL,
  `tournament_group_id` bigint UNSIGNED NOT NULL,
  `team_id` bigint UNSIGNED NOT NULL,
  `matches_played` int NOT NULL DEFAULT '0',
  `wins` int NOT NULL DEFAULT '0',
  `draws` int NOT NULL DEFAULT '0',
  `losses` int NOT NULL DEFAULT '0',
  `goals_for` int NOT NULL DEFAULT '0' COMMENT 'Bàn thắng',
  `goals_against` int NOT NULL DEFAULT '0' COMMENT 'Bàn thua',
  `goal_difference` int NOT NULL DEFAULT '0' COMMENT 'Hiệu số',
  `points` int NOT NULL DEFAULT '0' COMMENT 'Điểm số',
  `ranking` int DEFAULT NULL COMMENT 'Thứ hạng trong bảng',
  `is_qualified` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Có đi tiếp không',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tournament_group_standings`
--

INSERT INTO `tournament_group_standings` (`id`, `tournament_group_id`, `team_id`, `matches_played`, `wins`, `draws`, `losses`, `goals_for`, `goals_against`, `goal_difference`, `points`, `ranking`, `is_qualified`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 0, '2026-04-03 23:26:15', '2026-04-03 23:26:15'),
(2, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 0, '2026-04-03 23:26:15', '2026-04-03 23:26:15'),
(3, 1, 4, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 0, '2026-04-03 23:26:15', '2026-04-03 23:26:15'),
(4, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 0, '2026-04-03 23:26:15', '2026-04-03 23:26:15');

-- --------------------------------------------------------

--
-- Table structure for table `tournament_registrations`
--

CREATE TABLE `tournament_registrations` (
  `id` bigint UNSIGNED NOT NULL,
  `tournament_id` bigint UNSIGNED NOT NULL,
  `team_id` bigint UNSIGNED NOT NULL,
  `registration_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registration_status` enum('pending','approved','rejected','withdrawn') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_proof` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ảnh chuyển khoản',
  `payment_confirmed_at` datetime DEFAULT NULL,
  `payment_note` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú của đội',
  `admin_notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú của BTC',
  `rejected_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tournament_registrations`
--

INSERT INTO `tournament_registrations` (`id`, `tournament_id`, `team_id`, `registration_date`, `registration_status`, `payment_status`, `payment_method`, `payment_date`, `payment_proof`, `payment_confirmed_at`, `payment_note`, `notes`, `admin_notes`, `rejected_reason`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-03-28 06:21:50', 'approved', 'paid', NULL, '2026-03-29 06:21:50', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(2, 1, 2, '2026-03-28 06:21:50', 'approved', 'paid', NULL, '2026-03-29 06:21:50', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(3, 1, 3, '2026-03-28 06:21:50', 'approved', 'paid', NULL, '2026-03-29 06:21:50', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(4, 1, 4, '2026-03-28 06:21:50', 'approved', 'paid', NULL, '2026-03-29 06:21:50', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-03 23:21:50', '2026-04-03 23:21:50');

-- --------------------------------------------------------

--
-- Table structure for table `tournament_rounds`
--

CREATE TABLE `tournament_rounds` (
  `id` bigint UNSIGNED NOT NULL,
  `tournament_id` bigint UNSIGNED NOT NULL,
  `round_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `round_type` enum('group_stage','round_of_32','round_of_16','quarter_final','semi_final','third_place','final') COLLATE utf8mb4_unicode_ci NOT NULL,
  `round_order` int NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_knockout` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tournament_rounds`
--

INSERT INTO `tournament_rounds` (`id`, `tournament_id`, `round_name`, `round_type`, `round_order`, `start_date`, `end_date`, `is_knockout`, `created_at`, `updated_at`) VALUES
(1, 1, 'Vòng bảng', 'group_stage', 1, '2026-04-09', '2026-04-11', 0, '2026-04-03 23:26:15', '2026-04-03 23:26:15');

-- --------------------------------------------------------

--
-- Table structure for table `tournament_schedules`
--

CREATE TABLE `tournament_schedules` (
  `id` bigint UNSIGNED NOT NULL,
  `tournament_id` bigint UNSIGNED NOT NULL,
  `match_date` date NOT NULL,
  `session_type` enum('morning','afternoon','evening','full_day') COLLATE utf8mb4_unicode_ci NOT NULL,
  `day_type` enum('weekday','weekend') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tự động tính từ match_date',
  `number_of_fields` int NOT NULL COMMENT 'Số sân thuê trong buổi này',
  `price_per_field` decimal(15,2) NOT NULL COMMENT 'Giá 1 sân/buổi (lưu lại)',
  `session_cost` decimal(15,2) NOT NULL COMMENT 'Chi phí = số sân × giá',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_session_prices`
--

CREATE TABLE `tournament_session_prices` (
  `id` bigint UNSIGNED NOT NULL,
  `field_cluster_id` bigint UNSIGNED NOT NULL,
  `field_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '5v5, 7v7, 11v11',
  `day_type` enum('weekday','weekend') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ngày thường/Cuối tuần',
  `session_type` enum('morning','afternoon','evening','full_day') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Buổi: sáng 6-12h, chiều 12-18h, tối 18-23h, cả ngày 6-23h',
  `price_per_session` decimal(15,2) NOT NULL COMMENT 'Giá 1 sân/1 buổi',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tournament_session_prices`
--

INSERT INTO `tournament_session_prices` (`id`, `field_cluster_id`, `field_type`, `day_type`, `session_type`, `price_per_session`, `created_at`, `updated_at`) VALUES
(1, 3, '5v5', 'weekday', 'morning', 500000.00, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(2, 3, '5v5', 'weekday', 'afternoon', 600000.00, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(3, 3, '5v5', 'weekday', 'evening', 800000.00, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(4, 3, '5v5', 'weekday', 'full_day', 1800000.00, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(5, 3, '5v5', 'weekend', 'morning', 700000.00, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(6, 3, '5v5', 'weekend', 'afternoon', 800000.00, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(7, 3, '5v5', 'weekend', 'evening', 1000000.00, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(8, 3, '5v5', 'weekend', 'full_day', 2300000.00, '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(11, 1, '5v5', 'weekend', 'afternoon', 2500000.00, '2026-04-05 23:41:39', '2026-04-05 23:41:39'),
(12, 1, '5v5', 'weekday', 'afternoon', 2500000.00, '2026-04-05 23:41:58', '2026-04-05 23:41:58'),
(13, 1, '5v5', 'weekday', 'evening', 2500000.00, '2026-04-05 23:42:40', '2026-04-05 23:42:40'),
(14, 1, '5v5', 'weekday', 'full_day', 6000000.00, '2026-04-05 23:42:58', '2026-04-05 23:42:58'),
(16, 1, '5v5', 'weekend', 'evening', 1000000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(17, 1, '5v5', 'weekend', 'full_day', 2300000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(18, 1, '7v7', 'weekday', 'morning', 1000000.00, '2026-04-06 00:59:22', '2026-05-11 02:16:17'),
(19, 1, '7v7', 'weekday', 'afternoon', 800000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(20, 1, '7v7', 'weekday', 'evening', 1000000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(21, 1, '7v7', 'weekday', 'full_day', 2300000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(22, 1, '7v7', 'weekend', 'morning', 900000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(23, 1, '7v7', 'weekend', 'afternoon', 1000000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(24, 1, '7v7', 'weekend', 'evening', 1200000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(25, 1, '7v7', 'weekend', 'full_day', 2800000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(26, 1, '11v11', 'weekday', 'morning', 1000000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(27, 1, '11v11', 'weekday', 'afternoon', 1200000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(28, 1, '11v11', 'weekday', 'evening', 1500000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(29, 1, '11v11', 'weekday', 'full_day', 3500000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(30, 1, '11v11', 'weekend', 'morning', 1300000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(31, 1, '11v11', 'weekend', 'afternoon', 1500000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(32, 1, '11v11', 'weekend', 'evening', 1800000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(33, 1, '11v11', 'weekend', 'full_day', 4300000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(34, 2, '5v5', 'weekday', 'morning', 500000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(35, 2, '5v5', 'weekday', 'afternoon', 600000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(36, 2, '5v5', 'weekday', 'evening', 800000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(37, 2, '5v5', 'weekday', 'full_day', 1800000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(38, 2, '5v5', 'weekend', 'morning', 700000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(39, 2, '5v5', 'weekend', 'afternoon', 800000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(40, 2, '5v5', 'weekend', 'evening', 1000000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(41, 2, '5v5', 'weekend', 'full_day', 2300000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(42, 2, '7v7', 'weekday', 'morning', 700000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(43, 2, '7v7', 'weekday', 'afternoon', 800000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(44, 2, '7v7', 'weekday', 'evening', 1000000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(45, 2, '7v7', 'weekday', 'full_day', 2300000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(46, 2, '7v7', 'weekend', 'morning', 900000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(47, 2, '7v7', 'weekend', 'afternoon', 1000000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(48, 2, '7v7', 'weekend', 'evening', 1200000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(49, 2, '7v7', 'weekend', 'full_day', 2800000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(50, 2, '11v11', 'weekday', 'morning', 1000000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(51, 2, '11v11', 'weekday', 'afternoon', 1200000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(52, 2, '11v11', 'weekday', 'evening', 1500000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(53, 2, '11v11', 'weekday', 'full_day', 3500000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(54, 2, '11v11', 'weekend', 'morning', 1300000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(55, 2, '11v11', 'weekend', 'afternoon', 1500000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(56, 2, '11v11', 'weekend', 'evening', 1800000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(57, 2, '11v11', 'weekend', 'full_day', 4300000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(58, 3, '7v7', 'weekday', 'morning', 700000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(59, 3, '7v7', 'weekday', 'afternoon', 800000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(60, 3, '7v7', 'weekday', 'evening', 1000000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(61, 3, '7v7', 'weekday', 'full_day', 2300000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(62, 3, '7v7', 'weekend', 'morning', 900000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(63, 3, '7v7', 'weekend', 'afternoon', 1000000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(64, 3, '7v7', 'weekend', 'evening', 1200000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(65, 3, '7v7', 'weekend', 'full_day', 2800000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(66, 3, '11v11', 'weekday', 'morning', 1000000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(67, 3, '11v11', 'weekday', 'afternoon', 1200000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(68, 3, '11v11', 'weekday', 'evening', 1500000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(69, 3, '11v11', 'weekday', 'full_day', 3500000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(70, 3, '11v11', 'weekend', 'morning', 1300000.00, '2026-04-06 00:59:22', '2026-04-06 00:59:22'),
(71, 3, '11v11', 'weekend', 'afternoon', 1500000.00, '2026-04-06 00:59:23', '2026-04-06 00:59:23'),
(72, 3, '11v11', 'weekend', 'evening', 1800000.00, '2026-04-06 00:59:23', '2026-04-06 00:59:23'),
(73, 3, '11v11', 'weekend', 'full_day', 4300000.00, '2026-04-06 00:59:23', '2026-04-06 00:59:23'),
(74, 1, '5v5', 'weekend', 'morning', 2500000.00, '2026-05-11 02:04:17', '2026-05-11 02:04:17'),
(75, 1, '5v5', 'weekday', 'morning', 2500000.00, '2026-05-11 02:16:30', '2026-05-11 02:16:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `email_verification_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Căn cước công dân',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `email_verified_at`, `email_verification_token`, `phone`, `code_id`, `status`, `password`, `created_at`, `updated_at`) VALUES
(1, 1, 'Admin System', 'admin@sanbongda.com', '2026-05-06 03:37:48', NULL, '0900000000', NULL, 1, '$2y$12$XeU2tBXpmozNflEyAyfgTeRHymXXb.ABL1tyzuxLYc5a2s9dHxxqS', '2026-04-03 23:21:20', '2026-04-03 23:21:20'),
(2, 2, 'Nguyễn Văn Hùng', 'owner1@sanbongda.com', '2026-05-06 03:37:48', NULL, '0901234567', NULL, 1, '$2y$12$cjv1rePTNwuUM7CStQ1FjO4OQqEfo0V25dv.zJHPKesa/GFoHKcVK', '2026-04-03 23:21:21', '2026-04-03 23:21:21'),
(3, 2, 'Trần Thị Mai', 'owner2@sanbongda.com', '2026-05-06 03:37:48', NULL, '0907654321', NULL, 1, '$2y$12$7k94IbraM0BKdA1ECvYqeuPIpKn.p4ZoeeVewIhT1M9jKVd4dtv3O', '2026-04-03 23:21:21', '2026-04-03 23:21:21'),
(4, 3, 'Lê Văn Tú', 'staff1@sanbongda.com', '2026-05-06 03:37:48', NULL, '0912345678', NULL, 1, '$2y$12$3DB3qT9V5RnZoigSz2F91OmMhw2qZqFHrLxgelpZ97bKDH9jIoUda', '2026-04-03 23:21:21', '2026-04-03 23:21:21'),
(5, 3, 'Phạm Thị Lan', 'staff2@sanbongda.com', '2026-05-06 03:37:48', NULL, '0923456789', NULL, 1, '$2y$12$byB6pJ2rSs3rT1KAfX2RQuDKec1Kce2HB2zFTJwp87zZRY0lf2R1m', '2026-04-03 23:21:21', '2026-04-03 23:21:21'),
(6, 3, 'Hoàng Minh Tuấn', 'staff3@sanbongda.com', '2026-05-06 03:37:48', NULL, '0934567890', NULL, 1, '$2y$12$Ga7mdo4I.ENdqqC7MXB33.tqbKdBLrPJ/01UivUcjXkvvxYb8MfMe', '2026-04-03 23:21:21', '2026-04-03 23:21:21'),
(8, 1, 'Admin Test', 'admin@test.com', '2026-05-06 03:37:48', NULL, '0900000001', NULL, 1, '$2y$12$RV6HiEFgaQhhjcIoafbqMejKprVjBCjkPLmsHIBDOzEfZjQ2ITOdi', '2026-04-03 23:21:49', '2026-04-10 00:27:01'),
(9, 2, 'Chủ Sân Test', 'owner@test.com', '2026-05-06 03:37:48', NULL, '0900000002', NULL, 1, '$2y$12$v.p/GEZp3rxvtkaWJ73pduragkvrHbzGyiXJQmbd4AP50QZA7/dd2', '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(10, 4, 'Đội Trưởng 1', 'captain1@test.com', '2026-05-06 03:37:48', NULL, '0900000101', NULL, 1, '$2y$12$k2e/UC4v0E9ep/QsxrRWqO/0xXqAzsMeeKz3MlTxsnidHdat4W6uu', '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(11, 4, 'Đội Trưởng 2', 'captain2@test.com', '2026-05-06 03:37:48', NULL, '0900000102', NULL, 1, '$2y$12$o3eSPcT7GoXcmekPpx76/ujvMmfke.tRYp3HnhWQoz6pP4OTqkT9O', '2026-04-03 23:21:49', '2026-04-03 23:21:49'),
(12, 4, 'Đội Trưởng 3', 'captain3@test.com', '2026-05-06 03:37:48', NULL, '0900000103', NULL, 1, '$2y$12$asQIoLmBRJlRQqr16i0E2uApbAM5eEtV5k3HvCxlWDMsh2LbcRjDq', '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(13, 4, 'Đội Trưởng 4', 'captain4@test.com', '2026-05-06 03:37:48', NULL, '0900000104', NULL, 1, '$2y$12$DJp9REj0aPXvYY8tLY01o.dz5xjn/wkDT6r7pz/Vg1WXVGiEfgztu', '2026-04-03 23:21:50', '2026-04-03 23:21:50'),
(23, 4, 'Thành', 'luongtanthanh30122003@gmail.com', '2026-05-05 19:42:26', NULL, '0827746039', NULL, 1, '$2y$12$wcs.NW/fNzvA/uNqhV7vZePtpXNeJAlGu8SQcXu5PmyAt6XYEo/4O', '2026-05-05 19:42:07', '2026-05-07 01:33:49'),
(31, 4, 'Thành', 'luongtanthanh2k3@gmail.com', '2026-05-11 04:52:50', NULL, NULL, NULL, 1, '$2y$12$P6o0avnTHNsWxR7g0a1QxuOgNNvFOq5ISSSrPHQUM2TIcPdm3DHEy', '2026-05-11 04:47:20', '2026-05-11 04:52:50'),
(32, 2, 'Thành Lương', 'natteam1998@gmail.com', '2026-05-11 06:35:00', NULL, '0827746039', NULL, 1, '$2y$10$n0Ac4wiN9mMtMXMLkcfLw.fySHLVVjpZ/G1HDEKC5hhrajOc96kYW', '2026-05-11 06:35:00', '2026-05-11 06:35:00'),
(33, 3, 'Binh duongw', 'nguyenthaibinhduong182003@gmail.com', NULL, NULL, '0827746039', NULL, 1, '$2y$12$nATcU0iIc3RkKVsK75eeBuhBKel5NqB1C0qkLCsgaUqYIOWBv3R3i', '2026-05-11 06:47:31', '2026-05-11 06:47:31'),
(34, 2, 'Linh', 'luongtidai@gmail.com', '2026-05-11 21:52:19', NULL, '0827746039', NULL, 1, '$2y$12$PT.97oH4RTNc3hnePvRYa.qiU.VbdcKEgZMEQ5Z1LMeasBSL3fWTy', '2026-05-11 21:52:19', '2026-05-11 21:52:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_field_day_start` (`field_id`,`day_book`,`start_time`),
  ADD KEY `books_user_id_foreign` (`user_id`),
  ADD KEY `books_tournament_id_day_book_index` (`tournament_id`,`day_book`),
  ADD KEY `books_field_id_day_book_booking_type_index` (`field_id`,`day_book`,`booking_type`),
  ADD KEY `books_payment_status_day_book_index` (`payment_status`,`day_book`),
  ADD KEY `books_field_id_payment_status_index` (`field_id`,`payment_status`),
  ADD KEY `books_status_index` (`status`),
  ADD KEY `books_team_id_foreign` (`team_id`),
  ADD KEY `books_tournament_id_team_id_index` (`tournament_id`,`team_id`),
  ADD KEY `books_confirmed_by_user_id_foreign` (`confirmed_by_user_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `fields`
--
ALTER TABLE `fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fields_field_cluster_id_foreign` (`field_cluster_id`);

--
-- Indexes for table `field_clusters`
--
ALTER TABLE `field_clusters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `field_clusters_owner_id_foreign` (`owner_id`);

--
-- Indexes for table `field_hourly_prices`
--
ALTER TABLE `field_hourly_prices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_field_hourly_prices_cluster_day_field` (`field_cluster_id`,`day_type`,`field_type`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `matches_tournament_id_foreign` (`tournament_id`),
  ADD KEY `matches_tournament_round_id_foreign` (`tournament_round_id`),
  ADD KEY `matches_tournament_group_id_foreign` (`tournament_group_id`),
  ADD KEY `matches_home_team_id_foreign` (`home_team_id`),
  ADD KEY `matches_away_team_id_foreign` (`away_team_id`),
  ADD KEY `matches_home_from_match_id_foreign` (`home_from_match_id`),
  ADD KEY `matches_away_from_match_id_foreign` (`away_from_match_id`),
  ADD KEY `matches_winner_team_id_foreign` (`winner_team_id`),
  ADD KEY `matches_match_date_match_time_index` (`match_date`,`match_time`),
  ADD KEY `matches_status_index` (`status`),
  ADD KEY `matches_field_id_match_date_index` (`field_id`,`match_date`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id_is_read_index` (`user_id`,`is_read`),
  ADD KEY `notifications_created_at_index` (`created_at`);

--
-- Indexes for table `owner_profiles`
--
ALTER TABLE `owner_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_profiles_user_id_foreign` (`user_id`);

--
-- Indexes for table `owner_registrations`
--
ALTER TABLE `owner_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `owner_registrations_email_unique` (`email`),
  ADD KEY `owner_registrations_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_role_unique` (`name_role`);

--
-- Indexes for table `staffs`
--
ALTER TABLE `staffs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staffs_user_id_foreign` (`user_id`),
  ADD KEY `staffs_owner_id_foreign` (`owner_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_captain_per_team` (`captain_user_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_team_jersey` (`team_id`,`jersey_number`),
  ADD KEY `team_members_team_id_index` (`team_id`);

--
-- Indexes for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tournaments_field_cluster_id_foreign` (`field_cluster_id`),
  ADD KEY `tournaments_organizer_id_foreign` (`organizer_id`),
  ADD KEY `tournaments_status_index` (`status`),
  ADD KEY `tournaments_format_index` (`format`),
  ADD KEY `tournaments_payment_status_index` (`payment_status`),
  ADD KEY `tournaments_payment_method_index` (`payment_method`);

--
-- Indexes for table `tournament_groups`
--
ALTER TABLE `tournament_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tournament_group` (`tournament_id`,`group_name`);

--
-- Indexes for table `tournament_group_standings`
--
ALTER TABLE `tournament_group_standings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_group_team_standing` (`tournament_group_id`,`team_id`),
  ADD KEY `tournament_group_standings_team_id_foreign` (`team_id`);

--
-- Indexes for table `tournament_registrations`
--
ALTER TABLE `tournament_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tournament_team_registration` (`tournament_id`,`team_id`),
  ADD KEY `tournament_registrations_team_id_foreign` (`team_id`),
  ADD KEY `tournament_registrations_registration_status_index` (`registration_status`);

--
-- Indexes for table `tournament_rounds`
--
ALTER TABLE `tournament_rounds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tournament_rounds_tournament_id_round_order_index` (`tournament_id`,`round_order`);

--
-- Indexes for table `tournament_schedules`
--
ALTER TABLE `tournament_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tournament_schedule` (`tournament_id`,`match_date`,`session_type`);

--
-- Indexes for table `tournament_session_prices`
--
ALTER TABLE `tournament_session_prices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tournament_session_prices` (`field_cluster_id`,`field_type`,`day_type`,`session_type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_code_id_unique` (`code_id`),
  ADD UNIQUE KEY `users_email_verification_token_unique` (`email_verification_token`),
  ADD KEY `users_role_id_foreign` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fields`
--
ALTER TABLE `fields`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `field_clusters`
--
ALTER TABLE `field_clusters`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `field_hourly_prices`
--
ALTER TABLE `field_hourly_prices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `owner_profiles`
--
ALTER TABLE `owner_profiles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `owner_registrations`
--
ALTER TABLE `owner_registrations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staffs`
--
ALTER TABLE `staffs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `tournament_groups`
--
ALTER TABLE `tournament_groups`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tournament_group_standings`
--
ALTER TABLE `tournament_group_standings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tournament_registrations`
--
ALTER TABLE `tournament_registrations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tournament_rounds`
--
ALTER TABLE `tournament_rounds`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tournament_schedules`
--
ALTER TABLE `tournament_schedules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tournament_session_prices`
--
ALTER TABLE `tournament_session_prices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_confirmed_by_user_id_foreign` FOREIGN KEY (`confirmed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `books_field_id_foreign` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `books_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `books_tournament_id_foreign` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `books_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fields`
--
ALTER TABLE `fields`
  ADD CONSTRAINT `fields_field_cluster_id_foreign` FOREIGN KEY (`field_cluster_id`) REFERENCES `field_clusters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `field_clusters`
--
ALTER TABLE `field_clusters`
  ADD CONSTRAINT `field_clusters_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `owner_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `field_hourly_prices`
--
ALTER TABLE `field_hourly_prices`
  ADD CONSTRAINT `field_hourly_prices_field_cluster_id_foreign` FOREIGN KEY (`field_cluster_id`) REFERENCES `field_clusters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_away_from_match_id_foreign` FOREIGN KEY (`away_from_match_id`) REFERENCES `matches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matches_away_team_id_foreign` FOREIGN KEY (`away_team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matches_field_id_foreign` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `matches_home_from_match_id_foreign` FOREIGN KEY (`home_from_match_id`) REFERENCES `matches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matches_home_team_id_foreign` FOREIGN KEY (`home_team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matches_tournament_group_id_foreign` FOREIGN KEY (`tournament_group_id`) REFERENCES `tournament_groups` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matches_tournament_id_foreign` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matches_tournament_round_id_foreign` FOREIGN KEY (`tournament_round_id`) REFERENCES `tournament_rounds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matches_winner_team_id_foreign` FOREIGN KEY (`winner_team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `owner_profiles`
--
ALTER TABLE `owner_profiles`
  ADD CONSTRAINT `owner_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `owner_registrations`
--
ALTER TABLE `owner_registrations`
  ADD CONSTRAINT `owner_registrations_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staffs`
--
ALTER TABLE `staffs`
  ADD CONSTRAINT `staffs_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `owner_profiles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staffs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_captain_user_id_foreign` FOREIGN KEY (`captain_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD CONSTRAINT `tournaments_field_cluster_id_foreign` FOREIGN KEY (`field_cluster_id`) REFERENCES `field_clusters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tournaments_organizer_id_foreign` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tournament_groups`
--
ALTER TABLE `tournament_groups`
  ADD CONSTRAINT `tournament_groups_tournament_id_foreign` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tournament_group_standings`
--
ALTER TABLE `tournament_group_standings`
  ADD CONSTRAINT `tournament_group_standings_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tournament_group_standings_tournament_group_id_foreign` FOREIGN KEY (`tournament_group_id`) REFERENCES `tournament_groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tournament_registrations`
--
ALTER TABLE `tournament_registrations`
  ADD CONSTRAINT `tournament_registrations_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tournament_registrations_tournament_id_foreign` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tournament_rounds`
--
ALTER TABLE `tournament_rounds`
  ADD CONSTRAINT `tournament_rounds_tournament_id_foreign` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tournament_schedules`
--
ALTER TABLE `tournament_schedules`
  ADD CONSTRAINT `tournament_schedules_tournament_id_foreign` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tournament_session_prices`
--
ALTER TABLE `tournament_session_prices`
  ADD CONSTRAINT `tournament_session_prices_field_cluster_id_foreign` FOREIGN KEY (`field_cluster_id`) REFERENCES `field_clusters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
