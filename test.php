<?php

define('TOKEN', '91f53a7203ecea1ee18560656a5c9db1963062fdeb4e6642d513c751d48ace9be94c5aaae407aa30d4ab6');
require('tools.php');
//require('db.php');
//$data = sendReqestToDB("select * FROM `auth` WHERE `auth`.`id` = 9");


$result = apiCommand('messages.getLongPollServer', Array());
writeToLog($result, 'get long poll server');
	
$answer = longPolling($result['response']['server'], Array(
		'act' => 'a_check',
		'key' => $result['response']['key'],
		'ts' => $result['response']['ts'],
		'wait' => '25',
		'mode' => '2',

	));  
writeToLog($answer, 'long pulling answer');
$callBackData = array_merge($result, $answer);
echo json_encode($callBackData);





// $result = restCommand('imconnector.send.messages', Array(
// 		"CONNECTOR" => 'my_open_lines_ilya_chazof',
// 		"LINE" => "2",
// 		"MESSAGES" => Array(
// 			Array(
// 			   //Массив описания пользователя
// 			   'user' => Array(
// 				  'id' => '123456',//ID пользователя во внешней системе *
// 				  'last_name' => 'test',//Фамилия
// 				  'name' => 'testov'//Имя
				  
// 			   ),
// 			   //Массив описания сообщения
// 			   'message' => Array(
// 				  'id' => '1246', //ID сообщения во внешней системе.*
// 				  'date' => '1549378080', //Время сообщения в формате timestamp *
// 				  'text' => "kkcnjkvdfkvjdkfj"
// 			   ),

// 				//Массив описания чата
// 				'chat' => array(
// 					'id' => '223',//ID чата во внешней системе *
// 				)
// 			   )
// 		   )
// 	),  $data);

// 	writeToLog($result, 'send message to open lines');	








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




function apiCommand($method, array $params = Array(), $auth = TOKEN, $authRefresh = true)
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



function longPolling($server, array $params = Array())
{
	$queryUrl = $server;
	$queryData = http_build_query(array_merge($params, array('version' => '3')));
    $url = "https://".$queryUrl.'?'.$queryData;

	writeToLog(Array('URL' => $url), 'send data');

	$curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);

    $result = curl_exec($curl);
    curl_close($curl);

	$result = json_decode($result, 1);
	return $result;
}