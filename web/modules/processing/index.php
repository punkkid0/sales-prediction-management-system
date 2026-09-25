<?php

declare(strict_types=1);

require_login();
require_role(['admin', 'manager']);

$pdo = db();
$productId = (int) request('product_id', 0);
$days = max(14, min(90, (int) request('days', 30)));

$products = $pdo->query('SELECT id, name FROM products WHERE is_active = 1 ORDER BY name')->fetchAll();
if ($productId <= 0 && $products) {
    $productId = (int) $products[0]['id'];
}

$productName = '';
foreach ($products as $p) {
    if ((int) $p['id'] === $productId) {
        $productName = $p['name'];
        break;
    }
}

$raw = [];
$clean = [];
$stats = [
    'raw_rows' => 0,
    'raw_days' => 0,
    'missing_days' => 0,
    'invalid_rows' => 0,
    'promo_days' => 0,
    'clean_days' => 0,
];

if ($productId > 0) {
    $stmt = $pdo->prepare(
        'SELECT id, quantity, sale_date, total
         FROM sales
         WHERE product_id = ? AND sale_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         ORDER BY sale_date ASC, id ASC'
    );
    $stmt->execute([$productId, $days - 1]);
    $raw = $stmt->fetchAll();
    $stats['raw_rows'] = count($raw);

    $byDay = [];
    foreach ($raw as $row) {
        $qty = (int) $row['quantity'];
        if ($qty <= 0 || $row['sale_date'] === null || $row['sale_date'] === '') {
            $stats['invalid_rows']++;
            continue;
        }
        $d = $row['sale_date'];
        $byDay[$d] = ($byDay[$d] ?? 0) + $qty;
    }
    $stats['raw_days'] = count($byDay);

    $promos = $pdo->prepare(
        'SELECT product_id, start_date, end_date
         FROM promotions
         WHERE is_active = 1 AND (product_id IS NULL OR product_id = ?)'
    );
    $promos->execute([$productId]);
    $promoRows = $promos->fetchAll();

    $start = new DateTimeImmutable(date('Y-m-d', strtotime('-' . ($days - 1) . ' days')));
    $end = new DateTimeImmutable(date('Y-m-d'));
    for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
        $key = $d->format('Y-m-d');
        $qty = (float) ($byDay[$key] ?? 0);
        $missing = !isset($byDay[$key]);
        if ($missing) {
            $stats['missing_days']++;
        }
        $dow = (int) $d->format('N'); // 1 Mon .. 7 Sun
        $isWeekend = $dow >= 6 ? 1 : 0;
        $isPromo = 0;
        foreach ($promoRows as $pr) {
            if ($key >= $pr['start_date'] && $key <= $pr['end_date']) {
                $isPromo = 1;
                break;
            }
        }
        if ($isPromo) {
            $stats['promo_days']++;
        }
        $clean[] = [
            'date' => $key,
            'qty' => $qty,
            'missing' => $missing,
            'day_name' => $d->format('D'),
            'is_weekend' => $isWeekend,
            'month' => (int) $d->format('n'),
            'is_promo' => $isPromo,
        ];
    }
    $stats['clean_days'] = count($clean);
}

render_header('Data Processing & Cleaning');
?>
<p class="text-muted">
    This screen shows the same cleaning the prediction engine does before training:
    validate rows, fill missing days with 0, then prepare features.
</p>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="processing">
            <div class="col-md-5">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select">
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= $productId === (int) $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Window (days)</label>
                <input type="number" min="14" max="90" name="days" class="form-control" value="<?= (int) $days ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary">Prepare data</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-2"><div class="card card-stat p-3"><div class="stat-label">Raw sale rows</div><div class="stat-value"><?= (int) $stats['raw_rows'] ?></div></div></div>
    <div class="col-6 col-md-2"><div class="card card-stat p-3"><div class="stat-label">Days with sales</div><div class="stat-value"><?= (int) $stats['raw_days'] ?></div></div></div>
    <div class="col-6 col-md-2"><div class="card card-stat p-3"><div class="stat-label">Missing days filled</div><div class="stat-value"><?= (int) $stats['missing_days'] ?></div></div></div>
    <div class="col-6 col-md-2"><div class="card card-stat p-3"><div class="stat-label">Invalid rows skipped</div><div class="stat-value"><?= (int) $stats['invalid_rows'] ?></div></div></div>
    <div class="col-6 col-md-2"><div class="card card-stat p-3"><div class="stat-label">Promo days</div><div class="stat-value"><?= (int) $stats['promo_days'] ?></div></div></div>
    <div class="col-6 col-md-2"><div class="card card-stat p-3"><div class="stat-label">Clean days</div><div class="stat-value"><?= (int) $stats['clean_days'] ?></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">What this step does</div>
            <div class="card-body small">
                <p><strong>Data validation:</strong> quantity must be greater than 0 and the sale must have a date. Bad rows are counted and not used.</p>
                <p><strong>Handle missing data:</strong> days with no sale in the window are filled with quantity 0 so the timeline is complete.</p>
                <p><strong>Data cleaning:</strong> many sales on the same day are added into one daily total.</p>
                <p class="mb-0"><strong>Data preparation:</strong> each day gets features the model can learn from — weekday, weekend flag, month, and whether a promotion was active.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white fw-semibold">
                Prepared series<?= $productName !== '' ? ' — ' . e($productName) : '' ?>
            </div>
            <div class="table-responsive" style="max-height:420px;overflow:auto">
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Clean qty</th>
                        <th>Missing filled?</th>
                        <th>Weekend</th>
                        <th>Month</th>
                        <th>Promo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$clean): ?>
                        <tr><td colspan="7" class="text-muted text-center py-3">No product selected.</td></tr>
                    <?php else: foreach ($clean as $row): ?>
                        <tr class="<?= $row['missing'] ? 'table-warning' : '' ?>">
                            <td><?= e($row['date']) ?></td>
                            <td><?= e($row['day_name']) ?></td>
                            <td><?= e(number_format((float) $row['qty'], 0)) ?></td>
                            <td><?= $row['missing'] ? 'Yes (0)' : 'No' ?></td>
                            <td><?= (int) $row['is_weekend'] ? 'Yes' : 'No' ?></td>
                            <td><?= (int) $row['month'] ?></td>
                            <td><?= (int) $row['is_promo'] ? 'Yes' : 'No' ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
render_footer();
