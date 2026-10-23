<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use App\Config\Database;
use App\Helpers\Security;
use App\Middleware\RoleMiddleware;
use App\Models\GatePass;
use App\Models\InspectionReport;
use App\Models\AuditLog;

/**
 * InspectorController — Operational Hub for the Local Inspector Role
 * Southern Luzon State University – Alabat Campus (SLSU-Alabat IMS)
 * 
 * Handles Gate Pass Permits (Phase 1) and Inspection and Acceptance Reports (IAR Form AFA-SAF-1.016, Phase 2).
 * Strictly enforces OWASP secure coding, parameterized queries, and CSRF protection.
 */
class InspectorController {

    /**
     * Render the Inspector Dashboard Overview.
     */
    public static function index(): void {
        RoleMiddleware::allow(['inspector', 'admin']);
        $db = Database::getConnection();

        $metrics = InspectionReport::getMetrics($db);
        $recentPasses = GatePass::listAll($db, '', 5, 0)['passes'];
        $recentIars = InspectionReport::listAll($db, 'ALL', '', 5, 0)['reports'];

        Security::generateCsrfToken();

        $title = 'Local Inspector Dashboard';
        $breadcrumb = 'Inspector / Overview';

        require dirname(__DIR__, 2) . '/views/inspector/dashboard.php';
    }

    /**
     * Render the Gate Pass Desk workspace.
     */
    public static function gatePasses(): void {
        RoleMiddleware::allow(['inspector', 'admin']);
        $db = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $result = GatePass::listAll($db, $search, $limit, $offset);
        $passes = $result['passes'];
        $total = $result['total'];

        Security::generateCsrfToken();

        $title = 'Gate Pass Desk';
        $breadcrumb = 'Inspector / Gate Pass Desk';

        require dirname(__DIR__, 2) . '/views/inspector/gate-passes.php';
    }

    /**
     * Hybrid Lookup API endpoint for active borrowed assets.
     * Accessible via GET /api/gate-pass/lookup?barcode=... OR ?borrower=...
     */
    public static function apiLookup(): void {
        RoleMiddleware::allow(['inspector', 'admin', 'custodian']);
        header('Content-Type: application/json; charset=utf-8');

        $db = Database::getConnection();
        $barcode = trim($_GET['barcode'] ?? '');
        $borrower = trim($_GET['borrower'] ?? '');

        try {
            // Mode A: Barcode lookup
            if ($barcode !== '') {
                $lookup = GatePass::lookupByBarcode($db, $barcode);

                if (!$lookup) {
                    http_response_code(400);
                    echo json_encode(['status' => 'error', 'message' => 'Invalid barcode parameter.']);
                    exit;
                }

                if (isset($lookup['error'])) {
                    if ($lookup['error'] === 'not_found') {
                        http_response_code(404);
                    } else {
                        // Exists but not borrowed (HTTP 422 Unprocessable)
                        http_response_code(422);
                    }
                    echo json_encode(['status' => 'error', 'error_type' => $lookup['error'], 'message' => $lookup['message']]);
                    exit;
                }

                echo json_encode($lookup);
                exit;
            }

            // Mode B: Borrower lookup
            if ($borrower !== '') {
                $loans = GatePass::lookupByBorrower($db, $borrower);
                echo json_encode(['status' => 'success', 'loans' => $loans]);
                exit;
            }

            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Please provide either a ?barcode or ?borrower query parameter.']);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Internal server lookup error.']);
            exit;
        }
    }

    /**
     * Create an official Gate Pass Permit.
     * Responds to POST /inspector/gate-passes/create
     */
    public static function createGatePass(): void {
        RoleMiddleware::allow(['inspector', 'admin']);
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'CSRF security token expired or invalid.']);
                exit;
            }
            redirect('/inspector/gate-passes?error=' . urlencode('Security token expired. Please retry.'));
        }

        $db = Database::getConnection();
        $inspectorId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $requisitionerName = trim($_POST['requisitioner_name'] ?? '');
            $requisitioningOffice = trim($_POST['requisitioning_office'] ?? '');
            $purpose = trim($_POST['purpose'] ?? '');
            $requisitionerId = !empty($_POST['requisitioner_id']) ? (int)$_POST['requisitioner_id'] : null;

            $rawItems = $_POST['items'] ?? [];
            if (is_string($rawItems)) {
                $items = json_decode($rawItems, true);
                if (!is_array($items)) {
                    throw new InvalidArgumentException("Invalid items payload format.");
                }
            } else {
                $items = $rawItems;
            }

            if (empty($items)) {
                throw new InvalidArgumentException("At least one item must be added to the Gate Pass.");
            }

            $passData = [
                'requisitioner_id'      => $requisitionerId,
                'requisitioner_name'    => $requisitionerName,
                'requisitioning_office' => $requisitioningOffice,
                'purpose'               => $purpose,
                'pass_type'             => GatePass::PASS_TYPE_BORROWED,
            ];

            $result = GatePass::create($db, $passData, $items, $inspectorId);
            $printUrl = url('/inspector/gate-passes/print?id=' . $result['id']);

            // Audit log
            try {
                AuditLog::log($db, $inspectorId, 'CREATE_GATE_PASS', "Issued Gate Pass {$result['gate_pass_no']} for {$requisitionerName}", 'gate_passes', $result['id']);
            } catch (\Throwable $e) {}

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status'       => 'success',
                    'gate_pass_id' => $result['id'],
                    'gate_pass_no' => $result['gate_pass_no'],
                    'print_url'    => $printUrl,
                    'message'      => "Gate Pass {$result['gate_pass_no']} generated successfully!"
                ]);
                exit;
            }

            redirect('/inspector/gate-passes?success=' . urlencode("Gate Pass {$result['gate_pass_no']} issued successfully.") . '&open_print=' . $result['id']);

        } catch (InvalidArgumentException $e) {
            if ($isAjax) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
            redirect('/inspector/gate-passes?error=' . urlencode($e->getMessage()));
        } catch (Exception $e) {
            if ($isAjax) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'System error issuing Gate Pass. ' . $e->getMessage()]);
                exit;
            }
            redirect('/inspector/gate-passes?error=' . urlencode('An unexpected error occurred while creating the Gate Pass.'));
        }
    }

    /**
     * Render the official print view for a Gate Pass.
     * Injects JSON permit payload into pdf-lib template stamper.
     */
    public static function printGatePass(): void {
        RoleMiddleware::allow(['inspector', 'admin', 'custodian', 'executive_admin']);
        $db = Database::getConnection();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            die("Gate Pass not found.");
        }

        $pass = GatePass::getById($db, $id);
        if (!$pass) {
            http_response_code(404);
            die("Gate Pass permit record not found.");
        }

        require dirname(__DIR__, 2) . '/views/inspector/gate-pass-print.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 2: Inspection and Acceptance Report (IAR Form AFA-SAF-1.016)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Render the Inspection and Acceptance Report (IAR) Desk workspace.
     */
    public static function iarList(): void {
        RoleMiddleware::allow(['inspector', 'admin']);
        $db = Database::getConnection();

        $status = trim($_GET['status'] ?? 'ALL');
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $result = InspectionReport::listAll($db, $status, $search, $limit, $offset);
        $reports = $result['reports'];
        $total = $result['total'];

        $metrics = InspectionReport::getMetrics($db);
        Security::generateCsrfToken();

        $title = 'Inspection & Acceptance (IAR)';
        $breadcrumb = 'Inspector / IAR Desk';

        require dirname(__DIR__, 2) . '/views/inspector/iar.php';
    }

    /**
     * Create a new Inspection and Acceptance Report.
     * Responds to POST /inspector/iar/create
     */
    public static function createIar(): void {
        RoleMiddleware::allow(['inspector', 'admin']);
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'CSRF security token expired or invalid.']);
                exit;
            }
            redirect('/inspector/iar?error=' . urlencode('Security token expired. Please retry.'));
        }

        $db = Database::getConnection();
        $inspectorId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $rawItems = $_POST['items'] ?? [];
            if (is_string($rawItems)) {
                $items = json_decode($rawItems, true);
                if (!is_array($items)) {
                    throw new InvalidArgumentException("Invalid line items format.");
                }
            } else {
                $items = $rawItems;
            }

            if (empty($items)) {
                throw new InvalidArgumentException("At least one line item must be attached to the IAR.");
            }

            $reportData = [
                'entity_name'        => trim($_POST['entity_name'] ?? 'SLSU-ALABAT'),
                'fund_cluster'       => trim($_POST['fund_cluster'] ?? 'PRE-STF'),
                'supplier_name'      => trim($_POST['supplier_name'] ?? ''),
                'po_no'              => trim($_POST['po_no'] ?? ''),
                'po_date'            => trim($_POST['po_date'] ?? date('Y-m-d')),
                'invoice_no'         => trim($_POST['invoice_no'] ?? ''),
                'invoice_date'       => trim($_POST['invoice_date'] ?? date('Y-m-d')),
                'office_dept'        => trim($_POST['office_dept'] ?? ''),
                'rcc'                => trim($_POST['rcc'] ?? ''),
                'date_inspected'     => trim($_POST['date_inspected'] ?? date('Y-m-d')),
                'date_received'      => trim($_POST['date_received'] ?? date('Y-m-d')),
                'inspection_officer' => trim($_POST['inspection_officer'] ?? 'MANUEL S. VALDERAMA, MIT'),
                'property_officer'   => trim($_POST['property_officer'] ?? 'VERONICA C. OBIEN'),
                'is_inspected_order' => !empty($_POST['is_inspected_order']) ? 1 : 1,
                'acceptance_type'    => ($_POST['acceptance_type'] ?? '') === InspectionReport::ACCEPTANCE_PARTIAL ? InspectionReport::ACCEPTANCE_PARTIAL : InspectionReport::ACCEPTANCE_COMPLETE,
                'partial_remarks'    => trim($_POST['partial_remarks'] ?? ''),
            ];

            $result = InspectionReport::create($db, $reportData, $items, $inspectorId);
            $printUrl = url('/inspector/iar/print?id=' . $result['id']);

            // Audit log
            try {
                AuditLog::log($db, $inspectorId, 'CREATE_IAR', "Created IAR {$result['iar_no']} for supplier {$reportData['supplier_name']}", 'inspection_reports', $result['id']);
            } catch (\Throwable $e) {}

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status'    => 'success',
                    'iar_id'    => $result['id'],
                    'iar_no'    => $result['iar_no'],
                    'print_url' => $printUrl,
                    'message'   => "IAR {$result['iar_no']} created successfully! Ready for printing & wet-signatures."
                ]);
                exit;
            }

            redirect('/inspector/iar?success=' . urlencode("IAR {$result['iar_no']} created successfully.") . '&open_print=' . $result['id']);

        } catch (InvalidArgumentException $e) {
            if ($isAjax) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
            redirect('/inspector/iar?error=' . urlencode($e->getMessage()));
        } catch (Exception $e) {
            if ($isAjax) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'System error creating IAR. ' . $e->getMessage()]);
                exit;
            }
            redirect('/inspector/iar?error=' . urlencode('An unexpected error occurred while creating the Inspection Report.'));
        }
    }

    /**
     * Render the official print view for an IAR report.
     * Injects JSON report payload into pdf-lib template stamper.
     */
    public static function printIar(): void {
        RoleMiddleware::allow(['inspector', 'admin', 'custodian', 'executive_admin']);
        $db = Database::getConnection();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            die("Inspection Report not found.");
        }

        $report = InspectionReport::getById($db, $id);
        if (!$report) {
            http_response_code(404);
            die("Inspection and Acceptance Report not found.");
        }

        require dirname(__DIR__, 2) . '/views/inspector/iar-print.php';
    }

    /**
     * Upload wet-signed IAR scan and advance status to TRANSMITTED.
     * Responds to POST /inspector/iar/upload-scan
     */
    public static function uploadIarScan(): void {
        RoleMiddleware::allow(['inspector', 'admin']);
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'CSRF security token expired or invalid.']);
                exit;
            }
            redirect('/inspector/iar?error=' . urlencode('Security token expired. Please retry.'));
        }

        $db = Database::getConnection();
        $inspectorId = (int)($_SESSION['user_id'] ?? 0);
        $id = (int)($_POST['iar_id'] ?? 0);

        if ($id <= 0) {
            if ($isAjax) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Invalid IAR record identifier.']);
                exit;
            }
            redirect('/inspector/iar?error=' . urlencode('Invalid IAR record identifier.'));
        }

        $report = InspectionReport::getById($db, $id);
        if (!$report) {
            if ($isAjax) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'IAR record not found.']);
                exit;
            }
            redirect('/inspector/iar?error=' . urlencode('IAR record not found.'));
        }

        if (empty($_FILES['scanned_document']) || $_FILES['scanned_document']['error'] !== UPLOAD_ERR_OK) {
            $errMsg = 'Please select a valid scanned document file to upload.';
            if ($isAjax) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $errMsg]);
                exit;
            }
            redirect('/inspector/iar?error=' . urlencode($errMsg));
        }

        $file = $_FILES['scanned_document'];
        $maxBytes = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxBytes) {
            $errMsg = 'The file size exceeds the 10MB limit.';
            if ($isAjax) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $errMsg]);
                exit;
            }
            redirect('/inspector/iar?error=' . urlencode($errMsg));
        }

        // Verify MIME type securely via finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'application/pdf' => 'pdf',
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
        ];

        if (!isset($allowedMimes[$mime])) {
            $errMsg = 'Invalid file format. Allowed formats: PDF, JPEG, PNG.';
            if ($isAjax) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $errMsg]);
                exit;
            }
            redirect('/inspector/iar?error=' . urlencode($errMsg));
        }

        $ext = $allowedMimes[$mime];
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/iar';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $safeFileName = sprintf('iar_%d_%s_%s.%s', $id, date('Ymd_His'), bin2hex(random_bytes(6)), $ext);
        $targetPath = $uploadDir . '/' . $safeFileName;
        $relativeWebPath = '/uploads/iar/' . $safeFileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $errMsg = 'Failed to store uploaded file on server. Check folder permissions.';
            if ($isAjax) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $errMsg]);
                exit;
            }
            redirect('/inspector/iar?error=' . urlencode($errMsg));
        }

        try {
            InspectionReport::uploadSignedScan($db, $id, $relativeWebPath, $inspectorId);

            // Audit log
            try {
                AuditLog::log($db, $inspectorId, 'UPLOAD_IAR_SCAN', "Uploaded wet-signed scan for IAR {$report['iar_no']} and transmitted to Property Custodian", 'inspection_reports', $id);
            } catch (\Throwable $e) {}

            $successMsg = "Signed IAR {$report['iar_no']} scan uploaded successfully! Status advanced to Transmitted to Custodian.";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status'    => 'success',
                    'message'   => $successMsg,
                    'file_path' => $relativeWebPath,
                ]);
                exit;
            }

            redirect('/inspector/iar?success=' . urlencode($successMsg));

        } catch (Exception $e) {
            @unlink($targetPath);
            if ($isAjax) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Database error recording scan upload. ' . $e->getMessage()]);
                exit;
            }
            redirect('/inspector/iar?error=' . urlencode('System error recording scan upload.'));
        }
    }
}
