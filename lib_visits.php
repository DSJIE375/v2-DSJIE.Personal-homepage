<?php
/** 访问日志库：UA 解析 / 记录前台访问 / 读取日志（由前台 part1 与后台 _a4 加载） */
require_once __DIR__ . '/config.php';

/** UA 解析 → [设备, 浏览器, 系统, 是否爬虫] */
function ua_info(string $ua): array {
    $isbot = (bool) preg_match('/bot\/|spider|crawler|crawl|slurp|headless|python-requests|curl\/|wget\/|okhttp|axios|feedfetcher|monitor|scanner|facebookexternalhit|Twitterbot|TelegramBot|WhatsApp|preview/i', $ua);
    $br = '其他';
    if (stripos($ua, 'MicroMessenger') !== false)      $br = '微信内置';
    elseif (preg_match('/MQQBrowser|QQBrowser|QQ\//i', $ua)) $br = 'QQ浏览器';
    elseif (preg_match('/Baiduboxapp|baidubox/i', $ua)) $br = '百度App';
    elseif (preg_match('/Edg\//i', $ua) || stripos($ua, 'Edge/') !== false) $br = 'Edge';
    elseif (preg_match('/OPR\/|Opera/i', $ua))         $br = 'Opera';
    elseif (preg_match('/Firefox\//i', $ua))           $br = 'Firefox';
    elseif (preg_match('/SamsungBrowser/i', $ua))      $br = '三星浏览器';
    elseif (preg_match('/UCBrowser|UBrowser/i', $ua))  $br = 'UC浏览器';
    elseif (preg_match('/Chrome\/|CriOS\//i', $ua))    $br = 'Chrome';
    elseif (preg_match('/Safari\//i', $ua))            $br = 'Safari';
    elseif (preg_match('/MSIE |Trident\//i', $ua))     $br = 'IE';

    $os = '其他';
    if (preg_match('/HarmonyOS|Harmony/i', $ua))           $os = '鸿蒙';
    elseif (preg_match('/Windows Phone/i', $ua))           $os = 'Windows Phone';
    elseif (stripos($ua, 'Windows') !== false)             $os = 'Windows';
    elseif (preg_match('/iPhone|iPad|iPod|CPU OS/i', $ua)) $os = 'iOS';
    elseif (preg_match('/Mac OS X|Macintosh/i', $ua))      $os = 'macOS';
    elseif (preg_match('/Android/i', $ua))                 $os = 'Android';
    elseif (preg_match('/Linux|X11/i', $ua))               $os = 'Linux';

    $dev = '其他';
    if ($isbot)                                                    $dev = '爬虫';
    elseif (preg_match('/iPad|Tablet|PlayBook|SM-P|MiPad/i', $ua)) $dev = '平板';
    elseif (preg_match('/iPhone|iPod/i', $ua))                     $dev = 'iPhone';
    elseif (preg_match('/Android|HarmonyOS/i', $ua))               $dev = '安卓手机';
    elseif (preg_match('/Windows|Macintosh|Linux|X11/i', $ua))     $dev = '电脑';
    return [$dev, $br, $os, $isbot];
}

/** 前台每次页面加载调用；任何异常静默忽略，绝不影响页面输出 */
function visit_log(): void {
    try {
        $ua  = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        $ip  = (string)($_SERVER['REMOTE_ADDR'] ?? '-');
        $xff = trim((string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($xff !== '') $ip = trim(explode(',', $xff)[0]);
        list($dev, $br, $os, $isbot) = ua_info($ua);
        $row = [
            't'   => date('Y-m-d H:i:s'),
            'ip'  => mb_substr($ip, 0, 45, 'UTF-8'),
            'dev' => $dev, 'br' => $br, 'os' => $os, 'bot' => $isbot ? 1 : 0,
            'p'   => mb_substr((string)($_SERVER['REQUEST_URI'] ?? '/'), 0, 200),
            'r'   => mb_substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 200),
            'ua'  => mb_substr($ua, 0, 300, 'UTF-8'),
        ];
        $dir  = dirname(DATA_FILE);
        $file = $dir . '/visits.php';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        visit_counter_bump($isbot); // 累计浏览次数：在写入本次日志行之前计数，避免首次回填时把本次重复算入
        if (is_file($file) && @filesize($file) > 800000) @rename($file, $dir . '/visits_1.php');
        $json = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // 每行前缀 <?php exit;// ：被直接请求也只会执行 exit，不会泄露内容
        @file_put_contents($file, "<?php exit;//" . $json . "\n", FILE_APPEND | LOCK_EX);
    } catch (\Throwable $e) {}
}

/** 读取全部日志（新 → 旧；归档 visits_1.php 一并读入） */
function visit_read(): array {
    $out = [];
    $dir = dirname(DATA_FILE);
    foreach ([$dir . '/visits_1.php', $dir . '/visits.php'] as $file) {
        if (!is_file($file)) continue;
        $raw = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($raw)) continue;
        foreach (array_reverse($raw) as $ln) {
            $ln = preg_replace('/^<\?php exit;\/\//', '', (string)$ln);
            $j = json_decode($ln, true);
            if (is_array($j) && isset($j['t'])) $out[] = $j;
        }
    }
    return $out;
}

/** 累计浏览次数 +1（爬虫不计），文件锁保证并发不丢数；返回计数后的总次数 */
function visit_counter_bump(bool $isBot): int {
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $path = $dir . '/counter.php';
    $handle = @fopen($path, 'c+');
    if (!$handle || !@flock($handle, LOCK_EX)) {
        if ($handle) fclose($handle);
        return visit_counter_total();
    }
    rewind($handle);
    $raw = stream_get_contents($handle);
    $total = (int)trim(preg_replace('/^<\?php exit;\/\//', '', (string)$raw));
    if ($total === 0 && !isset($raw[0]) && !visit_counter_seeded()) {
        // 首次部署：以现有访问日志行数为基数回填历史访问量（仅执行一次）
        try { $total = count(visit_read()); } catch (\Throwable $e) { $total = 0; }
        visit_counter_seeded(true);
    }
    if (!$isBot) $total++;
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, '<?php exit;//' . $total);
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return $total;
}

/** 读取累计浏览次数（O(1)：只读一个小整数文件，供首页渲染） */
function visit_counter_total(): int {
    $path = dirname(DATA_FILE) . '/counter.php';
    if (!is_file($path)) return 0;
    $raw = @file_get_contents($path);
    $raw = preg_replace('/^<\?php exit;\/\//', '', (string)$raw);
    $n = (int)trim($raw);
    return $n > 0 ? $n : 0;
}

/** 回填标记（counter_seeded.php 存在则历史回填已完成） */
function visit_counter_seeded(bool $set = false): bool {
    $path = dirname(DATA_FILE) . '/counter_seeded.php';
    if ($set) { @file_put_contents($path, '<?php exit;//1'); return true; }
    return is_file($path);
}
