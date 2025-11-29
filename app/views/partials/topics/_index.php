<?php
global $payload;
$numActive = count(array_filter($payload, function ($value, $i) {
    return $value->active;
}, ARRAY_FILTER_USE_BOTH));
?>
<div class="level">
    <div class="level-left">
        <div class="level-item">
            <h1 class="title"><span id="topic-counter"><?=count($payload);?></span> topics
                <small class="subtitle has-text-grey">(<span id="active-counter"><?=$numActive;?></span> <span class="active-descriptor">active</span>)</small>
            </h1>
        </div>
    </div>
    <div class="level-right">
        <div class="level-item buttons">
            <button type="button" id="toggle-inactive-btn" class="button is-info is-outlined">Hide inactive</button>
        </div>
    </div>
</div>
<div class="buttons">
    <button type="button" class="button is-primary is-outlined add-new-btn" data-action="<?=$router->generate("new_topic");?>">Add new topic</button>
    <button type="button" class="button is-primary save-all-btn">Save all</button>
</div>
<div id="topic-list" data-action="<?=$router->generate("reorder_topics");?>">
<?php
foreach ($payload as $topic) {
    require get_partial('topics/show');
}
unset($topic);
?>
</div>
<div class="buttons">
    <button type="button" class="button is-primary is-outlined add-new-btn" data-action="<?=$router->generate("new_topic");?>">Add new topic</button>
    <button type="button" class="button is-primary save-all-btn">Save all</button>
</div>
