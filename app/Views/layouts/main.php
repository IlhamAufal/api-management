<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title><?= $title ?? 'MD-Bridge Integration Monitor' ?></title>
  <link rel="icon" href="<?= base_url('favicon.ico') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/css/all.min.css') ?>">
  <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/utilities-patch.css') ?>" rel="stylesheet">
</head>
<body
    x-data="{ page: '<?= esc($page ?? 'monitoring', 'js') ?>', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
    x-init="
         darkMode = JSON.parse(localStorage.getItem('darkMode'));
         $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
  >
  <?= $this->include('partials/preloader') ?>

  <div class="flex h-screen overflow-hidden">
    <?= $this->include('partials/sidebar') ?>

    <div class="relative flex flex-col flex-1 overflow-x-hidden overflow-y-auto">
      <?= $this->include('partials/overlay') ?>
      <?= $this->include('partials/navbar') ?>

      <main>
        <?= $this->renderSection('content') ?>
      </main>
    </div>
  </div>

  <?= $this->include('components/toast') ?>
  <?= $this->include('partials/footer_scripts') ?>
</body>
</html>
