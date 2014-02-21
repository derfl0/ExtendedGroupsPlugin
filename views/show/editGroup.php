<form class="studip_form" action="<?= $controller->url_for('show') ?>#group-<?= $group->id ?>" method="POST">
    <input type="hidden" name="id" value="<?= $group->id ?>">
    <label class="caption"><?= _('Gruppenname') ?>
        <input name="name" required="true" class="groupname" type="text" size="50" placeholder="<?= _('Mitarbeiterinnen und Mitarbeiter') ?>" value="<?= formatReady($group->name) ?>" >
    </label>
    <? if ($type['needs_size']): ?>
        <label class="caption"><?= _('Größe') ?>
            <input name="size" type="text" size="10" placeholder="<?= _('Unbegrenzt') ?>" value="<?= formatReady($group->size) ?>" >
        </label>
    <? endif; ?>
    <? foreach ($group->getDatafields() as $field): ?>
        <label class="caption"><?= $field->getName() ?>
            <?= $field->getHTML('datafields') ?>
        </label>
    <? endforeach; ?>
        <label class="caption">
        <?= _('Eintragstyp') ?>
            <select  name="selfassign">
                <option value="1" <?= $group->selfassign == 1 ? "SELECTED" : "" ?>><?= _('Offen') ?></option>
                <option value="2" <?= $group->selfassign == 2 ? "SELECTED" : "" ?>><?= _('Exklusiv') ?></option>
                <option value="0" <?= $group->selfassign == 0 ? "SELECTED" : "" ?>><?= _('Geschlossen') ?></option>
            </select>
        </label>
    <label class="caption">
        <input name="waitinglist" type="checkbox" value="1" <?= $group->additional->waitinglist ? "CHECKED" : "" ?>>
        <?= _('Warteliste') ?>
    </label>
    <label class="caption">
        <input name="visible" type="checkbox" value="1" <?= $group->additional->visible ? "CHECKED" : "" ?>>
        <?= _('Sichtbar') ?>
    </label>
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
