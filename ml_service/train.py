"""
Train forecasting models for one product or all products with enough history.

Usage:
  python train.py                  # all eligible products
  python train.py --product 1      # one product
  python train.py --model both     # linear + lstm (default)
  python train.py --model linear
  python train.py --model lstm
"""
from __future__ import annotations

import argparse
import json
import sys

from config import LOOKBACK_DAYS, MIN_HISTORY_DAYS, MODELS_DIR
from db import list_products_with_sales, save_model_run
from models_lr import train_linear
from models_lstm import train_lstm
from preprocess import build_daily_series


def product_history_days(product_id: int) -> int:
    try:
        daily = build_daily_series(product_id)
        return len(daily)
    except ValueError:
        return 0


def train_one(product_id: int, which: str = "both") -> dict:
    days = product_history_days(product_id)
    if days < MIN_HISTORY_DAYS:
        return {
            "product_id": product_id,
            "skipped": True,
            "reason": f"only {days} days of history; need >= {MIN_HISTORY_DAYS}",
        }

    results = {"product_id": product_id, "skipped": False, "lookback": LOOKBACK_DAYS, "models": {}}

    if which in ("linear", "both"):
        lr = train_linear(product_id)
        results["models"]["linear_regression"] = lr
        save_model_run(
            model_name=f"linear_regression_p{product_id}",
            mae=lr["mae"],
            rmse=lr["rmse"],
            notes=f"lookback={LOOKBACK_DAYS}",
        )
        print(f"  [LR ] product {product_id}: MAE={lr['mae']} RMSE={lr['rmse']}")

    if which in ("lstm", "both"):
        lstm = train_lstm(product_id)
        results["models"]["lstm"] = lstm
        save_model_run(
            model_name=f"lstm_p{product_id}",
            mae=lstm["mae"],
            rmse=lstm["rmse"],
            notes=f"backend={lstm.get('backend')}; lookback={LOOKBACK_DAYS}",
        )
        print(
            f"  [LSTM] product {product_id}: MAE={lstm['mae']} RMSE={lstm['rmse']} "
            f"({lstm.get('backend')})"
        )

    return results


def main(argv=None):
    parser = argparse.ArgumentParser(description="Train SPMS forecasting models")
    parser.add_argument("--product", type=int, default=None, help="Product id (default: all)")
    parser.add_argument(
        "--model",
        choices=["linear", "lstm", "both"],
        default="both",
        help="Which model family to train",
    )
    args = parser.parse_args(argv)

    MODELS_DIR.mkdir(parents=True, exist_ok=True)
    print("=== SPMS model training ===")

    if args.product:
        product_ids = [args.product]
    else:
        products = list_products_with_sales()
        product_ids = [int(p["id"]) for p in products]

    all_results = []
    for pid in product_ids:
        print(f"\nProduct {pid}...")
        try:
            all_results.append(train_one(pid, args.model))
        except Exception as exc:  # keep going across products
            print(f"  ERROR product {pid}: {exc}")
            all_results.append({"product_id": pid, "error": str(exc)})

    out = MODELS_DIR / "last_train_summary.json"
    out.write_text(json.dumps(all_results, indent=2, default=str), encoding="utf-8")
    print(f"\nSummary written to {out}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
