<?php

//加載所需的庫
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

//從文件名稱獲取模塊名稱
$gatewayModuleName = basename(__FILE__, '.php');

//獲取網關配置參數
$gatewayParams = getGatewayVariables($gatewayModuleName);

//檢查模塊是否啟用
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

//設定的模塊參數
$merID = $gatewayParams['MerID'];
$hashKey = $gatewayParams['hashKey'];
$hashIV = $gatewayParams['hashIV'];

//獲取回傳的參數

$resultmerID = $_POST["MerID"];
$transData = parse_str(urldecode(create_aes_decrypt($_POST["EncryptInfo"], $hashKey, $hashIV)),$transData);
$status = $transData["Status"];

//解密回傳的AES數據
function create_aes_decrypt($encryptStr, $hashKey, $hashIV) {
    list($encryptData, $tag) = explode(":::", hex2bin($encryptStr), 2);
    return openssl_decrypt($encryptData, "aes-256-gcm", trim($hashKey), 0, trim($hashIV), base64_decode($tag));
}

//檢查帳單編號
$invoiceId = checkCbInvoiceID($transData['MerTradeNo'], $gatewayParams['name']);

//檢查是否已有相同的交易編號
checkCbTransID($transData['TradeNo']);

//交易日誌
logTransaction($gatewayParams['name'], $_POST, $status);

//如果數據無誤，添加付款紀錄
if ($status == 'SUCCESS' && $resultmerID == $merID) {
    addInvoicePayment(
        $invoiceId,
        $transData['TradeNo'],
        $transData['TradeAmt'],
        $paymentFee,
        $gatewayModuleName
    );
}
