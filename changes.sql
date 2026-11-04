-- Date:29-08-2026
--Changes:Added new columns to bookings table "Delivery address, phone number, id number and quantity" please do run this on phpmyadmin to update the bookings table.

ALTER TABLE `bookings` 
ADD COLUMN `quantity` INT(11) NOT NULL DEFAULT 1 AFTER `total_days`,
ADD COLUMN `phone_number` VARCHAR(15) DEFAULT NULL AFTER `renter_id`,
ADD COLUMN `id_number` VARCHAR(50) DEFAULT NULL AFTER `id_proof_doc`,
ADD COLUMN `delivery_address` TEXT NOT NULL AFTER `id_number`;

--Date:3-09-2026
--Changes:updated equipment table category_id for equipment_id 3 to 2 and added new equipment with id 2 and 3.
UPDATE equipment
SET category_id = 2
WHERE equipment_id = 3;
 
INSERT INTO equipment
(
    equipment_id,
    category_id,
    lender_id,
    title,
    category,
    brand_model,
    power_hp,
    drive_type,
    model_year,
    fuel_type,
    working_width,
    equipment_condition,
    price_per_day,
    min_booking_days,
    service_location,
    distance_km,
    description,
    image,
    badge,
    status,
    rating,
    rating_count,
    is_featured
)
VALUES
(
    2,
    1,
    7,
    'rere',
    'Tractor',
    're fr',
    6,
    '4WD',
    2026,
    'Diesel',
    '',
    'Good',
    3.00,
    144,
    'Bengaluru Rural',
    25.0,
    'sdf',
    'default.png',
    'NONE',
    'Available',
    0.0,
    0,
    1
),
(
    3,
    2,
    7,
    'trt',
    'Harvesting',
    'ttr yt',
    45,
    '4WD',
    2026,
    'Diesel',
    '',
    'Good',
    44.00,
    1,
    'Bengaluru Rural',
    25.0,
    'gtrt',
    'default.png',
    'NONE',
    'Available',
    0.0,
    0,
    1
);

--Date: 11-09-2026
--changes: Added wishlist table to store user wishlist items.
CREATE TABLE `wishlist` (
    `wishlist_id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `equipment_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`wishlist_id`),
    UNIQUE KEY `unique_wishlist` (`user_id`, `equipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--date: 11-09-2026
--changes: Added recommended_equipment table to store recommended equipment for users.
SELECT recommendation_id, equipment_id, title, category
FROM recommended_equipment
ORDER BY recommendation_id;

--date: 11-09-2026
--changes: Updated recommended equipment IDs.
UPDATE recommended_equipment
SET equipment_id = 5
WHERE recommendation_id = 1;

--date: 11-09-2026
--changes: Updated recommended equipment IDs.
UPDATE recommended_equipment
SET equipment_id = 4
WHERE recommendation_id = 2;

--date: 11-09-2026
--changes: Updated recommended equipment IDs.
ALTER TABLE notifications
MODIFY notification_id INT(11) NOT NULL AUTO_INCREMENT;

--date: 11-09-2026
--changes: Added new columns to bookings table for delivery and return confirmation.
ALTER TABLE bookings
ADD COLUMN delivery_confirmed TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN delivery_confirmed_at DATETIME NULL,
ADD COLUMN return_confirmed TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN return_confirmed_at DATETIME NULL;

--date: 11-09-2026
--changes: Added Completed as the final booking status.
ALTER TABLE bookings
MODIFY status ENUM(
    'Pending',
    'Accepted',
    'Rejected',
    'Delivered',
    'Returned',
    'Completed',
    'Overdue'
) DEFAULT 'Pending';


--date:17-09-2026
--changes: Create the reviews table for the rating and review system
CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `renter_id` int(11) NOT NULL,
  `lender_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `review_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `booking_id` (`booking_id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `renter_id` (`renter_id`),
  KEY `lender_id` (`lender_id`),
  CONSTRAINT `fk_reviews_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_renter` FOREIGN KEY (`renter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_lender` FOREIGN KEY (`lender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--date: 18-09-2026
-- changes: Ratings & Reviews migration for agri_rental_db
-- Run after selecting agri_rental_db in phpMyAdmin.

ALTER TABLE `reviews`
    ADD UNIQUE KEY `unique_review_booking` (`booking_id`),
    ADD KEY `idx_reviews_equipment` (`equipment_id`),
    ADD KEY `idx_reviews_renter` (`renter_id`),
    ADD KEY `idx_reviews_lender` (`lender_id`);



ALTER TABLE `users`
    ADD COLUMN `city` varchar(100) DEFAULT NULL AFTER `address`,
    ADD COLUMN `state` varchar(100) DEFAULT NULL AFTER `city`;

-- Populate city/state for existing users from the current address values.
UPDATE `users` SET `city` = 'Bengaluru', `state` = 'Karnataka'
WHERE LOWER(TRIM(`address`)) IN ('banglore', 'bengaluru', 'bangalore');

UPDATE `users` SET `city` = 'Mysuru', `state` = 'Karnataka'
WHERE LOWER(TRIM(`address`)) IN ('mysuru', 'mysore');

UPDATE `users` SET `city` = 'Hassan', `state` = 'Karnataka'
WHERE LOWER(TRIM(`address`)) LIKE 'hassan%';

ALTER TABLE `users`
    ADD COLUMN `district` varchar(100) DEFAULT NULL AFTER `city`;

UPDATE `users` SET `city` = 'Bengaluru', `district` = 'Bengaluru Urban', `state` = 'Karnataka'
WHERE LOWER(TRIM(`address`)) IN ('banglore', 'bengaluru', 'bangalore');

UPDATE `users` SET `city` = 'Mysuru', `district` = 'Mysuru', `state` = 'Karnataka'
WHERE LOWER(TRIM(`address`)) IN ('mysuru', 'mysore');

UPDATE `users` SET `city` = 'Hassan', `district` = 'Hassan', `state` = 'Karnataka'
WHERE LOWER(TRIM(`address`)) LIKE 'hassan%';

-- Add lender-specified equipment quantity.
-- Existing equipment records receive 1 unit by default.
ALTER TABLE equipment
ADD COLUMN total_quantity INT NOT NULL DEFAULT 1 AFTER price_per_day;


ALTER TABLE equipment
ADD COLUMN security_deposit DECIMAL(10,2) NOT NULL DEFAULT 0.00
AFTER price_per_day;

ALTER TABLE equipment
ADD COLUMN max_booking_days INT(11) DEFAULT 30
AFTER min_booking_days;

-- 2. Optional one-time migration of existing values from the old items table.
-- Matching uses lender/title/category/price/image because item_id and equipment_id
-- are independent AUTO_INCREMENT values and must NOT be assumed equal.
UPDATE equipment e
LEFT JOIN items i
  ON i.lender_id = e.lender_id
 AND i.title = e.title
 AND i.category = e.category
 AND i.price_per_day = e.price_per_day
 AND (i.image = e.image OR i.image = '')
SET e.security_deposit = COALESCE(i.security_deposit, e.security_deposit),
    e.max_booking_days = COALESCE(i.max_rental_days, e.max_booking_days)
WHERE i.item_id IS NOT NULL;

-- 3. Keep existing inventory safe. Each equipment row represents its total inventory.
UPDATE equipment SET total_quantity = 1 WHERE total_quantity IS NULL OR total_quantity < 1;


ALTER TABLE bookings
MODIFY status ENUM(
    'Pending',
    'Accepted',
    'Rejected',
    'Delivered',
    'Returned',
    'Completed',
    'Overdue',
    'Cancelled'
) NOT NULL DEFAULT 'Pending';

//altered bookings tables
ALTER TABLE `bookings`
ADD COLUMN `delivery_date` DATE NULL AFTER `end_date`;

ALTER TABLE users
ADD COLUMN remember_token VARCHAR(255) NULL;

//10-10-2026
-- Step 3: Smart Rental Pricing + Price History
-- Run this once in phpMyAdmin while agri_rental_db is selected.
-- Existing equipment receives a baseline snapshot of its CURRENT price.
-- Actual price changes made after setup are captured automatically by the trigger.

CREATE TABLE IF NOT EXISTS `equipment_price_history` (
  `history_id` INT(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` INT(11) NOT NULL,
  `lender_id` INT(11) NOT NULL,
  `old_price` DECIMAL(10,2) DEFAULT NULL,
  `new_price` DECIMAL(10,2) NOT NULL,
  `change_type` ENUM('Initial','Price Change') NOT NULL DEFAULT 'Price Change',
  `changed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `idx_price_history_equipment_date` (`equipment_id`, `changed_at`),
  KEY `idx_price_history_lender_date` (`lender_id`, `changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Record a baseline current price for each existing item only if it has no history yet.
INSERT INTO `equipment_price_history`
  (`equipment_id`, `lender_id`, `old_price`, `new_price`, `change_type`, `changed_at`)
SELECT e.`equipment_id`, e.`lender_id`, NULL, e.`price_per_day`, 'Initial', e.`created_at`
FROM `equipment` e
WHERE NOT EXISTS (
  SELECT 1 FROM `equipment_price_history` h
  WHERE h.`equipment_id` = e.`equipment_id`
);

-- Recreate the trigger safely if this setup script is run again.
DROP TRIGGER IF EXISTS `equipment_price_history_after_update`;

CREATE TRIGGER `equipment_price_history_after_update`
AFTER UPDATE ON `equipment`
FOR EACH ROW
INSERT INTO `equipment_price_history`
  (`equipment_id`, `lender_id`, `old_price`, `new_price`, `change_type`, `changed_at`)
SELECT NEW.`equipment_id`, NEW.`lender_id`, OLD.`price_per_day`, NEW.`price_per_day`, 'Price Change', CURRENT_TIMESTAMP
WHERE NOT (OLD.`price_per_day` <=> NEW.`price_per_day`);


//

ALTER TABLE bookings
ADD COLUMN reminder_notification_sent_at DATETIME NULL DEFAULT NULL,
ADD COLUMN overdue_notification_sent_at DATETIME NULL DEFAULT NUll,

INSERT INTO `users`
(
    `full_name`,
    `email`,
    `phone`,
    `password`,
    `role`,
    `security_question`,
    `security_answer`,
    `address`,
    `city`,
    `district`,
    `state`
)
SELECT
    new_users.full_name,
    new_users.email,
    new_users.phone,
    '$2y$12$Yw3yQlK8HmKBIWHNLSygJ.tPV1AZieRxbgf5DVK6DEDv792sTn2aG',
    new_users.role,
    'What was the name of your first pet?',
    'mango123',
    new_users.address,
    new_users.city,
    new_users.district,
    'Karnataka'
FROM
(
    SELECT
        'Manjunath Gowda' AS full_name,
        'manjunath.lender02@example.com' AS email,
        '9000000102' AS phone,
        'lender' AS role,
        '25, Hunsur Road, Mysuru' AS address,
        'Mysuru' AS city,
        'Mysuru' AS district

    UNION ALL

    SELECT
        'Suresh Patil',
        'suresh.lender03@example.com',
        '9000000103',
        'lender',
        '18, Gokul Road, Hubballi',
        'Hubballi',
        'Dharwad'

    UNION ALL

    SELECT
        'Ramesh Shetty',
        'ramesh.lender04@example.com',
        '9000000104',
        'lender',
        '14, Sagar Road, Shivamogga',
        'Shivamogga',
        'Shivamogga'

    UNION ALL

    SELECT
        'Arun Kumar',
        'arun.renter01@example.com',
        '9000000201',
        'renter',
        '10, Yelahanka Main Road, Bengaluru',
        'Bengaluru',
        'Bengaluru Urban'

    UNION ALL

    SELECT
        'Kiran S',
        'kiran.renter02@example.com',
        '9000000202',
        'renter',
        '22, Bannur Road, Mysuru',
        'Mysuru',
        'Mysuru'

    UNION ALL

    SELECT
        'Vinay Patil',
        'vinay.renter03@example.com',
        '9000000203',
        'renter',
        '15, Gokul Road, Hubballi',
        'Hubballi',
        'Dharwad'

    UNION ALL

    SELECT
        'Deepak Gowda',
        'deepak.renter04@example.com',
        '9000000204',
        'renter',
        '8, Sagar Road, Shivamogga',
        'Shivamogga',
        'Shivamogga'

    UNION ALL

        SELECT
        'Harish Gowda',
        'harish.renter05@example.com',
        '9000000205',
        'renter',
        '12, Mandya Main Road, Mandya',
        'Mandya',
        'Mandya'
) AS new_users
WHERE NOT EXISTS
(
    SELECT 1
    FROM `users`
    WHERE `users`.`email` = new_users.email
);


--thanu paste madu sql ali
START TRANSACTION;

INSERT INTO equipment
(
    category_id,
    lender_id,
    title,
    category,
    brand_model,
    power_hp,
    drive_type,
    model_year,
    fuel_type,
    working_width,
    equipment_condition,
    price_per_day,
    security_deposit,
    total_quantity,
    min_booking_days,
    max_booking_days,
    service_location,
    distance_km,
    description,
    image,
    badge,
    status,
    rating,
    rating_count,
    is_featured
)

SELECT
    new_equipment.category_id,
    26,
    new_equipment.title,
    new_equipment.category,
    new_equipment.brand_model,
    new_equipment.power_hp,
    new_equipment.drive_type,
    new_equipment.model_year,
    new_equipment.fuel_type,
    new_equipment.working_width,
    new_equipment.equipment_condition,
    new_equipment.price_per_day,
    new_equipment.security_deposit,
    1,
    1,
    7,
    new_equipment.service_location,
    25.0,
    new_equipment.description,
    'default.png',
    'NONE',
    'Available',
    0.0,
    0,
    0

FROM
(
    SELECT
        2 AS category_id,
        'Harvesting' AS category,
        'Paddy Harvester' AS title,
        'Kubota Paddy Harvester' AS brand_model,
        35 AS power_hp,
        '4WD' AS drive_type,
        2023 AS model_year,
        'Diesel' AS fuel_type,
        '1.5 m' AS working_width,
        'Good' AS equipment_condition,
        3000.00 AS price_per_day,
        1500.00 AS security_deposit,
        'Bengaluru Rural, Hassan' AS service_location,
        'Paddy harvesting machine for cutting and collecting rice crops efficiently.' AS description

    UNION ALL

    SELECT
        2, 'Harvesting',
        'Mini Combine Harvester',
        'Mahindra Mini Combine',
        30, '4WD', 2024, 'Diesel',
        '1.2 m', 'Good', 2500.00, 1200.00,
        'Mysuru, Hassan',
        'Compact combine harvester suitable for harvesting crops on small and medium-sized farms.'

    UNION ALL

    SELECT
        2, 'Harvesting',
        'Power Reaper',
        'Kisan Kraft Power Reaper',
        7, 'Portable', 2023, 'Petrol',
        '1.0 m', 'Used', 1000.00, 400.00,
        'Tumakuru, Mysuru',
        'Power-operated reaper for cutting standing crops during the harvesting season.'

    UNION ALL

    SELECT
        6, 'Spraying',
        'Battery Sprayer',
        'Kisan Kraft Battery Sprayer',
        NULL, 'Portable', 2024, 'Battery',
        '16 L', 'Good', 250.00, 100.00,
        'Bengaluru Rural, Tumakuru',
        'Rechargeable battery sprayer for applying suitable agricultural crop treatments.'

    UNION ALL

    SELECT
        6, 'Spraying',
        'Knapsack Sprayer',
        'Manual Knapsack Sprayer',
        NULL, 'Manual', 2023, 'Manual',
        '16 L', 'Good', 150.00, 50.00,
        'Hassan, Mysuru',
        'Hand-operated backpack sprayer for applying agricultural liquids to crops.'

    UNION ALL

    SELECT
        6, 'Spraying',
        'Mist Blower Sprayer',
        'Kisan Kraft Mist Blower',
        3, 'Portable', 2024, 'Petrol',
        '14 L', 'Good', 350.00, 150.00,
        'Tumakuru, Hassan',
        'Portable mist blower for crop treatment in suitable agricultural fields.'

    UNION ALL

    SELECT
        3, 'Irrigation',
        'Diesel Water Pump',
        'Kirloskar Diesel Pump',
        7, 'Portable', 2023, 'Diesel',
        '3 inch', 'Good', 1200.00, 500.00,
        'Bengaluru Rural, Mysuru',
        'Diesel-powered water pump for transferring water to agricultural fields.'

    UNION ALL

    SELECT
        3, 'Irrigation',
        'Portable Water Pump',
        'Kirloskar Portable Pump',
        5, 'Portable', 2024, 'Diesel',
        '2 inch', 'Good', 800.00, 300.00,
        'Hassan, Tumakuru',
        'Portable irrigation pump suitable for moving water from a water source to farm fields.'

    UNION ALL

    SELECT
        3, 'Irrigation',
        'Solar Irrigation Pump',
        'Shakti Solar Pump',
        3, 'Portable', 2024, 'Solar',
        '2 inch', 'Good', 1500.00, 500.00,
        'Mysuru, Hassan',
        'Solar-powered irrigation pump designed to support agricultural water supply.'

    UNION ALL

    SELECT
        3, 'Irrigation',
        'Sprinkler Irrigation Set',
        'Agricultural Sprinkler Set',
        NULL, 'Portable', 2024, 'Manual',
        '20 m', 'Good', 500.00, 200.00,
        'Tumakuru, Mysuru',
        'Portable sprinkler equipment for distributing water across suitable agricultural plots.'

    UNION ALL

    SELECT
        4, 'Tillage',
        'Disc Plough',
        'Field King Disc Plough',
        45, '3 Point Linkage', 2023, 'Diesel',
        '3 Disc', 'Good', 900.00, 400.00,
        'Bengaluru Rural, Hassan',
        'Disc plough for breaking and turning soil before planting crops.'

    UNION ALL

    SELECT
        4, 'Tillage',
        'Cultivator',
        'Mahindra Agricultural Cultivator',
        45, '3 Point Linkage', 2024, 'Diesel',
        '9 Tine', 'Good', 700.00, 300.00,
        'Mysuru, Tumakuru',
        'Cultivator for loosening soil, preparing seedbeds, and controlling weeds.'

    UNION ALL

    SELECT
        4, 'Tillage',
        'Disc Harrow',
        'Field King Disc Harrow',
        40, '3 Point Linkage', 2023, 'Diesel',
        '6 Feet', 'Used', 650.00, 250.00,
        'Hassan, Mysuru',
        'Disc harrow for breaking soil clods and preparing fields for cultivation.'

    UNION ALL

    SELECT
        5, 'Seeding',
        'Seed Drill Machine',
        'Mahindra Seed Drill',
        40, '3 Point Linkage', 2024, 'Diesel',
        '9 Tine', 'Good', 800.00, 300.00,
        'Bengaluru Rural, Tumakuru',
        'Seed drill for placing seeds in rows at suitable depths and spacing.'

    UNION ALL

    SELECT
        5, 'Seeding',
        'Multi Crop Planter',
        'Agricultural Multi Crop Planter',
        40, '3 Point Linkage', 2023, 'Diesel',
        '4 Row', 'Good', 1000.00, 400.00,
        'Hassan, Mysuru',
        'Multi-crop planter for sowing suitable crop seeds with consistent spacing.'

    UNION ALL

    SELECT
        5, 'Seeding',
        'Maize Seed Planter',
        'Agricultural Maize Planter',
        45, '3 Point Linkage', 2024, 'Diesel',
        '2 Row', 'Good', 900.00, 350.00,
        'Tumakuru, Hassan',
        'Seed planter designed to place maize seeds in rows at suitable intervals.'

    UNION ALL

    SELECT
        5, 'Seeding',
        'Manual Seed Drill',
        'Manual Agricultural Seed Drill',
        NULL, 'Manual', 2023, 'Manual',
        'Hand Operated', 'Good', 300.00, 100.00,
        'Mysuru, Tumakuru',
        'Manually operated seed drill for sowing seeds in small agricultural plots.'

) AS new_equipment

WHERE NOT EXISTS
(
    SELECT 1
    FROM equipment AS existing_equipment
    WHERE existing_equipment.lender_id = 26
      AND existing_equipment.title = new_equipment.title
);

COMMIT;

SELECT
    category,
    COUNT(*) AS total_equipment
FROM equipment
WHERE lender_id = 26
GROUP BY category
ORDER BY category;

//date modified:02 novmebr 2026
--changes: Added new columns to bookings table for late charge collection.
ALTER TABLE bookings
ADD COLUMN late_charge_collected TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN late_charge_collected_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
ADD COLUMN late_charge_collected_at DATETIME NULL;