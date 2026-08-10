"""Generate SPMS Machine Learning Study Guide PDF."""
from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_JUSTIFY, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import cm, mm
from reportlab.platypus import (
    KeepTogether,
    ListFlowable,
    ListItem,
    PageBreak,
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    Table,
    TableStyle,
)

OUT = Path(__file__).resolve().parent / "SPMS_ML_Study_Guide_Linear_Regression_and_LSTM.pdf"

PRIMARY = colors.HexColor("#1F4E79")
ACCENT = colors.HexColor("#2E75B6")
LIGHT = colors.HexColor("#EEF3F8")
SOFT = colors.HexColor("#F7F9FC")
BORDER = colors.HexColor("#CCCCCC")
MUTED = colors.HexColor("#555555")


def styles():
    base = getSampleStyleSheet()
    s = {
        "cover_title": ParagraphStyle(
            "cover_title",
            parent=base["Title"],
            fontName="Helvetica-Bold",
            fontSize=22,
            textColor=PRIMARY,
            alignment=TA_CENTER,
            spaceAfter=8,
            leading=28,
        ),
        "cover_sub": ParagraphStyle(
            "cover_sub",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=12,
            textColor=ACCENT,
            alignment=TA_CENTER,
            spaceAfter=6,
            leading=16,
        ),
        "meta": ParagraphStyle(
            "meta",
            parent=base["Normal"],
            fontName="Helvetica-Oblique",
            fontSize=9,
            textColor=MUTED,
            alignment=TA_CENTER,
            spaceAfter=12,
        ),
        "h1": ParagraphStyle(
            "h1",
            parent=base["Heading1"],
            fontName="Helvetica-Bold",
            fontSize=14,
            textColor=PRIMARY,
            spaceBefore=14,
            spaceAfter=8,
            leading=18,
        ),
        "h2": ParagraphStyle(
            "h2",
            parent=base["Heading2"],
            fontName="Helvetica-Bold",
            fontSize=12,
            textColor=ACCENT,
            spaceBefore=10,
            spaceAfter=6,
            leading=15,
        ),
        "body": ParagraphStyle(
            "body",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=10,
            textColor=colors.black,
            alignment=TA_JUSTIFY,
            spaceAfter=6,
            leading=14,
        ),
        "bullet": ParagraphStyle(
            "bullet",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=10,
            leading=13,
            leftIndent=4,
        ),
        "note": ParagraphStyle(
            "note",
            parent=base["Normal"],
            fontName="Helvetica-Oblique",
            fontSize=9.5,
            textColor=MUTED,
            leading=13,
            spaceAfter=6,
        ),
        "formula": ParagraphStyle(
            "formula",
            parent=base["Normal"],
            fontName="Courier",
            fontSize=10,
            alignment=TA_CENTER,
            textColor=PRIMARY,
            spaceBefore=4,
            spaceAfter=8,
            leading=13,
        ),
        "footer": ParagraphStyle(
            "footer",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=8,
            textColor=MUTED,
            alignment=TA_CENTER,
        ),
        "cell": ParagraphStyle(
            "cell",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=8.5,
            leading=11,
        ),
        "cell_h": ParagraphStyle(
            "cell_h",
            parent=base["Normal"],
            fontName="Helvetica-Bold",
            fontSize=8.5,
            textColor=colors.white,
            leading=11,
        ),
        "quiz": ParagraphStyle(
            "quiz",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=10,
            leading=13,
            spaceAfter=4,
        ),
    }
    return s


def p(text, style):
    return Paragraph(text, style)


def bullets(items, style):
    return ListFlowable(
        [ListItem(Paragraph(i, style), leftIndent=12, value="bullet") for i in items],
        bulletType="bullet",
        start="•",
        leftIndent=15,
        bulletFontName="Helvetica",
        bulletFontSize=10,
    )


def make_table(headers, rows, col_widths, s):
    data = [[p(h, s["cell_h"]) for h in headers]]
    for row in rows:
        data.append([p(c, s["cell"]) for c in row])
    t = Table(data, colWidths=col_widths, repeatRows=1)
    t.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), PRIMARY),
                ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
                ("BACKGROUND", (0, 1), (-1, -1), SOFT),
                ("GRID", (0, 0), (-1, -1), 0.4, BORDER),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("LEFTPADDING", (0, 0), (-1, -1), 5),
                ("RIGHTPADDING", (0, 0), (-1, -1), 5),
                ("TOPPADDING", (0, 0), (-1, -1), 4),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
                ("ROWBACKGROUNDS", (0, 1), (-1, -1), [SOFT, colors.white]),
            ]
        )
    )
    return t


def callout(title, body_text, s):
    inner = [
        p(f"<b>{title}</b>", s["body"]),
        p(body_text, s["note"]),
    ]
    t = Table([[inner]], colWidths=[16.5 * cm])
    t.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, -1), LIGHT),
                ("BOX", (0, 0), (-1, -1), 1, ACCENT),
                ("LEFTPADDING", (0, 0), (-1, -1), 8),
                ("RIGHTPADDING", (0, 0), (-1, -1), 8),
                ("TOPPADDING", (0, 0), (-1, -1), 6),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
            ]
        )
    )
    return t


def footer(canvas, doc):
    canvas.saveState()
    canvas.setStrokeColor(BORDER)
    canvas.setLineWidth(0.5)
    canvas.line(2 * cm, 1.4 * cm, A4[0] - 2 * cm, 1.4 * cm)
    canvas.setFont("Helvetica", 8)
    canvas.setFillColor(MUTED)
    canvas.drawString(2 * cm, 0.9 * cm, "SPMS Study Guide · Linear Regression & LSTM")
    canvas.drawRightString(A4[0] - 2 * cm, 0.9 * cm, f"Page {doc.page}")
    canvas.restoreState()


def build():
    s = styles()
    story = []

    # Cover
    story.append(Spacer(1, 2.2 * cm))
    story.append(p("Sales Prediction Management System", s["cover_title"]))
    story.append(p("Machine Learning Study Guide", s["cover_sub"]))
    story.append(p("Linear Regression · LSTM · Train vs Predict · Metrics · Your Project", s["cover_sub"]))
    story.append(Spacer(1, 0.4 * cm))
    story.append(
        p(
            "A plain-English learning note for the thesis project.<br/>"
            "Read slowly. Practice the quizzes. Connect each idea to the code files listed.",
            s["meta"],
        )
    )
    story.append(Spacer(1, 0.6 * cm))
    story.append(
        callout(
            "How to use this PDF",
            "1) Read sections 1–3 for the big picture. 2) Study Linear Regression, then LSTM. "
            "3) Learn MAE/RMSE with the shop example. 4) Map ideas to your project files. "
            "5) Try the self-check questions without looking at answers first.",
            s,
        )
    )
    story.append(PageBreak())

    # TOC-ish overview
    story.append(p("1. What is prediction?", s["h1"]))
    story.append(
        p(
            "Prediction (also called <b>forecasting</b> when talking about the future) means: "
            "looking at <b>what already happened</b> and estimating <b>what might happen next</b>.",
            s["body"],
        )
    )
    story.append(
        p(
            "In this project, the question is: <i>For each product, about how many units will we sell "
            "in the next few days?</i>",
            s["body"],
        )
    )
    story.append(
        p(
            "It is <b>not magic</b> and <b>not a guarantee</b>. It is a smart estimate the computer "
            "learns from patterns in historical sales.",
            s["body"],
        )
    )
    story.append(p("Everyday shop example", s["h2"]))
    story.append(
        bullets(
            [
                "Indomie often sells more on weekends.",
                "When there is a promo, sales jump.",
                "The model learns: weekend + promo → higher sales.",
                "Then it says: next 7 days, expect about X cartons per day.",
            ],
            s["bullet"],
        )
    )
    story.append(Spacer(1, 0.25 * cm))
    story.append(
        callout(
            "Key words",
            "<b>History</b> = past sales in MySQL. "
            "<b>Pattern</b> = weekend/month/promo/trend. "
            "<b>Forecast</b> = estimated future quantities. "
            "<b>Model</b> = the learned rule/brain that maps patterns → forecast.",
            s,
        )
    )

    story.append(p("2. The prediction pipeline (factory line)", s["h1"]))
    story.append(
        p(
            "Your system does not jump straight to AI. It follows a clear pipeline:",
            s["body"],
        )
    )
    story.append(
        p(
            "MySQL sales  →  clean daily table  →  add features  →  train models  →  save files  →  API predicts",
            s["formula"],
        )
    )
    story.append(
        make_table(
            ["Step", "What happens", "Why it matters"],
            [
                [
                    "1. Collect",
                    "Read sales from the database",
                    "No data = nothing to learn",
                ],
                [
                    "2. Clean",
                    "One quantity per product per day; missing days = 0",
                    "Models need a regular timeline",
                ],
                [
                    "3. Features",
                    "Add day-of-week, month, weekend, promo flags",
                    "Gives the model useful clues",
                ],
                [
                    "4. Train",
                    "Learn Linear Regression + LSTM",
                    "Creates the 'brain' files",
                ],
                [
                    "5. Evaluate",
                    "Compute MAE and RMSE on held-out days",
                    "Shows how wrong the guesses are",
                ],
                [
                    "6. Predict",
                    "Forecast next N days via Flask API",
                    "Useful output for managers",
                ],
            ],
            [2.2 * cm, 7.0 * cm, 7.3 * cm],
            s,
        )
    )

    story.append(p("3. Features (the clues)", s["h1"]))
    story.append(
        p(
            "A model does not only see 'quantity sold'. We also give it <b>features</b> — extra clues "
            "that help explain why sales go up or down.",
            s["body"],
        )
    )
    story.append(
        make_table(
            ["Feature", "Meaning", "Example"],
            [
                ["qty (past)", "Recent sales amounts", "Last 30 days of units sold"],
                ["day_of_week", "Mon=0 … Sun=6", "Weekends often busier"],
                ["month", "1–12", "Some months are peak season"],
                ["is_weekend", "1 if Sat/Sun else 0", "Simple weekend flag"],
                ["is_promo", "1 if promotion active", "From Promotions table"],
                ["day_of_year", "1–365/366", "Fine-grained calendar position"],
            ],
            [3.2 * cm, 6.5 * cm, 6.8 * cm],
            s,
        )
    )
    story.append(Spacer(1, 0.2 * cm))
    story.append(
        p(
            "In code: <b>ml_service/preprocess.py</b> builds the daily series and these features. "
            "This also supports your thesis objective about external factors (promo + seasonality).",
            s["note"],
        )
    )

    story.append(PageBreak())

    # Linear Regression deep dive
    story.append(p("4. Linear Regression — the simple student", s["h1"]))
    story.append(p("4.1 What it is", s["h2"]))
    story.append(
        p(
            "<b>Linear Regression</b> is a classical statistics / machine-learning method. "
            "It finds a relationship that looks like a weighted sum of clues:",
            s["body"],
        )
    )
    story.append(
        p(
            "predicted_sales  ≈  a  +  b1·x1  +  b2·x2  +  b3·x3  +  …",
            s["formula"],
        )
    )
    story.append(
        bullets(
            [
                "<b>y</b> (predicted_sales) = the answer we want",
                "<b>x1, x2, …</b> = features/clues (past sales window, weekend, promo…)",
                "<b>a</b> = intercept (starting baseline)",
                "<b>b1, b2, …</b> = weights the model learns (importance of each clue)",
            ],
            s["bullet"],
        )
    )
    story.append(Spacer(1, 0.15 * cm))
    story.append(p("4.2 Everyday picture", s["h2"]))
    story.append(
        p(
            "Imagine plotting points for 'was it weekend?' vs 'sales', or 'promo?' vs 'sales', "
            "and drawing the <b>best straight-line style rule</b> through the cloud of points. "
            "That rule is the model.",
            s["body"],
        )
    )
    story.append(
        p(
            "Example intuition (not exact numbers):",
            s["body"],
        )
    )
    story.append(
        bullets(
            [
                "Base daily sales ≈ 4 units",
                "If weekend, add about +2",
                "If promo, add about +3",
                "If recent days were high, add a bit more",
            ],
            s["bullet"],
        )
    )
    story.append(Spacer(1, 0.15 * cm))
    story.append(p("4.3 Why we use it in SPMS", s["h2"]))
    story.append(
        bullets(
            [
                "Easy to explain in a thesis defense",
                "Trains very fast",
                "Good <b>baseline</b> (simple method first, then compare with LSTM)",
                "Matches your write-up mention of regression models",
            ],
            s["bullet"],
        )
    )
    story.append(p("4.4 Strengths and weaknesses", s["h2"]))
    story.append(
        make_table(
            ["Strengths", "Weaknesses"],
            [
                [
                    "Simple, transparent, fast",
                    "Struggles with complex wave-like patterns over long time",
                ],
                [
                    "Great teaching / comparison baseline",
                    "Assumes roughly linear combinations of features",
                ],
                [
                    "Works with fewer data points than deep nets",
                    "May underfit rich seasonal dynamics",
                ],
            ],
            [8.2 * cm, 8.3 * cm],
            s,
        )
    )
    story.append(Spacer(1, 0.2 * cm))
    story.append(p("4.5 How YOUR project uses Linear Regression", s["h2"]))
    story.append(
        bullets(
            [
                "File: <b>ml_service/models_lr.py</b>",
                "Looks at a flattened window of the last ~30 days of features",
                "Trains with scikit-learn <b>LinearRegression</b>",
                "Saves model to <b>models/lr_product_X.joblib</b>",
                "Predict rolls one day at a time for the next 7 days (horizon)",
            ],
            s["bullet"],
        )
    )
    story.append(
        callout(
            "Train vs Predict (Linear Regression)",
            "<b>Train:</b> show many past windows + actual next-day sales; learn weights a,b. "
            "<b>Predict:</b> take the newest window and apply the learned weights to estimate future days.",
            s,
        )
    )

    story.append(p("4.6 Mini math you should know", s["h2"]))
    story.append(
        p(
            "During training, the model chooses weights so that the average error between "
            "predicted y and true y becomes small. A common training idea is minimizing squared error:",
            s["body"],
        )
    )
    story.append(
        p(
            "error = (true_sales − predicted_sales)²   → make this small on average",
            s["formula"],
        )
    )
    story.append(
        p(
            "You do not need to derive proofs for the project, but you should be able to say: "
            "<i>Linear Regression fits a weighted sum of features to minimize prediction error.</i>",
            s["note"],
        )
    )

    story.append(PageBreak())

    # LSTM
    story.append(p("5. LSTM — the memory student", s["h1"]))
    story.append(p("5.1 What the name means", s["h2"]))
    story.append(
        p(
            "<b>LSTM</b> = <b>Long Short-Term Memory</b>. "
            "It is a type of <b>neural network</b> (deep learning model) designed for sequences — "
            "data that comes in order over time.",
            s["body"],
        )
    )
    story.append(
        p(
            "Sales is a sequence: Day1 → Day2 → Day3 → …  "
            "Shuffling those days would destroy the story. LSTM reads the story in order.",
            s["body"],
        )
    )

    story.append(p("5.2 Neural network in one breath", s["h2"]))
    story.append(
        p(
            "A neural network is many small math units (neurons) arranged in layers. "
            "Each connection has a weight. Training adjusts weights so outputs get closer to truth. "
            "Deep learning just means networks with enough structure to learn complex patterns.",
            s["body"],
        )
    )

    story.append(p("5.3 Why 'memory'?", s["h2"]))
    story.append(
        p(
            "Older simple networks forget long context quickly. LSTM cells use internal gates "
            "(forget / input / output style mechanisms) so the model can:",
            s["body"],
        )
    )
    story.append(
        bullets(
            [
                "Keep useful recent information (short-term)",
                "Also carry important longer patterns (long-term-ish memory)",
                "Ignore noise that is not helpful",
            ],
            s["bullet"],
        )
    )
    story.append(
        p(
            "You do not need to implement gates by hand for the thesis oral. "
            "You should say: <i>LSTM is a sequence model with memory, good for time-series sales.</i>",
            s["note"],
        )
    )

    story.append(p("5.4 Everyday picture", s["h2"]))
    story.append(
        p(
            "Linear Regression is like a checklist of rules. "
            "LSTM is more like a staff who watched the counter for weeks and says:",
            s["body"],
        )
    )
    story.append(
        p(
            "“Sales climbed for 10 days, last weekend was high, promo just ended, "
            "so the next few days may stay medium-high then cool down.”",
            s["formula"],
        )
    )

    story.append(p("5.5 Sliding window (very important)", s["h2"]))
    story.append(
        p(
            "In your project, lookback is about <b>30 days</b> and horizon about <b>7 days</b>:",
            s["body"],
        )
    )
    story.append(
        bullets(
            [
                "Input: features for the last 30 days",
                "Output: estimate for the next day (then roll forward for 7 days)",
                "This is called a sliding / rolling window approach",
            ],
            s["bullet"],
        )
    )
    story.append(
        p(
            "Example: use days 1–30 to guess day 31; then use days 2–31 to guess day 32; and so on.",
            s["body"],
        )
    )

    story.append(p("5.6 Strengths and weaknesses", s["h2"]))
    story.append(
        make_table(
            ["Strengths", "Weaknesses"],
            [
                [
                    "Good at time patterns and sequences",
                    "Needs more history than simple models",
                ],
                [
                    "Can capture non-linear ups and downs",
                    "Harder to explain than a straight formula",
                ],
                [
                    "Matches modern ML literature / your write-up",
                    "Can overfit or fail if data quality is poor",
                ],
            ],
            [8.2 * cm, 8.3 * cm],
            s,
        )
    )

    story.append(p("5.7 How YOUR project uses LSTM", s["h2"]))
    story.append(
        bullets(
            [
                "File: <b>ml_service/models_lstm.py</b>",
                "Uses <b>PyTorch</b> LSTM layers when available",
                "Fallback NumPy LSTM exists if Torch is missing",
                "Saves to <b>models/lstm_product_X.joblib</b>",
                "API default model is often LSTM, with linear as baseline comparison",
            ],
            s["bullet"],
        )
    )
    story.append(
        callout(
            "Defense sentence you can memorize",
            "“We use Linear Regression as a transparent baseline and LSTM as a sequence-aware deep model. "
            "Both are trained on chronological sales with calendar and promotion features, and compared using MAE and RMSE.”",
            s,
        )
    )

    story.append(PageBreak())

    # Compare
    story.append(p("6. Linear Regression vs LSTM (side by side)", s["h1"]))
    story.append(
        make_table(
            ["Point", "Linear Regression", "LSTM"],
            [
                ["Type", "Classical ML / statistics", "Deep learning neural net"],
                ["Thinks in", "Weighted sum of clues", "Sequence + memory over days"],
                ["Speed", "Very fast", "Slower to train"],
                ["Explainability", "High", "Medium"],
                ["Best for", "Baseline, simple trends", "Wavy multi-day patterns"],
                ["Your file", "models_lr.py", "models_lstm.py"],
                ["Your role", "Comparison baseline", "Main advanced model"],
            ],
            [3.2 * cm, 6.6 * cm, 6.7 * cm],
            s,
        )
    )

    story.append(p("7. Train vs Predict (both models)", s["h1"]))
    story.append(p("7.1 Train = learning time", s["h2"]))
    story.append(
        bullets(
            [
                "Show the model many past examples",
                "Model adjusts internal numbers to reduce error",
                "We hold out the latest part of the timeline as a test set (no shuffling)",
                "Command: <b>python train.py</b>",
                "Writes metrics into <b>model_runs</b> table and saves .joblib files",
            ],
            s["bullet"],
        )
    )
    story.append(p("7.2 Predict = exam time", s["h2"]))
    story.append(
        bullets(
            [
                "Ask a new question: product_id + next 7 days",
                "Load the saved model file",
                "Return forecast dates + quantities as JSON",
                "Endpoint: <b>POST /predict</b> on Flask (port 5000)",
            ],
            s["bullet"],
        )
    )
    story.append(
        p(
            "Never train and test on randomly shuffled time-series rows. "
            "Time has direction: train on earlier days, test on later days.",
            s["note"],
        )
    )

    story.append(p("8. MAE and RMSE (how we score models)", s["h1"]))
    story.append(
        p(
            "After predicting, we compare guesses to real sales on the test days.",
            s["body"],
        )
    )
    story.append(
        make_table(
            ["Metric", "Plain meaning", "Why useful"],
            [
                [
                    "MAE",
                    "Mean Absolute Error = average of |true − predicted|",
                    "Easy: 'wrong by about N units on average'",
                ],
                [
                    "RMSE",
                    "Root Mean Square Error = square errors, average, square-root",
                    "Punishes large mistakes more than MAE",
                ],
            ],
            [2.5 * cm, 8.0 * cm, 6.0 * cm],
            s,
        )
    )
    story.append(Spacer(1, 0.2 * cm))
    story.append(p("Tiny numerical example", s["h2"]))
    story.append(
        p(
            "True sales for 3 days: 10, 8, 12. Predictions: 9, 10, 11.",
            s["body"],
        )
    )
    story.append(
        bullets(
            [
                "Absolute errors: |10−9|=1, |8−10|=2, |12−11|=1",
                "MAE = (1+2+1)/3 = 1.33 units",
                "Squared errors: 1, 4, 1 → mean 2 → RMSE = √2 ≈ 1.41",
            ],
            s["bullet"],
        )
    )
    story.append(
        p(
            "<b>Lower MAE/RMSE is better.</b> If LSTM MAE is 1.9 and Linear MAE is 2.3 on the same product, "
            "LSTM was closer on average for that test period.",
            s["body"],
        )
    )

    story.append(PageBreak())

    # Project mapping
    story.append(p("9. How this maps to YOUR codebase", s["h1"]))
    story.append(
        make_table(
            ["Idea", "Where in the project"],
            [
                ["Shop UI (sell, stock, reports)", "web/ modules (Phase 1 PHP)"],
                ["Prediction Engine", "ml_service/ Flask app (Phase 2)"],
                ["Cleaning + features", "ml_service/preprocess.py"],
                ["Linear Regression", "ml_service/models_lr.py"],
                ["LSTM", "ml_service/models_lstm.py"],
                ["Training CLI", "ml_service/train.py"],
                ["API /health /predict /retrain", "ml_service/app.py"],
                ["Long demo history", "ml_service/generate_seed_sales.py"],
                ["Saved brains", "ml_service/models/*.joblib"],
                ["Stored forecasts / metrics", "MySQL tables forecasts, model_runs"],
            ],
            [6.5 * cm, 10.0 * cm],
            s,
        )
    )
    story.append(Spacer(1, 0.25 * cm))
    story.append(
        p(
            "Remember the split: <b>PHP = shop for humans</b>, <b>Python = brain for forecasts</b>, "
            "<b>MySQL = shared notebook</b>.",
            s["body"],
        )
    )

    story.append(p("10. Extra concepts worth learning", s["h1"]))
    story.append(p("10.1 Time series", s["h2"]))
    story.append(
        p(
            "A time series is data recorded over time at regular intervals (daily sales). "
            "Special rule: respect order; do not randomly shuffle before train/test split.",
            s["body"],
        )
    )
    story.append(p("10.2 Overfitting vs underfitting", s["h2"]))
    story.append(
        bullets(
            [
                "<b>Underfitting</b>: model too simple; misses real patterns (sometimes linear on complex waves)",
                "<b>Overfitting</b>: model memorizes training noise; looks great on train, weak on new days",
                "Holding out a chronological test set helps detect this",
            ],
            s["bullet"],
        )
    )
    story.append(p("10.3 Baseline model", s["h2"]))
    story.append(
        p(
            "A baseline is a simple method you compare against. "
            "If a complex model cannot beat the baseline, the complexity may not be worth it. "
            "That is why Linear Regression stays in the project even when LSTM exists.",
            s["body"],
        )
    )
    story.append(p("10.4 Horizon and lookback", s["h2"]))
    story.append(
        bullets(
            [
                "<b>Lookback</b>: how many past days the model reads (e.g. 30)",
                "<b>Horizon</b>: how many future days you ask for (e.g. 7)",
                "Longer horizon is usually harder and less accurate",
            ],
            s["bullet"],
        )
    )
    story.append(p("10.5 External factors", s["h2"]))
    story.append(
        p(
            "Sales are not only 'yesterday’s qty'. Promotions, seasonality, and weekends are external-ish signals. "
            "Your system includes calendar features and an is_promo flag from the promotions table — "
            "this supports Objective 2 in the write-up without needing live internet APIs.",
            s["body"],
        )
    )
    story.append(p("10.6 Honest limits (say this in defense)", s["h2"]))
    story.append(
        bullets(
            [
                "Forecasts depend on history quality and length",
                "Sudden shocks (pandemic, strike) may not be predictable from old data",
                "Future promos help only if entered into the system",
                "A forecast supports decisions; it does not replace manager judgment",
            ],
            s["bullet"],
        )
    )

    story.append(PageBreak())

    # Glossary
    story.append(p("11. Glossary (quick revise before exams)", s["h1"]))
    story.append(
        make_table(
            ["Term", "Simple meaning"],
            [
                ["Feature", "A clue/input the model uses"],
                ["Label / target", "The true answer during training (actual sales)"],
                ["Model", "Learned rule or network that maps features → prediction"],
                ["Train", "Learn from historical examples"],
                ["Predict / infer", "Use a trained model on new inputs"],
                ["Baseline", "Simple comparison model"],
                ["LSTM", "Sequence neural network with memory"],
                ["Linear Regression", "Weighted-sum formula model"],
                ["MAE / RMSE", "Error scores; lower is better"],
                ["Chronological split", "Train on earlier time, test on later time"],
                ["API", "Service endpoint other programs call (Flask)"],
                ["JSON", "Data format the API returns"],
            ],
            [4.5 * cm, 12.0 * cm],
            s,
        )
    )

    # Self check
    story.append(p("12. Self-check quiz (try before looking at answers)", s["h1"]))
    story.append(p("Questions", s["h2"]))
    qs = [
        "1. In one sentence, what does sales prediction mean in this project?",
        "2. Why do we keep Linear Regression if we already have LSTM?",
        "3. What is a feature? Name three features used in SPMS.",
        "4. Why must we not shuffle time-series rows before train/test split?",
        "5. What does MAE tell a manager in plain English?",
        "6. What is lookback vs horizon?",
        "7. Which project file cleans sales into a daily series?",
        "8. Which side is the 'shop' and which side is the 'brain'?",
        "9. Give one strength and one weakness of LSTM.",
        "10. Why can forecasts still be wrong even with a good model?",
    ]
    for q in qs:
        story.append(p(q, s["quiz"]))

    story.append(Spacer(1, 0.3 * cm))
    story.append(p("Short answers", s["h2"]))
    ans = [
        "1. Estimating future product sales quantities from historical patterns and related clues.",
        "2. It is a simple, explainable baseline for comparison and thesis transparency.",
        "3. A clue/input. Examples: past qty, day_of_week, is_promo (also month, weekend).",
        "4. Time has order; random shuffle leaks future information into training.",
        "5. On average, predictions are off by about MAE units.",
        "6. Lookback = past days read; horizon = future days requested.",
        "7. ml_service/preprocess.py",
        "8. Shop = PHP web app; brain = Python Flask ml_service.",
        "9. Strength: sequence memory / complex patterns. Weakness: needs more data; harder to explain.",
        "10. Markets change; data can be incomplete; unexpected events are not in history.",
    ]
    for a in ans:
        story.append(p(a, s["note"]))

    story.append(p("13. Practice plan (learn on your own)", s["h1"]))
    story.append(
        bullets(
            [
                "Day 1: Re-read sections 1–4. Explain Linear Regression to a friend in 60 seconds.",
                "Day 2: Re-read section 5. Draw a 30-day window → next-day arrow on paper.",
                "Day 3: Compute a tiny MAE by hand with 4 made-up days.",
                "Day 4: Open preprocess.py and models_lr.py; match comments to this PDF.",
                "Day 5: Open models_lstm.py and app.py; list the API endpoints from memory.",
                "Day 6: Run /health and one /predict (if services are up); interpret the JSON fields.",
                "Day 7: Write a half-page 'Methodology' paragraph using the defense sentence in §5.7.",
            ],
            s["bullet"],
        )
    )

    story.append(Spacer(1, 0.35 * cm))
    story.append(
        callout(
            "Commands cheat-sheet",
            "Generate history: python generate_seed_sales.py --clear --days 300<br/>"
            "Train: python train.py<br/>"
            "Start API: python app.py<br/>"
            "Health: http://127.0.0.1:5000/health<br/>"
            "Shop UI: http://localhost/spms/",
            s,
        )
    )

    story.append(Spacer(1, 0.5 * cm))
    story.append(
        p(
            "End of study guide. Revisit before implementation demos and oral defense. "
            "Phase 3 will connect these forecasts to the manager dashboard charts.",
            s["meta"],
        )
    )

    doc = SimpleDocTemplate(
        str(OUT),
        pagesize=A4,
        leftMargin=2 * cm,
        rightMargin=2 * cm,
        topMargin=1.6 * cm,
        bottomMargin=1.8 * cm,
        title="SPMS ML Study Guide — Linear Regression and LSTM",
        author="Sales Prediction Management System",
        subject="Learning notes for forecasting models used in the project",
    )
    doc.build(story, onFirstPage=footer, onLaterPages=footer)
    print(f"Wrote {OUT}")


if __name__ == "__main__":
    build()
