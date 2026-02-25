<?php
// ============================================
// Contact Form Backend Handler
// ============================================

// Include database configuration
require_once 'db_config.php';

$response = ['success' => false, 'message' => '', 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    // Sanitize and validate inputs
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($firstName)) {
        $response['errors']['first_name'] = 'First name is required';
    } elseif (strlen($firstName) < 2) {
        $response['errors']['first_name'] = 'First name must be at least 2 characters';
    }
    
    if (empty($lastName)) {
        $response['errors']['last_name'] = 'Last name is required';
    } elseif (strlen($lastName) < 2) {
        $response['errors']['last_name'] = 'Last name must be at least 2 characters';
    }
    
    if (empty($phone)) {
        $response['errors']['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9\s\-\+\(\)]{7,}$/', $phone)) {
        $response['errors']['phone'] = 'Please enter a valid phone number';
    }
    
    if (empty($email)) {
        $response['errors']['email'] = 'Email address is required';
    } elseif (preg_match('/\s/', $email)) {
        $response['errors']['email'] = 'Email address cannot contain spaces';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = 'Please enter a valid email address';
    }
    
    if (empty($course)) {
        $response['errors']['course'] = 'Please select a course';
    }
    
    if (empty($message)) {
        $response['errors']['message'] = 'Message is required';
    } elseif (strlen($message) < 10) {
        $response['errors']['message'] = 'Message must be at least 10 characters';
    }
    
    // If no errors, process the form
    if (empty($response['errors'])) {
        // Sanitize data to prevent injection
        $firstName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
        $lastName = htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        
        // Handle attachment upload (optional)
        $attachmentPath = null;
        if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['attachment'];
            $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($file['error'] === UPLOAD_ERR_OK && in_array($ext, $allowed) && $file['size'] <= $maxSize) {
                $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
                $target = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $safeName;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $attachmentPath = 'uploads/' . $safeName;
                }
            } else {
                error_log('Attachment upload failed or invalid file');
            }
        }

        // Insert data into database (including optional attachment)
        $insertSuccess = false;
        $insertSQL = "INSERT INTO contacts (first_name, last_name, email, phone, course, message, attachment) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($insertSQL);
        if ($stmt) {
            $stmt->bind_param("sssssss", $firstName, $lastName, $email, $phone, $course, $message, $attachmentPath);
            $insertSuccess = $stmt->execute();
            $stmt->close();
            
            if (!$insertSuccess) {
                error_log("Database insert error: " . $conn->error);
            }
        }
        
        // Prepare email to admin
        $to = 'realitydreaminternational@gmail.com';
        $subject = "New Contact Form Submission from {$firstName} {$lastName}";
        
        $emailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; }
                .header { background-color: #6B3E93; color: white; padding: 15px; border-radius: 5px 5px 0 0; }
                .field { margin: 15px 0; }
                .label { font-weight: bold; color: #6B3E93; }
                .value { padding: 8px; background-color: #f9f9f9; border-left: 3px solid #377D3E; padding-left: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'><h2>New Contact Form Submission</h2></div>
                <div class='field'>
                    <div class='label'>Name:</div>
                    <div class='value'>{$firstName} {$lastName}</div>
                </div>
                <div class='field'>
                    <div class='label'>Email:</div>
                    <div class='value'><a href='mailto:{$email}'>{$email}</a></div>
                </div>
                <div class='field'>
                    <div class='label'>Phone:</div>
                    <div class='value'>{$phone}</div>
                </div>
                <div class='field'>
                    <div class='label'>Course Interested In:</div>
                    <div class='value'>{$course}</div>
                </div>
                <div class='field'>
                    <div class='label'>Message:</div>
                    <div class='value'>{$message}</div>
                </div>
                <div class='field' style='margin-top: 20px; color: #999; font-size: 12px;'>
                    Submitted on: " . date('Y-m-d H:i:s') . "
                </div>
            </div>
        </body>
        </html>";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: realitydreaminternational@gmail.com" . "\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        
        // Send email to admin (use helper if available)
        require_once 'mail_config.php';
        $mailSent = send_site_mail($to, $subject, $emailBody, $headers);
        
        if ($mailSent) {
            $response['success'] = true;
            $response['message'] = 'Thank you! Your message has been sent successfully. We will get back to you soon.';
            
            // Send confirmation email to user
            $userSubject = "We received your message - Reality Dream Institute";
            $userBody = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Thank you for contacting us!</h2>
                <p>Dear {$firstName},</p>
                <p>We have received your message and will respond to you shortly.</p>
                <p><strong>Your inquiry details:</strong></p>
                <p>Course: {$course}</p>
                <p>We appreciate your interest in Reality Dream Institute.</p>
                <p>Best regards,<br><strong>Reality Dream Institute Team</strong></p>
            </body>
            </html>";
            
            $userHeaders = "MIME-Version: 1.0" . "\r\n";
            $userHeaders .= "Content-type: text/html; charset=UTF-8" . "\r\n";
            $userHeaders .= "From: realitydreaminternational@gmail.com\r\n";
            
            send_site_mail($email, $userSubject, $userBody, $userHeaders);
        } else {
            // Keep submission successful if saved in DB, even when mail transport fails.
            if ($insertSuccess) {
                $response['success'] = true;
                $response['message'] = 'Your message was received and saved. Email notification is temporarily unavailable.';
            } else {
                $response['success'] = false;
                $response['message'] = 'Sorry, there was an error sending your message. Please try again later.';
            }
        }
    } else {
        $response['message'] = 'Please fix the errors below and try again.';
    }
}
?>

<!-- Contact Section -->
    <section id="contact" class="py-10 md:py-14 section-padding" style="background-color: #F8F9FA;">
        <div class="container mx-auto px-4 md:px-6">
            <div class="text-center mb-6 md:mb-10">
                <h2 class="text-2xl md:text-3xl lg:text-4xl font-bold text-gray-800 mb-3 md:mb-4 section-title">Contact Us</h2>
                <div class="w-20 md:w-24 h-1 bg-gradient-to-r from-green to-purple mx-auto"></div>
                <p class="text-gray-600 mt-4 md:mt-6 max-w-3xl mx-auto text-sm md:text-base">
                    Have questions about our courses? Get in touch with us for more information.
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8 contact-grid">
                <div>
                    <h3 class="text-lg md:text-xl font-bold mb-3 md:mb-4">Get in Touch</h3>
                    
                    <div class="space-y-3 md:space-y-4">
                        <div class="flex items-start">
                            <div class="w-8 h-8 md:w-10 md:h-10 rounded-full flex items-center justify-center mr-2 md:mr-3 flex-shrink-0" style="background-color: #f0f8f0; color: #377D3E;">
                                <i class="fas fa-map-marker-alt text-base md:text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-base md:text-lg mb-0.5">Our Location</h4>
                                <p class="text-gray-600 text-sm md:text-base">Killfi Town, Ar Rayan Complex<br>Opp Titanic Building<br>Ground Floor Door 7</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-8 h-8 md:w-10 md:h-10 rounded-full flex items-center justify-center mr-2 md:mr-3 flex-shrink-0" style="background-color: #f5f0f9; color: #6B3E93;">
                                <i class="fas fa-phone-alt text-base md:text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-base md:text-lg mb-0.5">Phone Numbers</h4>
                                <p class="text-gray-600 text-sm md:text-base">0722 729 198<br>0743 187 154</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-8 h-8 md:w-10 md:h-10 rounded-full flex items-center justify-center mr-2 md:mr-3 flex-shrink-0" style="background-color: #fdf4e7; color: #E38822;">
                                <i class="fas fa-envelope text-base md:text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-base md:text-lg mb-0.5">Email Address</h4>
                                <p class="text-gray-600 text-sm md:text-base">realitydreaminternational@gmail.com</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 md:mt-6">
                        <h4 class="font-bold text-base md:text-lg mb-2 md:mb-3">Business Hours</h4>
                        <p class="text-gray-600 text-sm md:text-base">Monday - Friday: 8:00 AM - 5:00 PM<br>Saturday: 9:00 AM - 1:00 PM<br>Sunday: Closed</p>
                    </div>
                </div>
                
                <div class="info-card p-5 md:p-6 rounded-xl shadow-lg mobile-padding" style="background-color: #FFFFFF; border-radius: 0.75rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
                    <h3 class="text-lg md:text-xl font-bold mb-3 md:mb-4">Send Us a Message</h3>
                    
                    <?php if ($response['success']): ?>
                        <div class="mb-4 p-4 rounded-lg" style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724;">
                            <i class="fas fa-check-circle mr-2"></i>
                            <?php echo $response['message']; ?>
                        </div>
                    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($response['message'])): ?>
                        <div class="mb-4 p-4 rounded-lg" style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?php echo $response['message']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form id="contact-form" method="POST" action="" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 md:gap-3 mb-2 md:mb-3">
                            <div>
                                <label class="block text-gray-700 mb-1 md:mb-1 text-sm md:text-base">First Name <span style="color: #d32f2f;">*</span></label>
                                <input type="text" name="first_name" class="w-full p-2.5 border rounded-md focus:outline-none focus:ring-2 focus:ring-green text-sm md:text-base <?php echo !empty($response['errors']['first_name']) ? 'border-red-500' : 'border-gray-300'; ?>" 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES); ?>" required>
                                <?php if (!empty($response['errors']['first_name'])): ?>
                                    <p class="text-red-500 text-xs mt-1"><i class="fas fa-times-circle"></i> <?php echo $response['errors']['first_name']; ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-1 md:mb-1 text-sm md:text-base">Last Name <span style="color: #d32f2f;">*</span></label>
                                <input type="text" name="last_name" class="w-full p-2.5 border rounded-md focus:outline-none focus:ring-2 focus:ring-green text-sm md:text-base <?php echo !empty($response['errors']['last_name']) ? 'border-red-500' : 'border-gray-300'; ?>" 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES); ?>" required>
                                <?php if (!empty($response['errors']['last_name'])): ?>
                                    <p class="text-red-500 text-xs mt-1"><i class="fas fa-times-circle"></i> <?php echo $response['errors']['last_name']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-2 md:mb-3">
                            <label class="block text-gray-700 mb-1 md:mb-1 text-sm md:text-base">Phone Number <span style="color: #d32f2f;">*</span></label>
                            <input type="tel" name="phone" class="w-full p-2.5 border rounded-md focus:outline-none focus:ring-2 focus:ring-green text-sm md:text-base <?php echo !empty($response['errors']['phone']) ? 'border-red-500' : 'border-gray-300'; ?>" 
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES); ?>" required>
                            <?php if (!empty($response['errors']['phone'])): ?>
                                <p class="text-red-500 text-xs mt-1"><i class="fas fa-times-circle"></i> <?php echo $response['errors']['phone']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-2 md:mb-3">
                            <label class="block text-gray-700 mb-1 md:mb-1 text-sm md:text-base">Email Address <span style="color: #d32f2f;">*</span></label>
                            <input type="email" name="email" class="w-full p-2.5 border rounded-md focus:outline-none focus:ring-2 focus:ring-green text-sm md:text-base <?php echo !empty($response['errors']['email']) ? 'border-red-500' : 'border-gray-300'; ?>" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>"
                                   inputmode="email"
                                   autocomplete="email"
                                   pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$"
                                   oninput="this.value=this.value.replace(/\s+/g,'');"
                                   required>
                            <?php if (!empty($response['errors']['email'])): ?>
                                <p class="text-red-500 text-xs mt-1"><i class="fas fa-times-circle"></i> <?php echo $response['errors']['email']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-2 md:mb-3">
                            <label class="block text-gray-700 mb-1 md:mb-1 text-sm md:text-base">Course Interested In <span style="color: #d32f2f;">*</span></label>
                            <select name="course" class="w-full p-2.5 border rounded-md focus:outline-none focus:ring-2 focus:ring-green text-sm md:text-base <?php echo !empty($response['errors']['course']) ? 'border-red-500' : 'border-gray-300'; ?>" required>
                                <option value="">Select a course</option>
                                <option value="CCTV Installation Training" <?php echo ($_POST['course'] ?? '') === 'CCTV Installation Training' ? 'selected' : ''; ?>>CCTV Installation Training</option>
                                <option value="Solar Installation Training" <?php echo ($_POST['course'] ?? '') === 'Solar Installation Training' ? 'selected' : ''; ?>>Solar Installation Training</option>
                                <option value="Entrepreneurship Training" <?php echo ($_POST['course'] ?? '') === 'Entrepreneurship Training' ? 'selected' : ''; ?>>Entrepreneurship Training</option>
                                <option value="Front Desk & Cashier Training" <?php echo ($_POST['course'] ?? '') === 'Front Desk & Cashier Training' ? 'selected' : ''; ?>>Front Desk & Cashier Training</option>
                                <option value="Computer Packages" <?php echo ($_POST['course'] ?? '') === 'Computer Packages' ? 'selected' : ''; ?>>Computer Packages</option>
                                <option value="Content Creation & Videography" <?php echo ($_POST['course'] ?? '') === 'Content Creation & Videography' ? 'selected' : ''; ?>>Content Creation & Videography</option>
                            </select>
                            <?php if (!empty($response['errors']['course'])): ?>
                                <p class="text-red-500 text-xs mt-1"><i class="fas fa-times-circle"></i> <?php echo $response['errors']['course']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3 md:mb-4">
                            <label class="block text-gray-700 mb-1 md:mb-1 text-sm md:text-base">Message <span style="color: #d32f2f;">*</span></label>
                            <textarea name="message" rows="4" class="w-full p-2.5 border rounded-md focus:outline-none focus:ring-2 focus:ring-green text-sm md:text-base <?php echo !empty($response['errors']['message']) ? 'border-red-500' : 'border-gray-300'; ?>" required><?php echo htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES); ?></textarea>
                            <?php if (!empty($response['errors']['message'])): ?>
                                <p class="text-red-500 text-xs mt-1"><i class="fas fa-times-circle"></i> <?php echo $response['errors']['message']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3 md:mb-4">
                            <label class="block text-gray-700 mb-1 md:mb-1 text-sm md:text-base">Attachment (optional)</label>
                            <input type="file" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="w-full" />
                            <p class="text-gray-500 text-xs mt-1">Allowed: PDF, DOC, JPG, PNG — Max 5MB</p>
                        </div>
                        
                        <button type="submit" name="submit_contact" value="1" class="btn-enroll transition-all text-white font-semibold w-full py-2.5 rounded-md flex items-center justify-center space-x-2 text-sm md:text-base hover:shadow-lg" style="background-color: #6B3E93; transition: all 0.3s ease;">
                            <i class="fas fa-paper-plane text-sm"></i>
                            <span>Send Message</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

<?php
// Close database connection
$conn->close();
?>
