<?php

declare(strict_types=1);

require_login();
require_role(['admin', 'manager']);

$pdo = db();

$from = trim((string) request('from', date('Y-m-01')));
$to = trim((string) request('to', date('Y-m-d')));
$export = request('export') === 'csv';

// Daily summary
$stmt = $pdo->prepare(
    'SELECT sale_date,
            COUNT(*) AS transactions,
            SUM(quantity) AS units,
            SUM(total) AS revenue
     FROM sales
     WHERE sale_date BETWEEN ? AND ?
     GROUP BY sale_date
     ORDER BY sale_date DESC'
);
$stmt->execute([$from, $to]);
$daily = $stmt->fetchAll();

// Product breakdown
$stmt = $pdo->prepare(
    'SELECT p.name,
            SUM(s.quantity) AS units,
            SUM(s.total) AS revenue
     FROM sales s
     JOIN products p ON p.id = s.product_id
     WHERE s.sale_date BETWEEN ? AND ?
     GROUP BY p.id, p.name
     ORDER BY revenue DESC'
);
$stmt->execute([$from, $to]);
$byProduct = $stmt->fetchAll();

// Monthly (for current year range context)
$monthly = $pdo->query(
    "SELECT DATE_FORMAT(sale_date, '%Y-%m') AS ym,
            COUNT(*) AS transactions,
            SUM(total) AS revenue
     FROM sales
     GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
     ORDER BY ym DESC
     LIMIT 12"
)->fetchAll();

if ($export) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sales_report_' . $from . '_to_' . $to . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['sale_date', 'transactions', 'units', 'revenue']);
    foreach ($daily as $row) {
        fputcsv($out, [$row['sale_date'], $row['transactions'], $row['units'], $row['revenue']]);
    }
    fclose($out);
    exit;
}

$chartLabels = array_reverse(array_column($daily, 'sale_date'));
$chartRevenue = array_reverse(array_map(static fn($r) => (float) $r['revenue'], $daily));

render_header('Reports & Visualization');
?>
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="reports">
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>" required>
            </div>
            <div class="col-md-4">
                <button class="btn btn-sm btn-primary">Apply</button>
                <a class="btn btn-sm btn-success"
                   href="<?= e(url('reports', ['from' => $from, 'to' => $to, 'export' => 'csv'])) ?>">
                    Export CSV
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Trends analysis — daily revenue</div>
            <div class="card-body">
                <canvas id="reportChart" height="130"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Performance reports — monthly</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Month</th><th>Txns</th><th>Revenue</th></tr></thead>
                    <tbody>
                    <?php foreach ($monthly as $m): ?>
                        <tr>
                            <td><?= e($m['ym']) ?></td>
                            <td><?= (int) $m['transactions'] ?></td>
                            <td><?= e(money((float) $m['revenue'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Performance reports — daily summary</div>
            <div class="table-responsive" style="max-height:360px;overflow:auto">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Date</th><th>Txns</th><th>Units</th><th>Revenue</th></tr></thead>
                    <tbody>
                    <?php if (!$daily): ?>
                        <tr><td colspan="4" class="text-muted text-center py-3">No data in range.</td></tr>
                    <?php else: foreach ($daily as $d): ?>
                        <tr>
                            <td><?= e($d['sale_date']) ?></td>
                            <td><?= (int) $d['transactions'] ?></td>
                            <td><?= (int) $d['units'] ?></td>
                            <td><?= e(money((float) $d['revenue'])) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Trends analysis — by product</div>
            <div class="table-responsive" style="max-height:360px;overflow:auto">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Product</th><th>Units</th><th>Revenue</th></tr></thead>
                    <tbody>
                    <?php if (!$byProduct): ?>
                        <tr><td colspan="3" class="text-muted text-center py-3">No data in range.</td></tr>
                    <?php else: foreach ($byProduct as $p): ?>
                        <tr>
                            <td><?= e($p['name']) ?></td>
                            <td><?= (int) $p['units'] ?></td>
                            <td><?= e(money((float) $p['revenue'])) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('reportChart');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($chartLabels) ?>,
      datasets: [{
        label: 'Revenue (₦)',
        data: <?= json_encode($chartRevenue) ?>,
        backgroundColor: 'rgba(46,117,182,0.7)'
      }]
    },
    options: {
      responsive: true,
      scales: { y: { beginAtZero: true } }
    }
  });
});
</script>
<?php
render_footer();
