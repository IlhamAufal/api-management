<?php
$icon = $icon ?? 'database';
$statusColor = $statusColor ?? 'brand';
$iconClasses = [
    'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
    'success' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400',
    'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400',
];
?>
<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
  <div class="flex h-11 w-11 items-center justify-center rounded-xl <?= $iconClasses[$statusColor] ?? $iconClasses['brand'] ?>">
    <?php if ($icon === 'check'): ?>
      <span class="text-lg font-bold">✓</span>
    <?php elseif ($icon === 'clock'): ?>
      <span class="text-lg">◷</span>
    <?php else: ?>
      <span class="text-lg">▦</span>
    <?php endif; ?>
  </div>
  <div class="mt-5">
    <p class="text-sm text-gray-500 dark:text-gray-400"><?= esc($title ?? '') ?></p>
    <h3 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90"><?= esc($value ?? '—') ?></h3>
    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500"><?= esc($subText ?? '') ?></p>
  </div>
</div>
