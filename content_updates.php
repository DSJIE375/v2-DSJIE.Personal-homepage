<?php
/** Public page content refresh endpoint. */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/setup.php';

$version = is_file(DATA_FILE) ? (string)hash_file('sha256', DATA_FILE) : '';
$since = substr((string)($_GET['since'] ?? ''), 0, 64);
if ($since !== '' && $version !== '' && hash_equals($version, $since)) {
    echo json_encode(['ok' => true, 'changed' => false, 'version' => $version], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'changed' => true,
    'version' => $version,
    'siteVersion' => $site_version,
    'sections' => $sections,
    'radar' => $radar,
    'gbook' => $gbook,
    'siteName' => (string)($s['name'] ?? ''),
], $enc);
