# Sales Prediction Management System

PHP/MySQL sales app + Python Flask forecasting (Linear Regression & LSTM).

## Easy Windows start (double-click)

| File | What it does |
|------|----------------|
| **`1-SETUP.bat`** | First time only: check/install Python packages, XAMPP, import DB, link `/spms`, check ngrok |
| **`2-START-SERVERS.bat`** | Start MySQL + Apache + Flask, open local login |
| **`3-START-NGROK.bat`** | Public HTTPS link (PC must stay on; copy URL from http://127.0.0.1:4040) |
| **`STOP-SERVERS.bat`** | Stop everything |
| **`READ-ME-FIRST.txt`** | Short instructions for the person running it |

Local app: http://localhost/spms/

| Role | Email | Password |
|------|--------|----------|
| Admin | `admin@spms.local` | `password123` |
| Manager | `manager@spms.local` | `password123` |
| Staff | `staff1@spms.local` | `password123` |

## Manual setup (optional)

### Database
Start MySQL (XAMPP). Import `database/schema.sql` then `database/seed.sql`.

### Web
Point Apache at `web/public` (setup creates `C:\xampp\htdocs\spms` junction).

### ML API
```bash
cd ml_service
python -m pip install -r requirements.txt
python -m pip install torch --index-url https://download.pytorch.org/whl/cpu
python app.py
```

## Layout

```
1-SETUP.bat / 2-START-SERVERS.bat / 3-START-NGROK.bat / STOP-SERVERS.bat
database/     schema + seed SQL
web/          PHP app (public/ = web root)
ml_service/   Flask API + trained models/
scripts/      detailed .bat helpers
```

## Notes

- Forecasts need Flask running.  
- New products need sales history + `python train.py --product <id>`.  
- Trained models in `ml_service/models/` are included for demos.  
- Free ngrok URL changes when you restart ngrok.  
