<?php
if (isset($params) && isset($params['headers'])) {
	foreach ($headers as $value) {
		header((string) $value);
	}
}
if (isset($params) && isset($params['response_code'])) {
	http_response_code((int) $params['response_code']);
	echo '<h1 class="title is-1 has-text-centered">' . $params['response_code'] . '</h1>';
}
if (isset($payload)) {
	echo '<p class="subtitle is-3 has-text-centered">' . $payload . '</p>';
}
