<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Monitoring', 'href' => base_url('monitoring')],
      ['label' => 'Alur Workflow'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">Alur Workflow</h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
        Alur data-freshness per source: source &rarr; checker &rarr; watched table &rarr; dashboard monitoring. Pilih tab untuk melihat alur satu source.
      </p>
    </div>
    <a href="<?= base_url('monitoring') ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-brand-300 hover:text-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:border-brand-700 dark:hover:text-brand-400">
      <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
      Kembali ke Monitoring
    </a>
  </div>

  <?php if (empty($sources)): ?>
    <section class="rounded-2xl border border-gray-200 bg-white p-16 text-center dark:border-gray-800 dark:bg-white/[0.03]">
      <i class="fa-solid fa-diagram-project text-2xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
      <p class="mt-3 font-medium text-gray-700 dark:text-gray-200">Belum ada data untuk ditampilkan</p>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Daftarkan watched table di halaman Watched Tables, lalu aktifkan minimal satu source.
      </p>
      <a href="<?= base_url('watched-tables') ?>" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600">
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
        Kelola Watched Tables
      </a>
    </section>
  <?php else: ?>
    <!-- Tab bar: satu tab per source aktif; tiap tab adalah URL nyata. -->
    <nav class="seg-tabs" aria-label="Pilih source">
      <?php foreach ($sources as $tab): ?>
        <?php $isActive = $activeSource !== null && (int) $tab['id'] === (int) $activeSource['id']; ?>
        <a
          href="<?= base_url('monitoring/workflow/source/' . $tab['code']) ?>"
          class="seg-tabs__item<?= $isActive ? ' is-active' : '' ?>"
          <?= $isActive ? 'aria-current="page"' : '' ?>
        >
          <?= esc($tab['label']) ?>
          <!-- <code class="seg-tabs__code"><?= esc($tab['code']) ?></code> -->
        </a>
      <?php endforeach; ?>
    </nav>

    <?= $this->include('components/workflow_canvas', ['workflow' => $workflow]) ?>
  <?php endif; ?>

  <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
    Status dihitung dari umur kolom sync dibanding ambang stale per tabel. Data berasal dari snapshot terakhir; jalankan "Check All" di halaman Monitoring untuk memperbaruinya.
  </p>
</div>
<?= $this->endSection() ?>
