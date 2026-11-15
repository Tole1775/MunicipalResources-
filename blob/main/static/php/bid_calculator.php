<?php
/**
 * MunicipalResources Itemized Contractor Bid Calculator Interface
 * Renders with Trust-Main external stylesheet and TrustShell role themes.
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
    <style>
        .item-row { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem; }
        .permit-row { display: grid; grid-template-columns: 3fr 1fr auto; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem; }
        .item-row input, .permit-row input { width: 100%; padding: 0.4rem; }
        .remove-btn { background: #dc2626; color: white; border: none; padding: 0.4rem 0.8rem; cursor: pointer; border-radius: 2px; }
        .add-btn { background: var(--badge-bg); color: var(--badge-text); border: 1px solid var(--text-primary); padding: 0.4rem 0.8rem; cursor: pointer; border-radius: 2px; font-size: 0.8rem; margin-top: 0.5rem; }
    </style>
</head>
<body class="<?php echo htmlspecialchars($theme_class, ENT_QUOTES, 'UTF-8'); ?>">

    <?php include('includes/header.php'); ?>

    <div class="layout-container">
        <?php include('includes/sidebar.php'); ?>

        <main class="main-content">
            <div class="dashboard-wrapper">
                <header class="dashboard-header">
                    <h1>Itemized Contractor Bid Calculator &amp; Secure Dispatch</h1>
                </header>

                <div class="dashboard-wrapper" style="max-width: 900px; margin: 0 auto;">
                    <form id="bidForm" onsubmit="submitBid(event)" style="display: flex; flex-direction: column; gap: 1.5rem;">
                        
                        <!-- General Info Card -->
                        <div class="status-card" style="display: flex; flex-direction: column; gap: 1rem;">
                            <span class="card-label">Project &amp; Destination Details</span>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="control-group">
                                    <label for="contractor_name">Contractor / Firm Name</label>
                                    <input type="text" id="contractor_name" name="contractor_name" required placeholder="Enter firm name..." style="width:100%; padding:0.5rem;">
                                </div>
                                <div class="control-group">
                                    <label for="project_title">Project Title / Municipal RFP</label>
                                    <input type="text" id="project_title" name="project_title" required placeholder="Enter project designation..." style="width:100%; padding:0.5rem;">
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="target_email">Dispatch Destination Email</label>
                                <input type="email" id="target_email" name="target_email" required placeholder="destination@city-department.gov" style="width:100%; padding:0.5rem;">
                            </div>
                        </div>

                        <!-- Itemized Materials Card -->
                        <div class="status-card" style="display: flex; flex-direction: column;">
                            <span class="card-label" style="margin-bottom: 0.5rem;">Itemized Materials</span>
                            <div id="materialsContainer">
                                <div class="item-row">
                                    <input type="text" placeholder="Material Description" class="mat-name" required>
                                    <input type="number" placeholder="Qty" class="mat-qty" min="1" value="1" required oninput="calculateTotal()">
                                    <input type="number" placeholder="Unit Cost ($)" class="mat-cost" step="0.01" min="0" required oninput="calculateTotal()">
                                    <button type="type" class="remove-btn" onclick="this.parentElement.remove(); calculateTotal();">X</button>
                                </div>
                            </div>
                            <button type="button" class="add-btn" onclick="addMaterialRow()">+ Add Material Item</button>
                        </div>

                        <!-- Itemized Labor Card -->
                        <div class="status-card" style="display: flex; flex-direction: column;">
                            <span class="card-label" style="margin-bottom: 0.5rem;">Itemized Labor Roles</span>
                            <div id="laborContainer">
                                <div class="item-row">
                                    <input type="text" placeholder="Labor Classification / Role" class="lab-role" required>
                                    <input type="number" placeholder="Hours" class="lab-hours" step="0.5" min="0" value="0" required oninput="calculateTotal()">
                                    <input type="number" placeholder="Hourly Rate ($)" class="lab-rate" step="0.01" min="0" required oninput="calculateTotal()">
                                    <button type="type" class="remove-btn" onclick="this.parentElement.remove(); calculateTotal();">X</button>
                                </div>
                            </div>
                            <button type="button" class="add-btn" onclick="addLaborRow()">+ Add Labor Role</button>
                        </div>

                        <!-- Municipal Permits & Fees Card -->
                        <div class="status-card" style="display: flex; flex-direction: column;">
                            <span class="card-label" style="margin-bottom: 0.5rem;">Municipal Permits &amp; Fees</span>
                            <div id="permitsContainer">
                                <div class="permit-row">
                                    <input type="text" placeholder="Permit / Fee Description (e.g., Right-of-Way, Environmental Review)" class="per-name" required>
                                    <input type="number" placeholder="Cost ($)" class="per-cost" step="0.01" min="0" required oninput="calculateTotal()">
                                    <button type="type" class="remove-btn" onclick="this.parentElement.remove(); calculateTotal();">X</button>
                                </div>
                            </div>
                            <button type="button" class="add-btn" onclick="addPermitRow()">+ Add Permit / Fee</button>
                        </div>

                        <!-- Summary & Dispatch Card -->
                        <div class="status-card" style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 2rem; align-items: center;">
                                <div>
                                    <span class="card-label">Overhead &amp; Markup (%)</span>
                                    <input type="number" id="markup_percent" name="markup_percent" step="0.1" min="0" value="15" style="width: 80px; padding: 0.4rem; margin-top: 0.2rem;" oninput="calculateTotal()">
                                </div>
                                <div>
                                    <span class="card-label">Calculated Total Bid</span>
                                    <div id="liveTotal" class="card-value" style="font-size: 1.8rem; margin-top: 0.2rem;">$0.00</div>
                                </div>
                            </div>
                            <button type="submit" class="nav-link active" style="padding: 0.75rem 1.5rem; cursor: pointer; border: 1px solid var(--text-primary);">
                                DISPATCH ITEMIZED BID
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </main>
    </div>

    <?php include('includes/footer.php'); ?>

    <script>
        function addMaterialRow() {
            const container = document.getElementById('materialsContainer');
            const row = document.createElement('div');
            row.className = 'item-row';
            row.innerHTML = `
                <input type="text" placeholder="Material Description" class="mat-name" required>
                <input type="number" placeholder="Qty" class="mat-qty" min="1" value="1" required oninput="calculateTotal()">
                <input type="number" placeholder="Unit Cost ($)" class="mat-cost" step="0.01" min="0" required oninput="calculateTotal()">
                <button type="button" class="remove-btn" onclick="this.parentElement.remove(); calculateTotal();">X</button>
            `;
            container.appendChild(row);
        }

        function addLaborRow() {
            const container = document.getElementById('laborContainer');
            const row = document.createElement('div');
            row.className = 'item-row';
            row.innerHTML = `
                <input type="text" placeholder="Labor Classification / Role" class="lab-role" required>
                <input type="number" placeholder="Hours" class="lab-hours" step="0.5" min="0" value="0" required oninput="calculateTotal()">
                <input type="number" placeholder="Hourly Rate ($)" class="lab-rate" step="0.01" min="0" required oninput="calculateTotal()">
                <button type="button" class="remove-btn" onclick="this.parentElement.remove(); calculateTotal();">X</button>
            `;
            container.appendChild(row);
        }

        function addPermitRow() {
            const container = document.getElementById('permitsContainer');
            const row = document.createElement('div');
            row.className = 'permit-row';
            row.innerHTML = `
                <input type="text" placeholder="Permit / Fee Description" class="per-name" required>
                <input type="number" placeholder="Cost ($)" class="per-cost" step="0.01" min="0" required oninput="calculateTotal()">
                <button type="button" class="remove-btn" onclick="this.parentElement.remove(); calculateTotal();">X</button>
            `;
            container.appendChild(row);
        }

        function calculateTotal() {
            let totalMaterials = 0;
            document.querySelectorAll('#materialsContainer .item-row').forEach(row => {
                const qty = parseFloat(row.querySelector('.mat-qty').value) || 0;
                const cost = parseFloat(row.querySelector('.mat-cost').value) || 0;
                totalMaterials += qty * cost;
            });

            let totalLabor = 0;
            document.querySelectorAll('#laborContainer .item-row').forEach(row => {
                const hours = parseFloat(row.querySelector('.lab-hours').value) || 0;
                const rate = parseFloat(row.querySelector('.lab-rate').value) || 0;
                totalLabor += hours * rate;
            });

            let totalPermits = 0;
            document.querySelectorAll('#permitsContainer .permit-row').forEach(row => {
                const cost = parseFloat(row.querySelector('.per-cost').value) || 0;
                totalPermits += cost;
            });

            const subtotal = totalMaterials + totalLabor + totalPermits;
            const markup = parseFloat(document.getElementById('markup_percent').value) || 0;
            const total = subtotal + (subtotal * (markup / 100));

            document.getElementById('liveTotal').textContent = '$' + total.toFixed(2);
        }

        async function submitBid(event) {
            event.preventDefault();
            const form = document.getElementById('bidForm');
            const formData = new FormData(form);

            // Serialize materials
            const materials = [];
            document.querySelectorAll('#materialsContainer .item-row').forEach(row => {
                materials.push({
                    name: row.querySelector('.mat-name').value,
                    qty: row.querySelector('.mat-qty').value,
                    cost: row.querySelector('.mat-cost').value
                });
            });

            // Serialize labor
            const labor = [];
            document.querySelectorAll('#laborContainer .item-row').forEach(row => {
                labor.push({
                    role: row.querySelector('.lab-role').value,
                    hours: row.querySelector('.lab-hours').value,
                    rate: row.querySelector('.lab-rate').value
                });
            });

            // Serialize permits
            const permits = [];
            document.querySelectorAll('#permitsContainer .permit-row').forEach(row => {
                permits.push({
                    name: row.querySelector('.per-name').value,
                    cost: row.querySelector('.per-cost').value
                });
            });

            formData.append('materials_json', JSON.stringify(materials));
            formData.append('labor_json', JSON.stringify(labor));
            formData.append('permits_json', JSON.stringify(permits));

            try {
                const response = await fetch('bid_calculator.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (response.ok) {
                    alert(`[SUCCESS] ${result.message}\nTotal Bid: $${result.total_bid}\nChecksum: ${result.checksum}`);
                    form.reset();
                    calculateTotal();
                } else {
                    alert(`[ERROR] ${result.message}`);
                }
            } catch (err) {
                alert('[CRITICAL] Failed to transmit itemized bid through TrustShell network layer.');
            }
        }
    </script>
</body>
</html>
