<?php
// Increase session timeout parameters to 2 hours
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 0); // Cookie expires when browser is closed
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// If authenticated, refresh session modified time
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1) {
    $_SESSION['last_ping'] = time();
    echo json_encode([
        'status' => 'alive',
        'time' => time()
    ]);
} else {
    echo json_encode([
        'status' => 'guest'
    ]);
}
exit;
