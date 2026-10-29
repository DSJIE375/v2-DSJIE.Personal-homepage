<?php
/** Minimal SMTP transport for guestbook notifications; supports SSL, STARTTLS and AUTH LOGIN. */
function site_smtp_write($socket, string $data): bool {
    while ($data !== '') {
        $written = @fwrite($socket, $data);
        if ($written === false || $written === 0) return false;
        $data = substr($data, $written);
    }
    return true;
}

function site_smtp_read($socket): ?array {
    $response = '';
    $code = 0;
    do {
        $line = @fgets($socket, 2048);
        if ($line === false || !preg_match('/^(\d{3})([ -])/', $line, $match)) return null;
        $code = (int)$match[1];
        $response .= $line;
    } while ($match[2] === '-');
    return [$code, $response];
}

function site_smtp_command($socket, string $command, array $expectedCodes): bool {
    if (!site_smtp_write($socket, $command . "\r\n")) return false;
    $response = site_smtp_read($socket);
    return $response !== null && in_array($response[0], $expectedCodes, true);
}

function site_smtp_send(array $config, string $to, string $subject, string $body, string $htmlBody = ''): bool {
    $host = trim((string)($config['smtp_host'] ?? ''));
    $port = (int)($config['smtp_port'] ?? 0);
    $secure = (string)($config['smtp_secure'] ?? 'tls');
    $username = (string)($config['smtp_username'] ?? '');
    $password = (string)($config['smtp_password'] ?? '');
    $from = trim((string)($config['mail_from'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9.-]+$/', $host) || $port < 1 || $port > 65535 || !in_array($secure, ['tls', 'ssl', 'none'], true)) return false;
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) return false;

    $transport = $secure === 'ssl' ? 'ssl://' : 'tcp://';
    $context = stream_context_create(['ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'peer_name' => $host,
        'SNI_enabled' => true,
    ]]);
    $socket = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) return false;
    stream_set_timeout($socket, 15);

    try {
        $greeting = site_smtp_read($socket);
        if ($greeting === null || $greeting[0] !== 220) return false;
        $clientName = preg_replace('/[^A-Za-z0-9.-]/', '', (string)(gethostname() ?: 'localhost')) ?: 'localhost';
        if (!site_smtp_command($socket, 'EHLO ' . $clientName, [250])) return false;

        if ($secure === 'tls') {
            if (!site_smtp_command($socket, 'STARTTLS', [220])) return false;
            $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($crypto !== true || !site_smtp_command($socket, 'EHLO ' . $clientName, [250])) return false;
        }

        if ($username !== '') {
            if (!site_smtp_command($socket, 'AUTH LOGIN', [334])
                || !site_smtp_command($socket, base64_encode($username), [334])
                || !site_smtp_command($socket, base64_encode($password), [235])) return false;
        }

        if (!site_smtp_command($socket, 'MAIL FROM:<' . $from . '>', [250])) return false;
        if (!site_smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251])) return false;
        if (!site_smtp_command($socket, 'DATA', [354])) return false;

        $siteName = preg_replace('/[\r\n]+/', ' ', trim((string)($config['site_name'] ?? '网站通知')));
        $encodedName = '=?UTF-8?B?' . base64_encode($siteName) . '?=';
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $message = 'From: ' . $encodedName . ' <' . $from . ">\r\n"
            . 'To: <' . $to . ">\r\n"
            . 'Subject: ' . $encodedSubject . "\r\n"
            . 'Date: ' . gmdate('D, d M Y H:i:s') . " +0000\r\n"
            . 'MIME-Version: 1.0' . "\r\n";
        if ($htmlBody !== '') {
            $boundary = 'alt_' . bin2hex(random_bytes(12));
            $message .= 'Content-Type: multipart/alternative; boundary="' . $boundary . "\"\r\n\r\n"
                . '--' . $boundary . "\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($body), 76, "\r\n") . "\r\n"
                . '--' . $boundary . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($htmlBody), 76, "\r\n") . "\r\n"
                . '--' . $boundary . "--\r\n";
        } else {
            $message .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($body), 76, "\r\n");
        }
        $message = preg_replace('/^\./m', '..', $message);
        if (!site_smtp_write($socket, $message . ".\r\n")) return false;
        $accepted = site_smtp_read($socket);
        if ($accepted === null || $accepted[0] !== 250) return false;
        site_smtp_command($socket, 'QUIT', [221]);
        return true;
    } catch (\Throwable $e) {
        return false;
    } finally {
        if (is_resource($socket)) fclose($socket);
    }
}