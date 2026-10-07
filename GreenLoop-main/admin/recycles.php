<?php
require __DIR__ . '/../includes/init.php';
require_admin();

// ลำดับสถานะ: เดินหน้าได้อย่างเดียว และปฏิเสธได้จนกว่าจะรีไซเคิลสำเร็จ
$flow = ['pending' => ['accepted', 'rejected'], 'accepted' => ['received', 'rejected'], 'received' => ['completed', 'rejected'], 'completed' => [], 'rejected' => []];
$actionLabels = ['accepted' => 'รับคำขอ', 'received' => 'ได้รับอุปกรณ์แล้ว', 'completed' => 'อนุมัติรีไซเคิลสำเร็จ (+' . POINTS['recycle_done'] . ' Point)', 'rejected' => 'ปฏิเสธ'];

if (is_post()) {
    $id = (int) post('id');
    $to = post('status');
    $note = mb_substr(post('note'), 0, 255);
    $ok = in_tx(function () use ($id, $to, $note, $flow) {
        $r = row('SELECT * FROM recycles WHERE id = ? FOR UPDATE', [$id]);
        if (!$r || !in_array($to, $flow[$r['status']], true)) {
            return false;
        }
        q('UPDATE recycles SET status = ?, admin_note = ? WHERE id = ?', [$to, $note, $id]);
        if ($to === 'completed' && !$r['points_awarded']) {
            q('UPDATE recycles SET points_awarded = 1 WHERE id = ?', [$id]);
            add_points($r['user_id'], POINTS['recycle_done'], 'รีไซเคิลสำเร็จ: ' . $r['device_type'] . ' (#R' . $id . ')');
        }
        return true;
    });
    $ok ? flash('อัปเดตสถานะการรีไซเคิลแล้ว') : flash('ไม่สามารถเปลี่ยนสถานะนี้ได้', 'warning');
    back('admin/recycles.php');
}

$status = isset($flow[get('status')]) ? get('status') : '';
$list = rows('SELECT r.*, u.name, u.email FROM recycles r JOIN users u ON u.id = r.user_id' . ($status ? ' WHERE r.status = ?' : '') . ' ORDER BY r.id DESC LIMIT 200', $status ? [$status] : []);

$title = 'จัดการรีไซเคิล';
require __DIR__ . '/../includes/admin_header.php';
?>
<ul class="nav nav-pills mb-3">
  <li class="nav-item"><a class="nav-link <?= $status === '' ? 'active' : '' ?>" href="<?= e(url('admin/recycles.php')) ?>">ทั้งหมด</a></li>
  <?php foreach (array_keys($flow) as $k): ?>
  <li class="nav-item"><a class="nav-link <?= $status === $k ? 'active' : '' ?>" href="<?= e(url('admin/recycles.php?status=' . $k)) ?>"><?= STATUS_LABELS[$k][0] ?></a></li>
  <?php endforeach; ?>
</ul>
<div class="panel p-0 overflow-hidden">
<?php if ($list): ?>
<div class="table-responsive"><table class="table mb-0">
  <thead><tr><th class="ps-3">คำขอ</th><th>สมาชิก</th><th>อุปกรณ์</th><th>การส่งมอบ</th><th>สถานะ</th><th class="text-end pe-3">เปลี่ยนสถานะ</th></tr></thead>
  <tbody>
  <?php foreach ($list as $r): ?>
    <tr>
      <td class="ps-3 small"><b>#R<?= $r['id'] ?></b><br><?= thai_date($r['created_at']) ?></td>
      <td><?= e($r['name']) ?><div class="small text-muted"><?= e($r['email']) ?></div></td>
      <td><div class="d-flex gap-2 align-items-center">
        <?php if ($r['image']): ?><a href="<?= e(url($r['image'])) ?>" target="_blank" rel="noopener"><img class="slip-thumb" src="<?= e(url($r['image'])) ?>" alt="รูปอุปกรณ์"></a><?php endif; ?>
        <div><?= e($r['device_type']) ?> × <?= (int) $r['quantity'] ?><div class="small text-muted"><?= e($r['detail']) ?></div></div></div></td>
      <td class="small"><?= e(RECYCLE_METHODS[$r['method']]) ?><div class="text-muted"><?= e($r['contact']) ?></div></td>
      <td><?= badge($r['status']) ?><?= $r['admin_note'] !== '' ? '<div class="small text-muted">' . e($r['admin_note']) . '</div>' : '' ?></td>
      <td class="text-end pe-3">
        <?php if ($flow[$r['status']]): ?>
        <form method="post" class="d-flex gap-1 justify-content-end flex-wrap"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
          <input class="form-control form-control-sm" style="width:140px" name="note" value="<?= e($r['admin_note']) ?>" placeholder="หมายเหตุ" maxlength="255" aria-label="หมายเหตุ">
          <?php foreach ($flow[$r['status']] as $to): ?>
          <button class="btn btn-sm <?= $to === 'rejected' ? 'btn-outline-danger' : ($to === 'completed' ? 'btn-success' : 'btn-outline-primary') ?>" name="status" value="<?= $to ?>" <?= $to === 'rejected' || $to === 'completed' ? 'data-confirm="ยืนยัน: ' . e($actionLabels[$to]) . '?"' : '' ?>><?= $actionLabels[$to] ?></button>
          <?php endforeach; ?>
        </form>
        <?php else: ?><span class="small text-muted">—</span><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><div class="empty"><i class="bi bi-recycle"></i>ไม่มีคำขอรีไซเคิล</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
