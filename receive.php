<?php
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/util/Log.php");
require_once(__DIR__ . "/crypto/DingtalkCrypt.php");

$signature = $_GET["signature"];
$timeStamp = $_GET["timestamp"];
$nonce = $_GET["nonce"];
$postdata = file_get_contents("php://input");
$postList = json_decode($postdata,true);
$encrypt = $postList['encrypt'];
$crypt = new DingtalkCrypt(TOKEN, ENCODING_AES_KEY, SUITE_KEY);

$msg = "";
$errCode = $crypt->DecryptMsg($signature, $timeStamp, $nonce, $encrypt, $msg);

if ($errCode != 0) {
    Log::e(json_encode($_GET) . "  ERR:" . $errCode);
} else {
    /**
     * 套件创建成功后的回调推送
     */
    Log::i("DECRYPT MSG SUCCESS " . json_encode($_GET) . "  " . $msg);
    $eventMsg = json_decode($msg);
    $eventType = $eventMsg->EventType;
    
    if ('check_create_suite_url' === $eventType) {
    	Log::i("验证新创建的回调URL有效性: " . json_encode($_GET) . "  " . $msg);
    } else if ('check_update_suite_url' == $eventType) {
	Log::i("验证更新回调URL有效性: " . json_encode($_GET) . "  " . $msg);
    } else if ("suite_ticket" === $eventType) {
        //suite_ticket用于用签名形式生成accessToken(访问钉钉服务端的凭证)，需要保存到应用的db。
       	//钉钉会定期向本callback url推送suite_ticket新值用以提升安全性。
        //应用在获取到新的时值时，保存db成功后，返回给钉钉success加密串（如本demo的return）
	Log::i("应用suite_ticket数据推送:" . json_encode($_GET) . "  " . $msg);
    } else if ("tmp_auth_code" === $eventType) {
	//本事件应用应该异步进行授权开通企业的初始化，目的是尽最大努力快速返回给钉钉服务端。用以提升企业管理员开通应用体验
        //即使本接口没有收到数据或者收到事件后处理初始化失败都可以后续再用户试用应用时从前端获取到corpId并拉取授权企业信息，
        // 进而初始化开通及企业。
	Log::i("企业授权开通应用事件: " . json_encode($_GET) . "  " . $msg);
    } else {
    	// 其他类型事件处理
    }
    
    $res = "success";
    $encryptMsg = "";
    $errCode = $crypt->EncryptMsg($res, $timeStamp, $nonce, $encryptMsg);
    if ($errCode == 0) 
    {
        echo $encryptMsg;
        Log::i("RESPONSE: " . $encryptMsg);
    } 
    else 
    {
        Log::e("RESPONSE ERR: " . $errCode);
    }
}
