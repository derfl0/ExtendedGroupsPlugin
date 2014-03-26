<? foreach ($termine as $termin): ?>
    <? if ($termin['checked']): ?>
        <p>
            <?= $termin['display'] ?>
        </p>
    <?
    endif;
endforeach;
