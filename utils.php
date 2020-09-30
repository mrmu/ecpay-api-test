<?php
//curl 取得遠端內容
function curl_get_contents($url, $args=array(), $command='', $isPost = true) 
{
    if (!$isPost){
        $i = 0;
        foreach ($args as $key => $value){
            if ($i<=0){$fg = '?';}
            else{$fg = '&';}
            $command .= $fg."$key=$value";
            $i++;
        }
    }
    //echo $command;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url.$command);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if ($isPost){
        curl_setopt($ch, CURLOPT_POST, true); // 啟用POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( $args, '', '&')); //天殺的&amp;問題
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $r = curl_exec($ch);
    if ($r === false) {
        //wp_die('Curl error: ' . curl_error($ch) );
        curl_close($ch);
        return false;
    }
    else {
        curl_close($ch);
        return $r;
    }
}

// 取得 api 要使用的欄位，並建立 ecpay mac
function build_api_params($api, $params, $HashKey, $HashIV) {
    // api 會使用到的欄位
    $ary_use_fds = array();
    switch ($api) {
        // 綁定信用卡
        case 'TradeWithBindingCardID':
            $ary_use_fds = array( 
                'MerchantID', 'MerchantMemberID', 
                'ServerReplyURL', 'ClientRedirectURL', 'ClientBackURL',
                'MerchantTradeNo', 'MerchantTradeDate', 'TotalAmount',
                'TradeDesc', 'stage'
            );
        break;
        // 查詢
        case 'QueryMemberBinding':
            $ary_use_fds = array( 
                'MerchantID', 'MerchantMemberID'
            );
        break;
        // 解綁
        case 'DeleteCardID':
            $ary_use_fds = array( 
                'MerchantID', 'MerchantMemberID', 'CardID'
            );
        break;
    }
    // 取得 api 會用到的欄位設定值
    $use_params = array();
    foreach ($ary_use_fds as $fd) {
        $use_params[$fd] = $params[$fd];
    }

    // 將 api 會用到的欄位值，拿來建立 ECPay Mac
    $mac = ECPayGenCheckMac($use_params, $HashKey, $HashIV, 1);
    $use_params['CheckMacValue'] = $mac;

    return $use_params;
}

function api_rst_to_html($api, $rst, $mode = 'json') {

    if ($mode === 'json') {
        $json = $rst['JSonData'];
        $json = json_decode($json, true);
        $rst = $json;
    }
    // switch($api) {
    //     case 'QueryMemberBinding':            
    //     break;
    // }

    $html = '<table class="mt-2 table">';
    // 標題列
    $col_names = array_keys($rst);
    $html .= '<tr>';
    foreach ($col_names as $col) {
        $html .= '<th class="'.$col.'">'.$col.'</th>';
    }
    $html .= '</tr>';

    // 資料列
    $col_vals = array_values($rst);
    $html .= '<tr>';
    foreach ($col_vals as $val) {
        $col = array_search ($val, $rst);
        $html .= '<td class="'.$col.'">';
        // 若欄位是 JSonData 就另建 table 輸出
        if ($col === 'JSonData') {
            $json = json_decode($val, true);
            $html .= '<table class="table json">';
            foreach ($json as $jk => $jv) {
                $html .= '<tr><th>'.$jk.'</th><td>'.$jv.'</td></tr>';
            }
            $html .= '</table>';
        }else{
            $html .= $val;
        }
        $html .= '</td>';
    }
    $html .= '</tr>';

    $html .= '</table>';

    return $html;
}

function fetch_api_rst($api, $params, $HashKey, $HashIV, $ServiceURL) {

    $use_params = build_api_params($api, $params, $HashKey, $HashIV);

    // 呼叫 ECpay API，取得回傳結果
    $api_url = $ServiceURL . $api;
    $rst_str = curl_get_contents($api_url, $use_params);

    // 綁定信用卡
    if ($api === 'TradeWithBindingCardID') {
        return $rst_str;
    }

    $ary_rst_str = explode('&', $rst_str);
    $ary_user_data = array();
    foreach ($ary_rst_str as $keyval) {
        $ary_keyval = explode('=', $keyval);
        $key = $ary_keyval[0];
        $val = $ary_keyval[1];
        $ary_user_data[$key] = $val;
    }
    return $ary_user_data;

}

function ECPayGenCheckMac(array $params, $hashKey, $hashIV, $encType = 1)
{
    // 0) 如果資料中有 null，必需轉成空字串
    $params = array_map('strval', $params);
    
    // 1) 如果資料中有 CheckMacValue 必需先移除
    unset($params['CheckMacValue']);

    // 2) 將鍵值由 A-Z 排序
    uksort($params, 'strcasecmp');

    // 3) 將陣列轉為 query 字串
    $paramsString = urldecode(http_build_query($params));

    // 4) 最前方加入 HashKey，最後方加入 HashIV
    $paramsString = "HashKey={$hashKey}&{$paramsString}&HashIV={$hashIV}";
    // echo $paramsString.'<br><br>';

    // 5) 做 URLEncode
    $paramsString = urlencode($paramsString);
    // echo $paramsString.'<br><br>';

    // 6) 轉為全小寫
    $paramsString = strtolower($paramsString);
    // echo $paramsString.'<br><br>';

    // 7) 轉換特定字元
    $paramsString = str_replace('%2d', '-', $paramsString);
    $paramsString = str_replace('%5f', '_', $paramsString);
    $paramsString = str_replace('%2e', '.', $paramsString);
    $paramsString = str_replace('%21', '!', $paramsString);
    $paramsString = str_replace('%2a', '*', $paramsString);
    $paramsString = str_replace('%28', '(', $paramsString);
    $paramsString = str_replace('%29', ')', $paramsString);

    // 8) 進行編碼
    $paramsString = $encType ? hash('sha256', $paramsString) : md5($paramsString);

    // 9) 轉為全大寫後回傳
    return strtoupper($paramsString);
}

function generateRandomString($length = 13) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}