<?php
require_once 'db_config.php';
$response = ['success' => false, 'message' => '', 'errors' => []];
$formData = ['name' => '', 'email' => '', 'phone' => '', 'course' => ''];

if (isset($_GET['enrolled']) && $_GET['enrolled'] === '1') {
    $response['success'] = true;
    $response['message'] = "Enrollment successful! We'll contact you soon.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $formData = ['name' => $name, 'email' => $email, 'phone' => $phone, 'course' => $course];

    if (!$name) {
        $response['errors']['name'] = "Name is required";
    }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = "Valid email required";
    }
    if (!$phone) {
        $response['errors']['phone'] = "Phone required";
    }
    if (!$course) {
        $response['errors']['course'] = "Please select a course";
    }

    $attachmentPath = null;
    if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['attachment'];
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['error'] === UPLOAD_ERR_OK && in_array($ext, $allowed, true) && $file['size'] <= $maxSize) {
            $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
            $target = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $safeName;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $attachmentPath = 'uploads/' . $safeName;
            } else {
                $response['errors']['attachment'] = "Failed to upload file";
            }
        } else {
            $response['errors']['attachment'] = "Invalid file. Allowed: PDF, DOC, DOCX, JPG, JPEG, PNG (max 5MB)";
        }
    }

    if (empty($response['errors'])) {
        $stmt = $conn->prepare("INSERT INTO enrollments (name,email,phone,course,attachment,submitted_at) VALUES (?,?,?,?,?,NOW())");
        $stmt->bind_param("sssss", $name, $email, $phone, $course, $attachmentPath);
        if ($stmt->execute()) {
            header('Location: enroll.php?enrolled=1');
            exit;
        } else {
            $response['message'] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reality Dream Institute | Enroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
<body style="font-family: 'Poppins', sans-serif; background-color: #F8F9FA; color: #333333; overflow-x: hidden;">
    <nav class="sticky top-0 z-50 bg-transparent" style="background-color: #121826;">
        <div class="container mx-auto px-4 py-2">
            <div class="flex flex-wrap justify-between items-center">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center">
                        <img src="./logo.jpg" alt="Reality Dream Institute Logo" class="w-6 h-6 rounded-full object-cover">
                    </div>
                    <div>
                        <h1 class="text-md md:text-lg font-bold" style="background: linear-gradient(90deg, #377D3E, #6B3E93, #E38822); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700;">Reality Dream Institute</h1>
                        <p class="text-xs text-gray-300 mobile-hidden">Business, Tech & Innovation Hub</p>
                    </div>
                </div>

                <div class="hidden lg:flex space-x-5 xl:space-x-6 items-center">
                    <a href="index.html#home" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm">Home</a>
                    <a href="about.html" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm">About</a>
                    <a href="index.html#courses" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm">Courses</a>
                    <a href="blog.html" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Blog</a>
                    <a href="index.html#fees" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm">Fees</a>
                    <a href="contact.php" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm">Contact</a>
                    <div class="flex items-center space-x-1 md:space-x-2 ml-2 md:ml-3 pl-2 md:pl-3 border-l border-gray-700">
                        <button onclick="openPdfViewer()" class="btn-download transition-all bg-tint-green hover:bg-green-100 text-green border border-green-200 font-medium py-1.5 px-2 md:px-3 rounded-md flex items-center space-x-1 text-xs" style="background-color: #f0f8f0; color: #377D3E; border-color: #377D3E; transition: all 0.3s ease;">
                            <i class="fas fa-file-pdf text-xs"></i>
                            <span class="hidden sm:inline">Brochure</span>
                        </button>
                        <button onclick="window.location.href='enroll.php'" class="btn-enroll-nav transition-all bg-purple hover:bg-purple-600 text-white font-semibold py-1.5 px-2 md:px-3 rounded-md flex items-center space-x-1 text-xs" style="background-color: #6B3E93; transition: all 0.3s ease;">
                            <i class="fas fa-user-graduate text-xs"></i>
                            <span class="hidden sm:inline">Enroll</span>
                        </button>
                    </div>
                </div>

                <div class="lg:hidden flex items-center">
                    <button id="mobile-menu-button" class="text-gray-300 focus:outline-none">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                </div>
            </div>

            <div id="mobile-menu" class="lg:hidden hidden mt-3 pb-2">
                <div class="flex flex-col space-y-3">
                    <a href="index.html#home" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Home</a>
                    <a href="about.html" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">About</a>
                    <a href="index.html#courses" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Courses</a>
                    <a href="blog.html" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Blog</a>
                    <a href="index.html#fees" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Fees</a>
                    <a href="contact.php" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Contact</a>
                    <div class="nav-buttons flex flex-col space-y-2 pt-3 border-t border-gray-700">
                        <button onclick="openPdfViewer()" class="btn-download transition-all bg-tint-green hover:bg-green-100 text-green border border-green-200 font-medium py-2 rounded-md flex items-center justify-center space-x-2 text-sm" style="background-color: #f0f8f0; color: #377D3E; border-color: #377D3E; transition: all 0.3s ease;">
                            <i class="fas fa-file-pdf"></i>
                            <span>Download Brochure</span>
                        </button>
                        <button onclick="window.location.href='enroll.php'" class="btn-enroll-nav transition-all bg-purple hover:bg-purple-600 text-white font-semibold py-2 rounded-md flex items-center justify-center space-x-2 text-sm" style="background-color: #6B3E93; transition: all 0.3s ease;">
                            <i class="fas fa-user-graduate"></i>
                            <span>Enroll Now</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section class="relative py-16 md:py-20 bg-cover bg-center bg-no-repeat" style="background-image: url('Assets/images/enrol.jpg');">
        <div class="absolute inset-0 bg-black/55"></div>
        <div class="relative container mx-auto px-4 md:px-6 max-w-3xl">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-white">Enroll Now</h2>
            <p class="text-center text-gray-200 mb-8">Fill the form below and start your journey today!</p>

            <?php if ($response['success']): ?>
                <div class="bg-green-100 text-green-800 p-4 rounded mb-4"><?php echo $response['message']; ?></div>
            <?php elseif (!empty($response['message'])): ?>
                <div class="bg-red-100 text-red-800 p-4 rounded mb-4"><?php echo $response['message']; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="bg-white/90 backdrop-blur-md p-6 md:p-8 rounded-2xl shadow-2xl border border-white/40 space-y-4 md:space-y-5">
                <div>
                    <label class="block text-gray-800 font-medium mb-1">Full Name *</label>
                    <input type="text" name="name" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo !empty($response['errors']['name']) ? 'border-red-500' : 'border-gray-300'; ?>" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                    <?php if (!empty($response['errors']['name'])): ?><p class="text-red-500 text-sm"><?php echo $response['errors']['name']; ?></p><?php endif; ?>
                </div>

                <div>
                    <label class="block text-gray-800 font-medium mb-1">Email *</label>
                    <input type="email" name="email" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo !empty($response['errors']['email']) ? 'border-red-500' : 'border-gray-300'; ?>" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                    <?php if (!empty($response['errors']['email'])): ?><p class="text-red-500 text-sm"><?php echo $response['errors']['email']; ?></p><?php endif; ?>
                </div>

                <div>
                    <label class="block text-gray-800 font-medium mb-1">Phone *</label>
                    <input type="tel" name="phone" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo !empty($response['errors']['phone']) ? 'border-red-500' : 'border-gray-300'; ?>" value="<?php echo htmlspecialchars($formData['phone']); ?>" required>
                    <?php if (!empty($response['errors']['phone'])): ?><p class="text-red-500 text-sm"><?php echo $response['errors']['phone']; ?></p><?php endif; ?>
                </div>

                <div>
                    <label class="block text-gray-800 font-medium mb-1">Course *</label>
                    <select name="course" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo !empty($response['errors']['course']) ? 'border-red-500' : 'border-gray-300'; ?>" required>
                        <option value="">Select a course</option>
                        <option value="CCTV Installation" <?php echo $formData['course'] === 'CCTV Installation' ? 'selected' : ''; ?>>CCTV Installation</option>
                        <option value="Solar Installation" <?php echo $formData['course'] === 'Solar Installation' ? 'selected' : ''; ?>>Solar Installation</option>
                        <option value="Entrepreneurship" <?php echo $formData['course'] === 'Entrepreneurship' ? 'selected' : ''; ?>>Entrepreneurship</option>
                        <option value="Front Desk & Cashier" <?php echo $formData['course'] === 'Front Desk & Cashier' ? 'selected' : ''; ?>>Front Desk & Cashier</option>
                        <option value="Computer Packages" <?php echo $formData['course'] === 'Computer Packages' ? 'selected' : ''; ?>>Computer Packages</option>
                        <option value="Content Creation & Videography" <?php echo $formData['course'] === 'Content Creation & Videography' ? 'selected' : ''; ?>>Content Creation & Videography</option>
                    </select>
                    <?php if (!empty($response['errors']['course'])): ?><p class="text-red-500 text-sm"><?php echo $response['errors']['course']; ?></p><?php endif; ?>
                </div>

                <div>
                    <label class="block text-gray-800 font-medium mb-1">Upload File (optional, for more information)</label>
                    <input type="file" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="w-full p-2 border border-gray-300 rounded-lg bg-white">
                    <p class="text-gray-600 text-sm mt-1">Allowed: PDF, DOC, DOCX, JPG, JPEG, PNG - Max 5MB</p>
                    <?php if (!empty($response['errors']['attachment'])): ?><p class="text-red-500 text-sm"><?php echo $response['errors']['attachment']; ?></p><?php endif; ?>
                </div>

                <button type="submit" class="w-full bg-purple-700 text-white py-3 rounded-lg hover:bg-purple-800 transition font-semibold tracking-wide">Enroll Now</button>
            </form>
        </div>
    </section>

    <div id="pdfModal" class="hidden fixed inset-0 bg-black bg-opacity-70 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl h-full max-h-screen flex flex-col">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-bold text-gray-800">Reality Dream Institute - Brochure</h2>
                <button onclick="closePdfViewer()" class="text-gray-500 hover:text-gray-800 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-auto">
                <iframe id="pdfFrame" src="./Bronchure.pdf" type="application/pdf" class="w-full h-full" style="border: none;"></iframe>
            </div>
            <div class="flex justify-end items-center gap-3 p-4 border-t border-gray-200 bg-gray-50">
                <button onclick="printPdf()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
                <a href="./Bronchure.pdf" download class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md transition">
                    <i class="fas fa-download"></i>
                    <span>Download</span>
                </a>
                <button onclick="closePdfViewer()" class="flex items-center gap-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-md transition">
                    <i class="fas fa-times"></i>
                    <span>Close</span>
                </button>
            </div>
        </div>
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
        const mobileMenuBtn = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function () {
                mobileMenu.classList.toggle('hidden');
            });
        }

        function openPdfViewer() {
            const pdfModal = document.getElementById('pdfModal');
            pdfModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closePdfViewer() {
            const pdfModal = document.getElementById('pdfModal');
            pdfModal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function printPdf() {
            const pdfFrame = document.getElementById('pdfFrame');
            pdfFrame.contentWindow.print();
        }

        document.getElementById('pdfModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closePdfViewer();
            }
        });

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
            if (text.includes('course')) return { html: 'We offer CCTV, Solar, Entrepreneurship, Front Desk, Computer Packages, and Content Creation.', isHtml: false };
            if (text.includes('fee') || text.includes('price') || text.includes('cost')) return { html: 'Please check the Fees section on the home page for current course fees.', isHtml: false };
            if (text.includes('contact') || text.includes('phone') || text.includes('email')) return { html: 'Call 0722 729 198 / 0743 187 154 or email realitydreaminternational@gmail.com.', isHtml: false };
            if (text.includes('enroll') || text.includes('register')) return { html: 'Use this page to complete enrollment.', isHtml: false };
            return { html: "I can help with courses, fees, contact, and enrollment. If it's urgent, type: emergency.", isHtml: false };
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
<?php $conn->close(); ?>
