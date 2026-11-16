<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('donaciones_u3_session');
    session_set_cookie_params(['httponly'=>true, 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite'=>'Lax', 'path'=>'/']);
    session_start();
}
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\'; style-src \'self\'; img-src \'self\' data:; connect-src \'self\' https://api.frankfurter.dev; object-src \'none\'; base-uri \'self\'; form-action \'self\'');
$campaigns = [
 ['id'=>1,'title'=>'Mochilas para aprender','category'=>'Educación','goal'=>15000,'desc'=>'Material y mochilas para estudiantes que inician un nuevo ciclo escolar.','emoji'=>'🎒'],
 ['id'=>2,'title'=>'Alimentos para familias','category'=>'Comunidad','goal'=>12000,'desc'=>'Apoyos alimentarios para familias en situación de necesidad.','emoji'=>'🥫'],
 ['id'=>3,'title'=>'Huerto comunitario','category'=>'Medio ambiente','goal'=>18000,'desc'=>'Herramientas y semillas para un huerto educativo sustentable.','emoji'=>'🌱']
];
function store_path(): string { return __DIR__.'/storage/pledges.json'; }
function pledges(): array {
 $path=store_path(); if (!is_file($path)) return [];
 $raw=@file_get_contents($path); $v=json_decode($raw ?: '[]',true); return is_array($v)?$v:[];
}
function save_pledge(array $entry): bool {
 $p=store_path(); $f=@fopen($p,'c+'); if (!$f) return false;
 if (!flock($f, LOCK_EX)) { fclose($f); return false; }
 rewind($f); $current=stream_get_contents($f); $list=json_decode($current ?: '[]',true); if(!is_array($list))$list=[];
 $list[]=$entry; rewind($f); ftruncate($f,0);
 $json=json_encode($list,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
 $ok=fwrite($f,$json)!==false; fflush($f); flock($f,LOCK_UN); fclose($f); return $ok;
}
function e(string $s): string {return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verify_csrf(): bool { return isset($_POST['csrf']) && is_string($_POST['csrf']) && hash_equals(csrf(),$_POST['csrf']); }
function json_out($data, int $status=200): never {http_response_code($status); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store'); echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit;}
function find_campaign(int $id): ?array {global $campaigns;foreach($campaigns as $c){if($c['id']===$id)return $c;} return null;}
function total_by_campaign(int $id): int { $sum=0; foreach(pledges() as $p)if(($p['campaign_id']??null)===$id)$sum+=(int)$p['amount'];return $sum; }
function require_admin(): void {if(empty($_SESSION['admin'])) {header('Location: /login');exit;}}
