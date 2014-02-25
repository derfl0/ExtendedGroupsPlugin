<form>
    <?= CSRFProtection::tokenTag() ?>
    <?= sprintf(_('Gruppe %s wirklich leeren?'), $group->name) ?>
    <br>
    <?= Studip\Button::create(_('Leeren'), 'confirm') ?>
    <?= Studip\LinkButton::create(_('Abbrechen'), $controller->url_for('show/index'), array('class' => 'abort')) ?>
</form>