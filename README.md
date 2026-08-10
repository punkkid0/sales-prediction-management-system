# Sales Prediction Management System (SPMS)

Web-based **sales management** with **machine-learning sales forecasting** (Linear Regression + LSTM).

Built as a modular system:

- **PHP + MySQL** — authentication, POS sales, stock, reports, promotions  
- **Python Flask** — prediction engine (preprocess, train, `/predict`, `/retrain`)  
- **Integrated UI** — Forecasts page with charts, inventory suggestions, reorder watchlist  

---

## Stack

| Layer | Technology |
|-------|------------|
| Web app | PHP 8, Bootstrap 5, Chart.js |
| Database | MySQL / MariaDB |
| ML API | Flask, pandas, scikit-learn, PyTorch (LSTM) |
| Local stack | XAMPP (Apache + MySQL) recommended on Windows |

---

## Features

- Role-based login: **admin**, **manager**, **staff**
- Products, customers, suppliers, users, promotions
- POS-style sales with stock deduction and receipts
- Dashboard KPIs, reports, CSV export
- Daily sales series + calendar/promo features
- Linear Regression baseline + LSTM models
- Forecasts UI: history vs prediction, reorder suggestions, retrain (admin)

---

## Quick start (local)

### 1. Database

1. Start **MySQL** (XAMPP Control Panel).
2. Import `database/schema.sql`, then `database/seed.sql`.

Optional: generate longer history for better ML demos:

```bash
cd ml_service
python generate_seed_sales.py --clear --days 300
python train.py
```

### 2. Web app

1. Start **Apache**.
2. Point the site at `web/public` (e.g. junction/alias `http://localhost/spms/`).
3. Configure DB in `web/config/database.php` if needed (default: `root` / empty password / `sales_prediction_db`).

### 3. Prediction API

```bash
cd ml_service
python -m pip install -r requirements.txt
# Optional for best LSTM: pip install torch
python app.py
```

API: [http://127.0.0.1:5000/health](http://127.0.0.1:5000/health)

### 4. Open the app

[http://localhost/spms/](http://localhost/spms/)

| Role | Email | Password |
|------|--------|----------|
| Admin | `admin@spms.local` | `password123` |
| Manager | `manager@spms.local` | `password123` |
| Staff | `staff1@spms.local` | `password123` |

More detail: [docs/RUN.md](docs/RUN.md) · [docs/HANDOFF.md](docs/HANDOFF.md)

---

## Project layout

```
database/          schema.sql, seed.sql
web/               PHP application (document root: web/public)
  config/          database + app settings
  includes/        auth, layout, ML client
  modules/         dashboard, sales, forecasts, …
ml_service/        Flask prediction engine + trained models/
docs/              run guides, study notes
```

---

## Notes

- New products need **sales history** and **training** before forecast (`python train.py --product ID`).
- Forecasts require the Flask API to be running.
- Trained models under `ml_service/models/` are included so demos work without full retrain.

---

## License

Project source for academic / demo use.
