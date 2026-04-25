<?php
declare(strict_types=1);

function probe(string $url): array {
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$body = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
return ['url' => $url, 'status' => $code, 'error' => $err, 'ok' => ($err === '')];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
'public' => probe('https://bsky.social/xrpc/com.atproto.server.describeServer'),
'chat' => probe('https://api.bsky.chat/xrpc/chat.bsky.convo.listConvos?limit=1')
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);