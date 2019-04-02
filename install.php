<?php
require('tools.php');
writeToLog($_REQUEST, 'start install');
if ($_REQUEST['event'] == 'ONAPPINSTALL') {
	$result = restCommand('imconnector.register', Array(

		'ID' => 'my_open_lines_ilya_chazof', 
		'NAME' => 'My open lines',
		'ICON' => Array("DATA_IMAGE" => "image/svg+xml;charset=US-ASCII,%3C%3Fxml%20version%3D%221.0%22%20standalone%3D%22no%22%3F%3E%0A%3C%21DOCTYPE%20svg%20PUBLIC%20%22-//W3C//DTD%20SVG%2020010904//EN%22%0A%20%22http%3A//www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd%22%3E%0A%3Csvg%20version%3D%221.0%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%0A%20width%3D%22225.000000pt%22%20height%3D%22225.000000pt%22%20viewBox%3D%220%200%20225.000000%20225.000000%22%0A%20preserveAspectRatio%3D%22xMidYMid%20meet%22%3E%0A%3Cmetadata%3E%0ACreated%20by%20potrace%201.15%2C%20written%20by%20Peter%20Selinger%202001-2017%0A%3C/metadata%3E%0A%3Cg%20transform%3D%22translate%280.000000%2C225.000000%29%20scale%280.100000%2C-0.100000%29%22%0Afill%3D%22%23000000%22%20stroke%3D%22none%22%3E%0A%3Cpath%20d%3D%22M1048%202158%20c-207%20-196%20-357%20-541%20-360%20-830%20l-1%20-95%20-82%20-66%20c-98%20-78%0A-136%20-141%20-137%20-229%20-1%20-61%2067%20-382%2089%20-416%2017%20-28%2056%20-44%2088%20-36%2013%203%2072%2048%0A131%20100%2059%2052%20111%2094%20116%2094%205%200%2024%20-11%2041%20-24%2056%20-42%20107%20-58%20187%20-59%2080%20-1%0A138%2018%20202%2066%20l33%2025%20115%20-99%20c63%20-55%20125%20-102%20138%20-105%2028%20-8%2080%2013%2093%2037%205%0A11%2026%2097%2045%20191%2056%20273%2043%20330%20-105%20453%20l-78%2066%20-7%20112%20c-3%2062%20-13%20141%20-20%0A177%20-33%20155%20-108%20329%20-202%20471%20-58%2088%20-185%20219%20-212%20219%20-10%200%20-43%20-24%20-74%0A-52z%20m124%20-448%20c50%20-14%2097%20-55%20119%20-105%2060%20-132%20-53%20-282%20-196%20-261%20-155%2024%0A-215%20206%20-105%20316%2054%2054%20110%2070%20182%2050z%22/%3E%0A%3Cpath%20d%3D%22M1084%201610%20c-47%20-19%20-67%20-77%20-42%20-123%2017%20-33%2039%20-46%2078%20-46%2053%200%2090%0A38%2090%2093%200%2054%20-74%2099%20-126%2076z%22/%3E%0A%3Cpath%20d%3D%22M886%20484%20c-20%20-20%20-23%20-233%20-4%20-252%2020%20-20%2050%20-14%2083%2018%20l31%2030%2044%0A-91%20c69%20-139%2089%20-140%20156%20-8%2024%2049%2048%2089%2052%2089%205%200%2019%20-11%2032%20-25%2025%20-27%2059%0A-32%2078%20-13%208%208%2012%2051%2012%20130%200%20148%20-3%20150%20-117%20112%20-66%20-22%20-90%20-26%20-153%20-21%0A-41%203%20-99%2014%20-128%2026%20-63%2025%20-66%2025%20-86%205z%22/%3E%0A%3C/g%3E%0A%3C/svg%3E%0A"),
		'PLACEMENT_HANDLER' => ''			
	   
	 ), $_REQUEST[auth]);
	

	writeToLog($result, 'register connector');

	$result = restCommand('imconnector.activate', Array(
			"CONNECTOR" => 'my_open_lines_ilya_chazof',
			"LINE" => "2",
			"ACTIVE" => 1
		), $_REQUEST[auth]);


	writeToLog($result, 'activate connector');	


	$result = restCommand('event.bind', Array(
			'EVENT' => 'OnImConnectorMessageAdd',
			'HANDLER' => 'https://bitrix24.ichazof.tk/index.php'
		), $_REQUEST["auth"]);


	writeToLog($result, 'bind event');	
	
	$result = restCommand('event.get', Array(), $_REQUEST["auth"]);

	writeToLog($result, 'get events');	


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