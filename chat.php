<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

/**
 * Read message from JSON body or form POST.
 */
function get_input_message(): string {
    $raw = file_get_contents('php://input');
    if ($raw !== false && $raw !== '') {
        $json = json_decode($raw, true);
        if (is_array($json) && isset($json['message'])) {
            return trim((string) $json['message']);
        }
    }
    return trim((string)($_POST['message'] ?? ''));
}

function contains_any(string $text, array $needles): bool {
    foreach ($needles as $needle) {
        if ($needle !== '' && strpos($text, $needle) !== false) {
            return true;
        }
    }
    return false;
}

$message = get_input_message();

if ($message === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Message is required.'
    ]);
    exit;
}

if (mb_strlen($message) > 500) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Message too long. Maximum 500 characters.'
    ]);
    exit;
}

$text = mb_strtolower($message, 'UTF-8');

$response = [
    'success' => true,
    'is_html' => false,
    'reply' => '',
    'escalate' => false,
    'actions' => []
];

if (contains_any($text, ['emergency', 'urgent', 'help now', 'asap', 'staff'])) {
    $response['reply'] = 'Emergency support available now. Use call or WhatsApp below.';
    $response['escalate'] = true;
    $response['actions'] = [
        ['label' => 'Call Staff 1', 'type' => 'phone', 'value' => '+254722729198', 'href' => 'tel:+254722729198'],
        ['label' => 'Call Staff 2', 'type' => 'phone', 'value' => '+254743187154', 'href' => 'tel:+254743187154'],
        ['label' => 'WhatsApp Customer Care', 'type' => 'whatsapp', 'value' => '+254722729198', 'href' => 'https://wa.me/254722729198'],
        ['label' => 'Open Contact Page', 'type' => 'link', 'value' => 'contact.php', 'href' => 'contact.php']
    ];
} elseif (contains_any($text, ['course', 'training', 'program'])) {
    $response['reply'] = 'We offer CCTV, Solar, Entrepreneurship, Front Desk, Computer Packages, and Content Creation training.';
} elseif (contains_any($text, ['fee', 'price', 'cost'])) {
    $response['reply'] = 'Please check the Fees section on the home page for the latest course fees.';
} elseif (contains_any($text, ['contact', 'phone', 'email'])) {
    $response['reply'] = 'Call 0722 729 198 / 0743 187 154 or email realitydreaminternational@gmail.com.';
} elseif (contains_any($text, ['location', 'where'])) {
    $response['reply'] = 'We are in Kilifi Town, Ar Rayan Complex, Opp Titanic Building, Ground Floor Door 7.';
} elseif (contains_any($text, ['enroll', 'register', 'admission'])) {
    $response['reply'] = 'Use the Enroll button in the menu to open the enrollment form and submit your details.';
} elseif (contains_any($text, ['brochure'])) {
    $response['reply'] = 'Use the Brochure button in the top navigation to view or download the brochure.';
} else {
    $response['reply'] = "I can help with courses, fees, location, contact, enrollment, and emergencies. If it's urgent, type: emergency.";
}

echo json_encode($response);
