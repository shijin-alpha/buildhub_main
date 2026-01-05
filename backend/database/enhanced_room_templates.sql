-- Enhanced Room Templates with Walkways, Staircases, and Better Color Coding
-- Update existing room templates and add new ones

-- First, update the category enum to include new types
ALTER TABLE room_templates MODIFY COLUMN category ENUM('bedroom', 'bathroom', 'kitchen', 'living', 'dining', 'utility', 'outdoor', 'circulation', 'structural', 'other') NOT NULL;

-- Clear existing templates to insert enhanced ones
DELETE FROM room_templates;

-- Insert enhanced room templates with better color coding and updated dimensions
INSERT INTO room_templates (name, category, default_width, default_height, min_width, min_height, max_width, max_height, color, icon) VALUES

-- Bedrooms (Green tones)
('Master Bedroom', 'bedroom', 15, 12, 10, 10, 20, 16, '#c8e6c9', '🛏️'),
('Bedroom', 'bedroom', 12, 10, 8, 8, 16, 14, '#dcedc8', '🛏️'),
('Guest Bedroom', 'bedroom', 10, 10, 8, 8, 14, 12, '#e8f5e8', '🛏️'),
('Kids Bedroom', 'bedroom', 10, 9, 8, 8, 12, 12, '#f1f8e9', '🧸'),

-- Bathrooms (Blue tones)
('Master Bathroom', 'bathroom', 10, 8, 6, 5, 14, 12, '#b3e5fc', '🛁'),
('Bathroom', 'bathroom', 8, 6, 5, 4, 12, 10, '#e1f5fe', '🚿'),
('Powder Room', 'bathroom', 5, 4, 3, 3, 8, 6, '#f0f8ff', '🚽'),
('Attached Bathroom', 'bathroom', 7, 6, 5, 4, 10, 8, '#e3f2fd', '🚿'),

-- Kitchen (Pink/Red tones)
('Kitchen', 'kitchen', 12, 8, 8, 6, 16, 12, '#ffcdd2', '🍳'),
('Modular Kitchen', 'kitchen', 10, 8, 8, 6, 14, 10, '#f8bbd9', '🍳'),
('Pantry', 'kitchen', 6, 4, 4, 3, 8, 6, '#fce4ec', '🥫'),

-- Living Areas (Orange tones)
('Living Room', 'living', 16, 14, 12, 10, 24, 20, '#ffe0b2', '🛋️'),
('Family Room', 'living', 14, 12, 10, 8, 18, 16, '#ffcc80', '👨‍👩‍👧‍👦'),
('Drawing Room', 'living', 12, 10, 8, 8, 16, 14, '#fff3e0', '🪑'),
('TV Lounge', 'living', 12, 10, 8, 8, 16, 14, '#ffe0b2', '📺'),

-- Dining Areas (Purple tones)
('Dining Room', 'dining', 12, 10, 8, 8, 16, 14, '#e1bee7', '🍽️'),
('Breakfast Area', 'dining', 8, 6, 6, 4, 12, 8, '#f3e5f5', '☕'),

-- Utility Areas (Gray tones)
('Utility Room', 'utility', 8, 6, 4, 4, 12, 10, '#e0e0e0', '🧹'),
('Laundry Room', 'utility', 8, 6, 4, 4, 10, 8, '#eeeeee', '👕'),
('Store Room', 'utility', 6, 6, 4, 4, 10, 10, '#f5f5f5', '📦'),
('Servant Room', 'utility', 8, 8, 6, 6, 10, 10, '#e8eaf6', '🏠'),

-- Outdoor Areas (Light Green tones)
('Balcony', 'outdoor', 8, 4, 4, 3, 16, 8, '#c8e6c9', '🌿'),
('Terrace', 'outdoor', 12, 8, 6, 4, 20, 16, '#dcedc8', '🏡'),
('Garden', 'outdoor', 15, 10, 8, 6, 25, 20, '#e8f5e8', '🌳'),
('Courtyard', 'outdoor', 10, 10, 6, 6, 16, 16, '#f1f8e9', '🏛️'),

-- Circulation Areas (Yellow tones) - NEW CATEGORY
('Corridor', 'circulation', 20, 4, 15, 3, 30, 6, '#fff9c4', '🚶'),
('Hallway', 'circulation', 15, 6, 10, 4, 25, 8, '#fff59d', '🚶‍♂️'),
('Passage', 'circulation', 12, 3, 8, 2, 20, 5, '#ffecb3', '➡️'),
('Entrance Hall', 'circulation', 10, 8, 6, 6, 16, 12, '#ffe082', '🚪'),
('Foyer', 'circulation', 8, 8, 6, 6, 12, 12, '#ffd54f', '🏛️'),

-- Structural Elements (Brown tones) - NEW CATEGORY
('Staircase', 'structural', 8, 12, 6, 8, 12, 16, '#d7ccc8', '🪜'),
('Spiral Staircase', 'structural', 6, 6, 4, 4, 8, 8, '#bcaaa4', '🌀'),
('Elevator Shaft', 'structural', 6, 6, 4, 4, 8, 8, '#a1887f', '🛗'),
('Column', 'structural', 2, 2, 1, 1, 3, 3, '#8d6e63', '🏛️'),
('Beam Area', 'structural', 8, 2, 4, 1, 12, 3, '#795548', '🏗️'),

-- Other Special Rooms (Light Purple tones)
('Study Room', 'other', 10, 8, 6, 6, 14, 12, '#e8eaf6', '📚'),
('Home Office', 'other', 10, 8, 6, 6, 14, 12, '#c5cae9', '💻'),
('Pooja Room', 'other', 6, 6, 4, 4, 8, 8, '#d1c4e9', '🕉️'),
('Prayer Room', 'other', 6, 6, 4, 4, 8, 8, '#b39ddb', '🙏'),
('Home Theater', 'other', 16, 12, 12, 10, 20, 16, '#9575cd', '🎬'),
('Gym', 'other', 12, 10, 8, 8, 16, 14, '#7e57c2', '🏋️'),
('Library', 'other', 12, 10, 8, 8, 16, 14, '#673ab7', '📖'),
('Music Room', 'other', 10, 10, 8, 8, 14, 14, '#5e35b1', '🎵'),
('Workshop', 'other', 12, 8, 8, 6, 16, 12, '#512da8', '🔧'),
('Safe Room', 'other', 6, 6, 4, 4, 8, 8, '#4527a0', '🔒');