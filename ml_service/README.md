# Prediction API

```bash
pip install -r requirements.txt
python app.py
```

| Endpoint | Purpose |
|----------|---------|
| `GET /health` | Status |
| `GET /products` | Products + model flags |
| `POST /predict` | `{ "product_id", "horizon_days?", "model?" }` |
| `POST /retrain` | `{ "secret", "product_id?", "model?" }` |

```bash
python generate_seed_sales.py --clear --days 300
python train.py
python train.py --product 3
```
