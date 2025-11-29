<?php
$class = get_class($payload);
$properties = array_filter($payload->data, function ($key) {
	return !in_array($key, BaseRecord::METADATA);
}, ARRAY_FILTER_USE_KEY)
?>
<div class="content">
	<h1><?= $class; ?></h1>
	<h2>Info</h2>
	<dl>
		<?php foreach ($properties as $key => $value): ?>
			<dt><?= $key; ?></dt>
			<dd><?= value_format($value); ?></dd>
		<?php endforeach; ?>
	</dl>
	<h3>Metadata</h3>
	<dl>
		<dt><small>ID</small></dt>
		<dd><small><?= $record->id; ?></small></dd>
		<dt><small>Created</small></dt>
		<dd><small><?= $record->created_at; ?></small></dd>
		<dt><small>Updated</small></dt>
		<dd><small><?= $record->updated_at; ?></small></dd>
	</dl>
</div>
