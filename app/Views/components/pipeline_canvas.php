<?php
$pipelineStatus = strtoupper($pipelineStatus ?? 'WARNING');
$colors = [
    'SUCCESS' => ['line' => 'bg-success-500', 'node' => 'border-success-200 bg-success-50 text-success-700 dark:border-success-500/30 dark:bg-success-500/15 dark:text-success-300'],
    'FAILED'  => ['line' => 'bg-error-500', 'node' => 'border-error-200 bg-error-50 text-error-700 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-300'],
    'WARNING' => ['line' => 'bg-warning-500', 'node' => 'border-warning-200 bg-warning-50 text-warning-700 dark:border-warning-500/30 dark:bg-warning-500/15 dark:text-warning-300'],
];
$color = $colors[$pipelineStatus] ?? $colors['WARNING'];
$nodes = ['SAP Cloud', 'CI4 Worker', 'AWS RDS', 'MD-Bridge'];
?>
<section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
  <div class="mb-6 flex items-center justify-between gap-4">
    <div>
      <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Sync Pipeline</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">SAP master data delivery flow</p>
    </div>
    <?= view('components/badge_status', ['status' => $pipelineStatus]) ?>
  </div>
  <div class="flex flex-col items-stretch gap-3 md:flex-row md:items-center md:gap-0">
    <?php foreach ($nodes as $index => $node): ?>
      <div class="flex flex-1 flex-col items-center gap-3 md:flex-row">
        <div class="w-full rounded-xl border px-4 py-3 text-center text-sm font-semibold <?= $color['node'] ?>"><?= esc($node) ?></div>
        <?php if ($index < count($nodes) - 1): ?>
          <div class="h-7 w-1 rounded-full <?= $color['line'] ?> md:h-1 md:w-10 lg:w-16"></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
