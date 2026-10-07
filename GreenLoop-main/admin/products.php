<?php
require __DIR__ . '/../includes/init.php';
require_admin();

if (is_post()) {
    $p = row('SELECT * FROM products WHERE id = ?', [(int) post('id')]);
    $action = post('action');
    if (!$p) {
        flash('ไม่พบสินค้า', 'danger');
    } elseif ($action === 'approve' || $action === 'reject') {
        q('UPDATE products SET status = ? WHERE id = ?', [$action === 'approve' ? 'approved' : 'rejected', $p['id']]);
        flash($action === 'approve' ? 'อนุมัติสินค้าแล้ว' : 'ปฏิเสธสินค้าแล้ว');
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
    back('admin/products.php');
}

$status = isset(STATUS_LABELS[get('status')]) ? get('status') : '';
$kw = get('q');
$where = ' WHERE p.title LIKE ?';
$args = ['%' . addcslashes($kw, '%_\\') . '%'];
if ($status) {
    $where .= ' AND p.status = ?';
    $args[] = $status;
}
$list = rows(PRODUCT_SELECT . $where . " ORDER BY (p.status = 'pending') DESC, p.id DESC LIMIT 200", $args);

$title = 'จัดการสินค้า';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
  <form class="d-flex gap-2" method="get">
    <input class="form-control" name="q" value="<?= e($kw) ?>" placeholder="ค้นหาชื่อสินค้า" aria-label="ค้นหาสินค้า">
    <select class="form-select" name="status" aria-label="สถานะ"><option value="">ทุกสถานะ</option>
      <?php foreach (['pending', 'approved', 'rejected', 'sold', 'hidden'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= STATUS_LABELS[$s][0] ?></option><?php endforeach; ?></select>
    <button class="btn btn-outline-primary text-nowrap">กรอง</button>
  </form>
  <div><a class="btn btn-outline-primary" href="<?= e(url('admin/categories.php')) ?>"><i class="bi bi-grid"></i> หมวดหมู่</a>
    <a class="btn btn-primary" href="<?= e(url('sell.php?from=admin')) ?>"><i class="bi bi-plus-lg"></i> เพิ่มสินค้า</a></div>
</div>
<div class="panel p-0 overflow-hidden">
<?php if ($list): ?>
<div class="table-responsive"><table class="table mb-0">
  <thead><tr><th class="ps-3">สินค้า</th><th>ผู้ขาย</th><th class="text-end">ราคา</th><th>Score</th><th>สถานะ</th><th class="text-end pe-3">จัดการ</th></tr></thead>
  <tbody>
  <?php foreach ($list as $p): ?>
    <tr>
      <td class="ps-3"><div class="d-flex align-items-center gap-2"><a class="thumb-sm" href="<?= e(url('product.php?id=' . $p['id'])) ?>" target="_blank" rel="noopener"><?= product_image($p) ?></a>
        <div><a class="fw-medium text-dark" href="<?= e(url('product.php?id=' . $p['id'])) ?>" target="_blank" rel="noopener"><?= e($p['title']) ?></a>
          <div class="small text-muted"><?= $p['type'] === 'digital' ? 'ดิจิทัล' : 'มือสอง' ?> · <?= e($p['cat_name'] ?: '-') ?> · <?= thai_date($p['created_at'], false) ?><?= $p['type'] === 'digital' && $p['file_name'] ? ' · ไฟล์: ' . e($p['file_name']) : '' ?></div></div></div></td>
      <td class="small"><?= e($p['seller_name']) ?></td>
      <td class="text-end fw-semibold"><?= baht($p['price']) ?></td>
      <td><?= (int) $p['green_score'] ?></td>
      <td><?= badge($p['status']) ?></td>
      <td class="text-end pe-3 text-nowrap">
        <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
          <?php if ($p['status'] !== 'approved' && $p['status'] !== 'sold'): ?><button class="btn btn-sm btn-success" name="action" value="approve"><i class="bi bi-check-lg"></i> อนุมัติ</button><?php endif; ?>
          <?php if ($p['status'] === 'pending' || $p['status'] === 'approved'): ?><button class="btn btn-sm btn-outline-danger" name="action" value="reject" data-confirm="ปฏิเสธสินค้านี้?"><i class="bi bi-x-lg"></i> ปฏิเสธ</button><?php endif; ?>
          <a class="btn btn-sm btn-outline-primary" href="<?= e(url('sell.php?from=admin&id=' . $p['id'])) ?>" title="แก้ไข"><i class="bi bi-pencil"></i></a>
          <button class="btn btn-sm btn-outline-danger" name="action" value="delete" title="ลบ" data-confirm="ลบสินค้านี้?"><i class="bi bi-trash"></i></button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><div class="empty"><i class="bi bi-box-seam"></i>ไม่พบสินค้า</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
