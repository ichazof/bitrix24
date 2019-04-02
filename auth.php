<?
function redirect($url)
{
    //Header("HTTP 302 Found");
    Header("Location: ".$url);
    die();
}

define('APP_ID', 'local.5c61c59e0d00b1.26243302'); // take it from Bitrix24 after adding a new application
define('APP_SECRET_CODE', '3uWjRvLR34udcfTaqKMiuAMwg4dRNDVyKwtOxk9O7HwKoNn6Xo'); // take it from Bitrix24 after adding a new application
//define('APP_REG_URL', 'pegast.alyans.ru/index.php'); // the same URL you should set when adding a new application in Bitrix24
require('tools.php');
writeToLog($_REQUEST, 'REQUEST auth');

$domain = isset($_REQUEST['portal']) ? $_REQUEST['portal'] : ( isset($_REQUEST['domain']) ? $_REQUEST['domain'] : 'empty');

$step = 0;

if (isset($_REQUEST['portal'])) $step = 1;
if (isset($_REQUEST['code'])) $step = 2;

$btokenRefreshed = null;

$arScope = array('user');
switch ($step) {
    case 1:
        // we need to get the first authorization code from Bitrix24 where our application is _already_ installed
        requestCode($domain);
        break;

    case 2:
        //we've got the first authorization code and use it to get an access_token and a refresh_token (if you need it later)
        // echo "step 2 (getting an authorization code):<pre>";
        // print_r($_REQUEST);
        // echo "</pre><br/>";
        // echo "<P>";

        
        $arAccessParams = requestAccessToken($_REQUEST['code'], $_REQUEST['server_domain']);
        // saveParams($arAccessParams);
        // echo "step 3 (getting an access token):<pre>";
        // print_r($arAccessParams);
        // echo "</pre><br/>";
        writeToLog($arAccessParams, 'ok');
        require('db.php');
        $access_token = $arAccessParams['access_token'];
        $refresh_token = $arAccessParams['refresh_token'];
        $client_endpoint = $arAccessParams['client_endpoint'];
        //проверяем, есть ли такой портал, если есть обновляем данные
        $data = sendReqestToDB("sELECT * FROM `auth` WHERE `auth`.`client_endpoint` = '$client_endpoint'");
        if ($data) {
            writeToLog($data, "проверка на наличе портала");
            sendReqestToDB("uPDATE `auth` SET `access_token` = '$access_token', `refresh_token` = '$refresh_token' WHERE `auth`.`client_endpoint` = '$client_endpoint';");
        } else {

            //создаем новую запись и записываем в бд ключи пользователя
            sendReqestToDB("insert into `auth` (`access_token`, `refresh_token`, `client_endpoint`) values ('$access_token', '$refresh_token', '$client_endpoint')");
            writeToLog($arAccessParams, 'ok');

        }
        
        //записываем в бд ключи пользователя
        
        // $arCurrentB24User = executeREST($arAccessParams['client_endpoint'], 'user.current', array(),
        //     $arAccessParams['access_token']);

        break;
    default:
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
    <title>Quick start. Local server-side application without UI in Bitrix24</title>

</head>
<body>
<?if ($step == 0) {?>
    step 1 (redirecting to Bitrix24):<br/>
    <form action="" method="post">
        <input type="text" name="portal" placeholder="Bitrix24 URL">
        <input type="hidden" name="qwerty" value="test_test">
        <input type="submit" value="Authorize">
    </form>
    
    <?
}
elseif ($step == 2) {
    echo (string)$arCurrentB24User["result"]["NAME"] . "fghjkl;\n\nlkjhgfd " . $arCurrentB24User["result"]["LAST_NAME"];
}
?>
</body>
</html>
<?
function executeHTTPRequest ($queryUrl, array $params = array()) {
    $result = array();
    $queryData = http_build_query($params);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));

    $curlResult = curl_exec($curl);
    curl_close($curl);

    if ($curlResult != '') $result = json_decode($curlResult, true);

    return $result;
}

function requestCode ($domain) {
    $url = 'https://' . $domain . '/oauth/authorize/' .
        '?client_id=' . urlencode(APP_ID);
        
    redirect($url);

}

function requestAccessToken ($code, $server_domain) {
    $url = 'https://' . $server_domain . '/oauth/token/?' .
        'grant_type=authorization_code'.
        '&client_id='.urlencode(APP_ID).
        '&client_secret='.urlencode(APP_SECRET_CODE).
        '&code='.urlencode($code);
        
    return executeHTTPRequest($url);
}





