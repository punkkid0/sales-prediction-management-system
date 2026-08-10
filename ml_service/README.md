# SPMS Prediction Engine (Phase 2)

Python service that trains and serves sales forecasts.

## Setup (once)

```powershell
cd "C:\Users\HP\Desktop\Sales Prediction Management System\ml_service"
python -m pip install -r requirements.txt
python -m pip install torch --index-url https://download.pytorch.org/whl/cpu
```

MySQL must be running (XAMPP).

## One-time: create long sales history

```powershell
python generate_seed_sales.py --clear --days 300
```

## Train models

```powershell
python train.py                  # all products, LR + LSTM
python train.py --product 3      # one product
```

## Start API

```powershell
python app.py
```

API base: **http://127.0.0.1:5000**

| Endpoint | Purpose |
|----------|---------|
| GET `/health` | Is the service up? |
| GET `/products` | Products + model files |
| POST `/predict` | Forecast |
| POST `/retrain` | Train again (needs secret) |

### Predict example

```powershell
curl -X POST http://127.0.0.1:5000/predict -H "Content-Type: application/json" -d "{\"product_id\":3,\"horizon_days\":7,\"model\":\"lstm\"}"
```

### Retrain example

Secret is in `config.py` → `RETRAIN_SECRET` (same as PHP `app.php`).

```powershell
curl -X POST http://127.0.0.1:5000/retrain -H "Content-Type: application/json" -d "{\"secret\":\"change-me-spms-secret\",\"product_id\":3,\"model\":\"both\"}"
```

## Files

| File | Role |
|------|------|
| `app.py` | Flask API |
| `train.py` | Training CLI |
| `preprocess.py` | Daily series + features |
| `models_lr.py` | Linear Regression |
| `models_lstm.py` | LSTM (PyTorch / NumPy fallback) |
| `generate_seed_sales.py` | Fake history for demos |
| `models/` | Saved trained models |
