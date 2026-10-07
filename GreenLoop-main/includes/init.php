<?php
// แกนกลางของระบบ: session, ฐานข้อมูล, ตัวช่วย, สิทธิ์การใช้งาน, กระเป๋าเงิน, Green Point
session_start();
date_default_timezone_set('Asia/Bangkok');
mb_internal_encoding('UTF-8');

define('ROOT_PATH', str_replace('\\', '/', realpath(__DIR__ . '/..')));
$__doc = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
define('BASE_URL', ($__doc && stripos(ROOT_PATH, $__doc) === 0) ? substr(ROOT_PATH, strlen($__doc)) : '');

const POINTS = [
    'sell_market' => 20,
    'buy_market' => 10,
    'recycle_submit' => 10,
    'recycle_done' => 50,
    'sell_digital' => 5,
    'buy_digital' => 5,
];
const POINTS_PER_BAHT = 10;      // 10 คะแนน = ส่วนลด 1 บาท
const POINTS_MAX_PERCENT = 20;   // ใช้คะแนนลดได้สูงสุด 20% ของราคาสินค้า
const PROMOTE_DAYS = 7;
const PREMIUM_DAYS = 30;

const CONDITIONS = [
    'like_new' => ['เหมือนใหม่', 95],
    'very_good' => ['ดีมาก', 90],
    'good' => ['ดี', 85],
    'fair' => ['พอใช้', 75],
];
const STATUS_LABELS = [
    'pending' => ['รอตรวจสอบ', 'warning'],
    'approved' => ['อนุมัติแล้ว', 'success'],
    'rejected' => ['ไม่อนุมัติ', 'danger'],
    'sold' => ['ขายแล้ว', 'secondary'],
    'hidden' => ['ซ่อน', 'secondary'],
    'paid' => ['ชำระเงินแล้ว', 'info'],
    'shipped' => ['จัดส่งแล้ว', 'primary'],
    'completed' => ['สำเร็จ', 'success'],
    'cancelled' => ['ยกเลิก', 'danger'],
    'accepted' => ['รับคำขอแล้ว', 'info'],
    'received' => ['ได้รับอุปกรณ์แล้ว', 'primary'],
    'active' => ['ใช้งานได้', 'success'],
    'used' => ['ใช้แล้ว', 'secondary'],
    'delivered' => ['จัดส่งแล้ว', 'success'],
    'suspended' => ['ระงับ', 'danger'],
];
const RECYCLE_DEVICES = ['โทรศัพท์มือถือ', 'Notebook / คอมพิวเตอร์', 'Tablet', 'กล้อง', 'แบตเตอรี่ / สายชาร์จ', 'อุปกรณ์ IT อื่นๆ'];
const RECYCLE_METHODS = ['parcel' => 'ส่งพัสดุมาที่ศูนย์', 'dropoff' => 'นำมาส่งที่จุดรับ', 'pickup' => 'นัดรับถึงบ้าน'];
const DIGITAL_EXTS = ['zip', 'rar', '7z', 'pdf', 'epub', 'psd', 'ai', 'png', 'jpg', 'jpeg', 'ttf', 'otf', 'pptx', 'docx', 'xlsx', 'txt'];

// ---------------------------------------------------------------- ฐานข้อมูล
function db()
{
    static $pdo = null;
    if ($pdo === null) {
        $c = require ROOT_PATH . '/config.php';
        try {
            $pdo = new PDO(
                "mysql:host={$c['db_host']};port={$c['db_port']};dbname={$c['db_name']};charset=utf8mb4",
                $c['db_user'],
                $c['db_pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            $pdo->exec("SET time_zone = '+07:00'");
        } catch (PDOException $ex) {
            http_response_code(500);
            exit('<h3>เชื่อมต่อฐานข้อมูลไม่ได้</h3><p>กรุณานำเข้าไฟล์ <code>database.sql</code> และตรวจสอบการตั้งค่าใน <code>config.php</code></p>');
        }
    }
    return $pdo;
}

function q($sql, $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function row($sql, $params = [])
{
    $r = q($sql, $params)->fetch();
    return $r ?: null;
}

function rows($sql, $params = [])
{
    return q($sql, $params)->fetchAll();
}

function val($sql, $params = [])
{
    return q($sql, $params)->fetchColumn();
}

// ---------------------------------------------------------------- ตัวช่วยทั่วไป
function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}

function back($fallback = 'index.php')
{
    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    if ($ref && parse_url($ref, PHP_URL_HOST) . (parse_url($ref, PHP_URL_PORT) ? ':' . parse_url($ref, PHP_URL_PORT) : '') === $host) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

function flash($msg, $type = 'success')
{
    $_SESSION['flash'][] = [$type, $msg];
}

function post($key, $default = '')
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function get($key, $default = '')
{
    return isset($_GET[$key]) && is_string($_GET[$key]) ? trim($_GET[$key]) : $default;
}

function is_post()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function baht($n)
{
    $n = (float) $n;
    return '฿' . number_format($n, floor($n) == $n ? 0 : 2);
}

function thai_date($dt, $time = true)
{
    if (!$dt) {
        return '-';
    }
    $t = strtotime($dt);
    $m = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return date('j', $t) . ' ' . $m[(int) date('n', $t)] . ' ' . (date('Y', $t) + 543) . ($time ? ' ' . date('H:i', $t) : '');
}

function badge($status)
{
    $s = isset(STATUS_LABELS[$status]) ? STATUS_LABELS[$status] : [$status, 'secondary'];
    return '<span class="badge rounded-pill text-bg-' . $s[1] . '">' . e($s[0]) . '</span>';
}

function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

if (is_post() && !hash_equals(csrf_token(), isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : '')) {
    flash('คำขอไม่ถูกต้อง หรือไฟล์ที่อัปโหลดมีขนาดใหญ่เกินไป กรุณาลองใหม่', 'danger');
    back();
}

function setting($name, $default = '')
{
    static $all = null;
    if ($all === null) {
        $all = [];
        foreach (rows('SELECT name, value FROM settings') as $r) {
            $all[$r['name']] = $r['value'];
        }
    }
    return isset($all[$name]) && $all[$name] !== '' ? $all[$name] : $default;
}

// ---------------------------------------------------------------- ผู้ใช้และสิทธิ์
function user()
{
    static $u = false;
    if ($u === false) {
        $u = empty($_SESSION['uid']) ? null : row('SELECT * FROM users WHERE id = ?', [$_SESSION['uid']]);
        if ($u && $u['status'] !== 'active') {
            unset($_SESSION['uid']);
            $u = null;
        }
    }
    return $u;
}

function require_login()
{
    if (!user()) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'];
        flash('กรุณาเข้าสู่ระบบก่อนใช้งาน', 'warning');
        redirect('login.php');
    }
    return user();
}

function require_admin()
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        exit('403 — เฉพาะผู้ดูแลระบบ');
    }
    return $u;
}

function is_premium($u)
{
    return $u && $u['premium_until'] && strtotime($u['premium_until']) > time();
}

function premium_badge($u)
{
    return is_premium($u) ? ' <span class="badge badge-premium"><i class="bi bi-gem"></i> Premium</span>' : '';
}

function extend_premium($uid, $days)
{
    q('UPDATE users SET premium_until = DATE_ADD(GREATEST(COALESCE(premium_until, NOW()), NOW()), INTERVAL ? DAY) WHERE id = ?', [(int) $days, $uid]);
}

// ---------------------------------------------------------------- อัปโหลดไฟล์
// คืนค่า path ของไฟล์, null เมื่อไม่ได้เลือกไฟล์, false เมื่อไฟล์ไม่ถูกต้อง
function upload_image($field, $dir)
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    $info = $f['error'] === UPLOAD_ERR_OK && is_uploaded_file($f['tmp_name']) ? @getimagesize($f['tmp_name']) : false;
    $exts = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($exts[$info[2]]) || $f['size'] > 5 * 1024 * 1024) {
        flash('รูปภาพไม่ถูกต้อง (รองรับ JPG, PNG, GIF, WEBP ขนาดไม่เกิน 5MB)', 'danger');
        return false;
    }
    return store_upload($f['tmp_name'], $dir, $exts[$info[2]]);
}

function upload_file($field, $dir)
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name']) || !in_array($ext, DIGITAL_EXTS, true)) {
        flash('ไฟล์สินค้าไม่ถูกต้อง (รองรับ ' . implode(', ', DIGITAL_EXTS) . ')', 'danger');
        return false;
    }
    return store_upload($f['tmp_name'], $dir, $ext);
}

function store_upload($tmp, $dir, $ext)
{
    $rel = 'uploads/' . $dir;
    if (!is_dir(ROOT_PATH . '/' . $rel)) {
        mkdir(ROOT_PATH . '/' . $rel, 0775, true);
    }
    $rel .= '/' . bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($tmp, ROOT_PATH . '/' . $rel)) {
        flash('บันทึกไฟล์ไม่สำเร็จ', 'danger');
        return false;
    }
    return $rel;
}

// ---------------------------------------------------------------- กระเป๋าเงิน & Green Point
// ปรับยอดกระเป๋าเงิน (ค่าลบ = หักเงิน) คืน false เมื่อยอดเงินไม่พอ
function wallet_move($uid, $amount, $type, $note)
{
    $amount = round((float) $amount, 2);
    if ($amount == 0) {
        return true;
    }
    if ($amount < 0) {
        $st = q('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ? AND wallet_balance >= ?', [$amount, $uid, -$amount]);
        if (!$st->rowCount()) {
            return false;
        }
    } else {
        q('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?', [$amount, $uid]);
    }
    $bal = val('SELECT wallet_balance FROM users WHERE id = ?', [$uid]);
    q('INSERT INTO wallet_transactions (user_id, type, amount, balance_after, note) VALUES (?,?,?,?,?)', [$uid, $type, $amount, $bal, $note]);
    return true;
}

// เพิ่ม/ใช้ Green Point (ค่าลบ = ใช้คะแนน) คืน false เมื่อคะแนนไม่พอ
function add_points($uid, $points, $reason)
{
    $points = (int) $points;
    if ($points == 0) {
        return true;
    }
    if ($points < 0) {
        $st = q('UPDATE users SET green_points = green_points + ? WHERE id = ? AND green_points >= ?', [$points, $uid, -$points]);
        if (!$st->rowCount()) {
            return false;
        }
    } else {
        q('UPDATE users SET green_points = green_points + ? WHERE id = ?', [$points, $uid]);
    }
    q('INSERT INTO point_transactions (user_id, points, reason) VALUES (?,?,?)', [$uid, $points, $reason]);
    if ($points > 0) {
        check_achievements($uid);
    }
    return true;
}

function user_metrics($uid)
{
    return [
        'sales' => (int) val("SELECT COUNT(*) FROM orders WHERE seller_id = ? AND status = 'completed'", [$uid]),
        'purchases' => (int) val("SELECT COUNT(*) FROM orders WHERE buyer_id = ? AND status = 'completed'", [$uid]),
        'recycles' => (int) val("SELECT COUNT(*) FROM recycles WHERE user_id = ? AND status = 'completed'", [$uid]),
        'points' => (int) val('SELECT COALESCE(SUM(points), 0) FROM point_transactions WHERE user_id = ? AND points > 0', [$uid]),
    ];
}

function check_achievements($uid)
{
    $m = user_metrics($uid);
    $locked = rows('SELECT a.* FROM achievements a WHERE a.id NOT IN (SELECT achievement_id FROM user_achievements WHERE user_id = ?)', [$uid]);
    foreach ($locked as $a) {
        if ($m[$a['metric']] >= $a['threshold']) {
            q('INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?,?)', [$uid, $a['id']]);
            if (!empty($_SESSION['uid']) && $_SESSION['uid'] == $uid) {
                flash('ปลดล็อก Achievement ใหม่: ' . $a['name'], 'success');
            }
        }
    }
}

// ---------------------------------------------------------------- คำสั่งซื้อ
function in_tx(callable $fn)
{
    $pdo = db();
    if ($pdo->inTransaction()) {
        return $fn();
    }
    $pdo->beginTransaction();
    try {
        $r = $fn();
        $r === false ? $pdo->rollBack() : $pdo->commit();
        return $r;
    } catch (Exception $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

// ปิดคำสั่งซื้อ: โอนเงินให้ผู้ขาย (หักค่าธรรมเนียม) และแจก Green Point ทั้งสองฝ่าย
function complete_order($id)
{
    return in_tx(function () use ($id) {
        $o = row('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$id]);
        if (!$o || in_array($o['status'], ['completed', 'cancelled'], true)) {
            return false;
        }
        $seller = row('SELECT * FROM users WHERE id = ? FOR UPDATE', [$o['seller_id']]);
        $rate = (float) (is_premium($seller) ? setting('premium_commission_rate', 3) : setting('commission_rate', 5));
        if ($seller['fee_waivers'] > 0) {
            $rate = 0;
            q('UPDATE users SET fee_waivers = fee_waivers - 1 WHERE id = ?', [$seller['id']]);
        }
        $commission = round($o['price'] * $rate / 100, 2);
        $amount = $o['price'] - $commission;
        q("UPDATE orders SET status = 'completed', commission = ?, seller_amount = ?, completed_at = NOW() WHERE id = ?", [$commission, $amount, $id]);
        wallet_move($seller['id'], $amount, 'sale', 'ขายสินค้า ' . $o['product_title'] . ' (#' . $o['order_no'] . ') หักค่าธรรมเนียม ' . baht($commission));
        $digital = $o['type'] === 'digital';
        add_points($seller['id'], POINTS[$digital ? 'sell_digital' : 'sell_market'], ($digital ? 'ขาย Digital Product: ' : 'ขายสินค้ามือสอง: ') . $o['product_title']);
        add_points($o['buyer_id'], POINTS[$digital ? 'buy_digital' : 'buy_market'], ($digital ? 'ซื้อ Digital Product: ' : 'ซื้อสินค้ามือสอง: ') . $o['product_title']);
        return true;
    });
}

// ยกเลิกคำสั่งซื้อ: คืนเงิน คืนคะแนน คืนคูปอง และคืนสต็อก
function cancel_order($id)
{
    return in_tx(function () use ($id) {
        $o = row('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$id]);
        if (!$o || in_array($o['status'], ['completed', 'cancelled'], true)) {
            return false;
        }
        q("UPDATE orders SET status = 'cancelled' WHERE id = ?", [$id]);
        wallet_move($o['buyer_id'], $o['total'], 'refund', 'คืนเงินคำสั่งซื้อ #' . $o['order_no']);
        add_points($o['buyer_id'], $o['points_used'], 'คืนคะแนนจากคำสั่งซื้อ #' . $o['order_no']);
        if ($o['coupon_id']) {
            q("UPDATE redemptions SET status = 'active' WHERE id = ?", [$o['coupon_id']]);
        }
        if ($o['type'] === 'market') {
            q("UPDATE products SET stock = stock + 1, status = IF(status = 'sold', 'approved', status) WHERE id = ?", [$o['product_id']]);
        }
        return true;
    });
}

// ---------------------------------------------------------------- ส่วนแสดงผลที่ใช้ร่วมกัน
const PRODUCT_SELECT = 'SELECT p.*, c.name AS cat_name, c.icon AS cat_icon, u.name AS seller_name, u.premium_until AS seller_premium
    FROM products p LEFT JOIN categories c ON c.id = p.category_id JOIN users u ON u.id = p.user_id';

function product_image($p, $class = '')
{
    if (!empty($p['image'])) {
        return '<img src="' . e(url($p['image'])) . '" alt="' . e($p['title']) . '" class="' . $class . '" loading="lazy">';
    }
    $icon = !empty($p['cat_icon']) ? $p['cat_icon'] : ($p['type'] === 'digital' ? 'bi-file-earmark-richtext' : 'bi-box-seam');
    return '<div class="ph ph-' . e($p['type']) . ' ' . $class . '"><i class="bi ' . e($icon) . '"></i></div>';
}

function product_card($p)
{
    $promoted = $p['promoted_until'] && strtotime($p['promoted_until']) > time();
    $h = '<div class="card product-card h-100"><a href="' . e(url('product.php?id=' . $p['id'])) . '" class="thumb">' . product_image($p);
    if ($promoted) {
        $h .= '<span class="tag tag-promo"><i class="bi bi-megaphone-fill"></i> แนะนำ</span>';
    }
    $h .= '<span class="tag tag-type">' . ($p['type'] === 'digital' ? 'Digital' : 'มือสอง') . '</span></a>';
    $h .= '<div class="card-body"><a class="title stretched-link" href="' . e(url('product.php?id=' . $p['id'])) . '">' . e($p['title']) . '</a>';
    $h .= '<div class="meta">' . e($p['cat_name'] ?: 'ทั่วไป');
    if ($p['type'] === 'market' && isset(CONDITIONS[$p['item_condition']])) {
        $h .= ' · สภาพ' . CONDITIONS[$p['item_condition']][0];
    }
    $h .= '</div><div class="d-flex justify-content-between align-items-end mt-2"><span class="price">' . baht($p['price']) . '</span>';
    $h .= '<span class="score"><i class="bi bi-recycle"></i> ' . (int) $p['green_score'] . ' คะแนน</span></div></div></div>';
    return $h;
}

function banners($position)
{
    return rows('SELECT * FROM banners WHERE active = 1 AND position = ? ORDER BY sort_order, id', [$position]);
}

function banner_html($b, $class = '')
{
    $href = $b['link'] !== '' ? (preg_match('#^https?://#i', $b['link']) ? $b['link'] : url($b['link'])) : '#';
    $h = '<a href="' . e($href) . '" class="banner banner-' . e($b['position']) . ' ' . $class . '">';
    if ($b['image']) {
        $h .= '<img src="' . e(url($b['image'])) . '" alt="' . e($b['title']) . '">';
    } else {
        $h .= '<div class="banner-text"><span class="banner-label">' . ($b['position'] === 'side' ? 'Banner' : 'GreenLoop') . '</span><h2>' . e($b['title']) . '</h2>'
            . ($b['subtitle'] !== '' ? '<p>' . e($b['subtitle']) . '</p>' : '') . '</div><i class="bi bi-recycle banner-deco"></i>';
    }
    return $h . '</a>';
}

function avatar_html($u, $size = 36)
{
    if (!empty($u['avatar'])) {
        return '<img src="' . e(url($u['avatar'])) . '" class="avatar" style="width:' . $size . 'px;height:' . $size . 'px" alt="">';
    }
    return '<span class="avatar avatar-text" style="width:' . $size . 'px;height:' . $size . 'px;font-size:' . round($size * .42) . 'px">' . e(mb_substr($u['name'], 0, 1)) . '</span>';
}

function page_header($title, $subtitle = '')
{
    return '<div class="page-head"><h1 class="pill-title">' . e($title) . '</h1>' . ($subtitle ? '<p class="text-muted mb-0 mt-2">' . e($subtitle) . '</p>' : '') . '</div>';
}

function logo_html($height = 38)
{
    if (setting('logo')) {
        return '<img src="' . e(url(setting('logo'))) . '" alt="' . e(setting('site_name', 'GreenLoop')) . '" style="height:' . $height . 'px">';
    }
    return '<svg class="logo-mark" width="' . $height . '" height="' . $height . '" viewBox="0 0 48 48" aria-hidden="true"><path d="M30 4C18 4 10 10 10 20c0 3 1 6 3 8 1-8 6-14 14-18-5 5-9 11-10 20 9 1 17-6 17-17 0-3-1-6-4-9z" fill="#0f7b3f"/><path d="M8 38c0-4 3-7 7-7 6 0 8 10 16 10 4 0 7-3 7-6s-2-5-5-5c-2 0-4 1-6 4l-3-3c3-4 6-6 10-6 5 0 9 4 9 10s-5 11-12 11C20 46 19 36 14 36c-1 0-2 1-2 2s1 3 3 3c1 0 2 0 3-1l2 4c-2 1-4 2-6 2-4 0-6-4-6-8z" fill="#3fae5a"/></svg>'
        . '<span class="logo-text">' . e(setting('site_name', 'GreenLoop')) . '</span>';
}

// ---------------------------------------------------------------- แชท
function chat_threads($uid)
{
    return rows('SELECT t.other_id, u.name, u.avatar, u.role, m.body, m.created_at,
            (SELECT COUNT(*) FROM messages WHERE sender_id = t.other_id AND receiver_id = ? AND is_read = 0) AS unread
        FROM (SELECT IF(sender_id = ?, receiver_id, sender_id) AS other_id, MAX(id) AS last_id FROM messages WHERE sender_id = ? OR receiver_id = ? GROUP BY other_id) t
        JOIN users u ON u.id = t.other_id JOIN messages m ON m.id = t.last_id ORDER BY t.last_id DESC', [$uid, $uid, $uid, $uid]);
}

function chat_messages($a, $b)
{
    return rows('SELECT m.*, s.name AS sender_name, p.title AS product_title FROM messages m JOIN users s ON s.id = m.sender_id LEFT JOIN products p ON p.id = m.product_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) ORDER BY m.id', [$a, $b, $b, $a]);
}

// $meId = ผู้ที่ข้อความจะแสดงชิดขวา, $names = แสดงชื่อผู้ส่งทุกข้อความ (ใช้ตอนแอดมินดูประวัติแชท)
function render_messages($msgs, $meId, $names = false)
{
    $h = '';
    foreach ($msgs as $m) {
        $h .= '<div class="msg' . ($m['sender_id'] == $meId ? ' me' : '') . '">';
        if ($names) {
            $h .= '<div class="who">' . e($m['sender_name']) . '</div>';
        }
        if ($m['product_title']) {
            $h .= '<a class="d-block small text-decoration-underline text-reset" href="' . e(url('product.php?id=' . $m['product_id'])) . '"><i class="bi bi-box-seam"></i> ' . e($m['product_title']) . '</a>';
        }
        $h .= e($m['body']) . '<time>' . thai_date($m['created_at']) . '</time></div>';
    }
    return $h ?: '<div class="empty m-auto"><i class="bi bi-chat-dots"></i>เริ่มต้นการสนทนา</div>';
}

function send_message($from, $to, $body, $productId = null)
{
    $body = trim($body);
    if ($body === '' || $from == $to || !val('SELECT 1 FROM users WHERE id = ?', [$to])) {
        return false;
    }
    if ($productId && !val('SELECT 1 FROM products WHERE id = ?', [$productId])) {
        $productId = null;
    }
    q('INSERT INTO messages (sender_id, receiver_id, product_id, body) VALUES (?,?,?,?)', [$from, $to, $productId ?: null, mb_substr($body, 0, 2000)]);
    return true;
}
