<?php
require __DIR__ . '/includes/init.php';
$u = user();

if (is_post()) {
    $u = require_login();
    $device = post('device_type');
    $method = post('method');
    $contact = post('contact');
    $image = upload_image('image', 'recycles');
    if (!in_array($device, RECYCLE_DEVICES, true) || !isset(RECYCLE_METHODS[$method])) {
        flash('กรุณาเลือกประเภทอุปกรณ์และวิธีส่งมอบ', 'danger');
    } elseif ($contact === '') {
        flash('กรุณากรอกที่อยู่/ข้อมูลติดต่อ', 'danger');
    } elseif ($image !== false) {
        in_tx(function () use ($u, $device, $method, $contact, $image) {
            q('INSERT INTO recycles (user_id, device_type, detail, quantity, method, contact, image) VALUES (?,?,?,?,?,?,?)',
                [$u['id'], $device, mb_substr(post('detail'), 0, 255), max(1, min(100, (int) post('quantity', '1'))), $method, $contact, $image]);
            add_points($u['id'], POINTS['recycle_submit'], 'ส่งคำขอรีไซเคิล: ' . $device);
            return true;
        });
        flash('ส่งคำขอรีไซเคิลแล้ว รับ +' . POINTS['recycle_submit'] . ' Green Point');
    }
    redirect('recycle.php');
}

$list = $u ? rows('SELECT * FROM recycles WHERE user_id = ? ORDER BY id DESC', [$u['id']]) : [];
$steps = ['pending' => 1, 'accepted' => 2, 'received' => 3, 'completed' => 4, 'rejected' => 0];

$title = 'แจ้งรีไซเคิล E-Waste';
require __DIR__ . '/includes/header.php';
?>
<div class="banner banner-middle mb-4">
  <div class="banner-text"><span class="banner-label">Recycle · E-Waste</span>
    <h2>แจ้งความประสงค์รีไซเคิล</h2>
    <p>ส่งอุปกรณ์อิเล็กทรอนิกส์ที่ไม่ใช้แล้วเข้าสู่กระบวนการรีไซเคิลอย่างถูกวิธี รับ +<?= POINTS['recycle_submit'] ?> Point เมื่อส่งคำขอ และ +<?= POINTS['recycle_done'] ?> Point เมื่อรีไซเคิลสำเร็จ</p></div>
  <i class="bi bi-recycle banner-deco"></i>
</div>
<div class="row g-4">
  <div class="col-lg-5">
    <?php if ($u): ?>
    <form class="panel" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="panel-title"><i class="bi bi-recycle"></i> แบบฟอร์มแจ้งรีไซเคิล</div>
      <div class="mb-3"><label class="form-label" for="device_type">ประเภทอุปกรณ์</label>
        <select id="device_type" class="form-select" name="device_type" required><option value="">— เลือกประเภท —</option>
          <?php foreach (RECYCLE_DEVICES as $d): ?><option><?= e($d) ?></option><?php endforeach; ?></select></div>
      <div class="row g-3 mb-3">
        <div class="col-8"><label class="form-label" for="detail">ยี่ห้อ / รุ่น / สภาพ</label><input id="detail" class="form-control" name="detail" maxlength="255" placeholder="เช่น Nokia 3310 เปิดไม่ติด"></div>
        <div class="col-4"><label class="form-label" for="quantity">จำนวน</label><input id="quantity" class="form-control" type="number" name="quantity" min="1" max="100" value="1" required></div>
      </div>
      <div class="mb-3"><label class="form-label" for="method">วิธีส่งมอบ</label>
        <select id="method" class="form-select" name="method" required>
          <?php foreach (RECYCLE_METHODS as $k => $m): ?><option value="<?= $k ?>"><?= $m ?></option><?php endforeach; ?></select></div>
      <div class="mb-3"><label class="form-label" for="contact">ที่อยู่ / เบอร์ติดต่อ</label><textarea id="contact" class="form-control" name="contact" rows="2" required><?= e(trim($u['address'] . ' ' . $u['phone'])) ?></textarea></div>
      <div class="mb-3"><label class="form-label" for="image">รูปอุปกรณ์ (ถ้ามี)</label><input id="image" class="form-control" type="file" name="image" accept="image/*"></div>
      <button class="btn btn-primary w-100">ส่งคำขอรีไซเคิล</button>
    </form>
    <?php else: ?>
    <div class="panel text-center py-5"><i class="bi bi-recycle text-green" style="font-size:3rem"></i>
      <p class="mt-2">เข้าสู่ระบบเพื่อแจ้งรีไซเคิลและสะสม Green Point</p>
      <a class="btn btn-primary px-4" href="<?= e(url('login.php')) ?>">เข้าสู่ระบบ</a>
      <a class="btn btn-outline-primary px-4" href="<?= e(url('register.php')) ?>">สมัครสมาชิก</a></div>
    <?php endif; ?>
  </div>
  <div class="col-lg-7">
    <div class="panel mb-4">
      <div class="panel-title"><i class="bi bi-signpost-split"></i> ขั้นตอนการรีไซเคิล</div>
      <div class="flow">
        <div class="flow-step"><span class="ico"><i class="bi bi-send"></i></span><div>ส่งคำขอ<br><b class="text-green">+<?= POINTS['recycle_submit'] ?></b></div></div>
        <div class="flow-step"><span class="ico"><i class="bi bi-clipboard-check"></i></span><div>แอดมินตรวจสอบคำขอ</div></div>
        <div class="flow-step"><span class="ico"><i class="bi bi-box-seam"></i></span><div>ส่งมอบอุปกรณ์</div></div>
        <div class="flow-step"><span class="ico"><i class="bi bi-patch-check"></i></span><div>รีไซเคิลสำเร็จ<br><b class="text-green">+<?= POINTS['recycle_done'] ?></b></div></div>
      </div>
    </div>
    <?php if ($u): ?>
    <div class="panel">
      <div class="panel-title"><i class="bi bi-clock-history"></i> ติดตามสถานะการรีไซเคิล</div>
      <?php if (!$list): ?><div class="empty py-4"><i class="bi bi-inbox"></i>ยังไม่มีคำขอรีไซเคิล</div><?php endif; ?>
      <?php foreach ($list as $r): $s = $steps[$r['status']]; ?>
      <div class="border rounded-4 p-3 mb-2">
        <div class="d-flex justify-content-between gap-2 flex-wrap">
          <div><b><?= e($r['device_type']) ?></b> × <?= (int) $r['quantity'] ?> <span class="text-muted small"><?= e($r['detail']) ?></span>
            <div class="small text-muted">#R<?= $r['id'] ?> · <?= thai_date($r['created_at']) ?> · <?= e(RECYCLE_METHODS[$r['method']]) ?></div></div>
          <div><?= badge($r['status']) ?></div>
        </div>
        <?php if ($s): ?>
        <div class="tracker">
          <div class="st <?= $s >= 1 ? 'on' : '' ?>">ส่งคำขอ</div><div class="st <?= $s >= 2 ? 'on' : '' ?>">รับคำขอ</div>
          <div class="st <?= $s >= 3 ? 'on' : '' ?>">ได้รับอุปกรณ์</div><div class="st <?= $s >= 4 ? 'on' : '' ?>">รีไซเคิลสำเร็จ</div>
        </div>
        <?php endif; ?>
        <?php if ($r['admin_note'] !== ''): ?><div class="small mt-1"><i class="bi bi-chat-left-text"></i> <?= e($r['admin_note']) ?></div><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
