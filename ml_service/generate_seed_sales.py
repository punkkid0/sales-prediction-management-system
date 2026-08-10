"""
Generate 10 months of realistic-ish daily sales into MySQL for LSTM training.

- Weekly seasonality (weekends busier for some items)
- Mild trend
- Promo spikes when a promotion is active
- Random noise

Usage:
  python generate_seed_sales.py
  python generate_seed_sales.py --days 300 --clear
"""
from __future__ import annotations

import argparse
import random
from datetime import date, timedelta

import pymysql

from config import DB


# product_id -> base daily units (before noise)
BASE_DEMAND = {
    1: 2.2,   # Rice 50kg
    2: 3.5,   # Vegetable Oil
    3: 6.0,   # Indomie
    4: 4.0,   # Peak Milk
    5: 3.0,   # Sugar
    6: 1.8,   # USB
    7: 1.2,   # Power bank
    8: 1.5,   # Mouse
    9: 2.5,   # Notebook
    10: 2.0,  # Pen
    11: 2.2,  # Detergent
    12: 3.0,  # Tissue
}

UNIT_PRICES = {}  # filled from DB


def connect():
    return pymysql.connect(
        host=DB["host"],
        port=DB["port"],
        user=DB["user"],
        password=DB["password"],
        database=DB["database"],
        charset=DB["charset"],
        autocommit=True,
        cursorclass=pymysql.cursors.DictCursor,
    )


def load_prices(cur):
    cur.execute("SELECT id, unit_price FROM products")
    return {int(r["id"]): float(r["unit_price"]) for r in cur.fetchall()}


def load_promos(cur):
    cur.execute(
        "SELECT product_id, start_date, end_date FROM promotions WHERE is_active = 1"
    )
    return cur.fetchall()


def is_promo_day(d: date, product_id: int, promos) -> bool:
    for pr in promos:
        start, end = pr["start_date"], pr["end_date"]
        if d < start or d > end:
            continue
        pid = pr["product_id"]
        if pid is None or int(pid) == product_id:
            return True
    return False


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--days", type=int, default=300, help="History length in days")
    parser.add_argument(
        "--clear",
        action="store_true",
        help="Delete existing sales before inserting generated ones",
    )
    parser.add_argument("--staff-id", type=int, default=3)
    args = parser.parse_args()

    rng = random.Random(42)
    today = date.today()
    start = today - timedelta(days=args.days)

    conn = connect()
    cur = conn.cursor()
    prices = load_prices(cur)
    promos = load_promos(cur)

    if args.clear:
        cur.execute("DELETE FROM forecasts")
        cur.execute("DELETE FROM sales")
        print("Cleared sales + forecasts.")

    # Keep a few real recent sales if not clearing? If clear, rebuild all.
    rows = []
    for pid, base in BASE_DEMAND.items():
        price = prices.get(pid, 1000.0)
        for offset in range(args.days + 1):
            d = start + timedelta(days=offset)
            # seasonality
            dow = d.weekday()  # 0 Mon
            weekend_boost = 1.35 if dow >= 5 else 1.0
            # monthly-ish wave
            month_wave = 1.0 + 0.15 * ((d.month % 4) - 1.5) / 1.5
            # slow growth trend
            trend = 1.0 + 0.0008 * offset
            promo = 1.45 if is_promo_day(d, pid, promos) else 1.0

            mean = base * weekend_boost * month_wave * trend * promo
            # Poisson-like discrete demand
            qty = max(0, int(rng.gauss(mean, max(0.6, mean * 0.35))))
            # some days zero sales
            if rng.random() < 0.08:
                qty = 0
            if qty <= 0:
                continue

            # 1–2 transactions that day sometimes
            chunks = 1 if qty < 3 or rng.random() < 0.7 else 2
            remaining = qty
            for c in range(chunks):
                q = remaining if c == chunks - 1 else max(1, remaining // 2)
                remaining -= q
                if q <= 0:
                    continue
                customer_id = rng.choice([1, 2, 3, 4, 5, None])
                staff_id = rng.choice([3, 4])
                total = round(price * q, 2)
                rows.append((pid, customer_id, staff_id, q, price, total, d.isoformat()))

    sql = """
        INSERT INTO sales (product_id, customer_id, staff_id, quantity, unit_price, total, sale_date)
        VALUES (%s, %s, %s, %s, %s, %s, %s)
    """
    # insert in batches
    batch = 500
    for i in range(0, len(rows), batch):
        cur.executemany(sql, rows[i : i + batch])

    # bump stock so POS still works (don't leave zeros)
    cur.execute(
        """
        UPDATE products
        SET stock_qty = GREATEST(stock_qty, reorder_level + 50)
        """
    )

    cur.execute("SELECT COUNT(*) AS c FROM sales")
    count = cur.fetchone()["c"]
    print(f"Inserted generated sales. Total sales rows now: {count}")
    print(f"Date range: {start} → {today}")
    cur.close()
    conn.close()


if __name__ == "__main__":
    main()
