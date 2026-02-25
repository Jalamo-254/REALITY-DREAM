<?php
session_start();
require_once 'db_config.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $message = 'New password and confirmation do not match.';
    } elseif (strlen($new) < 8) {
        $message = 'New password must be at least 8 characters.';
    } else {
        $username = $_SESSION['admin_user'];
        $stmt = $conn->prepare('SELECT password_hash FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->bind_result($hash);
        if ($stmt->fetch() && password_verify($current, $hash)) {
            $stmt->close();
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $conn->prepare('UPDATE admin_users SET password_hash = ? WHERE username = ?');
            $upd->bind_param('ss', $newHash, $username);
            if ($upd->execute()) {
                $message = 'Password updated successfully.';
            } else {
                $message = 'Failed to update password.';
            }
            $upd->close();
        } else {
            $message = 'Current password is incorrect.';
            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Change Admin Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <div class="bg-white p-6 rounded shadow w-full max-w-md">
    <h2 class="text-2xl font-bold mb-4">Change Password</h2>
    <?php if ($message): ?>
      <div class="mb-4 text-sm text-gray-800 bg-yellow-50 p-2 rounded"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="post">
      <label class="block mb-2">Current Password</label>
      <input name="current_password" type="password" class="w-full p-2 border rounded mb-3" required>
      <label class="block mb-2">New Password</label>
      <input name="new_password" type="password" class="w-full p-2 border rounded mb-3" required>
      <label class="block mb-2">Confirm New Password</label>
      <input name="confirm_password" type="password" class="w-full p-2 border rounded mb-4" required>
      <div class="flex space-x-2">
        <button class="flex-1 bg-green-600 text-white py-2 rounded">Update Password</button>
        <a href="admin.php" class="flex-1 text-center bg-gray-200 py-2 rounded">Back</a>
      </div>
    </form>
  </div>
</body>
</html>
