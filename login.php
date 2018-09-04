<?php
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/util/Http.php");


function getSuiteTicket($suitkey) {
  return "temp_suite_ticket_only4_test";
}

$code = $_POST['authCode'];
$corpId = $_POST['corpId'];
// 时间戳
$timeStamp = time() * 1000;
// 正式应用应该由钉钉通过开发者的回调地址动态获取到
$suiteTicket = getSuiteTicket(SUITE_KEY);
// 构造/service/get_corp_token接口的消息体
$msg = $timeStamp."\n".$suiteTicket;
// 把timestamp+"\n"+suiteTicket当做签名字符串，suiteSecret做为签名秘钥，使用HmacSHA256算法计算签名，然后进行Base64 encode获取最后结果。然后把签名参数再进行urlconde，加到请求url后面
$sha = urlencode(base64_encode(hash_hmac('sha256', $msg, SUITE_SECRET, true)));
// 调用接口获取access_token
$res = Http::post("/service/get_corp_token",
array(
    "accessKey" => SUITE_KEY,
    "timestamp" => $timeStamp,
    "suiteTicket" => $suiteTicket,
    "signature" => $sha,
),
json_encode(array(
    "auth_corpid" => $corpId,
)));

$access_token = $res->access_token;

// 通过authcode和accesstoken获取哦用户信息
$res = Http::get("/user/getuserinfo",
array(
    "access_token" => $access_token,
    "code" => $code,
));

echo json_encode(array(
    "result" => array('userId' => $res->userid),
));
