<?php
class BaseController
{
	// protected $actions = array();

	final public static function render($view = 'index', $fullHtml = true)
	{
		if (in_array($view, ['json', 'JSON', 'api', 'API']) || request_json()) {
			require_once VIEWS . '/api.php';
		} else {
			$yield = get_partial($view);
			require_once VIEWS . ($fullHtml ? '/application.php' : '/partial.php');
		}
	}

	final public static function error($code = 404)
	{
		switch ($code) {
			case 400:
				static::badRequest400();
				break;
			case 401:
				static::unauthorized401();
				break;
			case 403:
				static::forbidden403();
				break;
			case 405:
				static::notAllowed405();
				break;
			case 418:
				static::teapot418();
				break;
			case 404:
			default:
				static::notFound404();
				break;
		}
	}

	public static function badRequest400($message = null)
	{
		global $payload, $params;
		$payload = 'Could not understand your request due to invalid syntax.';
		if (isset($message)) {
			$payload .= " $message";
		}
		$params = ['response_code' => 400];
		static::render('error');
	}

	public static function unauthorized401($message = null)
	{
		global $payload, $params;
		$payload = 'You must authenticate yourself properly.';
		if (isset($message)) {
			$payload .= " $message";
		}
		$params = ['response_code' => 401];
		static::render('error');
	}

	public static function forbidden403($message = null)
	{
		global $payload, $params;
		$payload = 'You do not have access rights for this resource.';
		if (isset($message)) {
			$payload .= " $message";
		}
		$params = ['response_code' => 403];
		static::render('error');
	}

	public static function notFound404($message = null)
	{
		global $payload, $params;
		$payload = 'Resource not found.';
		if (isset($message)) {
			$payload .= " $message";
		}
		$params = ['response_code' => 404];
		static::render('error');
	}

	public static function notAllowed405($message = null)
	{
		global $payload, $params;
		$payload = 'Method not allowed.';
		if (isset($message)) {
			$payload .= " $message";
		}
		$params = ['response_code' => 405];
		static::render('error');
	}

	public static function teapot418($message = null)
	{
		global $payload, $params;
		$payload = 'The server refuses the attempt to brew coffee with a teapot.';
		if (isset($message)) {
			$payload .= " $message";
		}
		$params = ['response_code' => 418];
		static::render('error');
	}

	public static function maintenance503($message = null)
	{
		global $payload, $params;
		$payload = 'This service is down for maintenance and is unavailable.';
		if (isset($message)) {
			$payload .= " $message";
		}
		$params = ['response_code' => 503];
		static::render('error');
	}

	public static function debug($payload)
	{
		$GLOBALS['payload'] = $payload;
		static::render('debug');
	}
}
