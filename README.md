# SLSU-Alabat Web-Based Inventory Management and Properties Tracking System

A centralized web application built for **Southern Luzon State University (SLSU) – Alabat Campus** to track, audit, and manage institutional property throughout its operational lifecycle. The system manages laboratory equipment, IT hardware, instructional media, office supplies, and furniture across academic departments and campus offices.

---

## Key Features

- **Strict Administrative Role Verification:** Self-service registration places new accounts into a `pending` queue. System administrators verify credentials before assigning operational roles, preventing unauthorized access to university resources.
- **Physical Barcode Generation:** Generates standardized 1D barcodes (`Code 128` and `Code 39`) via `JsBarcode`. Barcode labels can be formatted and printed directly from the browser onto standard sticker sheets for tagging equipment.
- **Hardware Laser & Camera-Based Scanning:** Supports both USB hardware barcode scanners (instant keyboard-wedge entry) and live device cameras (smartphones, tablets, and webcams) via `html5-qrcode`.
- **Policy-Enforced Borrow and Return Engine:** 
  - Prevents students from checking out faculty-restricted assets.
  - Automatically restricts checkouts for users with overdue items or unresolved damage holds.
  - Applies role-specific loan duration limits (4-hour laboratory sessions for students; multi-day project loans for faculty).
- **Damage Incidents & Settlement Holds:** Identifies equipment returned in damaged or defective condition, logs incident reports, places an automated borrow-hold on the user, and tracks settlement options (repair costs, in-kind replacement, or administrative waivers).
- **Formal Asset Disposal & Condemnation (BER):** Tracks items marked Beyond Economical Repair (BER) through staging and final decommissioning (scrapped, donated, sold, or cannibalized for parts) with recorded salvage values.
- **Auditing & ISO/IEC 25010 Quality Standards:** Maintains immutable audit logs (`audit_logs`) tracking user actions, IP addresses, and timestamps. Generates operational exports in CSV and printable executive summaries.

---

## Tech Stack

| Component | Technology | Version / Standard | Role & Implementation |
| :--- | :--- | :--- | :--- |
| **Backend Core** | PHP | `8.2+` | MVC architecture, native PSR-4 autoloader, controller dispatching, and server-side RBAC guards. |
| **Database** | MySQL / MariaDB | `8.0+` / `10.4+` | Relational data store with foreign key constraints, UTF-8 (`utf8mb4_unicode_ci`) encoding, and indexing on frequent lookup columns. |
| **Database Access** | PHP Data Objects (PDO) | Native PHP | Parameterized queries with emulation disabled (`ATTR_EMULATE_PREPARES => false`) for SQL injection defense. |
| **Frontend Styling** | Tailwind CSS & Vanilla CSS | `3.4+` / CSS3 | Utility styling with custom design tokens (`custom.css`), responsive layouts, and modal components. |
| **Camera Barcode Scanner** | `html5-qrcode` | `v2.3.8+` | Client-side camera video stream parsing for mobile and desktop web browsers. |
| **Barcode Generator** | `JsBarcode` | `v3.11.6+` | Client-side rendering of `Code 128` and `Code 39` barcodes onto SVG and HTML Canvas elements. |
| **Charts & Metrics** | `Chart.js` | `v4.4+` | Departmental asset distribution, loan activity volume, and asset condition distribution charts. |
| **Tabular UI** | Custom JavaScript DataTables | Vanilla ES6 | Real-time search filtering, category filtering, condition filtering, and paginated record navigation. |
| **Spreadsheet Exports** | PHP native CSV / `PhpSpreadsheet` | `^2.0` | Formula-neutralized CSV exports (`Item::neutralizeFormula`) and tabular reporting. |
| **PDF Reporting** | `dompdf/dompdf` | `^3.0` | Formatted institutional property pass slips and executive overview documents. |
| **Configuration** | `vlucas/phpdotenv` | `^5.6` | Environment variable management with safe parsing fallback. |
| **Local Web Server** | Apache (XAMPP) | `2.4.x` | Local development stack running on Windows with URL rewriting enabled (`mod_rewrite`). |

---

## Role-Based Access Control (RBAC)

The application enforces a five-tier authorization hierarchy. Every server request passes through `App\Middleware\RoleMiddleware` before reaching controller logic.

| Module / Operation | Admin (`admin`) | Custodian (`custodian`) | Executive Admin (`executive_admin`) | Faculty (`faculty`) | Student (`student`) |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Executive Overview & Analytics Cockpit** | No Access | Operational Studio | Primary Cockpit (`/dashboard`) | No Access | No Access |
| **User Directory & Account Approval** | Full Control | No Access | No Access | No Access | No Access |
| **Password Override & Role Elevation** | Full Control | No Access | No Access | No Access | No Access |
| **Asset Catalog View & Filtering** | No Access | Full Control | Read-Only | Read-Only (No Export) | Read-Only (No Export) |
| **Asset Intake & CSV Bulk Import** | No Access | Full Control | No Access | No Access | No Access |
| **Edit Asset Details & Location** | No Access | Full Control | No Access | No Access | No Access |
| **Declare BER / Decommission Asset** | No Access | Full Control | No Access | No Access | No Access |
| **Generate & Print Barcode Labels** | No Access | Full Control | No Access | No Access | No Access |
| **Barcode Scanning Lookup** | No Access | Full Control | No Access | Self-Service | Self-Service |
| **Loan Approval & Direct Issue** | No Access | Full Control | No Access | No Access | No Access |
| **Receive Returns & Log Condition** | No Access | Full Control | No Access | Return Request | Return Request |
| **Damage Incidents & Settlements** | Read-Only | Full Control | Read-Only | View Own Holds | View Own Holds |
| **Operational CSV Reports** | Export Logs | Full Operational | Executive CSV | No Access | No Access |
| **System Security & Audit Trail** | Full View | No Access | Read-Only | No Access | No Access |

---

## Borrowing Rules & Validation Flow

```
                   [ User Scans Asset Barcode ]
                                 │
                                 ▼
                     [ Verify User Status ]
                                 │
          ┌──────────────────────┴──────────────────────┐
          ▼                                             ▼
   [ Pending / Suspended ]                         [ Approved ]
          │                                             │
          ▼                                             ▼
   (Block: Contact Admin)                    [ Check Active Damage Holds ]
                                                        │
                                   ┌────────────────────┴────────────────────┐
                                   ▼                                         ▼
                             [ Has Hold ]                               [ Clear ]
                                   │                                         │
                                   ▼                                         ▼
                        (Block: Unsettled Damage)                 [ Check Overdue Items ]
                                                                             │
                                                        ┌────────────────────┴────────────────────┐
                                                        ▼                                         ▼
                                                  [ Has Overdue ]                              [ None ]
                                                        │                                         │
                                                        ▼                                         ▼
                                             (Block: Return Overdue)                    [ Check Loan Quota ]
                                                                                                  │
                                                                             ┌────────────────────┴────────────────────┐
                                                                             ▼                                         ▼
                                                                     [ Exceeds Quota ]                           [ Within Limit ]
                                                                             │                                         │
                                                                             ▼                                         ▼
                                                                  (Block: Max Loans Active)                 [ Check Asset Eligibility ]
                                                                                                                       │
                                                                                                  ┌────────────────────┴────────────────────┐
                                                                                                  ▼                                         ▼
                                                                                        [ Student Restricted ]                    [ Unrestricted ]
                                                                                                  │                                         │
                                                                                                  ▼                                         ▼
                                                                                       (Block: Faculty Only)                 [ Issue Loan Transaction ]
```

- **Student Eligibility:** Students (BSIT, BSFAS, & BIndTech) may only check out assets flagged with `is_student_borrowable = 1`. High-value or faculty-designated assets are reserved exclusively for faculty.
- **Loan Durations:**
  - Students: 4-hour session loans (same-day return before 5:00 PM) to ensure fair sharing of laboratory tools.
  - Faculty: Multi-day loans (up to 7 days by default) for course instruction and laboratory preparation.
- **Concurrent Loan Quotas:**
  - Students: Maximum 3 active items.
  - Faculty: Maximum 10 active items.
- **Automatic Account Holds:**
  - Users with at least one overdue asset are barred from new checkouts until the overdue item is checked in.
  - Items returned damaged or non-functional immediately update the borrower's record with `has_damage_hold = 1` and record an incident in `damage_incidents`.

---

## Directory Structure

```
slsu-inventory/
├── .env                                # Active environment file (Ignored by Git)
├── .env.example                        # Template for environment configuration
├── .gitignore                          # Excludes vendor/, cache, logs, and sensitive data
├── .htaccess                           # Root Apache directives and public folder rewrites
├── composer.json                       # PHP package definitions and PSR-4 namespace map
├── composer.lock                       # Pinned dependency versions
├── composer.phar                       # Standalone Composer CLI binary
├── run_migrations.bat                  # Batch script to execute migrations in XAMPP
├── setup_xampp_link.bat                # Batch script to create an NTFS Directory Junction
│
├── app/                                # Application source code (Namespace: App\)
│   ├── Config/
│   │   └── Database.php                # PDO connection singleton loader
│   ├── Controllers/                    # Application request handlers
│   │   ├── AdminUserController.php     # User management, approvals, role elevations
│   │   ├── AnalyticsController.php     # Executive analytics, KPI calculation, CSV exports
│   │   ├── AuditLogController.php      # Security log inspection and filtering
│   │   ├── AuthController.php          # Login, registration, session management
│   │   ├── CustodianLoanController.php # Active loans, reservations, overdue monitoring
│   │   ├── DashboardController.php     # Role-based dashboard dispatcher
│   │   ├── DisposalController.php      # BER staging, batch disposal, salvage valuation
│   │   ├── InventoryController.php     # Asset intake, updates, inventory CSV export
│   │   ├── NotificationController.php  # User alerts, stock notifications, badge counts
│   │   ├── RecordsController.php       # Borrower transaction history & clearance ledger
│   │   ├── SettingsController.php      # User profiles, avatar uploads, security settings
│   │   └── TransactionController.php   # Barcode lookup, checkout, returns, damage reports
│   ├── Helpers/                        # Framework utility classes
│   │   ├── Autoloader.php              # Standalone PSR-4 autoloader
│   │   ├── Router.php                  # Path-based HTTP router supporting GET/POST
│   │   └── Security.php                # CSRF generation/validation, password hashing, XSS filters
│   ├── Middleware/                     # Route guards
│   │   ├── AuthMiddleware.php          # Enforces active sessions and session integrity
│   │   └── RoleMiddleware.php          # Enforces RBAC permissions per route
│   ├── Models/                         # Data layer models (Prepared PDO queries)
│   │   ├── AuditLog.php                # Immutable audit log recorder and reader
│   │   ├── DamageIncident.php          # Damage tracking, clearance holds, settlements
│   │   ├── DisposalRecord.php          # Asset decommissioning records and methods
│   │   ├── Item.php                    # Asset catalog queries, barcode lookups, formulas
│   │   ├── Transaction.php             # Loan state transitions, checkouts, returns
│   │   └── User.php                    # User records, authentication, role assignment
│   └── Services/                       # Shared business services
│       └── NotificationService.php     # System alert dispatcher and notification preference rules
│
├── config/                             # Runtime system settings
│   ├── app.php                         # Application name, URL, roles, borrowing limits
│   └── database.php                    # PDO connection engine and Philippine Standard Time sync
│
├── database/                           # Schema and migration tools
│   ├── migrate.php                     # Standalone CLI migration and database creator
│   ├── schema.sql                      # Complete MySQL DDL schema (Tables, Indexes, Constraints)
│   ├── seed.sql                        # Default system administrator account seed
│   └── migrations/                     # Incremental SQL migration patches
│
├── public/                             # Web root exposed to browser
│   ├── .htaccess                       # Directs requests to index.php front controller
│   ├── index.php                       # Application entry point and route definitions
│   ├── css/
│   │   ├── custom.css                  # Custom styling, animations, and component tokens
│   │   └── tailwind.css                # Tailwind utility styles
│   ├── js/
│   │   ├── app.js                      # Core AJAX helpers, modals, and event bindings
│   │   ├── chart.min.js                # Chart.js library
│   │   ├── html5-qrcode.min.js         # Camera barcode scanning library
│   │   └── JsBarcode.all.min.js        # Barcode generation library
│   └── uploads/                        # User avatars and file attachments
│
├── scripts/                            # CLI verification, audit, and maintenance utilities
│   ├── check_assets.php                # Quick integrity check on registered assets
│   ├── migrate.php                     # Script for automated incremental schema updates
│   ├── migrate_disposals.php           # Backfills disposal table fields if missing
│   ├── purge_assets.php                # Reset asset records for fresh testing
│   ├── purge_data_keep_users.php       # Clear transactions while preserving accounts
│   ├── test_runner_exec.php            # Headless controller execution runner
│   ├── test_transactions_flow.php     # CLI simulation of full checkout/return cycle
│   ├── verify_custodian_reports.php    # Tests custodian export generation
│   ├── verify_dashboards_render.php    # Tests all dashboard views for clean rendering
│   ├── verify_disposal_lifecycle.php   # Tests BER staging to final disposal
│   ├── verify_executive_analytics.php  # Verifies analytics data generation
│   ├── verify_js_syntax.php            # Verifies JavaScript script integrity
│   ├── verify_notifications.php        # Tests notification dispatching
│   ├── verify_records_ledger.php       # Verifies transaction history output
│   ├── verify_role_boundaries.php      # Validates RBAC restrictions across all routes
│   └── verify_settings.php             # Tests user profile and credential updates
│
└── views/                              # Template layer (PHP + Tailwind markup)
    ├── admin/                          # System administrator interfaces
    │   ├── audit_logs.php              # Audit trail table and search filters
    │   └── user_management.php         # User approval queue and directory
    ├── auth/                           # Authentication forms
    │   ├── login.php                   # User login interface
    │   └── register.php                # Self-service registration form
    ├── components/                     # Reusable interface components
    │   ├── barcode-card.php            # Printable barcode label preview
    │   ├── button.php                  # Standardized button variants
    │   ├── countdown-timer.php         # Active loan timer component
    │   ├── data-table.php              # Data table container wrapper
    │   ├── empty-state.php             # Blank state indicator
    │   ├── form-input.php              # Standardized form input field
    │   ├── modal.php                   # Modal dialog structure
    │   ├── stat-card.php               # KPI statistic card
    │   └── status-badge.php            # Dynamic status indicator badge
    ├── custodian/                      # Property custodian operations
    │   └── loans.php                   # Active loans, reservations, and overdue queue
    ├── dashboard/                      # Role-specific landing pages
    │   ├── admin.php                   # System Administrator overview
    │   ├── custodian.php               # Property Custodian overview
    │   ├── executive.php               # Campus Director briefing delegator
    │   ├── faculty.php                 # Faculty equipment portal
    │   └── student.php                 # Student laboratory checkout portal
    ├── disposal/                       # Asset condemnation interface
    │   └── index.php                   # Staged and disposed property tables
    ├── inventory/                      # Asset registry
    │   ├── index.php                   # Master asset table, CSV import/export
    │   └── modals/                     # Asset intake, edit, BER, and delete modals
    ├── layouts/                        # Base page wrappers
    │   ├── shell.php                   # Main navigation header, drawer, and stylesheet imports
    │   └── shell-footer.php            # Footer, dynamic toast alerts, and JavaScript bundles
    ├── records/                        # Transaction logs and incident tracking
    │   ├── incidents.php               # Damage incident ledger and settlement clearing
    │   └── index.php                   # Personal borrower transaction history
    ├── reports/                        # Analytics and audit reports
    │   ├── executive_briefing.php      # Formatted printable executive report
    │   └── index.php                   # Interactive analytics dashboard with Chart.js
    ├── settings/                       # Account preferences
    │   └── index.php                   # Profile information, security settings, avatar
    └── transactions/                   # Equipment custody operations
        ├── scan.php                    # Universal barcode scanning and return desk
        └── modals/                     # Modal for item inspection details
```

---

## Database Schema Reference

The system database (`slsu_inventory`) consists of eight relational tables designed with foreign key constraints and indexes for fast lookups.

```
 +--------------------+        +-----------------------+        +----------------------+
 |       users        | 1    * |     transactions      | *    1 |        items         |
 |--------------------|--------|-----------------------|--------|----------------------|
 | user_id (PK)       |        | transaction_id (PK)   |        | item_id (PK)         |
 | id_number          |        | item_id (FK)          |        | property_number      |
 | username           |        | user_id (FK)          |        | barcode              |
 | full_name          |        | transaction_type      |        | item_name            |
 | email              |        | transaction_date      |        | category             |
 | password_hash      |        | expected_return_date  |        | location             |
 | role               |        | actual_return_date    |        | is_student_borrowable|
 | department_program |        | remarks               |        | status               |
 | account_status     |        | returned_condition    |        | item_condition       |
 | has_damage_hold    |        | processed_by (FK)     |        +----------------------+
 +--------------------+        +-----------------------+                   │ 1
           │ 1                            │ 1                              │
           │                              │                                │ *
           │ *                            │ *                              ▼
 +--------------------+        +-----------------------+        +----------------------+
 |   notifications    |        |   damage_incidents    |        |   disposal_records   |
 |--------------------|        |-----------------------|        |----------------------|
 | notification_id(PK)|        | incident_id (PK)      |        | id (PK)              |
 | user_id (FK)       |        | transaction_id (FK)   |        | item_id (FK)         |
 | type               |        | item_id (FK)          |        | disposed_by_user (FK)|
 | category           |        | borrower_id (FK)      |        | disposal_method      |
 | title              |        | damage_condition      |        | salvage_value        |
 | message            |        | damage_description    |        | disposal_date        |
 | is_read            |        | incident_status       |        +----------------------+
 +--------------------+        | settlement_type       |
                               +-----------------------+
```

### Table Definitions

1. **`users`**: User records, institutional identification numbers, authentication credentials, assigned roles (`admin`, `custodian`, `executive_admin`, `faculty`, `student`), account status (`pending`, `approved`, `rejected`, `suspended`), concurrent loan quotas, and damage hold flags.
2. **`login_attempts`**: Tracks IP addresses, usernames, and timestamps to mitigate brute-force authentication attacks.
3. **`items`**: Asset inventory records including university property number, unique barcode, name, description, category, storage location, student borrowable flag, status (`available`, `requested`, `borrowed`, `maintenance`, `for_disposal`, `disposed`), and physical condition (`mint`, `good`, `fair`, `damaged`, `not_working`, `for_disposal`).
4. **`transactions`**: Complete borrowing and return history linking an item and a user, containing transaction dates, scheduled deadlines, return dates, inspection notes, return conditions, and the custodian who processed the transaction.
5. **`damage_incidents`**: Tracks equipment returned in damaged or inoperable condition. Links the incident to the borrower, original transaction, and item, recording estimated costs, settlement options (`in_kind_replacement`, `repair_cost`, `admin_waiver`), and resolution sign-offs.
6. **`disposal_records`**: Formal record of decommissioned items. Captures disposal methods (`scrapped`, `sold`, `donated`, `cannibalized`, `transferred`), reasons, salvage values, disposal dates, and authorized personnel.
7. **`audit_logs`**: Immutable security log recording user actions, event descriptions, client IP addresses, user agents, and timestamps.
8. **`notifications`**: Targeted user alerts regarding loan approvals, return deadlines, overdue notices, and administrative account updates.

---

## Prerequisites

Ensure your system meets the following requirements before installation:

- **Operating System:** Windows 10/11, Ubuntu 22.04+, or macOS Sonoma+.
- **PHP:** Version `8.2` or higher (required extensions: `pdo_mysql`, `mbstring`, `openssl`, `gd`, `fileinfo`).
- **Database:** MySQL `8.0+` or MariaDB `10.4+` (default bundled with XAMPP).
- **Web Server:** Apache `2.4.x` with `mod_rewrite` enabled.
- **Dependency Manager:** Composer `2.x` (or the included standalone `composer.phar`).
- **Web Browser:** Google Chrome, Mozilla Firefox, or Microsoft Edge (modern versions supporting camera media devices).

---

## Getting Started (Local Setup with XAMPP)

Follow these steps to set up the system on a local Windows development machine using XAMPP.

### 1. Clone the Repository
Clone the project into your preferred directory:
```bash
git clone https://github.com/your-username/slsu-inventory.git "C:\Users\venre\Documents\AlabatIMS Project\slsu-inventory"
cd "C:\Users\venre\Documents\AlabatIMS Project\slsu-inventory"
```

### 2. Create the XAMPP Directory Junction
Rather than copying the codebase into `C:\xampp\htdocs\` and risking out-of-sync edits, create an NTFS Directory Junction. 

Right-click `setup_xampp_link.bat` and select **Run as administrator**, or run this command in an elevated Command Prompt:
```cmd
mklink /J "C:\xampp\htdocs\slsu-inventory" "C:\Users\venre\Documents\AlabatIMS Project\slsu-inventory"
```
This maps the project directly into Apache's web root without duplicating files.

### 3. Configure PHP Extensions in `php.ini`
Open `C:\xampp\php\php.ini` and verify that the following extensions are enabled (remove the leading semicolon if present):
```ini
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=gd
extension=fileinfo
```
Save the file and restart Apache from the XAMPP Control Panel.

### 4. Install PHP Dependencies
If you have Composer installed globally:
```bash
composer install
```
If you do not have Composer installed, use the included binary:
```bash
php composer.phar install
```

### 5. Configure Environment Variables
Copy `.env.example` to create your local `.env` file:
```cmd
copy .env.example .env
```
Ensure the database settings match your local XAMPP configuration:
```ini
APP_NAME="SLSU-Alabat IMS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/slsu-inventory/public

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=slsu_inventory
DB_USER=root
DB_PASS=

SESSION_LIFETIME=900
SESSION_SECURE_COOKIE=false
```

### 6. Run Database Migrations & Seeds
Start **MySQL** in the XAMPP Control Panel, then execute the migration script:
```bash
php database/migrate.php
```
*Alternatively, double-click `run_migrations.bat`.*

This script automatically:
1. Connects to MySQL and creates the `slsu_inventory` database if it does not already exist.
2. Applies table schemas and constraints from `database/schema.sql`.
3. Seeds the default System Administrator account from `database/seed.sql`.

### 7. Access the Application
Open your web browser and navigate to:
```
http://localhost/slsu-inventory/public/
```

Log in using the default administrative credentials:
- **Username:** `admin` (or `admin@slsu.edu.ph`)
- **Password:** `Admin@12345`

*Change this password immediately under **User Settings** before deploying to production.*

---

## Architecture & Request Lifecycle

The application operates as a lightweight Model-View-Controller system with a centralized Front Controller.

```
1. Client Browser (Tailwind UI / Barcode Scanner)
   │
   ▼
2. Apache Web Server (.htaccess)
   │ Rewrites incoming requests to /public/index.php
   │ Blocks access to .env, /app, /config, /views, /database
   │
   ▼
3. Front Controller (public/index.php)
   │ ├── Registers PSR-4 Autoloader (App\Helpers\Autoloader)
   │ ├── Loads environment variables (.env)
   │ ├── Initializes Security & Session (Security::initSession)
   │ └── Instantiates Router (App\Helpers\Router)
   │
   ▼
4. Route Guards & Middleware
   │ ├── AuthMiddleware: Verifies active session, checks session timeout & fingerprint
   │ └── RoleMiddleware: Confirms the user has the required RBAC role
   │
   ▼
5. Controller Dispatching (e.g., TransactionController)
   │ ├── Validates CSRF token on POST/PUT requests
   │ ├── Sanitizes user input
   │ ├── Runs borrowing business rules
   │ └── Calls Model methods
   │
   ▼
6. Data Layer (App\Models\* via PDO)
   │ ├── Executes parameterized SQL queries
   │ └── Logs actions to audit_logs
   │
   ▼
7. View Rendering
   └── Emits sanitized HTML (via views/layouts/shell.php) or JSON payload
```

### Key Security Implementations

- **Password Hashing:** Uses `PASSWORD_ARGON2ID` with an automated fallback to `PASSWORD_DEFAULT` (Bcrypt), avoiding legacy hashing algorithms like MD5 or SHA1.
- **CSRF Defense:** `Security::validateCsrfToken()` validates random 32-byte tokens on all state-altering requests. The token can be passed either as `$_POST['csrf_token']` or via the `X-CSRF-TOKEN` HTTP header for AJAX requests.
- **SQL Injection Prevention:** All SQL queries that handle dynamic parameters use PDO prepared statements with explicitly bound parameters. Native prepared emulation is disabled (`ATTR_EMULATE_PREPARES => false`).
- **Cross-Site Scripting (XSS):** Dynamic output in template files is filtered using `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')`.
- **CSV Formula Injection Mitigation:** All exported CSV columns pass through `Item::neutralizeFormula()`. Cells beginning with `=`, `+`, `-`, or `@` are prefixed with a single quote to prevent spreadsheet software from executing arbitrary formulas.
- **Session Hardening:**
  - Cookies use `HttpOnly` and `SameSite=Lax` flags.
  - Generates a browser fingerprint using the client's User-Agent to detect session hijacking.
  - Automatic session timeout after 15 minutes of inactivity (`SESSION_LIFETIME=900`).

---

## Environment Variables

| Variable | Required | Default | Description |
| :--- | :---: | :---: | :--- |
| `APP_NAME` | No | `SLSU-Alabat IMS` | Display title for headers, browser tabs, and notification emails. |
| `APP_ENV` | Yes | `local` | Current environment (`local`, `staging`, or `production`). |
| `APP_DEBUG` | Yes | `true` | When `false`, suppresses detailed error traces from end-users. |
| `APP_URL` | Yes | `http://localhost/slsu-inventory/public` | Base URL used to generate links and asset paths. |
| `DB_HOST` | Yes | `127.0.0.1` | Database hostname or IP address. |
| `DB_PORT` | Yes | `3306` | MySQL database connection port. |
| `DB_NAME` | Yes | `slsu_inventory` | Database schema name. |
| `DB_USER` | Yes | `root` | Database username. |
| `DB_PASS` | Yes | `""` | Database user password. |
| `SESSION_LIFETIME` | No | `900` | Inactivity window in seconds before expiring user sessions (15 minutes). |
| `SESSION_SECURE_COOKIE` | No | `false` | Set to `true` in production when serving exclusively over HTTPS. |

---

## Available Scripts & Verification Tools

The `scripts/` directory provides CLI tools to test and verify system behavior:

```bash
# Apply database migrations and seeds
php database/migrate.php

# Run an automated transaction simulation (Borrow -> Overdue Check -> Return -> Damage Log)
php scripts/test_transactions_flow.php

# Test Role-Based Access Control boundaries across all application routes
php scripts/verify_role_boundaries.php

# Verify dashboard rendering across all roles
php scripts/verify_dashboards_render.php

# Test asset disposal and condemnation workflow (BER staging to salvage)
php scripts/verify_disposal_lifecycle.php

# Validate executive analytics calculations and chart datasets
php scripts/verify_executive_analytics.php

# Check notification dispatching and unread counts
php scripts/verify_notifications.php

# Check custodian export generation
php scripts/verify_custodian_reports.php

# Test user settings and password updates
php scripts/verify_settings.php
```

---

## Testing & Quality Evaluation

The system was evaluated against the **ISO/IEC 25010 Software Quality Model** across five core characteristics:

1. **Functional Suitability:**
   - Evaluated via Black Box testing across key functional paths: user authentication, role assignment, asset intake, barcode printing, camera scanning, checkout quotas, return condition inspection, and asset disposal.
   - Verified that unauthorized students cannot bypass frontend checks to borrow restricted laboratory equipment.
2. **Performance Efficiency:**
   - Database queries are indexed on status, category, and foreign keys.
   - Clean page response times under local Apache environments averaging under 300ms.
3. **Usability:**
   - Dynamic tabbed interfaces in the Custodian Operations Center (`views/custodian/loans.php`) organize active loans, pending holds, and completed transactions into distinct views.
   - Barcode scanning provides immediate feedback with sound, status badges, and clear error alerts.
4. **Reliability:**
   - Transaction state changes (`Transaction::borrowItem`, `Transaction::returnItem`) run inside database transactions to ensure consistency.
   - Damaged equipment returns cannot be saved without an accompanying condition report.
5. **Security:**
   - Role boundaries verified by automated test scripts (`scripts/verify_role_boundaries.php`).
   - Zero-trust server-side validation on every POST request.

---

## Production Deployment Guide

### Deploying to a Standard Web Host or Cloud PaaS (e.g., InfinityFree / Apache VPS)

1. **Prepare the Database:**
   - Open your web host's database manager (such as phpMyAdmin).
   - Create a database (e.g., `epiz_12345678_slsu_inventory`).
   - Import `database/schema.sql` followed by `database/seed.sql`.
2. **Upload Files:**
   - Upload the project files to your server using SFTP or FileZilla.
   - Ensure the server document root points to the `public/` directory. If your host restricts you to a single `htdocs/` folder, place the application files one level above the web root, or use the root `.htaccess` file to route requests to `/public/`.
3. **Configure the Production Environment:**
   Create a `.env` file on the server with production settings:
   ```ini
   APP_NAME="SLSU-Alabat IMS"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com

   DB_HOST=sqlxxx.yourhost.com
   DB_PORT=3306
   DB_NAME=your_db_name
   DB_USER=your_db_user
   DB_PASS=your_strong_password

   SESSION_LIFETIME=900
   SESSION_SECURE_COOKIE=true
   ```
4. **Optimize Composer Autoloader:**
   Run this command in the project root:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
5. **Enable HTTPS / SSL (Required for Camera Scanning):**
   - Web browsers (Chrome, Edge, Safari) strictly require an **HTTPS** connection to access device cameras via `navigator.mediaDevices.getUserMedia()`.
   - Enable an SSL certificate on your domain (via Let's Encrypt or your host's free SSL provider). Camera barcode scanning will not function on remote devices over plain HTTP.

---

## Troubleshooting & Common Issues

### 1. Database Connection Refused (`SQLSTATE[HY000] [2002]`)
- **Cause:** MySQL service is stopped or listening on a different port.
- **Solution:** 
  1. Open the XAMPP Control Panel and verify the MySQL module is green.
  2. Check your `.env` file to confirm `DB_HOST=127.0.0.1` and `DB_PORT=3306`. Avoid using `localhost` on some Windows configurations where IPv6 resolution conflicts occur.

### 2. 404 Not Found on Route Navigation
- **Cause:** Apache `mod_rewrite` is disabled, or `.htaccess` overrides are ignored.
- **Solution:**
  1. Open `C:\xampp\apache\conf\httpd.conf`.
  2. Ensure this line is uncommented:
     ```apache
     LoadModule rewrite_module modules/mod_rewrite.so
     ```
  3. Ensure the `Directory` block for your web root has `AllowOverride All`:
     ```apache
     <Directory "C:/xampp/htdocs">
         AllowOverride All
         Require all granted
     </Directory>
     ```
  4. Restart Apache.

### 3. Barcode Scanner Camera Shows a Black Screen or Permission Error
- **Cause:** Missing camera permissions, or accessing the application over an insecure connection.
- **Solution:**
  1. If testing locally on the same machine, `http://localhost/` is treated as a secure origin by browsers.
  2. If accessing from another machine or phone on the local network (e.g., `http://192.168.1.50/`), browsers block camera access. Use an HTTPS connection or set up a reverse proxy with an SSL certificate.
  3. Verify that browser permissions grant camera access to the site.

### 4. Windows NTFS Directory Junction Fails (`mklink`)
- **Cause:** Missing administrative privileges.
- **Solution:** Right-click the Command Prompt icon, select **Run as Administrator**, and re-run the `setup_xampp_link.bat` script.

### 5. Timezone Discrepancies in Transaction Timestamps
- **Cause:** Database server defaults to UTC instead of local Philippine time.
- **Solution:** The application automatically runs `date_default_timezone_set('Asia/Manila')` and attempts `SET time_zone = '+08:00'` on every PDO connection in `config/database.php`. Ensure your MySQL server's system time matches Philippine Standard Time.

---

## License & Institutional Attribution

Developed for **Southern Luzon State University (SLSU) – Alabat Campus** under the academic study:
*Web-Based Inventory Management and Properties Tracking System for SLSU-Alabat* (April 2026).
All rights reserved. Proprietary software for university operational and educational use.
