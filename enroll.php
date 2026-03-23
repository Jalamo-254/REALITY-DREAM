<?php
require_once 'db_config.php';
require_once 'mail_config.php';
$response = ['success' => false, 'message' => '', 'errors' => []];
$formData = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'course' => '',
    'study_mode' => '',
    'intake_month' => '',
    'notes' => ''
];

if (isset($_GET['enrolled']) && $_GET['enrolled'] === '1') {
    $response['success'] = true;
    $response['message'] = "Enrollment successful! We'll contact you soon.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $studyMode = trim($_POST['study_mode'] ?? '');
    $intakeMonth = trim($_POST['intake_month'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $formData = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'course' => $course,
        'study_mode' => $studyMode,
        'intake_month' => $intakeMonth,
        'notes' => $notes
    ];

    if (!$name) {
        $response['errors']['name'] = "Name is required";
    }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = "Valid email required";
    }
    if (!$phone) {
        $response['errors']['phone'] = "Phone required";
    } elseif (!preg_match('/^[0-9\s\-\+\(\)]{7,}$/', $phone)) {
        $response['errors']['phone'] = "Enter a valid phone number";
    }
    if (!$course) {
        $response['errors']['course'] = "Please select a course";
    }
    if (!$studyMode) {
        $response['errors']['study_mode'] = "Please select a study mode";
    }
    if (!$intakeMonth) {
        $response['errors']['intake_month'] = "Please select intake month";
    }
    if (strlen($notes) > 1000) {
        $response['errors']['notes'] = "Notes cannot exceed 1000 characters";
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
        $stmt = $conn->prepare("INSERT INTO enrollments (name,email,phone,course,study_mode,intake_month,notes,attachment,status,submitted_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
        $status = 'New';
        $stmt->bind_param("sssssssss", $name, $email, $phone, $course, $studyMode, $intakeMonth, $notes, $attachmentPath, $status);
        if ($stmt->execute()) {
            $adminRecipients = get_admin_contact_emails();
            $subject = "New Enrollment Submission from {$name}";
            $adminBody = "
            <html>
            <body style='font-family: Arial, sans-serif; color:#1f2937;'>
                <h2 style='margin:0 0 12px;'>New Enrollment Submission</h2>
                <p><strong>Name:</strong> {$name}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Phone:</strong> {$phone}</p>
                <p><strong>Course:</strong> {$course}</p>
                <p><strong>Study Mode:</strong> {$studyMode}</p>
                <p><strong>Preferred Intake:</strong> {$intakeMonth}</p>
                <p><strong>Notes:</strong><br>" . nl2br(htmlspecialchars($notes, ENT_QUOTES, 'UTF-8')) . "</p>
                <p><strong>Attachment:</strong> " . ($attachmentPath ?: 'None') . "</p>
                <p style='color:#6b7280;font-size:12px;'>Submitted on: " . date('Y-m-d H:i:s') . "</p>
            </body>
            </html>";

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . (getenv('MAIL_FROM') ?: 'realitydreamacademy@gmail.com') . "\r\n";
            $headers .= "Reply-To: {$email}\r\n";

            $adminSent = false;
            foreach ($adminRecipients as $adminEmail) {
                $sent = send_site_mail($adminEmail, $subject, $adminBody, $headers);
                $adminSent = $adminSent || $sent;
            }

            $userSubject = "We received your enrollment - Reality Dream Institute";
            $userBody = "
            <html>
            <body style='font-family: Arial, sans-serif; color:#1f2937;'>
                <h2>Thank you for enrolling!</h2>
                <p>Dear {$name},</p>
                <p>We have received your enrollment request for <strong>{$course}</strong> and our team will contact you soon.</p>
                <p><strong>Study Mode:</strong> {$studyMode}<br><strong>Preferred Intake:</strong> {$intakeMonth}</p>
                <p>Best regards,<br><strong>Reality Dream Institute Team</strong></p>
            </body>
            </html>";
            $userHeaders = "MIME-Version: 1.0\r\n";
            $userHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
            $userHeaders .= "From: " . (getenv('MAIL_FROM') ?: 'realitydreamacademy@gmail.com') . "\r\n";
            send_site_mail($email, $userSubject, $userBody, $userHeaders);

            if (!$adminSent) {
                error_log('Enrollment saved but admin notification email failed for submission from: ' . $email);
            }
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
                    <div class="relative group">
                        <a href="programs.php" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm inline-flex items-center">Courses <i class="fas fa-chevron-down ml-1.5 text-[10px]"></i></a>
                        <div class="absolute left-0 top-full mt-2 w-56 bg-slate-900 border border-slate-700 rounded-lg shadow-xl p-2 hidden group-hover:block group-focus-within:block z-50">
                            <a href="programs.php#cctv-installation" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">CCTV Installation</a>
                            <a href="programs.php#solar-installation" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Solar Installation</a>
                            <a href="programs.php#entrepreneurship" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Entrepreneurship</a>
                            <a href="programs.php#front-desk-cashier" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Front Desk & Cashier</a>
                            <a href="programs.php#computer-packages" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Computer Packages</a>
                            <a href="programs.php#content-videography" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Content & Videography</a>
                        </div>
                    </div>
                    <a href="gallery.html" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm">Gallery</a>
                    <div class="relative group">
                        <a href="shop.html" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm inline-flex items-center">Shop <i class="fas fa-chevron-down ml-1.5 text-[10px]"></i></a>
                        <div class="absolute left-0 top-full mt-2 w-56 bg-slate-900 border border-slate-700 rounded-lg shadow-xl p-2 hidden group-hover:block group-focus-within:block z-50">
                            <a href="shop.html#cctv-cameras" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">CCTV Cameras</a>
                            <a href="shop.html#key-cutting" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Key Cuttings</a>
                            <a href="shop.html#wifi-installation" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">WiFi Installation</a>
                            <a href="shop.html#solar-panels" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Solar Panels</a>
                            <a href="shop.html#electric-fence" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Electric Fence</a>
                            <a href="shop.html#laser-sensors" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Laser Sensors</a>
                        </div>
                    </div>
                    <a href="blog.html" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Blog</a>
                    <a href="programs.php" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm">Fees</a>
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
                    <a href="programs.php" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Courses</a>
                    <a href="programs.php#cctv-installation" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- CCTV Installation</a>
                    <a href="programs.php#solar-installation" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Solar Installation</a>
                    <a href="programs.php#entrepreneurship" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Entrepreneurship</a>
                    <a href="programs.php#front-desk-cashier" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Front Desk & Cashier</a>
                    <a href="programs.php#computer-packages" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Computer Packages</a>
                    <a href="programs.php#content-videography" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Content & Videography</a>
                    <a href="gallery.html" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Gallery</a>
                    <a href="shop.html" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Shop</a>
                    <a href="shop.html#cctv-cameras" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- CCTV Cameras</a>
                    <a href="shop.html#key-cutting" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Key Cuttings</a>
                    <a href="shop.html#wifi-installation" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- WiFi Installation</a>
                    <a href="shop.html#solar-panels" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Solar Panels</a>
                    <a href="shop.html#electric-fence" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Electric Fence</a>
                    <a href="shop.html#laser-sensors" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Laser Sensors</a>
                    <a href="blog.html" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Blog</a>
                    <a href="programs.php" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Fees</a>
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-800 font-medium mb-1">Study Mode *</label>
                        <select name="study_mode" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo !empty($response['errors']['study_mode']) ? 'border-red-500' : 'border-gray-300'; ?>" required>
                            <option value="">Select mode</option>
                            <option value="Full-Time" <?php echo $formData['study_mode'] === 'Full-Time' ? 'selected' : ''; ?>>Full-Time</option>
                            <option value="Part-Time" <?php echo $formData['study_mode'] === 'Part-Time' ? 'selected' : ''; ?>>Part-Time</option>
                            <option value="Weekend" <?php echo $formData['study_mode'] === 'Weekend' ? 'selected' : ''; ?>>Weekend</option>
                            <option value="Online" <?php echo $formData['study_mode'] === 'Online' ? 'selected' : ''; ?>>Online</option>
                        </select>
                        <?php if (!empty($response['errors']['study_mode'])): ?><p class="text-red-500 text-sm"><?php echo $response['errors']['study_mode']; ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-gray-800 font-medium mb-1">Preferred Intake Month *</label>
                        <select name="intake_month" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo !empty($response['errors']['intake_month']) ? 'border-red-500' : 'border-gray-300'; ?>" required>
                            <option value="">Select month</option>
                            <option value="January" <?php echo $formData['intake_month'] === 'January' ? 'selected' : ''; ?>>January</option>
                            <option value="February" <?php echo $formData['intake_month'] === 'February' ? 'selected' : ''; ?>>February</option>
                            <option value="March" <?php echo $formData['intake_month'] === 'March' ? 'selected' : ''; ?>>March</option>
                            <option value="April" <?php echo $formData['intake_month'] === 'April' ? 'selected' : ''; ?>>April</option>
                            <option value="May" <?php echo $formData['intake_month'] === 'May' ? 'selected' : ''; ?>>May</option>
                            <option value="June" <?php echo $formData['intake_month'] === 'June' ? 'selected' : ''; ?>>June</option>
                            <option value="July" <?php echo $formData['intake_month'] === 'July' ? 'selected' : ''; ?>>July</option>
                            <option value="August" <?php echo $formData['intake_month'] === 'August' ? 'selected' : ''; ?>>August</option>
                            <option value="September" <?php echo $formData['intake_month'] === 'September' ? 'selected' : ''; ?>>September</option>
                            <option value="October" <?php echo $formData['intake_month'] === 'October' ? 'selected' : ''; ?>>October</option>
                            <option value="November" <?php echo $formData['intake_month'] === 'November' ? 'selected' : ''; ?>>November</option>
                            <option value="December" <?php echo $formData['intake_month'] === 'December' ? 'selected' : ''; ?>>December</option>
                        </select>
                        <?php if (!empty($response['errors']['intake_month'])): ?><p class="text-red-500 text-sm"><?php echo $response['errors']['intake_month']; ?></p><?php endif; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-800 font-medium mb-1">Additional Notes (optional)</label>
                    <textarea name="notes" rows="3" maxlength="1000" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo !empty($response['errors']['notes']) ? 'border-red-500' : 'border-gray-300'; ?>" placeholder="Any scheduling preference or extra details..."><?php echo htmlspecialchars($formData['notes']); ?></textarea>
                    <?php if (!empty($response['errors']['notes'])): ?><p class="text-red-500 text-sm"><?php echo $response['errors']['notes']; ?></p><?php endif; ?>
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

    <footer class="py-6 md:py-8" style="background-color: #121826; color: white;">
        <div class="container mx-auto px-4 md:px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 md:gap-6 footer-grid">
                <div>
                    <div class="flex items-start mb-3 md:mb-4">
                        <div class="flex-shrink-0 mr-2">
                            <img src="./logo.svg" alt="Reality Dream Institute Logo" class="w-8 h-8 md:w-10 md:h-10 rounded-lg">
                        </div>
                        <div>
                            <h2 class="text-base md:text-lg font-bold leading-tight">Reality Dream Institute</h2>
                            <p class="text-xs md:text-sm text-gray-300 mt-0.5">Business, Tech & Innovation Hub</p>
                        </div>
                    </div>
                    <p class="text-gray-300 text-sm md:text-base mb-3 leading-relaxed">
                        Empowering learners with practical technical skills for employment, entrepreneurship, and community transformation.
                    </p>
                    <div class="space-y-1">
                        <div class="flex items-start">
                            <i class="fas fa-map-marker-alt mr-2 mt-0.5 text-gray-400 text-xs flex-shrink-0"></i>
                            <p class="text-gray-300 text-sm md:text-base">Kilifi Town, Ar Rayan Complex</p>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-phone mr-2 mt-0.5 text-gray-400 text-xs flex-shrink-0"></i>
                            <div class="text-sm md:text-base">
                                <a href="tel:+254722729198" class="text-gray-300 hover:text-green-300 transition">0722 729 198</a>
                                <span class="text-gray-500 mx-1">|</span>
                                <a href="tel:+254743187154" class="text-gray-300 hover:text-green-300 transition">0743 187 154</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-base md:text-lg font-bold mb-2 md:mb-4">Quick Links</h3>
                    <ul class="space-y-1 md:space-y-2">
                        <li><a href="index.html" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Home</a></li>
                        <li><a href="about.html" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">About Us</a></li>
                        <li><a href="programs.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Courses</a></li>
                        <li><a href="programs.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Course Fees</a></li>
                        <li><a href="contact.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-base md:text-lg font-bold mb-2 md:mb-4">Popular Courses</h3>
                    <ul class="space-y-1 md:space-y-2">
                        <li><a href="programs.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">CCTV Installation Training</a></li>
                        <li><a href="programs.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Solar Installation Training</a></li>
                        <li><a href="programs.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Entrepreneurship Training</a></li>
                        <li><a href="programs.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Computer Packages</a></li>
                        <li><a href="programs.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Content Creation & Videography</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-base md:text-lg font-bold mb-2 md:mb-4">Newsletter</h3>
                    <p class="text-gray-300 text-sm md:text-base mb-2 md:mb-3 leading-relaxed">
                        Subscribe to get updates on new courses and offers.
                    </p>
                    <div class="flex mb-3 md:mb-4">
                        <input type="email" placeholder="Your email" class="flex-grow p-2 rounded-l-md text-gray-800 focus:outline-none text-sm md:text-base w-full">
                        <button class="px-3 md:px-4 rounded-r-md font-medium text-xs md:text-sm whitespace-nowrap" style="background-color: #377D3E;">
                            Subscribe
                        </button>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <i class="fas fa-envelope mr-2 text-gray-400 text-xs flex-shrink-0"></i>
                            <p class="text-gray-300 text-sm md:text-base break-all">realitydreamacademy@gmail.com</p>
                        </div>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <a href="https://www.facebook.com/realitydreamacademy" target="_blank" aria-label="Facebook" class="w-9 h-9 rounded-full text-gray-200 flex items-center justify-center hover:text-white transition" style="background: linear-gradient(135deg,#1877f2,#0d5fcb);">
                                <i class="fab fa-facebook-f text-sm"></i>
                            </a>
                            <a href="https://www.instagram.com/realitydreamacademy" target="_blank" aria-label="Instagram" class="w-9 h-9 rounded-full text-white flex items-center justify-center hover:opacity-90 transition" style="background: linear-gradient(135deg,#f58529,#dd2a7b,#8134af,#515bd4);">
                                <i class="fab fa-instagram text-sm"></i>
                            </a>
                            <a href="https://www.twitter.com/realitydreamacademy" target="_blank" aria-label="X / Twitter" class="w-9 h-9 rounded-full text-white flex items-center justify-center hover:opacity-90 transition border border-white/30 shadow-sm" style="background: linear-gradient(135deg,#000000,#1f2937);">
                                <span class="font-bold text-xs tracking-wide">X</span>
                            </a>
                            <a href="https://wa.me/254722729198" target="_blank" aria-label="WhatsApp" class="w-9 h-9 rounded-full text-white flex items-center justify-center hover:opacity-90 transition" style="background: linear-gradient(135deg,#25D366,#128C7E);">
                                <i class="fab fa-whatsapp text-sm"></i>
                            </a>
                        </div>
                        <div class="pt-3 space-y-2">
                            <p class="text-xs uppercase tracking-wider text-gray-400 font-semibold">Quick Contact</p>
                            <div class="bg-gray-800/60 rounded-lg p-2.5 border border-gray-700">
                                <p class="text-xs text-gray-300 mb-2">0722 729 198</p>
                                <div class="flex gap-2">
                                    <a href="tel:+254722729198" class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-xs font-semibold text-white transition w-full" style="background-color:#6B3E93;">
                                        <i class="fas fa-phone mr-1.5"></i>Call
                                    </a>
                                    <a href="https://wa.me/254722729198" target="_blank" class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-xs font-semibold text-white transition w-full" style="background-color:#377D3E;">
                                        <i class="fab fa-whatsapp mr-1.5"></i>WhatsApp
                                    </a>
                                </div>
                            </div>
                            <div class="bg-gray-800/60 rounded-lg p-2.5 border border-gray-700">
                                <p class="text-xs text-gray-300 mb-2">0743 187 154</p>
                                <div class="flex gap-2">
                                    <a href="tel:+254743187154" class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-xs font-semibold text-white transition w-full" style="background-color:#6B3E93;">
                                        <i class="fas fa-phone mr-1.5"></i>Call
                                    </a>
                                    <a href="https://wa.me/254743187154" target="_blank" class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-xs font-semibold text-white transition w-full" style="background-color:#377D3E;">
                                        <i class="fab fa-whatsapp mr-1.5"></i>WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-4 md:mt-6 pt-4 md:pt-6 text-center">
                <p class="text-gray-300 text-sm md:text-base">
                    &copy; <span id="current-year">2024</span> Reality Dream Institute. All rights reserved. | Dream It, Build It.
                </p>
                <p class="mt-1 text-gray-400 text-xs md:text-sm">
                    Business, Tech & Innovation Hub | Kilifi Town, Kenya
                </p>
            </div>
        </div>
    </footer>

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
                <button onclick="printPdf()" class="flex items-center gap-2 px-4 py-2 text-white font-medium rounded-md transition" style="background-color:#6B3E93;">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
                <a href="./Bronchure.pdf" download class="flex items-center gap-2 px-4 py-2 text-white font-medium rounded-md transition" style="background-color:#377D3E;">
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
            if (text.includes('contact') || text.includes('phone') || text.includes('email')) return { html: 'Call 0722 729 198 / 0743 187 154 or email realitydreamacademy@gmail.com.', isHtml: false };
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




