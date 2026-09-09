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