<?php
declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Throwable;

/**
 * IncidentReportExporter — Damage Incidents & Accountability Clearance Ledger (.xlsx)
 * Recreates the exact layout, dimensions, Arial typography, and signatories of FINAL_INVENTORY-REPORT.xlsx.
 */
class IncidentReportExporter {

    /**
     * Column Widths for the comprehensive 22-column incident ledger
     */
    private const COLUMN_WIDTHS = [
        'A' => 8.00,  // No.
        'B' => 14.00, // Incident ID
        'C' => 26.00, // Borrower Name
        'D' => 16.00, // Borrower ID
        'E' => 16.00, // Role
        'F' => 24.00, // Department / Program
        'G' => 28.00, // Asset Name
        'H' => 20.00, // Barcode
        'I' => 18.00, // Return Condition
        'J' => 32.00, // Damage Description
        'K' => 20.00, // Assessed Fee (PHP)
        'L' => 18.00, // Official Receipt #
        'M' => 18.00, // Total Paid (PHP)
        'N' => 22.00, // Remaining Balance (PHP)
        'O' => 16.00, // Payment Date
        'P' => 20.00, // Receipt Submitted At
        'Q' => 18.00, // Clearance Status
        'R' => 24.00, // Rejection Reason
        'S' => 28.00, // Settlement Notes
        'T' => 22.00, // Cleared By
        'U' => 16.00, // Date Cleared
        'V' => 20.00, // Date Reported
    ];

    /**
     * Standard row height in points (matching 18.75pt in template)
     */
    private const STANDARD_ROW_HEIGHT = 18.75;

    /**
     * Minimum data rows per sheet (empty bordered rows added if item count is less)
     */
    private const MIN_DATA_ROWS = 5;

    /**
     * Generate the complete official Incidents & Clearances Excel workbook.
     *
     * @param array<int, array<string, mixed>> $incidents Incidents data from database
     * @return Spreadsheet
     */
    public function generate(array $incidents): Spreadsheet {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('CLEARANCES & INCIDENTS');

        $this->buildSheet($sheet, $incidents);

        return $spreadsheet;
    }

    /**
     * Build the incidents ledger sheet with headers, data rows, padding, and signatories.
     *
     * @param Worksheet $sheet
     * @param array<int, array<string, mixed>> $incidents
     */
    private function buildSheet(Worksheet $sheet, array $incidents): void {
        // 1. Column Dimensions
        foreach (self::COLUMN_WIDTHS as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // 2. Header Row (Row 1)
        $sheet->getRowDimension(1)->setRowHeight(self::STANDARD_ROW_HEIGHT * 1.5);

        $headers = [
            'A1' => 'No.',
            'B1' => 'Incident ID',
            'C1' => 'Borrower Name',
            'D1' => 'Borrower ID',
            'E1' => 'Role',
            'F1' => 'Department',
            'G1' => 'Asset Name',
            'H1' => 'Barcode',
            'I1' => 'Return Condition',
            'J1' => 'Damage Description',
            'K1' => 'Assessed Fee (PHP)',
            'L1' => 'Official Receipt #',
            'M1' => 'Total Paid (PHP)',
            'N1' => 'Remaining Balance (PHP)',
            'O1' => 'Payment Date',
            'P1' => 'Receipt Submitted At',
            'Q1' => 'Clearance Status',
            'R1' => 'Rejection Reason',
            'S1' => 'Settlement Notes',
            'T1' => 'Cleared By',
            'U1' => 'Date Cleared',
            'V1' => 'Date Reported',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        $headerStyle = [
            'font' => [
                'name' => 'Arial',
                'size' => 11,
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF000000'],
                ],
            ],
        ];
        $sheet->getStyle('A1:V1')->applyFromArray($headerStyle);

        // 3. Data Rows
        $currentRow = 2;
        $itemIndex = 1;

        $thinBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF000000'],
                ],
            ],
        ];

        $centerAlign = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ];

        $leftAlign = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ];

        $rightAlign = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ];

        $dataFontStyle = [
            'font' => [
                'name' => 'Arial',
                'size' => 11,
            ],
        ];

        foreach ($incidents as $row) {
            $sheet->getRowDimension($currentRow)->setRowHeight(self::STANDARD_ROW_HEIGHT);

            // A: No.
            $sheet->setCellValue('A' . $currentRow, $itemIndex);
            $sheet->getStyle('A' . $currentRow)->applyFromArray($centerAlign);

            // B: Incident ID
            $sheet->setCellValueExplicit('B' . $currentRow, (string)($row['incident_id'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('B' . $currentRow)->applyFromArray($centerAlign);

            // C: Borrower Name
            $sheet->setCellValueExplicit('C' . $currentRow, (string)($row['borrower_name'] ?? 'Unknown'), DataType::TYPE_STRING);
            $sheet->getStyle('C' . $currentRow)->applyFromArray($leftAlign);

            // D: Borrower ID
            $sheet->setCellValueExplicit('D' . $currentRow, (string)($row['borrower_id_number'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('D' . $currentRow)->applyFromArray($centerAlign);

            // E: Role
            $roleLabel = ucfirst((string)($row['borrower_role'] ?? 'N/A'));
            $sheet->setCellValueExplicit('E' . $currentRow, $roleLabel, DataType::TYPE_STRING);
            $sheet->getStyle('E' . $currentRow)->applyFromArray($centerAlign);

            // F: Department / Program
            $sheet->setCellValueExplicit('F' . $currentRow, (string)($row['borrower_department'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('F' . $currentRow)->applyFromArray($leftAlign);

            // G: Asset Name
            $sheet->setCellValueExplicit('G' . $currentRow, (string)($row['item_name'] ?? 'Unknown'), DataType::TYPE_STRING);
            $sheet->getStyle('G' . $currentRow)->applyFromArray($leftAlign);

            // H: Barcode
            $sheet->setCellValueExplicit('H' . $currentRow, (string)($row['barcode'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('H' . $currentRow)->applyFromArray($centerAlign);

            // I: Return Condition
            $cond = ucwords(str_replace('_', ' ', (string)($row['damage_condition'] ?? 'Damaged')));
            $sheet->setCellValueExplicit('I' . $currentRow, $cond, DataType::TYPE_STRING);
            $sheet->getStyle('I' . $currentRow)->applyFromArray($centerAlign);

            // J: Damage Description
            $sheet->setCellValueExplicit('J' . $currentRow, (string)($row['damage_description'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('J' . $currentRow)->applyFromArray($leftAlign);

            // K: Assessed Fee (PHP)
            $fee = (float)($row['estimated_cost'] ?? 0.00);
            $sheet->setCellValue('K' . $currentRow, $fee);
            $sheet->getStyle('K' . $currentRow)->getNumberFormat()->setFormatCode('"PHP " #,##0.00');
            $sheet->getStyle('K' . $currentRow)->applyFromArray($rightAlign);

            // L: Official Receipt #
            $sheet->setCellValueExplicit('L' . $currentRow, (string)($row['receipt_number'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('L' . $currentRow)->applyFromArray($centerAlign);

            // M: Total Paid (PHP)
            $paid = (float)($row['amount_paid'] ?? 0.00);
            $sheet->setCellValue('M' . $currentRow, $paid);
            $sheet->getStyle('M' . $currentRow)->getNumberFormat()->setFormatCode('"PHP " #,##0.00');
            $sheet->getStyle('M' . $currentRow)->applyFromArray($rightAlign);

            // N: Remaining Balance (PHP)
            $balance = (float)($row['remaining_balance'] ?? max(0.00, $fee - $paid));
            $sheet->setCellValue('N' . $currentRow, $balance);
            $sheet->getStyle('N' . $currentRow)->getNumberFormat()->setFormatCode('"PHP " #,##0.00');
            $sheet->getStyle('N' . $currentRow)->applyFromArray($rightAlign);

            // O: Payment Date
            $sheet->setCellValueExplicit('O' . $currentRow, (string)($row['payment_date'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('O' . $currentRow)->applyFromArray($centerAlign);

            // P: Receipt Submitted At
            $sheet->setCellValueExplicit('P' . $currentRow, (string)($row['receipt_submitted_at'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('P' . $currentRow)->applyFromArray($centerAlign);

            // Q: Clearance Status
            $statusLabel = ucwords(str_replace('_', ' ', (string)($row['incident_status'] ?? 'pending')));
            $sheet->setCellValueExplicit('Q' . $currentRow, $statusLabel, DataType::TYPE_STRING);
            $sheet->getStyle('Q' . $currentRow)->applyFromArray($centerAlign);

            // R: Rejection Reason
            $sheet->setCellValueExplicit('R' . $currentRow, (string)($row['rejection_reason'] ?? 'None'), DataType::TYPE_STRING);
            $sheet->getStyle('R' . $currentRow)->applyFromArray($leftAlign);

            // S: Settlement Notes
            $sheet->setCellValueExplicit('S' . $currentRow, (string)($row['settlement_notes'] ?? 'None'), DataType::TYPE_STRING);
            $sheet->getStyle('S' . $currentRow)->applyFromArray($leftAlign);

            // T: Cleared By
            $sheet->setCellValueExplicit('T' . $currentRow, (string)($row['cleared_by_name'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('T' . $currentRow)->applyFromArray($leftAlign);

            // U: Date Cleared
            $sheet->setCellValueExplicit('U' . $currentRow, (string)($row['cleared_at'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('U' . $currentRow)->applyFromArray($centerAlign);

            // V: Date Reported
            $sheet->setCellValueExplicit('V' . $currentRow, (string)($row['created_at'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('V' . $currentRow)->applyFromArray($centerAlign);

            // Apply font and borders to row
            $sheet->getStyle("A{$currentRow}:V{$currentRow}")->applyFromArray($dataFontStyle);
            $sheet->getStyle("A{$currentRow}:V{$currentRow}")->applyFromArray($thinBorder);

            $currentRow++;
            $itemIndex++;
        }

        // 4. Minimum Padded Rows
        $incidentsCount = count($incidents);
        if ($incidentsCount < self::MIN_DATA_ROWS) {
            $neededPadding = self::MIN_DATA_ROWS - $incidentsCount;
            for ($p = 0; $p < $neededPadding; $p++) {
                $sheet->getRowDimension($currentRow)->setRowHeight(self::STANDARD_ROW_HEIGHT);
                $sheet->getStyle("A{$currentRow}:V{$currentRow}")->applyFromArray($thinBorder);
                $sheet->getStyle("A{$currentRow}:V{$currentRow}")->applyFromArray($dataFontStyle);
                $currentRow++;
            }
        }

        // 5. Signatory Footer (Matching FINAL_INVENTORY-REPORT.xlsx)
        // 1 empty spacing row
        $sheet->getRowDimension($currentRow)->setRowHeight(self::STANDARD_ROW_HEIGHT);
        $sigStart = $currentRow + 1;

        $sigRow1 = $sigStart;
        $sigRow2 = $sigStart + 1;
        $sigRow3 = $sigStart + 2;
        $sigRow4 = $sigStart + 3;

        foreach ([$sigRow1, $sigRow2, $sigRow3, $sigRow4] as $r) {
            $sheet->getRowDimension($r)->setRowHeight(self::STANDARD_ROW_HEIGHT);
        }

        // Merges for Signatory Section across 22 columns
        $sheet->mergeCells("A{$sigRow1}:D{$sigRow1}");
        $sheet->mergeCells("F{$sigRow1}:I{$sigRow1}");
        $sheet->mergeCells("A{$sigRow2}:D{$sigRow2}");
        $sheet->mergeCells("F{$sigRow2}:I{$sigRow2}");
        $sheet->mergeCells("A{$sigRow3}:D{$sigRow3}");
        $sheet->mergeCells("F{$sigRow3}:I{$sigRow3}");
        $sheet->mergeCells("A{$sigRow4}:D{$sigRow4}");
        $sheet->mergeCells("F{$sigRow4}:I{$sigRow4}");

        $sheet->mergeCells("E{$sigRow3}:E{$sigRow4}");
        $sheet->mergeCells("J{$sigRow3}:J{$sigRow4}");

        // Remaining columns merged vertically to balance the boxed footer grid
        $sheet->mergeCells("K{$sigRow1}:V{$sigRow4}");

        // Row 1: Labels
        $sheet->setCellValue("A{$sigRow1}", 'Prepared by: ');
        $sheet->setCellValue("E{$sigRow1}", 'Date:');
        $sheet->setCellValue("F{$sigRow1}", 'Checked by:');
        $sheet->setCellValue("J{$sigRow1}", 'Date:');

        // Row 3: Signatory Name
        $sheet->setCellValue("F{$sigRow3}", 'ARLIE MARIE F. BENIEGAS');

        // Row 4: Titles
        $sheet->setCellValue("A{$sigRow4}", 'Authorized Inventory Personel');
        $sheet->setCellValue("F{$sigRow4}", 'Over-all Property Custodian');

        // Typography for Signatures
        $labelStyle = [
            'font' => [
                'name' => 'Arial',
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_BOTTOM,
            ],
        ];
        $sheet->getStyle("A{$sigRow1}")->applyFromArray($labelStyle);

        $dateHeaderStyle = [
            'font' => [
                'name' => 'Arial',
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle("E{$sigRow1}")->applyFromArray($dateHeaderStyle);
        $sheet->getStyle("F{$sigRow1}")->applyFromArray($dateHeaderStyle);
        $sheet->getStyle("J{$sigRow1}")->applyFromArray($dateHeaderStyle);

        $nameStyle = [
            'font' => [
                'name' => 'Arial',
                'size' => 10,
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle("F{$sigRow3}")->applyFromArray($nameStyle);

        $titleStyle = [
            'font' => [
                'name' => 'Arial',
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle("A{$sigRow4}")->applyFromArray($titleStyle);
        $sheet->getStyle("F{$sigRow4}")->applyFromArray($titleStyle);

        // Signatory box outer and grid borders
        $sheet->getStyle("A{$sigRow1}:V{$sigRow4}")->applyFromArray($thinBorder);
    }
}
