# System-Wide Mobile 2-Column KPI Grid Implementation Plan

> **For Agent Execution:** Follow this implementation plan incrementally, task by task.

**Goal:** Implement a unified 2-column mobile grid layout for KPI/stat cards across all modules and dashboards in AlabatIMS (≤ 640px down to 360px), with odd-numbered sets laying out the last card as full-width (`span 2`), and ultra-narrow fallback (< 360px).

**Tech Stack:** Vanilla CSS3 (CSS Grid, `:nth-child` pseudo-selectors, Flexbox), PHP 8.2 (Views & Templates), Automated PHP Test Harness.

---

## Architecture & Design Decisions

* **Central CSS Authority:** Master styles defined in `public/css/custom.css` targeting `.grid-kpi-stats` and backward-compatible aliases (`.stats-grid`, `.grid-kpi-stats-6`, `.grid-kpi-stats-3col`, `.inspector-kpi-grid`, `.records-stats-grid`).
* **Mobile 2-Column Range:** Between `360px` and `640px`, grids force `grid-template-columns: repeat(2, 1fr) !important; gap: 10px !important;`.
* **Odd-Card Spanning Rule:**
  ```css
  :is(.grid-kpi-stats, .stats-grid, .grid-kpi-stats-6, .grid-kpi-stats-3col, .inspector-kpi-grid, .records-stats-grid)
    > :is(.stat-card, a.stat-card):last-child:nth-child(odd) {
    grid-column: span 2 !important;
  }
  ```
* **Ultra-Narrow Screen Fallback:** Below `360px`, `grid-template-columns: 1fr !important; grid-column: span 1 !important;`.
* **Card Proportions on Mobile:** Cards use `height: 100%` and compact padding (`12px`) with scaled typography (values `22px–24px`, labels `11px`, icons `30px`) so 2-column cards stay legible without clipping.

---

## Phased Implementation Tasks

### Task 1: Centralized CSS Definition in `public/css/custom.css`

**Files to modify:**
- `public/css/custom.css`

**Steps:**
1. Update lines 2470–2507 and lines 2962–2980 in `public/css/custom.css` to centralize the mobile responsive grid behavior for `.grid-kpi-stats`, `.grid-kpi-stats-6`, `.grid-kpi-stats-3col`, `.stats-grid`, `.inspector-kpi-grid`, and `.records-stats-grid`.
2. Add the `:last-child:nth-child(odd)` selector with `grid-column: span 2 !important;` inside the `@media (max-width: 640px) and (min-width: 360px)` block.
3. Add the ultra-narrow `@media (max-width: 359px)` fallback ensuring single-column display.
4. Scale `.stat-card` padding, typography, and icon size under `@media (max-width: 640px)`.

---

### Task 2: Standardize Inspector Views

**Files to modify:**
- `views/inspector/iar.php`
- `views/inspector/gate-passes.php`
- `views/inspector/dashboard.php`

**Steps:**
1. In `views/inspector/iar.php`, replace raw inline `style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:1rem;margin-bottom:1.5rem;"` with `class="grid-kpi-stats"`.
2. In `views/inspector/gate-passes.php`, replace raw inline `style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:1rem;margin-bottom:1.5rem;"` with `class="grid-kpi-stats"`.
3. In `views/inspector/dashboard.php`, clean up the local `<style>` media queries that previously had redundant `.inspector-kpi-grid` definitions.

---

### Task 3: Standardize Records, Clearances, and Admin Views

**Files to modify:**
- `views/records/index.php`
- `views/records/incidents.php`
- `views/clearances/student.php`
- `views/admin/audit_logs.php`

**Steps:**
1. In `views/records/index.php`, remove the local `.records-stats-grid` media query overrides in `<style>`, allowing central CSS to govern the responsive grid.
2. In `views/records/incidents.php`, replace raw inline `repeat(auto-fit, minmax(220px, 1fr))` on `.stats-grid` with `class="grid-kpi-stats"`.
3. In `views/clearances/student.php`, replace raw inline `repeat(auto-fit, minmax(210px, 1fr))` on `.stats-grid` with `class="grid-kpi-stats"`.
4. In `views/admin/audit_logs.php`, replace raw inline `repeat(auto-fit, minmax(220px, 1fr))` with `class="grid-kpi-stats"`.

---

### Task 4: Automated Verification Test

**Files to create:**
- `tests/MobileKpiGridTest.php`

**Steps:**
1. Write a standalone test script that verifies:
   - `public/css/custom.css` defines the 2-column rule for `360px` to `640px`.
   - `public/css/custom.css` includes the `:last-child:nth-child(odd)` span 2 rule.
   - `public/css/custom.css` includes the `< 360px` 1-column fallback.
   - No view files (`iar.php`, `gate-passes.php`, `audit_logs.php`, etc.) contain raw inline `repeat(auto-fit, minmax(...))` grid overrides.
2. Execute the test using `php tests/MobileKpiGridTest.php`.

---

### Task 5: End-to-End Visual Verification

1. Test Inspector IAR (`views/inspector/iar.php`): verify 4 cards form a 2x2 grid on mobile viewports.
2. Test Inspector Gate Passes (`views/inspector/gate-passes.php`): verify 3 cards render as 2 columns on top, 1 full-width card on bottom.
3. Test Custodian Loans (`views/custodian/loans.php`): verify 3 cards render as 2 columns on top, 1 full-width card on bottom.
4. Test Custodian Dashboard (`views/dashboard/custodian.php`): verify 6 cards render as 3 rows of 2.
5. Test Ultra-Narrow (< 360px): verify single column fallback.
