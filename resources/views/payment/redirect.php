<?php

/**
 * نمای انتقال به درگاه واقعی (زرین‌پال و…) — CSP-سازگار.
 *
 * نمای پیش‌فرض پکیج با اسکریپت درون‌خطیِ اتوسابمیت کار می‌کند که CSP
 * پروژه بلاکش می‌کند. این نما فرم را با دکمهٔ submit واقعی می‌فرستد
 * (بدون جاوااسکریپت) — یک کلیک اضافه، اما همیشه کار می‌کند.
 *
 * متغیرهای $action, $inputs, $method از RedirectionForm::render().
 */

$e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$relative = function (string $url): string {
    $path = (string) parse_url($url, PHP_URL_PATH);
    $query = (string) parse_url($url, PHP_URL_QUERY);
    if ($path === '' || $path === '/') { return $url; }
    return $query ? $path.'?'.$query : $path;
};

/* فاز ۱۳ — رنگ‌های پالت از پنل اپ تزریق می‌شود (PaymentGatewayService)؛
   در صورت نبود، آبی پیش‌فرض fallback می‌شود. */
$c = fn (string $k, string $fb): string => htmlspecialchars((string) ($inputs[$k] ?? $fb), ENT_QUOTES, 'UTF-8');

$brand600 = $c('brand600', '#2563eb');
$brand700 = $c('brand700', '#1d4ed8');
$brand900 = $c('brand900', '#1e3a8a');
$brand100 = $c('brand100', '#dbeafe');
$brand50 = $c('brand50', '#eff6ff');
$pageBg = $c('pageBg', '#f4f7fb');

$rgba = function (string $hex, float $a): string {
    $h = ltrim($hex, '#');
    if (strlen($h) === 3) { $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2]; }
    if (strlen($h) !== 6 || ! ctype_xdigit($h)) { return 'rgba(30,58,138,'.$a.')'; }
    $r = hexdec(substr($h, 0, 2));
    $g = hexdec(substr($h, 2, 2));
    $b = hexdec(substr($h, 4, 2));
    return 'rgba('.$r.','.$g.','.$b.','.$a.')';
};

/* کلیدهای رنگی فقط برای ظاهرند — نباید به درگاه واقعی POST شوند */
$paletteKeys = ['brand50', 'brand100', 'brand300', 'brand600', 'brand700', 'brand900', 'pageBg'];
$gatewayInputs = array_filter(
    (array) ($inputs ?? []),
    fn ($k) => ! in_array((string) $k, $paletteKeys, true),
    ARRAY_FILTER_USE_KEY
);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>انتقال به درگاه پرداخت</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            font-family: 'Vazirmatn', 'Segoe UI', Tahoma, Arial, sans-serif;
            background:
                radial-gradient(1000px 500px at 100% -10%, <?= $rgba($brand600, 0.10) ?>, transparent 60%),
                <?= $pageBg ?>;
            color: <?= $brand900 ?>;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .rd-card {
            width: 100%;
            max-width: 380px;
            background: #fff;
            border: 1px solid <?= $brand100 ?>;
            border-radius: 20px;
            box-shadow: 0 18px 50px <?= $rgba($brand900, 0.12) ?>;
            padding: 30px 24px 26px;
            text-align: center;
        }
        .rd-dots { display: flex; justify-content: center; gap: 8px; margin-bottom: 18px; }
        .rd-dots span { width: 12px; height: 12px; border-radius: 999px; background: <?= $brand600 ?>; opacity: .25; }
        .rd-dots span:nth-child(1) { opacity: 1; }
        .rd-dots span:nth-child(2) { opacity: .55; }
        .rd-title { font-size: 16.5px; font-weight: 800; }
        .rd-desc { font-size: 12.5px; color: <?= $brand700 ?>; margin: 8px 0 20px; line-height: 2; }
        .rd-btn {
            -webkit-appearance: none; appearance: none; font: inherit; cursor: pointer; border: 0;
            width: 100%;
            background: linear-gradient(100deg, <?= $brand600 ?>, <?= $brand700 ?>);
            color: #fff;
            border-radius: 12px;
            padding: 14px 10px;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 8px 20px <?= $rgba($brand700, 0.30) ?>;
            transition: transform .15s, box-shadow .15s;
        }
        .rd-btn:hover { transform: translateY(-1px); box-shadow: 0 10px 26px <?= $rgba($brand700, 0.38) ?>; }
        .rd-btn:focus-visible { outline: 3px solid <?= $rgba($brand600, 0.30) ?>; outline-offset: 2px; }
    </style>
</head>
<body>
    <div class="rd-card">
        <div class="rd-dots" aria-hidden="true"><span></span><span></span><span></span></div>
        <h1 class="rd-title">در حال انتقال به درگاه پرداخت</h1>
        <p class="rd-desc">برای ادامهٔ پرداخت امن، روی دکمهٔ زیر بزنید.</p>
        <form method="<?= $e($method) ?>" action="<?= $e($relative($action)) ?>">
            <?php foreach ($gatewayInputs as $name => $value): ?>
                <input type="hidden" name="<?= $e($name) ?>" value="<?= $e($value) ?>">
            <?php endforeach; ?>
            <button type="submit" class="rd-btn">ادامهٔ پرداخت</button>
        </form>
    </div>
</body>
</html>
