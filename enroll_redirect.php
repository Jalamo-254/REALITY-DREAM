<?php
// Central enrollment entry point.
// Set your Google Form URL below (or via env var GOOGLE_ENROLL_FORM_URL).
$googleFormUrl = getenv('GOOGLE_ENROLL_FORM_URL') ?: 'https://forms.gle/PASTE_YOUR_GOOGLE_FORM_LINK_HERE';

// Redirect to Google Form if configured; otherwise use local enrollment form.
if (
    !empty($googleFormUrl) &&
    stripos($googleFormUrl, 'https://forms.gle/') === 0 &&
    stripos($googleFormUrl, 'PASTE_YOUR_GOOGLE_FORM_LINK_HERE') === false
) {
    header('Location: ' . $googleFormUrl);
    exit;
}

header('Location: enroll.php');
exit;
?>
