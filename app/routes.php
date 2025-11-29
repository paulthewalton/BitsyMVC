<?php
$router->setBasePath(BASE_PATH);
$router->addRoutes([
	['GET',     '/',                            'TopicController::index',   'topics'],
	['POST',    '/',                            'TopicController::dataVis', 'active_topics'],
	['PATCH',   '/api/v1/topics',               'TopicController::reorder', 'reorder_topics'],
	['GET',     '/api/v1/topics/new',           'TopicController::new',     'new_topic'],
	['POST',    '/api/v1/topics/new',           'TopicController::save',    'create_topic'],
	['GET',     '/api/v1/topics/[i:id]',        'TopicController::show',    'show_topic'],
	['POST',    '/api/v1/topics/[i:id]',        'TopicController::save',    'update_topic'],
	['PATCH',   '/api/v1/topics/[i:id]',        'TopicController::modify',  'modify_topic'],
	['DELETE',  '/api/v1/topics/[i:id]',        'TopicController::delete',  'delete_topic'],
	['GET',     '/api/v1/topics/[i:id]/edit',   'TopicController::edit',    'edit_topic'],
	['GET',     '/api/v1/topics/keywords/new',  'TopicController::keyword', 'new_keyword'],
]);

if (MAINTENANCE_MODE && $_SERVER['REMOTE_ADDR'] !== 'XX.XX.XXX.XXX') {
	BaseController::maintenance503('Please check back later.');
}

if (ends_with($_SERVER['REQUEST_URI'], '.json')) {
	$_SERVER['HTTP_ACCEPT'] = 'application/json';
	$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, strlen($_SERVER['REQUEST_URI']) - 6);
}

if ($match = $router->match()) {
	if (is_callable($match['target'])) {
		call_user_func_array($match['target'], $match['params']);
	}
} else {
	BaseController::error(400);
}
