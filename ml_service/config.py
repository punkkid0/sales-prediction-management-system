"""ML service configuration."""
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent
MODELS_DIR = BASE_DIR / "models"
DATA_DIR = BASE_DIR / "data"

# MySQL (same as PHP web/config/database.php)
DB = {
    "host": "127.0.0.1",
    "port": 3306,
    "user": "root",
    "password": "",
    "database": "sales_prediction_db",
    "charset": "utf8mb4",
}

# Forecasting defaults
LOOKBACK_DAYS = 30          # past days fed into models
HORIZON_DAYS = 7            # days to predict ahead
MIN_HISTORY_DAYS = 60       # refuse training if product has fewer days
TEST_RATIO = 0.2            # last 20% of series held out (chronological)

# Security for /retrain (must match web/config/app.php ml_retrain_secret)
RETRAIN_SECRET = "change-me-spms-secret"

# Flask
HOST = "127.0.0.1"
PORT = 5000
DEBUG = False
