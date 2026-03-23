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
  <style>
    #chat-toggle { position: fixed; right: 20px; bottom: 20px; width: 56px; height: 56px; border-radius: 999px; background: linear-gradient(135deg, #377D3E, #6B3E93); color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 24px rgba(0,0,0,.25); cursor: pointer; z-index: 60; }
    #chatbot { position: fixed; right: 20px; bottom: 88px; width: 340px; max-width: calc(100vw - 24px); height: 470px; background: #fff; border-radius: 14px; box-shadow: 0 18px 40px rgba(0,0,0,.25); overflow: hidden; z-index: 60; display: none; border: 1px solid #e5e7eb; }
    #chatbot.open { display: flex; flex-direction: column; }
    #chat-header { background: linear-gradient(135deg, #121826, #2A2F3C); color: #fff; padding: 12px 14px; font-weight: 600; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
    #close-chat { cursor: pointer; font-size: 12px; }
    #chat-body { flex: 1; padding: 12px; background: #f8fafc; overflow-y: auto; }
    .chat-bot, .chat-user { margin-bottom: 10px; max-width: 85%; padding: 9px 11px; border-radius: 10px; line-height: 1.4; font-size: 13px; word-break: break-word; }
    .chat-bot { background: #fff; border: 1px solid #e5e7eb; color: #1f2937; }
    .chat-user { margin-left: auto; background: #6B3E93; color: #fff; }
    #chat-input-area { padding: 10px; border-top: 1px solid #e5e7eb; background: #fff; display: flex; gap: 8px; }
    #user-input { flex: 1; border: 1px solid #d1d5db; border-radius: 8px; padding: 9px 10px; outline: none; font-size: 13px; }
    #chat-send { border: 0; background: #377D3E; color: #fff; border-radius: 8px; padding: 0 12px; font-size: 13px; font-weight: 600; cursor: pointer; }
  </style>
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

  <div id="chat-toggle" title="Chat with assistant">💬</div>
  <div id="chatbot" aria-live="polite">
    <div id="chat-header">
      <span>Reality Dream Assistant</span>
      <span id="close-chat">Close</span>
    </div>
    <div id="chat-body">
      <div class="chat-bot">Hi! How can I help you today?</div>
    </div>
    <div id="chat-input-area">
      <input type="text" id="user-input" placeholder="Type your message...">
      <button id="chat-send" onclick="sendMessage()">Send</button>
    </div>
  </div>

  <script>
    const chatToggle = document.getElementById('chat-toggle');
    const chatbot = document.getElementById('chatbot');
    const closeChat = document.getElementById('close-chat');
    const chatBody = document.getElementById('chat-body');
    const userInput = document.getElementById('user-input');

    function appendChatMessage(text, type, isHtml) {
      const bubble = document.createElement('div');
      bubble.className = type === 'user' ? 'chat-user' : 'chat-bot';
      if (isHtml) bubble.innerHTML = text; else bubble.textContent = text;
      chatBody.appendChild(bubble);
      chatBody.scrollTop = chatBody.scrollHeight;
    }

    function getBotReply(message) {
      const text = message.toLowerCase();
      if (text.includes('emergency') || text.includes('urgent') || text.includes('help now') || text.includes('asap') || text.includes('staff')) {
        return { html: "Emergency support:<br><a href='tel:+254722729198' style='color:#6B3E93;font-weight:600;'>Call Staff: +254 722 729 198</a><br><a href='tel:+254743187154' style='color:#6B3E93;font-weight:600;'>Alternative: +254 743 187 154</a><br><a href='https://wa.me/254722729198' target='_blank' style='color:#377D3E;font-weight:600;'>WhatsApp Customer Care</a><br><a href='contact.php' style='color:#377D3E;font-weight:600;'>Open Contact Page</a>", isHtml: true };
      }
      if (text.includes('contact') || text.includes('phone') || text.includes('email')) return { html: 'Call 0722 729 198 / 0743 187 154 or email realitydreamacademy@gmail.com.', isHtml: false };
      if (text.includes('enroll') || text.includes('register')) return { html: 'Go to enroll_redirect.php to submit an enrollment form.', isHtml: false };
      return { html: "I can connect you to customer care. If it's urgent, type: emergency.", isHtml: false };
    }

    function sendMessage() {
      const message = (userInput.value || '').trim();
      if (!message) return;
      appendChatMessage(message, 'user');
      userInput.value = '';
      setTimeout(() => {
        const reply = getBotReply(message);
        appendChatMessage(reply.html, 'bot', reply.isHtml);
      }, 300);
    }

    chatToggle?.addEventListener('click', function() {
      chatbot.classList.toggle('open');
      if (chatbot.classList.contains('open')) userInput?.focus();
    });
    closeChat?.addEventListener('click', function() { chatbot.classList.remove('open'); });
    userInput?.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') { e.preventDefault(); sendMessage(); }
    });
  </script>
</body>
</html>


