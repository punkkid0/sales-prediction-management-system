"""MySQL access for the forecasting service."""
from __future__ import annotations

from typing import Any, Optional

import pandas as pd
import pymysql

from config import DB


def get_connection():
    return pymysql.connect(
        host=DB["host"],
        port=DB["port"],
        user=DB["user"],
        password=DB["password"],
        database=DB["database"],
        charset=DB["charset"],
        cursorclass=pymysql.cursors.DictCursor,
        autocommit=True,
    )


def fetch_sales(product_id: Optional[int] = None) -> pd.DataFrame:
    """Load sales joined with product name."""
    sql = """
        SELECT s.id, s.product_id, p.name AS product_name,
               s.quantity, s.sale_date, s.total
        FROM sales s
        JOIN products p ON p.id = s.product_id
        WHERE 1=1
    """
    params: list[Any] = []
    if product_id is not None:
        sql += " AND s.product_id = %s"
        params.append(product_id)
    sql += " ORDER BY s.sale_date ASC, s.id ASC"

    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, params)
            rows = cur.fetchall()
    return pd.DataFrame(rows)


def fetch_promotions(product_id: Optional[int] = None) -> pd.DataFrame:
    sql = """
        SELECT id, product_id, title, discount_pct, start_date, end_date, is_active
        FROM promotions
        WHERE is_active = 1
    """
    params: list[Any] = []
    if product_id is not None:
        sql += " AND (product_id IS NULL OR product_id = %s)"
        params.append(product_id)

    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, params)
            rows = cur.fetchall()
    return pd.DataFrame(rows)


def list_products_with_sales() -> list[dict]:
    sql = """
        SELECT p.id, p.name, COUNT(s.id) AS sale_rows,
               MIN(s.sale_date) AS first_sale, MAX(s.sale_date) AS last_sale,
               SUM(s.quantity) AS total_qty
        FROM products p
        LEFT JOIN sales s ON s.product_id = p.id
        WHERE p.is_active = 1
        GROUP BY p.id, p.name
        ORDER BY p.id
    """
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute(sql)
            return list(cur.fetchall())


def save_model_run(model_name: str, mae: float, rmse: float, notes: str = "") -> None:
    sql = """
        INSERT INTO model_runs (model_name, mae, rmse, notes)
        VALUES (%s, %s, %s, %s)
    """
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, (model_name, float(mae), float(rmse), notes or None))


def save_forecasts(
    product_id: int,
    forecasts: list[dict],
    model_used: str,
    mae: Optional[float],
    rmse: Optional[float],
    horizon_days: int,
) -> None:
    """Replace existing future-ish rows for this product+model, then insert new ones."""
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "DELETE FROM forecasts WHERE product_id = %s AND model_used = %s",
                (product_id, model_used),
            )
            sql = """
                INSERT INTO forecasts
                (product_id, forecast_date, predicted_qty, model_used, mae, rmse, horizon_days)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
            """
            for row in forecasts:
                cur.execute(
                    sql,
                    (
                        product_id,
                        row["date"],
                        float(row["qty"]),
                        model_used,
                        mae,
                        rmse,
                        horizon_days,
                    ),
                )
