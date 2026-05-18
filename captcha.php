<?php
// Generates CAPTCHA image and stores the code in $_SESSION['captcha']
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$chars   = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$length  = 5;
$captcha = '';
for ($i = 0; $i < $length; $i++) {
    $captcha .= $chars[random_int(0, strlen($chars) - 1)];
}
$_SESSION['captcha'] = $captcha;

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Expires: 0');

$w   = 160;
$h   = 50;
$img = imagecreatetruecolor($w, $h);

$bg    = imagecolorallocate($img, 240, 240, 255);
$noise = imagecolorallocate($img, 180, 180, 210);

imagefill($img, 0, 0, $bg);

for ($i = 0; $i < 350; $i++) {
    imagesetpixel($img, random_int(0, $w - 1), random_int(0, $h - 1), $noise);
}
for ($i = 0; $i < 5; $i++) {
    imageline($img,
        random_int(0, $w), random_int(0, $h),
        random_int(0, $w), random_int(0, $h),
        $noise
    );
}
for ($i = 0; $i < $length; $i++) {
    $col = imagecolorallocate($img, random_int(0, 80), random_int(0, 80), random_int(120, 220));
    $x   = 12 + $i * 28;
    $y   = random_int(5, 18);
    imagechar($img, 5, $x, $y, $captcha[$i], $col);
}

imagepng($img);
imagedestroy($img);
exit;
