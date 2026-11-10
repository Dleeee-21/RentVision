

CREATE DATABASE IF NOT EXISTS `rent_vision`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `rent_vision`;


CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `avatar` VARCHAR(500) DEFAULT NULL,
  `role` ENUM('Dev','Customer','Vendor') NOT NULL DEFAULT 'Customer',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `icon` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE `providers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `store_name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `location` VARCHAR(200) DEFAULT NULL,
  `avatar` VARCHAR(500) DEFAULT NULL,
  `rating` DECIMAL(2,1) DEFAULT 0.0,
  `total_transactions` INT UNSIGNED DEFAULT 0,
  `is_verified` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `provider_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `price_per_day` DECIMAL(12,2) NOT NULL,
  `price_per_week` DECIMAL(12,2) DEFAULT NULL,
  `deposit` DECIMAL(12,2) DEFAULT 0,
  `stock` INT UNSIGNED DEFAULT 1,
  `condition` ENUM('baru','bekas','sebagus_baru') DEFAULT 'baru',
  `is_available` TINYINT(1) DEFAULT 1,
  `rating` DECIMAL(2,1) DEFAULT 0.0,
  `total_reviews` INT UNSIGNED DEFAULT 0,
  `location` VARCHAR(200) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`provider_id`) REFERENCES `providers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE `product_images` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED NOT NULL,
  `image_url` VARCHAR(500) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  `sort_order` TINYINT UNSIGNED DEFAULT 0,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE `bookings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `booking_code` VARCHAR(20) NOT NULL UNIQUE,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `total_days` INT UNSIGNED NOT NULL,
  `total_price` DECIMAL(14,2) NOT NULL,
  `status` ENUM('pending','confirmed','active','completed','cancelled') DEFAULT 'pending',
  `payment_status` ENUM('unpaid','paid','refunded') DEFAULT 'unpaid',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE `booking_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED DEFAULT 1,
  `price_per_day` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(14,2) NOT NULL,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `token` VARCHAR(32) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NOT NULL,
  `booking_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `status` ENUM('pending','paid','expired') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NOT NULL,
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE `reviews` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `booking_id` INT UNSIGNED DEFAULT NULL,
  `rating` TINYINT UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE `wishlists` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_wishlist` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE `messages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT UNSIGNED NOT NULL,
  `receiver_id` INT UNSIGNED NOT NULL,
  `subject` VARCHAR(200) DEFAULT NULL,
  `body` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('booking','review','promo','system') DEFAULT 'system',
  `is_read` TINYINT(1) DEFAULT 0,
  `link` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;




-- usn ((password: password123)
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`) VALUES
('Fadly Bisma',   'fadly@rentvision.id',   '$2y$10$L66sInNWD6oFtwmDEl3Iy.knKfM8x.lAdG8Z29HaeOcQKNf9lSZx2', '081234567890', 'Customer'),
('Rina Hartanti', 'rina@kamerakita.id',    '$2y$10$L66sInNWD6oFtwmDEl3Iy.knKfM8x.lAdG8Z29HaeOcQKNf9lSZx2', '081234567891', 'Vendor'),
('Dimas Pratama', 'dimas@lenshouse.id',    '$2y$10$L66sInNWD6oFtwmDEl3Iy.knKfM8x.lAdG8Z29HaeOcQKNf9lSZx2', '081234567892', 'Vendor'),
('Sari Dewi',     'sari@focusrent.id',     '$2y$10$L66sInNWD6oFtwmDEl3Iy.knKfM8x.lAdG8Z29HaeOcQKNf9lSZx2', '081234567893', 'Vendor'),
('Admin',         'admin@rentvision.id',   '$2y$10$L66sInNWD6oFtwmDEl3Iy.knKfM8x.lAdG8Z29HaeOcQKNf9lSZx2', '081234567899', 'Dev');


INSERT INTO `categories` (`name`, `slug`, `icon`) VALUES
('Kamera',     'kamera',     'camera'),
('Lensa',      'lensa',      'lens'),
('Lighting',   'lighting',   'lighting'),
('Tripod',     'tripod',     'tripod'),
('Drone',      'drone',      'drone'),
('Audio',      'audio',      'audio'),
('Aksesoris',  'aksesoris',  'accessories');


INSERT INTO `providers` (`user_id`, `store_name`, `slug`, `description`, `location`, `rating`, `total_transactions`, `is_verified`) VALUES
(2, 'KameraKita',   'kamerakita',   'Toko peralatan fotografi terlengkap di Jakarta Selatan.',       'Jakarta Selatan', 4.9, 120, 1),
(3, 'LensHouse ID', 'lenshouse-id', 'Spesialis lensa premium dan aksesoris摄影.',                      'Jakarta Pusat',    4.8, 98,  1),
(4, 'FocusRent',    'focusrent',    'Sewa kamera & lighting profesional untuk event dan produksi.',   'Bekasi',           4.7, 87,  1);


INSERT INTO `products` (`provider_id`, `category_id`, `name`, `slug`, `description`, `price_per_day`, `price_per_week`, `deposit`, `stock`, `condition`, `rating`, `total_reviews`, `location`) VALUES

(1, 1, 'Sony A7 IV',                    'sony-a7-iv',                    'Kamera mirrorless full-frame 33MP dengan AI AF.',                  300000,  1800000, 2000000, 3, 'baru',   4.9, 128, 'Jakarta Selatan'),
(1, 1, 'Canon EOS R6 Mark II',          'canon-eos-r6-mark-ii',          'Mirrorless full-frame 24.2MP, burst 40fps.',                      350000,  2100000, 2500000, 2, 'baru',   4.8, 95,  'Jakarta Selatan'),
(1, 1, 'Nikon Z6 III',                  'nikon-z6-iii',                  'Hybrid mirrorless 24.5MP, video 6K.',                             320000,  1920000, 2200000, 2, 'baru',   4.7, 67,  'Jakarta Selatan'),

(2, 2, 'Sony FE 24-70mm f/2.8 GM II',   'sony-fe-2470-gm2',             'Lensa standar zoom profesional G Master.',                         250000,  1500000, 3000000, 4, 'baru',   4.8, 96,  'Jakarta Pusat'),
(2, 2, 'Canon RF 70-200mm f/2.8L IS',   'canon-rf-70200',                'Telephoto zoom premium untuk portrait & event.',                  280000,  1680000, 3500000, 3, 'baru',   4.9, 82,  'Jakarta Pusat'),
(2, 2, 'Sigma 35mm f/1.4 DG DN Art',    'sigma-35mm-art',                'Lensa fixed 35mm bukaan lebar, tajam.',                           120000,  720000,  1500000, 3, 'baru',   4.7, 54,  'Jakarta Pusat'),

(3, 3, 'Godox AD600Pro',                'godox-ad600pro',                'Strobe 600Ws, TTL HSS, color stability.',                         150000,  900000,  1000000, 4, 'baru',   4.7, 63,  'Bekasi'),
(3, 3, 'Aputure LS 600d Pro',           'aputure-ls600d',                'LED point-source 600W daylight, Bowens mount.',                  180000,  1080000, 1200000, 3, 'baru',   4.8, 45,  'Bekasi'),
(3, 3, 'Nanlite Forza 300B',            'nanlite-forza-300b',            'Bi-color LED 300W, output tinggi & portable.',                    130000,  780000,  800000,  3, 'baru',   4.6, 38,  'Bekasi'),

(1, 4, 'Manfrotto MT055CXPRO4',         'manfrotto-mt055',               'Tripod karbon 4-segmen, max height 170cm.',                      50000,   300000,  400000,  5, 'baru',   4.8, 72,  'Jakarta Selatan'),
(1, 4, 'Gitzo GT3543XLS',               'gitzo-gt3543xls',               'Systematic tripod karbon, ultra stabil.',                         75000,   450000,  600000,  2, 'baru',   4.9, 41,  'Jakarta Selatan'),

(2, 5, 'DJI Mavic Air 2S',              'dji-mavic-air-2s',               'Drone compact 5.4K, sensor 1-inch.',                              350000,  2100000, 3000000, 2, 'baru',   4.9, 73,  'Jakarta Pusat'),
(2, 5, 'DJI Air 3',                     'dji-air-3',                      'Drone dual-camera 4K/100fps, 46 menit flight.',                   400000,  2400000, 3500000, 2, 'baru',   4.8, 55,  'Jakarta Pusat'),

(3, 6, 'Rode Wireless GO II',           'rode-wireless-go-ii',            'Wireless mic 2-channel, built-in recording.',                     80000,   480000,  500000,  5, 'baru',   4.8, 88,  'Bekasi'),
(3, 6, 'Zoom H6',                        'zoom-h6',                        'Portable recorder 6-track, multi-capsule.',                       90000,   540000,  600000,  3, 'baru',   4.7, 52,  'Bekasi'),

(1, 7, 'Peak Design Everyday Backpack',  'peak-design-backpack',           'Tas kamera 30L, weatherproof, access ports.',                     45000,   270000,  300000,  4, 'baru',   4.9, 66,  'Jakarta Selatan'),
(1, 7, 'SanDisk Extreme Pro 128GB CF',  'sandisk-128gb-cf',               'CFexpress Type B, 1700MB/s read.',                                35000,   210000,  200000,  6, 'baru',   4.8, 93,  'Jakarta Selatan');


INSERT INTO `product_images` (`product_id`, `image_url`, `is_primary`, `sort_order`) VALUES
(1,  'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=500&q=80', 1, 0),
(2,  'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=500&q=80', 1, 0),
(3,  'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=500&q=80', 1, 0),
(4,  'https://images.unsplash.com/photo-1606986628253-05620e9bb18e?w=500&q=80', 1, 0),
(5,  'https://images.unsplash.com/photo-1617005082133-548c4dd27f35?w=500&q=80', 1, 0),
(6,  'https://images.unsplash.com/photo-1611261116515-f43e33b0eb84?w=500&q=80', 1, 0),
(7,  'https://images.unsplash.com/photo-1621459555621-71769cf7fa50?w=500&q=80', 1, 0),
(8,  'https://images.unsplash.com/photo-1500468756762-a401b6f17b46?w=500&q=80', 1, 0),
(9,  'https://images.unsplash.com/photo-1513506003901-1e6a229e2d15?w=500&q=80', 1, 0),
(10, 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=500&q=80', 1, 0),
(11, 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=500&q=80', 1, 0),
(12, 'https://images.unsplash.com/photo-1508444845599-5c89863b1c44?w=500&q=80', 1, 0),
(13, 'https://images.unsplash.com/photo-1527977966376-1c8408f9f108?w=500&q=80', 1, 0),
(14, 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?w=500&q=80', 1, 0),
(15, 'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?w=500&q=80', 1, 0),
(16, 'https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=500&q=80', 1, 0);


INSERT INTO `bookings` (`user_id`, `booking_code`, `start_date`, `end_date`, `total_days`, `total_price`, `status`, `payment_status`) VALUES
(1, 'RV-2026-001', '2026-07-20', '2026-07-24', 4, 1200000, 'active',    'paid'),
(1, 'RV-2026-002', '2026-08-01', '2026-08-03', 2, 500000,  'pending',   'unpaid');

INSERT INTO `booking_items` (`booking_id`, `product_id`, `quantity`, `price_per_day`, `subtotal`) VALUES
(1, 1, 1, 300000, 1200000),
(2, 4, 1, 250000, 500000);


INSERT INTO `reviews` (`user_id`, `product_id`, `rating`, `comment`) VALUES
(1, 1, 5, 'Kamera sangat bagus, autofocus cepat dan hasil foto tajam. Recommended!'),
(1, 4, 4, 'Lensa premium, bokeh indah. Sedikit berat tapi worth it.');


INSERT INTO `wishlists` (`user_id`, `product_id`) VALUES
(1, 7),
(1, 12);


INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`) VALUES
(1, 'Booking Dikonfirmasi', 'Booking RV-2026-001 Anda telah dikonfirmasi oleh KameraKita.', 'booking', 0),
(1, 'Promo Spesial',       'Diskon 20% untuk sewa drone minggu ini!',                     'promo',   0);
