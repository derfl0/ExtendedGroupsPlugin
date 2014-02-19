<form class="studip_form" action="<?= $controller->url_for('show') ?>#group-<?= $group->id ?>" method="POST">
    <input type="hidden" name="id" value="<?= $group->id ?>">
    <label class="caption"><?= _('Gruppenname') ?>
        <input name="name" required="true" class="groupname" type="text" size="50" placeholder="<?= _('Mitarbeiterinnen und Mitarbeiter') ?>" value="<?= formatReady($group->name) ?>" >
    </label>
    <? if ($type['needs_size']): ?>
        <label class="caption"><?= _('Gr��e') ?>
            <input name="size" type="text" size="10" placeholder="<?= _('Unbegrenzt') ?>" value="<?= formatReady($group->size) ?>" >
        </label>
    <? endif; ?>
    <? foreach ($group->getDatafields() as $field): ?>
        <label class="caption"><?= $field->getName() ?>
            <?= $field->getHTML('datafields') ?>
        </label>
    <? endforeach; ?>
    <? if ($type['needs_self_assign']): ?>
        <label class="caption">
            <input name="selfassign" type="checkbox" value="1" <?= $group->selfassign ? "CHECKED" : "" ?>>
            <?= _('Selbsteintrag') ?>
        </label>
    <? endif; ?>
    <noscript>
    <label class="caption"><?= _('Position') ?>
        <input name="size" type="text" size="10" placeholder="<?= _('0') ?>" value="<?= formatReady($group->position) ?>" >
    </label>
    </noscript>
    <label class="caption"><?= _('Einordnen unter') ?>
        <select name='range_id'>
            <option value='<?= $_SESSION['SessionSeminar'] ?>'>- <?= _('Hauptebene') ?> -</option>
            <?= $this->render_partial("show/_edit_subgroupselect.php", array('groups' => $groups, 'selected' => $group)) ?>
        </select>
    </label>
    <?= Studip\Button::create(_('Speichern'), 'save') ?>
    <?= Studip\LinkButton::create(_('Abbrechen'), URLHelper::getLink('dispatch.php/show/index'), array('class' => 'abort')) ?>
</form>
