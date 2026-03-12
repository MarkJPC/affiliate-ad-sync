-- Migration: Remove 'pending' approval status
-- All ads now default to 'approved'; only explicit admin action sets 'denied'.

-- Convert all pending ads to approved
UPDATE ads SET approval_status = 'approved' WHERE approval_status = 'pending';

-- Remove 'pending' from ENUM
ALTER TABLE ads MODIFY COLUMN approval_status ENUM('approved', 'denied') NOT NULL DEFAULT 'approved';
