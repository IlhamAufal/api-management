<?php
$status = strtoupper($status ?? 'PENDING');
$styles = [
    'OK'            => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
    'STALE'         => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
    'NEVER_SYNCED'  => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
    'MISSING_TABLE' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
    'CONN_ERROR'    => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
    'PENDING'       => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
];
$dotStyles = [
    'OK'            => 'bg-success-500',
    'STALE'         => 'bg-warning-500',
    'NEVER_SYNCED'  => 'bg-gray-400',
    'MISSING_TABLE' => 'bg-error-400',
    'CONN_ERROR'    => 'bg-error-500 animate-pulse',
    'PENDING'       => 'bg-gray-400',
];
?>
<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?= $styles[$status] ?? $styles['PENDING'] ?>">
  <span class="h-1.5 w-1.5 rounded-full <?= $dotStyles[$status] ?? $dotStyles['PENDING'] ?>"></span>
  <?= esc($status) ?>
</span>
