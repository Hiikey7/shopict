<?php
declare(strict_types=1);

/** Escape text for HTML output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Public URL of a file under assets/ (images, css, js...). */
function asset(string $path): string
{
    if (preg_match('#^https?://#i', $path)) {
        return image_url($path); // a picture stored on Cloudinary (or elsewhere)
    }
    $path = ltrim($path, '/');
    // The checked-in product uploads were optimized to WebP after the SQL snapshot
    // was exported, while the snapshot still refers to their former PNG/JPEG names.
    // Prefer the WebP sibling when the legacy filename is absent.
    $local = __DIR__ . '/../assets/' . $path;
    if (!is_file($local) && preg_match('/\.(?:png|jpe?g)$/i', $path)) {
        $webpPath = preg_replace('/\.(?:png|jpe?g)$/i', '.webp', $path);
        if (is_file(__DIR__ . '/../assets/' . $webpPath)) {
            $path = $webpPath;
        }
    }
    return 'assets/' . $path;
}

/**
 * Link to a Cloudinary picture with automatic format (WebP/AVIF) and quality, and when $width is given,
 * no wider than needed (2× for sharp phone screens). Other links are returned unchanged.
 */
function image_url(string $url, ?int $width = null): string
{
    if (!str_contains($url, 'res.cloudinary.com/') || !str_contains($url, '/image/upload/')) {
        return $url;
    }
    $t = 'f_auto,q_auto' . ($width ? ',c_limit,w_' . min(1600, $width * 2) : '');
    return preg_replace('#/image/upload/#', '/image/upload/' . $t . '/', $url, 1);
}

/** Whether Cloudinary details are filled in (config.php). */
function cloudinary_enabled(): bool
{
    $c = (array) config('cloudinary');
    return !empty($c['cloud_name']) && !empty($c['api_key']) && !empty($c['api_secret']);
}

/** Uploads a picture file to Cloudinary. Returns ['path' => secure URL, 'width' => .., 'height' => ..]. */
function cloudinary_upload(string $file, ?string $publicId = null): array
{
    $c = (array) config('cloudinary');
    $params = ['folder' => $c['folder'] ?? 'shopict', 'timestamp' => time()];
    if ($publicId !== null) {
        $params['public_id'] = $publicId;
        $params['overwrite'] = 'true';
    }
    ksort($params);
    $toSign = implode('&', array_map(fn ($k, $v) => "$k=$v", array_keys($params), $params));
    $params['signature'] = sha1($toSign . $c['api_secret']);
    $params['api_key'] = $c['api_key'];
    $params['file'] = new CURLFile($file);
    $ch = curl_init('https://api.cloudinary.com/v1_1/' . rawurlencode($c['cloud_name']) . '/image/upload');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $params, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $res = json_decode((string) $body, true);
    if (!is_array($res) || empty($res['secure_url'])) {
        throw new RuntimeException('Cloudinary upload failed: ' . ($res['error']['message'] ?? $err ?: 'no answer'));
    }
    return ['path' => $res['secure_url'], 'width' => (int) $res['width'], 'height' => (int) $res['height']];
}

/**
 * Makes a picture lighter: at most $max pixels wide/high, saved as WebP (keeps transparency).
 * Returns the new file path, or the original when it can't be improved.
 */
function optimize_image(string $file, int $max = 1600): string
{
    $info = @getimagesize($file);
    if (!$info || !function_exists('imagewebp')) {
        return $file;
    }
    $src = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($file),
        IMAGETYPE_PNG  => @imagecreatefrompng($file),
        IMAGETYPE_WEBP => @imagecreatefromwebp($file),
        default        => false, // GIFs are left as they are (may be animated)
    };
    if (!$src) {
        return $file;
    }
    [$w, $h] = [$info[0], $info[1]];
    $scale = min(1, $max / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $out = imagecreatetruecolor($nw, $nh);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
    imagecopyresampled($out, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $base = tempnam(sys_get_temp_dir(), 'img');
    @unlink($base);
    $dest = $base . '.webp';
    $ok = imagewebp($out, $dest, 82);
    if (!$ok || ($scale === 1.0 && filesize($dest) >= filesize($file))) { // not smaller: keep the original
        @unlink($dest);
        return $file;
    }
    return $dest;
}

/** A product name cut to $max characters with "…" (whole words where possible), for cart lists. */
function short_title(string $title, int $max = 40): string
{
    $title = trim($title);
    if (mb_strlen($title) <= $max) {
        return $title;
    }
    $cut = mb_substr($title, 0, $max);
    $space = mb_strrpos($cut, " ");
    if ($space !== false && $space > $max * 0.6) {
        $cut = mb_substr($cut, 0, $space);
    }
    return rtrim($cut, " ,.-–—:;/(") . "…";
}

/** An asset link with its file date added (?v=…), so browsers fetch the new copy after a change. */
function asset_v(string $path): string
{
    $file = __DIR__ . "/../assets/" . ltrim($path, "/");
    return asset($path) . (is_file($file) ? "?v=" . filemtime($file) : "");
}

/** Limits requests by a hashed client IP and scope; trusts REMOTE_ADDR, never forwarded headers. */
function request_rate_limited(string $scope, int $limit, int $windowSeconds): bool
{
    if ($limit < 1 || $windowSeconds < 1) {
        throw new InvalidArgumentException('Rate limits must be positive.');
    }
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $key = hash('sha256', $scope . "\0" . (filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown'));
    if (function_exists('apcu_enabled') && apcu_enabled() && function_exists('apcu_add') && function_exists('apcu_inc')) {
        $cacheKey = 'sict_rate_limit_' . $key;
        for ($attempt = 0; $attempt < 2; $attempt++) {
            if (apcu_add($cacheKey, 1, $windowSeconds)) {
                return false;
            }
            $success = false;
            $count = apcu_inc($cacheKey, 1, $success);
            if ($success) {
                return $count > $limit;
            }
        }
        throw new RuntimeException('Unable to update the APCu rate-limit counter.');
    }

    $file = __DIR__ . '/../storage/request-rate-limits.json';
    $handle = @fopen($file, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Unable to open the request rate-limit store.');
    }
    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Unable to lock the request rate-limit store.');
        }
        $contents = stream_get_contents($handle);
        $state = $contents === '' ? [] : json_decode($contents, true);
        if (!is_array($state) || ($contents !== '' && json_last_error() !== JSON_ERROR_NONE)) {
            throw new RuntimeException('The request rate-limit store is invalid JSON.');
        }

        $now = time();
        $cutoff = $now - $windowSeconds;
        $newKey = !array_key_exists($key, $state);
        $attempts = $state[$key] ?? [];
        if (!is_array($attempts)) {
            throw new RuntimeException('The request rate-limit entry is invalid.');
        }
        $attempts = array_values(array_filter($attempts, static fn ($at) => is_int($at) && $at > $cutoff));
        $limited = count($attempts) >= $limit;
        if (!$limited) {
            $attempts[] = $now;
        }
        $state[$key] = $attempts;

        if (count($state) > 4096) {
            foreach ($state as $entryKey => $entryAttempts) {
                if (!is_array($entryAttempts)) {
                    throw new RuntimeException('The request rate-limit entry is invalid.');
                }
                $state[$entryKey] = array_values(array_filter($entryAttempts, static fn ($at) => is_int($at) && $at > $cutoff));
                if ($state[$entryKey] === []) {
                    unset($state[$entryKey]);
                }
            }
            if ($newKey && count($state) >= 4096) {
                unset($state[$key]);
                $limited = true;
            }
        }

        $encoded = json_encode($state, JSON_THROW_ON_ERROR);
        rewind($handle);
        if (!ftruncate($handle, 0) || fwrite($handle, $encoded) !== strlen($encoded) || !fflush($handle)) {
            throw new RuntimeException('Unable to persist the request rate-limit store.');
        }
        flock($handle, LOCK_UN);
        return $limited;
    } finally {
        fclose($handle);
    }
}

/**
 * Makes the app icons from the store logo: install icons (192, 512, maskable 512), the phone home-screen
 * icon (180), the favicon (64 + favicon.ico) and the fallback logo. Returns false if the logo can't be read.
 */
function rebuild_app_icons(?string $logo = null): bool
{
    $logo ??= store_logo();
    $data = preg_match('#^https?://#i', $logo) ? @file_get_contents($logo) : @file_get_contents(__DIR__ . '/../assets/' . $logo);
    $src = $data !== false ? @imagecreatefromstring($data) : false;
    if (!$src) {
        return false;
    }
    [$w, $h] = [imagesx($src), imagesy($src)];
    $dir = __DIR__ . '/../assets/images';
    // $pad: share of each side left empty. White background for app icons; clear background for the favicon.
    $make = function (int $size, float $pad, bool $white) use ($src, $w, $h) {
        $im = imagecreatetruecolor($size, $size);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, $white ? imagecolorallocate($im, 255, 255, 255) : imagecolorallocatealpha($im, 0, 0, 0, 127));
        $box = $size * (1 - 2 * $pad);
        $scale = min($box / $w, $box / $h);
        $dw = (int) round($w * $scale);
        $dh = (int) round($h * $scale);
        imagecopyresampled($im, $src, (int) (($size - $dw) / 2), (int) (($size - $dh) / 2), 0, 0, $dw, $dh, $w, $h);
        return $im;
    };
    imagepng($make(192, 0.08, true), $dir . '/pwa/icon-192.png', 9);
    imagepng($make(512, 0.08, true), $dir . '/pwa/icon-512.png', 9);
    imagepng($make(512, 0.20, true), $dir . '/pwa/icon-maskable-512.png', 9); // Android crops to a circle
    imagepng($make(180, 0.10, true), $dir . '/logo/apple-touch-icon.png', 9);
    imagepng($make(64, 0.02, false), $dir . '/logo/favicon-64.png', 9);
    // favicon.ico holding 32×32 and 48×48 PNGs
    $ico = '';
    $entries = '';
    $offset = 6 + 16 * 2;
    foreach ([32, 48] as $s) {
        ob_start();
        imagepng($make($s, 0.02, false));
        $png = ob_get_clean();
        $entries .= pack('CCCCvvVV', $s, $s, 0, 0, 1, 32, strlen($png), $offset);
        $offset += strlen($png);
        $ico .= $png;
    }
    file_put_contents(__DIR__ . '/../favicon.ico', pack('vvv', 0, 1, 2) . $entries . $ico);
    return true;
}

/** The store logo (Admin → Settings), as a path under assets/. */
function store_logo(): string
{
    return setting('logo') ?: 'images/logo/logo.png';
}

/** Currency shown in front of every price. Prices are stored and shown in whole shillings. */
const CURRENCY_SYMBOL = 'KES';

/** A price for display, e.g. "KES12,499". */
function money($amount): string
{
    return CURRENCY_SYMBOL . number_format(round((float) $amount), 0, '.', ',');
}

/** Format a price entered as banner text, normalizing KSh/KES and grouping whole shillings. */
function money_text($value): string
{
    $text = trim((string) $value);
    if ($text === '') {
        return '';
    }
    $text = preg_replace('/\b(?:KES|KSh)\s*/iu', '', $text);
    if (preg_match('/^([^\d]*)([\d,]+(?:\.\d+)?)(.*)$/u', $text, $m)) {
        $prefix = trim($m[1]);
        $amount = number_format(round((float) str_replace(',', '', $m[2])), 0, '.', ',');
        return ($prefix !== '' ? $prefix . ' ' : '') . CURRENCY_SYMBOL . $amount . $m[3];
    }
    return $text;
}

/** A plain number for form fields: "12499" or "12499.5", without trailing zeros. */
function plain_number($amount): string
{
    return rtrim(rtrim(number_format((float) $amount, 2, '.', ''), '0'), '.');
}

/** Same as JavaScript's Number.prototype.toFixed(). */
function fixed($number, int $decimals): string
{
    return number_format((float) $number, $decimals, '.', '');
}

/** Same as JavaScript's Number.prototype.toLocaleString() for en-US integers. */
function locale_number($number): string
{
    $n = (float) $number;
    return number_format($n, floor($n) == $n ? 0 : 3, '.', ',');
}

/** Escapes multi-line text and joins the lines with the given line-break markup. */
function lines(?string $text, string $br = '<br/>'): string
{
    return implode($br, array_map('e', preg_split('/\r\n|\r|\n/', (string) $text)));
}

/** Renders an <img> with the same attributes next/image produced in the original site. */
function img(string $src, $width, $height, string $class = '', string $alt = '', string $style = 'color:transparent', string $loading = 'lazy'): string
{
    $loading = $loading === 'eager' ? 'eager' : 'lazy';
    return '<img alt="' . e($alt) . '" loading="' . $loading . '"' . ($loading === 'eager' ? ' fetchpriority="high"' : ' data-image-skeleton') . ' width="' . e($width) . '" height="' . e($height) . '" decoding="async" data-nimg="1"'
        . ($class !== '' ? ' class="' . e($class) . '"' : '')
        . ' style="' . e($style) . '" src="' . e(is_numeric($width) && preg_match('#^https?://#i', $src) ? image_url($src, (int) $width) : asset($src)) . '"/>';
}

/** Include a template from partials/ with the given variables in scope. */
function partial(string $name, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    include __DIR__ . '/../partials/' . $name . '.php';
}

/** Capture a partial's output as a string. */
function render(string $name, array $vars = []): string
{
    ob_start();
    partial($name, $vars);
    return (string) ob_get_clean();
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/** Reads the JSON body of an API request and checks the request method and CSRF token. */
function api_input(): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        json_response(['ok' => false, 'error' => 'Invalid session, please reload the page'], 419);
    }
    $data = json_decode((string) file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}
