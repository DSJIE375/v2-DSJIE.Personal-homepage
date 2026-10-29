<?php
/** 前台匿名互动统计 API */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/lib_analytics.php';

function analytics_reply(array $response, int $status = 200): void {
    http_response_code($status);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    analytics_reply(['ok' => 0], 405);
}

$raw = (string)file_get_contents('php://input');
if (strlen($raw) > 2048) analytics_reply(['ok' => 0], 413);
$input = json_decode($raw, true);
if (!is_array($input)) analytics_reply(['ok' => 0], 400);

$visitor = strtolower((string)($input['visitor'] ?? ''));
if (!analytics_valid_visitor($visitor)) analytics_reply(['ok' => 0], 400);
list(, , , $isBot) = ua_info((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
if ($isBot) analytics_reply(['ok' => 1, 'online' => analytics_online_count()]);

if (($input['action'] ?? '') === 'heartbeat') {
    analytics_reply(['ok' => 1, 'online' => analytics_online_update($visitor)]);
}

if (($input['action'] ?? '') !== 'event') analytics_reply(['ok' => 0], 400);
$type = (string)($input['type'] ?? '');
$allowed = ['page_view', 'section_view', 'button_click', 'link_click', 'download', 'guestbook_submit', 'guestbook_reply'];
if (!in_array($type, $allowed, true)) analytics_reply(['ok' => 0], 400);
$module = (string)($input['module'] ?? 'site');
$allowedModules = ['site', 'profile', 'navigation', 'about', 'works', 'essays', 'gallery', 'updates', 'friends', 'downloads', 'guestbook', 'copyright'];
if (!in_array($module, $allowedModules, true)) $module = 'site';
$label = clean_text((string)($input['label'] ?? ''), 80);
if ($label === '') $label = $type;
$saved = analytics_event_log([
    't' => date('Y-m-d H:i:s'),
    'visitor' => $visitor,
    'type' => $type,
    'module' => $module,
    'label' => $label,
]);
analytics_reply(['ok' => $saved ? 1 : 0], $saved ? 200 : 500);