<?php
// Increase session timeout to 2 hours (7200 seconds) to prevent users from being signed out during slow typing
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 0); // 0 means cookie expires when browser closes
session_start();

// Hardening: Prevent any stray output before the file download
ob_start();

// Function to create a DOCX file from the given data ($dt) and return a link to download it
function createFile($dt) {
    // Load PhpWord library via Composer
    require_once('vendor/autoload.php');

    // Load, alter, and save new DOCX based on the template
    $templ = new \PhpOffice\PhpWord\TemplateProcessor('files/vev_tmpl.docx');

    // Set values from $dt into the template (replacing placeholders)
    foreach ($dt as $k => $v) {
        $templ->setValue("$k", htmlspecialchars((string)$v));
    }
    
    // Save the modified DOCX file
    $docxFile = "files/exp_".$dt['id'].".docx";
    $templ->saveAs($docxFile);
    
    // Return the link to download the generated DOCX file
    return $docxFile;
}

require_once('conf.php');
date_default_timezone_set('Europe/Athens');

$admin = $_SESSION['admin'] ?? 0;

if (isset($_GET['id'])) {
    $progId = (int)$_GET['id']; // Cast to integer for safety
} else {
    die('Authentication Error... (no GET var)');
}

// Create connection (Object Oriented)
$conn = new mysqli($prDbhost, $prDbusername, $prDbpassword, $prDbname);
$conn->set_charset("utf8");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Use prepared statement to prevent SQL injection
$prTable = 'progs';
$prSxetos = '';
if (isset($_GET['year']) && preg_match('/^[a-zA-Z0-9_\-]+$/', $_GET['year'])) {
    $prTable = "progs_" . $_GET['year'];
    $prSxetos = $_GET['year'];
}
$stmt = $conn->prepare("SELECT p.id, p.titel, p.nam1, p.categ, p.nam2, p.nam3, p.sch1, s1.name as s1name FROM `$prTable` p JOIN $schTable s1 ON p.sch1 = s1.id WHERE p.id = ?");
$stmt->bind_param('i', $progId);
$stmt->execute();
$result = $stmt->get_result();

// Fetch record
$rec = $result->fetch_assoc();
if (!$rec) {
    die('Record not found.');
}
$rec['sxetos'] = $prSxetos; // Add year to template data
$stmt->close();

// Fetch Year Metadata (Protocol Num/Date)
$stmtMeta = $conn->prepare("SELECT protocol, protocol_date FROM progs_metadata WHERE year_name = ?");
$stmtMeta->bind_param('s', $prSxetos);
$stmtMeta->execute();
$metaRes = $stmtMeta->get_result();
$meta = $metaRes->fetch_assoc();
$rec['protocol'] = $meta['protocol'] ?? '';
$rec['protocol_num'] = $meta['protocol'] ?? ''; // Compatibility
$rec['protocol_date'] = $meta['protocol_date'] ?? '';
$stmtMeta->close();

// Check if the user is allowed to view the program
if (!$admin) {
    $sid = $rec['sch1'];
    if (!isset($_SESSION['sid']) || (string)$sid !== (string)$_SESSION['sid']) {
        die('<h2>Λάθος. Δεν έχετε δικαίωμα να δείτε αυτό το πρόγραμμα...</h2>');
    }
}

// Create DOCX file
$outFile = createFile($rec);

// Close connection
$conn->close();

// FINAL HARDENING: Clean any output buffer before sending the file
if (ob_get_length()) ob_clean();

// Offer the file for download
if (file_exists($outFile)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="'.basename($outFile).'"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($outFile));
    readfile($outFile);
    
    // Delete the file after download
    unlink($outFile);
}

exit;
