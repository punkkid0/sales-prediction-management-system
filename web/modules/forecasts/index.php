<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/ml_client.php';

require_login();
require_role(['admin', 'manager']);

$pdo = db();
$horizon = max(1, min(30, (int) request('horizon', 7)));
$productId = request('product_id') !== null && request('product_id') !== ''
    ? (int) request('product_id')
    : 0;
$model = (string) request('model', 'lstm');
if (!in_array($model, ['lstm', 'linear', 'both'], true)) {
    $model = 'lstm';
}

$predictResult = null;
$predictError = null;
$retrainResult = null;
$retrainError = null;

// Health check (non-blocking for page load if down — show banner)
$health = ml_health();
$apiUp = $health['ok'] === true;

// Handle actions
if (is_post()) {
    verify_csrf();
    $action = (string) request('action', '');

    if ($action === 'predict') {
        $productId = (int) request('product_id');
        $horizon = max(1, min(30, (int) request('horizon', 7)));
        $model = (string) request('model', 'lstm');
        if ($productId <= 0) {
            flash('danger', 'Please select a product.');
            redirect(url('forecasts'));
        }
        $resp = ml_predict($productId, $horizon, $model, true);
        if (!$resp['ok']) {
            flash('danger', $resp['error'] ?? 'Prediction failed.');
            redirect(url('forecasts', ['product_id' => $productId, 'horizon' => $horizon, 'model' => $model]));
        }
        flash('success', 'Forecast generated and saved.');
        redirect(url('forecasts', ['product_id' => $productId, 'horizon' => $horizon, 'model' => $model]));
    }

    if ($action === 'retrain') {
        if (!user_can('retrain_model')) {
            flash('danger', 'Only admins can retrain models.');
            redirect(url('forecasts'));
        }
        $productId = (int) request('product_id');
        $model = (string) request('model', 'both');
        $resp = ml_retrain($productId > 0 ? $productId : null, $model === 'linear' || $model === 'lstm' ? $model : 'both');
        if (!$resp['ok']) {
            flash('danger', $resp['error'] ?? 'Retrain failed.');
        } else {
            flash('success', 'Model retrain finished. Generate a new forecast to use updated weights.');
        }
        redirect(url('forecasts', [
            'product_id' => $productId > 0 ? $productId : null,
            'horizon' => $horizon,
            'model' => $model === 'both' ? 'lstm' : $model,
        ]));
    }
}

// Products list
$products = $pdo->query(
    'SELECT id, name, stock_qty, reorder_level, unit_price
     FROM products WHERE is_active = 1 ORDER BY name'
)->fetchAll();

// If no product selected, default to first
if ($productId <= 0 && $products) {
    $productId = (int) $products[0]['id'];
}

$selectedProduct = null;
foreach ($products as $p) {
    if ((int) $p['id'] === $productId) {
        $selectedProduct = $p;
        break;
    }
}

// Historical daily qty (last 45 days) for chart
$historyLabels = [];
$historyQty = [];
if ($productId > 0) {
    $stmt = $pdo->prepare(
        "SELECT sale_date, SUM(quantity) AS qty
         FROM sales
         WHERE product_id = ? AND sale_date >= DATE_SUB(CURDATE(), INTERVAL 44 DAY)
         GROUP BY sale_date
         ORDER BY sale_date ASC"
    );
    $stmt->execute([$productId]);
    $histRows = $stmt->fetchAll();
    $map = [];
    foreach ($histRows as $r) {
        $map[$r['sale_date']] = (float) $r['qty'];
    }
    for ($i = 44; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $historyLabels[] = $d;
        $historyQty[] = $map[$d] ?? 0;
    }
}

// Latest saved forecasts for product
$forecasts = [];
$forecastMeta = null;
if ($productId > 0) {
    $stmt = $pdo->prepare(
        'SELECT forecast_date, predicted_qty, model_used, mae, rmse, horizon_days, generated_at
         FROM forecasts
         WHERE product_id = ?
         ORDER BY generated_at DESC, forecast_date ASC
         LIMIT 60'
    );
    $stmt->execute([$productId]);
    $allFc = $stmt->fetchAll();

    // Keep only the latest generation batch (same generated_at + model)
    if ($allFc) {
        $latestGen = $allFc[0]['generated_at'];
        $latestModel = $allFc[0]['model_used'];
        foreach ($allFc as $row) {
            if ($row['generated_at'] === $latestGen && $row['model_used'] === $latestModel) {
                $forecasts[] = $row;
            }
        }
        if ($forecasts) {
            $forecastMeta = [
                'model_used' => $forecasts[0]['model_used'],
                'mae' => $forecasts[0]['mae'],
                'rmse' => $forecasts[0]['rmse'],
                'horizon_days' => $forecasts[0]['horizon_days'],
                'generated_at' => $forecasts[0]['generated_at'],
            ];
        }
    }
}

$forecastLabels = array_map(static fn($r) => $r['forecast_date'], $forecasts);
$forecastQty = array_map(static fn($r) => (float) $r['predicted_qty'], $forecasts);
$predictedDemand = array_sum($forecastQty);

// Reorder suggestion
$reorder = null;
if ($selectedProduct && $predictedDemand > 0) {
    $stock = (int) $selectedProduct['stock_qty'];
    $gap = $predictedDemand - $stock;
    $reorder = [
        'stock' => $stock,
        'predicted_demand' => round($predictedDemand, 2),
        'suggested_qty' => $gap > 0 ? (int) ceil($gap) : 0,
        'status' => $gap > 0 ? 'reorder' : 'ok',
        'reorder_level' => (int) $selectedProduct['reorder_level'],
    ];
}

// Reorder list across products (using latest forecasts sum vs stock)
$reorderList = $pdo->query(
    "SELECT p.id, p.name, p.stock_qty, p.reorder_level,
            COALESCE(f.pred_sum, 0) AS predicted_demand
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
     WHERE p.is_active = 1
     ORDER BY (COALESCE(f.pred_sum, 0) - p.stock_qty) DESC
     LIMIT 12"
)->fetchAll();

// Recent model runs
$modelRuns = $pdo->query(
    'SELECT model_name, mae, rmse, trained_at, notes
     FROM model_runs
     ORDER BY trained_at DESC
     LIMIT 8'
)->fetchAll();

render_header('Forecasts');
?>

<?php if (!$apiUp): ?>
    <div class="alert alert-warning">
        <strong>Prediction service is offline.</strong>
        <?= e($health['error'] ?? 'Start Flask: open a terminal in <code>ml_service</code> and run <code>python app.py</code>.') ?>
        You can still view saved forecasts below.
    </div>
<?php else: ?>
    <div class="alert alert-success py-2">
        Prediction engine online
        <span class="text-muted small">(<?= e((string) ($health['data']['service'] ?? 'ok')) ?>)</span>
    </div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Generate forecast</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="predict">
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-select" required>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= (int) $p['id'] ?>" <?= $productId === (int) $p['id'] ? 'selected' : '' ?>>
                                    <?= e($p['name']) ?> (stock: <?= (int) $p['stock_qty'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Model</label>
                        <select name="model" class="form-select">
                            <option value="lstm" <?= $model === 'lstm' ? 'selected' : '' ?>>LSTM (main)</option>
                            <option value="linear" <?= $model === 'linear' ? 'selected' : '' ?>>Linear Regression (baseline)</option>
                        </select>
                        <div class="form-text">LSTM = sequence memory model. Linear = simple baseline.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Horizon (days)</label>
                        <input type="number" min="1" max="30" name="horizon" class="form-control"
                               value="<?= (int) $horizon ?>">
                    </div>
                    <button class="btn btn-primary w-100" <?= $apiUp ? '' : 'disabled' ?>>
                        <i class="bi bi-lightning-charge"></i> Run prediction
                    </button>
                </form>

                <?php if (user_can('retrain_model')): ?>
                <hr>
                <form method="post" onsubmit="return confirm('Retrain can take a minute. Continue?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="retrain">
                    <input type="hidden" name="product_id" value="<?= (int) $productId ?>">
                    <input type="hidden" name="model" value="both">
                    <button class="btn btn-outline-warning w-100" <?= $apiUp ? '' : 'disabled' ?>>
                        <i class="bi bi-arrow-repeat"></i> Update model (retrain)
                    </button>
                    <div class="form-text mt-1">Admin only. Retrains LR + LSTM for this product using latest sales.</div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    History vs forecast
                    <?php if ($selectedProduct): ?>
                        — <?= e($selectedProduct['name']) ?>
                    <?php endif; ?>
                </span>
                <?php if ($forecastMeta): ?>
                    <span class="small text-muted">
                        <?= e($forecastMeta['model_used']) ?>
                        · MAE <?= e((string) $forecastMeta['mae']) ?>
                        · RMSE <?= e((string) $forecastMeta['rmse']) ?>
                        · <?= e(substr((string) $forecastMeta['generated_at'], 0, 16)) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$productId): ?>
                    <p class="text-muted mb-0">No products available.</p>
                <?php else: ?>
                    <canvas id="forecastChart" height="120"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Inventory suggestion</div>
            <div class="card-body">
                <?php if (!$reorder): ?>
                    <p class="text-muted mb-0">Run a forecast to see reorder advice for this product.</p>
                <?php else: ?>
                    <div class="mb-2"><span class="text-muted">Current stock:</span> <strong><?= (int) $reorder['stock'] ?></strong></div>
                    <div class="mb-2"><span class="text-muted">Predicted demand (horizon):</span> <strong><?= e((string) $reorder['predicted_demand']) ?></strong></div>
                    <div class="mb-2"><span class="text-muted">Static reorder level:</span> <?= (int) $reorder['reorder_level'] ?></div>
                    <?php if ($reorder['status'] === 'reorder'): ?>
                        <div class="alert alert-danger mb-0">
                            Stock may not cover predicted demand.
                            <div class="fs-5 fw-bold mt-1">Suggest order: <?= (int) $reorder['suggested_qty'] ?> units</div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mb-0">
                            Stock looks enough for the forecast horizon
                            (surplus ≈ <?= e((string) round($reorder['stock'] - $reorder['predicted_demand'], 1)) ?>).
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Saved forecast rows</div>
            <div class="table-responsive" style="max-height:260px;overflow:auto">
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Predicted qty</th>
                        <th>Model</th>
                        <th>MAE</th>
                        <th>RMSE</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$forecasts): ?>
                        <tr><td colspan="5" class="text-muted text-center py-3">No saved forecasts yet for this product.</td></tr>
                    <?php else: foreach ($forecasts as $f): ?>
                        <tr>
                            <td><?= e($f['forecast_date']) ?></td>
                            <td><?= e(number_format((float) $f['predicted_qty'], 2)) ?></td>
                            <td><?= e($f['model_used']) ?></td>
                            <td><?= e((string) $f['mae']) ?></td>
                            <td><?= e((string) $f['rmse']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Reorder watchlist (forecast demand vs stock)</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Product</th>
                        <th>Stock</th>
                        <th>Predicted demand</th>
                        <th>Gap</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reorderList as $row):
                        $gap = (float) $row['predicted_demand'] - (int) $row['stock_qty'];
                        ?>
                        <tr>
                            <td><?= e($row['name']) ?></td>
                            <td><?= (int) $row['stock_qty'] ?></td>
                            <td><?= e(number_format((float) $row['predicted_demand'], 2)) ?></td>
                            <td>
                                <?php if ($gap > 0): ?>
                                    <span class="badge text-bg-danger">+<?= e(number_format($gap, 1)) ?> short</span>
                                <?php else: ?>
                                    <span class="badge text-bg-success">OK</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= e(url('forecasts', ['product_id' => $row['id'], 'horizon' => $horizon, 'model' => $model])) ?>">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer small text-muted">
                Predicted demand uses the latest saved forecast batch per product. Run predictions to refresh.
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Recent model training scores</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                    <tr><th>Model</th><th>MAE</th><th>RMSE</th><th>When</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!$modelRuns): ?>
                        <tr><td colspan="4" class="text-muted text-center py-3">No training runs logged yet.</td></tr>
                    <?php else: foreach ($modelRuns as $m): ?>
                        <tr>
                            <td><?= e($m['model_name']) ?></td>
                            <td><?= e((string) $m['mae']) ?></td>
                            <td><?= e((string) $m['rmse']) ?></td>
                            <td class="small"><?= e(substr((string) $m['trained_at'], 0, 16)) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer small text-muted">
                Lower MAE / RMSE is better. Compare linear_regression vs lstm for the same product.
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('forecastChart');
  if (!ctx) return;

  const historyLabels = <?= json_encode($historyLabels) ?>;
  const historyQty = <?= json_encode($historyQty) ?>;
  const forecastLabels = <?= json_encode($forecastLabels) ?>;
  const forecastQty = <?= json_encode($forecastQty) ?>;

  // Build combined label axis: history then future forecast dates not already in history
  const labels = historyLabels.slice();
  forecastLabels.forEach(d => {
    if (!labels.includes(d)) labels.push(d);
  });

  const histData = labels.map(d => {
    const i = historyLabels.indexOf(d);
    return i >= 0 ? historyQty[i] : null;
  });
  const fcData = labels.map(d => {
    const i = forecastLabels.indexOf(d);
    return i >= 0 ? forecastQty[i] : null;
  });

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels.map(d => {
        // shorter tick labels
        const p = d.split('-');
        return p.length === 3 ? (p[1] + '/' + p[2]) : d;
      }),
      datasets: [
        {
          label: 'Actual sales (history)',
          data: histData,
          borderColor: '#1f4e79',
          backgroundColor: 'rgba(31,78,121,0.08)',
          spanGaps: false,
          tension: 0.25,
          pointRadius: 2
        },
        {
          label: 'Predicted sales',
          data: fcData,
          borderColor: '#e67e22',
          borderDash: [6, 4],
          backgroundColor: 'rgba(230,126,34,0.08)',
          spanGaps: false,
          tension: 0.25,
          pointRadius: 3
        }
      ]
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: true },
        tooltip: { enabled: true }
      },
      scales: {
        y: { beginAtZero: true, title: { display: true, text: 'Units' } }
      }
    }
  });
});
</script>
<?php
render_footer();
