<?php
//данный скрипт должен постоянно чекать вк и при получении сообщений слать их в битрикс
require('db.php');
require('tools.php');
writeToLog($_REQUEST, 'vk.php start');


	//получаем все данные для авторизаций
	$auth = sendReqestToDB("select * FROM `auth` WHERE `auth`.`id` = 17");


	//define('TOKEN', '20d852642e25766f156434530a63ee85bca272f3c70814f6fdd72e465dc6525db6e0c992887c7d17475cd');


		
	$answer = longPolling($auth['vk_server'], Array(
			'act' => 'a_check',
			'key' => $auth['vk_key'],
			'ts' => $auth['vk_ts'],
			'wait' => '25',
			'mode' => '2',

		));
	
	writeToLog($answer, 'long pulling answer');

	if (isset($answer["failed"])) {

			//получаем данные о сервере вк для лонгпулинга
		$result = apiCommand('messages.getLongPollServer', Array(), $auth['vk_token']);
		writeToLog($result, 'get long poll server');
		// обновим инфу в базе той, которую получили
		$ts_longPulling = $result['response']['ts'];
		$key_longPulling = $result['response']['key'];
		$server_longPulling = $result['response']['server'];
		sendReqestToDB("uPDATE `auth` SET `vk_ts` = '$ts_longPulling', `vk_server` = '$server_longPulling', `vk_key` = '$key_longPulling' WHERE `auth`.`id` = 17;");


	}	

	//$callBackData = array_merge($result, $answer);
	//это хз для чего, наверное чтобы обновить ts  для слядующей итерации
	if(isset($answer['ts'])) 
	{
		
		$ts = $answer['ts'];
		sendReqestToDB("uPDATE `auth` SET `vk_ts` = '$ts' WHERE `auth`.`id` = 17;");
	}


	//что то получилили из вк
	$updetes = $answer['updates'];
	//var_dump($updetes);
	foreach ($updetes as $el) {
		if ($el[0] == '4' && $el[2] == 1) {

			$messege = $el[5];
			$user_id = $el[3];
			//echo "https://api.vk.com/method/users.get?user_ids=".$user_id."&access_token=".$auth['vk_token']."&v=5.92";

			$user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids=".$user_id."&access_token=".$auth['vk_token']."&v=5.92")); 
			$user_name = $user_info->response[0]->first_name;
			var_dump($user_info);
			$user_last_name = $user_info->response[0]->last_name;
			//и извлекаем из ответа его имя 


			$result = restCommand('imconnector.send.messages', Array(
				"CONNECTOR" => 'my_open_lines_ilya_chazof',
				"LINE" => "2",
				"MESSAGES" => Array(
					Array(
					//Массив описания пользователя
					'user' => Array(
						'id' => $user_id,//ID пользователя во внешней системе *
						'last_name' => $user_last_name,//Фамилия
						'name' => $user_name//Имя
						
					),
					//Массив описания сообщения
					'message' => Array(
						'id' => el[1], //ID сообщения во внешней системе.*
						'date' => $el[4], //Время сообщения в формате timestamp *
						'text' => $messege
					),
		
						//Массив описания чата
						'chat' => array(
							'id' => $user_id,//ID чата во внешней системе *
						)
					)
				)
			),  $auth);
		
		writeToLog($result, 'send message to open lines');	
				
		
		
		
		
		
		}
	}









//отправляем сообщение в битрикс



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

	if ($authRefresh && isset($result['error']) && in_array($result['error'], array('expired_token', 'invalid_token')))
	{
		$auth = restAuth($auth);
		if ($auth)
		{
			$result = restCommand($method, $params, $auth, false);
		}
	}
	return $result;
}


function apiCommand($method, array $params = Array(), $access_token = TOKEN, $authRefresh = true)
{
	$queryUrl = 'https://api.vk.com/method/'.$method;
	$queryData = http_build_query(array_merge($params, array("access_token" => $access_token), array('v' => '5.92')));
	writeToLog(Array('URL' => $queryUrl, 'PARAMS' => array_merge($params, array('v' => 5.92))), 'send data');
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


function restAuth($auth)
{
	if(!isset($auth['refresh_token']))
		return false;

	$queryUrl = 'https://oauth.bitrix.info/oauth/token/';
	$queryData = http_build_query($queryParams = array(
		'grant_type' => 'refresh_token',
		'client_id' => $auth['app_id'],
		'client_secret' => $auth['app_secret'],
		'refresh_token' => $auth['refresh_token'],
	));

	writeToLog(Array('URL' => $queryUrl, 'PARAMS' => $queryParams), 'request auth data');

	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_HEADER => 0,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_URL => $queryUrl.'?'.$queryData,
	));

	$result = curl_exec($curl);
	curl_close($curl);

	$result = json_decode($result, 1);
	writeToLog($result, 'new auth data');
	$access_token = $result['access_token'];
	$refresh_token = $result['refresh_token'];
	sendReqestToDB("uPDATE `auth` SET `access_token` = '$access_token', `refresh_token` = '$refresh_token' WHERE `auth`.`id` = 17;");
	if (!isset($result['error']))
	{
		return $result;
		
		
		// $result['application_token'] = $auth['application_token'];
		// $appsConfig[$auth['application_token']]['AUTH'] = $result;

	}
	return false;

	
}

//для группы
// $data = json_decode(file_get_contents('php://input'));
// writeToLog($data, 'REQUEST from vk');
// //получаем токен битрикса
// $token = "91f53a7203ecea1ee18560656a5c9db1963062fdeb4e6642d513c751d48ace9be94c5aaae407aa30d4ab6";
// $auth = sendReqestToDB("select * FROM `auth` WHERE `auth`.`id` = 10");
// $user_id = $data->object->from_id;
// $user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$user_id}&access_token={$token}&v=5.92")); 
// $user_name = $user_info->response[0]->first_name;
// $user_last_name = $user_info->response[0]->last_name;
// //и извлекаем из ответа его имя 
// $user_name = $user_info->response[0]->first_name; 