"""Turn raw sales into a clean daily series with calendar + promo features."""
from __future__ import annotations

from typing import Optional

import numpy as np
import pandas as pd

from db import fetch_promotions, fetch_sales


FEATURE_COLS = [
    "qty",
    "day_of_week",
    "month",
    "is_weekend",
    "is_promo",
    "day_of_year",
]


def _promo_mask(dates: pd.DatetimeIndex, promos: pd.DataFrame, product_id: int) -> np.ndarray:
    """1 if any active promotion covers this product on that date."""
    flags = np.zeros(len(dates), dtype=float)
    if promos is None or promos.empty:
        return flags

    for _, pr in promos.iterrows():
        pid = pr.get("product_id")
        # NULL product_id in DB means store-wide
        if pid is not None and not (isinstance(pid, float) and np.isnan(pid)):
            if int(pid) != int(product_id):
                continue
        start = pd.to_datetime(pr["start_date"])
        end = pd.to_datetime(pr["end_date"])
        mask = np.asarray((dates >= start) & (dates <= end), dtype=bool)
        flags[mask] = 1.0
    return flags


def build_daily_series(product_id: int) -> pd.DataFrame:
    """
    Aggregate sales to one row per day.
    Missing days get qty=0 (shop closed / no sales still matters for forecasting).
    Adds external-factor style features: day_of_week, month, weekend, is_promo.
    """
    sales = fetch_sales(product_id)
    if sales.empty:
        raise ValueError(f"No sales found for product_id={product_id}")

    sales["sale_date"] = pd.to_datetime(sales["sale_date"])
    daily = (
        sales.groupby("sale_date", as_index=False)["quantity"]
        .sum()
        .rename(columns={"quantity": "qty"})
    )
    daily = daily.sort_values("sale_date")

    start = daily["sale_date"].min()
    end = daily["sale_date"].max()
    full_idx = pd.date_range(start, end, freq="D")
    daily = daily.set_index("sale_date").reindex(full_idx, fill_value=0.0)
    daily.index.name = "sale_date"
    daily = daily.reset_index()

    promos = fetch_promotions(product_id)
    dates = pd.DatetimeIndex(daily["sale_date"])

    daily["day_of_week"] = dates.dayofweek.astype(float)          # 0=Mon … 6=Sun
    daily["month"] = dates.month.astype(float)
    daily["is_weekend"] = (dates.dayofweek >= 5).astype(float)
    daily["day_of_year"] = dates.dayofyear.astype(float)
    daily["is_promo"] = _promo_mask(dates, promos, product_id)
    daily["qty"] = daily["qty"].astype(float)
    daily["product_id"] = product_id
    return daily


def make_supervised(
    daily: pd.DataFrame,
    lookback: int,
    horizon: int = 1,
) -> tuple[np.ndarray, np.ndarray, list[pd.Timestamp]]:
    """
    Sliding windows:
      X[t] = features for days [t-lookback … t-1]
      y[t] = qty for day t   (horizon=1)
    For multi-day horizon training we still predict 1-step and roll forward at inference.
    """
    values = daily[FEATURE_COLS].to_numpy(dtype=float)
    qty = daily["qty"].to_numpy(dtype=float)
    dates = list(pd.to_datetime(daily["sale_date"]))

    X_list, y_list, y_dates = [], [], []
    for i in range(lookback, len(daily) - horizon + 1):
        window = values[i - lookback : i]
        X_list.append(window.reshape(-1))  # flatten for linear / sklearn models
        y_list.append(qty[i + horizon - 1] if horizon == 1 else qty[i : i + horizon])
        y_dates.append(dates[i + horizon - 1] if horizon == 1 else dates[i])

    X = np.asarray(X_list, dtype=float)
    y = np.asarray(y_list, dtype=float)
    return X, y, y_dates


def make_lstm_sequences(
    daily: pd.DataFrame,
    lookback: int,
) -> tuple[np.ndarray, np.ndarray]:
    """3D sequences for LSTM: (samples, lookback, n_features) → next-day qty."""
    values = daily[FEATURE_COLS].to_numpy(dtype=float)
    qty = daily["qty"].to_numpy(dtype=float)

    Xs, ys = [], []
    for i in range(lookback, len(daily)):
        Xs.append(values[i - lookback : i])
        ys.append(qty[i])
    return np.asarray(Xs, dtype=float), np.asarray(ys, dtype=float)


def chronological_split(
    X: np.ndarray, y: np.ndarray, test_ratio: float = 0.2
) -> tuple[np.ndarray, np.ndarray, np.ndarray, np.ndarray]:
    """Never shuffle time series — last portion is the test set."""
    n = len(X)
    if n < 5:
        raise ValueError("Not enough samples after windowing to train/test.")
    cut = max(1, int(n * (1 - test_ratio)))
    if cut >= n:
        cut = n - 1
    return X[:cut], X[cut:], y[:cut], y[cut:]


def metrics(y_true: np.ndarray, y_pred: np.ndarray) -> dict[str, float]:
    y_true = np.asarray(y_true, dtype=float).ravel()
    y_pred = np.asarray(y_pred, dtype=float).ravel()
    mae = float(np.mean(np.abs(y_true - y_pred)))
    rmse = float(np.sqrt(np.mean((y_true - y_pred) ** 2)))
    return {"mae": round(mae, 4), "rmse": round(rmse, 4)}
