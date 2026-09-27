<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
function fail(string $m,int $c=400): never { http_response_code($c); exit(json_encode(['ok'=>false,'message'=>$m])); }
if ($_SERVER['REQUEST_METHOD']!=='POST') fail('Method not allowed.',405);
if (!is_file(__DIR__.'/config.php')) fail('Contact form is not configured yet.',500);
$c=require __DIR__.'/config.php';
if (!hash_equals($_SESSION['csrf']??'',(string)($_POST['csrf']??''))) fail('Please refresh and try again.');
if (!empty($_POST['website']??'')) fail('Message rejected.');
$now=time(); if ($now-(int)($_SESSION['last_contact']??0)<60) fail('Please wait a minute before sending another message.',429);
$n=trim((string)($_POST['name']??'')); $e=trim((string)($_POST['email']??'')); $s=trim((string)($_POST['subject']??'')); $m=trim((string)($_POST['message']??''));
if ($n===''||mb_strlen($n)>100) fail('Please enter a valid name.');
if (!filter_var($e,FILTER_VALIDATE_EMAIL)||strlen($e)>254) fail('Please enter a valid email.');
if ($s===''||mb_strlen($s)>150) fail('Please enter a subject.');
if (mb_strlen($m)<10||mb_strlen($m)>5000) fail('Message must be between 10 and 5000 characters.');
$t=(string)($_POST['cf-turnstile-response']??''); if ($t==='') fail('Please complete the anti-spam check.');
$payload=http_build_query(['secret'=>$c['turnstile']['secret_key'],'response'=>$t]);
$ctx=stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/x-www-form-urlencoded\r\n",'content'=>$payload,'timeout'=>8]]);
$raw=@file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify',false,$ctx);
$v=$raw?json_decode($raw,true):null; if(!is_array($v)||empty($v['success'])) fail('Anti-spam verification failed.');
try {
 $d=$c['db']; $pdo=new PDO("mysql:host={$d['host']};dbname={$d['name']};charset={$d['charset']}",$d['user'],$d['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
 $q=$pdo->prepare('INSERT INTO contact_messages (name,email,subject,message) VALUES (?,?,?,?)'); $q->execute([$n,$e,$s,$m]);
} catch(Throwable $x){ error_log('Contact DB: '.$x->getMessage()); fail('Your message could not be saved. Please try again later.',500); }
$_SESSION['last_contact']=$now; $_SESSION['csrf']=bin2hex(random_bytes(32));
if(!empty($c['mail']['enabled'])){ $ss=str_replace(["\r","\n"],' ',$s); @mail($c['mail']['to'],'Website: '.$ss,"Name: $n\nEmail: $e\n\n$m","From: {$c['mail']['from']}\r\nReply-To: $e\r\nContent-Type: text/plain; charset=UTF-8"); }
echo json_encode(['ok'=>true,'message'=>'Thank you. Your message has been saved successfully.']);
