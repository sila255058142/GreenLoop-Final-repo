<?php
require __DIR__ . '/../includes/init.php';
require_admin();
$types = ['coupon' => 'คูปองส่วนลด (บาท)', 'fee' => 'ฟรีค่าธรรมเนียมการขาย 1 ครั้ง', 'item' => 'ของรางวัลจัดส่ง', 'premium' => 'Premium (จำนวนวัน)'];

if (is_post()) {
    $id = (int) post('id');
    $action = post('action');
    if ($action === 'deliver') {
        q("UPDATE redemptions SET status = 'delivered' WHERE id = ? AND status = 'pending'", [$id]);
        flash('บันทึกการจัดส่งของรางวัลแล้ว');
    } elseif ($action === 'toggle') {
        q('UPDATE rewards SET active = 1 - active WHERE id = ?', [$id]);
        flash('เปลี่ยนสถานะรายการแลกแล้ว');
    } elseif ($action === 'delete') {
        q('DELETE FROM rewards WHERE id = ?', [$id]);
        flash('ลบรายการแลกแล้ว');
    } elseif ($action === 'save') {
        $type = isset($types[post('type')]) ? post('type') : 'coupon';
        $cost = (int) post('points_cost');
        $value = (float) post('value');
        if (post('name') === '' || $cost < 1) {
            flash('กรุณากรอกชื่อและจำนวนคะแนนที่ใช้แลก', 'danger');
        } elseif (in_array($type, ['coupon', 'premium'], true) && $value <= 0) {
            flash('กรุณากรอกมูลค่าคูปอง (บาท) หรือจำนวนวัน Premium', 'danger');
        } else {
            $icon = preg_match('/^bi-[a-z0-9-]+$/', post('icon')) ? post('icon') : 'bi-gift';
            $data = [mb_substr(post('name'), 0, 150), mb_substr(post('description'), 0, 255), $type, $cost, $value, $icon];
            if ($id) {
                q('UPDATE rewards SET name = ?, description = ?, type = ?, points_cost = ?, value = ?, icon = ? WHERE id = ?', array_merge($data, [$id]));
            } else {
                q('INSERT INTO rewards (name, description, type, points_cost, value, icon) VALUES (?,?,?,?,?,?)', $data);
            }
            flash('บันทึกรายการแลกแล้ว');
        }
    }
    redirect('admin/rewards.php');
}

$edit = (int) get('edit') ? row('SELECT * FROM rewards WHERE id = ?', [(int) get('edit')]) : null;
$f = $edit ?: ['id' => 0, 'name' => '', 'description' => '', 'type' => 'coupon', 'points_cost' => 100, 'value' => 0, 'icon' => 'bi-gift'];
$rewards = rows('SELECT r.*, (SELECT COUNT(*) FROM redemptions WHERE reward_id = r.id) AS used FROM rewards r ORDER BY r.points_cost');
$redemptions = rows("SELECT x.*, u.name, u.phone, u.address FROM redemptions x JOIN users u ON u.id = x.user_id ORDER BY (x.status = 'pending') DESC, x.id DESC LIMIT 100");

$title = 'ของรางวัล Green Point';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="row g-3">
  <div class="col-xl-8">
    <div class="panel p-0 overflow-hidden mb-3"><div class="table-responsive"><table class="table mb-0">
      <thead><tr><th class="ps-3">รายการแลก</th><th>ประเภท</th><th class="text-end">คะแนน</th><th>แลกแล้ว</th><th>สถานะ</th><th class="text-end pe-3">จัดการ</th></tr></thead>
      <tbody>
      <?php foreach ($rewards as $r): ?>
        <tr><td class="ps-3"><i class="bi <?= e($r['icon']) ?> text-green"></i> <span class="fw-medium"><?= e($r['name']) ?></span><div class="small text-muted"><?= e($r['description']) ?></div></td>
          <td class="small"><?= $types[$r['type']] ?><?= $r['value'] > 0 ? ': ' . (float) $r['value'] : '' ?></td>
          <td class="text-end"><?= number_format($r['points_cost']) ?></td><td><?= (int) $r['used'] ?></td>
          <td><?= $r['active'] ? '<span class="badge rounded-pill text-bg-success">เปิด</span>' : '<span class="badge rounded-pill text-bg-secondary">ปิด</span>' ?></td>
          <td class="text-end pe-3 text-nowrap"><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button class="btn btn-sm btn-outline-secondary" name="action" value="toggle" title="เปิด/ปิด"><i class="bi bi-eye<?= $r['active'] ? '-slash' : '' ?>"></i></button>
            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/rewards.php?edit=' . $r['id'])) ?>" title="แก้ไข"><i class="bi bi-pencil"></i></a>
            <button class="btn btn-sm btn-outline-danger" name="action" value="delete" title="ลบ" data-confirm="ลบรายการแลกนี้?"><i class="bi bi-trash"></i></button></form></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div></div>
    <div class="panel">
      <div class="panel-title"><i class="bi bi-ticket-perforated"></i> ประวัติการแลกคะแนน</div>
      <?php if ($redemptions): ?>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>วันที่</th><th>สมาชิก</th><th>รายการ</th><th>รหัส</th><th>สถานะ</th><th></th></tr></thead>
        <tbody><?php foreach ($redemptions as $x): ?>
          <tr><td class="small text-nowrap"><?= thai_date($x['created_at']) ?></td>
            <td><?= e($x['name']) ?><?php if ($x['type'] === 'item'): ?><div class="small text-muted"><?= e($x['phone']) ?> — <?= e($x['address']) ?></div><?php endif; ?></td>
            <td><?= e($x['reward_name']) ?> <span class="small text-muted">(<?= number_format($x['points']) ?> คะแนน)</span></td><td><code><?= e($x['code']) ?></code></td>
            <td><?= $x['status'] === 'pending' ? '<span class="badge rounded-pill text-bg-warning">รอจัดส่ง</span>' : badge($x['status']) ?></td>
            <td class="text-end"><?php if ($x['status'] === 'pending'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $x['id'] ?>"><button class="btn btn-sm btn-success" name="action" value="deliver">จัดส่งแล้ว</button></form><?php endif; ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <?php else: ?><div class="empty py-4"><i class="bi bi-inbox"></i>ยังไม่มีการแลกคะแนน</div><?php endif; ?>
    </div>
  </div>
  <div class="col-xl-4">
    <form class="panel" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
      <div class="panel-title"><i class="bi bi-<?= $edit ? 'pencil' : 'plus-circle' ?>"></i> <?= $edit ? 'แก้ไขรายการแลก' : 'เพิ่มรายการแลก' ?></div>
      <div class="mb-3"><label class="form-label" for="name">ชื่อ</label><input id="name" class="form-control" name="name" value="<?= e($f['name']) ?>" required maxlength="150"></div>
      <div class="mb-3"><label class="form-label" for="description">คำอธิบาย</label><input id="description" class="form-control" name="description" value="<?= e($f['description']) ?>" maxlength="255"></div>
      <div class="mb-3"><label class="form-label" for="type">ประเภท</label><select id="type" class="form-select" name="type"><?php foreach ($types as $k => $v): ?><option value="<?= $k ?>" <?= $f['type'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
      <div class="row g-3 mb-3">
        <div class="col-6"><label class="form-label" for="points_cost">คะแนนที่ใช้</label><input id="points_cost" class="form-control" type="number" name="points_cost" min="1" value="<?= (int) $f['points_cost'] ?>" required></div>
        <div class="col-6"><label class="form-label" for="value">มูลค่า (บาท / วัน)</label><input id="value" class="form-control" type="number" name="value" min="0" step="0.01" value="<?= (float) $f['value'] ?>"></div>
      </div>
      <div class="mb-3"><label class="form-label" for="icon">ไอคอน</label><input id="icon" class="form-control" name="icon" value="<?= e($f['icon']) ?>"></div>
      <button class="btn btn-primary w-100">บันทึก</button>
      <?php if ($edit): ?><a class="btn btn-light w-100 mt-2" href="<?= e(url('admin/rewards.php')) ?>">ยกเลิก</a><?php endif; ?>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
