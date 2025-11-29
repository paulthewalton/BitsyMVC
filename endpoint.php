<?php
// require_once __DIR__ . '/../credentials.php';
ob_start();
header('Content-Type: application/json');
header('X-XSS-Protection: 1; mode=block');
header('X-content-type-options: nosniff');
header('Access-Control-Allow-Origin: *');
$curl = curl_init();
curl_setopt($curl, CURLOPT_POST, 1);
// curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
// curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
curl_setopt($curl, CURLOPT_URL, 'http://localhost/app/');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Accept: application/json",
]);
echo curl_exec($curl);
curl_close($curl);
ob_get_flush();
