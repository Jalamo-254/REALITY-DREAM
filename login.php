<?php
session_start();
require_once 'db_config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, password_hash FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $passwordHash);
            $stmt->fetch();
            if (password_verify($password, $passwordHash)) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $username;
                header('Location: admin.php');
                exit;
            } else {
                $error = 'Invalid credentials.';
            }
        } else {
            $error = 'Invalid credentials.';
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login</title>
  <link rel="stylesheet" href="https://cdn.tailwindcss.com">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <div class="bg-white p-6 rounded shadow w-full max-w-md">
    <h2 class="text-2xl font-bold mb-4">Admin Login</h2>
    <?php if ($error): ?>
      <div class="mb-4 text-red-600"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post">
      <label class="block mb-2">Username</label>
      <input name="username" class="w-full p-2 border rounded mb-3" required>
      <label class="block mb-2">Password</label>
      <input type="password" name="password" class="w-full p-2 border rounded mb-4" required>
      <button class="w-full bg-blue-600 text-white py-2 rounded">Login</button>
    </form>
    <p class="mt-3 text-xs text-gray-500">Default admin: <strong>admin</strong> / <strong>Admin@2026</strong></p>
  </div>
</body>
</html>
