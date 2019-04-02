<?php

require('tools.php');
writeToLog($_REQUEST, 'REQUEST index.php');
if (isset($_REQUEST['code'])) 
{
    $result = array();
    $queryData = http_build_query($_REQUEST);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => 'https://bitrix24.ichazof.tk/auth.php',
        CURLOPT_POSTFIELDS => $queryData,
    ));

    $curlResult = curl_exec($curl);
    curl_close($curl);
    
}

if ($_REQUEST['event'] == 'ONIMCONNECTORMESSAGEADD') 
{
    $result = restCommand('imconnector.send.status.delivery', Array(
		'CONNECTOR' => $_REQUEST['data']['CONNECTOR'],
		'LINE' => $_REQUEST['data']['LINE'],
		'MESSAGES' => $_REQUEST['data']['MESSAGES']
	), $_REQUEST["auth"]);

	require('db.php');
	//получаем все данные для авторизаций
	$auth = sendReqestToDB("select * FROM `auth` WHERE `auth`.`id` = 17");

	$result = apiCommand('messages.send', Array(
		'user_id' => $_REQUEST['data']['MESSAGES'][0]['chat']['id'],
		'random_id' => time(),
		'message' => $_REQUEST['data']['MESSAGES'][0]['message']['text']
	), $auth['vk_token']);

	writeToLog($result, 'send to vk');
    
}




function apiCommand($method, array $params = Array(), $auth = "a96110ddb3027094591ead7a9c84a8cedbe3728ca3c379a6ab1e3fe7b60a0ecb76f4f825c92d051d4920d", $authRefresh = true)
{
	$queryUrl = 'https://api.vk.com/method/'.$method;
	$queryData = http_build_query(array_merge($params, array("access_token" => $auth), array('v' => '5.92')));
	writeToLog(Array('URL' => $queryUrl, 'PARAMS' => array_merge($params, array('v' => 5.52))), 'send data');
	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_HEADER => 0,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_SSL_VERIFYPEER => 1,
		CURLOPT_URL => $queryUrl,
		CURLOPT_POSTFIELDS => $queryData,
	));
	$result = curl_exec($curl);
    curl_close($curl);
	$result = json_decode($result, 1);
	return $result;
}


function restCommand($method, array $params = Array(), array $auth = Array(), $authRefresh = true)
{
	$queryUrl = $auth["client_endpoint"].$method;
	$queryData = http_build_query(array_merge($params, array("auth" => $auth["access_token"])));

	writeToLog(Array('URL' => $queryUrl, 'PARAMS' => array_merge($params, array("auth" => $auth["access_token"]))), 'send data');

	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_POST => 1,
		CURLOPT_HEADER => 0,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_SSL_VERIFYPEER => 1,
		CURLOPT_URL => $queryUrl,
		CURLOPT_POSTFIELDS => $queryData,
	));

	$result = curl_exec($curl);
	curl_close($curl);

	$result = json_decode($result, 1);



	return $result;
}