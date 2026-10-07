<?php
require __DIR__ . '/../includes/init.php';
require_admin();

if (is_post()) {
    $id = (int) post('id');
    $approve = post('action') === 'approve';
    $note = mb_substr(post('note'), 0, 255);
    $ok = in_tx(function () use ($id, $approve, $note) {
        $t = row("SELECT * FROM topups WHERE id = ? AND status = 'pending' FOR UPDATE", [$id]);
        if (!$t) {
            return false;
        }
        q('UPDATE topups SET status = ?, admin_note = ?, reviewed_at = NOW() WHERE id = ?', [$approve ? 'approved' : 'rejected', $note, $id]);
        if ($approve) {
            wallet_move($t['user_id'], $t['amount'], 'topup', 'เติมเงินผ่านการโอน (คำขอ #' . $id . ')');
        }
        return true;
    });
    $ok ? flash($approve ? 'อนุมัติและเพิ่มเงินเข้ากระเป๋าแล้ว' : 'ปฏิเสธคำขอเติมเงินแล้ว') : flash('คำขอนี้ถูกตรวจสอบไปแล้ว', 'warning');
    back('admin/topups.php');
}

$status = in_array(get('status'), ['pending', 'approved', 'rejected'], true) ? get('status') : '';
$list = rows('SELECT t.*, u.name, u.email FROM topups t JOIN users u ON u.id = t.user_id' . ($status ? ' WHERE t.status = ?' : '') . " ORDER BY (t.status = 'pending') DESC, t.id DESC LIMIT 200", $status ? [$status] : []);

$title = 'จัดการเติมเงิน';
require __DIR__ . '/../includes/admin_header.php';
?>
<ul class="nav nav-pills mb-3">
  <?php foreach (['' => 'ทั้งหมด', 'pending' => 'รอตรวจสอบ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ไม่อนุมัติ'] as $k => $v): ?>
  <li class="nav-item"><a class="nav-link <?= $status === $k ? 'active' : '' ?>" href="<?= e(url('admin/topups.php' . ($k ? '?status=' . $k : ''))) ?>"><?= $v ?></a></li>
  <?php endforeach; ?>
</ul>
<div class="panel p-0 overflow-hidden">
<?php if ($list): ?>
<div class="table-responsive"><table class="table mb-0">
  <thead><tr><th class="ps-3">คำขอ</th><th>สมาชิก</th><th class="text-end">จำนวนเงิน</th><th>สลิป</th><th>สถานะ</th><th class="text-end pe-3">ตรวจสอบ</th></tr></thead>
  <tbody>
  <?php foreach ($list as $t): ?>
    <tr>
      <td class="ps-3 small"><b>#<?= $t['id'] ?></b><br><?= thai_date($t['created_at']) ?></td>
      <td><?= e($t['name']) ?><div class="small text-muted"><?= e($t['email']) ?></div></td>
      <td class="text-end fw-semibold"><?= baht($t['amount']) ?></td>
      <td><a href="<?= e(url($t['slip'])) ?>" target="_blank" rel="noopener" title="เปิดดูสลิป"><img class="slip-thumb" src="<?= e(url($t['slip'])) ?>" alt="สลิป"></a></td>
      <td><?= badge($t['status']) ?><?= $t['admin_note'] !== '' ? '<div class="small text-muted">' . e($t['admin_note']) . '</div>' : '' ?></td>
      <td class="text-end pe-3">
        <?php if ($t['status'] === 'pending'): ?>
        <form method="post" class="d-flex gap-1 justify-content-end flex-wrap"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $t['id'] ?>">
          <input class="form-control form-control-sm" style="width:150px" name="note" placeholder="หมายเหตุ (ถ้ามี)" maxlength="255" aria-label="หมายเหตุ">
          <button class="btn btn-sm btn-success" name="action" value="approve" data-confirm="อนุมัติและเพิ่มเงิน <?= baht($t['amount']) ?> เข้ากระเป๋าของ <?= e($t['name']) ?>?"><i class="bi bi-check-lg"></i> อนุมัติ</button>
          <button class="btn btn-sm btn-outline-danger" name="action" value="reject" data-confirm="ปฏิเสธคำขอนี้?"><i class="bi bi-x-lg"></i> ปฏิเสธ</button>
        </form>
        <?php else: ?><span class="small text-muted"><?= thai_date($t['reviewed_at']) ?></span><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><div class="empty"><i class="bi bi-cash-coin"></i>ไม่มีคำขอเติมเงิน</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
