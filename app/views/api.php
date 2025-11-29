<?php

/**
 * api.php
 *
 * This file forms the base for views rendered for api consumption
 */

global $payload, $params, $dbUpdateTimestamp;
ob_start();
header('Content-Type: application/json');
header('X-XSS-Protection: 1; mode=block');
header('X-content-type-options: nosniff');
header('Access-Control-Allow-Origin: *');
if (isset($params) && isset($params['response_code'])) {
	http_response_code((int) $params['response_code']);
}
if (isset($params) && isset($params['headers'])) {
	foreach ($headers as $value) {
		header((string) $value);
	}
}
if (isset($payload)) {
	$data = [];
	if (isset($dbUpdateTimestamp)) {
		$data['last_update_time'] = $dbUpdateTimestamp;
	}
	if (is_bool($payload)) {
		$data['active'] = $payload;
	} else {
		$data['topics'] = $payload;
	}
	if ($encodedData = json_encode($data)) {
		echo $encodedData;
	} else {
		echo json_last_error_msg();
	}
}
ob_get_flush();
