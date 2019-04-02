<?php
error_reporting(0);

#####################
### CONFIG  ###
#####################
define('TOKEN', '20d852642e25766f156434530a63ee85bca272f3c70814f6fdd72e465dc6525db6e0c992887c7d17475cd');

define('CLIENT_ID', 'local.5c61c59e0d00b1.26243302'); // like 'app.67efrrt2990977.85678329' or 'local.57062d3061fc71.97850406' - This code should take in a partner's site, needed only if you want to write a message from Bot at any time without initialization by the user
define('CLIENT_SECRET', '3uWjRvLR34udcfTaqKMiuAMwg4dRNDVyKwtOxk9O7HwKoNn6Xo'); // like '8bb00435c88aaa3028a0d44320d60339' - TThis code should take in a partner's site, needed only if you want to write a message from Bot at any time without initialization by the user
#####################

writeToLog($_REQUEST, 'request');

$appsConfig = Array();
if (file_exists(__DIR__.'/config.php'))
	include(__DIR__.'/config.php');


if ($_REQUEST['event'] == 'ONIMCONNECTORMESSAGEADD') {
	$result = restCommand('imconnector.send.status.delivery', Array(
		'CONNECTOR' => $_REQUEST['data']['CONNECTOR'],
		'LINE' => $_REQUEST['data']['LINE'],
		'MESSAGES' => $_REQUEST['data']['MESSAGES']
	), $_REQUEST["auth"]);

	writeToLog($result, 'send status delivery');	

	$result = apiCommand('messages.send', Array(
            'user_id' => $_REQUEST['data']['MESSAGES'][0]['chat']['id'],
            'random_id' => time(),
            'message' => $_REQUEST['data']['MESSAGES'][0]['message']['text']
        ));

	// $result = restCommand('imconnector.send.messages', Array(
	// 	"CONNECTOR" => 'my_open_lines_ilya_chazof',
	// 	"LINE" => "2",
	// 	"MESSAGES" => Array(
	// 		Array(
	// 		   //Массив описания пользователя
	// 		   'user' => Array(
	// 			  'id' => '123456',//ID пользователя во внешней системе *
	// 			  'last_name' => 'test',//Фамилия
	// 			  'name' => 'testov'//Имя
				  
	// 		   ),
	// 		   //Массив описания сообщения
	// 		   'message' => Array(
	// 			  'id' => '1246', //ID сообщения во внешней системе.*
	// 			  'date' => '1549378080', //Время сообщения в формате timestamp *
	// 			  'text' => !isset($_REQUEST['data']['MESSAGES'][0]['message']['text']) ? 'no message' : $_REQUEST['data']['MESSAGES'][0]['message']['text']
	// 		   ),

	// 			//Массив описания чата
	// 			'chat' => array(
	// 				'id' => '223',//ID чата во внешней системе *
	// 			)
	// 		   )
	// 	   )
	// ),  $_REQUEST["auth"]);
	saveParams($_REQUEST['auth']);
	writeToLog($result, 'send message to vk');	
}

		
		


/**
 * Save application configuration.
 * WARNING: this method is only created for demonstration, never store config like this
 *
 * @param $params
 * @return bool
 */
function saveParams($params)
{
	$config = "<?php\n";
	$config .= "\$appsConfig = ".var_export($params, true).";\n";
	$config .= "?>";

	file_put_contents(__DIR__."/config.php", $config);

	return true;
}

/**
 * Send rest query to Bitrix24.
 *
 * @param $method - Rest method, ex: methods
 * @param array $params - Method params, ex: Array()
 * @param array $auth - Authorize data, received from event
 * @param boolean $authRefresh - If authorize is expired, refresh token
 * @return mixed
 */
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

/**
 * Get new authorize data if you authorize is expire.
 *
 * @param array $auth - Authorize data, received from event
 * @return bool|mixed
 */
function restAuth($auth)
{
	if (!CLIENT_ID || !CLIENT_SECRET)
		return false;

	if(!isset($auth['refresh_token']))
		return false;

	$queryUrl = 'https://oauth.bitrix.info/oauth/token/';
	$queryData = http_build_query($queryParams = array(
		'grant_type' => 'refresh_token',
		'client_id' => CLIENT_ID,
		'client_secret' => CLIENT_SECRET,
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
	if (!isset($result['error']))
	{
		$appsConfig = Array();
		if (file_exists(__DIR__.'/config.php'))
			include(__DIR__.'/config.php');

		$result['application_token'] = $auth['application_token'];
		$appsConfig = $result;
		saveParams($appsConfig);
	}
	else
	{
		$result = false;
	}

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

	// if ($authRefresh && isset($result['error']) && in_array($result['error'], array('expired_token', 'invalid_token')))
	// {
	// 	$auth = restAuth($auth);
	// 	if ($auth)
	// 	{
	// 		$result = restCommand($method, $params, $auth, false);
	// 	}
	// }

	return $result;
}

