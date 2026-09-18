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
