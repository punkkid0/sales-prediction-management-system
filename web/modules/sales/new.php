<?php

declare(strict_types=1);

require_login();
require_role(['admin', 'manager', 'staff']);

$pdo = db();

if (is_post()) {
    verify_csrf();
    $productId = (int) request('product_id');
    $customerId = request('customer_id') !== '' ? (int) request('customer_id') : null;
    $quantity = (int) request('quantity');
    $saleDate = (string) request('sale_date', date('Y-m-d'));
    $staffId = (int) current_user()['id'];

    if ($productId <= 0 || $quantity <= 0 || $saleDate === '') {
        flash('danger', 'Product, quantity and sale date are required.');
        redirect(url('sales_new'));
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT id, name, unit_price, stock_qty FROM products WHERE id = ? AND is_active = 1 FOR UPDATE'
        );
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new RuntimeException('Product not found or inactive.');
        }
        if ((int) $product['stock_qty'] < $quantity) {
            throw new RuntimeException(
                'Insufficient stock. Available: ' . (int) $product['stock_qty'] . '.'
            );
        }

        $unitPrice = (float) $product['unit_price'];
        $total = $unitPrice * $quantity;

        $ins = $pdo->prepare(
            'INSERT INTO sales (product_id, customer_id, staff_id, quantity, unit_price, total, sale_date)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$productId, $customerId, $staffId, $quantity, $unitPrice, $total, $saleDate]);
        $saleId = (int) $pdo->lastInsertId();

        $upd = $pdo->prepare('UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?');
        $upd->execute([$quantity, $productId]);

        $pdo->commit();
        flash('success', 'Sale recorded successfully.');
        redirect(url('receipt', ['id' => $saleId]));
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('danger', $e->getMessage());
        redirect(url('sales_new'));
    }
}

$products = $pdo->query(
    'SELECT id, name, unit_price, stock_qty FROM products WHERE is_active = 1 ORDER BY name'
)->fetchAll();
$customers = $pdo->query('SELECT id, name FROM customers ORDER BY name')->fetchAll();

render_header('New Sale');
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Point of Sale</div>
            <div class="card-body">
                <form method="post" id="saleForm">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <select name="product_id" id="product_id" class="form-select" required>
                            <option value="">— Select product —</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= (int) $p['id'] ?>"
                                        data-price="<?= e((string) $p['unit_price']) ?>"
                                        data-stock="<?= (int) $p['stock_qty'] ?>">
                                    <?= e($p['name']) ?> — <?= e(money((float) $p['unit_price'])) ?>
                                    (stock: <?= (int) $p['stock_qty'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select">
                            <option value="">— Walk-in / none —</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Quantity</label>
                            <input type="number" min="1" name="quantity" id="quantity" class="form-control" value="1" required>
                            <div class="form-text">Available: <span id="stockHint">—</span></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unit price</label>
                            <input type="text" id="unit_price_display" class="form-control" readonly value="—">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total</label>
                            <input type="text" id="total_display" class="form-control fw-bold" readonly value="—">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sale date</label>
                        <input type="date" name="sale_date" class="form-control" value="<?= e(date('Y-m-d')) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle"></i> Record sale
                    </button>
                    <a href="<?= e(url('sales')) ?>" class="btn btn-link">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
  const select = document.getElementById('product_id');
  const qty = document.getElementById('quantity');
  const priceEl = document.getElementById('unit_price_display');
  const totalEl = document.getElementById('total_display');
  const stockHint = document.getElementById('stockHint');

  function fmt(n) {
    return '₦' + Number(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
  }

  function update() {
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) {
      priceEl.value = '—';
      totalEl.value = '—';
      stockHint.textContent = '—';
      return;
    }
    const price = parseFloat(opt.dataset.price || '0');
    const stock = parseInt(opt.dataset.stock || '0', 10);
    const q = parseInt(qty.value || '0', 10);
    priceEl.value = fmt(price);
    totalEl.value = q > 0 ? fmt(price * q) : '—';
    stockHint.textContent = stock;
    qty.max = stock;
  }

  select.addEventListener('change', update);
  qty.addEventListener('input', update);
  update();
})();
</script>
<?php
render_footer();
