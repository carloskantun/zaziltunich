<?php
$lang = \App\Core\I18n::lang();
$groups = $exp['extra_groups'];
$optLabel = static fn (array $o) => ($lang === 'en' && $o['label_en'] !== '') ? $o['label_en'] : $o['label_es'];
$gName = static fn (array $g) => ($lang === 'en' && $g['name_en'] !== '') ? $g['name_en'] : $g['name_es'];
$gHelp = static fn (array $g) => ($lang === 'en' && $g['help_en'] !== '') ? $g['help_en'] : $g['help_es'];
$cfg = [
  'id' => $exp['id'], 'kind' => $exp['kind'], 'model' => $exp['pricing_model'],
  'minPax' => $exp['min_pax'], 'maxPax' => $exp['max_pax'],
  'api' => ['month' => raw_url('api/month'), 'slots' => raw_url('api/slots'), 'quote' => raw_url('api/quote')],
  't' => [
    'days' => explode(',', t('cal.days')), 'prev' => t('cal.prev'), 'next' => t('cal.next'), 'left' => t('book.left'),
    'full' => t('book.full'), 'pickDate' => t('book.pick_date'), 'pickCheckin' => t('book.pick_checkin'),
    'needDate' => t('book.need_date'), 'nights' => t('book.nights'), 'checkout' => t('book.checkout'),
  ],
  'lang' => $lang,
];
?>
    <form method="post" action="<?= e($formAction) ?>" id="zt-form" autocomplete="off" data-config="<?= e(json_encode($cfg, JSON_UNESCAPED_UNICODE)) ?>">
      <input type="hidden" name="exp" value="<?= (int) $exp['id'] ?>">
      <?php if ($adminMode): ?><?= csrf_field() ?><?php endif; ?>
      <input type="hidden" name="date" id="f-date"><input type="hidden" name="end_date" id="f-end"><input type="hidden" name="time" id="f-time">
      <div id="zt-cal" class="cal"></div>
      <p class="hint" id="zt-hint"><?= e($exp['kind'] === 'lodging' ? t('book.pick_checkin') : t('book.pick_date')) ?></p>
      <div id="zt-slots" class="slots"></div>

      <?php if ($exp['pricing_model'] === 'variant_fixed'): ?>
      <label class="fld"><span><?= e(t('book.variant')) ?></span>
        <select name="variant_id" data-live>
          <?php foreach ($exp['variants'] as $v): ?><option value="<?= (int) $v['id'] ?>"><?= e(($lang === 'en' && $v['name_en'] !== '') ? $v['name_en'] : $v['name_es']) ?> — <?= e(money($v['price'], $exp['currency'])) ?></option><?php endforeach; ?>
        </select></label>
      <?php endif; ?>

      <?php if ($exp['max_pax'] > 1 || $exp['min_pax'] > 1): ?>
      <label class="fld"><span><?= e(t('book.people')) ?></span>
        <div class="stepper"><button type="button" data-step="-1">−</button><input type="number" name="pax" id="f-pax" value="<?= (int) $exp['min_pax'] ?>" min="<?= (int) $exp['min_pax'] ?>" max="<?= (int) $exp['max_pax'] ?>" data-live><button type="button" data-step="1">+</button></div>
      </label>
      <?php else: ?><input type="hidden" name="pax" id="f-pax" value="1"><?php endif; ?>

      <?php foreach ($groups as $g): $gid = (int) $g['id']; ?>
      <fieldset class="extra" data-group="<?= $gid ?>" data-mode="<?= e($g['mode']) ?>">
        <legend><?= e($gName($g)) ?><?= $g['required'] ? ' *' : '' ?></legend>
        <?php if ($gHelp($g)): ?><small><?= e($gHelp($g)) ?></small><?php endif; ?>
        <?php if ($g['mode'] === 'auto'): ?>
          <?php if ($g['required']): ?><input type="hidden" name="extras[<?= $gid ?>]" value="1" data-live>
          <?php else: ?><label class="chk"><input type="checkbox" name="extras[<?= $gid ?>]" value="1" data-live> <?= e(t('book.include_it')) ?></label><?php endif; ?>
        <?php elseif ($g['selection'] === 'list'): ?>
          <select name="extras[<?= $gid ?>]" data-live>
            <?php if (!$g['required']): ?><option value="0"><?= e(t('book.choose')) ?></option><?php else: ?><option value="0"><?= e(t('book.choose')) ?></option><?php endif; ?>
            <?php foreach ($g['options'] as $o): ?><option value="<?= (int) $o['id'] ?>" data-min="<?= e($o['pax_min'] ?? '') ?>" data-max="<?= e($o['pax_max'] ?? '') ?>"><?= e($optLabel($o)) ?><?= $o['amount'] > 0 ? ' (+' . e(money($o['amount'], $exp['currency'])) . ')' : '' ?></option><?php endforeach; ?>
          </select>
        <?php elseif ($g['selection'] === 'checkbox'): foreach ($g['options'] as $o): ?>
          <label class="chk" data-min="<?= e($o['pax_min'] ?? '') ?>" data-max="<?= e($o['pax_max'] ?? '') ?>"><input type="checkbox" name="extras[<?= $gid ?>][]" value="<?= (int) $o['id'] ?>" data-live> <?= e($optLabel($o)) ?> (+<?= e(money($o['amount'], $exp['currency'])) ?>)</label>
        <?php endforeach; else: foreach ($g['options'] as $o): ?>
          <label class="qty" data-min="<?= e($o['pax_min'] ?? '') ?>" data-max="<?= e($o['pax_max'] ?? '') ?>"><span><?= e($optLabel($o)) ?> (+<?= e(money($o['amount'], $exp['currency'])) ?>)</span>
            <input type="number" name="extras[<?= $gid ?>][<?= (int) $o['id'] ?>]" value="0" min="0" max="<?= (int) ($o['max_qty'] ?? 50) ?>" data-live></label>
        <?php endforeach; endif; ?>
      </fieldset>
      <?php endforeach; ?>

      <div class="totals" id="zt-totals" hidden>
        <div id="zt-lines"></div>
        <p class="total"><span><?= e(t('book.cost')) ?></span> <strong id="zt-total"></strong></p>
        <p class="dep" id="zt-dep" hidden><span><?= e(t('book.deposit_now')) ?></span> <strong id="zt-deposit"></strong><br><small><?= e(t('book.balance')) ?> <span id="zt-balance"></span></small></p>
        <p class="err" id="zt-err"></p>
      </div>
      <?php if ($adminMode): ?><?= $adminExtra ?? '' ?><?php endif; ?>
      <button class="btn btn-lg" id="zt-submit" disabled><?= e($adminMode ? 'Crear reserva' : t('book.submit')) ?></button>
    </form>
