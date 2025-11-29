<?php

//------------------------------------------------------------------------------
// Helper functions
//

function debug_var($var): void
{
	echo '<pre>';
	var_dump($var);
	echo '</pre>';
}

function starts_with($haystack, $needle): bool
{
	return (substr($haystack, 0, strlen($needle)) === $needle);
}

function ends_with($haystack, $needle): bool
{
	return $needle === '' || (substr($haystack, -strlen($needle)) === $needle);
}

//------------------------------------------------------------------------------
// Application functions
//

function get_title(): string
{
	global $params;
	if (isset($params) && isset($params['title'])) {
		return $params['title'] . ' | CBC Plaza';
	}
	return 'CBC Plaza';
}

function get_partial($view): string
{
	$extensions = ['.php', '.html'];
	$pathSegments = explode('/', $view);
	$viewName = array_pop($pathSegments);
	$viewName = (strpos($viewName, '_') === 0) ? $viewName : '_' . $viewName;
	do {
		$dirPath = implode('/', $pathSegments);
		$targetDirectory = VIEW_PARTIALS . ($dirPath !== '' ? "/$dirPath" : '');
		foreach ($extensions as $ext) {
			if ($viewFile = find_in_dir($viewName . $ext, $targetDirectory)) {
				return $targetDirectory . '/' . $viewName . $ext;
			}
		}
	} while (array_pop($pathSegments));

	return VIEW_PARTIALS . '/' . '_404.php';
}

function find_in_dir($file, $dir = null): mixed
{
	$dir = isset($dir) ? $dir : __DIR__;
	if (file_exists($dir)) {
		$files = scandir($dir);
		if (in_array($file, $files)) {
			return $file;
		}
	}
	return false;
}

function value_format($value): mixed
{
	$formatted = null;
	if (is_array($value)) {
		$formatted = [];
		foreach ($value as $key => $value) {
			if (is_string($key)) {
				$formatted[] = "$key => " . value_format($value);
			} else {
				$formatted[] = value_format($value);
			}
		}
		$formatted = '[ ' . implode(', ', $formatted) . ' ]';
	} elseif (is_bool($value)) {
		$formatted = $value ? 'true' : 'false';
	}
	return isset($formatted) ? $formatted : $value;
}

function request_json(): bool
{
	return isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] === 'application/json';
}
