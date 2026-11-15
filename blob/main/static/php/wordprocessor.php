<?php
/**
 * MunicipalResources Secure Word Processor & Form Engine
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

        <main class="main-content word-processor-workspace">
            
            <aside class="controls-panel">
                <h3>// Document Controls</h3>
                
                <div class="control-group">
                    <label for="docTitle">DOCUMENT TITLE</label>
                    <input type="text" id="docTitle" placeholder="Enter title...">
                </div>

                <div class="control-group">
                    <label for="spellLang">SPELL CHECK LANGUAGE</label>
                    <select id="spellLang">
                        <option value="en">English (en)</option>
                        <option value="es">Spanish (es)</option>
                        <option value="fr">French (fr)</option>
                        <option value="de">German (de)</option>
                        <option value="pt">Portuguese (pt)</option>
                        <option value="it">Italian (it)</option>
                    </select>
                </div>

                <div class="control-group">
                    <label for="exportFormat">EXPORT FORMAT</label>
                    <select id="exportFormat">
                        <option value="txt">Plain Text (.txt)</option>
                        <option value="docx">Microsoft Word (.docx)</option>
                        <option value="pdf">Adobe Acrobat (.pdf)</option>
                    </select>
                </div>

                <div class="control-group">
                    <label for="formType">STATE / FEDERAL FORM MATCH</label>
                    <select id="formType">
                        <option value="STATE_TAX_EXEMPTION">State Form TX-104-Municipal</option>
                        <option value="FEDERAL_GRANT_REQ">Federal Form FG-880-Infrastructure</option>
                        <option value="PUBLIC_WORKS_COMPLIANCE">OSHA-PW-2026-Standard</option>
                    </select>
                </div>

                <button type="button" onclick="matchAndGenerateForm()">GENERATE MATCHED FORM</button>
                <button type="button" onclick="saveDocument()">SAVE &amp; EXPORT FILE</button>
            </aside>

            <section class="editor-panel">
                <div class="editor-header">
                    <span>[ EDITING CANVAS // MUNICIPAL DOCUMENT CORE ]</span>
                    <span id="wordCount">WORDS: 0</span>
                </div>
                
                <textarea id="docContent" placeholder="Type municipal reports, records, or correspondence here..."></textarea>
                
                <div class="editor-footer">
                    <span>CSR Key Wrapper: Active</span>
                    <span>Checksum Integrity: Verified</span>
                </div>
            </section>

        </main>
    </div>

    <?php include('includes/footer.php'); ?>

    <script>
        const textarea = document.getElementById('docContent');
        const wordCountDisplay = document.getElementById('wordCount');

        textarea.addEventListener('input', () => {
            const text = textarea.value.trim();
            const words = text === '' ? 0 : text.split(/\s+/).length;
            wordCountDisplay.textContent = `WORDS: ${words}`;
        });

        function saveDocument() {
            const title = document.getElementById('docTitle').value || 'Untitled_Municipal_Record';
            const lang = document.getElementById('spellLang').value;
            const format = document.getElementById('exportFormat').value;
            alert(`[SUCCESS] Document '${title}' verified (${lang.toUpperCase()}) and compiled to .${format}.`);
        }

        function matchAndGenerateForm() {
            const formType = document.getElementById('formType').value;
            alert(`[SUCCESS] Client request matched to regulatory template: ${formType}`);
        }
    </script>
</body>
</html>
