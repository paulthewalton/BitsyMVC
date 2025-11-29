<?php

/**
 * partial.php
 *
 * This file forms the base for partial views rendered for including in a
 * previously rendered view for a browser
 *
 * Eg: want to insert a form -> ajax request form -> return rendered partial
 */

global $payload, $params, $router;
ob_start();
if (isset($yield)) {
	require $yield;
}
ob_get_flush();
