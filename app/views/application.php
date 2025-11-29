<?php

/**
 * application.php
 *
 * This file forms the base for views rendered for a browser
 */

global $payload, $params, $router;
ob_start();
require_once get_partial('header');
if (isset($yield)) {
	require $yield;
}
require_once get_partial('footer');
ob_get_flush();
