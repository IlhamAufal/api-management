<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$statusMap = [
    1 => ['label' => 'Active', 'class' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400', 'dot' => 'bg-success-500'],
    0 => ['label' => 'Inactive', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300', 'dot' => 'bg-gray-400'],
];

// Daftar source unik (code => label) dari baris yang tampil, untuk opsi filter.
$sourceOptions = [];
foreach ($tables as $row) {
    if (! empty($row['source_code'])) {
        $sourceOptions[(string) $row['source_code']] = (string) $row['source_label'];
    }
}
asort($sourceOptions);
// Apakah ada baris tanpa source (tidak ditemukan di source aktif manapun).
$hasUnmatched = false;
foreach ($tables as $row) {
    if (empty($row['source_code'])) {
        $hasUnmatched = true;
        break;
    }
}

// Opsi untuk komponen custom_select (struktur [['value','label'], ...]).
$sourceSelectOptions = [];
foreach ($sourceOptions as $code => $label) {
    $sourceSelectOptions[] = ['value' => $code, 'label' => $label . ' (' . $code . ')'];
}
if ($hasUnmatched) {
    $sourceSelectOptions[] = ['value' => '__none__', 'label' => 'Tidak ditemukan'];
}
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Watched Tables'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">Watched Tables</h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Daftar tabel yang dipantau freshness-nya di semua source. Tambah tabel baru tanpa menyentuh kode.</p>
    </div>
    <a href="<?= base_url('watched-tables/new') ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600">
      <i class="fa-solid fa-plus" aria-hidden="true"></i>
      Tambah Tabel
    </a>
  </div>

  <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between" data-wt-filterbar>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
      <?= view('components/custom_select', ['cs' => [
          'name'        => 'wt-filter-source',
          'id'          => 'wt-filter-source',
          'value'       => '',
          'options'     => $sourceSelectOptions,
          'placeholder' => 'Semua source',
          'label'       => 'Source',
          'size'        => 'sm',
          'class'       => 'w-full sm:w-56',
          'attributes'  => 'aria-label="Filter source"',
      ]]) ?>
      <div class="cs-field w-full sm:w-64">
        <label for="wt-filter-search" class="cs-field__label">Cari tabel</label>
        <div class="cs-field__control">
          <i class="fa-solid fa-magnifying-glass cs-field__icon" aria-hidden="true"></i>
          <input id="wt-filter-search" type="text" placeholder="Nama tabel atau label…" class="cs-field__input" autocomplete="off" />
          <button type="button" id="wt-filter-search-clear" class="cs-field__clear" aria-label="Bersihkan pencarian" hidden>
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
          </button>
        </div>
      </div>
    </div>
    <div class="flex items-center gap-3 pb-0.5">
      <span id="wt-filter-count" class="inline-block select-text text-xs text-gray-500 dark:text-gray-400"></span>
      <button type="button" id="wt-filter-reset" class="inline-flex select-none items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:border-brand-700 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
        <i class="fa-solid fa-rotate-left text-[10px]" aria-hidden="true"></i>
        Reset
      </button>
    </div>
  </div>

  <section class="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[950px] text-left">
        <thead class="select-none bg-brand-500 text-white font-poppins">
          <tr>
            <th class="px-5 py-3.5 text-sm font-semibold">Tabel</th>
            <th class="px-5 py-3.5 text-sm font-semibold">Source</th>
            <th class="px-5 py-3.5 text-sm font-semibold">Label</th>
            <th class="px-5 py-3.5 text-sm font-semibold">Sync Column</th>
            <th class="px-5 py-3.5 text-right text-sm font-semibold">Stale After</th>
            <th class="px-5 py-3.5 text-sm font-semibold">Status</th>
            <th class="px-5 py-3.5 text-right text-sm font-semibold">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          <?php if (empty($tables)): ?>
            <tr>
              <td colspan="7" class="px-5 py-12 text-center">
                <i class="fa-solid fa-table-list text-2xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                <p class="mt-3 font-medium text-gray-700 dark:text-gray-200">Belum ada watched table</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Daftarkan tabel pertama dengan tombol "Tambah Tabel".</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($tables as $table): ?>
              <?php $isActive = (int) $table['is_active'] === 1; ?>
              <?php $status = $statusMap[(int) $table['is_active']] ?? $statusMap[0]; ?>
              <tr
                class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]"
                data-wt-row
                data-source="<?= esc(! empty($table['source_code']) ? $table['source_code'] : '__none__', 'attr') ?>"
                data-search="<?= esc(strtolower(($table['table_name'] ?? '') . ' ' . ($table['label'] ?? '')), 'attr') ?>"
              >
                <td class="px-5 py-4"><code class="inline-block select-text rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($table['table_name']) ?></code></td>
                <td class="px-5 py-4">
                  <?php if (! empty($table['source_code'])): ?>
                    <span class="inline-flex select-none items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">
                      <i class="fa-solid fa-database text-[10px]" aria-hidden="true"></i>
                      <?= esc($table['source_label']) ?>
                      <span class="text-brand-400 dark:text-brand-500">(<?= esc($table['source_code']) ?>)</span>
                    </span>
                  <?php else: ?>
                    <span class="inline-flex select-none items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400" title="Tabel tidak ditemukan di source aktif manapun">
                      <i class="fa-solid fa-circle-question text-[10px]" aria-hidden="true"></i>
                      Tidak ditemukan
                    </span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-4 font-semibold text-gray-800 dark:text-white/90"><span class="inline-block select-text"><?= esc($table['label']) ?></span></td>
                <td class="px-5 py-4"><code class="inline-block select-text rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($table['sync_column']) ?></code></td>
                <td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200 select-none"><span class="inline-block select-text"><?= number_format((int) $table['stale_after_minutes']) ?> mnt</span></td>
                <td class="px-5 py-4 select-none"><span class="inline-flex select-none items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?= $status['class'] ?>"><span class="h-1.5 w-1.5 rounded-full <?= $status['dot'] ?>"></span><?= esc($status['label']) ?></span></td>
                <td class="px-5 py-4 select-none">
                  <div class="flex select-none items-center justify-end gap-1.5">
                    <a href="<?= base_url('watched-tables/edit/' . (int) $table['id']) ?>" title="Edit" aria-label="Edit <?= esc($table['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-500 dark:border-gray-700 dark:text-gray-400 dark:hover:border-brand-700 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                      <i class="fa-solid fa-pen-to-square text-xs" aria-hidden="true"></i>
                    </a>
                    <form method="post" action="<?= base_url('watched-tables/toggle/' . (int) $table['id']) ?>" onsubmit="return confirm('<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?> tabel ini?')">
                      <?= csrf_field() ?>
                      <button type="submit" title="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>" aria-label="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?> <?= esc($table['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-500 dark:border-gray-700 dark:text-gray-400 dark:hover:border-brand-700 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                        <i class="fa-solid <?= $isActive ? 'fa-toggle-on text-success-600' : 'fa-toggle-off' ?> text-xs" aria-hidden="true"></i>
                      </button>
                    </form>
                    <form method="post" action="<?= base_url('watched-tables/delete/' . (int) $table['id']) ?>" onsubmit="return confirm('Nonaktifkan (soft delete) &quot;<?= esc($table['label'], 'attr') ?>&quot;? Riwayat check tetap tersimpan.')">
                      <?= csrf_field() ?>
                      <button type="submit" title="Hapus (soft)" aria-label="Hapus <?= esc($table['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-error-600 transition hover:border-error-300 hover:bg-error-50 dark:border-gray-700 dark:text-error-400 dark:hover:border-error-700 dark:hover:bg-error-500/10">
                        <i class="fa-solid fa-trash-can text-xs" aria-hidden="true"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <tr id="wt-no-results" class="hidden">
              <td colspan="7" class="px-5 py-12 text-center">
                <i class="fa-solid fa-filter-circle-xmark text-2xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                <p class="mt-3 font-medium text-gray-700 dark:text-gray-200">Tidak ada tabel yang cocok</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Coba ubah filter source atau kata kunci pencarian.</p>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<script>
  (function () {
    var sourceSel = document.getElementById('wt-filter-source');
    var searchInp = document.getElementById('wt-filter-search');
    var searchClear = document.getElementById('wt-filter-search-clear');
    var resetBtn  = document.getElementById('wt-filter-reset');
    var countEl   = document.getElementById('wt-filter-count');
    var noResults = document.getElementById('wt-no-results');
    if (!sourceSel) return; // tabel kosong: filter bar tetap ada tapi tanpa baris

    var rows = Array.prototype.slice.call(document.querySelectorAll('[data-wt-row]'));

    function apply() {
      var src = sourceSel.value;
      var q = (searchInp.value || '').trim().toLowerCase();
      var visible = 0;

      if (searchClear) searchClear.hidden = searchInp.value === '';

      rows.forEach(function (row) {
        var matchSource = src === '' || row.getAttribute('data-source') === src;
        var matchSearch = q === '' || (row.getAttribute('data-search') || '').indexOf(q) !== -1;
        var show = matchSource && matchSearch;
        row.classList.toggle('hidden', !show);
        if (show) visible++;
      });

      if (noResults) noResults.classList.toggle('hidden', visible !== 0);
      if (countEl) {
        countEl.textContent = visible === rows.length
          ? rows.length + ' tabel'
          : visible + ' dari ' + rows.length + ' tabel';
      }
    }

    sourceSel.addEventListener('change', apply);
    searchInp.addEventListener('input', apply);
    if (searchClear) {
      searchClear.addEventListener('click', function () {
        searchInp.value = '';
        searchInp.focus();
        apply();
      });
    }
    resetBtn.addEventListener('click', function () {
      sourceSel.value = '';
      // Beri tahu custom_select (Alpine) agar label trigger ikut ter-reset.
      sourceSel.dispatchEvent(new Event('change', { bubbles: true }));
      searchInp.value = '';
      apply();
    });

    apply();
  })();
</script>
<?= $this->endSection() ?>
