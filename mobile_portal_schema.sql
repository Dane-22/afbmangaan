-- Apply to the existing database before deploying Android portal management.
-- Adds the Archived status already offered by the website.
ALTER TABLE events MODIFY status ENUM('Upcoming','Ongoing','Completed','Cancelled','Archived') DEFAULT 'Upcoming';
