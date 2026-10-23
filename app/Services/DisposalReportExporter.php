<?php
declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use App\Models\DisposalRecord;
use Throwable;

/**
 * DisposalReportExporter — Official Decommissioned Assets & Disposal Ledger (.xlsx)
 * Recreates the exact layout, dimensions, Arial typography, and signatories of FINAL_INVENTORY-REPORT.xlsx.
 */
class DisposalReportExporter {

    /**
     * Column Widths matching physical report specifications
     */
    private const COLUMN_WIDTHS = [
        'A' => 8.00,  // No.
        'B' => 16.00, // Record ID
        'C' => 22.00, // Property Number
        'D' => 20.00, // Barcode
        'E' => 32.00, // Item Name
        'F' => 22.00, // Category
        'G' => 22.00, // Location
        'H' => 18.00, // Disposal Method
        'I' => 30.00, // Disposal Reason
        'J' => 22.00, // Salvage Value (PHP)
        'K' => 16.00, // Disposal Date
        'L' => 24.00, // Authorized Custodian
        'M' => 28.00, // Remarks
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
     * Generate the complete official Decommissioned Assets Excel workbook.
     *
     * @param array<int, array<string, mixed>> $records Decommissioned asset records from database
     * @return Spreadsheet
     */
    public function generate(array $records): Spreadsheet {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DECOMMISSIONED ASSETS');

        $this->buildSheet($sheet, $records);

        return $spreadsheet;
    }

    /**
     * Build the disposal ledger sheet with headers, data rows, padding, and signatories.
     *
     * @param Worksheet $sheet
     * @param array<int, array<string, mixed>> $records
     */
    private function buildSheet(Worksheet $sheet, array $records): void {
        // 1. Column Dimensions
        foreach (self::COLUMN_WIDTHS as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // 2. Header Row (Row 1)
        $sheet->getRowDimension(1)->setRowHeight(self::STANDARD_ROW_HEIGHT * 1.5);

        $headers = [
            'A1' => 'No.',
            'B1' => 'Disposal Record ID',
            'C1' => 'Property Number',
            'D1' => 'Barcode',
            'E1' => 'Item Name',
            'F1' => 'Category',
            'G1' => 'Location',
            'H1' => 'Disposal Method',
            'I1' => 'Disposal Reason',
            'J1' => 'Salvage Value (PHP)',
            'K1' => 'Disposal Date',
            'L1' => 'Authorized Custodian',
            'M1' => 'Remarks',
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
        $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

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

        foreach ($records as $row) {
            $sheet->getRowDimension($currentRow)->setRowHeight(self::STANDARD_ROW_HEIGHT);

            // A: No.
            $sheet->setCellValue('A' . $currentRow, $itemIndex);
            $sheet->getStyle('A' . $currentRow)->applyFromArray($centerAlign);

            // B: Record ID
            $sheet->setCellValueExplicit('B' . $currentRow, (string)($row['record_id'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('B' . $currentRow)->applyFromArray($centerAlign);

            // C: Property Number
            $sheet->setCellValueExplicit('C' . $currentRow, (string)($row['property_number'] ?? 'N/A'), DataType::TYPE_STRING);
            $sheet->getStyle('C' . $currentRow)->applyFromArray($centerAlign);

            // D: Barcode
            $sheet->setCellValueExplicit('D' . $currentRow, (string)($row['barcode'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('D' . $currentRow)->applyFromArray($centerAlign);

            // E: Item Name
            $sheet->setCellValueExplicit('E' . $currentRow, (string)($row['item_name'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('E' . $currentRow)->applyFromArray($leftAlign);

            // F: Category
            $sheet->setCellValueExplicit('F' . $currentRow, (string)($row['category'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('F' . $currentRow)->applyFromArray($leftAlign);

            // G: Location
            $sheet->setCellValueExplicit('G' . $currentRow, (string)($row['location'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('G' . $currentRow)->applyFromArray($leftAlign);

            // H: Disposal Method
            $methodKey = strtolower((string)($row['disposal_method'] ?? 'scrapped'));
            $methodLabel = DisposalRecord::METHOD_LABELS[$methodKey] ?? ucfirst($methodKey);
            $sheet->setCellValueExplicit('H' . $currentRow, $methodLabel, DataType::TYPE_STRING);
            $sheet->getStyle('H' . $currentRow)->applyFromArray($centerAlign);

            // I: Disposal Reason
            $sheet->setCellValueExplicit('I' . $currentRow, (string)($row['disposal_reason'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('I' . $currentRow)->applyFromArray($leftAlign);

            // J: Salvage Value (PHP)
            $salvageVal = (float)($row['salvage_value'] ?? 0.00);
            $sheet->setCellValue('J' . $currentRow, $salvageVal);
            $sheet->getStyle('J' . $currentRow)->getNumberFormat()->setFormatCode('"PHP " #,##0.00');
            $sheet->getStyle('J' . $currentRow)->applyFromArray($rightAlign);

            // K: Disposal Date
            $sheet->setCellValueExplicit('K' . $currentRow, (string)($row['disposal_date'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('K' . $currentRow)->applyFromArray($centerAlign);

            // L: Authorized Custodian
            $sheet->setCellValueExplicit('L' . $currentRow, (string)($row['disposed_by_name'] ?? 'Property Custodian'), DataType::TYPE_STRING);
            $sheet->getStyle('L' . $currentRow)->applyFromArray($leftAlign);

            // M: Remarks
            $sheet->setCellValueExplicit('M' . $currentRow, (string)($row['remarks'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('M' . $currentRow)->applyFromArray($leftAlign);

            // Apply font and borders to row
            $sheet->getStyle("A{$currentRow}:M{$currentRow}")->applyFromArray($dataFontStyle);
            $sheet->getStyle("A{$currentRow}:M{$currentRow}")->applyFromArray($thinBorder);

            $currentRow++;
            $itemIndex++;
        }

        // 4. Minimum Padded Rows
        $recordsCount = count($records);
        if ($recordsCount < self::MIN_DATA_ROWS) {
            $neededPadding = self::MIN_DATA_ROWS - $recordsCount;
            for ($p = 0; $p < $neededPadding; $p++) {
                $sheet->getRowDimension($currentRow)->setRowHeight(self::STANDARD_ROW_HEIGHT);
                $sheet->getStyle("A{$currentRow}:M{$currentRow}")->applyFromArray($thinBorder);
                $sheet->getStyle("A{$currentRow}:M{$currentRow}")->applyFromArray($dataFontStyle);
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

        // Merges for Signatory Section
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

        // Remaining columns merged vertically to balance the boxed footer grid
        $sheet->mergeCells("I{$sigRow1}:M{$sigRow4}");

        // Row 1: Labels
        $sheet->setCellValue("A{$sigRow1}", 'Prepared by: ');
        $sheet->setCellValue("D{$sigRow1}", 'Date:');
        $sheet->setCellValue("E{$sigRow1}", 'Checked by:');
        $sheet->setCellValue("H{$sigRow1}", 'Date:');

        // Row 3: Signatory Name
        $sheet->setCellValue("E{$sigRow3}", 'ARLIE MARIE F. BENIEGAS');

        // Row 4: Titles
        $sheet->setCellValue("A{$sigRow4}", 'Authorized Inventory Personel');
        $sheet->setCellValue("E{$sigRow4}", 'Over-all Property Custodian');

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
        $sheet->getStyle("D{$sigRow1}")->applyFromArray($dateHeaderStyle);
        $sheet->getStyle("E{$sigRow1}")->applyFromArray($dateHeaderStyle);
        $sheet->getStyle("H{$sigRow1}")->applyFromArray($dateHeaderStyle);

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
        $sheet->getStyle("E{$sigRow3}")->applyFromArray($nameStyle);

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
        $sheet->getStyle("E{$sigRow4}")->applyFromArray($titleStyle);

        // Signatory box outer and grid borders
        $sheet->getStyle("A{$sigRow1}:M{$sigRow4}")->applyFromArray($thinBorder);
    }
}
