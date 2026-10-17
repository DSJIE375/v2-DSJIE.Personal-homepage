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
    return $d;
}

/** 保存站点数据（加锁写入） */
function save_data(array $d): bool {
    $json = json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    return @file_put_contents(DATA_FILE, $json, LOCK_EX) !== false;
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

/** 处理上传文件，成功返回相对路径（相对站点根），失败返回 null */
function handle_upload(array $file, string $subdir): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > MAX_UPLOAD_MB * 1024 * 1024) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allow = ['jpg','jpeg','png','gif','webp','svg','pdf','zip','rar','7z','txt','md','apk','mp4','mp3','csv','json','html'];
    if (!in_array($ext, $allow, true)) return null;
    $dir = $subdir === 'files' ? FILES_DIR : UPLOAD_DIR;
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) return null;
    return $subdir . '/' . $name;
}

/** 导出给前端的公开留言（邮箱打码） */
function public_messages(array $data): array {
    $out = [];
    foreach ($data['messages'] as $m) {
        $mm = ['id'=>$m['id']??'', 'qq'=>$m['qq']??'', 'email'=>mask_mail($m['email']??''),
               'text'=>$m['text']??'', 'time'=>$m['time']??'', 'replies'=>[]];
        foreach (($m['replies'] ?? []) as $r) {
            $mm['replies'][] = ['id'=>$r['id']??'', 'qq'=>$r['qq']??'', 'email'=>mask_mail($r['email']??''),
                                'text'=>$r['text']??'', 'time'=>$r['time']??''];
        }
        $out[] = $mm;
    }
    return $out;
}
