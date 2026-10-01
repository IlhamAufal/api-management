<?php
$icon = $icon ?? 'database';
$statusColor = $statusColor ?? 'brand';
$iconClasses = [
    'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
    'success' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400',
    'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400',
];
?>
<div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
  <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl <?= $iconClasses[$statusColor] ?? $iconClasses['brand'] ?>">
    <?php if ($icon === 'check'): ?>
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <?php elseif ($icon === 'clock'): ?>
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <?php else: ?>
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3C7.58 3 4 4.34 4 6v12c0 1.66 3.58 3 8 3s8-1.34 8-3V6c0-1.66-3.58-3-8-3Zm0 4.5c-3.93 0-6.5-1.13-6.5-1.5S8.07 4.5 12 4.5 18.5 5.63 18.5 6 15.93 7.5 12 7.5Zm0 8c-3.93 0-6.5-1.13-6.5-1.5v-2.09C7 12.61 9.36 13 12 13s5-.39 6.5-1.09V14c0 .37-2.57 1.5-6.5 1.5Zm0 4c-3.93 0-6.5-1.13-6.5-1.5v-2.09C7 16.61 9.36 17 12 17s5-.39 6.5-1.09V18c0 .37-2.57 1.5-6.5 1.5Z" fill="currentColor"/></svg>
    <?php endif; ?>
  </span>
  <div class="min-w-0">
    <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><?= esc($title ?? '') ?></p>
    <p class="mt-0.5 text-xl font-bold text-gray-800 dark:text-white/90"><?= esc($value ?? '—') ?></p>
    <p class="mt-0.5 text-xs text-gray-400"><?= esc($subText ?? '') ?></p>
  </div>
</div>
