<?php
declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use Throwable;

/**
 * InventoryReportExporter — 1-to-1 Microsoft Excel (.xlsx) Report Generator
 * Recreates the exact layout, dimensions, typography, and signatories of FINAL_INVENTORY-REPORT.xlsx.
 */
class InventoryReportExporter {

    /**
     * Standard institutional laboratory and facility tabs from template
     */
    public const CANONICAL_SHEETS = [
        'SCIENCE LAB',
        'FARM LAB',
        'GIS & COMLAB',
        'FISHERIES LAB',
        'CLINIC',
    ];

    /**
     * Column Widths matching the physical template specifications
     */
    private const COLUMN_WIDTHS = [
        'A' => 14.86, // No.
        'B' => 20.05, // Date
        'C' => 44.96, // Item Description
        'D' => 18.00, // Beginning Qty
        'E' => 18.00, // End Qty
        'F' => 18.00, // Unit
        'G' => 22.00, // Location
        'H' => 24.00, // Remarks
        'I' => 28.00, // Specific Location
        'J' => 18.00, // With (/) or without tag (X)?
        'K' => 24.00, // Corrective Action
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
     * Generate the complete multi-sheet institutional inventory workbook.
     *
     * @param array<int, array<string, mixed>> $items Active items from database
     * @return Spreadsheet
     */
    public function generate(array $items): Spreadsheet {
        $spreadsheet = new Spreadsheet();

        // Group items by normalized location
        $grouped = $this->groupItemsByLocation($items);

        // Determine all sheets to build: canonical 5 + any additional database locations
        $sheetNames = self::CANONICAL_SHEETS;
        foreach (array_keys($grouped) as $locName) {
            if (!in_array($locName, $sheetNames, true)) {
                $sheetNames[] = $locName;
            }
        }

        // Build sheets
        $firstSheet = true;
        foreach ($sheetNames as $sheetName) {
            if ($firstSheet) {
                $sheet = $spreadsheet->getActiveSheet();
                $firstSheet = false;
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            $sanitizedTitle = $this->sanitizeSheetTitle($sheetName);
            $sheet->setTitle($sanitizedTitle);

            $sheetItems = $grouped[$sheetName] ?? [];
            $this->buildSheet($sheet, $sheetName, $sheetItems);
        }

        // Reset active sheet to first tab
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Build an individual location sheet with 1-to-1 headers, rows, padding, and signatures.
     *
     * @param Worksheet $sheet
     * @param string $locationName
     * @param array<int, array<string, mixed>> $items
     */
    private function buildSheet(Worksheet $sheet, string $locationName, array $items): void {
        // Apply column dimensions
        foreach (self::COLUMN_WIDTHS as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // ---------------------------------------------------------
        // 1. HEADERS (Rows 1 & 2)
        // ---------------------------------------------------------
        $sheet->getRowDimension(1)->setRowHeight(self::STANDARD_ROW_HEIGHT);
        $sheet->getRowDimension(2)->setRowHeight(self::STANDARD_ROW_HEIGHT);

        // Merge two-row headers
        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->mergeCells('C1:C2');
        $sheet->mergeCells('D1:E1'); // Quantity span
        $sheet->mergeCells('F1:F2');
        $sheet->mergeCells('G1:G2');
        $sheet->mergeCells('H1:H2');
        $sheet->mergeCells('I1:I2');
        $sheet->mergeCells('J1:J2');
        $sheet->mergeCells('K1:K2');

        // Header Labels
        $sheet->setCellValue('A1', 'No.');
        $sheet->setCellValue('B1', 'Date');
        $sheet->setCellValue('C1', 'Item Description');
        $sheet->setCellValue('D1', 'Inventory (Quantity)');
        $sheet->setCellValue('D2', 'Beginning');
        $sheet->setCellValue('E2', 'End');
        $sheet->setCellValue('F1', 'Unit');
        $sheet->setCellValue('G1', 'Location');
        $sheet->setCellValue('H1', 'Remarks');
        $sheet->setCellValue('I1', 'Specific Location');
        $sheet->setCellValue('J1', "With (/) or\nwithout tag (X)?");
        $sheet->setCellValue('K1', 'Corrective Action');

        // Style Headers
        $headerStyle = [
            'font' => [
                'name' => 'Arial',
                'size' => 12,
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
        $sheet->getStyle('A1:K2')->applyFromArray($headerStyle);

        // ---------------------------------------------------------
        // 2. DATA ROWS & PADDING (Starting at Row 3)
        // ---------------------------------------------------------
        $currentRow = 3;
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

        $dataFontStyle = [
            'font' => [
                'name' => 'Arial',
                'size' => 14,
            ],
        ];

        foreach ($items as $item) {
            $sheet->getRowDimension($currentRow)->setRowHeight(self::STANDARD_ROW_HEIGHT);

            // A: No.
            $sheet->setCellValue('A' . $currentRow, $itemIndex);

            // B: Date (Acquisition date or formatted Y-m-d)
            $dateVal = !empty($item['date_acquired']) ? (string)$item['date_acquired'] : '';
            $sheet->setCellValueExplicit('B' . $currentRow, $dateVal, DataType::TYPE_STRING);

            // C: Item Description (Name + Model / Specs if available)
            $desc = (string)($item['item_name'] ?? '');
            if (!empty($item['model'])) {
                $desc .= ' (' . trim((string)$item['model']) . ')';
            }
            $sheet->setCellValueExplicit('C' . $currentRow, $desc, DataType::TYPE_STRING);

            // D & E: Quantity (Beginning: 1, End: 1)
            $sheet->setCellValue('D' . $currentRow, 1);
            $sheet->setCellValue('E' . $currentRow, 1);

            // F: Unit
            $sheet->setCellValue('F' . $currentRow, 'Piece/s');

            // G: Location
            $sheet->setCellValueExplicit('G' . $currentRow, (string)($item['location'] ?? $locationName), DataType::TYPE_STRING);

            // H: Remarks (Condition / Status)
            $condition = strtolower((string)($item['item_condition'] ?? 'good'));
            $remarks = match ($condition) {
                'mint'         => 'Functional (Mint)',
                'good'         => 'Functional',
                'fair'         => 'Functional (Fair)',
                'damaged'      => 'Damaged',
                'not_working'  => 'Non-Functional',
                'for_disposal' => 'For Disposal',
                default        => 'Functional',
            };
            $sheet->setCellValue('H' . $currentRow, $remarks);

            // I: Specific Location (Sub-location / room or fallback to location)
            $specLoc = (string)($item['location'] ?? $locationName);
            $sheet->setCellValueExplicit('I' . $currentRow, $specLoc, DataType::TYPE_STRING);

            // J: With (/) or without tag (X)?
            $hasTag = (!empty($item['barcode']) || !empty($item['property_number']));
            $sheet->setCellValue('J' . $currentRow, $hasTag ? '/' : 'X');

            // K: Corrective Action
            $corrective = '';
            if (in_array($condition, ['damaged', 'not_working'], true)) {
                $corrective = 'To be repaired / assessed';
            } elseif ($condition === 'for_disposal') {
                $corrective = 'For condemnation';
            }
            $sheet->setCellValueExplicit('K' . $currentRow, $corrective, DataType::TYPE_STRING);

            // Apply cell formatting & borders
            $sheet->getStyle('A' . $currentRow . ':K' . $currentRow)->applyFromArray($thinBorder);
            $sheet->getStyle('A' . $currentRow . ':K' . $currentRow)->applyFromArray($dataFontStyle);
            $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->applyFromArray($centerAlign);
            $sheet->getStyle('C' . $currentRow)->applyFromArray($leftAlign);
            $sheet->getStyle('D' . $currentRow . ':K' . $currentRow)->applyFromArray($centerAlign);

            $currentRow++;
            $itemIndex++;
        }

        // Pad with empty bordered rows to ensure at least MIN_DATA_ROWS rows
        while ($itemIndex <= self::MIN_DATA_ROWS) {
            $sheet->getRowDimension($currentRow)->setRowHeight(self::STANDARD_ROW_HEIGHT);
            $sheet->getStyle('A' . $currentRow . ':K' . $currentRow)->applyFromArray($thinBorder);
            $currentRow++;
            $itemIndex++;
        }

        // ---------------------------------------------------------
        // 3. SIGNATORY FOOTER
        // ---------------------------------------------------------
        // Leave 1 empty row for spacing
        $sheet->getRowDimension($currentRow)->setRowHeight(self::STANDARD_ROW_HEIGHT);
        $sigStart = $currentRow + 1;

        $sigRow1 = $sigStart;
        $sigRow2 = $sigStart + 1;
        $sigRow3 = $sigStart + 2;
        $sigRow4 = $sigStart + 3;

        foreach ([$sigRow1, $sigRow2, $sigRow3, $sigRow4] as $r) {
            $sheet->getRowDimension($r)->setRowHeight(self::STANDARD_ROW_HEIGHT);
        }

        // Merges for Signatory Section matching template
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
        $sheet->mergeCells("I{$sigRow1}:I{$sigRow4}");
        $sheet->mergeCells("J{$sigRow1}:J{$sigRow4}");
        $sheet->mergeCells("K{$sigRow1}:K{$sigRow4}");

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
                'size' => 12,
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
                'size' => 12,
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

        // Signatory box outer and bottom borders
        $sheet->getStyle("A{$sigRow1}:K{$sigRow4}")->applyFromArray($thinBorder);
    }

    /**
     * Group items by location with safe normalization.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupItemsByLocation(array $items): array {
        $grouped = [];

        foreach ($items as $item) {
            $rawLoc = trim((string)($item['location'] ?? ''));
            if ($rawLoc === '') {
                $rawLoc = 'Other / General';
            }

            $targetLoc = $rawLoc;
            foreach (self::CANONICAL_SHEETS as $canonical) {
                if (strcasecmp($rawLoc, $canonical) === 0 || $this->matchesLocationAlias($canonical, $rawLoc)) {
                    $targetLoc = $canonical;
                    break;
                }
            }

            $grouped[$targetLoc][] = $item;
        }

        return $grouped;
    }

    /**
     * Check if a database location matches a canonical sheet name or known aliases.
     */
    private function matchesLocationAlias(string $canonical, string $candidate): bool {
        $candidateUpper = strtoupper(trim($candidate));
        $canonicalUpper = strtoupper(trim($canonical));

        if ($canonicalUpper === 'FISHERIES LAB' && (
            str_contains($candidateUpper, 'FISHERIES') ||
            str_contains($candidateUpper, 'BSFAS')
        )) {
            return true;
        }

        if ($canonicalUpper === 'GIS & COMLAB' && (
            str_contains($candidateUpper, 'COMLAB') ||
            str_contains($candidateUpper, 'GIS') ||
            str_contains($candidateUpper, 'COMPUTER LAB')
        )) {
            return true;
        }

        if ($canonicalUpper === 'SCIENCE LAB' && (
            str_contains($candidateUpper, 'SCIENCE') ||
            str_contains($candidateUpper, 'CHEMISTRY') ||
            str_contains($candidateUpper, 'BIOLOGY')
        )) {
            return true;
        }

        if ($canonicalUpper === 'FARM LAB' && (
            str_contains($candidateUpper, 'FARM') ||
            str_contains($candidateUpper, 'AGRICULTURE')
        )) {
            return true;
        }

        if ($canonicalUpper === 'CLINIC' && str_contains($candidateUpper, 'CLINIC')) {
            return true;
        }

        return false;
    }

    /**
     * Sanitize Excel worksheet title: max 31 characters, no invalid chars `\ / ? * : [ ]`.
     */
    public function sanitizeSheetTitle(string $title): string {
        $cleaned = (string)preg_replace('/[\\\\\/?*:\\[\\]]/', ' ', $title);
        $cleaned = trim((string)preg_replace('/\s+/', ' ', $cleaned));

        if ($cleaned === '') {
            $cleaned = 'Sheet';
        }

        if (mb_strlen($cleaned) > 31) {
            $cleaned = mb_substr($cleaned, 0, 31);
        }

        return $cleaned;
    }
}
