<?php
/** 匿名互动统计与在线访客状态 */
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/lib_visits.php';

function analytics_valid_visitor(string $visitor): bool {
    return (bool)preg_match('/^[a-f0-9]{32}$/', $visitor);
}

function analytics_event_log(array $event): bool {
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return false;
    $path = $dir . '/events_' . date('Ym') . '.php';
    $json = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $json !== false && @file_put_contents($path, '<?php exit;//' . $json . "\n", FILE_APPEND | LOCK_EX) !== false;
}

function analytics_read_events(int $since): array {
    $dir = dirname(DATA_FILE);
    $months = [date('Ym'), date('Ym', strtotime('-1 month'))];
    $events = [];
    foreach ($months as $month) {
        $path = $dir . '/events_' . $month . '.php';
        if (!is_file($path)) continue;
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) continue;
        foreach ($lines as $line) {
            $json = preg_replace('/^<\?php exit;\/\//', '', (string)$line);
            $event = json_decode($json, true);
            if (!is_array($event) || empty($event['t']) || empty($event['visitor'])) continue;
            $time = strtotime((string)$event['t']);
            if ($time !== false && $time >= $since) $events[] = $event;
        }
    }
    return $events;
}

function analytics_online_update(string $visitor): int {
    $path = dirname(DATA_FILE) . '/online.php';
    $handle = @fopen($path, 'c+');
    if (!$handle || !@flock($handle, LOCK_EX)) {
        if ($handle) fclose($handle);
        return 0;
    }
    rewind($handle);
    $raw = stream_get_contents($handle);
    $raw = preg_replace('/^<\?php exit;\/\//', '', (string)$raw);
    $online = json_decode($raw, true);
    if (!is_array($online)) $online = [];
    $now = time();
    foreach ($online as $id => $lastSeen) {
        if (!is_numeric($lastSeen) || (int)$lastSeen < $now - 65) unset($online[$id]);
    }
    $online[$visitor] = $now;
    $json = json_encode($online, JSON_UNESCAPED_SLASHES);
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, '<?php exit;//' . ($json !== false ? $json : '{}'));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return count($online);
}

function analytics_online_count(): int {
    $path = dirname(DATA_FILE) . '/online.php';
    if (!is_file($path)) return 0;
    $raw = @file_get_contents($path);
    $raw = preg_replace('/^<\?php exit;\/\//', '', (string)$raw);
    $online = json_decode($raw, true);
    if (!is_array($online)) return 0;
    $cutoff = time() - 65;
    $count = 0;
    foreach ($online as $lastSeen) {
        if (is_numeric($lastSeen) && (int)$lastSeen >= $cutoff) $count++;
    }
    return $count;
}