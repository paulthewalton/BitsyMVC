<?php
class TopicController extends BaseController
{
	public static function index()
	{
		global $payload, $params;
		$payload = Topic::order('custom_order');
		$params = ['title' => 'Topics'];
		self::render('topics/index');
	}

	public static function new()
	{
		global $payload;
		$payload = Topic::create();
		self::render('topics/edit', false);
	}

	public static function show($id)
	{
		global $payload;
		try {
			if ($payload = Topic::find((int) $id)) {
				self::render('topics/show', false);
			} else {
				self::error(404);
			}
		} catch (Exception $e) {
			self::error(400);
		}
	}

	public static function edit($id)
	{
		global $payload;
		try {
			if ($payload = Topic::find((int) $id)) {
				self::render('topics/edit', false);
			} else {
				self::error(404);
			}
		} catch (Exception $e) {
			self::error(400);
		}
	}

	public static function save($id = null)
	{
		global $payload;
		$postData = $_POST;
		if (isset($postData['active'])) {
			$postData['active'] = ($postData['active'] === 'false') ? false : true;
		}
		if (isset($postData['keywords']) && is_array($postData['keywords'])) {
			$postData['keywords'] = array_map(function ($each) {
				return [
					'keyword' => $each['keyword'],
					'priority' => (int) $each['priority']
				];
			}, $postData['keywords']);
		} else {
			unset($postData['keywords']);
		}
		if (isset($postData['rank'])) {
			$postData['rank'] = (int) $postData['rank'];
		}
		if (isset($postData['custom_order'])) {
			$postData['custom_order'] = (int) $postData['custom_order'];
		} else {
			$postData['custom_order'] = isset($id) ? (int) $id : $id;
		}
		try {
			if (isset($id)) {
				if ($topic = Topic::find((int) $id)) {
					foreach ($postData as $prop => $value) {
						$topic->{$prop} = $value;
					}
					$topic->save();
					$payload = $topic->refresh();
					self::render('topics/show', false);
				} else {
					self::error(404);
				}
			} else {
				$topic = Topic::create();
				foreach ($postData as $prop => $value) {
					$topic->{$prop} = $value;
				}
				$topic->save();
				$payload = Topic::findBy('topic', $topic->topic);
				self::render('topics/show', false);
			}
		} catch (Exception $e) {
			self::error(400);
		}
	}

	public static function delete($id)
	{
		global $payload;
		try {
			$payload = Topic::destroy((int) $id);
			self::render('json');
		} catch (Exception $e) {
			self::error(400);
		}
	}

	public static function toggle($id)
	{
		global $payload;
		try {
			if ($topic = Topic::find((int) $id)) {
				$topic->active = !$topic->active;
				$topic->save();
				$topic->refresh();
				$payload = $topic->active;
				self::render('json');
			} else {
				self::error(404);
			}
		} catch (Exception $e) {
			self::error(400);
		}
	}

	public static function keyword($id = 0)
	{
		global $payload, $params;
		if (isset($id)) {
			$id = (int) $id;
			if ($id !== 0) {
				try {
					if ($payload = Topic::find($id)) {
						$params = ['key' => count($payload->keywords)];
						self::render('topics/components/keyword-form', false);
					} else {
						self::error(404);
					}
				} catch (Exception $e) {
					self::error(400);
				}
			} else {
				$payload = Topic::create();
				self::render('topics/components/keyword-form', false);
			}
		} else {
			self::error(400);
		}
	}

	public static function modify($id)
	{
		global $payload;
		$patchData = json_decode(file_get_contents('php://input'), true);
		if (isset($patchData['active'])) {
			$patchData['active'] = ($patchData['active'] === 'false') ? false : true;
		}
		if (isset($patchData['keywords']) && is_array($patchData['keywords'])) {
			$patchData['keywords'] = array_map(function ($each) {
				return [
					'keyword' => $each['keyword'],
					'priority' => (int) $each['priority']
				];
			}, $patchData['keywords']);
		} else {
			unset($patchData['keywords']);
		}
		if (isset($patchData['rank'])) {
			$patchData['rank'] = (int) $patchData['rank'];
		}
		if (isset($patchData['custom_order'])) {
			$patchData['custom_order'] = (int) $patchData['custom_order'];
		} else {
			$patchData['custom_order'] = isset($id) ? (int) $id : $id;
		}
		try {
			if ($topic = Topic::find((int) $id)) {
				foreach ($patchData as $prop => $value) {
					$topic->{$prop} = $value;
				}
				$topic->save();
				$topic->refresh();
				$payload = $topic;
				self::render('json');
			} else {
				self::error(404);
			}
		} catch (Exception $e) {
			self::error(400);
		}
	}

	public static function reorder()
	{
		$patchData = json_decode(file_get_contents('php://input'), true);
		$payload = Topic::reorder($patchData);
	}

	public static function dataVis()
	{
		global $payload;
		$payload = Topic::query('SELECT * FROM "topics" WHERE "active" IS :active ORDER BY "custom_order"', ['active' => true]);
		self::render('json');
	}
}
