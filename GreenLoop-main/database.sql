-- GreenLoop : ส่งต่อเทคโนโลยี เพื่อโลกที่ยั่งยืน
-- นำเข้าไฟล์นี้ผ่าน phpMyAdmin หรือ:  mysql -u root < database.sql

CREATE DATABASE IF NOT EXISTS greenloop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE greenloop;
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS settings, messages, banners, redemptions, rewards, user_achievements, achievements,
  point_transactions, recycles, topups, wallet_transactions, orders, favorites, products, categories, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NOT NULL DEFAULT '',
  address TEXT,
  avatar VARCHAR(255) DEFAULT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  wallet_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
  green_points INT NOT NULL DEFAULT 0,
  fee_waivers INT NOT NULL DEFAULT 0,
  premium_until DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  type ENUM('market','digital') NOT NULL,
  icon VARCHAR(50) NOT NULL DEFAULT 'bi-box-seam'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  category_id INT DEFAULT NULL,
  type ENUM('market','digital') NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  price DECIMAL(12,2) NOT NULL,
  item_condition VARCHAR(20) NOT NULL DEFAULT '',
  stock INT NOT NULL DEFAULT 1,
  image VARCHAR(255) DEFAULT NULL,
  file_path VARCHAR(255) DEFAULT NULL,
  file_name VARCHAR(255) DEFAULT NULL,
  green_score INT NOT NULL DEFAULT 80,
  status ENUM('pending','approved','rejected','sold','hidden') NOT NULL DEFAULT 'pending',
  promoted_until DATETIME DEFAULT NULL,
  views INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_browse (status, type, created_at),
  CONSTRAINT fk_products_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_products_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE favorites (
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, product_id),
  CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rewards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description VARCHAR(255) NOT NULL DEFAULT '',
  type ENUM('coupon','fee','item','premium') NOT NULL,
  points_cost INT NOT NULL,
  value DECIMAL(10,2) NOT NULL DEFAULT 0,
  icon VARCHAR(50) NOT NULL DEFAULT 'bi-gift',
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE redemptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  reward_id INT DEFAULT NULL,
  reward_name VARCHAR(150) NOT NULL,
  type VARCHAR(20) NOT NULL,
  points INT NOT NULL,
  value DECIMAL(10,2) NOT NULL DEFAULT 0,
  code VARCHAR(30) NOT NULL,
  status ENUM('active','used','pending','delivered') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_red_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_red_reward FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_no VARCHAR(30) NOT NULL UNIQUE,
  buyer_id INT NOT NULL,
  seller_id INT NOT NULL,
  product_id INT NOT NULL,
  product_title VARCHAR(200) NOT NULL,
  type ENUM('market','digital') NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  points_used INT NOT NULL DEFAULT 0,
  coupon_id INT DEFAULT NULL,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL,
  commission DECIMAL(12,2) NOT NULL DEFAULT 0,
  seller_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('paid','shipped','completed','cancelled') NOT NULL DEFAULT 'paid',
  ship_name VARCHAR(100) NOT NULL DEFAULT '',
  ship_phone VARCHAR(30) NOT NULL DEFAULT '',
  ship_address TEXT,
  tracking VARCHAR(100) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME DEFAULT NULL,
  CONSTRAINT fk_orders_buyer FOREIGN KEY (buyer_id) REFERENCES users(id),
  CONSTRAINT fk_orders_seller FOREIGN KEY (seller_id) REFERENCES users(id),
  CONSTRAINT fk_orders_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wallet_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(20) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  balance_after DECIMAL(12,2) NOT NULL,
  note VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_wtx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE topups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  slip VARCHAR(255) NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  admin_note VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME DEFAULT NULL,
  CONSTRAINT fk_topups_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recycles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  device_type VARCHAR(100) NOT NULL,
  detail VARCHAR(255) NOT NULL DEFAULT '',
  quantity INT NOT NULL DEFAULT 1,
  method VARCHAR(20) NOT NULL DEFAULT 'parcel',
  contact TEXT,
  image VARCHAR(255) DEFAULT NULL,
  status ENUM('pending','accepted','received','completed','rejected') NOT NULL DEFAULT 'pending',
  admin_note VARCHAR(255) NOT NULL DEFAULT '',
  points_awarded TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_recycles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE point_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  points INT NOT NULL,
  reason VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ptx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE achievements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) NOT NULL,
  icon VARCHAR(50) NOT NULL,
  metric ENUM('sales','purchases','recycles','points') NOT NULL,
  threshold INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_achievements (
  user_id INT NOT NULL,
  achievement_id INT NOT NULL,
  unlocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, achievement_id),
  CONSTRAINT fk_ua_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ua_ach FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE banners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  subtitle VARCHAR(255) NOT NULL DEFAULT '',
  image VARCHAR(255) DEFAULT NULL,
  link VARCHAR(255) NOT NULL DEFAULT '',
  position ENUM('top','middle','side') NOT NULL DEFAULT 'top',
  sort_order INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  product_id INT DEFAULT NULL,
  body TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pair (sender_id, receiver_id),
  CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  name VARCHAR(50) PRIMARY KEY,
  value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- ข้อมูลเริ่มต้น
-- ---------------------------------------------------------------------------

-- ผู้ดูแลระบบ: admin@greenloop.local / admin1234   |   ผู้ใช้ตัวอย่าง: demo@greenloop.local / demo1234
INSERT INTO users (id, name, email, password_hash, phone, address, role, wallet_balance, green_points) VALUES
(1, 'ผู้ดูแลระบบ', 'admin@greenloop.local', '$2y$10$iDLUZ/N6GIsqYvbVOXNT2e.6bjvV0f/DakxJ.NFpBEbZ2WZjIAyeS', '02-000-0000', 'GreenDigital กรุงเทพฯ', 'admin', 0, 0),
(2, 'ร้าน GreenTech', 'seller@greenloop.local', '$2y$10$OLina/Sa8FRWg0GgRnx9auLzieFj04TSCAJp0I/Oi3vs2h2CG5yNG', '081-111-1111', '99 ถ.พหลโยธิน กรุงเทพฯ 10400', 'user', 1500, 120),
(3, 'Pixel Studio', 'studio@greenloop.local', '$2y$10$OLina/Sa8FRWg0GgRnx9auLzieFj04TSCAJp0I/Oi3vs2h2CG5yNG', '082-222-2222', 'เชียงใหม่', 'user', 800, 60),
(4, 'ผู้ใช้ตัวอย่าง', 'demo@greenloop.local', '$2y$10$OLina/Sa8FRWg0GgRnx9auLzieFj04TSCAJp0I/Oi3vs2h2CG5yNG', '083-333-3333', '12/3 ถ.สุขุมวิท กรุงเทพฯ 10110', 'user', 30000, 200);

INSERT INTO wallet_transactions (user_id, type, amount, balance_after, note) VALUES
(2, 'topup', 1500, 1500, 'ยอดเริ่มต้นบัญชีตัวอย่าง'),
(3, 'topup', 800, 800, 'ยอดเริ่มต้นบัญชีตัวอย่าง'),
(4, 'topup', 30000, 30000, 'ยอดเริ่มต้นบัญชีตัวอย่าง');

INSERT INTO point_transactions (user_id, points, reason) VALUES
(2, 120, 'คะแนนเริ่มต้นบัญชีตัวอย่าง'),
(3, 60, 'คะแนนเริ่มต้นบัญชีตัวอย่าง'),
(4, 200, 'คะแนนเริ่มต้นบัญชีตัวอย่าง');

INSERT INTO categories (id, name, type, icon) VALUES
(1, 'โทรศัพท์มือถือ', 'market', 'bi-phone'),
(2, 'Notebook', 'market', 'bi-laptop'),
(3, 'Tablet', 'market', 'bi-tablet'),
(4, 'กล้อง', 'market', 'bi-camera'),
(5, 'อุปกรณ์ IT', 'market', 'bi-pc-display'),
(6, 'อุปกรณ์เสริม', 'market', 'bi-headphones'),
(7, 'Template', 'digital', 'bi-layout-text-window-reverse'),
(8, 'Graphic', 'digital', 'bi-palette'),
(9, 'E-Book', 'digital', 'bi-book'),
(10, 'Digital Art', 'digital', 'bi-brush'),
(11, 'Font', 'digital', 'bi-fonts'),
(12, 'Icon', 'digital', 'bi-grid-3x3-gap'),
(13, 'Software', 'digital', 'bi-code-square');

INSERT INTO products (user_id, category_id, type, title, description, price, item_condition, stock, green_score, status, promoted_until) VALUES
(2, 2, 'market', 'MacBook Air M1', 'MacBook Air ชิป M1 RAM 8GB SSD 256GB สี Space Gray แบตเตอรี่ 89% ใช้งานปกติทุกฟังก์ชัน มีกล่องและที่ชาร์จแท้', 18900, 'very_good', 1, 90, 'approved', DATE_ADD(NOW(), INTERVAL 30 DAY)),
(2, 1, 'market', 'iPhone 12', 'iPhone 12 ความจุ 128GB สีน้ำเงิน เครื่องศูนย์ไทย สุขภาพแบต 85% มีรอยขนแมวเล็กน้อย', 8500, 'good', 1, 85, 'approved', DATE_ADD(NOW(), INTERVAL 30 DAY)),
(2, 3, 'market', 'iPad Air 4 Wi-Fi 64GB', 'iPad Air รุ่นที่ 4 จอ 10.9 นิ้ว สีเขียว พร้อมเคสและฟิล์ม ใช้งานเรียนออนไลน์ สภาพสวย', 11900, 'very_good', 1, 90, 'approved', NULL),
(2, 4, 'market', 'Canon EOS M50 + เลนส์ 15-45mm', 'กล้อง Mirrorless ชัตเตอร์ประมาณ 8,000 พร้อมแบต 2 ก้อน กระเป๋า และเมมโมรี่ 64GB', 13500, 'good', 1, 85, 'approved', NULL),
(2, 6, 'market', 'หูฟัง Sony WH-1000XM4', 'หูฟังตัดเสียงรบกวน สีดำ อุปกรณ์ครบกล่อง ฟองน้ำเปลี่ยนใหม่', 5900, 'like_new', 1, 95, 'approved', NULL),
(2, 5, 'market', 'จอมอนิเตอร์ Dell 24 นิ้ว IPS', 'จอ Full HD ขอบบาง ไม่มี Dead Pixel พร้อมสาย HDMI', 2900, 'good', 2, 85, 'approved', NULL),
(4, 5, 'market', 'คีย์บอร์ด Logitech MX Keys', 'คีย์บอร์ดไร้สาย ภาษาไทย/อังกฤษ ใช้งานปกติ มีรอยมันที่ปุ่มเล็กน้อย', 1890, 'fair', 1, 75, 'approved', NULL),
(4, 1, 'market', 'Samsung Galaxy S21 5G', 'ความจุ 256GB สี Phantom Gray จอไม่มีรอย เครื่องศูนย์ไทย', 7900, 'very_good', 1, 90, 'approved', NULL),
(3, 7, 'digital', 'AI Template', 'ชุด Template สำหรับงานนำเสนอด้าน AI และเทคโนโลยี 40 สไลด์ แก้ไขได้ทั้งหมด (.pptx / .ai)', 199, '', 999, 80, 'approved', DATE_ADD(NOW(), INTERVAL 30 DAY)),
(3, 9, 'digital', 'E-Book Design', 'E-Book พื้นฐานการออกแบบกราฟิกสำหรับมือใหม่ 120 หน้า ไฟล์ PDF อ่านได้ทุกอุปกรณ์', 149, '', 999, 70, 'approved', DATE_ADD(NOW(), INTERVAL 30 DAY)),
(3, 12, 'digital', 'Eco Icon Pack 500 ไอคอน', 'ชุดไอคอนแนวรักษ์โลก 500 ชิ้น รูปแบบ SVG / PNG ใช้เชิงพาณิชย์ได้', 259, '', 999, 80, 'approved', NULL),
(3, 11, 'digital', 'ฟอนต์ไทย GreenSans', 'ฟอนต์ภาษาไทยหัวมน 6 น้ำหนัก เหมาะกับงานเว็บไซต์และสื่อสิ่งพิมพ์', 390, '', 999, 80, 'approved', NULL),
(3, 10, 'digital', 'Digital Art: Forest Wallpaper Pack', 'ภาพวาดดิจิทัลธีมป่าไม้ 12 ภาพ ความละเอียด 4K สำหรับมือถือและเดสก์ท็อป', 99, '', 999, 75, 'approved', NULL),
(3, 8, 'digital', 'Social Media Graphic Kit', 'ชุดกราฟิกโพสต์โซเชียล 60 แบบ ไฟล์ PSD แก้ไขสีและข้อความได้', 299, '', 999, 80, 'approved', NULL),
(4, 13, 'digital', 'โปรแกรมจัดการสต็อกร้านค้า (PHP)', 'ซอร์สโค้ดระบบจัดการสต็อกสินค้า PHP + MySQL พร้อมคู่มือติดตั้ง', 590, '', 999, 75, 'approved', NULL);

INSERT INTO achievements (name, description, icon, metric, threshold) VALUES
('นักช้อปรักษ์โลก', 'ซื้อสินค้าสำเร็จครั้งแรก', 'bi-bag-heart', 'purchases', 1),
('ผู้ส่งต่อมือใหม่', 'ขายสินค้าสำเร็จครั้งแรก', 'bi-shop', 'sales', 1),
('พ่อค้าแม่ค้าสายกรีน', 'ขายสินค้าสำเร็จครบ 5 รายการ', 'bi-award', 'sales', 5),
('ฮีโร่รีไซเคิล', 'รีไซเคิลอุปกรณ์สำเร็จครั้งแรก', 'bi-recycle', 'recycles', 1),
('ผู้พิทักษ์ E-Waste', 'รีไซเคิลอุปกรณ์สำเร็จครบ 5 ครั้ง', 'bi-shield-check', 'recycles', 5),
('Green Starter', 'สะสม Green Point ครบ 100 คะแนน', 'bi-star', 'points', 100),
('Green Champion', 'สะสม Green Point ครบ 500 คะแนน', 'bi-trophy', 'points', 500),
('Earth Guardian', 'สะสม Green Point ครบ 1,000 คะแนน', 'bi-globe-asia-australia', 'points', 1000);

INSERT INTO rewards (name, description, type, points_cost, value, icon) VALUES
('ส่วนลดค่าธรรมเนียมการขาย', 'ฟรีค่าธรรมเนียมการขาย 1 รายการ', 'fee', 100, 0, 'bi-bag-check'),
('คูปองส่วนลด ฿20', 'ใช้เป็นส่วนลดเมื่อซื้อสินค้าบนแพลตฟอร์ม', 'coupon', 150, 20, 'bi-ticket-perforated'),
('คูปองพิเศษ ฿50', 'คูปองโปรโมชั่นพิเศษสำหรับสมาชิก', 'coupon', 350, 50, 'bi-star'),
('ถุงผ้ารักษ์โลก GreenLoop', 'ของรางวัลเพื่อสิ่งแวดล้อม จัดส่งถึงบ้าน', 'item', 300, 0, 'bi-handbag'),
('ปลูกต้นไม้ 1 ต้นในนามคุณ', 'ร่วมปลูกต้นไม้กับโครงการ GreenLoop', 'item', 500, 0, 'bi-tree'),
('อัปเกรดสมาชิก Premium 30 วัน', 'ปลดล็อกสิทธิพิเศษสมาชิก Premium', 'premium', 800, 30, 'bi-gem');

INSERT INTO banners (title, subtitle, link, position, sort_order) VALUES
('ส่งต่อเทคโนโลยี เพื่อโลกที่ยั่งยืน', 'ซื้อขายอุปกรณ์ไอทีมือสอง สินค้าดิจิทัล และรีไซเคิล E-Waste ในที่เดียว พร้อมสะสม Green Point', 'products.php?type=market', 'top', 1),
('รีไซเคิล E-Waste รับ +50 Green Point', 'อุปกรณ์ที่ไม่ใช้แล้ว อย่าทิ้ง! แจ้งรีไซเคิลกับเราวันนี้', 'recycle.php', 'top', 2),
('Digital Market เปิดแล้ว', 'Template, Graphic, E-Book, Font และ Software จากครีเอเตอร์ไทย', 'products.php?type=digital', 'middle', 1),
('พื้นที่โฆษณา', 'ลงโฆษณากับ GreenLoop ติดต่อทีมงาน', 'chat.php?with=1', 'side', 1);

INSERT INTO settings (name, value) VALUES
('site_name', 'GreenLoop'),
('tagline', 'ส่งต่อเทคโนโลยี เพื่อโลกที่ยั่งยืน'),
('logo', ''),
('contact_email', 'hello@greenloop.local'),
('contact_phone', '02-000-0000'),
('contact_line', '@greenloop'),
('contact_address', 'GreenDigital Co., Ltd. กรุงเทพมหานคร'),
('bank_name', 'ธนาคารกสิกรไทย'),
('bank_account_name', 'บจก. กรีนดิจิทัล'),
('bank_account_no', '000-0-00000-0'),
('qr_code', ''),
('footer_text', 'GreenLoop — Powered by GreenDigital'),
('commission_rate', '5'),
('premium_commission_rate', '3'),
('premium_price', '99'),
('promote_price', '29');
