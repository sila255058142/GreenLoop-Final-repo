<?php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล (ค่าเริ่มต้นตรงกับ XAMPP: root / ไม่มีรหัสผ่าน)
// สามารถกำหนดผ่าน environment variable GL_DB_* แทนการแก้ไฟล์นี้ได้
function gl_env($name, $default)
{
    $v = getenv($name);
    return $v === false ? $default : $v;
}

return [
    'db_host' => gl_env('GL_DB_HOST', '127.0.0.1'),
    'db_port' => gl_env('GL_DB_PORT', '3306'),
    'db_name' => gl_env('GL_DB_NAME', 'greenloop'),
    'db_user' => gl_env('GL_DB_USER', 'root'),
    'db_pass' => gl_env('GL_DB_PASS', ''),
];
