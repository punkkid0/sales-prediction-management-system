"""Linear Regression baseline model."""
from __future__ import annotations

import datetime as dt
from pathlib import Path
from typing import Any

import joblib
import numpy as np
import pandas as pd
from sklearn.linear_model import LinearRegression
from sklearn.preprocessing import StandardScaler

from config import LOOKBACK_DAYS, MODELS_DIR, TEST_RATIO
from preprocess import (
    FEATURE_COLS,
    build_daily_series,
    chronological_split,
    make_supervised,
    metrics,
)


def model_path(product_id: int) -> Path:
    MODELS_DIR.mkdir(parents=True, exist_ok=True)
    return MODELS_DIR / f"lr_product_{product_id}.joblib"


def train_linear(product_id: int) -> dict[str, Any]:
    daily = build_daily_series(product_id)
    X, y, _ = make_supervised(daily, lookback=LOOKBACK_DAYS, horizon=1)
    X_train, X_test, y_train, y_test = chronological_split(X, y, TEST_RATIO)

    scaler = StandardScaler()
    X_train_s = scaler.fit_transform(X_train)
    X_test_s = scaler.transform(X_test)

    model = LinearRegression()
    model.fit(X_train_s, y_train)

    pred_test = model.predict(X_test_s)
    scores = metrics(y_test, pred_test)

    payload = {
        "model": model,
        "scaler": scaler,
        "lookback": LOOKBACK_DAYS,
        "feature_mode": "flattened_window",
        "product_id": product_id,
        "metrics": scores,
    }
    joblib.dump(payload, model_path(product_id))
    return {"model_name": "linear_regression", "product_id": product_id, **scores}


def load_linear(product_id: int) -> dict:
    path = model_path(product_id)
    if not path.exists():
        raise FileNotFoundError(f"No Linear Regression model for product {product_id}. Train first.")
    return joblib.load(path)


def predict_linear(product_id: int, horizon_days: int) -> dict[str, Any]:
    """Roll one-step predictions forward for horizon_days."""
    bundle = load_linear(product_id)
    model: LinearRegression = bundle["model"]
    scaler: StandardScaler = bundle["scaler"]
    lookback = int(bundle["lookback"])

    daily = build_daily_series(product_id)
    hist = daily[FEATURE_COLS].to_numpy(dtype=float).copy()
    last_date = pd.to_datetime(daily["sale_date"].iloc[-1]).date()

    forecasts = []
    cur = hist.copy()
    for step in range(horizon_days):
        window = cur[-lookback:].reshape(1, -1)
        window_s = scaler.transform(window)
        qty = float(model.predict(window_s)[0])
        qty = max(0.0, qty)  # sales can't be negative

        next_date = last_date + dt.timedelta(days=step + 1)
        forecasts.append({"date": next_date.strftime("%Y-%m-%d"), "qty": round(qty, 2)})

        # Append synthetic next-day feature row for rolling forecast
        next_row = _next_feature_row(next_date, qty)
        cur = np.vstack([cur, next_row])

    return {
        "model_used": "linear_regression",
        "product_id": product_id,
        "mae": bundle.get("metrics", {}).get("mae"),
        "rmse": bundle.get("metrics", {}).get("rmse"),
        "forecasts": forecasts,
    }


def _next_feature_row(date: dt.date, qty: float) -> np.ndarray:
    """Build one FEATURE_COLS row for a future day (promo assumed 0 for future)."""
    # FEATURE_COLS = qty, day_of_week, month, is_weekend, is_promo, day_of_year
    dow = float(date.weekday())
    month = float(date.month)
    is_weekend = 1.0 if date.weekday() >= 5 else 0.0
    day_of_year = float(date.timetuple().tm_yday)
    return np.array([[qty, dow, month, is_weekend, 0.0, day_of_year]], dtype=float)
