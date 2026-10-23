<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

$db = require ROOT_PATH . '/config/database.php';

echo "=== Seeding Inspector Dashboard to ensure 5 latest items on both tables ===\n";

// 1. Gate Passes
$gatePasses = [
    [
        'gate_pass_no' => 'GP-2026-0003',
        'requisitioner_name' => 'Carlos M. Mendoza',
        'requisitioning_office' => 'Campus Administration / Maintenance',
        'purpose' => 'Multi-Purpose Gymnasium Audio-Visual Setup - University Week',
        'pass_type' => 'BORROWED',
        'status' => 'ISSUED',
        'created_by' => 3,
        'created_at' => '2026-10-21 09:15:00',
        'items' => [
            [
                'item_description' => 'Yamaha StagePas 600BT Portable PA Sound System',
                'model' => 'StagePas 600BT',
                'serial_no' => 'YM-991204',
                'date_acquired' => '2025-02-14',
                'unit_price' => 48900.00,
                'total_amount' => 48900.00,
                'remarks' => 'Clean condition with dual speakers and powered mixer'
            ],
            [
                'item_description' => 'Shure BLX288/PG58 Wireless Microphone System',
                'model' => 'BLX288/PG58',
                'serial_no' => 'SH-330129',
                'date_acquired' => '2025-02-14',
                'unit_price' => 21500.00,
                'total_amount' => 21500.00,
                'remarks' => 'Includes 2 handheld transmitters and receiver base'
            ]
        ]
    ],
    [
        'gate_pass_no' => 'GP-2026-0004',
        'requisitioner_name' => 'Prof. Elena Ramos',
        'requisitioning_office' => 'College of Fisheries (BSFAS)',
        'purpose' => 'Mangrove Estuary Marine Biota Sampling - Perez & Alabat Waters',
        'pass_type' => 'BORROWED',
        'status' => 'ISSUED',
        'created_by' => 3,
        'created_at' => '2026-10-21 11:30:00',
        'items' => [
            [
                'item_description' => 'YSI Pro2030 Dissolved Oxygen & Conductivity Instrument',
                'model' => 'Pro2030',
                'serial_no' => 'YS-441209',
                'date_acquired' => '2024-09-10',
                'unit_price' => 34500.00,
                'total_amount' => 34500.00,
                'remarks' => 'Calibrated sensor with 4-meter field cable and probe guard'
            ]
        ]
    ],
    [
        'gate_pass_no' => 'GP-2026-0005',
        'requisitioner_name' => 'Engr. Aris D. Morales',
        'requisitioning_office' => 'BSIT / Computer Studies Lab',
        'purpose' => 'Regional IT Skills Competition - Off-Campus Field Deployment',
        'pass_type' => 'BORROWED',
        'status' => 'ISSUED',
        'created_by' => 3,
        'created_at' => '2026-10-21 14:15:00',
        'items' => [
            [
                'item_description' => 'Dell Latitude 5430 Rugged Field Laptop',
                'model' => 'Latitude 5430',
                'serial_no' => 'DL-8823901',
                'date_acquired' => '2025-01-18',
                'unit_price' => 56000.00,
                'total_amount' => 56000.00,
                'remarks' => 'Configured for network penetration and packet capture demos'
            ],
            [
                'item_description' => 'Ubiquiti UniFi 6 Pro Access Point',
                'model' => 'U6-Pro',
                'serial_no' => 'UB-7712390',
                'date_acquired' => '2025-01-18',
                'unit_price' => 9800.00,
                'total_amount' => 9800.00,
                'remarks' => 'With PoE adapter and gigabit patch cord'
            ]
        ]
    ]
];

foreach ($gatePasses as $gpData) {
    $existing = $db->prepare("SELECT id FROM gate_passes WHERE gate_pass_no = :no");
    $existing->execute([':no' => $gpData['gate_pass_no']]);
    if ($existing->fetch()) {
        echo "  [.] Gate Pass {$gpData['gate_pass_no']} already exists, skipping.\n";
        continue;
    }

    $stmt = $db->prepare("
        INSERT INTO gate_passes (
            gate_pass_no, requisitioner_name, requisitioning_office, purpose,
            pass_type, status, created_by, created_at, updated_at
        ) VALUES (
            :no, :req_name, :req_office, :purpose,
            :pass_type, :status, :created_by, :created_at, :updated_at
        )
    ");
    $stmt->execute([
        ':no'         => $gpData['gate_pass_no'],
        ':req_name'   => $gpData['requisitioner_name'],
        ':req_office' => $gpData['requisitioning_office'],
        ':purpose'    => $gpData['purpose'],
        ':pass_type'  => $gpData['pass_type'],
        ':status'     => $gpData['status'],
        ':created_by' => $gpData['created_by'],
        ':created_at' => $gpData['created_at'],
        ':updated_at' => $gpData['created_at'],
    ]);

    $gpId = (int)$db->lastInsertId();

    $itemStmt = $db->prepare("
        INSERT INTO gate_pass_items (
            gate_pass_id, item_description, model, serial_no, date_acquired, unit_price, total_amount, remarks
        ) VALUES (
            :gp_id, :desc, :model, :serial, :date_acq, :price, :total, :remarks
        )
    ");

    foreach ($gpData['items'] as $item) {
        $itemStmt->execute([
            ':gp_id'    => $gpId,
            ':desc'     => $item['item_description'],
            ':model'    => $item['model'],
            ':serial'   => $item['serial_no'],
            ':date_acq' => $item['date_acquired'],
            ':price'    => $item['unit_price'],
            ':total'    => $item['total_amount'],
            ':remarks'  => $item['remarks'],
        ]);
    }

    echo "  [+] Seeded Gate Pass {$gpData['gate_pass_no']} (ID: {$gpId}) with " . count($gpData['items']) . " items.\n";
}

// 2. Inspection Reports
$iars = [
    [
        'iar_no' => 'IAR-2026-0004',
        'entity_name' => 'SLSU-ALABAT',
        'fund_cluster' => 'PRE-STF',
        'supplier_name' => 'SCIENTIFIC PHILS. LABORATORY INC.',
        'po_no' => 'PO-2026-FSH-0095',
        'po_date' => '2026-10-16',
        'invoice_no' => 'INV-902188',
        'invoice_date' => '2026-10-19',
        'office_dept' => 'College of Fisheries (BSFAS)',
        'rcc' => 'RCC-04-12',
        'date_inspected' => '2026-10-21',
        'date_received' => '2026-10-21',
        'inspection_officer' => 'MANUEL S. VALDERAMA, MIT',
        'property_officer' => 'VERONICA C. OBIEN',
        'is_inspected_order' => 1,
        'acceptance_type' => 'COMPLETE',
        'status' => 'TRANSMITTED',
        'created_by' => 3,
        'created_at' => '2026-10-21 10:15:00',
        'items' => [
            [
                'item_description' => 'Olympus CX23 Binocular Biological Microscope with LED Illumination',
                'unit' => 'units',
                'quantity' => 2,
                'unit_cost' => 45000.00,
                'total_cost' => 90000.00,
                'model' => 'CX23-LED',
                'serial_no' => 'OLY-2026-9901',
            ]
        ]
    ],
    [
        'iar_no' => 'IAR-2026-0005',
        'entity_name' => 'SLSU-ALABAT',
        'fund_cluster' => 'PRE-STF',
        'supplier_name' => 'INFOTRACK COMPUTERS & ENTERPRISE',
        'po_no' => 'PO-2026-IT-0114',
        'po_date' => '2026-10-18',
        'invoice_no' => 'INV-883011',
        'invoice_date' => '2026-10-20',
        'office_dept' => 'BSIT / Computer Studies',
        'rcc' => 'RCC-04-08',
        'date_inspected' => '2026-10-21',
        'date_received' => '2026-10-21',
        'inspection_officer' => 'MANUEL S. VALDERAMA, MIT',
        'property_officer' => 'VERONICA C. OBIEN',
        'is_inspected_order' => 1,
        'acceptance_type' => 'COMPLETE',
        'status' => 'REGISTERED',
        'created_by' => 3,
        'created_at' => '2026-10-21 14:00:00',
        'items' => [
            [
                'item_description' => 'Cisco CBS350-24T-4G Managed Gigabit Switch',
                'unit' => 'units',
                'quantity' => 1,
                'unit_cost' => 36500.00,
                'total_cost' => 36500.00,
                'model' => 'CBS350-24T-4G',
                'serial_no' => 'CS-8812904',
            ],
            [
                'item_description' => 'APC Smart-UPS 1500VA LCD 230V with SmartConnect',
                'unit' => 'units',
                'quantity' => 2,
                'unit_cost' => 28000.00,
                'total_cost' => 56000.00,
                'model' => 'SMT1500IC',
                'serial_no' => 'APC-4412098',
            ]
        ]
    ]
];

foreach ($iars as $iarData) {
    $existing = $db->prepare("SELECT id FROM inspection_reports WHERE iar_no = :no");
    $existing->execute([':no' => $iarData['iar_no']]);
    if ($existing->fetch()) {
        echo "  [.] IAR {$iarData['iar_no']} already exists, skipping.\n";
        continue;
    }

    $stmt = $db->prepare("
        INSERT INTO inspection_reports (
            iar_no, entity_name, fund_cluster, supplier_name, po_no, po_date,
            invoice_no, invoice_date, office_dept, rcc, date_inspected, date_received,
            inspection_officer, property_officer, is_inspected_order, acceptance_type,
            status, created_by, created_at, updated_at
        ) VALUES (
            :iar_no, :entity_name, :fund_cluster, :supplier_name, :po_no, :po_date,
            :invoice_no, :invoice_date, :office_dept, :rcc, :date_inspected, :date_received,
            :inspection_officer, :property_officer, :is_inspected_order, :acceptance_type,
            :status, :created_by, :created_at, :updated_at
        )
    ");
    $stmt->execute([
        ':iar_no'             => $iarData['iar_no'],
        ':entity_name'        => $iarData['entity_name'],
        ':fund_cluster'       => $iarData['fund_cluster'],
        ':supplier_name'      => $iarData['supplier_name'],
        ':po_no'              => $iarData['po_no'],
        ':po_date'            => $iarData['po_date'],
        ':invoice_no'         => $iarData['invoice_no'],
        ':invoice_date'       => $iarData['invoice_date'],
        ':office_dept'        => $iarData['office_dept'],
        ':rcc'                => $iarData['rcc'],
        ':date_inspected'     => $iarData['date_inspected'],
        ':date_received'      => $iarData['date_received'],
        ':inspection_officer' => $iarData['inspection_officer'],
        ':property_officer'   => $iarData['property_officer'],
        ':is_inspected_order' => $iarData['is_inspected_order'],
        ':acceptance_type'    => $iarData['acceptance_type'],
        ':status'             => $iarData['status'],
        ':created_by'         => $iarData['created_by'],
        ':created_at'         => $iarData['created_at'],
        ':updated_at'         => $iarData['created_at'],
    ]);

    $iarId = (int)$db->lastInsertId();

    $itemStmt = $db->prepare("
        INSERT INTO inspection_report_items (
            iar_id, item_no, item_description, unit, quantity, unit_cost, total_cost, model, serial_no, is_registered
        ) VALUES (
            :iar_id, :item_no, :desc, :unit, :qty, :unit_cost, :total_cost, :model, :serial, 0
        )
    ");

    $itemSeq = 1;
    foreach ($iarData['items'] as $item) {
        $itemStmt->execute([
            ':iar_id'     => $iarId,
            ':item_no'    => $itemSeq++,
            ':desc'       => $item['item_description'],
            ':unit'       => $item['unit'],
            ':qty'        => $item['quantity'],
            ':unit_cost'  => $item['unit_cost'],
            ':total_cost' => $item['total_cost'],
            ':model'      => $item['model'],
            ':serial'     => $item['serial_no'],
        ]);
    }

    echo "  [+] Seeded IAR {$iarData['iar_no']} (ID: {$iarId}) with " . count($iarData['items']) . " items.\n";
}

echo "=== Done! ===\n";
