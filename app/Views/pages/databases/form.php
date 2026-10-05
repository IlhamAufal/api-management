<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$value = static function (string $field, string $default = '') use ($source, $oldInput) {
    if ($oldInput !== [] && array_key_exists($field, $oldInput)) {
        return (string) $oldInput[$field];
    }

    return (string) ($source[$field] ?? $default);
};
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6" x-data="{ showTutorial: false }">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Databases', 'href' => base_url('databases')],
      ['label' => $isEdit ? 'Edit Database' : 'Tambah Database'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-sm font-semibold tracking-wide text-brand-500">MD-Bridge</p>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90"><?= $isEdit ? 'Edit Database' : 'Tambah Database' ?></h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Isi kode database (source) dan labelnya. Kredensial koneksi tidak diisi di form ini.</p>
    </div>
    <div class="flex shrink-0 flex-wrap items-center gap-3">
      <?php if (! empty($tutorial)): ?>
        <button
          type="button"
          class="tutorial-trigger"
          @click="showTutorial = true"
          aria-haspopup="dialog"
          title="Buka tutorial penambahan database"
        >
          <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
          cara tambah database
        </button>
      <?php endif; ?>

      <a href="<?= $cancelUrl ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Kembali
      </a>
    </div>
  </div>

  <?php if (! empty($tutorial)): ?>
    <style>
      .tutorial-md { line-height: 1.65; }
      .tutorial-md h1 { font-size: 1.25rem; font-weight: 700; margin: 1rem 0 .5rem; color: #1e293b; }
      .tutorial-md h2 { font-size: 1.05rem; font-weight: 700; margin: 1.25rem 0 .5rem; color: #1e293b; border-left: 3px solid #6366f1; padding-left: .6rem; }
      .tutorial-md h3 { font-size: .95rem; font-weight: 600; margin: 1rem 0 .4rem; color: #334155; }
      .tutorial-md p { margin: .55rem 0; }
      .tutorial-md ul, .tutorial-md ol { margin: .55rem 0; padding-left: 1.4rem; }
      .tutorial-md ol { list-style: decimal; }
      .tutorial-md ul { list-style: disc; }
      .tutorial-md li { margin: .3rem 0; }
      .tutorial-md li.tutorial-check { list-style: none; margin-left: -1.1rem; display: flex; gap: .45rem; align-items: flex-start; }
      .tutorial-md li.tutorial-check input[type="checkbox"] { width: .95em; height: .95em; margin: 0; accent-color: #64748b; opacity: .7; flex-shrink: 0; cursor: default; }
      .tutorial-md code.tutorial-code { background: #f1f5f9; border-radius: .25rem; padding: .1rem .3rem; font-size: .8em; font-family: ui-monospace, Consolas, monospace; color: #0f172a; }
      .tutorial-md pre.tutorial-pre { background: #0f172a; color: #e2e8f0; border-radius: .75rem; padding: .9rem 1rem; overflow-x: auto; margin: .7rem 0; font-size: .8rem; line-height: 1.5; }
      .tutorial-md pre.tutorial-pre code { font-family: ui-monospace, Consolas, monospace; background: none; color: inherit; }
      .tutorial-md table.tutorial-table { width: 100%; border-collapse: collapse; margin: .7rem 0; font-size: .82rem; display: block; overflow-x: auto; }
      .tutorial-md table.tutorial-table th, .tutorial-md table.tutorial-table td { border: 1px solid #e2e8f0; padding: .45rem .6rem; text-align: left; vertical-align: top; }
      .tutorial-md table.tutorial-table th { background: #f8fafc; font-weight: 600; white-space: nowrap; }
      .tutorial-md blockquote.tutorial-quote { border-left: 3px solid #cbd5e1; background: #f8fafc; margin: .7rem 0; padding: .55rem .8rem; border-radius: 0 .5rem .5rem 0; color: #475569; font-size: .9em; }
      .tutorial-md hr.tutorial-hr { border: 0; border-top: 1px dashed #e2e8f0; margin: 1.1rem 0; }
      .tutorial-md strong { color: #0f172a; }
      .dark .tutorial-md h1, .dark .tutorial-md h2, .dark .tutorial-md h3 { color: #f1f5f9; }
      .dark .tutorial-md code.tutorial-code { background: rgba(255,255,255,.08); color: #e2e8f0; }
      .dark .tutorial-md table.tutorial-table th, .dark .tutorial-md table.tutorial-table td { border-color: #334155; }
      .dark .tutorial-md table.tutorial-table th { background: rgba(255,255,255,.05); }
      .dark .tutorial-md blockquote.tutorial-quote { background: rgba(255,255,255,.04); border-left-color: #475569; color: #94a3b8; }
      .dark .tutorial-md strong { color: #f8fafc; }
      .dark .tutorial-md hr.tutorial-hr { border-top-color: #334155; }
    </style>
  <?php endif; ?>

  <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <form method="post" action="<?= esc($formAction) ?>" class="p-5 md:p-6">
      <?= csrf_field() ?>
      <input type="hidden" name="return_to" value="<?= $isEdit ? 'edit/' . (int) $source['id'] : 'new' ?>" />

      <?php if (! empty($errors)): ?>
        <div class="mb-5 flex items-start gap-2 rounded-xl border border-error-200 bg-error-50 p-4 text-sm font-medium text-error-700 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-300">
          <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
          <div>
            <p class="font-semibold">Periksa kembali input Anda:</p>
            <ul class="mt-1 list-inside list-disc">
              <?php foreach ($errors as $error): ?>
                <li><?= esc($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <div class="mb-5 flex items-start gap-2 rounded-xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-700 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-400">
        <i class="fa-solid fa-key mt-0.5" aria-hidden="true"></i>
        <div>
          <p class="font-semibold">Credential diisi manual oleh developer</p>
          <p class="mt-1">Kode database harus sama dengan nama connection group di <code class="rounded bg-warning-100 px-1.5 py-0.5 text-xs dark:bg-warning-500/20">app/Config/Database.php</code> dan nilai credential di <code class="rounded bg-warning-100 px-1.5 py-0.5 text-xs dark:bg-warning-500/20">.env</code> (<code class="rounded bg-warning-100 px-1.5 py-0.5 text-xs dark:bg-warning-500/20">database.<?= esc($value('code', '<kode>')) ?>.*</code>). Selama group belum ada, database tampil <strong>PENDING_CONFIG</strong> — bukan error. Pakai tombol <strong>Uji Koneksi</strong> untuk memverifikasi sebelum menyimpan.</p>
        </div>
      </div>

      <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        <div>
          <label for="code" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Kode Database <span class="text-error-500">*</span></label>
          <input type="text" id="code" name="code" value="<?= esc($value('code')) ?>" required maxlength="50" placeholder="zona_erp" <?= $isEdit ? 'readonly' : '' ?> pattern="[a-z][a-z0-9_]*" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-transparent dark:text-white/90 <?= $isEdit ? 'cursor-not-allowed bg-gray-50 text-gray-500 dark:bg-white/[0.02] dark:text-gray-400' : '' ?>" />
          <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400"><?= $isEdit ? 'Kode tidak bisa diubah setelah dibuat (dipakai nama connection group).' : 'Huruf kecil, angka, dan underscore — contoh: npd, sap, zona_erp.' ?></p>
        </div>

        <div>
          <label for="label" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Label <span class="text-error-500">*</span></label>
          <input type="text" id="label" name="label" value="<?= esc($value('label')) ?>" required maxlength="100" placeholder="Zona ERP" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-transparent dark:text-white/90" />
        </div>

        <div>
          <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Aktif</span>
          <label for="is_active" class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-transparent dark:text-white/90">
            <input type="checkbox" id="is_active" name="is_active" value="1" <?= $value('is_active', '1') === '1' ? 'checked' : '' ?> class="peer sr-only" />
            <span class="toggle-track" aria-hidden="true"></span>
            Ikut check berikutnya
            <span class="toggle-state" data-on="Aktif" data-off="Nonaktif" aria-hidden="true"></span>
          </label>
          <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Nonaktifkan untuk mengecualikan database; riwayat tetap tersimpan.</p>
        </div>
      </div>

      <div class="mt-6 flex flex-wrap items-center gap-3">
        <button type="submit" formaction="<?= base_url('databases/test') ?>" formmethod="post" title="Uji koneksi sesuai kode yang diisi — tanpa menyimpan" class="inline-flex items-center justify-center gap-2 rounded-lg border border-warning-300 bg-warning-50 px-5 py-2.5 text-sm font-semibold text-warning-700 transition hover:bg-warning-100 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-400 dark:hover:bg-warning-500/20">
          <i class="fa-solid fa-plug" aria-hidden="true"></i>
          Uji Koneksi
        </button>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600">
          <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
          <?= $isEdit ? 'Simpan Perubahan' : 'Daftarkan Database' ?>
        </button>
        <a href="<?= $cancelUrl ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]">Batal</a>
      </div>
    </form>
  </section>

  <?php if (! empty($tutorial)): ?>
    <!-- Modal tutorial — konten di-parse dari docs/TUTORIAL-TAMBAH-DATABASE.md -->
    <div
      class="app-modal"
      x-show="showTutorial"
      x-transition.opacity.duration.150ms
      @keydown.escape.window="showTutorial = false"
      role="dialog"
      aria-modal="true"
      aria-labelledby="tutorial-modal-title"
      style="display: none;"
    >
      <div class="app-modal__backdrop" @click="showTutorial = false"></div>

      <div class="app-modal__panel">
        <div class="app-modal__head">
          <div class="min-w-0">
            <p class="text-sm font-semibold tracking-wide text-brand-500">MD-Bridge</p>
            <h2 id="tutorial-modal-title" class="mt-1 flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white/90">
              <i class="fa-solid fa-circle-question text-brand-500" aria-hidden="true"></i>
              Tutorial: cara menambahkan database baru
            </h2>
            <p class="mt-1 text-xs font-normal text-gray-400 dark:text-gray-500">docs/TUTORIAL-TAMBAH-DATABASE.md</p>
          </div>
          <button type="button" class="app-modal__close" @click="showTutorial = false" aria-label="Tutup tutorial">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
          </button>
        </div>

        <div class="app-modal__body">
          <article class="tutorial-md text-sm text-gray-600 dark:text-gray-300">
            <?= $tutorial ?>
          </article>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>