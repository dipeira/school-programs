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
    $pruid_new = isset($_POST['pruid']) ? $_POST['pruid'] : '';
    $prDbusername_new = isset($_POST['prDbusername']) ? $_POST['prDbusername'] : '';
    $prDbpassword_new = isset($_POST['prDbpassword']) ? $_POST['prDbpassword'] : '';

    $content = "<?php\n";
    $content .= "// Debug options\n";
    $content .= "// \$prDebug: set to 1 for local testing, 0 for production\n";
    $content .= "\$prDebug = " . $prDebug_new . ";\n";
    $content .= "// for testing when debug=1\n";
    $content .= "\$prsch_name = '" . addslashes($prsch_name ?? '') . "';\n";
    $content .= "\$pruid = '" . addslashes($pruid_new) . "';\n";
    $content .= "\$prem1 = '" . addslashes($prem1 ?? '') . "';\n";
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
        .setup-card { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="setup-card">
        <h3 class="mb-4 text-primary">⚙️ Secure Local Setup</h3>
        <p class="text-muted">This page is completely invisible to public internet traffic (Restricted to 127.0.0.1). Manage your local developer settings securely here.</p>
        <hr>
        <?= $message ?>
        <form method="POST" action="">
            <div class="form-check form-switch mb-4 mt-4">
                <input class="form-check-input" type="checkbox" id="prDebug" name="prDebug" <?= $prDebug ? 'checked' : '' ?> style="transform: scale(1.3); margin-left: -2em;">
                <label class="form-check-label ms-2" for="prDebug" style="font-weight: 600;">Enable Local Debug Mode</label>
                <small class="d-block text-muted mt-1">If enabled, bypasses CAS authentication and logs you in instantly as the Test User.</small>
            </div>
            
            <div class="mb-3">
                <label for="pruid" class="form-label fw-bold">Simulated Test User ID (e.g. 9170101)</label>
                <input type="text" class="form-control bg-light" id="pruid" name="pruid" value="<?= htmlspecialchars($pruid) ?>" required>
            </div>

            <div class="mb-3">
                <label for="prDbusername" class="form-label fw-bold">Local MySQL Username</label>
                <input type="text" class="form-control bg-light" id="prDbusername" name="prDbusername" value="<?= htmlspecialchars($prDbusername) ?>" required>
            </div>

            <div class="mb-4">
                <label for="prDbpassword" class="form-label fw-bold">Local MySQL Password</label>
                <input type="text" class="form-control bg-light" id="prDbpassword" name="prDbpassword" value="<?= htmlspecialchars($prDbpassword) ?>">
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Apply Configuration to conf.php</button>
                <a href="index.php" class="btn btn-outline-secondary">Return to Application</a>
            </div>
        </form>
    </div>
</body>
</html>
