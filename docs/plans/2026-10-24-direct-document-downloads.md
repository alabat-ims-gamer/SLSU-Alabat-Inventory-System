# Direct PDF Document Downloads Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Remove browser print preview popup tabs (`window.open(..., '_blank')` and `target="_blank"`) and enable direct PDF file downloads (`Content-Disposition: attachment`) across Reports, IAR, and Gate Pass documents.

**Architecture:** Append `format=pdf` to official document export URLs and trigger downloads in the active window (`window.location.assign` / standard anchor tags), eliminating `_blank` popup tabs so files stream directly into the browser Downloads folder via Dompdf.

**Tech Stack:** PHP 8.2 (via `C:\xampp\php\php.exe`), Dompdf, Vanilla JavaScript (ES6), HTML5.

---

### Task 1: Output Buffer Safety in PDF Print Engines

**Files:**
- Modify: `views/reports/institutional-print.php:20-46`
- Modify: `views/inspector/iar-print.php:20-47`
- Modify: `views/inspector/gate-pass-print.php:20-47`
- Test: `tests/DirectDocumentDownloadTest.php`

**Step 1: Write the failing test**
Create `tests/DirectDocumentDownloadTest.php`:
```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

function testAssert(bool $condition, string $message): void {
    if (!$condition) {
        echo "[FAIL] {$message}\n";
        exit(1);
    }
    echo "[PASS] {$message}\n";
}

echo "=== Direct Document Download Verification Suite ===\n";

$instPrint = file_get_contents(__DIR__ . '/../views/reports/institutional-print.php');
testAssert(strpos($instPrint, 'ob_clean()') !== false, "institutional-print.php cleans active buffer before streaming PDF");

$iarPrint = file_get_contents(__DIR__ . '/../views/inspector/iar-print.php');
testAssert(strpos($iarPrint, 'ob_clean()') !== false, "iar-print.php cleans active buffer before streaming PDF");

$gpPrint = file_get_contents(__DIR__ . '/../views/inspector/gate-pass-print.php');
testAssert(strpos($gpPrint, 'ob_clean()') !== false, "gate-pass-print.php cleans active buffer before streaming PDF");
```

**Step 2: Run test to verify it fails**
Run: `C:\xampp\php\php.exe tests/DirectDocumentDownloadTest.php`
Expected: FAIL on `institutional-print.php cleans active buffer`

**Step 3: Write minimal implementation**
In `views/reports/institutional-print.php`, `views/inspector/iar-print.php`, and `views/inspector/gate-pass-print.php`:
Add buffer cleansing right before the PDF binary headers:
```php
    if (ob_get_length()) {
        ob_clean();
    }
```

**Step 4: Run test to verify it passes**
Run: `C:\xampp\php\php.exe tests/DirectDocumentDownloadTest.php`
Expected: PASS

**Step 5: Commit**
```bash
git add views/reports/institutional-print.php views/inspector/iar-print.php views/inspector/gate-pass-print.php tests/DirectDocumentDownloadTest.php
git commit -m "fix(export): add buffer cleansing to PDF download stream engines"
```

---

### Task 2: Reports Module Direct PDF Download Transition

**Files:**
- Modify: `views/reports/index.php:897-913`
- Test: `tests/DirectDocumentDownloadTest.php`

**Step 1: Write test assertion**
In `tests/DirectDocumentDownloadTest.php`, add:
```php
$reportsIndex = file_get_contents(__DIR__ . '/../views/reports/index.php');
testAssert(strpos($reportsIndex, "format: 'pdf'") !== false || strpos($reportsIndex, "format: \"pdf\"") !== false, "reports index passes format=pdf for official audit reports");
testAssert(strpos($reportsIndex, "window.open(targetUrl, '_blank')") === false, "reports index no longer opens institutional report in _blank tab");
testAssert(strpos($reportsIndex, "window.location.assign(targetUrl)") !== false, "reports index downloads institutional report directly in active window");
```

**Step 2: Run test to verify it fails**
Run: `C:\xampp\php\php.exe tests/DirectDocumentDownloadTest.php`
Expected: FAIL on `reports index passes format=pdf`

**Step 3: Write minimal implementation**
In `views/reports/index.php` (around line 898):
```javascript
      // Mode 1: Official Institutional Audit Report (Direct PDF Download)
      if (selectedFormat === 'official') {
        const query = new URLSearchParams({
          timeframe: tf,
          category: cat,
          format: 'pdf',
        });

        if (tf === 'custom') {
          if (startDate) query.set('start_date', startDate);
          if (endDate) query.set('end_date', endDate);
        }

        const targetUrl = (window.appUrl ? window.appUrl('/api/analytics/institutional-report') : '/api/analytics/institutional-report') + '?' + query.toString();
        
        if (typeof Toast !== 'undefined' && typeof Toast.info === 'function') {
          Toast.info('Preparing Document', 'Compiling institutional PDF audit report. Your download will start momentarily...');
        } else if (typeof Toast !== 'undefined' && typeof Toast.show === 'function') {
          Toast.show('Preparing Document', 'Compiling institutional PDF audit report...', { type: 'info' });
        }

        window.location.assign(targetUrl);
        closeReportModal();
        return;
      }
```

**Step 4: Run test to verify it passes**
Run: `C:\xampp\php\php.exe tests/DirectDocumentDownloadTest.php`
Expected: PASS

**Step 5: Commit**
```bash
git add views/reports/index.php tests/DirectDocumentDownloadTest.php
git commit -m "feat(reports): download institutional report directly as PDF without new tab"
```

---

### Task 3: Inspector IAR Forms Direct PDF Download Transition

**Files:**
- Modify: `views/inspector/iar.php:21, 225`
- Modify: `views/inspector/dashboard.php:263`
- Test: `tests/DirectDocumentDownloadTest.php`

**Step 1: Write test assertion**
In `tests/DirectDocumentDownloadTest.php`, add:
```php
$iarView = file_get_contents(__DIR__ . '/../views/inspector/iar.php');
testAssert(strpos($iarView, "inspector/iar/print?id=' . \$r['id'] . '&format=pdf") !== false, "iar.php passes format=pdf on IAR download link");
testAssert(strpos($iarView, "iar/print?id=' . \$r['id']) ?>\" target=\"_blank\"") === false, "iar.php table link has no target=_blank");

$inspectorDash = file_get_contents(__DIR__ . '/../views/inspector/dashboard.php');
testAssert(strpos($inspectorDash, "inspector/iar/print?id=' . \$iar['id'] . '&format=pdf") !== false, "dashboard.php passes format=pdf on IAR download link");
testAssert(strpos($inspectorDash, "iar/print?id=' . \$iar['id']) ?>\" target=\"_blank\"") === false, "dashboard.php IAR link has no target=_blank");
```

**Step 2: Run test to verify it fails**
Run: `C:\xampp\php\php.exe tests/DirectDocumentDownloadTest.php`
Expected: FAIL on `iar.php passes format=pdf`

**Step 3: Write minimal implementation**
- In `views/inspector/iar.php`:
  Update lines 21 and 225:
  Change `url('/inspector/iar/print?id=' . (int)$_GET['open_print'])` to include `&format=pdf` and remove `target="_blank"`.
  Change `url('/inspector/iar/print?id=' . $r['id'])` to `url('/inspector/iar/print?id=' . $r['id'] . '&format=pdf')`, remove `target="_blank"`, and update title to `"Export Official Form AFA-SAF-1.016 (PDF)"`.
- In `views/inspector/dashboard.php`:
  Update line 263:
  Change `url('/inspector/iar/print?id=' . $iar['id'])` to `url('/inspector/iar/print?id=' . $iar['id'] . '&format=pdf')`, remove `target="_blank"`, and update title to `"Export Official Form AFA-SAF-1.016 (PDF)"`.

**Step 4: Run test to verify it passes**
Run: `C:\xampp\php\php.exe tests/DirectDocumentDownloadTest.php`
Expected: PASS

**Step 5: Commit**
```bash
git add views/inspector/iar.php views/inspector/dashboard.php tests/DirectDocumentDownloadTest.php
git commit -m "feat(inspector): download IAR forms directly as PDF without new tab"
```

---

### Task 4: Gate Pass Direct PDF Download Transition

**Files:**
- Modify: `views/inspector/gate-passes.php:50, 143`
- Modify: `views/inspector/dashboard.php:161`
- Modify: `views/inspector/components/gate-pass-modal.php:391`
- Test: `tests/DirectDocumentDownloadTest.php`

**Step 1: Write test assertion**
In `tests/DirectDocumentDownloadTest.php`, add:
```php
$gpView = file_get_contents(__DIR__ . '/../views/inspector/gate-passes.php');
testAssert(strpos($gpView, "&format=pdf") !== false, "gate-passes.php appends format=pdf to print URLs");
testAssert(strpos($gpView, "target=\"_blank\"") === false, "gate-passes.php action buttons have no target=_blank");

$gpModal = file_get_contents(__DIR__ . '/../views/inspector/components/gate-pass-modal.php');
testAssert(strpos($gpModal, "window.open(result.print_url, '_blank')") === false, "gate-pass-modal.php no longer opens print_url in _blank tab");
testAssert(strpos($gpModal, "format=pdf") !== false, "gate-pass-modal.php appends format=pdf for direct download");

testAssert(strpos($inspectorDash, "gate-passes/print?id=' . \$gp['id'] . '&format=pdf") !== false, "dashboard.php passes format=pdf on Gate Pass download link");
testAssert(strpos($inspectorDash, "gate-passes/print?id=' . \$gp['id']) ?>\" target=\"_blank\"") === false, "dashboard.php Gate Pass link has no target=_blank");
```

**Step 2: Run test to verify it fails**
Run: `C:\xampp\php\php.exe tests/DirectDocumentDownloadTest.php`
Expected: FAIL on `gate-passes.php appends format=pdf`

**Step 3: Write minimal implementation**
- In `views/inspector/gate-passes.php`:
  Update table link (line 50) and JavaScript print handler (line 143) to append `&format=pdf`, remove `target="_blank"`, and use `window.location.assign(...)` instead of `window.open(...)`.
- In `views/inspector/dashboard.php`:
  Update gate pass link (line 161) to include `&format=pdf` and remove `target="_blank"`.
- In `views/inspector/components/gate-pass-modal.php`:
  Line 391:
  ```javascript
  const printPdfUrl = result.print_url + (result.print_url.includes('?') ? '&' : '?') + 'format=pdf';
  window.location.assign(printPdfUrl);
  ```

**Step 4: Run test to verify it passes**
Run: `C:\xampp\php\php.exe tests/DirectDocumentDownloadTest.php`
Expected: PASS

**Step 5: Commit**
```bash
git add views/inspector/gate-passes.php views/inspector/dashboard.php views/inspector/components/gate-pass-modal.php tests/DirectDocumentDownloadTest.php
git commit -m "feat(inspector): download Gate Passes directly as PDF without new tab"
```

---

### Task 5: End-to-End Regression & Header Verification

**Files:**
- Execute: `tests/GatePassSystemTest.php`
- Execute: `tests/IarSystemTest.php`
- Execute: `scripts/verify_institutional_report.php`
- Execute: `tests/DirectDocumentDownloadTest.php`

**Step 1: Run complete verification suite**
Run:
```powershell
C:\xampp\php\php.exe tests/GatePassSystemTest.php
C:\xampp\php\php.exe tests/IarSystemTest.php
C:\xampp\php\php.exe scripts/verify_institutional_report.php
C:\xampp\php\php.exe tests/DirectDocumentDownloadTest.php
```
Expected: All tests PASS with zero regressions.

**Step 2: Final Commit**
```bash
git commit --allow-empty -m "chore(export): verified direct document download across all test suites"
```
