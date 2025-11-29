<?php
$topic = isset($topic) ? $topic : $payload;
$isNew = !is_int($topic->id);
$id = $isNew ? 0 : $topic->id;
$topic->sortKeywords();
$placeholders = ['canucks', 'hockey', 'yoga', '#throwbackthursday', 'traffic', '#vanpoli', 'sriracha', 'kombucha', '#weather', '#stillraining', 'rain&trade;', '#rain', '#rainagain','#rainyetagain', '#youcallthisrain?', '#rainlife', '#GotRain?', '#ICantBelieveItsNotRain', '#RainItsWhatsForDinner', '@VancityReynolds'];
?>
<article class="box topic is-being-edited <?= $isNew ? 'new' : ''; ?>" id="topic-<?=$id;?>">
    <form class="topic-edit" data-action="action-<?=$id;?>">
        <h1 class="title is-4"><?=($isNew ? 'Add' : 'Edit');?> Topic</h1>
        <div class="field">
            <label for="topic-<?=$id;?>-topic" class="label">Topic <small class="subtitle is-6 is-italic has-text-grey">(required)</small></label>
            <div class="control">
                <input type="text" name="topic" id="topic-<?=$id;?>-topic" class="input" value="<?=$topic->topic;?>" required placeholder="<?= $placeholders[rand(0, count($placeholders)-1)]; ?>">
            </div>
        </div>
        <div class="field">
            <div class="control">
                <label for="topic-<?=$id;?>-active" class="checkbox">
                    <input type="checkbox" name="active" id="topic-<?=$id;?>-active" <?=$topic->active ? ' checked' : '';?>> Active
                </label>
            </div>
        </div>
        <div class="columns is-mobile is-column-mobile-only">
            <div class="column is-half-tablet is-ordered-2-mobile-only">
                <fieldset class="field">
                    <legend class="label">Keywords</legend>
<?php
if (is_array($topic->keywords)) {
    foreach ($topic->keywords as $key => $keyword) {
        require get_partial('/topics/components/keyword-form');
    }
    unset($key, $keyword);
}
?>
                    <button type="button" class="button is-small is-outlined topic-add-keyword-btn" data-action="<?=$router->generate('new_keyword');?>">Add keyword</button>
                </fieldset>
            </div>
            <div class="column is-half-tablet is-ordered-1-mobile-only">
                <div class="columns is-mobile">
                    <div class="column">
                        <div class="field">
                            <label for="topic-<?=$id;?>-color" class="label">Color<span class="color-indicator topic-color-display"></span></label>
                            <div class="control">
                                <input type="text" name="color" id="topic-<?=$id;?>-color" class="input has-text-monospaced" value="<?=$topic->color;?>" required>
                            </div>
                            <p class="help">Must be a valid RGB hexcode</p>
                        </div>
                    </div>
                    <div class="column">
                        <div class="field">
                            <label for="topic-<?=$id;?>-rank" class="label">Rank</label>
                            <div class="field has-addons">
                                <div class="control">
                                    <div class="button is-static"><span class="icon"><i class="far fa-bullhorn"></i></span></div>
                                </div>
                                <div class="control">
                                    <div class="select">
                                        <select class="select" name="rank" id="topic-<?=$id;?>-rank">
                                            <option value="1"<?=$topic->rank === 1 ? ' selected' : '';?>>1</option>
                                            <option value="2"<?=$topic->rank === 2 ? ' selected' : '';?>>2</option>
                                            <option value="3"<?=$topic->rank === 3 ? ' selected' : '';?>>3</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="buttons is-right">
            <button type="button" class="button is-danger is-outlined is-small topic-delete-btn" data-action="<?=$router->generate("delete_topic", ['id' => $topic->id]);?>">Delete</button>
            <button type="button" class="button is-info is-outlined is-small topic-reset-btn" data-action="<?=$router->generate("show_topic", ['id' => $topic->id]);?>">Cancel</button>
        <?php if ($isNew): ?>
            <button type="submit" class="button is-primary is-small topic-save-btn" data-action="<?=$router->generate("create_topic");?>">Save topic</button>
        <?php else: ?>
            <button type="submit" class="button is-primary is-small topic-save-btn" data-action="<?=$router->generate("update_topic", ['id' => $topic->id]);?>">Save topic</button>
        <?php endif;?>
        </div>
    </form>
</article>
