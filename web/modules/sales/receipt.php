<?php

declare(strict_types=1);

require_login();

$id = (int) request('id', 0);
if ($id <= 0) {
    flash('danger', 'Receipt not found.');
    redirect(url('sales'));
}

$stmt = db()->prepare(
    'SELECT s.*, p.name AS product_name, p.category,
            c.name AS customer_name, c.phone AS customer_phone,
            u.name AS staff_name
     FROM sales s
     JOIN products p ON p.id = s.product_id
     LEFT JOIN customers c ON c.id = s.customer_id
     JOIN users u ON u.id = s.staff_id
     WHERE s.id = ?'
);
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    flash('danger', 'Receipt not found.');
    redirect(url('sales'));
}

if (!user_can('view_all_sales') && (int) $sale['staff_id'] !== (int) current_user()['id']) {
    flash('danger', 'You cannot view this receipt.');
    redirect(url('sales'));
}

render_header('Receipt #' . $id);
?>
<div class="no-print mb-3">
    <button class="btn btn-primary" onclick="window.print()">
        <i class="bi bi-printer"></i> Print
    </button>
    <a class="btn btn-outline-secondary" href="<?= e(url('sales_new')) ?>">New sale</a>
    <a class="btn btn-link" href="<?= e(url('sales')) ?>">Back to history</a>
</div>

<div class="receipt-box">
    <div class="text-center mb-3">
        <h2 class="h5 mb-0"><?= e(app_config('name')) ?></h2>
        <div class="text-muted small">Sales Receipt</div>
    </div>
    <hr>
    <div class="d-flex justify-content-between small mb-2">
        <span>Receipt #<?= (int) $sale['id'] ?></span>
        <span><?= e($sale['sale_date']) ?></span>
    </div>
    <div class="small mb-3">
        <div><strong>Staff:</strong> <?= e($sale['staff_name']) ?></div>
        <div><strong>Customer:</strong> <?= e($sale['customer_name'] ?? 'Walk-in') ?></div>
        <?php if (!empty($sale['customer_phone'])): ?>
            <div><strong>Phone:</strong> <?= e($sale['customer_phone']) ?></div>
        <?php endif; ?>
    </div>
    <table class="table table-sm">
        <thead>
        <tr>
            <th>Item</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Price</th>
            <th class="text-end">Total</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                <?= e($sale['product_name']) ?>
                <?php if ($sale['category']): ?>
                    <div class="text-muted small"><?= e($sale['category']) ?></div>
                <?php endif; ?>
            </td>
            <td class="text-end"><?= (int) $sale['quantity'] ?></td>
            <td class="text-end"><?= e(money((float) $sale['unit_price'])) ?></td>
            <td class="text-end"><?= e(money((float) $sale['total'])) ?></td>
        </tr>
        </tbody>
        <tfoot>
        <tr>
            <th colspan="3" class="text-end">Grand total</th>
            <th class="text-end"><?= e(money((float) $sale['total'])) ?></th>
        </tr>
        </tfoot>
    </table>
    <p class="text-center text-muted small mb-0">Thank you for your purchase.</p>
    <p class="text-center text-muted small">Generated <?= e(date('Y-m-d H:i')) ?></p>
</div>
<?php
render_footer();
