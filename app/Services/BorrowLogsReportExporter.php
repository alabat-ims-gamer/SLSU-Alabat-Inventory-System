<?php
declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * BorrowLogsReportExporter — Institutional Multi-Sheet Borrow Logs Report Generator (.xlsx)
 * Generates three distinct sheets matching the layout, dimensions, Arial typography,
 * and signatories of FINAL_INVENTORY-REPORT.xlsx:
 *  1. ACTIVE BORROWS
 *  2. PENDING PICKUPS
 *  3. COMPLETED LOGS
 */
class BorrowLogsReportExporter {

    private const STANDARD_ROW_HEIGHT = 18.75;
    private const MIN_DATA_ROWS = 5;

    /**
     * Generate the complete 3-sheet Borrow Logs Excel workbook.
     *
     * @param array<int, array<string, mixed>> $activeLoans
     * @param array<int, array<string, mixed>> $pendingReservations
     * @param array<int, array<string, mixed>> $completedLoans
     * @return Spreadsheet
     */
    public function generate(array $activeLoans, array $pendingReservations, array $completedLoans): Spreadsheet {
        $spreadsheet = new Spreadsheet();

        // 1. Sheet 1: Active Borrows
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('ACTIVE BORROWS');
        $this->buildActiveSheet($sheet1, $activeLoans);

        // 2. Sheet 2: Pending Pickups
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('PENDING PICKUPS');
        $this->buildPendingSheet($sheet2, $pendingReservations);

        // 3. Sheet 3: Completed Logs
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('COMPLETED LOGS');
        $this->buildCompletedSheet($sheet3, $completedLoans);

        // Set active sheet back to Sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Build Sheet 1: ACTIVE BORROWS
     */
    private function buildActiveSheet(Worksheet $sheet, array $loans): void {
        $widths = [
            'A' => 8.00,  // No.
            'B' => 14.00, // Trans ID
            'C' => 26.00, // Borrower Name
            'D' => 16.00, // Borrower ID
            'E' => 14.00, // Role
            'F' => 24.00, // Department
            'G' => 28.00, // Asset Name
            'H' => 22.00, // Property Number
            'I' => 20.00, // Barcode
            'J' => 18.00, // Borrow Date
            'K' => 18.00, // Expected Return
            'L' => 22.00, // Status / Overdue
            'M' => 20.00, // Facility / Location
            'N' => 26.00, // Remarks
        ];

        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $sheet->getRowDimension(1)->setRowHeight(self::STANDARD_ROW_HEIGHT * 1.5);
        $headers = [
            'A1' => 'No.',
            'B1' => 'Transaction ID',
            'C1' => 'Borrower Name',
            'D1' => 'Borrower ID',
            'E1' => 'Role',
            'F1' => 'Department',
            'G1' => 'Asset Name',
            'H1' => 'Property Number',
            'I1' => 'Barcode',
            'J1' => 'Borrow Date',
            'K1' => 'Expected Return',
            'L1' => 'Due Status',
            'M1' => 'Location',
            'N1' => 'Remarks',
        ];
        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }
        $this->applyHeaderStyle($sheet, 'A1:N1');

        $currentRow = 2;
        $idx = 1;
        $styles = $this->getCommonStyles();

        foreach ($loans as $row) {
            $sheet->getRowDimension($currentRow)->setRowHeight(self::STANDARD_ROW_HEIGHT);

            $sheet->setCellValue('A' . $currentRow, $idx);
            $sheet->getStyle('A' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('B' . $currentRow, (string)($row['transaction_id'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('B' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('C' . $currentRow, (string)($row['borrower_name'] ?? 'Unknown'), DataType::TYPE_STRING);
            $sheet->getStyle('C' . $currentRow)->applyFromArray($styles['left']);

            $sheet->setCellValueExplicit('D' . $currentRow, (string)($row['borrower_id_number'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('D' . $currentRow)->applyFromArray($styles['center']);

            $role = ucfirst((string)($row['borrower_role'] ?? 'N/A'));
            $sheet->setCellValueExplicit('E' . $currentRow, $role, DataType::TYPE_STRING);
            $sheet->getStyle('E' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('F' . $currentRow, (string)($row['borrower_department'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('F' . $currentRow)->applyFromArray($styles['left']);

            $sheet->setCellValueExplicit('G' . $currentRow, (string)($row['item_name'] ?? 'Unknown'), DataType::TYPE_STRING);
            $sheet->getStyle('G' . $currentRow)->applyFromArray($styles['left']);

            $sheet->setCellValueExplicit('H' . $currentRow, (string)($row['property_number'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('H' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('I' . $currentRow, (string)($row['barcode'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('I' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('J' . $currentRow, (string)($row['transaction_date'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('J' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('K' . $currentRow, (string)($row['expected_return_date'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('K' . $currentRow)->applyFromArray($styles['center']);

            $status = (string)($row['due_status_label'] ?? 'Active');
            $sheet->setCellValueExplicit('L' . $currentRow, $status, DataType::TYPE_STRING);
            $sheet->getStyle('L' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('M' . $currentRow, (string)($row['location'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('M' . $currentRow)->applyFromArray($styles['left']);

            $sheet->setCellValueExplicit('N' . $currentRow, (string)($row['remarks'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('N' . $currentRow)->applyFromArray($styles['left']);

            $sheet->getStyle("A{$currentRow}:N{$currentRow}")->applyFromArray($styles['font']);
            $sheet->getStyle("A{$currentRow}:N{$currentRow}")->applyFromArray($styles['border']);

            $currentRow++;
            $idx++;
        }

        $this->padEmptyRows($sheet, count($loans), $currentRow, 'N');
        $this->buildSignatoryBlock($sheet, $currentRow, 'N');
    }

    /**
     * Build Sheet 2: PENDING PICKUPS
     */
    private function buildPendingSheet(Worksheet $sheet, array $requests): void {
        $widths = [
            'A' => 8.00,  // No.
            'B' => 26.00, // Borrower Name
            'C' => 16.00, // Borrower ID
            'D' => 14.00, // Role
            'E' => 24.00, // Department
            'F' => 28.00, // Asset Name
            'G' => 22.00, // Property Number
            'H' => 20.00, // Barcode
            'I' => 20.00, // Location
            'J' => 28.00, // Intended Purpose
            'K' => 22.00, // Queue Status / Expiry
        ];

        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $sheet->getRowDimension(1)->setRowHeight(self::STANDARD_ROW_HEIGHT * 1.5);
        $headers = [
            'A1' => 'No.',
            'B1' => 'Borrower Name',
            'C1' => 'Borrower ID',
            'D1' => 'Role',
            'E1' => 'Department',
            'F1' => 'Asset Name',
            'G1' => 'Property Number',
            'H1' => 'Barcode',
            'I1' => 'Location',
            'J1' => 'Intended Purpose',
            'K1' => 'Queue Status',
        ];
        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }
        $this->applyHeaderStyle($sheet, 'A1:K1');

        $currentRow = 2;
        $idx = 1;
        $styles = $this->getCommonStyles();

        foreach ($requests as $row) {
            $sheet->getRowDimension($currentRow)->setRowHeight(self::STANDARD_ROW_HEIGHT);

            $sheet->setCellValue('A' . $currentRow, $idx);
            $sheet->getStyle('A' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('B' . $currentRow, (string)($row['requester_name'] ?? 'Unknown'), DataType::TYPE_STRING);
            $sheet->getStyle('B' . $currentRow)->applyFromArray($styles['left']);

            $sheet->setCellValueExplicit('C' . $currentRow, (string)($row['requester_id_number'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('C' . $currentRow)->applyFromArray($styles['center']);

            $role = ucfirst((string)($row['requester_role'] ?? 'N/A'));
            $sheet->setCellValueExplicit('D' . $currentRow, $role, DataType::TYPE_STRING);
            $sheet->getStyle('D' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('E' . $currentRow, (string)($row['requester_department'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('E' . $currentRow)->applyFromArray($styles['left']);

            $sheet->setCellValueExplicit('F' . $currentRow, (string)($row['item_name'] ?? 'Unknown'), DataType::TYPE_STRING);
            $sheet->getStyle('F' . $currentRow)->applyFromArray($styles['left']);

            $sheet->setCellValueExplicit('G' . $currentRow, (string)($row['property_number'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('G' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('H' . $currentRow, (string)($row['barcode'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('H' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('I' . $currentRow, (string)($row['location'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('I' . $currentRow)->applyFromArray($styles['left']);

            $sheet->setCellValueExplicit('J' . $currentRow, (string)($row['request_purpose'] ?? 'Academic Use'), DataType::TYPE_STRING);
            $sheet->getStyle('J' . $currentRow)->applyFromArray($styles['left']);

            $status = (string)($row['time_remaining_label'] ?? 'Pending Pickup');
            $sheet->setCellValueExplicit('K' . $currentRow, $status, DataType::TYPE_STRING);
            $sheet->getStyle('K' . $currentRow)->applyFromArray($styles['center']);

            $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray($styles['font']);
            $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray($styles['border']);

            $currentRow++;
            $idx++;
        }

        $this->padEmptyRows($sheet, count($requests), $currentRow, 'K');
        $this->buildSignatoryBlock($sheet, $currentRow, 'K');
    }

    /**
     * Build Sheet 3: COMPLETED LOGS
     */
    private function buildCompletedSheet(Worksheet $sheet, array $history): void {
        $widths = [
            'A' => 8.00,  // No.
            'B' => 14.00, // Trans ID
            'C' => 26.00, // Borrower Name
            'D' => 16.00, // Borrower ID
            'E' => 14.00, // Role
            'F' => 24.00, // Department
            'G' => 28.00, // Asset Name
            'H' => 22.00, // Property Number
            'I' => 20.00, // Barcode
            'J' => 18.00, // Borrow Date
            'K' => 18.00, // Return Date
            'L' => 18.00, // Return Condition
            'M' => 22.00, // Received By
            'N' => 26.00, // Remarks
        ];

        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $sheet->getRowDimension(1)->setRowHeight(self::STANDARD_ROW_HEIGHT * 1.5);
        $headers = [
            'A1' => 'No.',
            'B1' => 'Transaction ID',
            'C1' => 'Borrower Name',
            'D1' => 'Borrower ID',
            'E1' => 'Role',
            'F1' => 'Department',
            'G1' => 'Asset Name',
            'H1' => 'Property Number',
            'I1' => 'Barcode',
            'J1' => 'Borrow Date',
            'K1' => 'Actual Return Date',
            'L1' => 'Return Condition',
            'M1' => 'Received By',
            'N1' => 'Remarks',
        ];
        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }
        $this->applyHeaderStyle($sheet, 'A1:N1');

        $currentRow = 2;
        $idx = 1;
        $styles = $this->getCommonStyles();

        foreach ($history as $row) {
            $sheet->getRowDimension($currentRow)->setRowHeight(self::STANDARD_ROW_HEIGHT);

            $sheet->setCellValue('A' . $currentRow, $idx);
            $sheet->getStyle('A' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('B' . $currentRow, (string)($row['transaction_id'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('B' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('C' . $currentRow, (string)($row['borrower_name'] ?? 'Unknown'), DataType::TYPE_STRING);
            $sheet->getStyle('C' . $currentRow)->applyFromArray($styles['left']);

            $sheet->setCellValueExplicit('D' . $currentRow, (string)($row['borrower_id_number'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('D' . $currentRow)->applyFromArray($styles['center']);

            $role = ucfirst((string)($row['borrower_role'] ?? 'N/A'));
            $sheet->setCellValueExplicit('E' . $currentRow, $role, DataType::TYPE_STRING);
            $sheet->getStyle('E' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('F' . $currentRow, (string)($row['borrower_department'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('F' . $currentRow)->applyFromArray($styles['left']);

            $sheet->setCellValueExplicit('G' . $currentRow, (string)($row['item_name'] ?? 'Unknown'), DataType::TYPE_STRING);
            $sheet->getStyle('G' . $currentRow)->applyFromArray($styles['left']);

            $sheet->setCellValueExplicit('H' . $currentRow, (string)($row['property_number'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('H' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('I' . $currentRow, (string)($row['barcode'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('I' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('J' . $currentRow, (string)($row['transaction_date'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('J' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('K' . $currentRow, (string)($row['actual_return_date'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('K' . $currentRow)->applyFromArray($styles['center']);

            $cond = ucwords(str_replace('_', ' ', (string)($row['returned_condition'] ?? 'Good')));
            $sheet->setCellValueExplicit('L' . $currentRow, $cond, DataType::TYPE_STRING);
            $sheet->getStyle('L' . $currentRow)->applyFromArray($styles['center']);

            $sheet->setCellValueExplicit('M' . $currentRow, (string)($row['processed_by_name'] ?? 'Property Custodian'), DataType::TYPE_STRING);
            $sheet->getStyle('M' . $currentRow)->applyFromArray($styles['left']);

            $sheet->setCellValueExplicit('N' . $currentRow, (string)($row['remarks'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('N' . $currentRow)->applyFromArray($styles['left']);

            $sheet->getStyle("A{$currentRow}:N{$currentRow}")->applyFromArray($styles['font']);
            $sheet->getStyle("A{$currentRow}:N{$currentRow}")->applyFromArray($styles['border']);

            $currentRow++;
            $idx++;
        }

        $this->padEmptyRows($sheet, count($history), $currentRow, 'N');
        $this->buildSignatoryBlock($sheet, $currentRow, 'N');
    }

    /**
     * Helper to apply standardized table header styling
     */
    private function applyHeaderStyle(Worksheet $sheet, string $cellRange): void {
        $sheet->getStyle($cellRange)->applyFromArray([
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
        ]);
    }

    /**
     * Pad empty rows if dataset is less than minimum
     */
    private function padEmptyRows(Worksheet $sheet, int $count, int &$currentRow, string $maxCol): void {
        if ($count < self::MIN_DATA_ROWS) {
            $needed = self::MIN_DATA_ROWS - $count;
            $styles = $this->getCommonStyles();
            for ($p = 0; $p < $needed; $p++) {
                $sheet->getRowDimension($currentRow)->setRowHeight(self::STANDARD_ROW_HEIGHT);
                $sheet->getStyle("A{$currentRow}:{$maxCol}{$currentRow}")->applyFromArray($styles['border']);
                $sheet->getStyle("A{$currentRow}:{$maxCol}{$currentRow}")->applyFromArray($styles['font']);
                $currentRow++;
            }
        }
    }

    /**
     * Build the 4-row institutional signatory footer block matching FINAL_INVENTORY-REPORT.xlsx
     */
    private function buildSignatoryBlock(Worksheet $sheet, int &$currentRow, string $maxCol): void {
        // 1 empty row for spacing
        $sheet->getRowDimension($currentRow)->setRowHeight(self::STANDARD_ROW_HEIGHT);
        $sigStart = $currentRow + 1;

        $sigRow1 = $sigStart;
        $sigRow2 = $sigStart + 1;
        $sigRow3 = $sigStart + 2;
        $sigRow4 = $sigStart + 3;

        foreach ([$sigRow1, $sigRow2, $sigRow3, $sigRow4] as $r) {
            $sheet->getRowDimension($r)->setRowHeight(self::STANDARD_ROW_HEIGHT);
        }

        // Merge cells for Left (Prepared by) and Right (Checked by)
        $sheet->mergeCells("A{$sigRow1}:C{$sigRow1}");
        $sheet->mergeCells("E{$sigRow1}:G{$sigRow1}");
        $sheet->mergeCells("A{$sigRow2}:C{$sigRow2}");
        $sheet->mergeCells("E{$sigRow2}:G{$sigRow2}");
        $sheet->mergeCells("A{$sigRow3}:C{$sigRow3}");
        $sheet->mergeCells("E{$sigRow3}:G{$sigRow3}");
        $sheet->mergeCells("A{$sigRow4}:C{$sigRow4}");
        $sheet->mergeCells("E{$sigRow4}:G{$sigRow4}");

        $sheet->mergeCells("D{$sigRow3}:D{$sigRow4}");
        $sheet->mergeCells("H{$sigRow3}:H{$sigRow4}");

        // Merge remaining right columns if any (H to maxCol)
        if ($maxCol > 'H') {
            $sheet->mergeCells("I{$sigRow1}:{$maxCol}{$sigRow4}");
        }

        // Labels
        $sheet->setCellValue("A{$sigRow1}", 'Prepared by: ');
        $sheet->setCellValue("D{$sigRow1}", 'Date:');
        $sheet->setCellValue("E{$sigRow1}", 'Checked by:');
        $sheet->setCellValue("H{$sigRow1}", 'Date:');

        // Signatory Name
        $sheet->setCellValue("E{$sigRow3}", 'ARLIE MARIE F. BENIEGAS');

        // Titles
        $sheet->setCellValue("A{$sigRow4}", 'Authorized Inventory Personel');
        $sheet->setCellValue("E{$sigRow4}", 'Over-all Property Custodian');

        // Typography & Alignment
        $sheet->getStyle("A{$sigRow1}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_BOTTOM],
        ]);

        $dateHeaderStyle = [
            'font' => ['name' => 'Arial', 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle("D{$sigRow1}")->applyFromArray($dateHeaderStyle);
        $sheet->getStyle("E{$sigRow1}")->applyFromArray($dateHeaderStyle);
        $sheet->getStyle("H{$sigRow1}")->applyFromArray($dateHeaderStyle);

        $sheet->getStyle("E{$sigRow3}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $titleStyle = [
            'font' => ['name' => 'Arial', 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle("A{$sigRow4}")->applyFromArray($titleStyle);
        $sheet->getStyle("E{$sigRow4}")->applyFromArray($titleStyle);

        $thinBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF000000'],
                ],
            ],
        ];
        $sheet->getStyle("A{$sigRow1}:{$maxCol}{$sigRow4}")->applyFromArray($thinBorder);

        $currentRow = $sigRow4 + 1;
    }

    /**
     * Shared formatting styles
     */
    private function getCommonStyles(): array {
        return [
            'border' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FF000000'],
                    ],
                ],
            ],
            'center' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
            'left' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
            'font' => [
                'font' => [
                    'name' => 'Arial',
                    'size' => 11,
                ],
            ],
        ];
    }
}
