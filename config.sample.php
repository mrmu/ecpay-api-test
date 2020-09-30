<?php
define ('DEV', 0);
define ('PRO', 1);
$conf = array();
$conf[DEV]['ConfigMode'] = 'stage';
$conf[DEV]['ServiceURL'] = 'https://payment-stage.ecpay.com.tw/MerchantMember/';
$conf[DEV]['HashKey'] = 'pwFHCqoQZGmho4w6';
$conf[DEV]['HashIV'] = 'EkRm7iFT261dpevs'; 
$conf[DEV]['MerchantID'] = '3002607';

$conf[PRO]['ConfigMode'] = 'production';
$conf[PRO]['ServiceURL'] = 'https://payment.ecpay.com.tw/MerchantMember/';
$conf[PRO]['HashKey'] = '';
$conf[PRO]['HashIV'] = '';
$conf[PRO]['MerchantID'] = '';

// 使用正式(PRO)或測試(DEV)設定
$config = $conf[DEV];

$http = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$cur_url = $http.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);