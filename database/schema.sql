CREATE DATABASE IF NOT EXISTS grievance_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE grievance_portal;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','parent','teacher','administrator','official') NOT NULL DEFAULT 'student',
  school_name VARCHAR(180) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE complaint_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE complaints (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tracking_code VARCHAR(24) NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  subject VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  status ENUM('submitted','under_review','assigned','in_progress','resolved','closed','rejected') NOT NULL DEFAULT 'submitted',
  owner_name VARCHAR(120) NULL,
  resolution_notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT complaints_user_fk FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT complaints_category_fk FOREIGN KEY (category_id) REFERENCES complaint_categories(id)
);

CREATE TABLE complaint_status_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  complaint_id INT UNSIGNED NOT NULL,
  changed_by INT UNSIGNED NOT NULL,
  status VARCHAR(30) NOT NULL,
  note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT status_complaint_fk FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
  CONSTRAINT status_user_fk FOREIGN KEY (changed_by) REFERENCES users(id)
);

CREATE TABLE notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  complaint_id INT UNSIGNED NULL,
  title VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  is_read BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT notifications_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT notifications_complaint_fk FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE SET NULL
);

INSERT INTO complaint_categories (name) VALUES
  ('Facilities & infrastructure'), ('Academic support'), ('Safety & wellbeing'), ('Transport'), ('Other');
