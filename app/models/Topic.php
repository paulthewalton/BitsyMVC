<?php
class Topic extends BaseRecord
{
	const TABLE_NAME = 'topics';
	const COLUMNS = [
		'topic' => [
			'type' => 'VARCHAR',
			'constraints' => 'UNIQUE NOT NULL',
		],
		'rank' => [
			'type' => 'INTEGER',
			'constraints' => 'NOT NULL',
		],
		'color' => [
			'type' => 'TEXT',
			'constraints' => 'NOT NULL',
		],
		'active' => [
			'type' => 'BOOLEAN',
			'constraints' => 'NOT NULL',
		],
		'keywords' => [
			'type' => 'BLOB',
			'constraints' => '',
		],
		'custom_order' => [
			'type' => 'INTEGER',
			'constraints' => ''
		]
	] + parent::COLUMNS;

	protected $data = [
		'id' => null,
		'created_at' => null,
		'updated_at' => null,
		'topic' => null,
		'rank' => 1,
		'color' => '#DF0031',
		'active' => true,
		'keywords' => [],
		'custom_order' => null,
	];

	public static function validate($prop, $value)
	{
		switch ($prop) {
			case 'id':
				if ($value !== null && !is_int($value)) {
					throw new InvalidArgumentException("$prop must be an integer or null {{$value}}", 1);
				} elseif (is_int($value) && $value < 1) {
					throw new InvalidArgumentException("$prop must be greater than 0 {{$value}}", 1);
				}
				break;
			case 'created_at':
				break;
			case 'updated_at':
				break;
			case 'topic':
				if (!is_string($value) || $value === '') {
					throw new InvalidArgumentException("$prop cannot be empty {{$value}}", 1);
				}
				break;
			case 'rank':
				if (!is_int($value)) {
					throw new InvalidArgumentException("$prop must be an integer {{$value}}", 1);
				} elseif (!in_array($value, range(1, 3))) {
					throw new InvalidArgumentException("$prop may only have a value of 1, 2 or 3 {{$value}}", 1);
				}
				break;
			case 'color':
				if (!is_string($value) || !preg_match('/#([a-f0-9]{3}){1,2}\b/i', $value)) {
					throw new InvalidArgumentException("$prop must be a 3- or 6-digit hexcode string preceded by a hash (#) {{$value}}", 1);
				}
				break;
			case 'active':
				if (!is_bool($value)) {
					throw new InvalidArgumentException("$prop must be a boolean {{$value}}", 1);
				}
				break;
			case 'keywords':
				if (!is_array($value)) {
					throw new InvalidArgumentException("$prop must be an array {{$value}}", 1);
				} elseif (count(array_filter($value, function ($keyword, $i) {
					return !is_string($keyword['keyword']) || !is_int($keyword['priority']);
				}, ARRAY_FILTER_USE_BOTH)) > 0) {
					throw new InvalidArgumentException("$prop must be an array of sub-arrays, each with a string ['keyword'] and an int ['priority']", 1);
				}
				break;
			case 'custom_order':
				if (isset($value) && !is_int($value)) {
					throw new InvalidArgumentException("$prop must be an integer {{$value}}", 1);
				}
				break;
			default:
				throw new UnexpectedValueException("Unexpected property \"$prop\"", 1);
		}
		return true;
	}

	public function sortKeywords(): void
	{
		if (is_array($this->data['keywords'])) {
			usort($this->data['keywords'], function ($a, $b) {
				return $a['priority'] <=> $b['priority'];
			});
		}
	}

	public function jsonSerialize(): array
	{
		$jsonData = [];
		$jsonData['id'] = $this->id;
		$jsonData['name'] = $this->topic;
		$jsonData['color'] = $this->color;
		$jsonData['rank'] = $this->rank;
		$jsonData['active'] = $this->active;
		$jsonData['keywords'] = array_map(function ($each) {
			return [
				'name' => $each['keyword'],
				'priority' => $each['priority']
			];
		}, $this->keywords);
		return $jsonData;
	}

	public static function reorder($newOrder): void
	{
		$db = Topic::openDataBase();
		$db->exec('BEGIN');
		foreach ($newOrder as $idOrderPair) {
			$db->exec('UPDATE "topics" SET "custom_order" = ' . $idOrderPair['order'] . ' WHERE "id" = ' . $idOrderPair['id']);
		}
		$db->exec('COMMIT');
	}
}
