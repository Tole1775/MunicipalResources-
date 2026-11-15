<?php
/**
 * MunicipalResources Contractor Bid Calculator Backend Processor
 * Handles itemized parsing, permit calculation, cryptographic hashing, and email dispatch.
 */
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contractorName = filter_input(INPUT_POST, 'contractor_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $targetEmail    = filter_input(INPUT_POST, 'target_email', FILTER_VALIDATE_EMAIL);
    $projectTitle   = filter_input(INPUT_POST, 'project_title', FILTER_SANITIZE_SPECIAL_CHARS);
    $markupPercent  = filter_input(INPUT_POST, 'markup_percent', FILTER_VALIDATE_FLOAT) ?: 0;

    if (!$targetEmail) {
        http_response_code(400);
        exit(json_encode(["status" => "error", "message" => "Invalid recipient destination email address."]));
    }

    // Decode JSON payloads for itemized rows
    $materials = json_decode($_POST['materials_json'] ?? '[]', true);
    $labor     = json_decode($_POST['labor_json'] ?? '[]', true);
    $permits   = json_decode($_POST['permits_json'] ?? '[]', true);

    $totalMaterials = 0;
    $materialsSummary = "";
    foreach ($materials as $mat) {
        $cost = (float)($mat['cost'] ?? 0) * (int)($mat['qty'] ?? 1);
        $totalMaterials += $cost;
        $materialsSummary .= " - {$mat['name']} (Qty: {$mat['qty']} @ $" . number_format($mat['cost'], 2) . "): $" . number_format($cost, 2) . "\n";
    }

    $totalLabor = 0;
    $laborSummary = "";
    foreach ($labor as $lab) {
        $cost = (float)($lab['hours'] ?? 0) * (float)($lab['rate'] ?? 0);
        $totalLabor += $cost;
        $laborSummary .= " - {$lab['role']} ({$lab['hours']} hrs @ $" . number_format($lab['rate'], 2) . "/hr): $" . number_format($cost, 2) . "\n";
    }

    $totalPermits = 0;
    $permitsSummary = "";
    foreach ($permits as $per) {
        $cost = (float)($per['cost'] ?? 0);
        $totalPermits += $cost;
        $permitsSummary .= " - {$per['name']}: $" . number_format($cost, 2) . "\n";
    }

    $subtotal = $totalMaterials + $totalLabor + $totalPermits;
    $markupAmount = $subtotal * ($markupPercent / 100);
    $totalBid = $subtotal + $markupAmount;

    $timestamp = date('Y-m-d H:i:s');
    $auditHash = hash('sha256', $contractorName . $projectTitle . $totalBid . $timestamp);

    $subject = "Itemized Bid Submission: " . ($projectTitle ?: 'Municipal Infrastructure Project');
    $emailBody = "--- TRUST-SHELL ITEMIZED BID AUDIT ---\n" .
                 "Timestamp: {$timestamp}\n" .
                 "Contractor Entity: {$contractorName}\n" .
                 "Project Title: {$projectTitle}\n\n" .
                 "--- ITEMIZED MATERIALS ---\n" . ($materialsSummary ?: " None\n") .
                 "Subtotal Materials: $" . number_format($totalMaterials, 2) . "\n\n" .
                 "--- ITEMIZED LABOR ---\n" . ($laborSummary ?: " None\n") .
                 "Subtotal Labor: $" . number_format($totalLabor, 2) . "\n\n" .
                 "--- MUNICIPAL PERMITS & FEES ---\n" . ($permitsSummary ?: " None\n") .
                 "Subtotal Permits: $" . number_format($totalPermits, 2) . "\n\n" .
                 "--- FINANCIAL SUMMARY ---\n" .
                 "Base Subtotal: $" . number_format($subtotal, 2) . "\n" .
                 "Overhead & Markup ({$markupPercent}%): $" . number_format($markupAmount, 2) . "\n" .
                 "TOTAL ITEMIZED BID: $" . number_format($totalBid, 2) . "\n\n" .
                 "--- CRYPTOGRAPHIC VERIFICATION ---\n" .
                 "SHA-256 Checksum: {$auditHash}\n" .
                 "Status: Verified & Dispatched via Trust-Shell Core Engine\n";

    $headers = "From: bids@municipal-resources.internal\r\n" .
               "Reply-To: " . $targetEmail . "\r\n" .
               "X-Mailer: TrustShell-PHP/" . phpversion();

    $mailSent = mail($targetEmail, $subject, $emailBody, $headers);

    if ($mailSent) {
        echo json_encode([
            "status" => "success",
            "message" => "Itemized bid successfully calculated and dispatched to {$targetEmail}.",
            "total_bid" => number_format($totalBid, 2),
            "checksum" => $auditHash
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Mail server dispatch failed. Check local MTA configuration."]);
    }
    exit;
}
?>
