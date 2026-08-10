# Handoff — how to run SPMS

## Requirements

- Windows: XAMPP (Apache + MySQL + PHP 8) **or** equivalent stack  
- Python 3.10+ (3.14 works with PyTorch CPU in our setup)  
- Browser  

## Demo logins

| Role | Email | Password |
|------|--------|----------|
| Admin | admin@spms.local | password123 |
| Manager | manager@spms.local | password123 |
| Staff | staff1@spms.local | password123 |

## Start order

1. **MySQL** → import `database/schema.sql` then `database/seed.sql`  
2. **Apache** → open `http://localhost/spms/` (map `web/public` into htdocs)  
3. **Flask** → `cd ml_service` → `python app.py`  
4. Login as **manager** → **Forecasts** → Run prediction  

Windows helpers:

- `docs/start-xampp.ps1`  
- `docs/start-ml.ps1`  
- `docs/stop-xampp.ps1`  

## Config files

- `web/config/database.php` — MySQL host/user/password  
- `web/config/app.php` — `ml_api_base` (default `http://127.0.0.1:5000`)  
- `ml_service/config.py` — DB + retrain secret (must match PHP secret for retrain)  

## If forecast says “Train first”

```bash
cd ml_service
python train.py --product <ID> --model both
```

## What each part is

| Folder | Role |
|--------|------|
| `web/` | Shop UI (people, sales, stock, reports, forecasts page) |
| `ml_service/` | Prediction brain (API) |
| `database/` | Schema + seed users/products |
