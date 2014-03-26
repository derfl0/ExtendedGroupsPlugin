<form method="post" class="studip_form termine">
    <?= CSRFProtection::tokenTag(); ?>
    <input type="hidden" name="group" value="<?= $group ?>">
    <? foreach ($termine as $termin): ?>
        <input value="0" type="hidden" name="termine[<?= $termin['termin_id'] ?>]" <?= $termin['checked'] ?>>
        <label>
            <input value="1" type="checkbox" name="termine[<?= $termin['termin_id'] ?>]" <?= $termin['checked'] ?>>
            <?= $termin['display'] ?>
        </label>
    <? endforeach; ?>
    <?= Studip\Button::create(_('Speichern')); ?>
</form>
