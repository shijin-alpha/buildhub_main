-- Add photo-related fields to enhanced_progress_notifications table
-- This allows notifications to include information about attached photos

ALTER TABLE enhanced_progress_notifications 
ADD COLUMN has_photos BOOLEAN DEFAULT FALSE AFTER message,
ADD COLUMN geo_photos_count INT DEFAULT 0 AFTER has_photos;

-- Update existing records to set has_photos based on related progress updates
UPDATE enhanced_progress_notifications n
JOIN daily_progress_updates p ON n.reference_id = p.id AND n.notification_type = 'daily_update'
SET n.has_photos = (
    CASE 
        WHEN JSON_LENGTH(p.progress_photos) > 0 THEN TRUE
        ELSE FALSE
    END
);

-- Add index for photo-related queries
CREATE INDEX idx_notifications_photos ON enhanced_progress_notifications(has_photos, geo_photos_count);