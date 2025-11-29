<?php
if (isset($params)) {
    $key = isset($params['key']) ? $params['key'] : null;
    $keyword = isset($params['keyword']) ? $params['keyword'] : null;
}
$topic = isset($topic) ? $topic : $payload;
$id = is_int($topic->id) ? $topic->id : 0;
$key = isset($key) ? $key : 1;
$keyword = isset($keyword) ? $keyword : ['keyword' => '', 'priority' => 0];
?>
<div class="field has-addons topic-keyword" id="topic-<?=$id;?>-keyword-<?=$key;?>">
    <div class="control">
        <input type="text" name="topic-keywords[][keyword]" id="topic-<?=$id;?>-keyword-<?=$key;?>-keyword" class="input is-small keyword-keyword" value="<?=$keyword['keyword'];?>" required>
    </div>
    <div class="control">
        <div class="select is-small">
            <select class="select keyword-priority" name="keywords[][priority]" id="topic-<?=$id;?>-keyword-<?=$key;?>-priority">
                <option value="1"<?=$keyword['priority'] === 1 ? ' selected' : '';?>>Priority 1</option>
                <option value="2"<?=$keyword['priority'] === 2 ? ' selected' : '';?>>Priority 2</option>
            </select>
        </div>
    </div>
    <div class="control">
        <button type="button" class="button is-small is-danger is-outlined topic-delete-keyword-btn">
            <span class="icon">
                <i class="far fa-times"></i>
            </span>
        </button>
    </div>
</div>