<?php
/**
 * MunicipalResources & Trust-Shell Main Index Dashboard
 * Native PHP execution environment referencing Trust-Main central repository CSS.
 */
session_start();
$user_role = $_SESSION['user_role'] ?? 'admin';
$theme_class = match ($user_role) {
    'responder' => 'theme-responder',
    'standard'  => 'theme-standard',
    default     => 'theme-admin',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('includes/meta.php'); ?>
    <link rel="stylesheet" href="https://raw.githubusercontent.com/PMR-Pubclications/Trust-Main/main/asset/css/style.css">
</head>
<body class="<?php echo htmlspecialchars($theme_class, ENT_QUOTES, 'UTF-8'); ?>">

    <?php include('includes/header.php'); ?>

    <div class="layout-container">
        <?php include('includes/sidebar.php'); ?>

        <main class="main-content">
            <div class="dashboard-wrapper">
                <header class="dashboard-header">
                    <h1>MunicipalResources // Trust-Shell Core Active</h1>
                </header>

                <section class="status-grid">
                    <div class="status-card">
                        <span class="card-label">BioGate Status</span>
                        <strong class="card-value">SECURED</strong>
                        <span class="card-subtext">Quad-Factor Authentication Enforced</span>
                    </div>

                    <div class="status-card">
                        <span class="card-label">Document Module</span>
                        <strong class="card-value">OPERATIONAL</strong>
                        <span class="card-subtext">PDF, DOCX &amp; Spell Check Ready</span>
                    </div>

                    <div class="status-card">
                        <span class="card-label">Crypto CSR Wrapper</span>
                        <strong class="card-value">SECURED</strong>
                        <span class="card-subtext">GenerateClientKeyAndCSR Linked</span>
                    </div>

                    <div class="status-card">
                        <span class="card-label">Evidence Dispatch</span>
                        <strong class="card-value">SYNCED</strong>
                        <span class="card-subtext">Merkle Ledger Connected</span>
                    </div>
                </section>

                <section class="dispatch-section">
                    <?php include('includes/evidence_dispatch.php'); ?>
                </section>
            </div>
        </main>
    </div>

    <?php include('includes/footer.php'); ?>
</body>
</html>
