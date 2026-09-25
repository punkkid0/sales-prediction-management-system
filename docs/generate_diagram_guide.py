"""Defense guide mapped to the supervisor 6-block diagram."""
from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_JUSTIFY
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.platypus import (
    KeepTogether,
    PageBreak,
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    Table,
    TableStyle,
)

OUT = Path(__file__).resolve().parent / "SPMS_Diagram_Defense_Guide.pdf"
DESKTOP = Path.home() / "Desktop" / "SPMS_Diagram_Defense_Guide.pdf"

PRIMARY = colors.HexColor("#1F4E79")
ACCENT = colors.HexColor("#2E75B6")
LIGHT = colors.HexColor("#EEF3F8")
SOFT = colors.HexColor("#F7F9FC")
BORDER = colors.HexColor("#CCCCCC")
MUTED = colors.HexColor("#555555")
MARGIN = 20 * mm
CONTENT_W = A4[0] - 2 * MARGIN


def styles():
    base = getSampleStyleSheet()
    return {
        "cover": ParagraphStyle("cover", parent=base["Title"], fontName="Helvetica-Bold",
                                fontSize=18, textColor=PRIMARY, alignment=TA_CENTER, spaceAfter=6, leading=22),
        "sub": ParagraphStyle("sub", parent=base["Normal"], fontName="Helvetica",
                              fontSize=10.5, textColor=ACCENT, alignment=TA_CENTER, spaceAfter=4, leading=14),
        "meta": ParagraphStyle("meta", parent=base["Normal"], fontName="Helvetica-Oblique",
                               fontSize=9, textColor=MUTED, alignment=TA_CENTER, spaceAfter=8),
        "h1": ParagraphStyle("h1", parent=base["Heading1"], fontName="Helvetica-Bold",
                             fontSize=12.5, textColor=PRIMARY, spaceBefore=10, spaceAfter=6, leading=16),
        "h2": ParagraphStyle("h2", parent=base["Heading2"], fontName="Helvetica-Bold",
                             fontSize=10.5, textColor=ACCENT, spaceBefore=8, spaceAfter=4, leading=13),
        "body": ParagraphStyle("body", parent=base["Normal"], fontName="Helvetica",
                               fontSize=9.5, alignment=TA_JUSTIFY, spaceAfter=5, leading=13),
        "bullet": ParagraphStyle("bullet", parent=base["Normal"], fontName="Helvetica",
                                 fontSize=9.5, leading=12.5, leftIndent=12, spaceAfter=2),
        "note": ParagraphStyle("note", parent=base["Normal"], fontName="Helvetica-Oblique",
                               fontSize=9, textColor=MUTED, leading=12, spaceAfter=4),
        "cell": ParagraphStyle("cell", parent=base["Normal"], fontName="Helvetica", fontSize=8, leading=10.5),
        "cell_h": ParagraphStyle("cell_h", parent=base["Normal"], fontName="Helvetica-Bold",
                                 fontSize=8, textColor=colors.white, leading=10.5),
        "q": ParagraphStyle("q", parent=base["Normal"], fontName="Helvetica-Bold", fontSize=9.5, leading=12, spaceAfter=1),
    }


S = None


def p(text, key):
    return Paragraph(text, S[key])


def bullets(items):
    return [Paragraph("- " + i, S["bullet"]) for i in items]


def table(headers, rows, widths):
    assert abs(sum(widths) - CONTENT_W) < 2, (sum(widths), CONTENT_W)
    data = [[p(h, "cell_h") for h in headers]]
    for row in rows:
        data.append([p(c, "cell") for c in row])
    t = Table(data, colWidths=widths, repeatRows=1)
    t.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), PRIMARY),
        ("GRID", (0, 0), (-1, -1), 0.4, BORDER),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 4),
        ("RIGHTPADDING", (0, 0), (-1, -1), 4),
        ("TOPPADDING", (0, 0), (-1, -1), 3),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 3),
        ("ROWBACKGROUNDS", (0, 1), (-1, -1), [SOFT, colors.white]),
    ]))
    return t


def callout(title, body):
    inner = [p(f"<b>{title}</b>", "body"), p(body, "note")]
    t = Table([[inner]], colWidths=[CONTENT_W])
    t.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, -1), LIGHT),
        ("BOX", (0, 0), (-1, -1), 1, ACCENT),
        ("LEFTPADDING", (0, 0), (-1, -1), 8),
        ("RIGHTPADDING", (0, 0), (-1, -1), 8),
        ("TOPPADDING", (0, 0), (-1, -1), 5),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
    ]))
    return t


def footer(canvas, doc):
    canvas.saveState()
    y = 12 * mm
    canvas.setStrokeColor(BORDER)
    canvas.setLineWidth(0.6)
    canvas.line(MARGIN, y + 4, A4[0] - MARGIN, y + 4)
    canvas.setFont("Helvetica", 8)
    canvas.setFillColor(MUTED)
    canvas.drawString(MARGIN, y - 2, "SPMS Defense Guide - matches supervisor diagram")
    canvas.drawRightString(A4[0] - MARGIN, y - 2, f"Page {doc.page}")
    canvas.restoreState()


def build():
    global S
    S = styles()
    W = CONTENT_W
    story = []

    story.append(Spacer(1, 8 * mm))
    story.append(p("Sales Prediction Management System", "cover"))
    story.append(p("Defense and User Guide", "sub"))
    story.append(p("Mapped to the supervisor software block diagram", "sub"))
    story.append(p("How each block works in the running software", "meta"))
    story.append(callout(
        "One sentence",
        "SPMS is a web shop system that records sales, cleans the history, forecasts future demand "
        "with Linear Regression and LSTM, shows charts and reports, and recommends whether to reorder stock.",
    ))
    story.append(p("How the diagram is built", "h1"))
    story.append(p(
        "The supervisor diagram has one title box, Sales Prediction Management System, then six modules side by side. "
        "Each module has four smaller boxes under it. The left menu of the software uses the same six names.",
        "body",
    ))
    story.append(table(
        ["Diagram module", "Menu in the software", "Who can open it"],
        [
            ["1. User Management", "Dashboard, Manage Users, Logout", "All users; Manage Users is admin only"],
            ["2. Sales Data Management", "Sales Entry, Sales History, Products, Customers", "Staff, manager, admin"],
            ["3. Data Processing and Cleaning", "Clean and Prepare", "Manager and admin"],
            ["4. Sales Prediction and Forecasting", "Predict and Evaluate", "Manager and admin"],
            ["5. Reports and Visualization", "Charts and Reports", "Manager and admin"],
            ["6. Decision Support and Inventory Planning", "Inventory Planning", "Manager and admin"],
        ],
        [W * 0.34, W * 0.40, W * 0.26],
    ))

    story.append(p("How to start the software", "h1"))
    story.extend(bullets([
        "Unzip or clone the project. Do not install PostgreSQL. The database is MySQL inside XAMPP.",
        "Once: double-click 1-SETUP.bat and wait until it says setup finished.",
        "Every time: double-click 2-START-SERVERS.bat, then open http://localhost/spms/",
        "If Forecasts says the engine is offline, wait about 10 seconds and refresh. Flask is still loading.",
        "Optional public link: double-click 3-START-NGROK.bat, open http://127.0.0.1:4040, copy the https address and add /spms/",
        "When finished: double-click STOP-SERVERS.bat.",
        "Password for every demo account is password123.",
    ]))
    story.append(table(
        ["Role", "Email", "Job in the shop"],
        [
            ["Admin", "admin@spms.local", "Full control, users, and model retrain"],
            ["Manager", "manager@spms.local", "Reports, forecasts, stock decisions"],
            ["Staff", "staff1@spms.local", "Record sales and print receipts"],
        ],
        [W * 0.18, W * 0.36, W * 0.46],
    ))
    story.append(PageBreak())

    # BLOCK 1
    story.append(p("Block 1 - User Management", "h1"))
    story.append(p(
        "This block controls who can enter the system and what they are allowed to touch. "
        "Passwords are stored as hashes, not plain text. After login, a session starts. Logout destroys that session.",
        "body",
    ))
    story.append(table(
        ["Diagram box", "What it means", "Where in the software"],
        [
            ["Login / Authentication", "Check email and password before opening the shop", "Login page"],
            ["Access Control", "Staff, manager, and admin see different menus", "Every page checks the role"],
            ["Manage Users", "Create accounts, change role, activate or deactivate", "Manage Users (admin only)"],
            ["Logout", "End the session safely", "Logout button at the top right"],
        ],
        [W * 0.26, W * 0.40, W * 0.34],
    ))
    story.append(p(
        "Defense line: staff can sell, managers can analyse, and only the admin can create logins or retrain the model.",
        "note",
    ))

    # BLOCK 2
    story.append(p("Block 2 - Sales Data Management", "h1"))
    story.append(p(
        "This block is the daily shop work: record what was sold, keep product and customer records, and correct a sale if it was entered wrong.",
        "body",
    ))
    story.append(table(
        ["Diagram box", "What it means", "Where in the software"],
        [
            ["Sales Data Entry", "Record a sale: product, customer, quantity, date", "Sales Entry"],
            ["Product Data Management", "Name, price, stock, reorder level, supplier", "Products"],
            ["Customer Data Management", "Name, phone, email, address", "Customers"],
            ["Data Updates", "Change a saved sale and adjust stock", "Sales History, then Update"],
        ],
        [W * 0.28, W * 0.38, W * 0.34],
    ))
    story.append(p("How a sale works", "h2"))
    story.extend(bullets([
        "The user picks a product. The screen shows the price and the stock.",
        "Quantity times unit price becomes the total.",
        "If quantity is more than stock, the sale is blocked. That stops overselling.",
        "If stock is enough, the sale is saved and stock goes down by that quantity.",
        "A receipt can be opened and printed.",
        "Update on Sales History puts the old quantity back into stock, then deducts the new quantity.",
    ]))
    story.append(p(
        "Suppliers and Promotions also live in this area. Promotions later become an external factor for forecasting.",
        "note",
    ))

    story.append(PageBreak())

    # BLOCK 3
    story.append(p("Block 3 - Data Processing and Cleaning", "h1"))
    story.append(p(
        "Raw sales are messy for a model. Some days have many receipts. Some days have none. "
        "This block turns that mess into one clean number per day, plus extra clues. "
        "Open Clean and Prepare, pick a product, and choose how many days to look at.",
        "body",
    ))
    story.append(table(
        ["Diagram box", "What the software does", "What you see"],
        [
            ["Data Validation", "Drops rows with no date or quantity of zero", "Invalid rows skipped count"],
            ["Handle Missing Data", "Days with no sale become quantity 0", "Yellow rows marked Missing filled"],
            ["Data Cleaning", "Many sales on one day are added into one daily total", "Clean qty column"],
            ["Data Preparation", "Adds weekday, weekend, month, and promo flag", "Those columns on the prepared table"],
        ],
        [W * 0.26, W * 0.40, W * 0.34],
    ))
    story.append(p(
        "Why this matters: Linear Regression and LSTM both read this prepared series. "
        "If missing days were left as gaps, the timeline would be broken.",
        "note",
    ))

    # BLOCK 4
    story.append(p("Block 4 - Sales Prediction and Forecasting", "h1"))
    story.append(p(
        "This block learns from the cleaned history and guesses future daily sales. "
        "Open Predict and Evaluate. Two models are used on purpose.",
        "body",
    ))
    story.append(table(
        ["Model", "Plain meaning", "Role in the project"],
        [
            ["Linear Regression", "A simple weighted formula from past clues to the next quantity", "Baseline, easy to explain"],
            ["LSTM", "A neural network that reads sales as a sequence and keeps a memory of recent days", "Main forecasting model"],
        ],
        [W * 0.24, W * 0.42, W * 0.34],
    ))
    story.append(table(
        ["Diagram box", "What it means", "Where in the software"],
        [
            ["Model Training", "Learn weights from past days and save the model", "Admin button Update model, or python train.py"],
            ["Generate Predictions", "Ask for the next N days for one product", "Run prediction"],
            ["Forecast Results", "Show the guessed quantities", "Orange dashed line and the saved forecast table"],
            ["Model Evaluation", "Score how wrong each model was", "MAE and RMSE table, Better MAE badge"],
        ],
        [W * 0.24, W * 0.40, W * 0.36],
    ))
    story.append(p("How to read the chart", "h2"))
    story.extend(bullets([
        "Blue line = real past sales.",
        "Orange dashed line = the model's guess for future days. It is not money already earned.",
        "Horizon 7 means the next 7 days.",
        "Predicted quantity is always units of the selected product, for example tissue packs or cartons.",
    ]))
    story.append(p("MAE and RMSE", "h2"))
    story.extend(bullets([
        "MAE is the average size of the mistake, in product units. Wrong by about 2 packs a day means MAE near 2.",
        "RMSE is similar, but a few very large mistakes pull it up more.",
        "Lower is better. They are not order quantities and they are not stock.",
        "Recent model training scores on the right lists the latest scores. The comparison table is for the product you selected.",
    ]))

    story.append(PageBreak())

    # BLOCK 5
    story.append(p("Block 5 - Reports and Visualization", "h1"))
    story.append(p(
        "This block is for looking backward: what already sold, which products did well, and the shape of the trend. "
        "Open Charts and Reports. Pick a start date and an end date.",
        "body",
    ))
    story.append(table(
        ["Diagram box", "What you see", "How to use it in defense"],
        [
            ["Charts and Graphs", "Bar chart of daily revenue", "A picture of sales over the chosen period"],
            ["Trends Analysis", "Daily revenue chart and sales by product", "Which days and products are stronger"],
            ["Performance Reports", "Daily summary and monthly overview", "Transactions, units, and revenue"],
            ["Export Reports", "Export CSV button", "Open the file in Excel"],
        ],
        [W * 0.26, W * 0.36, W * 0.38],
    ))
    story.append(p(
        "The dashboard also shows today's revenue, this month, a 14-day chart, and low-stock alerts. "
        "That is a quick view. Charts and Reports is the fuller view.",
        "note",
    ))

    # BLOCK 6
    story.append(p("Block 6 - Decision Support and Inventory Planning", "h1"))
    story.append(p(
        "Forecasts are only useful if a manager can act on them. "
        "Inventory Planning compares what is on the shelf with what the model thinks customers will buy.",
        "body",
    ))
    story.append(table(
        ["Diagram box", "What it means", "Where in the software"],
        [
            ["Sales Insights", "What sold recently, next to the forecast", "Sold last 7 days column"],
            ["Inventory Planning", "Stock and reorder level versus predicted demand", "Stock, reorder level, predicted demand"],
            ["Business Decision Support", "A plain sentence the manager can follow", "Recommendation column"],
            ["Recommendation Generation", "Order a number of units, or do not order", "Badge: Order X, Stock OK, or Need forecast"],
        ],
        [W * 0.28, W * 0.38, W * 0.34],
    ))
    story.append(callout(
        "Example to memorise",
        "Stock now is 20 units. Predicted demand for the next 7 days is 35 units. "
        "35 is expected sales, not the stock you will have. "
        "20 is not enough for 35, so the recommendation is to order about 15 units. "
        "If stock were 100 and demand were 35, the recommendation would be Stock OK.",
    ))
    story.append(p(
        "Reorder level is a fixed warning the manager typed on the product, for example 10. "
        "It is separate from the forecast. A product can be above the reorder level and still need an order if the forecast is high.",
        "body",
    ))

    story.append(PageBreak())

    story.append(p("How the blocks connect", "h1"))
    story.extend(bullets([
        "User Management lets the right person in.",
        "Sales Data Management stores products, customers, and each sale, and reduces stock.",
        "Data Processing and Cleaning builds a daily series and features.",
        "Sales Prediction and Forecasting trains Linear Regression and LSTM, then writes future quantities.",
        "Reports and Visualization shows what already happened.",
        "Decision Support compares the forecast with stock and says whether to order.",
    ]))
    story.append(p(
        "Browser to PHP pages to MySQL. The prediction pages call a Python Flask service on this same computer, port 5000. "
        "PHP never contains the neural network. That split matches a modular design: shop, database, and prediction engine.",
        "body",
    ))

    story.append(p("Five-minute demo", "h1"))
    story.extend(bullets([
        "Login as staff1@spms.local. Open Sales Entry. Sell 1 unit. Open the receipt. Stock has gone down.",
        "Logout. Login as manager@spms.local. Open Dashboard. Show today's money and low stock.",
        "Open Clean and Prepare. Show a yellow missing day filled with 0, and the promo or weekend columns.",
        "Open Predict and Evaluate. Run prediction for 7 days. Blue is history. Orange is the guess. Show MAE.",
        "Open Charts and Reports. Show the trend chart and mention Export CSV.",
        "Open Inventory Planning. Read one recommendation out loud: Order X or Stock OK.",
        "Optional: login as admin and show Manage Users and the Update model button.",
    ]))

    story.append(p("Questions a supervisor may ask", "h1"))
    qa = [
        ("What does the system do?",
         "It records sales and forecasts future product demand so stock can be planned."),
        ("Why are there three roles?",
         "Staff sell. Managers analyse and plan stock. Admin controls users and retraining."),
        ("Which diagram block is the forecast?",
         "Block 4, Sales Prediction and Forecasting. Results are used again in Block 6."),
        ("Why two models?",
         "Linear Regression is the simple baseline. LSTM reads the sales timeline. We compare them with MAE and RMSE."),
        ("What is predicted demand?",
         "The sum of predicted units over the horizon, for example 7 days. It is expected sales, not future stock."),
        ("What is a recommendation?",
         "If predicted demand is greater than stock, order the difference. Otherwise stock is enough."),
        ("Where is data cleaning?",
         "Clean and Prepare. Missing days become 0. Same-day sales are added together. Features are added."),
        ("Is the forecast guaranteed?",
         "No. It supports a decision. A sudden event that is not in the history can still surprise the shop."),
        ("What database is used?",
         "MySQL through XAMPP. Not PostgreSQL."),
        ("What if Forecasts says the engine is offline?",
         "Flask is not ready. Wait and refresh, or run 2-START-SERVERS.bat again."),
    ]
    for q, a in qa:
        story.append(KeepTogether([p("Q: " + q, "q"), p("A: " + a, "note")]))

    story.append(p("Limits to say honestly", "h1"))
    story.extend(bullets([
        "The model needs enough past sales. A brand-new product with no history cannot be forecast until it is trained.",
        "Promo helps only if the promotion was entered in the Promotions page.",
        "A public ngrok link works only while that computer stays on.",
        "MAE and RMSE describe past test error. They do not promise next week will match exactly.",
    ]))
    story.append(Spacer(1, 4 * mm))
    story.append(p(
        "Read Blocks 2, 4, and 6 again before the defense, then practise the five-minute demo once.",
        "meta",
    ))

    doc = SimpleDocTemplate(
        str(OUT), pagesize=A4,
        leftMargin=MARGIN, rightMargin=MARGIN,
        topMargin=16 * mm, bottomMargin=18 * mm,
        title="SPMS Defense Guide",
        author="Sales Prediction Management System",
    )
    doc.build(story, onFirstPage=footer, onLaterPages=footer)
    DESKTOP.write_bytes(OUT.read_bytes())
    print(OUT)
    print(DESKTOP)


if __name__ == "__main__":
    build()
