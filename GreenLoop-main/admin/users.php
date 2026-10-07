<?php
require __DIR__ . '/../includes/init.php';
$admin = require_admin();

if (is_post()) {
    $action = post('action');
    $id = (int) post('id');
    $target = $id ? row('SELECT * FROM users WHERE id = ?', [$id]) : null;
    if ($action === 'save') {
        $name = post('name');
        $email = post('email');
        $pass = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $role = post('role') === 'admin' ? 'admin' : 'user';
        if ($target && $target['id'] == $admin['id']) {
            $role = 'admin';
        }
        if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('กรุณากรอกชื่อและอีเมลให้ถูกต้อง', 'danger');
        } elseif (val('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$email, $id])) {
            flash('อีเมลนี้ถูกใช้งานแล้ว', 'danger');
        } elseif ((!$target || $pass !== '') && strlen($pass) < 8) {
            flash('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร', 'danger');
        } elseif ($target) {
            q('UPDATE users SET name = ?, email = ?, phone = ?, address = ?, role = ? WHERE id = ?', [$name, $email, mb_substr(post('phone'), 0, 30), post('address'), $role, $id]);
            if ($pass !== '') {
                q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $id]);
            }
            flash('บันทึกข้อมูลสมาชิกแล้ว');
        } else {
            q('INSERT INTO users (name, email, password_hash, phone, address, role) VALUES (?,?,?,?,?,?)', [$name, $email, password_hash($pass, PASSWORD_DEFAULT), mb_substr(post('phone'), 0, 30), post('address'), $role]);
            flash('เพิ่มสมาชิกแล้ว');
        }
    } elseif (!$target || $target['id'] == $admin['id']) {
        flash('ไม่สามารถทำรายการกับบัญชีนี้ได้', 'warning');
    } elseif ($action === 'toggle') {
        q('UPDATE users SET status = ? WHERE id = ?', [$target['status'] === 'active' ? 'suspended' : 'active', $id]);
        flash($target['status'] === 'active' ? 'ระงับบัญชีแล้ว' : 'ยกเลิกการระงับบัญชีแล้ว');
    } elseif ($action === 'delete') {
        try {
            q('DELETE FROM users WHERE id = ?', [$id]);
            flash('ลบสมาชิกแล้ว');
        } catch (PDOException $ex) {
            flash('สมาชิกนี้มีประวัติคำสั่งซื้อจึงลบไม่ได้ กรุณาใช้การระงับบัญชีแทน', 'warning');
        }
    }
    redirect('admin/users.php');
}

$view = (int) get('view') ? row('SELECT * FROM users WHERE id = ?', [(int) get('view')]) : null;
$edit = (int) get('edit') ? row('SELECT * FROM users WHERE id = ?', [(int) get('edit')]) : null;
$adding = get('add') !== '';
$kw = get('q');
$like = '%' . addcslashes($kw, '%_\\') . '%';
$list = rows('SELECT u.*, (SELECT COUNT(*) FROM products WHERE user_id = u.id) AS products,
    (SELECT COUNT(*) FROM orders WHERE buyer_id = u.id OR seller_id = u.id) AS orders
    FROM users u WHERE u.name LIKE ? OR u.email LIKE ? ORDER BY u.id DESC', [$like, $like]);

$title = 'จัดการสมาชิก';
require __DIR__ . '/../includes/admin_header.php';
?>
<?php if ($view):
    $orders = rows('SELECT * FROM orders WHERE buyer_id = ? OR seller_id = ? ORDER BY id DESC LIMIT 30', [$view['id'], $view['id']]);
    $wtx = rows('SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 30', [$view['id']]);
    $ptx = rows('SELECT * FROM point_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 30', [$view['id']]);
?>
<a class="btn btn-light btn-sm mb-3" href="<?= e(url('admin/users.php')) ?>"><i class="bi bi-arrow-left"></i> กลับ</a>
<div class="panel mb-3 d-flex flex-wrap gap-3 align-items-center">
  <?= avatar_html($view, 64) ?>
  <div class="flex-grow-1"><div class="h5 mb-0"><?= e($view['name']) ?><?= premium_badge($view) ?> <?= badge($view['status']) ?></div>
    <div class="text-muted small"><?= e($view['email']) ?> · <?= e($view['phone'] ?: '-') ?> · สมัครเมื่อ <?= thai_date($view['created_at'], false) ?></div>
    <div class="small"><?= e($view['address']) ?></div></div>
  <div class="text-end"><div>Wallet <b><?= baht($view['wallet_balance']) ?></b></div><div>Green Point <b><?= number_format($view['green_points']) ?></b></div></div>
</div>
<div class="row g-3">
  <div class="col-xl-12"><div class="panel">
    <div class="panel-title"><i class="bi bi-receipt"></i> ประวัติคำสั่งซื้อ / ขาย</div>
    <?php if ($orders): ?><div class="table-responsive"><table class="table table-sm mb-0">
      <thead><tr><th>วันที่</th><th>เลขที่</th><th>บทบาท</th><th>สินค้า</th><th class="text-end">ราคา</th><th>สถานะ</th></tr></thead>
      <tbody><?php foreach ($orders as $o): ?><tr><td class="small"><?= thai_date($o['created_at']) ?></td><td class="small">#<?= e($o['order_no']) ?></td><td><?= $o['buyer_id'] == $view['id'] ? 'ผู้ซื้อ' : 'ผู้ขาย' ?></td><td><?= e($o['product_title']) ?></td><td class="text-end"><?= baht($o['price']) ?></td><td><?= badge($o['status']) ?></td></tr><?php endforeach; ?></tbody>
    </table></div><?php else: ?><div class="text-muted small">ไม่มีรายการ</div><?php endif; ?>
  </div></div>
  <div class="col-xl-6"><div class="panel h-100">
    <div class="panel-title"><i class="bi bi-wallet2"></i> ประวัติกระเป๋าเงิน</div>
    <?php if ($wtx): ?><table class="table table-sm mb-0"><tbody><?php foreach ($wtx as $t): ?><tr><td class="small text-nowrap"><?= thai_date($t['created_at']) ?></td><td class="small"><?= e($t['note']) ?></td><td class="text-end text-nowrap <?= $t['amount'] < 0 ? 'text-danger' : 'text-success' ?>"><?= ($t['amount'] < 0 ? '-' : '+') . baht(abs($t['amount'])) ?></td></tr><?php endforeach; ?></tbody></table>
    <?php else: ?><div class="text-muted small">ไม่มีรายการ</div><?php endif; ?>
  </div></div>
  <div class="col-xl-6"><div class="panel h-100">
    <div class="panel-title"><i class="bi bi-award"></i> ประวัติ Green Point</div>
    <?php if ($ptx): ?><table class="table table-sm mb-0"><tbody><?php foreach ($ptx as $t): ?><tr><td class="small text-nowrap"><?= thai_date($t['created_at']) ?></td><td class="small"><?= e($t['reason']) ?></td><td class="text-end <?= $t['points'] < 0 ? 'text-danger' : 'text-success' ?>"><?= ($t['points'] > 0 ? '+' : '') . number_format($t['points']) ?></td></tr><?php endforeach; ?></tbody></table>
    <?php else: ?><div class="text-muted small">ไม่มีรายการ</div><?php endif; ?>
  </div></div>
</div>

<?php elseif ($edit || $adding): $f = $edit ?: ['id' => 0, 'name' => '', 'email' => '', 'phone' => '', 'address' => '', 'role' => 'user']; ?>
<form class="panel" method="post" style="max-width:720px">
  <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
  <div class="panel-title"><i class="bi bi-person"></i> <?= $edit ? 'แก้ไขสมาชิก' : 'เพิ่มสมาชิก' ?></div>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label" for="name">ชื่อ</label><input id="name" class="form-control" name="name" value="<?= e($f['name']) ?>" required maxlength="100"></div>
    <div class="col-md-6"><label class="form-label" for="email">อีเมล</label><input id="email" class="form-control" type="email" name="email" value="<?= e($f['email']) ?>" required maxlength="150"></div>
    <div class="col-md-6"><label class="form-label" for="phone">เบอร์โทรศัพท์</label><input id="phone" class="form-control" name="phone" value="<?= e($f['phone']) ?>" maxlength="30"></div>
    <div class="col-md-6"><label class="form-label" for="role">สิทธิ์</label><select id="role" class="form-select" name="role"><option value="user">ผู้ใช้</option><option value="admin" <?= $f['role'] === 'admin' ? 'selected' : '' ?>>ผู้ดูแลระบบ</option></select></div>
    <div class="col-12"><label class="form-label" for="address">ที่อยู่</label><textarea id="address" class="form-control" name="address" rows="2"><?= e($f['address']) ?></textarea></div>
    <div class="col-md-6"><label class="form-label" for="password"><?= $edit ? 'รหัสผ่านใหม่ (เว้นว่างหากไม่เปลี่ยน)' : 'รหัสผ่าน' ?></label><input id="password" class="form-control" type="password" name="password" minlength="8" <?= $edit ? '' : 'required' ?> autocomplete="new-password"></div>
  </div>
  <div class="mt-3"><button class="btn btn-primary px-4">บันทึก</button> <a class="btn btn-light" href="<?= e(url('admin/users.php')) ?>">ยกเลิก</a></div>
</form>

<?php else: ?>
<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
  <form class="d-flex gap-2" method="get"><input class="form-control" name="q" value="<?= e($kw) ?>" placeholder="ค้นหาชื่อ / อีเมล" aria-label="ค้นหาสมาชิก"><button class="btn btn-outline-primary">ค้นหา</button></form>
  <a class="btn btn-primary" href="<?= e(url('admin/users.php?add=1')) ?>"><i class="bi bi-person-plus"></i> เพิ่มสมาชิก</a>
</div>
<div class="panel p-0 overflow-hidden"><div class="table-responsive"><table class="table mb-0">
  <thead><tr><th class="ps-3">สมาชิก</th><th>สิทธิ์</th><th class="text-end">Wallet</th><th class="text-end">Point</th><th>สินค้า</th><th>ออเดอร์</th><th>สถานะ</th><th class="text-end pe-3">จัดการ</th></tr></thead>
  <tbody>
  <?php foreach ($list as $x): ?>
    <tr>
      <td class="ps-3"><div class="d-flex align-items-center gap-2"><?= avatar_html($x, 36) ?><div><div class="fw-medium"><?= e($x['name']) ?><?= premium_badge($x) ?></div><div class="small text-muted"><?= e($x['email']) ?></div></div></div></td>
      <td><?= $x['role'] === 'admin' ? '<span class="badge text-bg-success">Admin</span>' : 'ผู้ใช้' ?></td>
      <td class="text-end"><?= baht($x['wallet_balance']) ?></td><td class="text-end"><?= number_format($x['green_points']) ?></td>
      <td><?= (int) $x['products'] ?></td><td><?= (int) $x['orders'] ?></td><td><?= badge($x['status']) ?></td>
      <td class="text-end pe-3 text-nowrap">
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('admin/users.php?view=' . $x['id'])) ?>" title="ดูประวัติ"><i class="bi bi-clock-history"></i></a>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/users.php?edit=' . $x['id'])) ?>" title="แก้ไข"><i class="bi bi-pencil"></i></a>
        <?php if ($x['id'] != $admin['id']): ?>
        <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $x['id'] ?>">
          <button class="btn btn-sm btn-outline-warning" name="action" value="toggle" title="<?= $x['status'] === 'active' ? 'ระงับบัญชี' : 'ยกเลิกการระงับ' ?>" data-confirm="<?= $x['status'] === 'active' ? 'ระงับบัญชีนี้?' : 'ยกเลิกการระงับบัญชีนี้?' ?>"><i class="bi bi-<?= $x['status'] === 'active' ? 'slash-circle' : 'check-circle' ?>"></i></button>
          <button class="btn btn-sm btn-outline-danger" name="action" value="delete" title="ลบ" data-confirm="ลบสมาชิก <?= e($x['name']) ?> และข้อมูลทั้งหมดของสมาชิกนี้?"><i class="bi bi-trash"></i></button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
