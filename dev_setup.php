<?php
session_start();

// ====== SECURITY CHECK: LOCALHOST ONLY ======
$allowed_ips = ['127.0.0.1', '::1'];
$client_ip = $_SERVER['REMOTE_ADDR'];

if (!in_array($client_ip, $allowed_ips)) {
    http_response_code(403);
    die('<h1 style="color:red; font-family:sans-serif; text-align:center; margin-top:50px;">403 Forbidden</h1><p style="text-align:center; font-family:sans-serif;">This utility is strictly restricted to local development environments.</p>');
}
// ============================================

require_once('conf.php');

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prDebug_new = isset($_POST['prDebug']) ? 1 : 0;
    $prsch_name_new = isset($_POST['prsch_name']) ? $_POST['prsch_name'] : '';
    $pruid_new = isset($_POST['pruid']) ? $_POST['pruid'] : '';
    $prem1_new = isset($_POST['prem1']) ? $_POST['prem1'] : '';
    $prDbusername_new = isset($_POST['prDbusername']) ? $_POST['prDbusername'] : '';
    $prDbpassword_new = isset($_POST['prDbpassword']) ? $_POST['prDbpassword'] : '';

    $content = "<?php\n";
    $content .= "// Debug options\n";
    $content .= "// \$prDebug: set to 1 for local testing, 0 for production\n";
    $content .= "\$prDebug = " . $prDebug_new . ";\n";
    $content .= "// for testing when debug=1\n";
    $content .= "\$prsch_name = '" . addslashes($prsch_name_new) . "';\n";
    $content .= "\$pruid = '" . addslashes($pruid_new) . "';\n";
    $content .= "\$prem1 = '" . addslashes($prem1_new) . "';\n";
    $content .= "\$prem2 = '" . addslashes($prem2 ?? '') . "';\n\n";

    $content .= "// Admin Usernames (CAS uid)\n";
    $content .= "\$prAdmin1 = '" . addslashes($prAdmin1 ?? '') . "';\n";
    $content .= "\$prAdmin2 = '" . addslashes($prAdmin2 ?? '') . "';\n";
    $content .= "// DB credentials\n";
    $content .= "\$prDbhost = '" . addslashes($prDbhost ?? '') . "';\n";
    $content .= "\$prDbname = '" . addslashes($prDbname ?? '') . "';\n";
    $content .= "\$prDbusername = '" . addslashes($prDbusername_new) . "';\n";
    $content .= "\$prDbpassword = '" . addslashes($prDbpassword_new) . "';\n\n";

    $content .= "\$prTable = '" . addslashes($prTable ?? 'progs') . "';\n";
    $content .= "\$schTable = '" . addslashes($schTable ?? 'progs_schools') . "';\n";
    $content .= "?>";

    if (file_put_contents('conf.php', $content) !== false) {
        $message = '<div class="alert alert-success">Configuration saved successfully! <a href="index.php">Go to Dashboard</a></div>';
        // Reload Variables to display updated values
        $prDebug = $prDebug_new;
        $pruid = $pruid_new;
        $prsch_name = $prsch_name_new;
        $prem1 = $prem1_new;
        $prDbusername = $prDbusername_new;
        $prDbpassword = $prDbpassword_new;
    } else {
        $message = '<div class="alert alert-danger">Error: Could not write to conf.php! Permission denied.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Development Setup</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .setup-card { max-width: 600px; margin: 30px auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="setup-card">
        <h3 class="mb-4 text-primary">⚙️ Secure Local Setup</h3>
        <p class="text-muted small">This page is strictly restricted to 127.0.0.1. Manage your local developer settings securely here.</p>
        <hr>
        <?= $message ?>
        <form method="POST" action="">
            <div class="form-check form-switch mb-4 mt-4">
                <input class="form-check-input" type="checkbox" id="prDebug" name="prDebug" <?= $prDebug ? 'checked' : '' ?> style="transform: scale(1.3); margin-left: -2em;">
                <label class="form-check-label ms-2" for="prDebug" style="font-weight: 600;">Enable Local Debug Mode</label>
                <small class="d-block text-muted mt-1">If enabled, bypasses CAS authentication and logs you in instantly as the Test User.</small>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="pruid" class="form-label fw-bold">Simulated User ID</label>
                    <input type="text" class="form-control bg-light" id="pruid" name="pruid" value="<?= htmlspecialchars($pruid) ?>" placeholder="e.g. 9170101" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="prem1" class="form-label fw-bold">Simulated Email</label>
                    <input type="email" class="form-control bg-light" id="prem1" name="prem1" value="<?= htmlspecialchars($prem1) ?>" placeholder="mail@sch.gr">
                </div>
            </div>

            <div class="mb-3">
                <label for="prsch_name" class="form-label fw-bold">Simulated School Name</label>
                <input type="text" class="form-control bg-light" id="prsch_name" name="prsch_name" value="<?= htmlspecialchars($prsch_name) ?>" placeholder="e.g. 1ο Δημοτικό κλπ">
            </div>

            <hr class="my-4">

            <div class="mb-3">
                <label for="prDbusername" class="form-label fw-bold font-monospace small uppercase text-muted">MySQL Username</label>
                <input type="text" class="form-control bg-light" id="prDbusername" name="prDbusername" value="<?= htmlspecialchars($prDbusername) ?>" required>
            </div>

            <div class="mb-4">
                <label for="prDbpassword" class="form-label fw-bold font-monospace small uppercase text-muted">MySQL Password</label>
                <input type="text" class="form-control bg-light" id="prDbpassword" name="prDbpassword" value="<?= htmlspecialchars($prDbpassword) ?>">
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg shadow-sm">Apply Configuration</button>
                <a href="index.php" class="btn btn-outline-secondary">Return to Application</a>
            </div>
        </form>
    </div>
</body>
</html>
