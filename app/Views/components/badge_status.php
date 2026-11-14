<?php
$status = strtoupper($status ?? 'PENDING');
$styles = [
    'OK'             => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
    'STALE'          => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
    'NEVER_SYNCED'   => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
    'MISSING_TABLE'  => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
    'CONN_ERROR'     => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
    'PENDING_CONFIG' => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
    'PENDING'        => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
];
?>
<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $styles[$status] ?? $styles['PENDING'] ?>">
  <?= esc($status) ?>
</span>
