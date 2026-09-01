<?php
/**
 * Reset demo user passwords to password123
 * Usage: php fix_passwords.php
 */
declare(strict_types=1);

$hash = password_hash('password123', PASSWORD_DEFAULT);
$sql = "USE sales_prediction_db;\nUPDATE users SET password_hash=" . var_export($hash, true) . ";\n";
$out = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'spms_pwd.sql';
file_put_contents($out, $sql);
echo $out;
