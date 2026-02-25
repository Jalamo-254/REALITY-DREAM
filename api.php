<?php
// Simple REST endpoints for submissions (GET list, POST create)
require_once 'db_config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $res = $conn->query('SELECT id, first_name, last_name, email, phone, course, message, attachment, submitted_date, status FROM contacts ORDER BY submitted_date DESC');
    $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($method === 'POST') {
    $input = $_POST;
    $firstName = trim($input['first_name'] ?? '');
    $lastName = trim($input['last_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $course = trim($input['course'] ?? '');
    $message = trim($input['message'] ?? '');

    $stmt = $conn->prepare('INSERT INTO contacts (first_name, last_name, email, phone, course, message) VALUES (?, ?, ?, ?, ?, ?)');
    if ($stmt) {
        $stmt->bind_param('ssssss', $firstName, $lastName, $email, $phone, $course, $message);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => (bool)$ok]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unsupported method']);
