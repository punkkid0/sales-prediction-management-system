"""
Flask Prediction API (Phase 2)

Endpoints:
  GET  /health
  GET  /products
  POST /predict   JSON: { product_id, horizon_days?, model? }
  POST /retrain   JSON: { product_id?, model?, secret }

Run:
  cd ml_service
  python app.py
"""
from __future__ import annotations

from flask import Flask, jsonify, request

from config import HORIZON_DAYS, HOST, PORT, RETRAIN_SECRET
from db import list_products_with_sales, save_forecasts
from models_lr import model_path as lr_path
from models_lr import predict_linear
from models_lstm import model_path as lstm_path
from models_lstm import predict_lstm
from train import train_one

app = Flask(__name__)


@app.get("/health")
def health():
    return jsonify(
        {
            "status": "ok",
            "service": "spms-prediction-engine",
            "phase": 2,
        }
    )


@app.get("/products")
def products():
    rows = list_products_with_sales()
    for r in rows:
        pid = int(r["id"])
        r["has_lr_model"] = lr_path(pid).exists()
        r["has_lstm_model"] = lstm_path(pid).exists()
        # jsonify-friendly
        for k, v in list(r.items()):
            if hasattr(v, "isoformat"):
                r[k] = v.isoformat()
            elif v is not None and not isinstance(v, (str, int, float, bool)):
                r[k] = str(v)
    return jsonify({"products": rows})


@app.post("/predict")
def predict():
    data = request.get_json(silent=True) or {}
    try:
        product_id = int(data.get("product_id"))
    except (TypeError, ValueError):
        return jsonify({"error": "product_id is required (integer)"}), 400

    horizon = int(data.get("horizon_days") or HORIZON_DAYS)
    horizon = max(1, min(horizon, 30))
    model = (data.get("model") or "lstm").lower()
    persist = bool(data.get("persist", True))

    try:
        if model in ("linear", "linear_regression", "lr"):
            result = predict_linear(product_id, horizon)
        elif model in ("lstm", "deep"):
            # Prefer LSTM; fall back to linear if LSTM missing
            try:
                result = predict_lstm(product_id, horizon)
            except FileNotFoundError:
                result = predict_linear(product_id, horizon)
                result["note"] = "LSTM not trained; used linear_regression fallback"
        elif model == "both":
            out = {"product_id": product_id, "horizon_days": horizon, "results": {}}
            try:
                out["results"]["linear_regression"] = predict_linear(product_id, horizon)
            except Exception as exc:
                out["results"]["linear_regression"] = {"error": str(exc)}
            try:
                out["results"]["lstm"] = predict_lstm(product_id, horizon)
            except Exception as exc:
                out["results"]["lstm"] = {"error": str(exc)}
            return jsonify(out)
        else:
            return jsonify({"error": f"Unknown model '{model}'. Use linear, lstm, or both."}), 400

        if persist and result.get("forecasts"):
            save_forecasts(
                product_id=product_id,
                forecasts=result["forecasts"],
                model_used=result["model_used"],
                mae=result.get("mae"),
                rmse=result.get("rmse"),
                horizon_days=horizon,
            )
            result["saved_to_db"] = True
        return jsonify(result)
    except FileNotFoundError as exc:
        return jsonify({"error": str(exc), "hint": "POST /retrain first"}), 404
    except ValueError as exc:
        return jsonify({"error": str(exc)}), 400
    except Exception as exc:
        return jsonify({"error": str(exc)}), 500


@app.post("/retrain")
def retrain():
    data = request.get_json(silent=True) or {}
    secret = data.get("secret") or request.headers.get("X-Retrain-Secret")
    if secret != RETRAIN_SECRET:
        return jsonify({"error": "Unauthorized (bad retrain secret)"}), 401

    model = (data.get("model") or "both").lower()
    if model in ("linear_regression", "lr"):
        model = "linear"
    if model not in ("linear", "lstm", "both"):
        return jsonify({"error": "model must be linear, lstm, or both"}), 400

    product_id = data.get("product_id")
    results = []
    if product_id is None:
        products = list_products_with_sales()
        ids = [int(p["id"]) for p in products]
    else:
        ids = [int(product_id)]

    for pid in ids:
        try:
            results.append(train_one(pid, model if model != "linear_regression" else "linear"))
        except Exception as exc:
            results.append({"product_id": pid, "error": str(exc)})

    return jsonify({"status": "ok", "trained": results})


@app.get("/")
def index():
    return jsonify(
        {
            "service": "SPMS Prediction Engine",
            "endpoints": {
                "GET /health": "liveness check",
                "GET /products": "products + model availability",
                "POST /predict": "{product_id, horizon_days?, model?}",
                "POST /retrain": "{secret, product_id?, model?}",
            },
        }
    )


if __name__ == "__main__":
    print(f"SPMS Prediction API → http://{HOST}:{PORT}", flush=True)
    # use_reloader=False so a single process stays bound (Windows service-style start)
    app.run(host=HOST, port=PORT, debug=False, use_reloader=False, threaded=True)
