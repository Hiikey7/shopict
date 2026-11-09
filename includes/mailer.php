<?php
declare(strict_types=1);

// Minimal mailer: SMTP (STARTTLS / SSL, AUTH LOGIN) or a "log" driver that saves each
// message to storage/mail/ for local testing. Configured in config.php under "mail".

/**
 * Sends an HTML email (with a plain-text alternative). Returns true when it was handed to the
 * mail server (or saved, with the log driver). Failures are logged, never thrown.
 */
function send_mail(string $to, string $subject, string $html, string $text, ?string $replyTo = null): bool
{
    $cfg = (array) config('mail') + ['driver' => 'log'];
    $fromEmail = (string) ($cfg['from_email'] ?? 'no-reply@localhost');
    $fromName = (string) ($cfg['from_name'] ?? setting('store_name', 'Shop ICT Gadgets'));
    $message = build_mime_message($to, $subject, $html, $text, $fromEmail, $fromName, $replyTo);

    try {
        if (($cfg['driver'] ?? 'log') === 'smtp') {
            smtp_send($cfg, $fromEmail, $to, $message);
        } else {
            $dir = __DIR__ . '/../storage/mail';
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $name = date('Ymd-His') . '-' . preg_replace('/[^a-z0-9]+/i', '-', $to) . '-' . bin2hex(random_bytes(3));
            file_put_contents("$dir/$name.html", "<!-- To: " . htmlspecialchars($to) . " | Subject: " . htmlspecialchars($subject) . " -->\n" . $html);
            file_put_contents("$dir/$name.eml", $message);
        }
        return true;
    } catch (Throwable $e) {
        error_log('mail to ' . $to . ' failed: ' . $e->getMessage());
        return false;
    }
}

function mime_header(string $value): string
{
    return preg_match('/[^\x20-\x7e]/', $value) ? '=?UTF-8?B?' . base64_encode($value) . '?=' : $value;
}

function build_mime_message(string $to, string $subject, string $html, string $text, string $fromEmail, string $fromName, ?string $replyTo): string
{
    $boundary = 'b' . bin2hex(random_bytes(12));
    $domain = substr(strrchr($fromEmail, '@') ?: '@localhost', 1);
    $headers = [
        'Date: ' . date('r'),
        'From: ' . mime_header($fromName) . ' <' . $fromEmail . '>',
        'To: <' . $to . '>',
        'Subject: ' . mime_header($subject),
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $domain . '>',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];
    if ($replyTo) {
        $headers[] = 'Reply-To: <' . $replyTo . '>';
    }
    $part = fn (string $type, string $body) => "--$boundary\r\nContent-Type: $type; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . rtrim(chunk_split(base64_encode($body), 76, "\r\n")) . "\r\n";
    return implode("\r\n", $headers) . "\r\n\r\n" . $part('text/plain', $text) . $part('text/html', $html) . "--$boundary--\r\n";
}

/** @throws RuntimeException on any SMTP error */
function smtp_send(array $cfg, string $from, string $to, string $message): void
{
    $host = (string) $cfg['host'];
    $port = (int) ($cfg['port'] ?? 587);
    $enc = strtolower((string) ($cfg['encryption'] ?? 'tls'));
    $remote = ($enc === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $ctx = stream_context_create(['ssl' => ['peer_name' => $host, 'verify_peer' => true, 'verify_peer_name' => true]]);
    $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        throw new RuntimeException("cannot connect to $remote: $errstr ($errno)");
    }
    stream_set_timeout($fp, 20);

    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = function (?string $line, array $expect) use ($fp, $read): string {
        if ($line !== null) {
            fwrite($fp, $line . "\r\n");
        }
        $reply = $read();
        if (!in_array((int) substr($reply, 0, 3), $expect, true)) {
            throw new RuntimeException('SMTP: ' . trim($reply ?: 'no reply') . ($line !== null && !str_starts_with($line, 'AUTH') && strlen($line) < 200 ? " (after: $line)" : ''));
        }
        return $reply;
    };

    try {
        $cmd(null, [220]);
        $me = gethostname() ?: 'localhost';
        $cmd("EHLO $me", [250]);
        if ($enc === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                throw new RuntimeException('could not start TLS');
            }
            $cmd("EHLO $me", [250]);
        }
        if (($cfg['username'] ?? '') !== '') {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode((string) $cfg['username']), [334]);
            $cmd(base64_encode((string) $cfg['password']), [235]);
        }
        $cmd("MAIL FROM:<$from>", [250]);
        $cmd("RCPT TO:<$to>", [250, 251]);
        $cmd('DATA', [354]);
        // Dot-stuffing: a line starting with "." gets an extra one.
        $cmd(preg_replace('/^\./m', '..', $message) . "\r\n.", [250]);
        $cmd('QUIT', [221]);
    } finally {
        fclose($fp);
    }
}
