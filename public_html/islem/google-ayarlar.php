<?php
/**
 * ZiraatBox - Google API Ayarları
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../sistem/ayar.php';

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

$google_client = new Google_Client();

$client_id     = function_exists('env') ? env('GOOGLE_CLIENT_ID') : '';
$client_secret = function_exists('env') ? env('GOOGLE_CLIENT_SECRET') : '';
$redirect_uri  = function_exists('env') ? env('GOOGLE_REDIRECT_URL') : 'https://ziraatbox.com/islem/google-callback.php';

$google_client->setClientId($client_id);
$google_client->setClientSecret($client_secret);
$google_client->setRedirectUri($redirect_uri);

$google_client->addScope('email');
$google_client->addScope('profile');

// Giriş linki üretici
$login_url = $google_client->createAuthUrl();

// Eski veya farklı çağıran modüller için değişken uyumluluğu
$client = $google_client;