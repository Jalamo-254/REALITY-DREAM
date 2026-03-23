<?php
// ============================================
// Database Configuration
// ============================================

// Database connection details for XAMPP
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_password = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_NAME') ?: 'Reality_Dream';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => 'Database Connection Error: ' . $conn->connect_error
    ]));
}

// Set charset to UTF-8 MB4
$conn->set_charset('utf8mb4');

// Create contacts table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    course VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    attachment VARCHAR(255) DEFAULT NULL,
    submitted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'New',
    KEY idx_contacts_status (status),
    KEY idx_contacts_submitted_date (submitted_date),
    KEY idx_contacts_email (email)
)";

if (!$conn->query($createTableSQL)) {
    error_log("Error creating table: " . $conn->error);
}

// Create enrollments table if it doesn't exist
$createEnrollmentsSQL = "CREATE TABLE IF NOT EXISTS enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    course VARCHAR(200) NOT NULL,
    study_mode VARCHAR(50) DEFAULT NULL,
    intake_month VARCHAR(30) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    attachment VARCHAR(255) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'New',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_enrollments_status (status),
    KEY idx_enrollments_submitted_at (submitted_at),
    KEY idx_enrollments_email (email)
)";

if (!$conn->query($createEnrollmentsSQL)) {
    error_log("Error creating enrollments table: " . $conn->error);
}

// Ensure attachment column exists for older enrollments tables
$checkEnrollAttachment = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'attachment'");
if ($checkEnrollAttachment && $checkEnrollAttachment->num_rows === 0) {
    if (!$conn->query("ALTER TABLE enrollments ADD COLUMN attachment VARCHAR(255) DEFAULT NULL AFTER course")) {
        error_log("Error adding enrollments.attachment column: " . $conn->error);
    }
}

// Ensure status column exists for enrollments
$checkEnrollStatus = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'status'");
if ($checkEnrollStatus && $checkEnrollStatus->num_rows === 0) {
    if (!$conn->query("ALTER TABLE enrollments ADD COLUMN status VARCHAR(20) DEFAULT 'New' AFTER attachment")) {
        error_log("Error adding enrollments.status column: " . $conn->error);
    }
}

// Ensure study_mode column exists for enrollments
$checkEnrollStudyMode = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'study_mode'");
if ($checkEnrollStudyMode && $checkEnrollStudyMode->num_rows === 0) {
    if (!$conn->query("ALTER TABLE enrollments ADD COLUMN study_mode VARCHAR(50) DEFAULT NULL AFTER course")) {
        error_log("Error adding enrollments.study_mode column: " . $conn->error);
    }
}

// Ensure intake_month column exists for enrollments
$checkEnrollIntake = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'intake_month'");
if ($checkEnrollIntake && $checkEnrollIntake->num_rows === 0) {
    if (!$conn->query("ALTER TABLE enrollments ADD COLUMN intake_month VARCHAR(30) DEFAULT NULL AFTER study_mode")) {
        error_log("Error adding enrollments.intake_month column: " . $conn->error);
    }
}

// Ensure notes column exists for enrollments
$checkEnrollNotes = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'notes'");
if ($checkEnrollNotes && $checkEnrollNotes->num_rows === 0) {
    if (!$conn->query("ALTER TABLE enrollments ADD COLUMN notes TEXT DEFAULT NULL AFTER intake_month")) {
        error_log("Error adding enrollments.notes column: " . $conn->error);
    }
}

// Ensure indexes exist for faster admin filtering/search
@($conn->query("ALTER TABLE contacts ADD INDEX IF NOT EXISTS idx_contacts_status (status)"));
@($conn->query("ALTER TABLE contacts ADD INDEX IF NOT EXISTS idx_contacts_submitted_date (submitted_date)"));
@($conn->query("ALTER TABLE contacts ADD INDEX IF NOT EXISTS idx_contacts_email (email)"));
@($conn->query("ALTER TABLE enrollments ADD INDEX IF NOT EXISTS idx_enrollments_status (status)"));
@($conn->query("ALTER TABLE enrollments ADD INDEX IF NOT EXISTS idx_enrollments_submitted_at (submitted_at)"));
@($conn->query("ALTER TABLE enrollments ADD INDEX IF NOT EXISTS idx_enrollments_email (email)"));

// Create admin users table if it doesn't exist
$createAdminSQL = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
";

if (!$conn->query($createAdminSQL)) {
    error_log("Error creating admin_users table: " . $conn->error);
}

// Seed default admin user if not exists (username: admin, password: Admin@2026)
$checkAdmin = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
if ($checkAdmin) {
    $defaultUser = 'admin';
    $checkAdmin->bind_param('s', $defaultUser);
    $checkAdmin->execute();
    $checkAdmin->store_result();
    if ($checkAdmin->num_rows === 0) {
        $passwordHash = password_hash('Admin@2026', PASSWORD_DEFAULT);
        $insertAdmin = $conn->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
        if ($insertAdmin) {
            $insertAdmin->bind_param('ss', $defaultUser, $passwordHash);
            $insertAdmin->execute();
            $insertAdmin->close();
        }
    }
    $checkAdmin->close();
}

// Ensure uploads directory exists
$uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0755, true);
}

// Return connection for use in other files
?>
