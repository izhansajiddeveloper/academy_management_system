-- ======================================
-- DATABASE
-- ======================================
CREATE DATABASE IF NOT EXISTS academy_management_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE academy_management_system;

-- ======================================
-- 1️⃣ USER & ROLE MANAGEMENT
-- ======================================

CREATE TABLE user_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO user_types (type_name) VALUES
('admin'),
('teacher'),
('student');

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type_id INT NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_type_id) REFERENCES user_types(id)
) ENGINE=InnoDB;

-- ======================================
-- 2️⃣ SESSIONS
-- ======================================

CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(50) NOT NULL,
    start_date DATE,
    end_date DATE,
    status ENUM('active','completed') DEFAULT 'active'
) ENGINE=InnoDB;

-- ======================================
-- 3️⃣ SKILLS (COURSES)
-- ======================================

CREATE TABLE skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(150) NOT NULL,
    duration_months INT NOT NULL,
    level ENUM('basic','intermediate','advanced') DEFAULT 'basic',
    description TEXT,
    has_syllabus TINYINT(1) DEFAULT 1,
    has_practice TINYINT(1) DEFAULT 1,
    status ENUM('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB;

CREATE TABLE skill_syllabus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_id INT NOT NULL,
    topic_title VARCHAR(200) NOT NULL,
    topic_order INT NOT NULL,
    FOREIGN KEY (skill_id) REFERENCES skills(id)
) ENGINE=InnoDB;

-- ======================================
-- 4️⃣ STUDENT & TEACHER PROFILES
-- ======================================

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_code VARCHAR(50) UNIQUE,
    name VARCHAR(150) NOT NULL,
    father_name VARCHAR(150),
    gender ENUM('male','female','other'),
    dob DATE,
    phone VARCHAR(30),
    address TEXT,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    teacher_code VARCHAR(50) UNIQUE,
    name VARCHAR(150) NOT NULL,
    qualification VARCHAR(150),
    experience_years INT,
    phone VARCHAR(30),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ======================================
-- 5️⃣ BATCHES
-- ======================================

CREATE TABLE batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_id INT NOT NULL,
    session_id INT NOT NULL,
    batch_name VARCHAR(100) NOT NULL,
    start_time TIME,
    end_time TIME,
    max_students INT,
    FOREIGN KEY (skill_id) REFERENCES skills(id),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
) ENGINE=InnoDB;

-- ======================================
-- 6️⃣ STUDENT ENROLLMENTS
-- ======================================

CREATE TABLE student_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    skill_id INT NOT NULL,
    session_id INT NOT NULL,
    batch_id INT NOT NULL,
    admission_date DATE,
    status ENUM('active','completed','dropped') DEFAULT 'active',
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (skill_id) REFERENCES skills(id),
    FOREIGN KEY (session_id) REFERENCES sessions(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id)
) ENGINE=InnoDB;

-- ======================================
-- 7️⃣ TEACHER ASSIGNMENT
-- ======================================

CREATE TABLE teacher_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    batch_id INT NOT NULL,
    session_id INT NOT NULL,
    assigned_date DATE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
) ENGINE=InnoDB;

-- ======================================
-- 8️⃣ FEES
-- ======================================

CREATE TABLE fee_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_id INT NOT NULL,
    session_id INT NOT NULL,
    total_fee DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (skill_id) REFERENCES skills(id),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
) ENGINE=InnoDB;

CREATE TABLE fee_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    student_id INT NOT NULL,
    skill_id INT NOT NULL,
    session_id INT NOT NULL,
    batch_id INT NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_date DATE,
    payment_method VARCHAR(50),
    remarks TEXT,
    FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (skill_id) REFERENCES skills(id),
    FOREIGN KEY (session_id) REFERENCES sessions(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id)
) ENGINE=InnoDB;

-- ======================================
-- 9️⃣ ATTENDANCE
-- ======================================

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    student_id INT NOT NULL,
    skill_id INT NOT NULL,
    session_id INT NOT NULL,
    batch_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present','absent') NOT NULL,
    FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (skill_id) REFERENCES skills(id),
    FOREIGN KEY (session_id) REFERENCES sessions(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id)
) ENGINE=InnoDB;

-- ======================================
-- 🔟 SKILL PROGRESS & CERTIFICATES
-- ======================================

CREATE TABLE skill_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    session_id INT NOT NULL,
    topics_completed INT DEFAULT 0,
    total_topics INT DEFAULT 0,
    completion_percent DECIMAL(5,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
) ENGINE=InnoDB;

CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    student_id INT NOT NULL,
    skill_id INT NOT NULL,
    session_id INT NOT NULL,
    certificate_no VARCHAR(100) UNIQUE,
    issued_date DATE,
    FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (skill_id) REFERENCES skills(id),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
) ENGINE=InnoDB;

-- ======================================
-- 1️⃣1️⃣ QUIZZES
-- ======================================

CREATE TABLE skill_quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_id INT NOT NULL,
    session_id INT NOT NULL,
    quiz_title VARCHAR(150) NOT NULL,
    quiz_type ENUM('practice','assessment') DEFAULT 'practice',
    total_marks INT NOT NULL,
    FOREIGN KEY (skill_id) REFERENCES skills(id),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
) ENGINE=InnoDB;

CREATE TABLE skill_quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question TEXT NOT NULL,
    FOREIGN KEY (quiz_id) REFERENCES skill_quizzes(id)
) ENGINE=InnoDB;

CREATE TABLE skill_quiz_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    option_text VARCHAR(255) NOT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES skill_quiz_questions(id)
) ENGINE=InnoDB;

CREATE TABLE skill_quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    student_id INT NOT NULL,
    session_id INT NOT NULL,
    score INT,
    attempt_date DATE,
    FOREIGN KEY (quiz_id) REFERENCES skill_quizzes(id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
) ENGINE=InnoDB;

-- ======================================
-- 1️⃣2️⃣ SYSTEM TABLES
-- ======================================

CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200),
    message TEXT,
    target_role ENUM('admin','teacher','student','all'),
    session_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id)
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action TEXT,
    session_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
) ENGINE=InnoDB;
-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30),
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;