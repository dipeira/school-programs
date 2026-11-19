<?php
// Increase session timeout to 2 hours (7200 seconds) to prevent users from being signed out during slow typing
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 0); // 0 means cookie expires when browser closes
session_start();
header('Content-Type: application/json');

// Security check: require admin session
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== 1 || empty($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Δεν έχετε δικαιώματα διαχειριστή (Unauthorized).']);
    exit;
}

if (!isset($_POST['configData'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Δεν εστάλησαν δεδομένα ρυθμίσεων (No config data provided).']);
    exit;
}

$configData = json_decode($_POST['configData'], true);

if ($configData === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Μη έγκυρη μορφή δεδομένων (Invalid JSON data).']);
    exit;
}

$file = 'config.json';

// Encode the updated configuration data as JSON and write it to the file
// JSON_PRETTY_PRINT for readability, JSON_UNESCAPED_UNICODE to preserve Greek characters
$jsonContent = json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if ($jsonContent === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Αποτυχία κωδικοποίησης JSON: ' . json_last_error_msg()]);
    exit;
}

// Attempt to ensure file is writable if permissions allow
if (file_exists($file) && !is_writable($file)) {
    @chmod($file, 0666);
}

if (@file_put_contents($file, $jsonContent) !== false) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Αδυναμία εγγραφής στο αρχείο config.json στον server (Δικαιώματα / Permissions). Εκτελέστε στον server: sudo chmod 666 config.json'
    ]);
}
?>
