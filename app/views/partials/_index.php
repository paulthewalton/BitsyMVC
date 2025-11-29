<?php
$object = is_array($payload) ? $payload[0] : $payload;
$class = get_class($object);
$classStr = strtolower($class);
$cols = $object::COLUMNS;
$propertyCols = array_filter($cols, function ($key) use ($class): bool {
	return !in_array($key, $class::METADATA);
}, ARRAY_FILTER_USE_KEY);
$metaCols = $object::METADATA;
?>
<div class="content">
	<h1><?= $class; ?>s</h1>
	<table>
		<colgroup id="colgroup-properties">
			<?php foreach ($propertyCols as $columnName => $manifest): ?>
				<col id="col-<?= $columnName; ?>" class="col-<?= strtolower($manifest['type']); ?>">
			<?php endforeach; ?>
		</colgroup>
		<colgroup id="colgroup-metadata">
			<?php foreach ($metaCols as $columnName => $manifest): ?>
				<col id="col-<?= $columnName; ?>" class="col-<?= strtolower($manifest['type']); ?>">
			<?php endforeach; ?>
		</colgroup>
		<colgroup id="colgroup-actions">
			<col id="col-show" class="col-action">
			<col id="col-edit" class="col-action">
			<col id="col-delete" class="col-action">
		</colgroup>
		<thead>
			<tr>
				<?php foreach (array_keys($cols) as $columnName): ?>
					<th><?= $columnName; ?></th>
				<?php endforeach; ?>
				<th colspan="3">Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($payload as $unit): ?>
				<tr>
					<?php foreach (array_keys($cols) as $prop): ?>
						<td><?= value_format($unit->{$prop}); ?></td>
					<?php endforeach; ?>
					<td><a href="<?= $router->generate("show_$classStr", ['id' => $unit->id]); ?>">Show</a></td>
					<td><a href="<?= $router->generate("edit_$classStr", ['id' => $unit->id]); ?>">Edit</a></td>
					<td><a href="<?= $router->generate("delete_$classStr", ['id' => $unit->id]); ?>">Delete</a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
