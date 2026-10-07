<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$text = ['site_name', 'tagline', 'contact_email', 'contact_phone', 'contact_line', 'contact_address', 'bank_name', 'bank_account_name', 'bank_account_no', 'footer_text'];
$numbers = ['commission_rate' => [0, 50], 'premium_commission_rate' => [0, 50], 'premium_price' => [0, 100000], 'promote_price' => [0, 100000]];

if (is_post()) {
    $save = function ($name, $value) {
        q('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$name, $value]);
    };
    foreach ($text as $k) {
        $save($k, mb_substr(post($k), 0, 500));
    }
    foreach ($numbers as $k => $range) {
        if (is_numeric(post($k))) {
            $save($k, (string) max($range[0], min($range[1], (float) post($k))));
        }
    }
    foreach (['logo', 'qr_code'] as $k) {
        $img = upload_image($k, 'site');
        if ($img) {
            $save($k, $img);
        } elseif ($img === null && post('remove_' . $k)) {
            $save($k, '');
        }
    }
    flash('บันทึกการตั้งค่าเว็บไซต์แล้ว');
    redirect('admin/settings.php');
}

$title = 'ตั้งค่าเว็บไซต์';
require __DIR__ . '/../includes/admin_header.php';
$field = function ($name, $label, $type = 'text') {
    return '<label class="form-label" for="' . $name . '">' . $label . '</label><input id="' . $name . '" class="form-control" type="' . $type . '" name="' . $name . '" value="' . e(setting($name)) . '"' . ($type === 'number' ? ' min="0" step="0.01"' : ' maxlength="500"') . '>';
};
?>
<form method="post" enctype="multipart/form-data" class="row g-3"><?= csrf_field() ?>
  <div class="col-xl-6"><div class="panel h-100">
    <div class="panel-title"><i class="bi bi-globe"></i> ข้อมูลเว็บไซต์และโลโก้</div>
    <div class="mb-3"><?= $field('site_name', 'ชื่อเว็บไซต์') ?></div>
    <div class="mb-3"><?= $field('tagline', 'คำโปรย') ?></div>
    <div class="mb-3"><?= $field('footer_text', 'ข้อความ Footer') ?></div>
    <label class="form-label" for="logo">โลโก้</label>
    <input id="logo" class="form-control" type="file" name="logo" accept="image/*" data-preview="#logo-preview">
    <img id="logo-preview" class="mt-2 <?= setting('logo') ? '' : 'd-none' ?>" style="max-height:60px" src="<?= setting('logo') ? e(url(setting('logo'))) : '' ?>" alt="โลโก้ปัจจุบัน">
    <?php if (setting('logo')): ?><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo"><label class="form-check-label small" for="remove_logo">ลบโลโก้และใช้โลโก้เริ่มต้น</label></div><?php endif; ?>
  </div></div>
  <div class="col-xl-6"><div class="panel h-100">
    <div class="panel-title"><i class="bi bi-telephone"></i> ข้อมูลติดต่อ</div>
    <div class="mb-3"><?= $field('contact_email', 'อีเมล', 'email') ?></div>
    <div class="mb-3"><?= $field('contact_phone', 'เบอร์โทรศัพท์') ?></div>
    <div class="mb-3"><?= $field('contact_line', 'LINE') ?></div>
    <div><?= $field('contact_address', 'ที่อยู่') ?></div>
  </div></div>
  <div class="col-xl-6"><div class="panel h-100">
    <div class="panel-title"><i class="bi bi-bank"></i> ข้อมูลธนาคารและ QR Code (สำหรับเติมเงิน)</div>
    <div class="mb-3"><?= $field('bank_name', 'ธนาคาร') ?></div>
    <div class="mb-3"><?= $field('bank_account_name', 'ชื่อบัญชี') ?></div>
    <div class="mb-3"><?= $field('bank_account_no', 'เลขที่บัญชี') ?></div>
    <label class="form-label" for="qr_code">QR Code รับเงิน</label>
    <input id="qr_code" class="form-control" type="file" name="qr_code" accept="image/*" data-preview="#qr-preview">
    <img id="qr-preview" class="mt-2 <?= setting('qr_code') ? '' : 'd-none' ?>" style="max-height:140px" src="<?= setting('qr_code') ? e(url(setting('qr_code'))) : '' ?>" alt="QR Code ปัจจุบัน">
    <?php if (setting('qr_code')): ?><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_qr_code" value="1" id="remove_qr_code"><label class="form-check-label small" for="remove_qr_code">ลบ QR Code</label></div><?php endif; ?>
  </div></div>
  <div class="col-xl-6"><div class="panel h-100">
    <div class="panel-title"><i class="bi bi-cash-stack"></i> ระบบสร้างรายได้ (Revenue Model)</div>
    <div class="row g-3">
      <div class="col-md-6"><?= $field('commission_rate', 'ค่าธรรมเนียมการขาย (%)', 'number') ?></div>
      <div class="col-md-6"><?= $field('premium_commission_rate', 'ค่าธรรมเนียมสมาชิก Premium (%)', 'number') ?></div>
      <div class="col-md-6"><?= $field('premium_price', 'ราคา Premium / ' . PREMIUM_DAYS . ' วัน (บาท)', 'number') ?></div>
      <div class="col-md-6"><?= $field('promote_price', 'ราคาโปรโมทสินค้า / ' . PROMOTE_DAYS . ' วัน (บาท)', 'number') ?></div>
    </div>
    <div class="form-text mt-3">ตัวอย่าง: ขายสินค้าได้ ฿1,000 ที่ค่าธรรมเนียม <?= e(setting('commission_rate', 5)) ?>% — GreenLoop ได้ค่าธรรมเนียม <?= baht(1000 * (float) setting('commission_rate', 5) / 100) ?></div>
  </div></div>
  <div class="col-12"><button class="btn btn-primary px-5">บันทึกการตั้งค่า</button></div>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
