<?php
/**
 * Reusable breadcrumb navigation (TailAdmin style).
 *
 * Expected variable:
 *   $items = [ ['label' => 'Home', 'href' => base_url('/')], ['label' => 'Current Page'] ]
 * The last item is rendered as the current page (no link).
 */
$breadcrumbItems = $items ?? [];
$breadcrumbLast = count($breadcrumbItems) - 1;
?>
<nav aria-label="Breadcrumb" class="mb-4">
  <ol class="flex flex-wrap items-center gap-1.5 text-sm">
    <?php foreach ($breadcrumbItems as $index => $item): ?>
      <?php if ($index < $breadcrumbLast): ?>
        <li class="flex items-center gap-1.5">
          <a href="<?= esc($item['href'] ?? base_url('/')) ?>" class="font-medium text-gray-500 transition hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400"><?= esc($item['label']) ?></a>
          <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">/</span>
        </li>
      <?php else: ?>
        <li class="font-medium text-gray-800 dark:text-white/90" aria-current="page"><?= esc($item['label']) ?></li>
      <?php endif; ?>
    <?php endforeach; ?>
  </ol>
</nav>
