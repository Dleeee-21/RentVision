--
-- 002_roles.sql
-- Rename role: renter -> Customer, provider -> Vendor, admin -> Dev
--

ALTER TABLE users MODIFY role ENUM('Dev','Customer','Vendor','renter','provider','admin') NOT NULL DEFAULT 'Customer';

UPDATE users SET role='Customer' WHERE role='renter';
UPDATE users SET role='Vendor'   WHERE role='provider';
UPDATE users SET role='Dev'      WHERE role='admin';

ALTER TABLE users MODIFY role ENUM('Dev','Customer','Vendor') NOT NULL DEFAULT 'Customer';