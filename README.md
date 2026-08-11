# Sales Prediction Management System

PHP/MySQL sales app + Python Flask forecasting (Linear Regression & LSTM).

## Stack

- **Web:** PHP 8, Bootstrap 5, Chart.js  
- **DB:** MySQL / MariaDB  
- **ML API:** Flask, scikit-learn, PyTorch  

## Setup

### 1. Database

Start MySQL (XAMPP). Import:

1. `database/schema.sql`  
2. `database/seed.sql`  

Optional longer sales history for ML:

```bash
cd ml_service
python generate_seed_sales.py --clear --days 300
python train.py
```

### 2. Web app

Start Apache. Point document root / alias at `web/public`  
(e.g. `http://localhost/spms/`).

DB settings: `web/config/database.php`  
(default: `root`, empty password, database `sales_prediction_db`)

### 3. Prediction API

```bash
cd ml_service
python -m pip install -r requirements.txt
python -m pip install torch --index-url https://download.pytorch.org/whl/cpu
python app.py
```

Health: http://127.0.0.1:5000/health  

Windows helper: `scripts/start-ml.ps1`  
XAMPP helper: `scripts/start-xampp.ps1`

### 4. Open

http://localhost/spms/

| Role | Email | Password |
|------|--------|----------|
| Admin | `admin@spms.local` | `password123` |
| Manager | `manager@spms.local` | `password123` |
| Staff | `staff1@spms.local` | `password123` |

## Layout

```
database/     schema + seed SQL
web/          PHP app (public/ = web root)
ml_service/   Flask API + models/
scripts/      Windows start/stop helpers
```

## Notes

- Forecasts need the Flask API running.  
- New products need sales history + `python train.py --product <id>` before predict.  
- Trained models in `ml_service/models/` are included for demos.  
