<? foreach ($path as $name => $p): ?>
    <input type="hidden" id="<?= $name ?>" value="<?= $p ?>" />
<? endforeach; ?>
<? if (!$groups): ?>
    <?= _('Es wurden noch keine Gruppen angelegt') ?>
<? endif; ?>
<? foreach ($groups as $group): ?>
    <? if(!($tutor  || $group->additional->visible)) continue; ?>
    <?= $this->render_partial('show/_group.php', array('group' => $group)) ?>
<? endforeach; ?>