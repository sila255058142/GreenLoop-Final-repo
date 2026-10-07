<?php
require __DIR__ . '/includes/init.php';
$u = require_login();
$promotePrice = (float) setting('promote_price', 29) * (is_premium($u) ? 0.5 : 1);

if (is_post()) {
    $p = row('SELECT * FROM products WHERE id = ? AND user_id = ?', [(int) post('id'), $u['id']]);
    $action = post('action');
    if (!$p) {
        flash('ไม่พบสินค้า', 'danger');
    } elseif ($action === 'promote') {
        if ($p['status'] !== 'approved') {
            flash('โปรโมทได้เฉพาะสินค้าที่อนุมัติแล้ว', 'warning');
        } else {
            $ok = in_tx(function () use ($u, $p, $promotePrice) {
                if (!wallet_move($u['id'], -$promotePrice, 'promote', 'โปรโมทสินค้า ' . $p['title'] . ' ' . PROMOTE_DAYS . ' วัน')) {
                    return false;
                }
                q('UPDATE products SET promoted_until = DATE_ADD(GREATEST(COALESCE(promoted_until, NOW()), NOW()), INTERVAL ' . PROMOTE_DAYS . ' DAY) WHERE id = ?', [$p['id']]);
                return true;
            });
            $ok ? flash('โปรโมทสินค้าเรียบร้อย สินค้าจะแสดงอันดับต้นๆ ' . PROMOTE_DAYS . ' วัน') : flash('ยอดเงินใน Wallet ไม่เพียงพอ กรุณาเติมเงิน', 'danger');
        }
    } elseif ($action === 'toggle' && in_array($p['status'], ['approved', 'hidden'], true)) {
        q('UPDATE products SET status = ? WHERE id = ?', [$p['status'] === 'approved' ? 'hidden' : 'approved', $p['id']]);
        flash($p['status'] === 'approved' ? 'ซ่อนสินค้าแล้ว' : 'แสดงสินค้าแล้ว');
    } elseif ($action === 'delete') {
        try {
            q('DELETE FROM products WHERE id = ?', [$p['id']]);
            foreach ([$p['image'], $p['file_path']] as $f) {
                if ($f && is_file(ROOT_PATH . '/' . $f)) {
                    unlink(ROOT_PATH . '/' . $f);
                }
            }
            flash('ลบสินค้าแล้ว');
        } catch (PDOException $ex) {
            q("UPDATE products SET status = 'hidden' WHERE id = ?", [$p['id']]);
            flash('สินค้านี้มีประวัติคำสั่งซื้อจึงลบไม่ได้ ระบบซ่อนสินค้าให้แทน', 'warning');
        }
    }
    redirect('my-products.php');
}

$list = rows(PRODUCT_SELECT . ' WHERE p.user_id = ? ORDER BY p.created_at DESC, p.id DESC', [$u['id']]);
$title = 'สินค้าของฉัน';
require __DIR__ . '/includes/header.php';
?>
<div class="section-head">
  <h1 class="pill-title">จัดการสินค้าของฉัน</h1>
  <a class="btn btn-primary" href="<?= e(url('sell.php')) ?>"><i class="bi bi-plus-lg"></i> เพิ่มสินค้า</a>
</div>
<div class="panel p-0 overflow-hidden">
<?php if ($list): ?>
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th class="ps-3">สินค้า</th><th>ราคา</th><th>คงเหลือ</th><th>สถานะ</th><th>เข้าชม</th><th class="text-end pe-3">จัดการ</th></tr></thead>
    <tbody>
    <?php foreach ($list as $p): $promoted = $p['promoted_until'] && strtotime($p['promoted_until']) > time(); ?>
      <tr>
        <td class="ps-3"><div class="d-flex align-items-center gap-2">
          <a class="thumb-sm" href="<?= e(url('product.php?id=' . $p['id'])) ?>"><?= product_image($p) ?></a>
          <div><a class="fw-medium text-dark" href="<?= e(url('product.php?id=' . $p['id'])) ?>"><?= e($p['title']) ?></a>
            <div class="small text-muted"><?= $p['type'] === 'digital' ? 'ดิจิทัล' : 'มือสอง' ?> · <?= e($p['cat_name'] ?: '-') ?>
              <?php if ($promoted): ?> · <span class="text-warning"><i class="bi bi-megaphone-fill"></i> โปรโมทถึง <?= thai_date($p['promoted_until'], false) ?></span><?php endif; ?></div></div>
        </div></td>
        <td class="fw-semibold"><?= baht($p['price']) ?></td>
        <td><?= $p['type'] === 'digital' ? '∞' : (int) $p['stock'] ?></td>
        <td><?= badge($p['status']) ?></td>
        <td><?= number_format($p['views']) ?></td>
        <td class="text-end pe-3 text-nowrap">
          <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
            <?php if ($p['status'] === 'approved'): ?><button class="btn btn-sm btn-warning" name="action" value="promote" data-confirm="โปรโมทสินค้านี้ <?= PROMOTE_DAYS ?> วัน หัก Wallet <?= baht($promotePrice) ?> ?"><i class="bi bi-megaphone"></i> โปรโมท <?= baht($promotePrice) ?></button><?php endif; ?>
            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('sell.php?id=' . $p['id'])) ?>" title="แก้ไข"><i class="bi bi-pencil"></i></a>
            <?php if (in_array($p['status'], ['approved', 'hidden'], true)): ?><button class="btn btn-sm btn-outline-secondary" name="action" value="toggle" title="<?= $p['status'] === 'approved' ? 'ซ่อน' : 'แสดง' ?>"><i class="bi bi-eye<?= $p['status'] === 'approved' ? '-slash' : '' ?>"></i></button><?php endif; ?>
            <button class="btn btn-sm btn-outline-danger" name="action" value="delete" title="ลบ" data-confirm="ลบสินค้านี้?"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php else: ?>
  <div class="empty"><i class="bi bi-tags"></i>คุณยังไม่มีสินค้า<br><a class="btn btn-primary mt-3" href="<?= e(url('sell.php')) ?>">ลงขายสินค้าชิ้นแรก</a></div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
