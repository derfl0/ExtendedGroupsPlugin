<? foreach ($group->members->orderBy('position') as $user): ?>
    <tr data-userid="<?= $user->user_id ?>">
        <td class="dragHandle"></td>
        <td><?= $user->position + 1 ?></td>
        <td><?= $user->avatar() ?></td>
        <td><?= $user->name() ?></td>
        <td class="actions">
            <? if($tutor): ?>
            <a title="<?= _('Aus Gruppe austragen') ?>" class="modal" href="<?= $controller->url_for("show/delete/{$group->id}/{$user->user_id}") ?>">
                <?= Assets::img("icons/16/blue/trash.png", tooltip2(_('Person aus Gruppe austragen'))) ?>
            </a>
            <? endif; ?>
        </td>
    </tr>
<? endforeach; ?>