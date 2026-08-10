"""Generate Forecasts page study guide PDF for SPMS."""
from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_JUSTIFY
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import cm
from reportlab.platypus import (
    ListFlowable,
    ListItem,
    PageBreak,
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    Table,
    TableStyle,
)

OUT = Path(__file__).resolve().parent / "SPMS_Forecasts_Page_Study_Guide.pdf"

PRIMARY = colors.HexColor("#1F4E79")
ACCENT = colors.HexColor("#2E75B6")
LIGHT = colors.HexColor("#EEF3F8")
SOFT = colors.HexColor("#F7F9FC")
BORDER = colors.HexColor("#CCCCCC")
MUTED = colors.HexColor("#555555")
WARN = colors.HexColor("#C0392B")
OK = colors.HexColor("#1E8449")


def styles():
    base = getSampleStyleSheet()
    return {
        "cover": ParagraphStyle(
            "cover", parent=base["Title"], fontName="Helvetica-Bold",
            fontSize=20, textColor=PRIMARY, alignment=TA_CENTER, spaceAfter=8, leading=26,
        ),
        "sub": ParagraphStyle(
            "sub", parent=base["Normal"], fontName="Helvetica",
            fontSize=11, textColor=ACCENT, alignment=TA_CENTER, spaceAfter=6, leading=15,
        ),
        "meta": ParagraphStyle(
            "meta", parent=base["Normal"], fontName="Helvetica-Oblique",
            fontSize=9, textColor=MUTED, alignment=TA_CENTER, spaceAfter=10,
        ),
        "h1": ParagraphStyle(
            "h1", parent=base["Heading1"], fontName="Helvetica-Bold",
            fontSize=13, textColor=PRIMARY, spaceBefore=12, spaceAfter=7, leading=17,
        ),
        "h2": ParagraphStyle(
            "h2", parent=base["Heading2"], fontName="Helvetica-Bold",
            fontSize=11, textColor=ACCENT, spaceBefore=9, spaceAfter=5, leading=14,
        ),
        "body": ParagraphStyle(
            "body", parent=base["Normal"], fontName="Helvetica",
            fontSize=10, alignment=TA_JUSTIFY, spaceAfter=6, leading=13.5,
        ),
        "bullet": ParagraphStyle(
            "bullet", parent=base["Normal"], fontName="Helvetica",
            fontSize=10, leading=13,
        ),
        "note": ParagraphStyle(
            "note", parent=base["Normal"], fontName="Helvetica-Oblique",
            fontSize=9.5, textColor=MUTED, leading=12.5, spaceAfter=6,
        ),
        "formula": ParagraphStyle(
            "formula", parent=base["Normal"], fontName="Courier",
            fontSize=9.5, alignment=TA_CENTER, textColor=PRIMARY,
            spaceBefore=3, spaceAfter=7, leading=12,
        ),
        "cell": ParagraphStyle(
            "cell", parent=base["Normal"], fontName="Helvetica", fontSize=8.5, leading=11,
        ),
        "cell_h": ParagraphStyle(
            "cell_h", parent=base["Normal"], fontName="Helvetica-Bold",
            fontSize=8.5, textColor=colors.white, leading=11,
        ),
        "quiz": ParagraphStyle(
            "quiz", parent=base["Normal"], fontName="Helvetica",
            fontSize=10, leading=13, spaceAfter=3,
        ),
    }


def p(text, style):
    return Paragraph(text, style)


def bullets(items, style):
    return ListFlowable(
        [ListItem(Paragraph(i, style), leftIndent=10, value="bullet") for i in items],
        bulletType="bullet", start="•", leftIndent=14, bulletFontSize=10,
    )


def table(headers, rows, widths, s):
    data = [[p(h, s["cell_h"]) for h in headers]]
    for row in rows:
        data.append([p(c, s["cell"]) for c in row])
    t = Table(data, colWidths=widths, repeatRows=1)
    t.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), PRIMARY),
        ("BACKGROUND", (0, 1), (-1, -1), SOFT),
        ("GRID", (0, 0), (-1, -1), 0.4, BORDER),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 4),
        ("RIGHTPADDING", (0, 0), (-1, -1), 4),
        ("TOPPADDING", (0, 0), (-1, -1), 3),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 3),
        ("ROWBACKGROUNDS", (0, 1), (-1, -1), [SOFT, colors.white]),
    ]))
    return t


def callout(title, body, s, color=ACCENT):
    inner = [p(f"<b>{title}</b>", s["body"]), p(body, s["note"])]
    t = Table([[inner]], colWidths=[16.5 * cm])
    t.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, -1), LIGHT),
        ("BOX", (0, 0), (-1, -1), 1.2, color),
        ("LEFTPADDING", (0, 0), (-1, -1), 8),
        ("RIGHTPADDING", (0, 0), (-1, -1), 8),
        ("TOPPADDING", (0, 0), (-1, -1), 5),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
    ]))
    return t


def footer(canvas, doc):
    canvas.saveState()
    canvas.setStrokeColor(BORDER)
    canvas.line(2 * cm, 1.4 * cm, A4[0] - 2 * cm, 1.4 * cm)
    canvas.setFont("Helvetica", 8)
    canvas.setFillColor(MUTED)
    canvas.drawString(2 * cm, 0.9 * cm, "SPMS · Forecasts Page Study Guide")
    canvas.drawRightString(A4[0] - 2 * cm, 0.9 * cm, f"Page {doc.page}")
    canvas.restoreState()


def build():
    s = styles()
    story = []

    # Cover
    story.append(Spacer(1, 1.8 * cm))
    story.append(p("Sales Prediction Management System", s["cover"]))
    story.append(p("Forecasts Page — Full Study Guide", s["sub"]))
    story.append(p(
        "Generate forecast · Chart · Inventory suggestion · Reorder watchlist · MAE / RMSE",
        s["sub"],
    ))
    story.append(p(
        "Plain-English notes with examples for self-study and thesis defense preparation.",
        s["meta"],
    ))
    story.append(callout(
        "How to use this PDF",
        "Part A = whole Forecasts page tour. Part B = deep dive on inventory numbers "
        "(stock, predicted demand, reorder level, suggest order) and MAE/RMSE. "
        "Read Part B slowly with a calculator if you want. End has quiz + answers.",
        s,
    ))
    story.append(PageBreak())

    # ========== PART A ==========
    story.append(p("PART A — What is the Forecasts page?", s["h1"]))
    story.append(p(
        "The Forecasts page is the <b>manager planning board</b>. "
        "The shop already records sales and stock. Here you ask: "
        "<i>How much of this product might we sell soon, and do we have enough stock?</i>",
        s["body"],
    ))
    story.append(p(
        "Only <b>Manager</b> and <b>Admin</b> can open it. Staff use New Sale / History instead.",
        s["body"],
    ))
    story.append(p(
        "You (manager) click Run prediction → PHP calls Python brain → "
        "brain returns next N days of units → saved in MySQL → chart + tables + reorder tips.",
        s["formula"],
    ))

    story.append(p("A1. Online / offline banner", s["h2"]))
    story.append(table(
        ["Banner", "Meaning"],
        [
            ["Prediction engine online", "Python Flask brain is running. You can run new predictions."],
            ["Prediction service offline", "Brain not running. Old saved forecasts may still show; new runs fail until you start python app.py."],
        ],
        [5.5 * cm, 11 * cm],
        s,
    ))

    story.append(p("A2. Generate forecast (left form)", s["h2"]))
    story.append(table(
        ["Control", "Meaning"],
        [
            ["Product", "Which item to forecast (Tissue Pack, Indomie, milk power, …). Units always mean units of THIS product."],
            ["Model", "Who guesses: LSTM (sequence model) or Linear Regression (simple baseline)."],
            ["Horizon (days)", "How far ahead. Horizon 7 = next 7 days."],
            ["Run prediction", "Ask the brain now; save results; refresh chart/tables."],
            ["Update model (retrain)", "Admin only. Teach the brain again from newest sales. Then run prediction again."],
        ],
        [4.2 * cm, 12.3 * cm],
        s,
    ))

    story.append(p("A3. History vs forecast (chart)", s["h2"]))
    story.append(table(
        ["Line", "Meaning"],
        [
            ["Blue solid — Actual sales", "What really sold in the past (~last 45 days). Truth."],
            ["Orange dashed — Predicted", "What the model guesses for future days. Not real sales yet."],
        ],
        [5 * cm, 11.5 * cm],
        s,
    ))
    story.append(p(
        "Left side of the chart = past (blue). Right side with future dates = guess (orange). "
        "Small text above may show model name, MAE, RMSE, and when the forecast was generated.",
        s["body"],
    ))

    story.append(p("A4. Saved forecast rows (table)", s["h2"]))
    story.append(p(
        "Same forecast as the orange line, listed day by day.",
        s["body"],
    ))
    story.append(table(
        ["Column", "Meaning"],
        [
            ["Date", "Future calendar day being guessed"],
            ["Predicted qty", "How many units of the SELECTED product expected that day"],
            ["Model", "lstm or linear_regression"],
            ["MAE / RMSE", "Model quality scores from evaluation (lower is better)"],
        ],
        [3.5 * cm, 13 * cm],
        s,
    ))

    story.append(p("A5. Reorder watchlist", s["h2"]))
    story.append(p(
        "Shop-wide view: for each product, compare latest saved forecast total vs current stock. "
        "Red <b>Short</b> = may need restock. Green <b>OK</b> = stock covers predicted demand. "
        "If you never ran a forecast for a product, predicted demand may be 0.",
        s["body"],
    ))

    story.append(p("A6. Recent model training scores", s["h2"]))
    story.append(p(
        "Report card from training (python train.py or Update model). "
        "Compare linear_regression_p3 vs lstm_p3: lower MAE/RMSE usually means better on the test period.",
        s["body"],
    ))

    story.append(PageBreak())

    # ========== PART B — deep inventory ==========
    story.append(p("PART B — Inventory suggestion explained carefully", s["h1"]))
    story.append(p(
        "This is the section people confuse most. Read slowly.",
        s["note"],
    ))

    story.append(p("B1. Everything is about ONE product", s["h2"]))
    story.append(p(
        "When you select <b>Tissue Pack</b> and run prediction, every number on that panel is about "
        "<b>Tissue Pack units only</b> — not money, not all products mixed.",
        s["body"],
    ))
    story.append(bullets([
        "Predicted qty = how many <b>packs of tissue</b> (if product is Tissue Pack)",
        "If product is Indomie Carton → units = cartons of Indomie",
        "If product is milk power → units = packs/tins of that product",
        "Stock = how many of that same product you have in the store now",
    ], s["bullet"]))
    story.append(Spacer(1, 0.15 * cm))
    story.append(callout(
        "Remember",
        "Predicted quantity is always: units of the product you selected — not naira, not mixed items.",
        s,
    ))

    story.append(p("B2. The four numbers (definitions)", s["h2"]))
    story.append(table(
        ["Term", "What it is", "What it is NOT"],
        [
            [
                "Current stock",
                "How many units you have on the shelf / in store RIGHT NOW",
                "Not future stock. Not sales money.",
            ],
            [
                "Predicted demand (horizon)",
                "SUM of predicted daily sales over the next N days (e.g. 7 days). How many units customers may BUY in that period.",
                "NOT 'stock you will have in 7 days'. NOT leftover inventory.",
            ],
            [
                "Static reorder level",
                "A fixed warning line YOU set on the product (e.g. 10). When stock ≤ 10, dashboard can show low-stock alert. Manual rule.",
                "NOT the AI forecast. NOT automatic order quantity.",
            ],
            [
                "Suggest order",
                "Extra units to BUY from supplier so stock can cover predicted demand. Roughly: demand − stock (if demand > stock).",
                "NOT 'you will have 35 in stock'. It is 'you may need to order 15 more'.",
            ],
        ],
        [3.3 * cm, 7.2 * cm, 6 * cm],
        s,
    ))

    story.append(p("B3. Your example — fixed step by step", s["h2"]))
    story.append(p(
        "You said: stock = 20, next 7 days predicted total = 35, extra 15, suggest order = ?",
        s["body"],
    ))
    story.append(p("<b>Correct reading</b>", s["h2"]))
    story.append(bullets([
        "Stock <b>now</b> = 20 units of that product (e.g. 20 tissue packs).",
        "Horizon = 7 days.",
        "Predicted demand = 35 means: over the next 7 days, the model thinks customers will buy about <b>35 units total</b> (day1+day2+…+day7).",
        "That is <b>expected sales / demand</b>, not 'stock level after 7 days'.",
        "If nobody restocks: 20 on hand is less than 35 needed → you may run short by about 15.",
        "Suggest order ≈ 35 − 20 = <b>15 units to buy from supplier</b>.",
    ], s["bullet"]))
    story.append(Spacer(1, 0.15 * cm))
    story.append(callout(
        "Wrong reading (common mistake)",
        "Wrong: “In 7 days my stock will become 35.” "
        "Right: “Customers may want about 35 units in 7 days; I only have 20; I should order about 15.”",
        s,
        color=WARN,
    ))

    story.append(p("B4. Mini table for the same example", s["h2"]))
    story.append(table(
        ["Day", "Predicted qty (units of THIS product)", "Running total demand"],
        [
            ["Day 1 (e.g. Mon)", "6", "6"],
            ["Day 2", "5", "11"],
            ["Day 3", "4", "15"],
            ["Day 4", "5", "20"],
            ["Day 5", "6", "26"],
            ["Day 6", "4", "30"],
            ["Day 7", "5", "35"],
        ],
        [4.5 * cm, 7 * cm, 5 * cm],
        s,
    ))
    story.append(p(
        "Predicted demand (horizon) = 35. Current stock = 20. Suggest order = ceil(35 − 20) = 15.",
        s["formula"],
    ))
    story.append(p(
        "If after 7 days you sold exactly 35 and never restocked, stock would go toward zero "
        "(or negative in theory — the system blocks overselling on real sales). "
        "The suggestion is meant to help you restock <b>before</b> that happens.",
        s["body"],
    ))

    story.append(p("B5. Another example (enough stock)", s["h2"]))
    story.append(bullets([
        "Product: Indomie Carton",
        "Current stock = 100 cartons",
        "Horizon 7 predicted demand = 40 cartons",
        "100 ≥ 40 → green: stock looks enough",
        "Surplus ≈ 100 − 40 = 60 cartons buffer",
        "Suggest order = 0 (no forced order from this rule)",
    ], s["bullet"]))

    story.append(p("B6. Static reorder level vs predicted demand", s["h2"]))
    story.append(p(
        "These are two different alarms.",
        s["body"],
    ))
    story.append(table(
        ["Alarm", "Based on", "Example"],
        [
            [
                "Static reorder level",
                "Fixed number on product form (manager sets 10, 20, …)",
                "Stock is 8 and reorder level is 10 → low-stock badge on dashboard even without AI",
            ],
            [
                "Predicted demand / suggest order",
                "AI forecast for next N days vs current stock",
                "Stock 50, reorder level 10 (looks fine statically), but 7-day demand is 80 → still suggest order 30",
            ],
        ],
        [4 * cm, 6.5 * cm, 6 * cm],
        s,
    ))
    story.append(p(
        "So: reorder level = simple permanent warning line. "
        "Predicted demand = smart short-term expected sales. "
        "A good manager looks at both.",
        s["body"],
    ))

    story.append(PageBreak())

    story.append(p("B7. Where the daily predicted qty comes from", s["h2"]))
    story.append(bullets([
        "Model reads recent history of that product (and features like weekend/promo).",
        "It outputs a number for each future day: e.g. 6, 5, 4, …",
        "Those appear in Saved forecast rows and as the orange chart line.",
        "Inventory suggestion adds those days together for the horizon total.",
    ], s["bullet"]))
    story.append(p(
        "Formula used in the app (conceptually):",
        s["body"],
    ))
    story.append(p(
        "predicted_demand = sum(predicted_qty for each day in horizon)\n"
        "if predicted_demand > stock: suggested_order = ceil(predicted_demand − stock)\n"
        "else: suggested_order = 0",
        s["formula"],
    ))

    # ========== PART C MAE RMSE ==========
    story.append(p("PART C — MAE and RMSE explained better", s["h1"]))
    story.append(p(
        "MAE and RMSE are <b>report card scores</b> for the model. "
        "They are NOT sales forecasts and NOT stock levels.",
        s["body"],
    ))
    story.append(callout(
        "One line to memorize",
        "MAE / RMSE measure how wrong the model was on past test days. "
        "Lower number = better. They do not tell you how many units to order.",
        s,
    ))

    story.append(p("C1. Why we need them", s["h2"]))
    story.append(p(
        "Anyone can invent a guess. Metrics ask: when we hide the last part of history and let the model "
        "predict those days, how close was it to the truth?",
        s["body"],
    ))

    story.append(p("C2. Absolute error (building block)", s["h2"]))
    story.append(p(
        "For one day: absolute error = |true sales − predicted sales| "
        "(always positive distance).",
        s["body"],
    ))
    story.append(p(
        "True = 10 tissue packs, predicted = 7 → error = 3 packs that day.",
        s["formula"],
    ))

    story.append(p("C3. MAE — Mean Absolute Error", s["h2"]))
    story.append(p(
        "MAE = average of those absolute errors across many test days.",
        s["body"],
    ))
    story.append(p(
        "Manager English: “On a typical test day, the model was off by about MAE units.”",
        s["body"],
    ))
    story.append(p(
        "Example days true: 10, 8, 12. Predictions: 9, 10, 11.\n"
        "Errors: 1, 2, 1. MAE = (1+2+1)/3 = 1.33 units.",
        s["formula"],
    ))
    story.append(p(
        "If product is Tissue Pack and MAE = 1.33, the model was wrong by about "
        "1.3 packs per day on average on the test set — not 1.3 naira.",
        s["note"],
    ))

    story.append(p("C4. RMSE — Root Mean Square Error", s["h2"]))
    story.append(p(
        "RMSE also measures average error, but <b>big mistakes hurt more</b> because errors are squared first.",
        s["body"],
    ))
    story.append(bullets([
        "Square each error: 1²=1, 2²=4, 1²=1",
        "Mean of squares = (1+4+1)/3 = 2",
        "RMSE = √2 ≈ 1.41 units",
    ], s["bullet"]))
    story.append(p(
        "If most days are slightly wrong but one day is wildly wrong, RMSE rises more than MAE. "
        "That helps you see unstable models.",
        s["body"],
    ))

    story.append(p("C5. MAE vs RMSE cheatsheet", s["h2"]))
    story.append(table(
        ["", "MAE", "RMSE"],
        [
            ["Idea", "Average absolute miss", "Average miss, big misses amplified"],
            ["Unit", "Same as product units", "Same as product units"],
            ["Lower better?", "Yes", "Yes"],
            ["Sensitive to outliers?", "Less", "More"],
            ["Manager phrase", "Usually off by ~MAE units/day", "Penalizes large bad days more"],
        ],
        [3.5 * cm, 6.5 * cm, 6.5 * cm],
        s,
    ))

    story.append(p("C6. Worked comparison (Linear vs LSTM)", s["h2"]))
    story.append(table(
        ["Model", "MAE", "RMSE", "How to read"],
        [
            ["Linear Regression", "2.27", "2.91", "On average ~2.3 units wrong/day on test"],
            ["LSTM", "1.86", "2.33", "On average ~1.9 units wrong/day — better here"],
        ],
        [4 * cm, 2.5 * cm, 2.5 * cm, 7.5 * cm],
        s,
    ))
    story.append(p(
        "Because LSTM MAE and RMSE are lower, we say LSTM performed better on that product’s test period. "
        "That does not guarantee next week will be perfect — only that it fit history better.",
        s["body"],
    ))

    story.append(p("C7. What MAE/RMSE are NOT", s["h2"]))
    story.append(bullets([
        "NOT the number of units to order",
        "NOT predicted demand for 7 days",
        "NOT stock remaining",
        "NOT a percentage unless you convert them yourself",
        "NOT comparable blindly across products with very different sales scales (a MAE of 2 on USB sticks vs 2 on rice bags is different business impact)",
    ], s["bullet"]))

    story.append(PageBreak())

    # ========== PART D more examples ==========
    story.append(p("PART D — Full walkthrough examples", s["h1"]))

    story.append(p("Example 1 — Tissue Pack", s["h2"]))
    story.append(bullets([
        "You select product: Tissue Pack",
        "Horizon: 7, Model: LSTM",
        "Run prediction",
        "Saved rows might show Mon 3, Tue 2, Wed 4, Thu 3, Fri 5, Sat 6, Sun 5 → demand = 28 packs",
        "Current stock = 12 packs",
        "28 > 12 → Suggest order = 16 packs of tissue",
        "Static reorder level = 20 → even without AI, stock 12 is already below 20 (low stock alert)",
        "MAE 1.88 means historically the model was off by ~2 packs/day on average on test days",
    ], s["bullet"]))

    story.append(p("Example 2 — Rice 50kg (slow mover)", s["h2"]))
    story.append(bullets([
        "Predicted 7-day demand = 8 bags total",
        "Stock = 40 bags",
        "40 > 8 → OK, suggest order 0",
        "Reorder level = 15 → stock still above static warning",
        "Manager may still order early for supplier lead time — AI is advice, not a law",
    ], s["bullet"]))

    story.append(p("Example 3 — Promo week", s["h2"]))
    story.append(bullets([
        "Product has an active promotion in the Promotions table",
        "Feature is_promo helps the model expect higher sales",
        "Predicted demand may jump vs a normal week",
        "Inventory suggestion may flip from OK to Short even if stock looked fine last week",
    ], s["bullet"]))

    story.append(p("PART E — How sections connect", s["h1"]))
    story.append(p(
        "Generate forecast → creates daily predicted qty\n"
        "Chart → draws history (blue) + predictions (orange)\n"
        "Saved rows → same predictions as a table\n"
        "Inventory suggestion → sum(predictions) vs stock for THIS product\n"
        "Reorder watchlist → same idea for many products\n"
        "Training scores → MAE/RMSE quality of brains when trained",
        s["formula"],
    ))

    story.append(p("PART F — Self-check quiz", s["h1"]))
    qs = [
        "1. If predicted demand (horizon 7) is 35, does that mean stock will be 35 in 7 days?",
        "2. Stock 20, demand 35. What is suggest order about?",
        "3. Predicted qty is units of what?",
        "4. What is static reorder level?",
        "5. MAE = 2 for Tissue Pack means what in plain English?",
        "6. Why might RMSE be higher than MAE?",
        "7. Blue chart line vs orange chart line?",
        "8. Can Staff open Forecasts?",
    ]
    for q in qs:
        story.append(p(q, s["quiz"]))
    story.append(Spacer(1, 0.25 * cm))
    story.append(p("Answers", s["h2"]))
    ans = [
        "1. No. 35 is expected customer purchases over 7 days, not ending stock.",
        "2. About 15 more units to buy so stock can cover demand (35−20).",
        "3. Units of the selected product only (e.g. tissue packs if Tissue Pack is selected).",
        "4. A fixed low-stock warning number set on the product; not the AI demand total.",
        "5. On test days the model was wrong by about 2 packs per day on average.",
        "6. Because large mistakes are squared and pull RMSE up more.",
        "7. Blue = real past sales; orange = model’s future guess.",
        "8. No — Manager/Admin only.",
    ]
    for a in ans:
        story.append(p(a, s["note"]))

    story.append(p("PART G — Study tips", s["h1"]))
    story.append(bullets([
        "Explain Example B3 out loud without looking (stock 20, demand 35, order 15).",
        "On the live page, pick one product and write: stock, demand, suggest order, MAE.",
        "Never mix MAE with suggest order — different jobs.",
        "Defense sentence: “We forecast unit demand over a horizon and compare it to on-hand stock to support reorder decisions; model quality is reported via MAE and RMSE.”",
    ], s["bullet"]))

    story.append(Spacer(1, 0.4 * cm))
    story.append(callout(
        "Commands (if services stopped)",
        "Shop: http://localhost/spms/ (Apache + MySQL in XAMPP)<br/>"
        "Brain: cd ml_service → python app.py  or  docs/start-ml.ps1<br/>"
        "Login manager@spms.local / password123 → Forecasts",
        s,
    ))

    story.append(Spacer(1, 0.35 * cm))
    story.append(p(
        "End of guide. Re-read Part B and Part C before demos. "
        "Your ML theory PDF (Linear Regression & LSTM) pairs well with this UI guide.",
        s["meta"],
    ))

    doc = SimpleDocTemplate(
        str(OUT),
        pagesize=A4,
        leftMargin=2 * cm,
        rightMargin=2 * cm,
        topMargin=1.5 * cm,
        bottomMargin=1.8 * cm,
        title="SPMS Forecasts Page Study Guide",
        author="Sales Prediction Management System",
    )
    doc.build(story, onFirstPage=footer, onLaterPages=footer)
    print(f"Wrote {OUT}")


if __name__ == "__main__":
    build()
