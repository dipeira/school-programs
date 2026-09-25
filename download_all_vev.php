<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
// Prevent timeout and memory exhaustion during bulk operations
@set_time_limit(0);
@ini_set('max_execution_time', 0);
@ini_set('memory_limit', '512M');

// Increase session timeout to 2 hours (7200 seconds) to prevent users from being signed out during slow typing
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 0); // 0 means cookie expires when browser closes
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != 1) {
    die("Authentication Error...");
}

// Track temporary files for guaranteed cleanup upon completion or shutdown
$filesToDelete = [];
register_shutdown_function(function() use (&$filesToDelete) {
    if (!empty($filesToDelete)) {
        foreach ($filesToDelete as $file) {
            if (!empty($file) && file_exists($file)) {
                @unlink($file);
            }
        }
    }
});

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
    require_once('vendor/autoload.php');

    $templ = new \PhpOffice\PhpWord\TemplateProcessor('files/vev_tmpl.docx');

    foreach ($dt as $k => $v) {
        $templ->setValue("$k", htmlspecialchars((string)$v));
    }
    
    $docxFile = "files/exp_".$dt['id'].".docx";
    $templ->saveAs($docxFile);
    
    return $docxFile;
}

/**
 * Converts multiple DOCX files to PDF in high-speed batches using headless LibreOffice.
 */
function convertDocxBatchToPdf(array $docxPaths) {
    if (empty($docxPaths)) return;

    $sofficeCandidates = [
        'C:\Program Files\LibreOffice\program\soffice.com',
        'C:\Program Files\LibreOffice\program\soffice.exe',
        'C:\Program Files (x86)\LibreOffice\program\soffice.com',
        'C:\Program Files (x86)\LibreOffice\program\soffice.exe',
        'soffice',
        'libreoffice'
    ];

    $sofficeCmd = null;
    foreach ($sofficeCandidates as $candidate) {
        if (file_exists($candidate)) {
            $sofficeCmd = '"' . $candidate . '"';
            break;
        }
    }

    if (!$sofficeCmd) {
        $sofficeCmd = 'soffice';
    }

    $validPaths = array_filter($docxPaths, 'file_exists');
    if (empty($validPaths)) return;

    $outDir = realpath(dirname(reset($validPaths)));
    $chunks = array_chunk($validPaths, 50);

    foreach ($chunks as $chunk) {
        $escapedFiles = array_map(function($p) { return escapeshellarg(realpath($p)); }, $chunk);
        $cmd = $sofficeCmd . ' --headless --convert-to pdf ' . implode(' ', $escapedFiles) . ' --outdir ' . escapeshellarg($outDir);
        @exec($cmd, $output, $returnCode);
    }
}

// Generate all DOCX files first and track absolute paths for cleanup
$docxMap = [];
while ($rec = $result->fetch_assoc()) {
    $rec['sxetos'] = $prSxetos;
    $rec['protocol'] = $protocol;
    $rec['protocol_num'] = $protocol;
    $rec['protocol_date'] = $protocol_date;
    
    $docxFile = createFile($rec);
    $docxAbs = realpath($docxFile) ?: (__DIR__ . '/' . $docxFile);
    $docxMap[$rec['id']] = $docxAbs;
    $filesToDelete[] = $docxAbs;
}

$conn->close();

// Batch convert all created DOCX files to PDF
convertDocxBatchToPdf(array_values($docxMap));

// Create ZIP file
$zip = new ZipArchive();
$zipFileName = 'files/vevaioseis_' . $prSxetos . '_' . uniqid() . '.zip';
$zipAbs = __DIR__ . '/' . $zipFileName;

if ($zip->open($zipAbs, ZipArchive::CREATE) !== TRUE) {
    die("Could not create ZIP file");
}

// Add converted PDF files (or DOCX fallback) to the ZIP
foreach ($docxMap as $progId => $docxPath) {
    $pathInfo = pathinfo($docxPath);
    $pdfPath = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename'] . '.pdf';

    if (file_exists($pdfPath)) {
        $pdfAbs = realpath($pdfPath) ?: $pdfPath;
        $filesToDelete[] = $pdfAbs;
        $zip->addFile($pdfAbs, "Vevaiosi_" . $progId . ".pdf");
    } else {
        $zip->addFile($docxPath, "Vevaiosi_" . $progId . ".docx");
    }
}

$zip->close();
$filesToDelete[] = $zipAbs; // Registered for automatic cleanup upon exit

// Trigger browser download of the ZIP file
if (file_exists($zipAbs)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="vevaioseis_' . $prSxetos . '.zip"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($zipAbs));
    
    if (ob_get_length()) ob_clean();
    flush();
    readfile($zipAbs);
    
    // Explicit immediate cleanup right after readfile stream completes
    foreach ($filesToDelete as $f) {
        if (!empty($f) && file_exists($f)) {
            @unlink($f);
        }
    }
}

// The shutdown function automatically runs upon exit, unlinking all temporary DOCX, PDF, and ZIP files.
exit;

