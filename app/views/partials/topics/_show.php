<?php
$topic = isset($topic) ? $topic : $payload;
$topic->sortKeywords();
?>
<article class="box topic" id="<?="topic-$topic->id";?>">
    <div class="level">
        <div class="level-left is-level-mobile is-left-mobile">
            <div class="level-item">
                <h1 class="title is-5"><span class="topic-topic"><?=$topic->topic;?></span></h1>
            </div>
            <div class="level-item">
                <button class="button topic-active-toggle is-small is-rounded <?=$topic->active ? ' is-primary' : '';?>" id="<?="topic-active-toggle-$topic->id";?>" data-action="<?=$router->generate("modify_topic", ['id' => $topic->id]);?>">
                    <span class="topic-active"><?=$topic->active ? 'Active' : 'Inactive';?></span>
                </button>
            </div>
        </div>
        <div class="level-right is-level-mobile is-left-mobile">
            <div class="level-item">
                <div class="tags has-addons">
                    <span class="tag is-medium">Rank</span>
                    <span class="tag is-dark is-medium topic-rank"><?=$topic->rank;?></span>
                </div>
            </div>
            <div class="level-item">
                <div class="tags has-addons">
                    <span class="tag is-medium">Color</span>
                    <span class="tag is-dark is-medium has-text-monospaced topic-color topic-color-display"><?=$topic->color;?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="columns">
        <div class="column">
            <div class="tags">
                <?php foreach ($topic->keywords as $keyword): ?>
                <span class="tag topic-keyword is-priority-<?=$keyword['priority'];?>" data-priority="<?=$keyword['priority'];?>"><?=$keyword['keyword'];?></span>
                <?php endforeach;unset($keyword)?>
            </div>
        </div>
        <div class="column is-narrow">
            <div class="buttons is-right is-bottom">
                <button class="button is-danger is-outlined is-small topic-delete-btn" data-action="<?=$router->generate("delete_topic", ['id' => $topic->id]);?>">Delete</button>
                <button class="button is-outlined is-info is-small topic-edit-btn" data-action="<?=$router->generate('edit_topic', ['id' => $topic->id]);?>">Edit</button>
            </div>
        </div>
    </div>
</article>