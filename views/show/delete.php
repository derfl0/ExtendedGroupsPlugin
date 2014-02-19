<form method="post" action="<?= $controller->url_for("show/delete/{$group->id}/{$user->user_id}") ?>">
    <?= CSRFProtection::tokenTag() ?>
    <?= sprintf(_('%s wirklich aus %s austragen?'), $user->getFullname(), $group->name) ?>
    <br>
    <?= Studip\Button::create(_('Entfernen'), 'confirm') ?>
    <?= Studip\LinkButton::create(_('Abbrechen'), URLHelper::getLink('show/index'), array('class' => 'abort')) ?>
</form>