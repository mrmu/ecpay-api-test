<?php
include_once('config.php');
include_once('utils.php');

$api = (isset($_POST['api'])) ? $_POST['api'] : '';
$MerchantMemberID = (isset($_POST['MerchantMemberID'])) ? $_POST['MerchantMemberID'] : '';
$CardID = (isset($_POST['CardID'])) ? $_POST['CardID'] : '';

$MerchantID = $config['MerchantID'];
$ServiceURL = $config['ServiceURL'].$api;
$HashKey = $config['HashKey'];
$HashIV = $config['HashIV'];

$ServerReplyURL = $cur_url.'/_server_reply.php';
$ClientRedirectURL = $cur_url.'/_client_redirect.php';
$ClientBackURL = $cur_url.'/_client_back.php';

$MerchantTradeNo = round(microtime(true) * 1000);

$MerchantTradeDate = date('Y/m/d h:i:s', time());// '2018/08/30 00:00:00';
$TotalAmount = '5';
$TradeDesc = 'Desc';

$ary_use_fds = array();
if (!empty($api)) {
    switch ($api) {
        case 'TradeWithBindingCardID':
            $ary_use_fds = array( 
                'MerchantID', 'MerchantMemberID', 
                'ServerReplyURL', 'ClientRedirectURL', 'ClientBackURL',
                'MerchantTradeNo', 'MerchantTradeDate', 'TotalAmount',
                'TradeDesc', 'stage'
            );
        break;
        case 'QueryMemberBinding':
            $ary_use_fds = array( 
                'MerchantID', 'MerchantMemberID'
            );
        break;
        case 'DeleteCardID':
            $ary_use_fds = array( 
                'MerchantID', 'MerchantMemberID', 'CardID'
            );
        break;
    }
}

$params['MerchantID'] = $MerchantID;
$params['MerchantMemberID'] = $MerchantMemberID;
$params['ServerReplyURL'] = $ServerReplyURL;
$params['ClientRedirectURL'] = $ClientRedirectURL;
$params['ClientBackURL'] = $ClientBackURL;
$params['MerchantTradeNo'] = $MerchantTradeNo;
$params['MerchantTradeDate'] = $MerchantTradeDate;
$params['TotalAmount'] = $TotalAmount;
$params['TradeDesc'] = $TradeDesc;
$params['CardID'] = $CardID;
$params['stage'] = '0';

$use_params = array();
foreach ($ary_use_fds as $fd) {
    $use_params[$fd] = $params[$fd];
}

$mac = ECPayGenCheckMac($use_params, $HashKey, $HashIV, 1);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1, width=device-width"/>
<title>ECPay API Test</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<style>
</style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1>ECPay API test</h1>
                <form method="post" class="mb-5" action="">
                    <label>API：</label>
                    <select name="api" class="form-control">
                        <option value="TradeWithBindingCardID">綁定信用卡</option>
                        <option value="QueryMemberBinding">查詢會員</option>
                        <option value="DeleteCardID">解除綁定信用卡</option>
                    </select>
                    <label>Merchant Member ID：(如：<?php echo $MerchantID;?>A0001) </label>
                    <input type="text" class="form-control" name="MerchantMemberID" value="<?php echo $MerchantMemberID;?>" placeholder="通常是 Merchant ID + 網站會員ID">
                    <label>Merchant Trade No：(timestamp, 每次呼叫都要不同) </label>
                    <input type="text" class="form-control" name="MerchantTradeNo" value="<?php echo $MerchantTradeNo;?>">
                    <button type="submit" class="btn btn-secondary">取得參數</button>
                </form>

                <?php if(!empty($api) && !empty($MerchantMemberID)): ?>

                <form id="__ecpayForm" method="post" target="_self" action="<?php echo $ServiceURL;?>">
                <?php
                foreach ($use_params as $fd => $val) {
                    ?>
                    <input type="hidden" class="form-control" name="<?php echo $fd?>" value="<?php echo $val;?>">
                    <?php
                }
                ?>
                <input type="hidden" name="CheckMacValue" value="<?php echo $mac;?>">
                <input type="submit" class="btn btn-primary" id="__paymentButton" value="Send To Ecpay">
                </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>