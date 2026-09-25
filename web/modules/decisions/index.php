<?php

declare(strict_types=1);

require_login();
require_role(['admin', 'manager']);

$pdo = db();

$rows = $pdo->query(
    "SELECT p.id, p.name, p.stock_qty, p.reorder_level, p.unit_price,
            COALESCE(f.pred_sum, 0) AS predicted_demand,
            COALESCE(a.actual_7, 0) AS actual_7,
            COALESCE(a.revenue_7, 0) AS revenue_7
     FROM products p
     LEFT JOIN (
         SELECT f1.product_id, SUM(f1.predicted_qty) AS pred_sum
         FROM forecasts f1
         INNER JOIN (
             SELECT product_id, MAX(generated_at) AS max_gen
             FROM forecasts
             GROUP BY product_id
         ) latest ON latest.product_id = f1.product_id AND latest.max_gen = f1.generated_at
         GROUP BY f1.product_id
     ) f ON f.product_id = p.id
     LEFT JOIN (
         SELECT product_id,
                SUM(quantity) AS actual_7,
                SUM(total) AS revenue_7
         FROM sales
         WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY product_id
     ) a ON a.product_id = p.id
     WHERE p.is_active = 1
     ORDER BY (COALESCE(f.pred_sum, 0) - p.stock_qty) DESC, p.name"
)->fetchAll();

$orderCount = 0;
$okCount = 0;
$lowStatic = 0;
foreach ($rows as $r) {
    $demand = (float) $r['predicted_demand'];
    $stock = (int) $r['stock_qty'];
    if ($demand > 0 && $demand > $stock) {
        $orderCount++;
    } elseif ($demand > 0) {
        $okCount++;
    }
    if ($stock <= (int) $r['reorder_level']) {
        $lowStatic++;
    }
}

render_header('Decision Support & Inventory Planning');
?>
<p class="text-muted">
    This page turns sales and forecasts into plain recommendations: order more, or stock is enough.
</p>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card card-stat p-3">
            <div class="stat-label">Sales insight</div>
            <div class="stat-value"><?= count($rows) ?></div>
            <div class="small text-muted">Active products reviewed</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-stat p-3">
            <div class="stat-label">Recommendations to order</div>
            <div class="stat-value text-danger"><?= $orderCount ?></div>
            <div class="small text-muted"><?= $okCount ?> products look covered by stock</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-stat p-3">
            <div class="stat-label">Below reorder level</div>
            <div class="stat-value"><?= $lowStatic ?></div>
            <div class="small text-muted">Static stock warning, separate from the forecast</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold">Business decision support</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
            <tr>
                <th>Product</th>
                <th>Stock</th>
                <th>Reorder level</th>
                <th>Sold last 7 days</th>
                <th>Predicted demand</th>
                <th>Recommendation</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6" class="text-muted text-center py-4">No products.</td></tr>
            <?php else: foreach ($rows as $r):
                $stock = (int) $r['stock_qty'];
                $level = (int) $r['reorder_level'];
                $demand = (float) $r['predicted_demand'];
                $actual = (int) $r['actual_7'];
                $suggest = 0;
                if ($demand > $stock) {
                    $suggest = (int) ceil($demand - $stock);
                }
                if ($demand <= 0) {
                    $rec = 'No forecast yet. Run a prediction before ordering from the model.';
                    $badge = 'secondary';
                } elseif ($suggest > 0) {
                    $rec = 'Order about ' . $suggest . ' units. Forecast demand is higher than stock.';
                    $badge = 'danger';
                } else {
                    $rec = 'Stock is enough for the current forecast. No order needed from the model.';
                    $badge = 'success';
                }
                if ($stock <= $level) {
                    $rec .= ' Also below the static reorder level.';
                }
                ?>
                <tr>
                    <td><?= e($r['name']) ?></td>
                    <td><?= $stock ?></td>
                    <td><?= $level ?></td>
                    <td><?= $actual ?></td>
                    <td><?= e(number_format($demand, 1)) ?></td>
                    <td>
                        <span class="badge text-bg-<?= e($badge) ?> mb-1"><?= $suggest > 0 ? 'Order ' . $suggest : ($demand <= 0 ? 'Need forecast' : 'Stock OK') ?></span>
                        <div class="small"><?= e($rec) ?></div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer small text-muted">
        Predicted demand uses the latest saved forecast. Sold last 7 days is actual history, for comparison.
        <a href="<?= e(url('forecasts')) ?>">Open forecasting</a> to refresh a product.
    </div>
</div>
<?php
render_footer();
