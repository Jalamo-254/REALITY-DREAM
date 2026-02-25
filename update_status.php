<?php
session_start();
require_once 'db_config.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'New';
    $stmt = $conn->prepare('UPDATE contacts SET status = ? WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        $stmt->close();
    }
}

header('Location: admin.php');
exit;
