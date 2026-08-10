# How to run — Sales Prediction Management System (Phase 1)

## Already installed on this PC

| Tool | Location |
|------|----------|
| XAMPP 8.2 | `C:\xampp` |
| PHP 8.2 | `C:\xampp\php` (also on User PATH) |
| MySQL/MariaDB | `C:\xampp\mysql` (on User PATH) |
| App URL | http://localhost/spms/ |
| phpMyAdmin | http://localhost/phpmyadmin/ |
| DB name | `sales_prediction_db` |
| App link | `C:\xampp\htdocs\spms` → project `web\public` |

---

## Every time you want to work (start Apache + MySQL)

### Easiest way — XAMPP Control Panel

1. Open **Start menu** → search **XAMPP Control Panel**  
   (or run `C:\xampp\xampp-control.exe`)
2. Click **Start** next to **Apache** (turns green when running)
3. Click **Start** next to **MySQL** (turns green when running)
4. Open your browser: **http://localhost/spms/**

If Start fails (port busy):
- Port **80** used by Skype/IIS → stop that app, or change Apache port in XAMPP
- Port **3306** used by another MySQL → stop the other service

### Alternative — PowerShell helpers

From the project folder:

```powershell
powershell -ExecutionPolicy Bypass -File docs\start-xampp.ps1
```

Stop:

```powershell
powershell -ExecutionPolicy Bypass -File docs\stop-xampp.ps1
```

---

## Demo logins

| Role    | Email               | Password    |
|---------|---------------------|-------------|
| Admin   | admin@spms.local    | password123 |
| Manager | manager@spms.local  | password123 |
| Staff   | staff1@spms.local   | password123 |

---

## Database (already imported once)

If you need to re-import:

1. Start MySQL in XAMPP Control Panel
2. Open http://localhost/phpmyadmin/
3. Import `database/schema.sql`, then `database/seed.sql`

Or CLI:

```powershell
mysql -u root -h 127.0.0.1 < database\schema.sql
mysql -u root -h 127.0.0.1 < database\seed.sql
```

Config file if needed: `web/config/database.php`  
(default: user `root`, empty password, host `127.0.0.1`)

---

## What Phase 1 includes

- Login / logout with roles
- Products, customers, suppliers, users, promotions CRUD
- POS-style sales with stock deduction + receipt
- Sales history filters
- Dashboard KPIs + Chart.js
- Reports + CSV export
- Low-stock alerts (`reorder_level`)

## Phase 2–3 (prediction)

1. Start Apache + MySQL (above).
2. Start the brain:

```powershell
powershell -ExecutionPolicy Bypass -File docs\start-ml.ps1
```

Or:

```powershell
cd ml_service
python app.py
```

3. Login as **manager** or **admin** → sidebar **Forecasts**.
4. Pick a product → **Run prediction** → chart + reorder suggestion.

- API health: http://127.0.0.1:5000/health  
- Shop: http://localhost/spms/  
