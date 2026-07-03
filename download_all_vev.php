<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
// Increase session timeout to 2 hours (7200 seconds) to prevent users from being signed out during slow typing
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 0); // 0 means cookie expires when browser closes
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != 1) {
    die("Authentication Error...");
}

require_once('conf.php');
date_default_timezone_set('Europe/Athens');

// Load current school year from config.json
$jsonString = file_get_contents('config.json');
$configData = json_decode($jsonString, true);
$currentYear = '';
if ($configData) {
    foreach ($configData as $item) {
        if ($item['name'] === 'prSxetos') {
            $currentYear = $item['value'];
            break;
        }
    }
}

$admin = $_SESSION['admin'] ?? 0;
$sid = $_SESSION['sid'] ?? 0;

$prTable = 'progs';
$prSxetos = $currentYear;
if (isset($_GET['year']) && preg_match('/^[a-zA-Z0-9_\-]+$/', $_GET['year'])) {
    if ($_GET['year'] !== $currentYear) {
        $prTable = "progs_" . $_GET['year'];
    }
    $prSxetos = $_GET['year'];
}

$conn = db_connect();
$conn->set_charset("utf8");

// Fetch Year Metadata (Protocol Num/Date)
$isProtocolSet = false;
$protocol = '';
$protocol_date = '';
$stmtMeta = $conn->prepare("SELECT protocol, protocol_date FROM progs_metadata WHERE year_name = ?");
if ($stmtMeta) {
    $stmtMeta->bind_param('s', $prSxetos);
    $stmtMeta->execute();
    $metaRes = $stmtMeta->get_result();
    if ($meta = $metaRes->fetch_assoc()) {
        if (!empty($meta['protocol']) && !empty($meta['protocol_date']) && $meta['protocol_date'] !== '0000-00-00') {
            $isProtocolSet = true;
            $protocol = $meta['protocol'];
            $protocol_date = date('d/m/Y', strtotime($meta['protocol_date']));
        }
    }
    $stmtMeta->close();
}

if (!$isProtocolSet) {
    die("Error: Protocol parameters are not set.");
}

// Detect legacy table schema
$isLegacy = false;
$checkSch1 = $conn->query("SHOW COLUMNS FROM `$prTable` LIKE 'sch1'");
if ($checkSch1 && $checkSch1->num_rows == 0) {
    $isLegacy = true; 
}

// Fetch program records
if ($isLegacy) {
    $where = "p.agree = 'Ναι'";
    if (!$admin) {
        // Find school code corresponding to the school ID ($sid) in session
        $schCode = '';
        $stmt_code = $conn->prepare("SELECT code FROM $schTable WHERE id = ?");
        if ($stmt_code) {
            $stmt_code->bind_param('i', $sid);
            $stmt_code->execute();
            $res_code = $stmt_code->get_result();
            if ($row_code = $res_code->fetch_assoc()) {
                $schCode = $row_code['code'];
            }
            $stmt_code->close();
        }
        $where .= " AND p.sch_id = '" . mysqli_real_escape_string($conn, $schCode) . "'";
    }
    $sql = "SELECT p.id, p.title AS titel, p.nam1, p.category AS categ, p.nam2, p.nam3, p.sch_id AS sch1, s1.name as s1name 
            FROM `$prTable` p 
            JOIN $schTable s1 ON p.sch_id = s1.code 
            WHERE $where";
} else {
    $where = "p.vev = 'Ναι'";
    if (!$admin) {
        $where .= " AND p.sch1 = " . (int)$sid;
    }
    $sql = "SELECT p.id, p.titel, p.nam1, p.categ, p.nam2, p.nam3, p.sch1, s1.name as s1name 
            FROM `$prTable` p 
            JOIN $schTable s1 ON p.sch1 = s1.id 
            WHERE $where";
}

$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    die("<h2>Δεν βρέθηκαν προγράμματα προς έκδοση βεβαίωσης για το επιλεγμένο έτος.</h2>");
}

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

// Create ZIP file
$zip = new ZipArchive();
$zipFileName = 'files/vevaioseis_' . $prSxetos . '_' . uniqid() . '.zip';
if ($zip->open($zipFileName, ZipArchive::CREATE) !== TRUE) {
    die("Could not create ZIP file");
}

$tempFiles = [];

while ($rec = $result->fetch_assoc()) {
    $rec['sxetos'] = $prSxetos;
    $rec['protocol'] = $protocol;
    $rec['protocol_num'] = $protocol;
    $rec['protocol_date'] = $protocol_date;
    
    // Create docx
    $docxFile = createFile($rec);
    
    // Add to ZIP (use a clean filename inside the ZIP, e.g., "Vevaiosi_123.docx")
    $zip->addFile($docxFile, "Vevaiosi_" . $rec['id'] . ".docx");
    
    $tempFiles[] = $docxFile;
}

$zip->close();
$conn->close();

// Download the ZIP
if (file_exists($zipFileName)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.basename($zipFileName).'"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($zipFileName));
    
    // Flush buffer to avoid memory overflow for large files
    ob_clean();
    flush();
    readfile($zipFileName);
    
    // Clean up files
    unlink($zipFileName);
    foreach ($tempFiles as $tempFile) {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
}
exit;
