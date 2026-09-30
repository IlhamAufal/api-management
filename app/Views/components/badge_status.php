<?php
$status = strtoupper($status ?? 'PENDING');
$styles = [
    'SUCCESS' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
    'RUNNING' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
    'FAILED'  => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
    'WARNING' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
    'PENDING' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
];
$dotStyles = [
    'SUCCESS' => 'bg-success-500',
    'RUNNING' => 'bg-warning-500 animate-pulse',
    'FAILED'  => 'bg-error-500',
    'WARNING' => 'bg-warning-500',
    'PENDING' => 'bg-gray-400',
];
?>
<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?= $styles[$status] ?? $styles['PENDING'] ?>">
  <span class="h-1.5 w-1.5 rounded-full <?= $dotStyles[$status] ?? $dotStyles['PENDING'] ?>"></span>
  <?= esc($status) ?>
</span>
