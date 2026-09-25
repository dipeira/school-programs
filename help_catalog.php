<?php
session_start();
require_once('conf.php');
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != 1) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Βοήθεια για τη Δημοσίευση στον Κατάλογο - Προγράμματα Σχολικών Δραστηριοτήτων</title>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-container {
            flex: 1;
            padding: 40px 20px;
        }
        .help-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .btn-back {
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.2s ease;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <div class="help-card p-5 mb-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-5 pb-3 border-bottom">
                <div>
                    <h2 class="fw-bold text-primary mb-1"><i class="bi bi-info-circle-fill me-2"></i>Οδηγός Δημοσίευσης στον Κατάλογο</h2>
                    <p class="text-muted mb-0">Βοήθεια και πληροφορίες σχετικά με την υποβολή και προβολή σχολικών δραστηριοτήτων</p>
                </div>
                <a href="index.php" class="btn btn-secondary btn-back">
                    <i class="bi bi-arrow-left me-1"></i>Πίσω στο Dashboard
                </a>
            </div>

            <!-- Onboarding Steps -->
            <h4 class="fw-bold text-dark mb-4 text-center">Πώς να καταχωρίσετε το Πρόγραμμά σας</h4>
            <div class="row row-cols-1 row-cols-md-3 g-4 mt-2 text-start justify-content-center" style="max-width: 1000px; margin: 0 auto;">
                <div class="col">
                    <div class="card border-0 bg-light-subtle h-100 p-4 rounded-3 shadow-sm card-step">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-primary rounded-circle p-2 me-2 d-inline-flex align-items-center justify-content-center" style="width:30px; height:30px;">1</span>
                            <h6 class="fw-bold text-dark mb-0">Είσοδος στο Σύστημα</h6>
                        </div>
                        <p class="text-muted small mb-0">Συνδεθείτε στην πλατφόρμα με τους επίσημους κωδικούς ΠΣΔ του σχολείου σας (κουμπί πάνω δεξιά).</p>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 bg-light-subtle h-100 p-4 rounded-3 shadow-sm card-step">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-primary rounded-circle p-2 me-2 d-inline-flex align-items-center justify-content-center" style="width:30px; height:30px;">2</span>
                            <h6 class="fw-bold text-dark mb-0">Καταχώριση Δράσης</h6>
                        </div>
                        <p class="text-muted small mb-0">Συμπληρώστε τα στοιχεία της σχολικής δραστηριότητας, των εκπαιδευτικών και των μαθητών.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 bg-light-subtle h-100 p-4 rounded-3 shadow-sm card-step">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-primary rounded-circle p-2 me-2 d-inline-flex align-items-center justify-content-center" style="width:30px; height:30px;">3</span>
                            <h6 class="fw-bold text-dark mb-0">Δημοσίευση & Φωτογραφίες</h6>
                        </div>
                        <p class="text-muted small mb-0">Ενεργοποιήστε την επιλογή "Δημοσίευση στον κατάλογο", γράψτε μια παρουσίαση και ανεβάστε έως 8 φωτογραφίες.</p>
                    </div>
                </div>
            </div>

            <!-- Showcase Categories -->
            <div class="mt-5 pt-4">
                <h4 class="fw-bold text-dark mb-4 text-center">Κατηγορίες Προγραμμάτων</h4>
                <div class="row row-cols-1 row-cols-md-3 g-4 mt-2 text-start justify-content-center" style="max-width: 1000px; margin: 0 auto;">
                    <div class="col">
                        <div class="card border-0 bg-white h-100 p-4 rounded-3 shadow-sm border-top border-danger border-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-heart-pulse-fill text-danger fs-3 me-2"></i>
                                <h6 class="fw-bold text-dark mb-0">Αγωγή Υγείας</h6>
                            </div>
                            <p class="text-muted small mb-0">Θεματολογίες που αφορούν τη διατροφή, την πρόληψη, την ψυχική υγεία, την ασφάλεια και την κοινωνική ευεξία των μαθητών.</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card border-0 bg-white h-100 p-4 rounded-3 shadow-sm border-top border-success border-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-tree-fill text-success fs-3 me-2"></i>
                                <h6 class="fw-bold text-dark mb-0">Περιβαλλοντική Εκπαίδευση</h6>
                            </div>
                            <p class="text-muted small mb-0">Δράσεις για την προστασία του περιβάλλοντος, την ανακύκλωση, την αειφορία και τη γνωριμία των παιδιών με τη φύση.</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card border-0 bg-white h-100 p-4 rounded-3 shadow-sm border-top border-warning border-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-palette-fill text-warning fs-3 me-2"></i>
                                <h6 class="fw-bold text-dark mb-0">Πολιτιστικά Θέματα</h6>
                            </div>
                            <p class="text-muted small mb-0">Πρωτοβουλίες για τις τέχνες, το θέατρο, τη λογοτεχνία, την τοπική ιστορία, την παράδοση και την πολιτιστική κληρονομιά.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
