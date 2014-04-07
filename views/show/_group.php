<a name="group-<?= $group->id ?>"></a>
<table id="<?= $group->id ?>" class="default moveable inflatable" style="margin-top: 30px;">
    <colgroup>
        <col width="1">
        <col width="1">
        <col width="10">
        <col>
        <col width="10%">
    </colgroup>
    <caption>
        <?= $numbers[$group->id] ?> <?= formatReady($group->name) ?>
        <span class="actions">
            <? if ($type['needs_self_assign']): ?>
                <?
                switch ($group->selfassign):
                    case 0:
                        ?>
                        <?= Assets::img("icons/16/grey/lock-locked.png", tooltip2(_('Diese Gruppe ist offen. Benutzer können sich jederzeit eintragen'))) ?>
                        <?php break; ?>
                    <? case 1: ?>
                        <?= Assets::img("icons/16/grey/lock-unlocked.png", tooltip2(_('Diese Gruppe ist geschlossen. Benutzer können sich nicht selbständig in dieser Gruppe anmelden'))) ?>
                        <?php break; ?>
                    <? case 2: ?>
                        <?= Assets::img("icons/16/grey/star.png", tooltip2(_('Diese Gruppe ist exklusiv. Benutzer können sich maximal in einer exklusiven Gruppe anmelden'))) ?>
                <? endswitch; ?>
            <? endif; ?>
            <? if ($group->additional->waitinglist): ?>
                <?= Assets::img("icons/16/grey/log.png", tooltip2(_('Diese Gruppe verfügt über eine Warteliste. Benutzer können sich über die eigentliche Gruppengröße hinaus eintragen und rücken automatisch nach'))) ?>
            <? endif; ?>
            <? if ($tutor): ?>
                <? if ($group->additional->visible): ?>
                    <?= Assets::img("icons/16/grey/visibility-visible.png", tooltip2(_('Diese Gruppe ist für Benutzer sichtbar'))) ?>
                <? else: ?>
                    <?= Assets::img("icons/16/grey/visibility-invisible.png", tooltip2(_('Diese Gruppe ist für Benutzer unsichtbar'))) ?>
                <? endif; ?>
                <a class='modal' title="<?= _('Gruppe ändern') ?>" href="<?= $controller->url_for("show/editGroup/{$group->id}") ?>">
                    <?= Assets::img("icons/16/blue/edit.png", tooltip2(_('Gruppe ändern'))) ?>
                </a>
                <a class='modal' title="<?= _('Termine anpassen') ?>" href="<?= $controller->url_for("show/termine/{$group->id}") ?>">
                    <?= Assets::img("icons/16/blue/date.png", tooltip2(_('Termine anpassen'))) ?>
                </a>
                <a class='modal' title="<?= _('Mitglieder hinzufügen') ?>" href="<?= $controller->url_for("show/memberAdd/{$group->id}") ?>">
                    <?= Assets::img("icons/16/blue/add/community.png", tooltip2(_('Mitglieder hinzufügen'))) ?>
                </a>
                <a class='modal' title="<?= _('Gruppe löschen') ?>" href="<?= $controller->url_for("show/deleteGroup/{$group->id}") ?>">
                    <?= Assets::img("icons/16/blue/trash.png", tooltip2(_('Gruppe löschen'))) ?>
                </a>
                <? if (!$group->additional->waitinglist): ?>
                    <a class='modal' title="<?= _('Gruppe alphabetisch sortieren') ?>" href="<?= $controller->url_for("show/sortAlphabetic/{$group->id}") ?>">
                        <?= Assets::img("icons/16/blue/arr_2down.png", tooltip2(_('Gruppe alphabetisch sortieren'))) ?>
                    </a>
                <? endif; ?>
            <? else: ?>
                <a class='modal' title="<?= _('Termine anzeigen') ?>" href="<?= $controller->url_for("show/termine/{$group->id}") ?>">
                    <?= Assets::img("icons/16/blue/date.png", tooltip2(_('Termine anzeigen'))) ?>
                </a>
                <? if ($type['needs_self_assign']): ?>
                    <? if ($group->isMember() && $group->selfassign): ?>
                        <a href="<?= $controller->url_for("show/leaveGroup/{$group->id}") ?>">
                            <?= Assets::img("icons/16/blue/door-leave.png", tooltip2(_('Gruppe verlassen'))) ?>
                        </a>
                    <? endif; ?>
                    <? if ($group->userMayJoin($user_id)): ?>
                        <a href="<?= $controller->url_for("show/joinGroup/{$group->id}") ?>">
                            <?= Assets::img("icons/16/blue/door-enter.png", tooltip2(_('Gruppe beitreten'))) ?>
                        </a>
                    <? endif; ?>
                <? endif; ?>
            <? endif; ?>
        </span>
    </caption>
    <thead>
        <tr>
            <th colspan="4">
                <?= min(array(count($group->members), $group->size ? : PHP_INT_MAX)) ?> <?= count($group->members) != 1 ? _('Mitglieder') : _('Mitglied'); ?>
            <th class="actions"></th>
        </tr>
    </thead>
    <tbody>
        <?= $this->render_partial("show/_members.php", array('group' => $group)) ?>
    </tbody>
    <tfoot>
    </tfoot>
</table>
<? if ($group->children): ?>
    <ul class='tree-seperator'>
        <li>
            <? foreach ($group->children as $child): ?>
                <?= $this->render_partial('show/_group.php', array('group' => $child)) ?>
            <? endforeach ?>
        </li>
    </ul>
<? endif; ?>
