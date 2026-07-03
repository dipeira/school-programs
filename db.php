<?php
session_start();
require_once('conf.php'); // Include your configuration file

function getTableName($yearParam) {
    global $prTable;
    if (empty($yearParam)) {
        return $prTable;
    }
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
    if ($yearParam === $currentYear) {
        return $prTable;
    }
    return "progs_" . $yearParam;
}

// GET YEAR METADATA
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_metadata') {
    $mysqli = db_connect();
    $result = $mysqli->query("SELECT * FROM progs_metadata");
    $meta = [];
    while ($row = $result->fetch_assoc()) {
        $meta[] = $row;
    }
    echo json_encode($meta);
    if (isset($mysqli)) $mysqli->close();
    exit;
}

// GET CATALOG ACTION
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_catalog') {
    $year = isset($_GET['year']) ? preg_replace('/[^a-zA-Z0-9\-_]/', '', $_GET['year']) : '';
    $catTable = getTableName($year);
    $mysqli = db_connect();
    
    // Explicit selection of columns to prevent column name collisions (s.id overwriting p.id)
    $sql = "SELECT p.id AS pid, p.titel, p.categ, p.publish, p.publish_text, p.publish_images, s.name AS school_name 
            FROM `$catTable` p 
            JOIN $schTable s ON p.sch1 = s.id 
            WHERE p.publish = 'Ναι' 
            ORDER BY p.id DESC";
            
    $result = $mysqli->query($sql);
    $catalog = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $catalog[] = $row;
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($catalog);
    $mysqli->close();
    exit;
}

// SAVE YEAR METADATA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_metadata') {
    if (!isset($_SESSION['uid']) || ($_SESSION['uid'] !== 'dipeira' && $_SESSION['uid'] !== 'taypeira')) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized action.']);
        exit;
    }
    $mysqli = db_connect();
    $metadata = json_decode($_POST['metadata'], true);
    
    $mysqli->begin_transaction();
    try {
        foreach ($metadata as $record) {
            $year = $mysqli->real_escape_string($record['year_name']);
            $protocol = $mysqli->real_escape_string($record['protocol']);
            $p_date = $mysqli->real_escape_string($record['protocol_date']);
            
            // Handle date conversion if needed or NULL if empty
            $mysql_date = !empty($p_date) ? "'" . $mysqli->real_escape_string($p_date) . "'" : "NULL";

            $sql = "INSERT INTO progs_metadata (year_name, protocol, protocol_date) 
                    VALUES ('$year', '$protocol', $mysql_date) 
                    ON DUPLICATE KEY UPDATE protocol='$protocol', protocol_date=$mysql_date";
            $mysqli->query($sql);
        }
        $mysqli->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    if (isset($mysqli)) $mysqli->close();
    exit;
}

// ARCHIVE TABLE OPERATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive') {
    if (!isset($_SESSION['uid']) || $_SESSION['uid'] !== 'dipeira') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized action. Admin rank required.']);
        exit;
    }
    
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $mysqli = db_connect();
        $suffix = preg_replace('/[^a-zA-Z0-9\-_]/', '', $_POST['archive_year_suffix']);
        if (empty($suffix)) {
            echo json_encode(['success' => false, 'error' => 'Invalid backup suffix format.']);
            exit;
        }
        
        $backupTableName = "progs_" . $suffix;
        
        // Check if table already exists
        $check = $mysqli->query("SHOW TABLES LIKE '$backupTableName'");
        if ($check->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Το αρχείο ' . $backupTableName . ' υπάρχει ήδη!']);
            exit;
        }
        
        // RENAME causes implicit commit
        $mysqli->query("RENAME TABLE `$prTable` TO `$backupTableName`");
        $mysqli->query("CREATE TABLE `$prTable` LIKE `$backupTableName`");
        
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Archiving failed: ' . $e->getMessage()]);
    }
    if (isset($mysqli)) $mysqli->close();
    exit;
}

// RESTORE TABLE OPERATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore') {
    if (!isset($_SESSION['uid']) || $_SESSION['uid'] !== 'dipeira') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized action. Admin rank required.']);
        exit;
    }
    
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $mysqli = db_connect();
        $suffix = preg_replace('/[^a-zA-Z0-9\-_]/', '', $_POST['restore_year_suffix']);
        if (empty($suffix)) {
            echo json_encode(['success' => false, 'error' => 'Invalid backup suffix format.']);
            exit;
        }
        
        $backupTableName = "progs_" . $suffix;
        
        // Check if backup table exists
        $check = $mysqli->query("SHOW TABLES LIKE '$backupTableName'");
        if ($check->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Το επιλεγμένο αρχείο δεν υπάρχει!']);
            exit;
        }
        
        $mysqli->query("DROP TABLE IF EXISTS `$prTable`");
        $mysqli->query("RENAME TABLE `$backupTableName` TO `$prTable`");
        
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Restoration failed: ' . $e->getMessage()]);
    }
    if (isset($mysqli)) $mysqli->close();
    exit;
}

// get program record
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    if (isset($_GET['year']) && !empty($_GET['year']) && preg_match('/^[a-zA-Z0-9_\-]+$/', $_GET['year'])) {
        $prTable = getTableName($_GET['year']);
    }
    // Retrieve the record ID from the GET request
    $recordId = (int)$_GET['id']; // Cast to integer for safety

    // Use the $recordId to fetch the record details from your database
    $conn = db_connect();

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT p.*, s.name as sch1name FROM `$prTable` p JOIN $schTable s ON p.sch1 = s.id WHERE p.id = ?");
    $stmt->bind_param('i', $recordId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // Fetch the record data
        $recordData = $result->fetch_assoc();

        // Close the statement and database connection
        $stmt->close();
        $conn->close();

        // Return the record data as JSON (or any other format you prefer)
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($recordData);
    } else {
        // Close the statement and database connection
        $stmt->close();
        $conn->close();
        // Handle the case where the record doesn't exist
        echo 'Record not found';
    }

// get school name (by id)
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['sch_id'])) {
    $mysqli = db_connect();
    // Use prepared statement to prevent SQL injection
    $schId = (int)$_GET['sch_id']; // Cast to integer for safety
    $stmt = $mysqli->prepare("SELECT name FROM $schTable WHERE id = ?");
    $stmt->bind_param('i', $schId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    $mysqli->close();
    echo $row['name'];

// get all schools
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['all_schools'])) {
    $mysqli = db_connect();

    // Query your database to get options from the $schTable table
    if (isset($_GET['term']) ){
        // Use prepared statement to prevent SQL injection
        $searchTerm = "%" . $_GET['term'] . "%";
        $stmt = $mysqli->prepare("SELECT id, name FROM $schTable WHERE name LIKE ?");
        $stmt->bind_param('s', $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query("SELECT id, name FROM $schTable");
    }    

    $options = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $options[] = array(
                'id' => $row['id'],
                'text' => $row['name']
            );
        }
    }

    // Close the statement and database connection
    if (isset($stmt)) {
        $stmt->close();
    }
    $mysqli->close();

    // Return the options as JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($options);

} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_temp_images') {
    // Process uploaded files and validate size and count
    $existing_count = isset($_POST['existing_count']) ? (int)$_POST['existing_count'] : 0;
    
    if (!isset($_FILES['publish_files'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Δεν επιλέχθηκαν αρχεία.']);
        exit;
    }
    
    $files = $_FILES['publish_files'];
    if (isset($files['name']) && !is_array($files['name'])) {
        $files = [
            'name' => [$files['name']],
            'type' => [$files['type']],
            'tmp_name' => [$files['tmp_name']],
            'error' => [$files['error']],
            'size' => [$files['size']]
        ];
    }
    
    if (isset($files['name']) && is_array($files['name'])) {
        $num_files = count($files['name']);
        if ($existing_count + $num_files > 8) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Μπορείτε να ανεβάσετε έως 8 φωτογραφίες συνολικά.']);
            exit;
        }
        
        // Validate combined file size limit
        $total_size = 0;
        for ($i = 0; $i < $num_files; $i++) {
            if (isset($files['error'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
                $total_size += $files['size'][$i];
            }
        }
        if ($total_size > 8388608) { // 8MB
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Το συνολικό μέγεθος των επιλεγμένων αρχείων υπερβαίνει το όριο των 8MB!']);
            exit;
        }
        
        // Check allowed formats
        for ($i = 0; $i < $num_files; $i++) {
            if (isset($files['error'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowed)) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'error' => 'Μη επιτρεπτός τύπος αρχείου: ' . htmlspecialchars($files['name'][$i])]);
                    exit;
                }
            }
        }
        
        $new_paths = [];
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        for ($i = 0; $i < $num_files; $i++) {
            if (isset($files['error'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                $unique_name = 'uploads/img_' . uniqid() . '_' . time() . '_' . $i . '.' . $ext;
                if (move_uploaded_file($files['tmp_name'][$i], $unique_name)) {
                    $new_paths[] = $unique_name;
                }
            }
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'paths' => $new_paths]);
        exit;
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Σφάλμα κατά τη μεταφόρτωση.']);
        exit;
    }

// POST preprocessing for file uploads and deletes
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    // Process file uploads and deleted images
    $recordId = (int)$_POST['id'];
    $mysqli = db_connect();
    
    $uploaded_paths = [];
    if (isset($_POST['publish_images'])) {
        $uploaded_paths = json_decode($_POST['publish_images'], true) ?: [];
    } else if ($recordId > 0) {
        $stmt = $mysqli->prepare("SELECT publish_images FROM `$prTable` WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $recordId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $uploaded_paths = json_decode($row['publish_images'] ?? '[]', true) ?: [];
            }
            $stmt->close();
        }
    }
    
    // Handle deleted images
    if (isset($_POST['deleted_images']) && !empty($_POST['deleted_images'])) {
        $deleted = json_decode($_POST['deleted_images'], true) ?: [];
        foreach ($deleted as $del_img) {
            $del_img = str_replace('\\', '/', $del_img);
            if (strpos($del_img, 'uploads/img_') === 0 && !strpos($del_img, '..')) {
                $full_path = __DIR__ . '/' . $del_img;
                if (file_exists($full_path)) {
                    @unlink($full_path);
                }
                $key_idx = array_search($del_img, $uploaded_paths);
                if ($key_idx !== false) {
                    unset($uploaded_paths[$key_idx]);
                }
            }
        }
        $uploaded_paths = array_values($uploaded_paths);
    }
    
    // Handle new file uploads
    if (isset($_FILES['publish_files'])) {
        $files = $_FILES['publish_files'];
        // Check if multiple files or single file structure
        if (isset($files['name']) && !is_array($files['name'])) {
            $files = [
                'name' => [$files['name']],
                'type' => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'error' => [$files['error']],
                'size' => [$files['size']]
            ];
        }
        
        if (isset($files['name']) && is_array($files['name'])) {
            $num_files = count($files['name']);
            for ($i = 0; $i < $num_files; $i++) {
                if (isset($files['error'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
                    $file_size = $files['size'][$i];
                    if ($file_size > 8388608) { // 8MB
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'error' => 'Το αρχείο ' . htmlspecialchars($files['name'][$i]) . ' υπερβαίνει το όριο των 8MB.']);
                        $mysqli->close();
                        exit;
                    }
                    
                    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($ext, $allowed)) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'error' => 'Μη επιτρεπτός τύπος αρχείου: ' . htmlspecialchars($files['name'][$i])]);
                        $mysqli->close();
                        exit;
                    }
                    
                    if (!file_exists('uploads')) {
                        mkdir('uploads', 0777, true);
                    }
                    
                    $unique_name = 'uploads/img_' . uniqid() . '_' . time() . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($files['tmp_name'][$i], $unique_name)) {
                        $uploaded_paths[] = $unique_name;
                    }
                }
            }
        }
    }
    
    // Set the value for database insertion
    $_POST['publish_images'] = json_encode($uploaded_paths);
    
    // Now route to INSERT or UPDATE operation
    if ($recordId == 0) {
        // INSERT operation
        $fields = array();
        $values = array();

        foreach ($_POST as $key => $value) {
            // Skip utility parameters
            if (in_array($key, ['action', 'id', 'record_id', 'deleted_images', 'publish_check', 'publish_files'])) {
                continue;
            }
            if ($key == 'praxidate') {
                $dateTime = DateTime::createFromFormat('d/m/Y', $value);
                if ($dateTime != false) {
                    $mysql_date = $dateTime->format('Y-m-d');
                    $fields[] = "`$key`";
                    $values[] = "'" . $mysql_date . "'";
                }
                continue;
            }
            $fields[] = "`$key`";
            $values[] = "'" . mysqli_real_escape_string($mysqli, $value) . "'";
        }

        if (count($fields) > 0 && count($values) > 0) {
            $sql = "INSERT INTO `$prTable` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
            if (mysqli_query($mysqli, $sql)) {
                $response = ['success' => true];
            } else {
                $response = ['success' => false, 'error' => 'Database insert error: ' . mysqli_error($mysqli)];
            }
        } else {
            $response = ['success' => false, 'error' => 'No fields to insert'];
        }

        $mysqli->close();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    } else {
        // UPDATE operation
        $updates = array();

        foreach ($_POST as $key => $value) {
            // Skip utility parameters
            if (in_array($key, ['action', 'id', 'record_id', 'deleted_images', 'publish_check', 'publish_files'])) {
                continue;
            }
            if ($key == 'praxidate') {
                $dateTime = DateTime::createFromFormat('d/m/Y', $value);
                if ($dateTime != false) {
                    $mysql_date = $dateTime->format('Y-m-d');
                    $updates[] = "`$key` = '" . $mysql_date . "'";
                }
                continue;
            }
            $updates[] = "`$key` = '" . mysqli_real_escape_string($mysqli, $value) . "'";
        }

        if (count($updates) > 0) {
            $sql = "UPDATE `$prTable` SET " . implode(', ', $updates) . " WHERE id = " . (int)$recordId;
            if (mysqli_query($mysqli, $sql)) {
                $response = ['success' => true];
            } else {
                $response = ['success' => false, 'error' => 'Database update error: ' . mysqli_error($mysqli)];
            }
        } else {
            $response = ['success' => false, 'error' => 'No fields to update'];
        }

        $mysqli->close();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    }

// delete record
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    $mysqli = db_connect();

    // Prepare the SQL query to delete the record
    $sql = "DELETE FROM `$prTable` WHERE id = ?";
    $stmt = $mysqli->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('i', $deleteId);
        if ($stmt->execute()) {
            $response = ['success' => true];
        } else {
            $response = ['success' => false, 'error' => 'Database delete error: ' . $stmt->error];
        }
        $stmt->close();
    } else {
        $response = ['success' => false, 'error' => 'Statement preparation failed: ' . $mysqli->error];
    }
    $mysqli->close();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit;
} else {
    // Handle invalid or missing parameters
    $response = ['success' => false, 'error' => 'Invalid request'];
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit;
}
?>
