# Phase 2 explained simply + how the code is organized

## What is “prediction” here? (plain English)

Prediction means:

> Looking at **how much of each product was sold in the past**, then estimating **how much you might sell in the next few days**.

It is **not magic** and **not a guarantee**.  
It is a **smart estimate** the computer learns from patterns.

### Everyday example

- Last many weeks, **Indomie** sells more on weekends  
- When there is a **promo**, sales jump  
- The model learns: “weekend + promo → higher sales”  
- Then it says: “Next 7 days, maybe about X cartons per day”

Manager uses that to plan stock (later Phase 3 shows this on the dashboard).

---

## The two models (like two students guessing)

| Model | Simple meaning | Strength |
|-------|----------------|----------|
| **Linear Regression** | Draws a “best straight-line relationship” between past patterns and tomorrow’s sales | Fast, easy to explain, good **baseline** |
| **LSTM** | A neural network that is good at **sequences over time** (remembers recent patterns) | Often better for ups/downs over days |

We always train **both**, then compare with:

- **MAE** — average “how many units wrong”  
- **RMSE** — similar, but punishes big mistakes more  

**Lower MAE/RMSE = better.**

---

## The pipeline (factory line)

```
MySQL sales  →  clean daily table  →  train models  →  save files  →  Flask API answers questions
```

1. **Collect** past sales from the database  
2. **Clean** → one number per product per day (empty days = 0)  
3. **Add clues (features)**  
   - day of week  
   - month  
   - weekend?  
   - promo running?  
4. **Train** Linear Regression + LSTM  
5. **Serve** predictions on `http://127.0.0.1:5000`

---

## How this maps to your thesis write-up

| Write-up idea | Phase 2 piece |
|---------------|---------------|
| Prediction Engine (Fig 3.5) | `ml_service/` Flask app |
| Preprocessing | `preprocess.py` |
| Regression | `models_lr.py` |
| LSTM / deep learning | `models_lstm.py` |
| MAE / RMSE | printed in training + returned by API |
| External factors | `is_promo`, calendar features |
| Feedback / retrain | `POST /retrain` |

---

## Codebase map (whole project)

```
Sales Prediction Management System/
│
├── vincent 123.docx                 ← your thesis write-up
├── Sales_Prediction_System_Build_Plan.docx
│
├── database/
│   ├── schema.sql                   ← table designs
│   └── seed.sql                     ← starter users/products
│
├── web/                             ← Phase 1 PHP shop system
│   ├── public/index.php             ← front door (router)
│   ├── config/                      ← DB password, app name
│   ├── includes/                    ← login helpers, layout, CSRF
│   └── modules/                     ← each menu page
│       ├── auth/                    ← login/logout
│       ├── dashboard/
│       ├── products/, customers/, suppliers/
│       ├── sales/                   ← new sale, history, receipt
│       ├── promotions/
│       ├── users/
│       └── reports/
│
├── ml_service/                      ← Phase 2 brain (Python)
│   ├── app.py                       ← Flask API (the waiter)
│   ├── train.py                     ← train models
│   ├── generate_seed_sales.py       ← create long history for learning
│   ├── preprocess.py                ← clean + features
│   ├── models_lr.py                 ← Linear Regression
│   ├── models_lstm.py               ← LSTM
│   ├── db.py                        ← talk to MySQL
│   ├── config.py                    ← settings
│   └── models/                      ← saved trained brains (.joblib)
│
└── docs/
    ├── RUN.md
    └── PHASE2_EXPLAINED.md          ← this file
```

### PHP side (shop)

- User clicks buttons in browser  
- PHP checks login role  
- PHP reads/writes MySQL (sales, products, …)

### Python side (brain)

- Does **not** show the pretty pages  
- Only answers API questions like:  
  “What will product 3 sell for the next 7 days?”  
- Returns **JSON** (data), not HTML

Later Phase 3: PHP will **call** this Python brain and draw charts.

---

## Flask endpoints (what the brain can answer)

| Call | Meaning |
|------|---------|
| `GET /health` | “Are you awake?” |
| `GET /products` | List products + whether models exist |
| `POST /predict` | “Forecast this product” |
| `POST /retrain` | “Learn again from newest sales” (needs secret) |

Example predict body:

```json
{ "product_id": 3, "horizon_days": 7, "model": "lstm" }
```

---

## What you do day-to-day in Phase 2

1. MySQL running (XAMPP)  
2. Generate long history once: `python generate_seed_sales.py --clear --days 300`  
3. Train: `python train.py`  
4. Start API: `python app.py`  
5. Test predict with browser tool / curl / Phase 3 later  

---

## Honest limits (good for defense)

- Forecasts depend on **history quality**  
- Sudden events (pandemic, strike) may not be in the data  
- Future promos are unknown unless you enter them  
- Model is a **decision support tool**, not a crystal ball  
