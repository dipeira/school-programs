<?php
// Increase session timeout to 2 hours (7200 seconds) to prevent users from being signed out during slow typing
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 0); // 0 means cookie expires when browser closes
session_start();
if (!isset($_SESSION['loggedin'])) {
    $_SESSION['loggedin'] = 0;
}
require_once('conf.php');
date_default_timezone_set('Europe/Athens');


// get school data
function get_school($code, $conn) {
	global $schTable;
    // Cast to string and escape to prevent SQL injection or type errors
    $code_safe = mysqli_real_escape_string($conn, (string)$code);
	$sql = "SELECT id,name FROM $schTable WHERE code = '$code_safe'";
	$result = $conn->query($sql);
    if (!$result) return ['id' => 0, 'name' => 'Άγνωστο'];
	$row = mysqli_fetch_assoc($result);
	return [
			'id' => $row['id'] ?? 0,
			'name' => $row['name'] ?? 'Άγνωστο'
	];
}

// Load variables from config.json file
// Read the contents of config.json
$jsonString = file_get_contents('config.json');
// Decode the JSON string to an associative array
$configData = json_decode($jsonString, true);
// Extract values from the associative array and set them as variables
foreach ($configData as $configItem) {
    ${$configItem['name']} = $configItem['value'];
}

if (!$prDebug) {
	// Initialize phpCAS early
	require_once('vendor/autoload.php');
	phpCAS::client(CAS_VERSION_3_0,'sso.sch.gr',443,'','https://srv1-dipe.ira.sch.gr');
	
	// Handle logout
	if (isset($_POST['logout'])) {
		session_unset();
		session_destroy(); 
		phpCAS::logout();
		header("Location: index.php");
        exit;
	}
	
	phpCAS::setNoCasServerValidation();
	phpCAS::handleLogoutRequests(array('sso-test.sch.gr'));

    // Check authentication. This transparently handles tickets and valid CAS redirects!
    $isAuthenticated = phpCAS::isAuthenticated();

	// if user not logged-in and hasn't pressed the login button, display login form
	if (!$isAuthenticated && !isset($_POST['login-btn'])):
		?>
	<!DOCTYPE html>
		<html lang="el">
			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title>Είσοδος - Προγράμματα Σχολικών Δραστηριοτήτων</title>
				<!-- Bootstrap CSS & Icons -->
				<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
                <!-- Google Fonts -->
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
				<style>
                    body {
                        font-family: 'Inter', sans-serif;
                        background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
                        min-height: 100vh;
                        display: flex;
                        flex-direction: column;
                    }
                    .login-container {
                        flex: 1;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .login-card {
                        background: #ffffff;
                        border-radius: 16px;
                        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
                        padding: 3.5rem 2.5rem;
                        max-width: 480px;
                        width: 100%;
                        text-align: center;
                        border: 1px solid rgba(0,0,0,0.05);
                    }
                    .login-icon {
                        font-size: 3.5rem;
                        color: #0d6efd;
                        margin-bottom: 1rem;
                    }
                    .title {
                        font-weight: 700;
                        color: #2b3440;
                        font-size: 1.6rem;
                        line-height: 1.3;
                        margin-bottom: 0.5rem;
                    }
                    .subtitle {
                        color: #6c757d;
                        font-size: 0.95rem;
                        margin-bottom: 2rem;
                    }
                    .btn-login {
                        padding: 0.8rem 1.5rem;
                        font-weight: 600;
                        border-radius: 8px;
                        font-size: 1.05rem;
                        transition: all 0.3s ease;
                        background-color: #0d6efd;
                    }
                    .btn-login:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3);
                        background-color: #0b5ed7;
                    }
                    .info-text {
                        font-size: 0.85rem;
                        color: #8fa0b5;
                        margin-top: 1.5rem;
                        line-height: 1.4;
                    }
                    .footer {
                        padding: 1.5rem 0;
                        background: transparent;
                        text-align: center;
                        color: #7a8b9e;
                        font-size: 0.85rem;
                    }
                    .footer a {
                        color: #7a8b9e;
                        text-decoration: none;
                        transition: color 0.2s;
                    }
                    .footer a:hover {
                        color: #0d6efd;
                    }
                    .github-icon {
                        font-size: 1.2rem;
                    }
				</style>
			</head>
			
			<body>
				<div class="login-container px-3">
					<div class="login-card">
                        <i class="bi bi-journal-bookmark-fill login-icon"></i>
						<h1 class="title">Προγράμματα<br>Σχολικών Δραστηριοτήτων</h1>
                        <span class="badge bg-primary mb-3 py-2 px-3 fw-medium">Έτος <?=$prSxetos?></span>
						<p class="subtitle">
							Σύστημα ελέγχου, διαχείρισης και αυτόματης έκδοσης βεβαιώσεων
						</p>

						<form id="login" method="post">
							<button type="submit" class="btn btn-primary w-100 btn-login" name="login-btn">
								<i class="bi bi-box-arrow-in-right me-2"></i>Είσοδος μέσω Π.Σ.Δ.
							</button>
						</form>

                        <div class="info-text">
						    <i class="bi bi-info-circle me-1"></i>Η είσοδος γίνεται με κωδικούς μονάδας (ΠΣΔ).<br>
                            ΟΧΙ με προσωπικούς κωδικούς ή MySchool.
						</div>
					</div>
				</div>

				<footer class="footer">
					<div class="container d-flex justify-content-center align-items-center gap-2">
                        <span>&copy; <?= date("Y") ?> ΔΙ.Π.Ε. Ηρακλείου - Τμήμα Δ' Πληροφορικής</span>
                        <span>|</span>
                        <a href="https://github.com/dipeira/school-programs" target="_blank" title="Πηγαίος Κώδικας στο Github" class="github-icon">
                            <i class="bi bi-github"></i>
                        </a>
					</div>
				</footer>
			</body>
		</html>
	<?php
	die();
	endif;

	// force CAS authentication if button was pressed but not authenticated
	if (!$isAuthenticated) {
	    phpCAS::forceAuthentication();
    }

	// at this step, the user has been authenticated by the CAS server
	$_SESSION['loggedin'] = 1;
} else {
    $_SESSION['loggedin'] = 1;
}

// Check for records to enable dynamic titles in <head>
$conn = new mysqli($prDbhost, $prDbusername, $prDbpassword, $prDbname);
$isArchive = false;
$availableYears = [];
if ($conn->connect_error) {
    // Fail silently or handle error later
} else {
    $tableQuery = $conn->query("SHOW TABLES LIKE 'progs\_%'");
    if ($tableQuery) {
        while ($t = $tableQuery->fetch_array()) {
            $year = str_replace('progs_', '', $t[0]);
            // Only add if it matches YYYY-YY format (e.g. 2024-25)
            if (preg_match('/^\d{4}-\d{2}$/', $year)) {
                $availableYears[] = $year;
            }
        }
    }
    if (isset($_GET['year']) && in_array($_GET['year'], $availableYears)) {
        $prTable = 'progs_' . $_GET['year'];
        $isArchive = true;
        $prSxetos = $_GET['year']; // Override title
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo 'Προγράμματα Σχολικών Δραστηριοτήτων ' . $prSxetos; ?></title>
    <!-- Include Bootstrap CSS and DataTables.net CSS here -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.css" />
		<link rel="stylesheet" href="https://cdn.datatables.net/2.0.3/css/dataTables.dataTables.css" />
		<link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.1/css/buttons.dataTables.css" />
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" />
		<style>
			.btn {
						margin: 2px 0px 2px 0px;
					}
		</style>
</head>
<body>
<?php
if (!$prDebug)
{
	$sch_name = phpCAS::getAttribute('description');
	$sch_code = phpCAS::getAttribute('edupersonorgunitdn-gsnunitcode');
	$uid = phpCAS::getUser();
	$em1 = $uid . "@sch.gr";
	$em2 = phpCAS::getAttribute('mail');
	
	$_SESSION['admin'] = ($uid === $prAdmin1 || $uid === $prAdmin2 || $uid === 'dipeira' || $uid === 'taypeira') ? 1 : 0;
	$_SESSION['email1'] = $em1;
	$_SESSION['email2'] = $em2;
	$_SESSION['uid'] = $uid;
}
// fill for local testing
else {
  $uid = $pruid;
  $sch_code = $uid; 
  $sch_name = $prsch_name;
  $em1 = $prem1;
  $em2 = $prem2;

  $_SESSION['admin'] = ($uid === $prAdmin1 || $uid === $prAdmin2) ? 1 : 0;
  $_SESSION['email1'] = $em1;
  $_SESSION['email2'] = $em2;
  $_SESSION['uid'] = $uid;
}


    if (!$_SESSION['admin']) {
        $clauses = [];
        if (!empty($_SESSION['email1'])) $clauses[] = "s.email1='" . mysqli_real_escape_string($conn, $_SESSION['email1']) . "'";
        if (!empty($_SESSION['email2'])) $clauses[] = "s.email2='" . mysqli_real_escape_string($conn, $_SESSION['email2']) . "'";
        if (!empty($sch_code)) $clauses[] = "s.code='" . mysqli_real_escape_string($conn, $sch_code) . "'";
        
        $where = !empty($clauses) ? implode(" OR ", $clauses) : "1=0"; // Match nothing if no identifier
        $sql = "SELECT *,p.id as pid FROM `$prTable` p JOIN $schTable s ON s.id = p.sch1 WHERE ($where)";
	} else {
		$sql = "SELECT *,p.id as pid FROM `$prTable` p JOIN $schTable s ON s.id = p.sch1";
	}
    $schid = 0; // Initialize schid
	$result = $conn->query($sql);
    
    // Ensure admin name is set early to prevent empty-name lookup
    if ($_SESSION['admin']){
        $schid = 1;
        $sch_name = "Διαχείριση Διεύθυνσης";
    }

    if (strlen($sch_name) == 0){
        $function_data = get_school($sch_code, $conn);
        $sch_name = $function_data['name'];
        $schid = $function_data['id'];
    }
		
		// only admin can delete for now
		$canDelete = $_SESSION['admin'] ? 1 : 0;
		echo '<div class="container">';
		echo "<center><h1><i class='bi-newspaper'></i>&nbsp;&nbsp;Προγράμματα Σχολικών Δραστηριοτήτων $prSxetos</h1></center>";
    echo "<h4>Σχολείο: " . $sch_name . "</h4>";
    
    echo '<form method="GET" class="d-inline-flex align-items-center mb-3 mt-2">';
    echo '<label class="fw-bold me-2">Επιλογή Έτους: </label>';
    echo '<select name="year" class="form-select w-auto" onchange="this.form.submit()">';
    echo '<option value="">Τρέχον Έτος (Ενεργό)</option>';
    foreach ($availableYears as $y) {
        $sel = (isset($_GET['year']) && $_GET['year'] === $y) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($y) . '" ' . $sel . '>' . htmlspecialchars($y) . '</option>';
    }
    echo '</select></form>';

    if ($isArchive) {
        echo '<div class="alert alert-warning py-2 mb-3"><strong><i class="bi-exclamation-triangle"></i> Λειτουργία Αρχείου (Read-Only):</strong> Προβολή Ιστορικών Δεδομένων. Η επεξεργασία έχει απενεργοποιηθεί.</div>';
    }
        // if no results
        if (!$result->num_rows) {
            $outmsg = "<h2>Δεν υπάρχουν αποτελέσματα...</h2><p>Ελέγξτε ότι:<ol><li>Ο λογαριασμός με τον οποίο κάνατε είσοδο είναι λογαριασμός <strong>Σχολικής Μονάδας ΠΣΔ <small>(π.χ. για λήψη email, είσοδο στο survey κλπ)</small></strong> και <strong>ΟΧΙ</strong> προσωπικός ή του MySchool*.</li><li>Βεβαιωθείτε ότι η σχολική σας μονάδα έχει προγράμματα σχολικών δραστηριοτήτων.</li><li>Αν ελέγξατε τα παραπάνω και συνεχίζετε να έχετε πρόβλημα, επικοινωνήστε με τη Δ/νση</li></ol><br>
            * Σε περίπτωση που είστε συνδεδεμένοι στο MySchool πρέπει να αποσυνδεθείτε και μετά να κάνετε είσοδο στο σύστημα αυτό.</p>";
            echo '<div style="font-size:10pt;color:black;font-family:arial;">' . $outmsg . '</div>';
						$add_prog = ($_SESSION['admin'] || (!$_SESSION['admin'] && $canAdd)) && !$isArchive ? '' : 'disabled d-none';
						$sid = $schid > 0 ? $schid : 0;
						echo '<a href="#" class="btn btn-success add-record '.$add_prog.'" data-schid='.$sid.'><span class="bi-plus-circle"></span>&nbsp;Προσθήκη</a></td>';
        } else {
						echo '<div id="alertContainer"></div>';
            // Display DataTable with records
            echo '<table id="progs" class="table table-bordered table-striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>A/A</th>';
            echo '<th>Όνομα Σχολείου</th>';
            echo '<th>Επταψήφιος κωδικός σχολείου</th>';
						echo '<th>Κατηγορία</th>';
            echo '<th>Τίτλος προγράμματος</th>';
            echo '<th>Έλεγχος</th>';
						echo '<th>Βεβαίωση</th>';
            echo '<th>Τελ. Μεταβολή</th>';
						echo '<th>Ενέργεια</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row['pid'] . '</td>';
                echo '<td>' . $row['name'] . '</td>';
                echo '<td>' . $row['code'] . '</td>';
								echo '<td>' . $row['categ'] . '</td>';
                echo '<td>' . $row['titel'] . '</td>';
                echo '<td>' . $row['chk'] . '</td>';
								echo '<td>' . $row['vev'] . '</td>';
                echo '<td><span class="d-none">' . date('YmdHis', strtotime($row['timestamp'])) . '</span>' . date('d-m-Y, H:i:s',strtotime($row['timestamp'])) . '</td>';
                                $archSuffix = isset($_GET['year']) ? '&year=' . $_GET['year'] : '';
                                $archData = isset($_GET['year']) ? $_GET['year'] : '';
                                if (!$isArchive) {
								    echo '<td><a href="#" class="btn btn-warning edit-record" data-record-id="'.$row['pid'].'" data-sch-id="'.$row['sch1']. '" data-canvev="'.$canVev;
								    echo '" data-lock-basic="'.$lockBasic.'" data-admin="'.$_SESSION['admin'].'"><span class="bi-pencil-square"></span>&nbsp;Επεξεργασία</a>';
                                } else { echo '<td>'; }
								echo '&nbsp;<a href="#" class="btn btn-info view-record" data-record-id="'.$row['pid'].'" data-year="'.$archData.'"><span class="bi-eye"></span>&nbsp;Προβολή</a>';
                                $vevDisabled = ($row['vev'] === 'Ναι') ? '' : ' disabled';
								echo (($showVev && !$isArchive) || $_SESSION['admin']) ? '&nbsp;<a href="exp.php?id='.$row['pid'].$archSuffix.'" class="btn btn-success btn-vev'.$vevDisabled.'" data-record-id="'.$row['pid'].'"><span class="bi-file-earmark-text"></span>&nbsp;Βεβαίωση</a>' : '';
								if (!$isArchive) echo $canDelete ? '&nbsp;<a href="#" class="btn btn-danger" onclick="confirmDelete('.$row['pid'].')"><span class="bi bi-trash"></span>&nbsp;Διαγραφή</a>' : '';
								echo '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
						$add_prog = ($_SESSION['admin'] || (!$_SESSION['admin'] && $canAdd)) && !$isArchive ? '' : 'disabled d-none';
						echo '<a href="#" class="btn btn-success add-record '.$add_prog.'" data-schid="'.$schid.'"><span class="bi-plus-circle"></span>&nbsp;Προσθήκη</a></td>';
        }
				// Logout button
				echo "<br><br>";
				echo '<form action="" method="POST">';
				echo '<div class="d-flex flex-wrap gap-2 align-items-center">';
				if ($_SESSION['admin']){
					echo '<button type="button" class="btn btn-primary" id="btnConfig" data-bs-toggle="modal" data-bs-target="#configModal"><span class="bi-gear"></span>&nbsp;Παράμετροι</button>';

					echo '<button type="button" class="btn btn-success" id="exportButton" data-year="'.(isset($_GET['year'])?$_GET['year']:'').'"><span class="bi bi-file-earmark-excel"></span>&nbsp;Εξαγωγή σε Excel</button>';
                    if ($_SESSION['uid'] === 'dipeira' || $_SESSION['uid'] === 'taypeira') {
                        echo '<button type="button" class="btn btn-danger" id="btnAdminYear" data-bs-toggle="modal" data-bs-target="#archiveModal"><span class="bi-archive"></span>&nbsp;Διαχείριση Έτους</button>';
                    }
    			//Open Configuration Modal
				}
				echo '<button type="submit" class="btn btn-danger" id="btnLogout" name="logout"><span class="bi-box-arrow-right"></span>&nbsp;Έξοδος</button>';
				echo '</div>';
				echo '</form>';
				echo "</div>";
        $conn->close();
    






$author = '(c) 2024, Τμήμα Δ - Πληροφορικής & Νέων Τεχνολογιών, Δ/νση Π.Ε. Ηρακλείου.';
echo '<div style="font-size:9pt;color:black">' . $author . '</div>';





?>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Προβολή Εγγραφής</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- View record details content -->
            </div>
        </div>
    </div>
</div>
<!-- Archive Modal -->
<div class="modal fade" id="archiveModal" tabindex="-1" aria-labelledby="archiveModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="archiveModalLabel"><i class="bi-exclamation-triangle-fill"></i> Διαχείριση Σχολικού Έτους</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        
        <!-- Tabs Nav -->
        <ul class="nav nav-tabs bg-light pt-2 px-2" id="archiveTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active text-danger fw-bold" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archive-panel" type="button" role="tab" aria-controls="archive-panel" aria-selected="true"><i class="bi-archive"></i> Νέο Αρχείο</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link text-primary fw-bold" id="restore-tab" data-bs-toggle="tab" data-bs-target="#restore-panel" type="button" role="tab" aria-controls="restore-panel" aria-selected="false"><i class="bi-arrow-counterclockwise"></i> Undo / Επαναφορά</button>
          </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content p-3" id="archiveTabsContent">
          <!-- Archive Panel -->
          <div class="tab-pane fade show active" id="archive-panel" role="tabpanel" aria-labelledby="archive-tab">
            <p class="text-danger fw-bold">ΠΡΟΣΟΧΗ: Αυτή η ενέργεια μεταφέρει ΟΛΑ τα τρέχοντα προγράμματα στο αρχείο και μηδενίζει τον πίνακα για τη νέα σχολική χρονιά!</p>
            <form id="archiveForm">
                <input type="hidden" name="action" value="archive">
                <div class="mb-3">
                    <label for="archive_year_suffix" class="form-label fw-bold">Όνομα Σχολικού Έτους (π.χ. 2024-25):</label>
                    <input type="text" class="form-control" id="archive_year_suffix" name="archive_year_suffix" placeholder="YYYY-YY" required pattern="\d{4}-\d{2}" title="Παρακαλούμε χρησιμοποιήστε τη μορφή ΕΕΕΕ-ΕΕ (π.χ. 2024-25)">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmArchive" required>
                    <label class="form-check-label fw-bold" for="confirmArchive">
                        Κατανοώ ότι ο τρέχων πίνακας θα καθαριστεί οριστικά!
                    </label>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-danger"><i class="bi-archive"></i> Αρχειοθέτηση Τώρα</button>
                </div>
            </form>
          </div>
          
          <!-- Restore Panel -->
          <div class="tab-pane fade" id="restore-panel" role="tabpanel" aria-labelledby="restore-tab">
            <p class="text-primary fw-bold">Επαναφορά (Undo): Αυτή η ενέργεια φέρνει πίσω έναν αρχειοθετημένο πίνακα.</p>
            <form id="restoreForm">
                <input type="hidden" name="action" value="restore">
                <div class="mb-3">
                    <label for="restore_year_suffix" class="form-label fw-bold">Επιλέξτε Έτος προς Επαναφορά:</label>
                    <select class="form-select border-primary" id="restore_year_suffix" name="restore_year_suffix" required>
                        <option value="">-- Επιλογή Έτους --</option>
                        <?php foreach($availableYears as $y) { echo '<option value="'.htmlspecialchars($y).'">'.htmlspecialchars($y).'</option>'; } ?>
                    </select>
                </div>
                <div class="alert alert-danger px-3 py-2 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmRestore" required>
                        <label class="form-check-label fw-bold" for="confirmRestore">
                            ΠΡΟΣΟΧΗ: Η επαναφορά θα <u>ΔΙΑΓΡΑΨΕΙ οριστικά</u> όλες τις νέες εγγραφές που τυχόν έγιναν μετέπειτα!
                        </label>
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary"><i class="bi-arrow-counterclockwise"></i> Εκτέλεση Επαναφοράς</button>
                </div>
            </form>
          </div>
        </div>

      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ακύρωση & Κλείσιμο</button>
      </div>
    </div>
  </div>
</div>
<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Επεξεργασία Προγράμματος</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
						<form id="editForm">
            <div class="modal-body">
                <!-- Edit record details content with tabs -->
                <ul class="nav nav-tabs" id="editTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="school-tab" data-bs-toggle="tab" href="#school" role="tab" aria-controls="school" aria-selected="true">Σχολείο</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="program-tab" data-bs-toggle="tab" href="#program" role="tab" aria-controls="program" aria-selected="false">Πρόγραμμα</a>
                    </li>
										<li class="nav-item">
                        <a class="nav-link" id="teachers-tab" data-bs-toggle="tab" href="#teachers" role="tab" aria-controls="teachers" aria-selected="false">Εκπαιδευτικοί</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="progress-tab" data-bs-toggle="tab" href="#progress" role="tab" aria-controls="progress" aria-selected="false">Πρόοδος</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="status-tab" data-bs-toggle="tab" href="#status" role="tab" aria-controls="status" aria-selected="false">Κατάσταση</a>
                    </li>
                </ul>
                <div class="tab-content" id="editTabsContent">
                    <!-- Edit record details for each tab -->
                    <div class="tab-pane fade show active" id="school" role="tabpanel" aria-labelledby="school-tab">
                        <!-- School details content -->
												<input type="hidden" class="form-control" id="id" name="id" value=0>
												<div class="form-group">
													<label for="sch1">Σχολείο</label>
													<select class="form-select" id="sch1" name="sch1" style="width: 50%" required></select>
												</div>
												<div class="form-group">
													<label for="princ1">Διευθυντής/-ντρια - Πρ/νος/-η σχολείου</label>
													<input type="text" class="form-control" id="princ1" name="princ1">
												</div>
												<div class="form-group">
													<label for="sch2">Συνεργαζόμενο Σχολείο</label>
													<select class="form-select" id="sch2" name="sch2" style="width: 50%"></select>
												</div>
												<div class="form-group">
													<label for="princ2">Διευθυντής/-ντρια - Πρ/νος/-η συνεργαζόμενου σχολείου</label>
													<input type="text" class="form-control" id="princ2" name="princ2">
												</div>
										</div>
                    <div class="tab-pane fade" id="program" role="tabpanel" aria-labelledby="program-tab">
                        <!-- Program details content -->
												<div class="form-group">
													<label for="titel">Τίτλος Προγράμματος *</label>
													<input type="text" class="form-control" id="titel" name="titel" required>
												</div>
												<div class="form-group">
													<label for="categ">Κατηγορία</label>
													<select name="categ" id="categ" class="form-select" aria-label="Κατηγορία">
														<option value="Αγωγής Υγείας">Αγωγής Υγείας</option>
														<option value="Περιβαλλοντικής Εκπαίδευσης">Περιβαλλοντικής Εκπαίδευσης</option>
														<option value="Πολιτιστικών Θεμάτων">Πολιτιστικών Θεμάτων</option>
													</select>
												</div>
												<div class="form-group">
													<label for="subti">Υποτίτλος</label>
													<input type="text" class="form-control" id="subti" name="subti">
												</div>
												<div class="form-group">
													<label for="praxi">Πράξη</label>
													<input type="text" class="form-control" id="praxi" name="praxi" pattern="[0-9]*" title="Please enter only numeric values." value=0>
												</div>
												<div class="form-group">
													<label for="praxidate">Ημερομηνία Πράξης</label>
													<input type="text" class="form-control datepicker" id="praxidate" name="praxidate">
												</div>
												<div class="form-group">
													<label for="grade">Τάξη/-εις</label>
													<input type="text" class="form-control" id="grade" name="grade">
												</div>
												<div class="form-group">
													<label for="nr">Αριθμός Συμμετεχόντων</label>
													<input type="text" class="form-control" id="nr" name="nr" value=0>
												</div>
												<div class="form-group">
													<label for="nr_boys">Αριθμός Αγοριών</label>
													<input type="text" class="form-control" id="nr_boys" name="nr_boys" value=0>
												</div>
												<div class="form-group">
													<label for="nr_girls">Αριθμός Κοριτσιών</label>
													<input type="text" class="form-control" id="nr_girls" name="nr_girls" value=0>
												</div>
												<div class="form-group">
													<label for="cha">Χαρακτηριστικά ομάδας</label>
													<select name="cha" id="cha" class="form-select" aria-label="Κατηγορία">
														<option value="Μικτή ομάδα">Μικτή ομάδα</option>
														<option value="Αμιγές τμήμα">Αμιγές τμήμα</option>
													</select>
												</div>
												<div class="form-group">
													<label for="arxeio">Ύπαρξη αρχείου Σχολικών Δραστηριοτήτων στο Σχολείο</label>
													<select name="arxeio" id="arxeio" class="form-select" aria-label="Κατηγορία">
														<option value="Όχι">Όχι</option>
														<option value="Ναι">Ναι</option>
													</select>
												</div>
												<div class="form-group">
													<label for="theme">Θεματολογία</label>
													<textarea class="form-control" id="theme" name="theme"></textarea>
												</div>
												<div class="form-group">
													<label for="goal">Στόχος</label>
													<textarea class="form-control" id="goal" name="goal"></textarea>
												</div>
												<div class="form-group">
													<label for="notes">Σχόλια - Σημειώσεις</label>
													<textarea class="form-control" id="notes" name="notes"></textarea>
												</div>
                    </div>
										<div class="tab-pane fade" id="teachers" role="tabpanel" aria-labelledby="teachers-tab">
												<!-- Teachers details content -->
												<div class="form-group">
													<label for="nam1">Όν/μο Εκπαιδευτικού 1 *</label>
													<input type="text" class="form-control" id="nam1" name="nam1" required>
												</div>
												<div class="form-group">
													<label for="eid1">Ειδικότητα Εκπαιδευτικού 1</label>
													<select name="eid1" id="eid1" class="form-select" aria-label="Ειδικότητα">
														<option value="ΠΕ05">ΠΕ05</option>
														<option value="ΠΕ06">ΠΕ06</option>
														<option value="ΠΕ07">ΠΕ07</option>
														<option value="ΠΕ08">ΠΕ08</option>
														<option value="ΠΕ11">ΠΕ11</option>
														<option value="ΠΕ79">ΠΕ79</option>
														<option value="ΠΕ86">ΠΕ86</option>
														<option value="ΠΕ60">ΠΕ60</option>
														<option value="ΠΕ70">ΠΕ70</option>
														<option value="ΠΕ91">ΠΕ91</option>
														<option value="ΠΕ61">ΠΕ61</option>
														<option value="ΠΕ71">ΠΕ71</option>
														<option value="Άλλο">Άλλο</option>
													</select>
												</div>
												<div class="form-group">
													<label for="email1">Email Εκπαιδευτικού 1</label>
													<input type="text" class="form-control" id="email1" name="email1">
												</div>
												<div class="form-group">
													<label for="email1">Τηλέφωνο Εκπαιδευτικού 1</label>
													<input type="text" class="form-control" id="mob1" name="mob1">
												</div>
												<hr class="border-4" />
												<div class="form-group">
													<label for="nam1">Όν/μο Εκπαιδευτικού 2</label>
													<input type="text" class="form-control" id="nam2" name="nam2">
												</div>
												<div class="form-group">
													<label for="eid2">Ειδικότητα Εκπαιδευτικού 2</label>
													<select name="eid2" id="eid2" class="form-select" aria-label="Ειδικότητα">
														<option value="ΠΕ05">ΠΕ05</option>
														<option value="ΠΕ06">ΠΕ06</option>
														<option value="ΠΕ07">ΠΕ07</option>
														<option value="ΠΕ08">ΠΕ08</option>
														<option value="ΠΕ11">ΠΕ11</option>
														<option value="ΠΕ79">ΠΕ79</option>
														<option value="ΠΕ86">ΠΕ86</option>
														<option value="ΠΕ60">ΠΕ60</option>
														<option value="ΠΕ70">ΠΕ70</option>
														<option value="ΠΕ91">ΠΕ91</option>
														<option value="ΠΕ61">ΠΕ61</option>
														<option value="ΠΕ71">ΠΕ71</option>
														<option value="Άλλο">Άλλο</option>
													</select>
												</div>
												<div class="form-group">
													<label for="email1">Email Εκπαιδευτικού 2</label>
													<input type="text" class="form-control" id="email2" name="email2">
												</div>
												<div class="form-group">
													<label for="email1">Τηλέφωνο Εκπαιδευτικού 2</label>
													<input type="text" class="form-control" id="mob2" name="mob2">
												</div>
												<hr class="border-4" />
												<div class="form-group">
													<label for="nam1">Όν/μο Εκπαιδευτικού 3</label>
													<input type="text" class="form-control" id="nam3" name="nam3">
												</div>
												<div class="form-group">
													<label for="eid3">Ειδικότητα Εκπαιδευτικού 3</label>
													<select name="eid3" id="eid3" class="form-select" aria-label="Ειδικότητα">
														<option value="ΠΕ05">ΠΕ05</option>
														<option value="ΠΕ06">ΠΕ06</option>
														<option value="ΠΕ07">ΠΕ07</option>
														<option value="ΠΕ08">ΠΕ08</option>
														<option value="ΠΕ11">ΠΕ11</option>
														<option value="ΠΕ79">ΠΕ79</option>
														<option value="ΠΕ86">ΠΕ86</option>
														<option value="ΠΕ60">ΠΕ60</option>
														<option value="ΠΕ70">ΠΕ70</option>
														<option value="ΠΕ91">ΠΕ91</option>
														<option value="ΠΕ61">ΠΕ61</option>
														<option value="ΠΕ71">ΠΕ71</option>
														<option value="Άλλο">Άλλο</option>
													</select>
												</div>
												<div class="form-group">
													<label for="email1">Email Εκπαιδευτικού 3</label>
													<input type="text" class="form-control" id="email3" name="email3">
												</div>
												<div class="form-group">
													<label for="email1">Τηλέφωνο Εκπαιδευτικού 3</label>
													<input type="text" class="form-control" id="mob3" name="mob3">
												</div>
                    </div>
                    <div class="tab-pane fade" id="progress" role="tabpanel" aria-labelledby="progress-tab">
                        <!-- Progress details content -->
												<div class="form-group">
													<label for="meth">Μέθοδος</label>
													<textarea type="text" class="form-control" id="meth" name="meth"></textarea>
												</div>
												<div class="form-group">
													<label for="month">Μήνας έναρξης</label>
													<input type="text" class="form-control" id="month" name="month">
												</div>
												<div class="form-group">
													<label for="m1">1ος μήνας</label>
													<input type="text" class="form-control" id="m1" name="m1">
												</div>
												<div class="form-group">
													<label for="m2">2ος μήνας</label>
													<input type="text" class="form-control" id="m2" name="m2">
												</div>
												<div class="form-group">
													<label for="m3">3ος μήνας</label>
													<input type="text" class="form-control" id="m3" name="m3">
												</div>
												<div class="form-group">
													<label for="m4">4ος μήνας</label>
													<input type="text" class="form-control" id="m4" name="m4">
												</div>
												<div class="form-group">
													<label for="m5">5ος μήνας</label>
													<input type="text" class="form-control" id="m5" name="m5">
												</div>
												<div class="form-group">
													<label for="visit">Αριθμός προβλεπόμενων επισκέψεων - Συνεργασίες με άλλους φορείς</label>
													<input type="text" class="form-control" id="visit" name="visit">
												</div>
												<div class="form-group">
													<label for="foreis">Φορείς επισκέψεων</label>
													<input type="text" class="form-control" id="foreis" name="foreis">
												</div>
												<div class="form-group">
													<label for="dura">Διάρκεια</label>
													<select name="dura" id="dura" class="form-select" aria-label="Έλεγχος">
														<option value="3 μήνες">3 μήνες</option>
														<option value="4 μήνες">4 μήνες</option>
														<option value="5 μήνες">5 μήνες</option>
													</select>
												</div>
                    </div>
                    <div class="tab-pane fade" id="status" role="tabpanel" aria-labelledby="status-tab">
                        <!-- Status details content -->
												<div class="form-group">
													<label for="chk">Βεβαιώνεται ότι ο/η δ/ντής/τρια ή προϊσταμένος/νη της σχολικής μονάδας έλεγξε το παρόν σχέδιο προγράμματος σχολικών δραστηριοτήτων, έκανε απαραίτητες τυχόν διορθώσεις και βεβαιώνει ότι τα στοιχεία που αναφέρονται στο παρόν σχέδιο προγράμματος είναι σωστά.</label>
													<select name="chk" id="chk" class="form-select" aria-label="Έλεγχος">
														<option value="Ναι">Ναι</option>
														<option value="Όχι">Όχι</option>
													</select>
												</div>
												<div class="form-group">
													<label for="vev">Ο/Η  δ/ντής/τρια ή προϊσταμένος/νη βεβαιώνει ότι το συγκεκριμένο σχέδιο προγράμματος σχολικών δραστηριοτήτων ολοκληρώθηκε επιτυχώς και τα αποτελέσματα του προγράμματος είναι διαθέσιμα στο σχολική μονάδα.</label>
													<select name="vev" id="vev" class="form-select" aria-label="Βεβαίωση">
														<option value="Όχι">Όχι</option>
														<option value="Ναι">Ναι</option>
													</select>
												</div>
                    </div>
                </div>
            </div> <!-- of modal body -->
						<div class="modal-footer">
								<button type="button" class="btn btn-secondary btn-danger close-btn" data-bs-dismiss="modal"><i class="bi-x-circle"></i>&nbsp;Κλείσιμο</button>
								<button type="submit" class="btn btn-primary btn-success save-btn"><i class="bi-save"></i>&nbsp;Αποθήκευση</button>
						</div>
						</div> <!-- of form --> 
        </div> <!-- of modal content -->
    </div> <!-- of modal dialog -->
</div> <!-- of modal -->

<!-- Configuration Modal -->
<div class="modal fade" id="configModal" tabindex="-1" aria-labelledby="configModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="configModalLabel">Παράμετροι εφαρμογής</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Tabs Nav -->
        <ul class="nav nav-tabs mb-3" id="configTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-settings" type="button" role="tab">Γενικά</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="metadata-tab" data-bs-toggle="tab" data-bs-target="#metadata-settings" type="button" role="tab">Πρωτόκολλα/Ημερομηνίες</button>
          </li>
        </ul>
        
        <!-- Tabs Content -->
        <div class="tab-content" id="configTabsContent">
          <!-- General Settings Tab -->
          <div class="tab-pane fade show active" id="general-settings" role="tabpanel">
            <p class="text-muted small">Φορτώνει ρυθμίσεις...</p>
          </div>
          
          <!-- Metadata Tab -->
          <div class="tab-pane fade" id="metadata-settings" role="tabpanel">
            <div class="mb-3">
              <label for="selectMetadataYear" class="form-label fw-bold">Επιλογή Σχολικού Έτους</label>
              <div class="input-group">
                <select class="form-select" id="selectMetadataYear">
                  <option value="">Φορτώνει έτη...</option>
                </select>
                <button type="button" id="btnCreateNextYear" class="btn btn-outline-success">
                  <i class="bi bi-plus-circle"></i> Νέο Έτος
                </button>
              </div>
            </div>
            <hr>
            <div id="yearProtocolInputs" class="d-none">
              <div class="mb-3">
                <label for="meta_p_num" class="form-label">Αρ. Πρωτοκόλλου</label>
                <input type="text" class="form-control" id="meta_p_num">
              </div>
              <div class="mb-3">
                <label for="meta_p_date" class="form-label">Ημερομηνία Πρωτοκόλλου</label>
                <input type="date" class="form-control" id="meta_p_date">
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <input type="hidden" id="currentSxetos" value="<?php echo $prSxetos; ?>">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi-x-circle"></i>&nbsp;Κλείσιμο</button>&nbsp;
        <button type="button" class="btn btn-primary" id="saveConfigBtn"><i class="bi-save"></i>&nbsp;Αποθήκευση</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.js" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous" type="text/javascript"></script>
<script src="https://cdn.datatables.net/2.0.3/js/dataTables.js" type="text/javascript"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.1/js/dataTables.buttons.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.1/js/buttons.dataTables.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.1/js/buttons.colVis.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.1/js/buttons.html5.min.js"></script>
<!-- Add SweetAlert2 from CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Bootstrap Font Icon CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">

<input type="hidden" id="isAdmin" value="<?php echo $_SESSION['admin'] ? '1' : '0'; ?>">
<script src="script.js?v=<?php echo time(); ?>" type="text/javascript"></script>

</body>

</html>
