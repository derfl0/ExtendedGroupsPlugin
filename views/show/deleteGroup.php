<form method="post" action="<?= $controller->url_for("show/deleteGroup/{$group->id}") ?>">
    <?= CSRFProtection::tokenTag() ?>
    <?= sprintf(_('Gruppe %s wirklich l�schen?'), $group->name) ?>
    <br>
    <?= Studip\Button::create(_('L�schen'), 'confirm') ?>
    <?= Studip\LinkButton::create(_('Abbrechen'), $controller->url_for('show/index'), array('class' => 'abort')) ?>
</form>