-- Shop ICT Gadgets database: schema + store content (prices in KES). Import into an empty server:
--   mysql -u root < database/onsus_shop.sql
-- Seeded admin login email: admin@ict.com (set a unique password before deployment).
SET NAMES utf8mb4;


/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `placement` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` smallint unsigned NOT NULL DEFAULT '1',
  `eyebrow` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Small text above the title (tag)',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title_highlight` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Emphasised second part of the title',
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_label` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `button_text` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bg_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tag_color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_hex` char(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Custom title colour, e.g. #1d2733',
  `bg_hex` char(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Custom background colour, shown under / instead of the background image',
  `title_color` char(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Hero main title colour',
  `title_highlight_color` char(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Hero main title highlight colour',
  `subtitle_color` char(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Hero main subtitle colour',
  `button_color` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Hero main button background: red or white',
  `hero_price_color` char(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Hero main price colour',
  `hero_price_visible` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether to show the main hero price',
  `meta` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON, e.g. hero feature list',
  `product_id` int unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_banner_placement` (`placement`,`position`),
  KEY `fk_banners_product` (`product_id`),
  CONSTRAINT `fk_banners_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` (`id`, `placement`, `position`, `eyebrow`, `title`, `title_highlight`, `subtitle`, `price_label`, `price`, `button_text`, `link_url`, `image`, `bg_image`, `tag_color`, `text_color`, `price_color`, `text_hex`, `bg_hex`, `meta`, `product_id`, `is_active`, `updated_at`) VALUES (1,'hero_main',1,NULL,'Xiaomi','2K Monitor','27\" QHD IPS panel',NULL,NULL,'Shop now','./','images/uploads/20261105-1424ff0b4de9.png','images/uploads/20261105-07f8554b1c90.png',NULL,NULL,NULL,NULL,NULL,'[{\"label\":\"100Hz\",\"value\":\"Refresh Rate\"},{\"label\":\"2560 x 1440\",\"value\":\"resolution\"},{\"label\":\"Ports\",\"value\":\"DisplayPort 1.4,HDMI\"}]',NULL,1,'2026-11-05 19:03:39'),(2,'hero_side_1',1,'Exclusive','Black panther',NULL,NULL,NULL,'Controlller','Now available','#','images/uploads/20261105-a636ddc6cb8d.png','images/uploads/20261106-39de46c2580d.png',NULL,NULL,NULL,'#050505',NULL,NULL,NULL,1,'2026-11-05 22:07:57'),(4,'featured_card',1,'Logi','C270 HD WEBCAM',NULL,'Powered by High-Fidelity \r\n Audio Technology',NULL,'3900',NULL,'./','images/uploads/20261105-172b67e6d8cf.png',NULL,'text-secondary','text-black','text-secondary',NULL,NULL,NULL,NULL,1,'2026-11-05 20:33:19'),(5,'featured_card',2,'Harman Kardon','Onyx Studio 9',NULL,'The Empire Strikes Back',NULL,'35,000',NULL,'./','images/uploads/20261105-bb29162fa573.png',NULL,'text-white','text-white','text-white',NULL,NULL,NULL,NULL,1,'2026-11-05 20:39:00'),(6,'featured_card',3,'HP pavilion laptops','Energize Your\r\n Imagination',NULL,'Power by 7th gen intel processor',NULL,'139,836',NULL,'./','images/uploads/20261105-c1fb095f2729.png',NULL,'text-secondary','text-black','text-secondary',NULL,NULL,NULL,NULL,1,'2026-11-05 21:00:30'),(7,'smart_home',1,'New arrival','HP Victus 15-fa2082wm',NULL,NULL,NULL,'KES 128,000',NULL,'./','images/uploads/20261106-f258fea45641.png',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-05 21:14:59'),(8,'promo_slider',1,NULL,'Lenovo ThinkBook','8GB/MX450 2GB',NULL,'From','KSh 51,471',NULL,'./','images/uploads/20261106-77bf048baf31.png','images/banner/banner-3.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-05 21:27:52'),(9,'promo_slider',2,NULL,'ThinkPad X1 Carbon Gen 9','4K HDR-Core i7 32GB',NULL,'From','KSh 180,471',NULL,'./','images/uploads/20261106-364ba130e19f.png','images/banner/banner-4.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-05 21:26:33'),(10,'promo_slider',3,NULL,'MacBook Air M3','16GB / 512GB',NULL,'From','KSh 141,771',NULL,'./','images/uploads/20261106-7eeb14f60768.png','images/uploads/20261104-a6ea933d4c0f.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-05 21:26:47'),(11,'hero_main',2,NULL,'Samsung','27\' Inch','27\" QHD IPS panel',NULL,NULL,'Shop now','./','images/uploads/20261105-8064d9b2a70f.png','images/uploads/20261105-901d2a9cd5d5.png',NULL,NULL,NULL,NULL,NULL,'[{\"label\":\"100Hz\",\"value\":\"Refresh Rate\"},{\"label\":\"2560 x 1440\",\"value\":\"resolution\"},{\"label\":\"Ports\",\"value\":\"DisplayPort 1.4,HDMI\"}]',NULL,1,'2026-11-05 19:14:18'),(12,'hero_side_1',2,'HP','EliteBook 1040 G8',NULL,NULL,NULL,'KES61,000','Now available','#','images/uploads/20261105-b958ee27c0ce.png','images/uploads/20261106-fa4e0ce19b77.png',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-05 21:18:49'),(13,'promo_slider',4,NULL,'Samsung Essential Monitor','27-inch FHD Display',NULL,NULL,NULL,NULL,NULL,'images/uploads/20261106-7cff9e505103.png','images/uploads/20261106-915ff7c1e53a.png',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-05 21:35:30');
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brands` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Uploaded logo; the name is shown as text when empty',
  `link_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Empty = search the shop for the brand name',
  `position` smallint unsigned NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` (`id`, `name`, `logo`, `link_url`, `position`, `is_active`) VALUES (1,'Apple',NULL,NULL,8,1),(2,'SAMSUNG',NULL,NULL,4,1),(3,'Sony',NULL,NULL,103,0),(4,'hp',NULL,NULL,1,1),(5,'Lenovo',NULL,NULL,2,1),(6,'Xiaomi',NULL,NULL,106,0),(7,'JBL',NULL,NULL,107,0),(8,'Beats',NULL,NULL,108,0),(9,'Logitech',NULL,NULL,9,1),(10,'TCL',NULL,NULL,110,0),(11,'Corsair',NULL,NULL,111,0),(12,'Fitbit',NULL,NULL,112,0),(17,'DELL',NULL,NULL,3,1),(18,'EPSON',NULL,NULL,5,1),(19,'Canon',NULL,NULL,6,1),(20,'acer',NULL,NULL,7,1);
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A new line becomes a line break on large screens',
  `img_src` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` smallint unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` (`id`, `title`, `img_src`, `position`) VALUES (1,'Laptops & accessories','images/collection/category-1.png',1),(2,'Phones & tablets','images/collection/category-2.png',2),(3,'Apple products','images/collection/category-3.png',3),(4,'Server & workstation','images/collection/category-4.png',4),(5,'Game\ncontroller','images/collection/category-5.png',5),(6,'Computer\nscreen','images/collection/category-6.png',6),(7,'Network equipment','images/collection/category-7.png',7),(8,'Audio equipments','images/collection/category-8.png',8),(9,'Storage & digital devices','images/collection/category-9.png',9),(10,'Camera & smartHome','images/collection/category-10.png',10),(11,'Laptops & accessories','images/collection/category-1.png',11);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `features` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `icon_class` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `wow_delay` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0s',
  `position` smallint unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `features` WRITE;
/*!40000 ALTER TABLE `features` DISABLE KEYS */;
INSERT INTO `features` (`id`, `icon_class`, `title`, `wow_delay`, `position`) VALUES (1,'icon icon-delivery','Free delivery','0s',1),(2,'icon icon-check-2','Support 24/7','0.1s',2),(3,'icon icon-money-bag','Payment','0.2s',3),(4,'icon icon-shield','Reliable','0.3s',4),(5,'icon icon-accept','Guarantee','0.4s',5);
/*!40000 ALTER TABLE `features` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int unsigned DEFAULT NULL COMMENT 'NULL = main category',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Used in shop links: shop.php?cat=gaming-laptops',
  `position` smallint unsigned NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_product_categories_parent` (`parent_id`,`position`),
  CONSTRAINT `fk_product_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` (`id`, `parent_id`, `name`, `slug`, `position`, `is_active`) VALUES (1,NULL,'Laptops','laptops',1,1),(2,NULL,'Monitors','monitors',2,1),(3,NULL,'Audio','audio',3,1),(4,NULL,'Smartphones','smartphones',4,1),(5,NULL,'Printers','printers',5,1),(6,NULL,'Accessories','accessories',6,1),(7,1,'Brand New Laptops','brand-new-laptops',1,1),(8,1,'Gaming Laptops','gaming-laptops',2,1),(9,1,'MacBooks','macbooks',3,1),(10,1,'Ex-UK Laptops','ex-uk-laptops',4,1),(11,1,'Business Laptops','business-laptops',5,1),(12,1,'2-in-1 Convertible Laptops','2-in-1-convertible-laptops',6,1),(13,1,'Student Laptops','student-laptops',7,1),(14,1,'Workstation Laptops','workstation-laptops',8,1),(15,2,'Brand New Monitors','brand-new-monitors',1,1),(16,2,'Gaming Monitors','gaming-monitors',2,1),(17,2,'Ex-UK Monitors','ex-uk-monitors',3,1),(18,2,'Curved Monitors','curved-monitors',4,1),(19,2,'4K UHD Monitors','4k-uhd-monitors',5,1),(20,2,'Office Monitors','office-monitors',6,1),(21,2,'Ultrawide Monitors','ultrawide-monitors',7,1),(22,2,'Portable Monitors','portable-monitors',8,1),(23,3,'Bluetooth Speakers','bluetooth-speakers',1,1),(24,3,'Soundbars','soundbars',2,1),(25,3,'Wireless Earbuds','wireless-earbuds',3,1),(26,3,'Wired Earphones','wired-earphones',4,1),(27,3,'Over-Ear Headphones','over-ear-headphones',5,1),(28,3,'Gaming Headsets','gaming-headsets',6,1),(29,3,'Smart Speakers','smart-speakers',7,1),(30,3,'Studio Microphones','studio-microphones',8,1),(31,4,'iPhones','iphones',1,1),(32,4,'Samsung Galaxy','samsung-galaxy',2,1),(33,4,'Android Phones','android-phones',3,1),(34,4,'Brand New Phones','brand-new-phones',4,1),(35,4,'Ex-UK Phones','ex-uk-phones',5,1),(36,4,'Feature Phones','feature-phones',6,1),(37,4,'Tablets','tablets',7,1),(38,4,'Smartwatches','smartwatches',8,1),(39,5,'Inkjet Printers','inkjet-printers',1,1),(40,5,'Laser Printers','laser-printers',2,1),(41,5,'All-in-One Printers','all-in-one-printers',3,1),(42,5,'Wireless Printers','wireless-printers',4,1),(43,5,'Receipt Printers','receipt-printers',5,1),(44,5,'Barcode Printers','barcode-printers',6,1),(45,5,'Photo Printers','photo-printers',7,1),(46,5,'Scanner Machines','scanner-machines',8,1),(47,6,'Laptop Bags','laptop-bags',1,1),(48,6,'Keyboards','keyboards',2,1),(49,6,'Wireless Mice','wireless-mice',3,1),(50,6,'USB Flash Drives','usb-flash-drives',4,1),(51,6,'External Hard Drives','external-hard-drives',5,1),(52,6,'HDMI Cables','hdmi-cables',6,1),(53,6,'Power Banks','power-banks',7,1),(54,6,'WiFi Routers','wifi-routers',8,1);
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` smallint unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_product_images_product` (`product_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` (`id`, `product_id`, `image`, `position`) VALUES (1,367,'images/product/product-thumb/product-6.jpg',1),(2,367,'images/product/product-thumb/thumb-20.jpg',2),(3,367,'images/product/product-thumb/thumb-21.jpg',3),(4,368,'images/uploads/20261104-893fdf0b19ef.jpg',1),(5,368,'images/uploads/20261104-a0970a00beb4.jpg',2),(6,369,'images/uploads/20261105-6610a75338a8.png',1),(7,369,'images/uploads/20261105-1cb7e34d81cd.jpg',2);
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variants` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `options` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON object: option name => value',
  `price` decimal(12,3) NOT NULL,
  `old_price` decimal(12,3) DEFAULT NULL,
  `sku` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock` int unsigned DEFAULT NULL COMMENT 'NULL = not tracked',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `position` smallint unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_variant_product` (`product_id`,`position`),
  CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
INSERT INTO `product_variants` (`id`, `product_id`, `options`, `price`, `old_price`, `sku`, `stock`, `is_default`, `position`) VALUES (1,369,'{\"size\":\"22\",\"color\":\"black\"}',24000.000,25000.000,NULL,NULL,1,1),(2,369,'{\"size\":\"27\",\"color\":\"black\"}',27000.000,28000.000,NULL,NULL,0,2);
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Quick View "About this item": one bullet per line',
  `specs` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON list of [label, value] pairs',
  `option_names` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'JSON list of variant option names, e.g. ["RAM","Storage"]',
  `price` decimal(12,3) NOT NULL,
  `old_price` decimal(12,3) DEFAULT NULL,
  `img_src` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `img_hover` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `img_width` smallint unsigned DEFAULT NULL,
  `img_height` smallint unsigned DEFAULT NULL,
  `hover_width` smallint unsigned DEFAULT NULL,
  `hover_height` smallint unsigned DEFAULT NULL,
  `sale_label` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sale badge text, e.g. 22 or 28%',
  `available` int unsigned DEFAULT NULL,
  `sold` int unsigned DEFAULT NULL,
  `progress_width` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sold progress bar width, e.g. 40%',
  `countdown_timer` int unsigned DEFAULT NULL,
  `discount` decimal(12,3) DEFAULT NULL COMMENT 'Save badge amount',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_products_category` (`category_id`),
  KEY `idx_products_active_id` (`is_active`,`id`),
  KEY `idx_products_active_category_id` (`is_active`,`category_id`,`id`),
  KEY `idx_products_active_price_id` (`is_active`,`price`,`id`),
  KEY `idx_products_active_brand` (`is_active`,`brand`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=370 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` (`id`, `title`, `sku`, `category`, `category_id`, `brand`, `description`, `specs`, `option_names`, `price`, `old_price`, `img_src`, `img_hover`, `img_width`, `img_height`, `hover_width`, `hover_height`, `sale_label`, `available`, `sold`, `progress_width`, `countdown_timer`, `discount`, `is_active`, `created_at`, `updated_at`) VALUES (1,'30\" Antique Bronze Baldaud Roman Numeral Wall Clock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,4838.000,6321.000,'images/product/product-thumb/product-1.jpg',NULL,600,520,NULL,NULL,'27%',72,57,'80%',102738,NULL,1,'2026-11-04 09:32:55','2026-11-05 18:22:12'),(24,'Retroflag Case for Raspberry Pi 3 B+ – Classic Design with Game Controller Compatibility',NULL,'Game Controller',NULL,NULL,NULL,NULL,NULL,9288.000,11610.000,'images/product/product-66.jpg','images/product/product-45.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 18:13:48'),(25,'iPhone 11 (64GB) VN/A – Powerful Performance, Dual Camera, Liquid Retina Display',NULL,'iPhones',31,NULL,NULL,NULL,NULL,7895.000,9869.000,'images/product/product-67.jpg','images/product/product-25.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(26,'Apple AirPods – True Wireless Earbuds with Seamless Connectivity',NULL,'Wireless Earbuds',25,NULL,NULL,NULL,NULL,9803.000,12254.000,'images/product/product-68.jpg','images/product/product-54.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(27,'Leica Summaron-M 28mm f/5.6 – Classic Prime Lens Boxed Edition',NULL,'Camera & Accessories',NULL,NULL,NULL,NULL,NULL,8540.000,10675.000,'images/product/product-69.jpg','images/product/product-69.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 18:13:48'),(28,'Beats Studio3 Wireless – Noise Cancelling Headphones with Apple W1 Chip & Class 1 Bluetooth',NULL,'Over-Ear Headphones',27,NULL,NULL,NULL,NULL,10307.000,12887.000,'images/product/product-75.jpg','images/product/product-65.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(29,'Fitbit Charge 2 – Lavender Rose Gold Smart Fitness Tracker (Size S/L)',NULL,'Smartwatches',38,NULL,NULL,NULL,NULL,4967.000,5933.000,'images/product/product-70.jpg','images/product/product-28.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(30,'Urbanears Pampas – Wireless Over-Ear Headphones with Immersive Sound (Almond Beige)',NULL,'Over-Ear Headphones',27,NULL,NULL,NULL,NULL,6320.000,NULL,'images/product/product-134.jpg','images/product/product-detail-12.jpg',500,500,652,652,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(31,'Upgrader Headphones – Altec Lansing by ECCO Design, Premium Sound & Comfort',NULL,'Over-Ear Headphones',27,NULL,NULL,NULL,NULL,3548.000,NULL,'images/product/product-2.jpg','images/product/product-detail-9.jpg',500,500,652,652,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(32,'Apple Watch Series 6 (GPS) – 40mm Aluminum Case with Sport Band, Official Warranty',NULL,'Smartwatches',38,NULL,NULL,NULL,NULL,8256.000,NULL,'images/product/product-3.jpg','images/product/product-10.jpg',500,500,500,500,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(33,'Lenovo Yoga 910-13IKB – 2-in-1 Ultrabook with Touchscreen & 360° Hinge',NULL,'2-in-1 Convertible Laptops',12,NULL,NULL,NULL,NULL,5159.000,NULL,'images/product/product-4.jpg','images/product/product-19.jpg',500,500,500,500,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(34,'JBL LIVE200BT – Wireless Neckband Earphones with Premium Sound & Comfort',NULL,'Wireless Earbuds',25,NULL,NULL,NULL,NULL,1935.000,NULL,'images/product/product-5.jpg','images/product/product-153.jpg',500,500,500,500,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(35,'SteelSeries Aerox 9 Wireless – Ultra-Lightweight Gaming Mouse with 12 Programmable Buttons',NULL,'Wireless Mice',49,NULL,NULL,NULL,NULL,11288.000,NULL,'images/product/product-6.jpg','images/product/product-43.jpg',500,500,500,500,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(36,'3.5mm Stereo Earphones – HiFi Bass Headphones with Microphone for Music & Calls',NULL,'Wired Earphones',26,NULL,NULL,NULL,NULL,9623.000,NULL,'images/product/product-7.jpg','images/product/product-7.jpg',500,500,500,500,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(39,'Sony PlayStation 5 (PS5) – Next-Gen Gaming Console with Ultra-Fast SSD & 4K Graphics',NULL,'Game Consoles',NULL,NULL,NULL,NULL,NULL,9224.000,11609.000,'images/product/product-81.jpg','images/product/product-21.jpg',500,500,NULL,NULL,'28%',58,21,'30%',NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:17:27'),(40,'TCL 32-inch 3-Series 720p Roku Smart TV - 32S335, 2021 Model',NULL,'Smart TVs',NULL,NULL,NULL,NULL,NULL,8136.000,11965.000,'images/product/product-detail-14.jpg','images/product/product-detail-16.jpg',652,652,NULL,NULL,'32%',59,41,'41%',NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 18:13:48'),(41,'Logitech M510 Wireless Computer Mouse for PC with USB Unifying...',NULL,'Wireless Mice',49,NULL,NULL,NULL,NULL,7980.000,9740.000,'images/product/product-81.jpg','images/product/product-11.jpg',500,500,NULL,NULL,'18%',78,22,'22%',NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(42,'SAMSUNG Galaxy Z Flip Factory Unlocked Cell Phone',NULL,'Samsung Galaxy',32,NULL,NULL,NULL,NULL,9675.000,12900.000,'images/product/product-39.jpg','images/product/product-56.jpg',500,500,NULL,NULL,'25%',30,70,'70%',NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(43,'Samsung Electronics Samsung Galaxy S21 5G Factory Unlocked Android...',NULL,'Samsung Galaxy',32,NULL,NULL,NULL,NULL,8991.000,10965.000,'images/product/product-40.jpg','images/product/product-detail-6.jpg',500,500,652,652,NULL,45,62,'62%',NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(44,'Lammcou Headphone Holder for PS5 Mini Hanger...',NULL,'Game Consoles',NULL,NULL,NULL,NULL,NULL,8101.000,10127.000,'images/product/product-81.jpg','images/product/product-21.jpg',500,500,NULL,NULL,'20%',45,70,'80%',NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 18:13:48'),(70,'Samsung Galaxy Note 10+, 256GB, Aura Black - Fully Unlocked',NULL,'Samsung Galaxy',32,NULL,NULL,NULL,NULL,8088.000,10217.000,'images/product/product-56.jpg','images/product/product-detail-6.jpg',500,500,NULL,NULL,'22',5,14,'40%',102738,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(71,'PowerA Enhanced Wired Controller for Xbox Series X|S - white',NULL,'Game Controllers',NULL,NULL,NULL,NULL,NULL,7301.000,9127.000,'images/product/product-57.jpg','images/product/product-81.jpg',500,500,NULL,NULL,'10',4,15,'80%',102738,NULL,1,'2026-11-04 09:32:55','2026-11-05 18:13:48'),(72,'Samsung Galaxy Note 10+, 256GB, Aura Black - Fully Unlocked',NULL,'Samsung Galaxy',32,NULL,NULL,NULL,NULL,10320.000,12900.000,'images/product/product-56.jpg','images/product/product-detail-6.jpg',500,500,NULL,NULL,'70',5,14,'80%',102738,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(73,'2021 Apple 10.2-inch iPad (Wi-Fi, 64GB) - Silver',NULL,'Tablets',37,NULL,NULL,NULL,NULL,6398.000,7843.000,'images/product/product-58.jpg','images/product/product-74.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(74,'Apple Watch Band - Sport Band (45mm) Starlight - Regular',NULL,'Smartwatches',38,NULL,NULL,NULL,NULL,4618.000,5818.000,'images/product/product-59.jpg','images/product/product-72.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(75,'Tech21 Essentials for iPhone 12/12 Pro – Clear Protective Case with 5ft Drop Protection',NULL,'Accessories',6,NULL,NULL,NULL,NULL,9804.000,12294.000,'images/product/product-60.jpg','images/product/product-25.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(76,'Apple MacBook Pro (13-Inch) – 8GB RAM, 512GB Storage, Silver Finish',NULL,'MacBooks',9,NULL,NULL,NULL,NULL,1974.000,2477.000,'images/product/product-61.jpg','images/product/product-71.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(77,'Playstation DualSense Wireless Controller',NULL,'Game Controller',NULL,NULL,NULL,NULL,NULL,7418.000,9262.000,'images/product/product-23.jpg','images/product/product-46.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 18:13:48'),(78,'2021 Apple 10.2-inch iPad (Wi-Fi, 64GB) - Silver',NULL,'Tablets',37,NULL,NULL,NULL,NULL,7108.000,8824.000,'images/product/product-58.jpg','images/product/product-74.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(82,'AeroCool Cylon RGB Mid Tower with Acrylic Side window, Black',NULL,'Accessories',6,NULL,NULL,NULL,NULL,7108.000,8824.000,'images/product/product-62.jpg','images/product/product-17.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(83,'PowerA Enhanced Wired Controller for Xbox Series X|S - white',NULL,'Game Controllers',NULL,NULL,NULL,NULL,NULL,4102.000,5128.000,'images/product/product-66.jpg','images/product/product-66.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 18:13:48'),(84,'Corsair 5000D Airflow – Tempered Glass Mid-Tower ATX PC Case, Black with Enhanced Cooling Performance',NULL,'Accessories',6,NULL,NULL,NULL,NULL,8617.000,10784.000,'images/product/product-63.jpg','images/product/product-63.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(85,'Corsair iCUE 4000X RGB – Mid-Tower ATX PC Case with Tempered Glass and RGB Lighting, White',NULL,'Accessories',6,NULL,NULL,NULL,NULL,2219.000,2799.000,'images/product/product-64.jpg','images/product/product-64.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(86,'PlayStation DualSense Wireless Controller – Next-Gen Controller with Haptic Feedback and Adaptive Triggers',NULL,'Game Controllers',NULL,NULL,NULL,NULL,NULL,8153.000,10178.000,'images/product/product-65.jpg','images/product/sc-2.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 18:13:48'),(87,'2021 Apple 10.2-inch iPad (Wi-Fi, 64GB) - Silver',NULL,'Tablets',37,NULL,NULL,NULL,NULL,6527.000,8179.000,'images/product/product-58.jpg','images/product/product-74.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(88,'Samsung Galaxy S10+, 128GB, Ceramic Black - Unlocked',NULL,'Samsung Galaxy',32,NULL,NULL,NULL,NULL,4154.000,5199.000,'images/product/product-82.jpg','images/product/product-56.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(89,'PowerA Enhanced Wired Controller for Xbox Series X|S – White with Precision Controls and Comfortable Grip',NULL,'Game Controllers',NULL,NULL,NULL,NULL,NULL,1896.000,2387.000,'images/product/product-57.jpg','images/product/product-81.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 18:13:48'),(90,'Baseus D02 Pro Bluetooth Earphones – Stereo Wireless 5.0, HIFI Sound, Foldable Design for Sports',NULL,'Wireless Earbuds',25,NULL,NULL,NULL,NULL,5766.000,7211.000,'images/product/product-5.jpg','images/product/product-7.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(91,'TCL 32-Inch 3-Series 720p Roku Smart TV – Model 32S335, 2021 Edition with Built-in Streaming Features',NULL,'Smart TVs',NULL,NULL,NULL,NULL,NULL,8811.000,11017.000,'images/product/product-12.jpg','images/product/product-detail-15.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 18:13:48'),(92,'Audio-Technica ATH-AD700X Audiophile Open-Air',NULL,'Over-Ear Headphones',27,NULL,NULL,NULL,NULL,4360.000,5495.000,'images/product/product-20.jpg','images/product/product-37.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(93,'Destiny Special Edition Game Console – Limited Edition with Exclusive Features',NULL,'Game Consoles',NULL,NULL,NULL,NULL,NULL,7946.000,9959.000,'images/product/product-17.jpg','images/product/product-103.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 18:13:48'),(94,'Corsair 5000D Airflow – Tempered Glass Mid-Tower ATX Case with Enhanced Cooling Performance',NULL,'Accessories',6,NULL,NULL,NULL,NULL,2129.000,2696.000,'images/product/product-63.jpg','images/product/product-62.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(95,'Apple iPhone 12 Pro Max, 128GB, Graphite - Unlocked',NULL,'iPhones',31,NULL,NULL,NULL,NULL,7147.000,8966.000,'images/product/product-169.jpg','images/product/product-55.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(96,'AeroCool Cylon RGB Mid Tower – Acrylic Side Window, Black with LED Lighting for Enhanced Cooling',NULL,'Accessories',6,NULL,NULL,NULL,NULL,8295.000,10385.000,'images/product/product-64.jpg','images/product/product-90.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(97,'Apple iPhone 11 Pro Max, 256GB, Space Gray',NULL,'iPhones',31,NULL,NULL,NULL,NULL,3896.000,4876.000,'images/product/product-165.jpg','images/product/product-106.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(98,'Audio-Technica ATH-AD700X – Audiophile Open-Air Headphones with Superior Sound Quality and Comfort',NULL,'Over-Ear Headphones',27,NULL,NULL,NULL,NULL,9391.000,11739.000,'images/product/product-78.jpg','images/product/product-9.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(99,'Destiny Special Edition Game Console – Limited Edition Console with Exclusive Features and Design',NULL,'Game Consoles',NULL,NULL,NULL,NULL,NULL,1883.000,2361.000,'images/product/product-167.jpg','images/product/product-167.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 18:13:48'),(100,'Baseus D02 Pro Bluetooth Earphones – Stereo Wireless 5.0, HIFI Sound, Foldable Design for Sports and Active Use',NULL,'Wireless Earbuds',25,NULL,NULL,NULL,NULL,6695.000,8372.000,'images/product/product-168.jpg','images/product/product-83.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(101,'Lifely Laptop Stand – Adjustable Ergonomic Stand, Ships from Woodlands, Real Wood Design for Comfortable Viewing',NULL,'Accessories',6,NULL,NULL,NULL,NULL,8759.000,10926.000,'images/product/product-166.jpg','images/product/product-142.jpg',500,500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 22:12:27'),(367,'Drone with Camera 1080P HD, Toss to Launch RC Drone for Adults with Smart APP Trajectory Flight Altitude Hold One Key Take Off/Landing Headless 360°Flip Camera Drone 2 Batteries',NULL,NULL,NULL,NULL,NULL,NULL,NULL,9365.000,11713.000,'images/product/product-thumb/product-6.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-11-04 09:32:55','2026-11-05 18:13:48'),(368,'Galaxy Buds Pro Test',NULL,'Wireless Earbuds',25,NULL,NULL,NULL,NULL,16706.000,20511.000,'images/uploads/20261104-b4ec5c38934d.jpg','images/uploads/20261104-ec7045aeae66.jpg',274,274,274,274,NULL,NULL,NULL,'35%',NULL,NULL,1,'2026-11-04 09:44:00','2026-11-05 22:12:27'),(369,'Samsung Essential Monitor','Samsung Essential Monitor','Brand New Monitors',15,'samsung','Samsung 27-inch Essential Monitor S30GD Monitor – 27-inch FHD Display\r\n\r\nUpgrade your workspace with the Samsung 27-inch Essential Monitor S30GD Monitor, a reliable Samsung monitor designed for office work, business productivity, studying, browsing, entertainment and multi-screen workstation setups. It combines Samsung display quality, a clean modern design and practical performance for users who want a dependable screen for work, study, gaming, entertainment or professional use.\r\n\r\nDisplay & Visual Quality\r\n\r\nThe 27-inch screen gives you a comfortable viewing area for everyday tasks and extended use. The FHD display provides clear visuals for documents, websites, video calls, streaming, dashboards, CCTV viewing and productivity applications.\r\n\r\nFull HD / QHD Visual Clarity\r\n\r\nThe FHD resolution gives users a clear and comfortable display experience for daily work, browsing, studying, presentations and entertainment.\r\n\r\nDesign & Workspace Setup\r\n\r\nFinished in Black, this monitor has a modern appearance that fits well in office desks, home workstations, gaming setups, reception areas, study rooms and business environments. It can be used as a primary display or as a second screen to improve multitasking and workflow efficiency.\r\n\r\nProductivity Benefits\r\n\r\nA dedicated monitor improves comfort, visibility and efficiency compared to working on a small laptop screen. It is useful for handling spreadsheets, emails, websites, coding, design work, online meetings and entertainment with better screen space.\r\n\r\nKey Features\r\n\r\n• Samsung S30GD series\r\n\r\n• Model Number: LS27D300GAMXUE\r\n\r\n• 27-inch display\r\n\r\n• FHD resolution\r\n\r\n• Essential Monitor\r\n\r\n• Black finish\r\n\r\n• Suitable for office, home, gaming and productivity use\r\n\r\n• Availability: Yes\r\n\r\nIdeal For\r\n\r\n• Office productivity\r\n\r\n• Home workstation setups\r\n\r\n• Students and online learning\r\n\r\n• Business users\r\n\r\n• CCTV and monitoring setups\r\n\r\n• Streaming and entertainment\r\n\r\n• General display and multi-screen productivity\r\n\r\nWhy Buy This Monitor?\r\n\r\nThe Samsung 27-inch Essential Monitor S30GD Monitor is a practical choice for users looking for a dependable Samsung display with strong visual quality, modern styling and flexible use across home, office, business and entertainment environments.','[[\"color\",\"black\"],[\"condition\",\"brandnew\"]]','[\"size\",\"color\"]',24000.000,25000.000,'images/uploads/20261105-a216d6d5e873.png',NULL,1536,1024,NULL,NULL,NULL,50,400,NULL,1,NULL,1,'2026-11-05 20:07:38','2026-11-05 22:12:27');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `promotion_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_products` (
  `promotion_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  PRIMARY KEY (`promotion_id`,`product_id`),
  KEY `fk_promo_products_product` (`product_id`),
  CONSTRAINT `fk_promo_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_promo_products_promo` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `promotion_products` WRITE;
/*!40000 ALTER TABLE `promotion_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `promotion_products` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `badge` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Short label shown in Quick View, e.g. Black Friday',
  `discount_type` enum('percent','fixed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percent',
  `discount_value` decimal(12,3) NOT NULL,
  `applies_to` enum('all','category','products') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `promotions` WRITE;
/*!40000 ALTER TABLE `promotions` DISABLE KEYS */;
/*!40000 ALTER TABLE `promotions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `section_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `section_products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `section` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` smallint unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `animation` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'WOW.js animation class, e.g. fadeInUp',
  `wow_delay` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_section_position` (`section`,`position`),
  KEY `fk_section_products_product` (`product_id`),
  CONSTRAINT `fk_section_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_section_products_section` FOREIGN KEY (`section`) REFERENCES `sections` (`code`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `section_products` WRITE;
/*!40000 ALTER TABLE `section_products` DISABLE KEYS */;
INSERT INTO `section_products` (`id`, `section`, `position`, `product_id`, `animation`, `wow_delay`) VALUES (1,'deal_of_day',1,70,NULL,NULL),(2,'deal_of_day',2,71,NULL,NULL),(3,'deal_of_day',3,72,NULL,NULL),(10,'best_sellers',1,82,'fadeInLeft','0s'),(11,'best_sellers',2,83,'fadeInLeft','0.1s'),(12,'best_sellers',3,84,'fadeInLeft','0.2s'),(13,'best_sellers',4,85,'fadeInLeft','0.3s'),(14,'best_sellers',5,86,'fadeInLeft','0.4s'),(15,'best_sellers',6,87,NULL,NULL),(20,'hot_featured',1,367,NULL,NULL),(21,'smart_home',1,92,'fadeInUp','0s'),(22,'smart_home',2,93,'fadeInUp','0.1s'),(23,'smart_home',3,94,'fadeInUp','0.2s'),(24,'smart_home',4,95,'fadeInUp','0.3s'),(25,'smart_home',5,96,'fadeInUp','0s'),(26,'smart_home',6,97,'fadeInUp','0.1s'),(27,'smart_home',7,98,'fadeInUp','0.2s'),(28,'smart_home',8,99,'fadeInUp','0.3s'),(29,'smart_home',9,100,NULL,NULL),(30,'smart_home',10,101,NULL,NULL),(31,'top_20',1,24,NULL,'0s'),(32,'top_20',2,25,NULL,'0s'),(33,'top_20',3,26,NULL,'0s'),(34,'top_20',4,27,NULL,'0s'),(35,'top_20',5,28,NULL,'0s'),(36,'top_20',6,29,NULL,'0s'),(37,'featured_products',1,27,NULL,'0s'),(38,'featured_products',2,28,NULL,'0s'),(39,'featured_products',3,29,NULL,'0s'),(40,'featured_products',4,24,NULL,'0s'),(41,'featured_products',5,25,NULL,'0s'),(42,'featured_products',6,26,NULL,'0s'),(43,'top_selling',1,24,NULL,'0s'),(44,'top_selling',2,25,NULL,'0s'),(45,'top_selling',3,26,NULL,'0s'),(46,'top_selling',4,27,NULL,'0s'),(47,'top_selling',5,28,NULL,'0s'),(48,'top_selling',6,29,NULL,'0s'),(49,'on_sale',1,27,NULL,'0s'),(50,'on_sale',2,28,NULL,'0s'),(51,'on_sale',3,29,NULL,'0s'),(52,'on_sale',4,24,NULL,'0s'),(53,'on_sale',5,25,NULL,'0s'),(54,'on_sale',6,26,NULL,'0s'),(55,'recently_viewed',1,30,'fadeInLeft','0s'),(56,'recently_viewed',2,31,'fadeInLeft','0.1s'),(57,'recently_viewed',3,32,'fadeInLeft','0.2s'),(58,'recently_viewed',4,33,'fadeInLeft','0.3s'),(59,'recently_viewed',5,34,'fadeInLeft','0.4s'),(60,'recently_viewed',6,35,NULL,NULL),(61,'recently_viewed',7,36,NULL,NULL),(62,'search_featured',1,39,'fadeInLeft','0s'),(63,'search_featured',2,40,'fadeInLeft','0.1s'),(64,'search_featured',3,41,'fadeInLeft','0.2s'),(65,'search_featured',4,42,'fadeInLeft','0.3s'),(66,'search_featured',5,43,'fadeInLeft','0.4s'),(67,'search_featured',6,44,NULL,NULL),(68,'trending',1,73,'fadeInLeft','0s'),(69,'trending',2,72,'fadeInLeft','0.1s'),(70,'trending',3,71,'fadeInLeft','0.2s'),(71,'trending',4,92,'fadeInUp','0.3s'),(72,'hot_grid',1,24,NULL,NULL),(73,'hot_grid',2,25,NULL,NULL),(74,'hot_grid',3,26,NULL,NULL),(75,'hot_grid',4,27,NULL,NULL),(76,'trending',5,368,'fadeInUp','0.3s'),(77,'smart_home',11,40,NULL,NULL),(78,'smart_home',12,32,NULL,NULL),(79,'smart_home',13,39,NULL,NULL),(80,'smart_home',14,28,NULL,NULL),(81,'smart_home',15,26,NULL,NULL),(82,'smart_home',16,29,NULL,NULL),(83,'smart_home',17,367,NULL,NULL),(84,'smart_home',18,1,NULL,NULL),(85,'smart_home',19,369,NULL,NULL),(86,'smart_home',20,91,NULL,NULL),(87,'smart_home',21,77,NULL,NULL),(88,'smart_home',22,33,NULL,NULL),(89,'smart_home',23,76,NULL,NULL),(90,'smart_home',24,42,NULL,NULL),(91,'smart_home',25,43,NULL,NULL),(92,'smart_home',26,78,NULL,NULL),(93,'smart_home',27,34,NULL,NULL),(94,'smart_home',28,30,NULL,NULL),(95,'smart_home',29,41,NULL,NULL),(96,'smart_home',30,35,NULL,NULL),(97,'smart_home',31,27,NULL,NULL),(98,'smart_home',32,25,NULL,NULL);
/*!40000 ALTER TABLE `section_products` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sections` (
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `max_products` smallint unsigned DEFAULT NULL COMMENT 'NULL = unlimited, 0 = section has no product list',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Help text shown in the admin',
  `position` smallint unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sections` WRITE;
/*!40000 ALTER TABLE `sections` DISABLE KEYS */;
INSERT INTO `sections` (`code`, `title`, `is_active`, `max_products`, `note`, `position`) VALUES ('best_sellers','Best Sellers',0,NULL,'Product slider.',4),('brands','Authorised Brands',0,0,'Logo carousel near the bottom of the home page. Manage the logos in Brands.',14),('categories','Product Category',1,0,'Category slider. Manage the items under Categories.',2),('deal_of_day','Deal Of The Day',1,NULL,'Wide cards with sale %, stock bar and the deal countdown. Uses the product\'s sale %, available, sold and progress fields.',1),('featured_products','Featured Products',1,NULL,'Column in the four-column list block. Shown 3 products per slide.',9),('hot_featured','Hot Product – highlighted card',0,1,'The large card in the middle of the Hot Product grid. Shows the product\'s gallery images and \"Save\" amount.',6),('hot_grid','Hot Product',0,4,'Grid of 4 horizontal cards around the highlighted card. Hiding this hides the whole Hot Product block.',5),('on_sale','On-sale Product',1,NULL,'Column in the four-column list block. Shown 3 products per slide.',11),('recently_viewed','Trending Products',1,NULL,'Product slider near the bottom of the home page, styled like Trending Now.',12),('search_featured','Featured Product',1,NULL,'Slider inside the search popup. Uses sale %, sold, available and progress fields.',13),('smart_home','Featured Products',1,32,'Featured products grid next to its banner: 2 rows of 4 beside it, then 4 rows of 6 below (32 on computers; 12 on tablets, 8 on phones).',7),('store_map','Visit Our Shop',1,0,'Google map to the shop with address, hours and directions. The map location is set in Settings.',15),('top_20','Top 20',1,NULL,'Column in the four-column list block. Shown 3 products per slide.',8),('top_selling','Top Selling Product',1,NULL,'Column in the four-column list block. Shown 3 products per slide.',10),('trending','Trending Now',1,NULL,'Product slider.',3);
/*!40000 ALTER TABLE `sections` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` (`key`, `value`) VALUES ('currency','KES'),('deal_ends_at','2025-07-01 23:59:59'),('delivery_fee','0'),('footer_about','Dealers in IT products, Electronics, Accessories, Phones, Homewear, Servers, Networking Accessories, etc.'),('free_delivery_over','32250'),('hero_bg_image','images/banner/banner-8.jpg'),('map_query','Laxmi Plaza, Nairobi'),('order_notify_email',NULL),('quickview_default_product','1'),('store_address','Nairobi, Laxmi Plaza, 3rd Flr, Room No 5'),('store_city','Nairobi, Kenya'),('store_email','ictgadgetsshop@gmail.com'),('store_hours','Open Mon-Sat 8am-6pm'),('store_name','Shop ICT Gadgets'),('store_phone','+254713869018'),('whatsapp_number',NULL);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `is_admin`, `created_at`) VALUES (2,'Administrator','admin@ict.com',NULL,'$2y$10$7NVjrzhY7yIAZBYYTtjEPuPHELNVBNNSbVs8AB4WB1A/jDj.89T/e',1,'2026-11-04 09:32:56');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `visitor_id` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL DEFAULT '0',
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_visitor_item` (`visitor_id`,`product_id`,`variant_id`),
  KEY `fk_cart_items_product` (`product_id`),
  CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wishlist_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlist_items` (
  `visitor_id` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` int unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`visitor_id`,`product_id`),
  KEY `fk_wishlist_items_product` (`product_id`),
  CONSTRAINT `fk_wishlist_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. ONS-1001, set right after insert',
  `access_token` char(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Lets the customer open their confirmation page',
  `channel` enum('web','whatsapp') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'web',
  `status` enum('new','confirmed','processing','shipped','delivered','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `customer_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Kenya',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Delivery instructions from the customer',
  `delivery_method` enum('pickup','delivery') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'delivery',
  `payment_method` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pay_on_delivery',
  `subtotal` decimal(12,2) NOT NULL,
  `delivery_fee` decimal(12,2) NOT NULL DEFAULT '0.00',
  `promo_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promotion_id` int unsigned DEFAULT NULL,
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL,
  `admin_note` text COLLATE utf8mb4_unicode_ci COMMENT 'Internal note, not shown to the customer',
  `visitor_id` char(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_orders_status` (`status`,`created_at`),
  KEY `idx_orders_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `product_id` int unsigned DEFAULT NULL,
  `variant_id` int unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `variant_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `quantity` int unsigned NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  `is_preorder` tinyint(1) NOT NULL DEFAULT '0',
  `promotion_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `fk_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analytics_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '/',
  `product_id` int unsigned DEFAULT NULL,
  `variant_id` int unsigned DEFAULT NULL,
  `visitor_id` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referrer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Referring host, other sites only',
  `device` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'desktop',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_events_type_time` (`type`,`created_at`),
  KEY `idx_events_product` (`product_id`,`type`),
  KEY `idx_events_time` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=335 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- Complete the schema expected by the current storefront after importing
-- onsus_shop.sql. Safe to run more than once; does not drop or delete data.
SET NAMES utf8mb4;

-- Home category tiles link to product_categories.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'categories' AND column_name = 'category_id');
SET @sql := IF(@has_col = 0, 'ALTER TABLE categories ADD COLUMN category_id INT UNSIGNED NULL COMMENT ''Product category the tile links to'' AFTER img_src, ADD CONSTRAINT fk_categories_product_category FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Multiple subcategory assignments.
CREATE TABLE IF NOT EXISTS product_category_links (
  product_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (product_id, category_id),
  KEY idx_pcl_category (category_id),
  CONSTRAINT fk_pcl_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_pcl_category FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO product_category_links (product_id, category_id)
SELECT id, category_id FROM products WHERE category_id IS NOT NULL;

-- Buying guides and their current SEO fields.
CREATE TABLE IF NOT EXISTS posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(160) NOT NULL,
  excerpt VARCHAR(320) NULL,
  body MEDIUMTEXT NULL,
  image VARCHAR(255) NULL,
  category_id INT UNSIGNED NULL,
  product_ids VARCHAR(255) NULL,
  seo_title VARCHAR(120) NULL,
  meta_description VARCHAR(320) NULL,
  focus_keyword VARCHAR(120) NULL,
  secondary_keywords VARCHAR(600) NULL,
  author VARCHAR(80) NULL,
  status ENUM('draft', 'review', 'published') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY posts_slug (slug),
  KEY posts_status (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product SEO fields used by storefront and admin pages.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'seo_title');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN seo_title VARCHAR(120) NULL, ADD COLUMN meta_description VARCHAR(320) NULL, ADD COLUMN focus_keyword VARCHAR(120) NULL, ADD COLUMN secondary_keywords VARCHAR(600) NULL, ADD COLUMN faqs TEXT NULL, ADD COLUMN image_alts TEXT NULL, ADD COLUMN gtin VARCHAR(20) NULL, ADD COLUMN mpn VARCHAR(64) NULL, ADD COLUMN noindex TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Category and brand landing-page SEO fields.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_categories' AND column_name = 'seo_title');
SET @sql := IF(@has_col = 0, 'ALTER TABLE product_categories ADD COLUMN seo_title VARCHAR(120) NULL, ADD COLUMN meta_description VARCHAR(320) NULL, ADD COLUMN intro TEXT NULL, ADD COLUMN guide TEXT NULL, ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'brands' AND column_name = 'seo_title');
SET @sql := IF(@has_col = 0, 'ALTER TABLE brands ADD COLUMN seo_title VARCHAR(120) NULL, ADD COLUMN meta_description VARCHAR(320) NULL, ADD COLUMN intro TEXT NULL, ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('seo_google_verification', NULL), ('seo_bing_verification', NULL),
  ('seo_ga4_id', NULL), ('seo_ai_training', 'allow'), ('seo_return_days', '3');

-- Connect existing legacy home tiles to a sensible main product category.
UPDATE categories c
JOIN product_categories pc ON pc.slug = CASE
  WHEN LOWER(c.title) LIKE '%laptop%' OR LOWER(c.title) LIKE '%computer%' THEN 'laptops'
  WHEN LOWER(c.title) LIKE '%monitor%' OR LOWER(c.title) LIKE '%screen%' THEN 'monitors'
  WHEN LOWER(c.title) LIKE '%audio%' OR LOWER(c.title) LIKE '%speaker%' THEN 'audio'
  WHEN LOWER(c.title) LIKE '%phone%' OR LOWER(c.title) LIKE '%tablet%' OR LOWER(c.title) LIKE '%mobile%' THEN 'smartphones'
  WHEN LOWER(c.title) LIKE '%printer%' THEN 'printers'
  ELSE 'accessories'
END
SET c.category_id = pc.id
WHERE c.category_id IS NULL;

-- Make seeded banner calls to action open the catalog.
UPDATE banners SET link_url = 'shop.php' WHERE link_url = './';


-- Repair missing schema pieces independently after a partial/older migration.
-- Safe to run repeatedly; does not drop tables or product data.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(160) NOT NULL,
  excerpt VARCHAR(320) NULL,
  body MEDIUMTEXT NULL,
  image VARCHAR(255) NULL,
  category_id INT UNSIGNED NULL,
  product_ids VARCHAR(255) NULL,
  seo_title VARCHAR(120) NULL,
  meta_description VARCHAR(320) NULL,
  focus_keyword VARCHAR(120) NULL,
  secondary_keywords VARCHAR(600) NULL,
  author VARCHAR(80) NULL,
  status ENUM('draft', 'review', 'published') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY posts_slug (slug),
  KEY posts_status (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add each product SEO field separately so partially applied migrations recover cleanly.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'seo_title');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN seo_title VARCHAR(120) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'meta_description');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN meta_description VARCHAR(320) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'focus_keyword');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN focus_keyword VARCHAR(120) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'secondary_keywords');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN secondary_keywords VARCHAR(600) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'faqs');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN faqs TEXT NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'image_alts');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN image_alts TEXT NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'gtin');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN gtin VARCHAR(20) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'mpn');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN mpn VARCHAR(64) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'noindex');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN noindex TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Older posts tables may predate the editorial keyword migration.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'posts' AND column_name = 'focus_keyword');
SET @sql := IF(@has_col = 0, 'ALTER TABLE posts ADD COLUMN focus_keyword VARCHAR(120) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'posts' AND column_name = 'secondary_keywords');
SET @sql := IF(@has_col = 0, 'ALTER TABLE posts ADD COLUMN secondary_keywords VARCHAR(600) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ensure the SEO editor's fields exist even after a partially applied 018 migration.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_categories' AND column_name = 'seo_title');
SET @sql := IF(@has_col = 0, 'ALTER TABLE product_categories ADD COLUMN seo_title VARCHAR(120) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_categories' AND column_name = 'meta_description');
SET @sql := IF(@has_col = 0, 'ALTER TABLE product_categories ADD COLUMN meta_description VARCHAR(320) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_categories' AND column_name = 'intro');
SET @sql := IF(@has_col = 0, 'ALTER TABLE product_categories ADD COLUMN intro TEXT NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_categories' AND column_name = 'guide');
SET @sql := IF(@has_col = 0, 'ALTER TABLE product_categories ADD COLUMN guide TEXT NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_categories' AND column_name = 'updated_at');
SET @sql := IF(@has_col = 0, 'ALTER TABLE product_categories ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'brands' AND column_name = 'seo_title');
SET @sql := IF(@has_col = 0, 'ALTER TABLE brands ADD COLUMN seo_title VARCHAR(120) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'brands' AND column_name = 'meta_description');
SET @sql := IF(@has_col = 0, 'ALTER TABLE brands ADD COLUMN meta_description VARCHAR(320) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'brands' AND column_name = 'intro');
SET @sql := IF(@has_col = 0, 'ALTER TABLE brands ADD COLUMN intro TEXT NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'brands' AND column_name = 'updated_at');
SET @sql := IF(@has_col = 0, 'ALTER TABLE brands ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('seo_google_verification', NULL), ('seo_bing_verification', NULL),
  ('seo_ga4_id', NULL), ('seo_ai_training', 'allow'), ('seo_return_days', '3');
