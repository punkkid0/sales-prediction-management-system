<?php

declare(strict_types=1);

require_login();

$pdo = db();
$user = current_user();

// KPI queries
$today = date('Y-m-d');
$monthStart = date('Y-m-01');

$stmt = $pdo->prepare('SELECT COALESCE(SUM(total),0) AS revenue, COUNT(*) AS cnt FROM sales WHERE sale_date = ?');
$stmt->execute([$today]);
$todayStats = $stmt->fetch();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(total),0) AS revenue, COUNT(*) AS cnt FROM sales WHERE sale_date >= ?');
$stmt->execute([$monthStart]);
$monthStats = $stmt->fetch();

$productCount = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
$customerCount = (int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();

$lowStock = $pdo->query(
    'SELECT id, name, stock_qty, reorder_level
     FROM products
     WHERE is_active = 1 AND stock_qty <= reorder_level
     ORDER BY stock_qty ASC
     LIMIT 10'
)->fetchAll();

$forecastCount = (int) $pdo->query('SELECT COUNT(DISTINCT product_id) FROM forecasts')->fetchColumn();
$latestModelRun = $pdo->query(
    'SELECT model_name, mae, rmse, trained_at FROM model_runs ORDER BY trained_at DESC LIMIT 1'
)->fetch() ?: null;

// Last 14 days chart data
$chartRows = $pdo->query(
    "SELECT sale_date, SUM(total) AS revenue, SUM(quantity) AS qty
     FROM sales
     WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
     GROUP BY sale_date
     ORDER BY sale_date ASC"
)->fetchAll();

$labels = [];
$revenues = [];
$qtys = [];
$map = [];
foreach ($chartRows as $row) {
    $map[$row['sale_date']] = $row;
}
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('M j', strtotime($d));
    $revenues[] = isset($map[$d]) ? (float) $map[$d]['revenue'] : 0;
    $qtys[] = isset($map[$d]) ? (int) $map[$d]['qty'] : 0;
}

// Recent sales
if (user_can('view_all_sales')) {
    $recent = $pdo->query(
        'SELECT s.id, s.sale_date, s.quantity, s.total, p.name AS product_name, u.name AS staff_name
         FROM sales s
         JOIN products p ON p.id = s.product_id
         JOIN users u ON u.id = s.staff_id
         ORDER BY s.created_at DESC
         LIMIT 8'
    )->fetchAll();
} else {
    $stmt = $pdo->prepare(
        'SELECT s.id, s.sale_date, s.quantity, s.total, p.name AS product_name, u.name AS staff_name
         FROM sales s
         JOIN products p ON p.id = s.product_id
         JOIN users u ON u.id = s.staff_id
         WHERE s.staff_id = ?
         ORDER BY s.created_at DESC
         LIMIT 8'
    );
    $stmt->execute([$user['id']]);
    $recent = $stmt->fetchAll();
}

render_header('Dashboard');
?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-stat p-3">
            <div class="stat-label">Today's revenue</div>
            <div class="stat-value"><?= e(money((float) $todayStats['revenue'])) ?></div>
            <div class="small text-muted"><?= (int) $todayStats['cnt'] ?> sale(s)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat p-3">
            <div class="stat-label">This month</div>
            <div class="stat-value"><?= e(money((float) $monthStats['revenue'])) ?></div>
            <div class="small text-muted"><?= (int) $monthStats['cnt'] ?> sale(s)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat p-3">
            <div class="stat-label">Active products</div>
            <div class="stat-value"><?= $productCount ?></div>
            <div class="small text-muted"><?= $customerCount ?> customers</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat p-3">
            <div class="stat-label">Low stock items</div>
            <div class="stat-value text-danger"><?= count($lowStock) ?></div>
            <div class="small text-muted">At or below reorder level</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Sales revenue (last 14 days)</div>
            <div class="card-body">
                <canvas id="salesChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Low stock alerts</div>
            <div class="card-body p-0">
                <?php if (!$lowStock): ?>
                    <p class="text-muted p-3 mb-0">All products are above reorder level.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                            <tr><th>Product</th><th>Stock</th><th>Reorder</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($lowStock as $row): ?>
                                <tr>
                                    <td><?= e($row['name']) ?></td>
                                    <td><span class="badge badge-low"><?= (int) $row['stock_qty'] ?></span></td>
                                    <td><?= (int) $row['reorder_level'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (user_can('view_forecasts')): ?>
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="fw-semibold"><i class="bi bi-lightning-charge text-warning"></i> Sales forecasts</div>
            <div class="small text-muted">
                Products with saved forecasts: <strong><?= $forecastCount ?></strong>
                <?php if ($latestModelRun): ?>
                    · Last train: <?= e($latestModelRun['model_name']) ?>
                    (MAE <?= e((string) $latestModelRun['mae']) ?>)
                <?php endif; ?>
            </div>
        </div>
        <a class="btn btn-primary" href="<?= e(url('forecasts')) ?>">Open Forecasts</a>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Recent sales</span>
        <a href="<?= e(url('sales')) ?>" class="btn btn-sm btn-outline-primary">View all</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Total</th>
                <th>Staff</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$recent): ?>
                <tr><td colspan="7" class="text-muted text-center py-4">No sales yet.</td></tr>
            <?php else: foreach ($recent as $row): ?>
                <tr>
                    <td><?= (int) $row['id'] ?></td>
                    <td><?= e($row['sale_date']) ?></td>
                    <td><?= e($row['product_name']) ?></td>
                    <td><?= (int) $row['quantity'] ?></td>
                    <td><?= e(money((float) $row['total'])) ?></td>
                    <td><?= e($row['staff_name']) ?></td>
                    <td><a href="<?= e(url('receipt', ['id' => $row['id']])) ?>">Receipt</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('salesChart');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($labels) ?>,
      datasets: [{
        label: 'Revenue (₦)',
        data: <?= json_encode($revenues) ?>,
        borderColor: '#1f4e79',
        backgroundColor: 'rgba(31,78,121,0.12)',
        fill: true,
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: true } },
      scales: { y: { beginAtZero: true } }
    }
  });
});
</script>
<?php
render_footer();
