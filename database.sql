-- Reference schema (install.php runs this programmatically)
CREATE DATABASE IF NOT EXISTS resultpadi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE resultpadi_db;

CREATE TABLE IF NOT EXISTS super_admins (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(150) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS schools (id INT AUTO_INCREMENT PRIMARY KEY, school_name VARCHAR(180) NOT NULL, slug VARCHAR(180) NOT NULL UNIQUE, logo_url VARCHAR(255) DEFAULT NULL, admin_email VARCHAR(150) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, status ENUM('active','suspended') NOT NULL DEFAULT 'active', termly_fee_paid_until DATE DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (id INT AUTO_INCREMENT PRIMARY KEY, school_id INT NOT NULL, admission_no VARCHAR(60) NOT NULL, surname VARCHAR(80) NOT NULL, first_name VARCHAR(80) NOT NULL, class_name VARCHAR(50) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_school_adm (school_id, admission_no), CONSTRAINT fk_students_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS results (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, school_id INT NOT NULL, session VARCHAR(20) NOT NULL, term VARCHAR(20) NOT NULL, subjects_json LONGTEXT NOT NULL, total_score DECIMAL(8,2) NOT NULL DEFAULT 0, average DECIMAL(6,2) NOT NULL DEFAULT 0, position INT DEFAULT NULL, teacher_remark TEXT DEFAULT NULL, views INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CONSTRAINT fk_results_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE, CONSTRAINT fk_results_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE) ENGINE=InnoDB;

-- Seed Super Admin (password: password123)
INSERT INTO super_admins (email, password_hash) SELECT 'admin@resultpadi.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS4bZJ18JuywdBq7wxZ9wzqA5cpm'
WHERE NOT EXISTS (SELECT 1 FROM super_admins WHERE email='admin@resultpadi.com');