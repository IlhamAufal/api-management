<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 sm:p-6 lg:p-8">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Monitoring'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">Data Freshness Monitor</h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-400">Pilih source untuk memantau umur data tiap tabel dan kepatuhan ambang batas stale</p>
    </div>
    <div class="flex shrink-0 flex-wrap items-center gap-3">
      <?php if (! empty($sources) && ($isAll || $activeSource !== null)): ?>
      <button type="submit" form="monitoring-check-form" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-brand-500">
        <i class="fa-solid fa-database text-xs" aria-hidden="true"></i>
        Cek Database
      </button>
      <?php endif; ?>
      <a href="<?= base_url('monitoring/workflow') ?>" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800">
        <i class="fa-solid fa-diagram-project text-xs" aria-hidden="true"></i>
        Alur Workflow
      </a>
    </div>
  </div>

  <?php if (empty($sources)): ?>
    <section class="mt-8 rounded-2xl border border-gray-200 bg-white px-5 py-16 text-center dark:border-gray-800 dark:bg-white/[0.03]">
      <i class="fa-solid fa-plug-circle-xmark text-2xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
      <p class="mt-3 font-medium text-gray-700 dark:text-gray-200">Belum ada source aktif</p>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Aktifkan minimal satu source di Config/Database.php + .env.</p>
    </section>
  <?php else: ?>
    <!-- Tab bar: satu tab per source aktif; tiap tab adalah URL nyata. -->
    <nav class="seg-tabs" aria-label="Pilih source">
      <a
        href="<?= base_url('monitoring/all') ?>"
        class="seg-tabs__item<?= $isAll ? ' is-active' : '' ?>"
        <?= $isAll ? 'aria-current="page"' : '' ?>
      >ALL</a>
      <?php foreach ($sources as $tab): ?>
        <?php $isActive = ! $isAll && $activeSource !== null && (int) $tab['id'] === (int) $activeSource['id']; ?>
        <a
          href="<?= base_url('monitoring/source/' . $tab['code']) ?>"
          class="seg-tabs__item<?= $isActive ? ' is-active' : '' ?>"
          <?= $isActive ? 'aria-current="page"' : '' ?>
        >
          <?= esc($tab['label']) ?>
          <!-- <code class="seg-tabs__code"><?= esc($tab['code']) ?></code> -->
        </a>
      <?php endforeach; ?>
    </nav>

    <?php if ($isAll || $activeSource !== null): ?>
      <?php $hasFilter = $filters['q'] !== '' || $filters['status'] !== '' || $filters['source'] !== ''; ?>
      <!-- Filter async: GET ke URL tab ini, hasil diganti tanpa reload (lihat script di bawah). -->
      <form id="monitoring-filter" method="get" action="<?= esc($filterAction, 'attr') ?>" class="mt-6 flex flex-col gap-3 rounded-xl border border-gray-200/80 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900/60 sm:flex-row sm:flex-wrap sm:items-end">
        <label class="flex min-w-[220px] flex-1 flex-col gap-1 text-xs font-medium text-gray-600 dark:text-gray-400">
          Cari tabel
          <input type="search" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Label atau nama tabel" class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
        </label>
        <?= view('components/custom_select', ['cs' => [
            'name'        => 'status',
            'id'          => 'status',
            'label'       => 'Status',
            'value'       => $filters['status'],
            'options'     => $statusOptions,
            'placeholder' => 'Semua status',
            'size'        => 'sm',
            'class'       => 'w-48',
            'attributes'  => 'aria-label="Filter status"',
        ]]) ?>
        <?php if ($isAll): ?>
          <?php
          $sourceChoices = [];
          foreach ($sources as $opt) {
              $sourceChoices[$opt['code']] = $opt['label'];
          }
          ?>
          <?= view('components/custom_select', ['cs' => [
              'name'        => 'source',
              'id'          => 'source',
              'label'       => 'Source',
              'value'       => $filters['source'],
              'options'     => $sourceChoices,
              'placeholder' => 'Semua source',
              'size'        => 'sm',
              'class'       => 'w-48',
              'attributes'  => 'aria-label="Filter source"',
          ]]) ?>
        <?php endif; ?>
        <div class="flex items-center gap-2 sm:ml-auto">
          <button type="button" id="monitoring-filter-reset" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"<?= $hasFilter ? '' : ' hidden' ?>>Reset</button>
        </div>      </form>

      <!-- Form check (POST) untuk tombol "Cek database" di bar filter. -->
      <form id="monitoring-check-form" method="post" action="<?= $isAll ? base_url('monitoring/check-all') : base_url('monitoring/check-source/' . $activeSource['code']) ?>" hidden>
        <?= csrf_field() ?>
      </form>

      <div id="monitoring-results">
      <p class="mt-4 text-xs text-gray-500 dark:text-gray-400"<?= $hasFilter ? '' : ' hidden' ?>>Menampilkan <?= count($rows) ?> dari <?= (int) $totalRows ?> baris.</p>
      <section class="mt-4 overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900/60">
        <div class="overflow-x-auto">
          <?php if (empty($rows)): ?>
            <div class="px-5 py-16 text-center">
              <i class="fa-solid fa-table-cells-large text-2xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
              <p class="mt-3 font-medium text-gray-700 dark:text-gray-200"><?= $hasFilter ? 'Tidak ada baris yang cocok dengan filter' : 'Belum ada watched table aktif' ?></p>
              <p class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= $hasFilter ? 'Ubah atau reset filter.' : 'Daftarkan tabel di halaman Watched Tables.' ?></p>
            </div>
          <?php else: ?>
            <table class="w-full min-w-[720px] text-left">
              <thead class="bg-brand-500 text-xs font-semibold uppercase tracking-wider text-white">
                <tr>
                  <th class="px-5 py-3">Tabel</th>
                  <?php if ($isAll): ?><th class="px-5 py-3">Source</th><?php endif; ?>
                  <th class="px-5 py-3">Status</th>
                  <th class="px-5 py-3">Umur Data</th>
                  <th class="px-5 py-3">Keterangan</th>
                  <th class="px-5 py-3 text-right">Baris</th>
                  <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800/80">
                <?php foreach ($rows as $row): ?>
                  <?php $table = $row['table']; $snapshot = $row['snapshot']; $rowSource = $row['source']; ?>
                  <tr class="transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-800/40">
                    <td class="px-5 py-3.5">
                      <p class="text-sm font-medium text-gray-900 dark:text-gray-100"><?= esc($table['label']) ?></p>
                      <code class="mt-0.5 block font-mono text-xs text-gray-500 dark:text-gray-400"><?= esc($table['table_name']) ?></code>
                      <code class="block font-mono text-[10px] text-gray-400">sync: <?= esc($table['sync_column']) ?> · stale &gt; <?= (int) $table['stale_after_minutes'] ?>m</code>
                    </td>
                    <?php if ($isAll): ?>
                      <td class="px-5 py-3.5">
                        <a href="<?= base_url('monitoring/source/' . $rowSource['code']) ?>" class="text-sm font-medium text-gray-700 hover:underline dark:text-gray-200"><?= esc($rowSource['label']) ?></a>
                        <code class="block font-mono text-[10px] text-gray-400"><?= esc($rowSource['code']) ?></code>
                      </td>
                    <?php endif; ?>
                    <td class="px-5 py-3.5">
                      <?= view('components/badge_status', ['status' => $row['status']]) ?>
                      <?php if (! empty($snapshot['error_message'])): ?>
                        <span class="mt-1 block max-w-[260px] truncate text-[10px] text-error-500" title="<?= esc($snapshot['error_message'], 'attr') ?>"><?= esc($snapshot['error_message']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                      <?php if ($snapshot === null): ?>
                        <span class="text-gray-400">belum dicek</span>
                      <?php else: ?>
                        <span>data terakhir: <?= esc($relative($snapshot['last_synced_at'])) ?></span>
                        <?php if ($snapshot['last_synced_at'] !== null): ?>
                          <span class="mt-0.5 block font-mono text-[10px] tabular-nums text-gray-400"><?= esc($snapshot['last_synced_at']) ?></span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                      <?php if ($snapshot === null): ?>
                        <span class="text-gray-400">—</span>
                      <?php else: ?>
                        <span>dicek <?= esc($relative($snapshot['checked_at'])) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-right font-mono text-xs tabular-nums text-gray-600 dark:text-gray-300">
                      <?= ($snapshot !== null && $snapshot['row_count'] !== null) ? number_format((int) $snapshot['row_count']) : '—' ?>
                    </td>
                    <td class="px-5 py-3.5">
                      <div class="flex items-center justify-end gap-1.5">
                        <a href="<?= base_url('monitoring/history/' . (int) $table['id'] . '/' . (int) $rowSource['id']) ?>" title="Lihat riwayat check" aria-label="Lihat riwayat check <?= esc($table['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                          <i class="fa-solid fa-clock-rotate-left text-xs" aria-hidden="true"></i>
                        </a>
                        <form method="post" action="<?= base_url('monitoring/check-cell/' . (int) $table['id'] . '/' . (int) $rowSource['id']) ?>">
                          <?= csrf_field() ?>
                          <button type="submit" title="Check sel ini" aria-label="Check <?= esc($table['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                            <i class="fa-solid fa-rotate text-xs" aria-hidden="true"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </section>
      </div>

      <script>
      (function () {
        var form = document.getElementById('monitoring-filter');
        var resetBtn = document.getElementById('monitoring-filter-reset');
        if (!form || !window.fetch) return;
        var controller = null;
        var timer = null;

        function params() {
          var p = new URLSearchParams();
          new FormData(form).forEach(function (v, k) {
            if (v !== '' && k !== 'csrf_test_name') p.append(k, v);
          });
          return p;
        }

        function load() {
          var qs = params().toString();
          var url = form.getAttribute('action') + (qs ? '?' + qs : '');
          var box = document.getElementById('monitoring-results');
          if (controller) controller.abort();
          controller = new AbortController();
          box.style.opacity = '0.5';
          resetBtn.hidden = qs === '';

          fetch(url, { signal: controller.signal, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
            .then(function (html) {
              var doc = new DOMParser().parseFromString(html, 'text/html');
              var fresh = doc.getElementById('monitoring-results');
              if (!fresh) throw new Error('no results');
              box.innerHTML = fresh.innerHTML;
              box.style.opacity = '';
              history.replaceState(null, '', url);
            })
            .catch(function (e) {
              if (e.name === 'AbortError') return;
              window.location.href = url; // fallback: muat penuh
            });
        }

        form.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(timer); load(); });
        form.addEventListener('input', function (e) {
          if (e.target.name === 'q') { clearTimeout(timer); timer = setTimeout(load, 300); }
        });
        form.addEventListener('change', function (e) {
          if (e.target.tagName === 'SELECT') load();
        });
        resetBtn.addEventListener('click', function () {
          form.querySelector('[name=q]').value = '';
          form.querySelectorAll('select').forEach(function (s) {
            if (s.value !== '') { s.value = ''; s.dispatchEvent(new Event('change', { bubbles: true })); }
          });
          clearTimeout(timer);
          load();
        });
      })();
      </script>
    <?php endif; ?>
  <?php endif; ?>

  <?php if (! empty($hiddenCount)): ?>
    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
      <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
      <?= (int) $hiddenCount ?> tabel disembunyikan karena tidak ada di <?= $isAll ? 'source terkait' : 'source ini' ?> (status MISSING_TABLE).
    </p>
  <?php endif; ?>

  <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
    Trigger check manual; "STALE" berarti "terakhir dicek, datanya sudah lewat ambang". Urut dari status terburuk lebih dulu.
  </p>
</div>
<?= $this->endSection() ?>
