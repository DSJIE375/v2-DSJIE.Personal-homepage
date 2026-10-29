<?php
/** 公共函数库 */
require_once __DIR__ . '/config.php';

/** 读取站点数据 */
function load_data(): array {
    $raw = @file_get_contents(DATA_FILE);
    $d = $raw ? json_decode($raw, true) : null;
    if (!is_array($d)) $d = [];
    $defaults = ['site'=>[], 'about'=>[], 'works'=>[], 'essays'=>[],
                 'gallery'=>[], 'downloads'=>[], 'messages'=>[],
                 'updates'=>[], 'friends'=>[]];
    foreach ($defaults as $k => $v) if (!isset($d[$k]) || !is_array($d[$k])) $d[$k] = $v;
    $identity = identity_config();
    $legacyCredentials = [];
    foreach (['master_qq', 'master_pwd'] as $key) {
        if (empty($identity[$key]) && !empty($d['site'][$key])) $legacyCredentials[$key] = (string)$d['site'][$key];
    }
    if ($legacyCredentials && save_identity_config($legacyCredentials)) $identity = identity_config();
    $changed = false;
    if (!empty($identity['role_key'])) {
        foreach (['master_qq', 'master_pwd'] as $key) {
            if (array_key_exists($key, $d['site'])) {
                unset($d['site'][$key]);
                $changed = true;
            }
            if (array_key_exists($key, $identity)) $d['site'][$key] = (string)$identity[$key];
        }
        $changed = migrate_guestbook_role_signatures($d, $identity) || $changed;
        if ($changed) save_data($d);
    }
    return $d;
}

/** 写前放开权限：解压后权限/属主不对时的兜底（本站进程属主 → chmod 755 后即可写入） */
function ensure_writable(string $file): void {
    if (is_file($file)) {
        @chmod($file, 0755);
        if (!is_writable($file)) @chmod($file, 0777);
    } else {
        $d = dirname($file);
        if (is_dir($d)) @chmod($d, 0755);
    }
}
/** 自动写入：直写 → 修权限重试 → 临时文件+改名（改名只看目录权限，绕开文件属主不是 www 的场景） */
function write_auto(string $file, string $content): bool {
    ensure_writable($file);
    if (@file_put_contents($file, $content, LOCK_EX) !== false) return true;
    $d = dirname($file);
    if (is_dir($d) && !is_writable($d)) @chmod($d, 0777);
    if (!is_dir($d) || !is_writable($d)) return false;
    $tmp = $file . '.tmp';
    if (@file_put_contents($tmp, $content, LOCK_EX) === false) return false;
    @chmod($tmp, 0666);
    if (@rename($tmp, $file)) return true;
    @unlink($tmp);
    return false;
}

/** 保存站点数据（加锁写入，失败时自动临时文件+改名替换） */
function save_data(array $d): bool {
    if (isset($d['site']) && is_array($d['site'])) {
        unset($d['site']['master_qq'], $d['site']['master_pwd']);
    }
    $json = json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    return write_auto(DATA_FILE, $json);
}

/** HTML 转义 */
function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** 生成唯一 ID */
function nid(): string {
    return uniqid('', true) . mt_rand(100, 999);
}

/** 当前时间字符串 */
function now_str(): string {
    return date('Y-m-d H:i');
}

/** 清洗普通文本输入 */
function clean_text($s, int $max = 500): string {
    $s = trim(strip_tags((string)$s));
    return mb_substr($s, 0, $max, 'UTF-8');
}

/** 私有身份配置：身份密钥与站长凭据不进入公开内容 JSON */
function identity_config(): array {
    if (!defined('IDENTITY_FILE') || !is_file(IDENTITY_FILE)) return [];
    $identity = @include IDENTITY_FILE;
    return is_array($identity) ? $identity : [];
}

function save_identity_config(array $updates): bool {
    $identity = identity_config();
    foreach (['master_qq', 'master_pwd', 'mail_notify_to', 'mail_from', 'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_username', 'smtp_password'] as $key) {
        if (array_key_exists($key, $updates)) $identity[$key] = (string)$updates[$key];
    }
    foreach (['mail_notify_to', 'mail_from'] as $key) {
        if (!empty($identity[$key]) && !filter_var($identity[$key], FILTER_VALIDATE_EMAIL)) return false;
    }
    if (!empty($identity['smtp_host'])) {
        $port = (int)($identity['smtp_port'] ?? 0);
        if ($port < 1 || $port > 65535) return false;
        if (!in_array($identity['smtp_secure'] ?? '', ['tls', 'ssl', 'none'], true)) return false;
    }
    if (empty($identity['role_key'])) return false;
    $php = "<?php\nreturn " . var_export($identity, true) . ";\n";    return write_auto(IDENTITY_FILE, $php);
}

function smtp_notification_status(): string {
    $identity = identity_config();
    $from = trim((string)($identity['mail_from'] ?? ''));
    if (empty($identity['smtp_host'])) return '尚未填写 SMTP 服务器地址。';
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) return '发件邮箱为空或格式无效。';
    if ((int)($identity['smtp_port'] ?? 0) < 1) return 'SMTP 端口无效。';
    if (!in_array($identity['smtp_secure'] ?? '', ['tls', 'ssl', 'none'], true)) return 'SMTP 加密方式无效。';
    if (in_array($identity['smtp_secure'], ['tls', 'ssl'], true) && !extension_loaded('openssl')) return 'PHP 未启用 OpenSSL，无法建立 TLS/SSL 连接。';
    if (!empty($identity['smtp_username']) && empty($identity['smtp_password'])) return 'SMTP 授权码未设置。';
    return '';
}

function notification_email_html(string $title, string $intro, array $details): string {
    $siteName = trim((string)(load_data()['site']['name'] ?? '网站通知'));
    $rows = '';
    foreach ($details as $label => $value) {
        $rows .= '<tr><td style="padding:0 0 14px;color:#777;font-size:12px;vertical-align:top;width:100px">' . e($label) . '</td>'
            . '<td style="padding:0 0 14px;color:#222;font-size:14px;line-height:1.7;word-break:break-word">'
            . nl2br(e((string)$value), false) . '</td></tr>';
    }
    return '<!doctype html><html lang="zh-CN"><body style="margin:0;padding:24px 12px;background:#f2f3f5;color:#222;font-family:Arial,\'Microsoft YaHei\',sans-serif">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">'
        . '<tr><td style="padding:20px 24px;background:#111827;color:#fff"><div style="font-size:12px;opacity:.72">' . e($siteName) . '</div>'
        . '<div style="margin-top:6px;font-size:20px;font-weight:700">' . e($title) . '</div></td></tr>'
        . '<tr><td style="padding:24px"><p style="margin:0 0 20px;color:#555;font-size:14px;line-height:1.7">' . e($intro) . '</p>'
        . '<table role="presentation" cellpadding="0" cellspacing="0" width="100%">' . $rows . '</table></td></tr>'
        . '<tr><td style="padding:14px 24px;border-top:1px solid #eee;color:#999;font-size:11px">此邮件由 ' . e($siteName) . ' 自动发送，请勿直接回复。</td></tr>'
        . '</table></body></html>';
}

/** Send UTF-8 plain text and optional branded HTML through the configured SMTP server. */
function send_notification_email(string $to, string $subject, string $body, string $htmlBody = ''): bool {
    $identity = identity_config();
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || smtp_notification_status() !== '') return false;
    require_once __DIR__ . '/smtp_mailer.php';
    $siteName = trim((string)(load_data()['site']['name'] ?? '网站通知'));
    $identity['site_name'] = $siteName;
    $subject = '[' . $siteName . '] ' . $subject;
    $body = '网站：' . $siteName . "\n\n" . $body;
    return site_smtp_send($identity, $to, $subject, $body, $htmlBody);
}

/** 角色签名覆盖身份与留言正文，改动 content.json 任一字段都会使签名失效 */
function guestbook_role_signature(array $entry): string {
    $identity = identity_config();
    $key = (string)($identity['role_key'] ?? '');
    if ($key === '') return '';
    $fields = [];
    foreach (['id', 'qq', 'email', 'text', 'time', 'role'] as $field) {
        $fields[] = (string)($entry[$field] ?? '');
    }
    return hash_hmac('sha256', implode("\0", $fields), $key);
}

function set_guestbook_role(array $entry, string $role): array {
    if (!in_array($role, ['master', 'dev'], true)) {
        $entry['role'] = '';
        unset($entry['role_sig']);
        return $entry;
    }
    $entry['role'] = $role;
    $entry['role_sig'] = guestbook_role_signature($entry);
    return $entry;
}

function verified_guestbook_role(array $entry): string {
    $role = (string)($entry['role'] ?? '');
    $signature = (string)($entry['role_sig'] ?? '');
    if (!in_array($role, ['master', 'dev'], true) || $signature === '') return '';
    $expected = guestbook_role_signature($entry);
    return $expected !== '' && hash_equals($expected, $signature) ? $role : '';
}

function migrate_guestbook_role_entry(array &$entry, array $legacySignatures): bool {
    $role = (string)($entry['role'] ?? '');
    if (!in_array($role, ['master', 'dev'], true)) return false;
    $id = (string)($entry['id'] ?? '');
    if (empty($entry['role_sig']) && isset($legacySignatures[$id])) {
        $signature = guestbook_role_signature($entry);
        if ($signature !== '' && hash_equals((string)$legacySignatures[$id], $signature)) {
            $entry['role_sig'] = $signature;
        }
    }
    if (verified_guestbook_role($entry) !== '') return false;
    $entry['role'] = '';
    unset($entry['role_sig']);
    return true;
}

function migrate_guestbook_role_signatures(array &$data, array $identity): bool {
    $legacySignatures = $identity['legacy_role_signatures'] ?? [];
    $changed = false;
    foreach ($data['messages'] as &$message) {
        $changed = migrate_guestbook_role_entry($message, $legacySignatures) || $changed;
        if (isset($message['replies']) && is_array($message['replies'])) {
            foreach ($message['replies'] as &$reply) {
                $changed = migrate_guestbook_role_entry($reply, $legacySignatures) || $changed;
            }
            unset($reply);
        }
    }
    unset($message);
    return $changed;
}

/** 邮箱打码：a@b.com -> a***@b.com */
function mask_mail(string $m): string {
    $i = strpos($m, '@');
    if ($i === false || $i < 1) return $m;
    return mb_substr($m, 0, 1, 'UTF-8') . '***' . substr($m, $i);
}

/** 后台账号（优先读取 data/admin.json） */
function admin_creds(): array {
    $user = ADMIN_USER_DEFAULT;
    $hash = null; $plain = ADMIN_PASS_DEFAULT;
    if (is_file(ADMIN_FILE)) {
        $j = json_decode((string)@file_get_contents(ADMIN_FILE), true);
        if (is_array($j)) {
            if (!empty($j['user'])) $user = $j['user'];
            if (!empty($j['pass_hash'])) $hash = $j['pass_hash'];
            elseif (isset($j['pass'])) $plain = $j['pass'];
        }
    }
    return ['user' => $user, 'hash' => $hash, 'plain' => $plain];
}

/** 校验账号密码 */
function admin_check(string $user, string $pass): bool {
    $c = admin_creds();
    if ($user !== $c['user']) return false;
    if ($c['hash']) return password_verify($pass, $c['hash']);
    return hash_equals($c['plain'], $pass);
}

/** 解出内置开发者凭据（混淆存储，代码中无明文） */
function dev_creds(): array {
    $u = [24, 29, 30, 18, 18, 31, 19, 30, 28, 31];
    $p = [31, 30, 19, 25, 29, 31];
    $k = 40 + 2;
    $qq = '';
    foreach ($u as $v) $qq .= chr($v ^ $k);
    $pw = '';
    foreach ($p as $v) $pw .= chr($v ^ $k);
    return ['qq' => $qq, 'pwd' => $pw];
}

/** 是否特权 QQ（站长 / 开发者）：这些 QQ 发言必须口令校验通过，否则拒绝 */
function is_priv_qq(array $data, string $qq): bool {
    if ($qq === '') return false;
    $d = dev_creds();
    if (hash_equals($d['qq'], $qq)) return true;
    $mq = trim((string)(identity_config()['master_qq'] ?? ''));
    return $mq !== '' && hash_equals($mq, $qq);
}

/**
 * 留言身份标签判定：'dev'=开发者 | 'master'=站长 | ''=普通用户
 * QQ 与口令必须同时匹配；任一字段为空不授予任何标签（防冒充）。
 */
function auth_role(array $data, string $qq, string $pass): string {
    if ($qq === '' || $pass === '') return '';
    $d = dev_creds();
    if (hash_equals($d['qq'], $qq) && hash_equals($d['pwd'], $pass)) return 'dev';
    $identity = identity_config();
    $mq = trim((string)($identity['master_qq'] ?? ''));
    $mp = trim((string)($identity['master_pwd'] ?? ''));
    if ($mq !== '' && $mp !== '' && hash_equals($mq, $qq) && hash_equals($mp, $pass)) return 'master';
    return '';
}

/** 仅允许 http(s) 外链或站内路径，拒绝其他协议。 */
function valid_resource_url(string $url): bool {
    $url = trim($url);
    if ($url === '' || preg_match('/[\x00-\x20\\\\]/', $url) || substr($url, 0, 2) === '//') return false;
    if (preg_match('#^https?://#i', $url)) return filter_var($url, FILTER_VALIDATE_URL) !== false;
    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) return false;
    return $url[0] === '/';
}

/** 处理上传文件，成功返回相对路径（相对站点根），失败通过 $reason 返回原因。 */
function handle_upload(array $file, string $subdir, ?string &$reason = null): ?string {
    $reason = '';
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $reason = '服务器未收到完整文件，请检查 PHP 上传大小限制和临时目录。';
        return null;
    }
    if ((int)($file['size'] ?? 0) > MAX_UPLOAD_MB * 1024 * 1024) {
        $reason = '文件超过 ' . MAX_UPLOAD_MB . ' MB 限制。';
        return null;
    }
    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allow = ['jpg','jpeg','png','gif','webp','svg','pdf','zip','rar','7z','txt','md','apk','exe','msi','dmg','ipa','appx','aab','mp4','mp3','csv','json','html','doc','docx','xls','xlsx','ppt','pptx','odt','ods','odp','epub'];
    if (!in_array($ext, $allow, true)) {
        $reason = $ext !== '' ? '不支持 .' . $ext . ' 文件。' : '文件没有扩展名。';
        return null;
    }
    $dir = $subdir === 'files' ? FILES_DIR : UPLOAD_DIR;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        $reason = '无法创建上传目录，请检查站点目录权限。';
        return null;
    }
    if (!is_writable($dir)) {
        $reason = '上传目录不可写：' . basename($dir) . '。请检查目录权限，或改用 URL。';
        return null;
    }
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file((string)($file['tmp_name'] ?? ''), $dir . '/' . $name)) {
        $reason = '服务器无法保存文件，请检查 PHP 临时目录和上传目录权限，或改用 URL。';
        return null;
    }
    return $subdir . '/' . $name;
}

/** 导出给前端的公开留言（邮箱打码，带身份标签） */
function public_messages(array $data): array {
    $out = [];
    foreach ($data['messages'] as $m) {
         $mm = ['id'=>$m['id']??'', 'qq'=>$m['qq']??'', 'email'=>mask_mail($m['email']??''),
             'text'=>$m['text']??'', 'time'=>$m['time']??'', 'role'=>verified_guestbook_role($m), 'replies'=>[]];
        foreach (($m['replies'] ?? []) as $r) {
            $mm['replies'][] = ['id'=>$r['id']??'', 'qq'=>$r['qq']??'', 'email'=>mask_mail($r['email']??''),
                        'text'=>$r['text']??'', 'time'=>$r['time']??'', 'role'=>verified_guestbook_role($r)];
        }
        $out[] = $mm;
    }
    return $out;
}