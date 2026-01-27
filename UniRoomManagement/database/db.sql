-- Create database
CREATE DATABASE IF NOT EXISTS uniroom_db;
USE uniroom_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'faculty', 'admin', 'club') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rooms table
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    room_name VARCHAR(100),
    capacity INT NOT NULL,
    building VARCHAR(50),
    floor INT,
    description TEXT,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Time slots table
CREATE TABLE time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_name VARCHAR(50),
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    UNIQUE KEY unique_slot (start_time, end_time)
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    slot_id INT NOT NULL,
    booking_date DATE NOT NULL,
    purpose TEXT,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    priority INT DEFAULT 1, -- 1=Student, 2=Club, 3=Faculty
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (slot_id) REFERENCES time_slots(id),
    INDEX idx_booking_date (booking_date),
    INDEX idx_status (status)
);

-- Blocked rooms (for admin)
CREATE TABLE blocked_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    blocked_date DATE NOT NULL,
    reason TEXT,
    blocked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (blocked_by) REFERENCES users(id)
);

-- Room issue reports
CREATE TABLE IF NOT EXISTS room_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('low','medium','high') DEFAULT 'low',
    status ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    INDEX idx_reports_status (status),
    INDEX idx_reports_room (room_id)
);

-- Insert default time slots
INSERT INTO time_slots (slot_name, start_time, end_time) VALUES
('Morning Session 1', '09:30:00', '11:00:00'),
('Morning Session 2', '11:00:00', '12:30:00'),
('Afternoon Session 1', '13:00:00', '14:30:00'),
('Afternoon Session 2', '14:30:00', '16:00:00');

-- Insert sample rooms
INSERT INTO rooms (room_number, room_name, capacity, building, floor, description) VALUES
('101', 'Room 101', 30, 'Main Building', 1, 'General purpose room'),
('102', 'Room 102', 25, 'Main Building', 1, 'Study room'),
('103', 'Room 103', 40, 'Main Building', 1, 'Lecture room'),
('104', 'Room 104', 35, 'Main Building', 1, 'Seminar room'),
('105', 'Room 105', 20, 'Main Building', 1, 'Small group room'),
('201', 'Room 201', 50, 'Main Building', 2, 'Large classroom'),
('202', 'Room 202', 30, 'Main Building', 2, 'Computer lab'),
('203', 'Room 203', 25, 'Main Building', 2, 'Quiet study'),
('204', 'Room 204', 35, 'Main Building', 2, 'Meeting room');

-- Campus events table
CREATE TABLE IF NOT EXISTS campus_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    location VARCHAR(200),
    room_id INT NULL,
    audience ENUM('all','students','faculty','club') DEFAULT 'all',
    is_published TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_events_start (start_at)
);

-- Sample events
INSERT INTO campus_events (title, description, start_at, end_at, location, audience)
VALUES
('AI Workshop', 'Hands-on intro session', '2026-02-01 10:00:00', '2026-02-01 12:00:00', 'Room 402', 'students'),
('Debate Finals', 'Inter-college finals', '2026-02-05 14:00:00', '2026-02-05 17:00:00', 'Main Hall', 'all');

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_id INT NOT NULL,
    type ENUM('booking_cancelled', 'booking_overridden', 'booking_approved', 'booking_rejected') DEFAULT 'booking_cancelled',
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
);