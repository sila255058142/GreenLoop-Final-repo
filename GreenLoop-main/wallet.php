<?php
require __DIR__ . '/includes/init.php';
$u = require_login();

if (is_post()) {
    $amount = post('amount');
    $slip = upload_image('slip', 'slips');
    if (!is_numeric($amount) || $amount < 20 || $amount > 100000) {
        flash('จำนวนเงินต้องอยู่ระหว่าง ฿20 - ฿100,000', 'danger');
    } elseif ($slip === null) {
        flash('กรุณาแนบสลิปการโอนเงิน', 'danger');
    } elseif ($slip) {
        q('INSERT INTO topups (user_id, amount, slip) VALUES (?,?,?)', [$u['id'], round($amount, 2), $slip]);
        flash('ส่งคำขอเติมเงินแล้ว รอผู้ดูแลระบบตรวจสอบสลิป');
    }
    redirect('wallet.php');
}

$topups = rows('SELECT * FROM topups WHERE user_id = ? ORDER BY id DESC LIMIT 30', [$u['id']]);
$txs = rows('SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 50', [$u['id']]);
$txTypes = ['topup' => 'เติมเงิน', 'purchase' => 'ซื้อสินค้า', 'sale' => 'ขายสินค้า', 'refund' => 'คืนเงิน', 'promote' => 'โปรโมทสินค้า', 'premium' => 'สมาชิก Premium', 'adjust' => 'ปรับยอด'];

$title = 'กระเป๋าเงิน';
require __DIR__ . '/includes/header.php';
echo page_header('ระบบกระเป๋าเงิน (Wallet)');
?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="wallet-card mb-4">
      <div class="opacity-75">ยอดเงินคงเหลือ</div>
      <div class="amount"><?= baht($u['wallet_balance']) ?></div>
      <div class="small opacity-75 mt-2"><?= e($u['name']) ?></div>
      <i class="bi bi-wallet2 bi-bg"></i>
    </div>
    <form class="panel" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="panel-title"><i class="bi bi-plus-circle"></i> เติมเงิน</div>
      <div class="bg-mint rounded-3 p-3 mb-3 small">
        <div class="d-flex gap-3 align-items-center">
          <?php if (setting('qr_code')): ?><img src="<?= e(url(setting('qr_code'))) ?>" alt="QR Code สำหรับโอนเงิน" style="width:110px;height:110px;object-fit:contain;background:#fff;border-radius:.5rem"><?php endif; ?>
          <div>โอนเงินมาที่<br><b><?= e(setting('bank_name', '-')) ?></b><br>ชื่อบัญชี: <?= e(setting('bank_account_name', '-')) ?><br>เลขที่บัญชี: <b><?= e(setting('bank_account_no', '-')) ?></b></div>
        </div>
      </div>
      <div class="mb-3"><label class="form-label" for="amount">จำนวนเงิน (บาท)</label><input id="amount" class="form-control" type="number" name="amount" min="20" max="100000" step="0.01" required></div>
      <div class="mb-3"><label class="form-label" for="slip">สลิปการโอนเงิน</label><input id="slip" class="form-control" type="file" name="slip" accept="image/*" required data-preview="#slip-preview">
        <img id="slip-preview" class="d-none mt-2 rounded" style="max-height:160px" alt="ตัวอย่างสลิป"></div>
      <button class="btn btn-primary w-100">ส่งคำขอเติมเงิน</button>
    </form>
  </div>
  <div class="col-lg-7">
    <div class="panel mb-4">
      <div class="panel-title"><i class="bi bi-clock-history"></i> ประวัติการเติมเงิน</div>
      <?php if ($topups): ?>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>วันที่</th><th>จำนวน</th><th>สลิป</th><th>สถานะ</th><th>หมายเหตุ</th></tr></thead>
        <tbody><?php foreach ($topups as $t): ?>
          <tr><td class="small"><?= thai_date($t['created_at']) ?></td><td class="fw-semibold"><?= baht($t['amount']) ?></td>
            <td><a href="<?= e(url($t['slip'])) ?>" target="_blank" rel="noopener"><img class="slip-thumb" src="<?= e(url($t['slip'])) ?>" alt="สลิป"></a></td>
            <td><?= badge($t['status']) ?></td><td class="small text-muted"><?= e($t['admin_note']) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <?php else: ?><div class="empty py-4"><i class="bi bi-inbox"></i>ยังไม่มีประวัติการเติมเงิน</div><?php endif; ?>
    </div>
    <div class="panel">
      <div class="panel-title"><i class="bi bi-list-ul"></i> รายการเคลื่อนไหว</div>
      <?php if ($txs): ?>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>วันที่</th><th>ประเภท</th><th>รายละเอียด</th><th class="text-end">จำนวน</th><th class="text-end">คงเหลือ</th></tr></thead>
        <tbody><?php foreach ($txs as $t): ?>
          <tr><td class="small text-nowrap"><?= thai_date($t['created_at']) ?></td><td class="small"><?= e(isset($txTypes[$t['type']]) ? $txTypes[$t['type']] : $t['type']) ?></td><td class="small text-muted"><?= e($t['note']) ?></td>
            <td class="text-end fw-semibold text-nowrap <?= $t['amount'] < 0 ? 'text-danger' : 'text-success' ?>"><?= ($t['amount'] < 0 ? '-' : '+') . baht(abs($t['amount'])) ?></td>
            <td class="text-end small text-nowrap"><?= baht($t['balance_after']) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <?php else: ?><div class="empty py-4"><i class="bi bi-inbox"></i>ยังไม่มีรายการ</div><?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
