<form method="post" action="<?= $controller->url_for('show/removeUsersWithoutGroup') ?>">
    <?= sprintf(_('Wollen sie wirklich folgende Benutzer aus der Veranstaltung %s austragen?'), Course::findCurrent()->name) ?>
    <ul>
        <? foreach ($users as $user): ?>
            <li>
                <?= $user->getFullname(); ?> (<?= $user->username; ?>)
            </li>
        <? endforeach; ?>
    </ul>
    <?= \Studip\Button::create('Austragen', 'execute') ?>
    <?= \Studip\Button::create('Abbrechen', 'abort', array('class' => 'abort')) ?>
</form>