-- Date:29-08-2026
--Changes:Added new columns to bookings table "Delivery address, phone number, id number and quantity" please do run this on phpmyadmin to update the bookings table.

ALTER TABLE `bookings` 
ADD COLUMN `quantity` INT(11) NOT NULL DEFAULT 1 AFTER `total_days`,
ADD COLUMN `phone_number` VARCHAR(15) DEFAULT NULL AFTER `renter_id`,
ADD COLUMN `id_number` VARCHAR(50) DEFAULT NULL AFTER `id_proof_doc`,
ADD COLUMN `delivery_address` TEXT NOT NULL AFTER `id_number`;