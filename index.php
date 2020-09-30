<?php
include_once('config.php');
include_once('utils.php');

// 取得從 user 填寫的 form 欄位資料
$api = (isset($_POST['api'])) ? $_POST['api'] : '';
$MerchantMemberID = (isset($_POST['MerchantMemberID'])) ? $_POST['MerchantMemberID'] : '';
$CardID = (isset($_POST['CardID'])) ? $_POST['CardID'] : '';

// 取得 config 設定值
$MerchantID = $config['MerchantID'];
$ServiceURL = $config['ServiceURL'].$api;
$HashKey = $config['HashKey'];
$HashIV = $config['HashIV'];

// 進行綁定信用卡時要跳轉回來的頁面
$ServerReplyURL = $cur_url.'/_server_reply.php';
$ClientRedirectURL = $cur_url.'/_client_redirect.php';
$ClientBackURL = $cur_url.'/_client_back.php';

// 廠商交易編號，每次呼叫的值都必須不同 (timestamp)
$MerchantTradeNo = round(microtime(true) * 1000);

// 廠商交易日期 (綁定的時間)
$MerchantTradeDate = date('Y/m/d h:i:s', time());// '2018/08/30 00:00:00';

// 交易金額 (綁定金額)
$TotalAmount = '5';
$TradeDesc = 'Desc';

// 收集上述資料，作為 API 呼叫所需的參數
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

// 呼叫 API 並取得結果
// TBD: 綁定信用卡須跳轉到 ecpay
$api_rst = fetch_api_rst($api, $params, $HashKey, $HashIV, $config['ServiceURL']);

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
table .CheckMacValue{display: none;}
</style>
</head>
<body>
    <div class="container">
        <div class="row mt-3">
            <div class="col-12">
                <h1>ECPay 不定期不定額 API demo</h1>
                <div class="mb-3">
                    設定檔：
                    <span class="badge badge-primary"><?php echo $config['ConfigMode'];?></span>
                </div>
                <form method="post" class="mb-5" action="">
                    <label>API：</label>
                    <select name="api" class="form-control">
                        <!--<option value="TradeWithBindingCardID">綁定信用卡</option>-->
                        <option value="QueryMemberBinding">查詢會員</option>
                        <option value="DeleteCardID">解除綁定信用卡</option>
                    </select>
                    <label>Merchant Member ID：(如：<?php echo $MerchantID;?>A0001) </label>
                    <input type="text" class="form-control" name="MerchantMemberID" value="<?php echo $MerchantMemberID;?>" placeholder="通常是 Merchant ID + 網站會員ID">

                    <div class="d-none">
                        <label>Merchant Trade No：(timestamp, 每次呼叫都要不同) </label>
                        <input type="text" class="form-control" name="MerchantTradeNo" value="<?php echo $MerchantTradeNo;?>">
                    </div>

                    <div class="cardid_wrap d-none">
                        <label>Card ID：(解除綁定時必填) </label>
                        <input type="text" class="form-control" name="CardID" value="<?php echo $CardID;?>">
                    </div>

                    <button type="submit" class="mt-3 btn btn-secondary">呼叫 API</button>
                </form>

            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <section id="api_result">
                <?php 
                // 輸出呼叫 API 的結果
                if (!empty($api) && !empty($api_rst)) {
                    if ($api !== 'TradeWithBindingCardID') {
                        echo api_rst_to_html($api, $api_rst, 'all');
                    }else{
                        // TBD: 綁定信用卡須跳轉到 ecpay
                        print_r($api_rst);
                    }
                }
                ?>
                </section>
            </div>
        </div>

    </div>
</body>
<script>
$(function(){
    // 若有選定的 API 就設為 select 的值
    const api_sel = '<?php echo $api;?>';
    if (api_sel) {
        $('select[name=api]').val(api_sel);
    }

    // API select 變更選項
    $(document).on('change', 'select[name=api]', function(e){
        $('.cardid_wrap').addClass('d-none');
        console.log($(this).val());
        switch($(this).val()) {
            // 選擇「解綁信用卡」就出現 CardID 欄位
            case 'DeleteCardID':
                $('.cardid_wrap').removeClass('d-none');
                break;
            default:
                break;
        }
    });
})
</script>
</html>