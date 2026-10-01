<aside
  :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'"
  class="sidebar fixed left-0 top-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-hidden border-r border-gray-200 bg-white px-5 dark:border-gray-800 dark:bg-gray-900 lg:static lg:translate-x-0"
>
  <div :class="sidebarToggle ? 'justify-center' : 'justify-between'" class="flex items-center gap-2 pt-8 pb-7">
    <a href="<?= base_url('/') ?>">
      <span class="logo" :class="sidebarToggle ? 'hidden' : ''">
        <img class="dark:hidden" src="<?= base_url('assets/images/logo/logo.svg') ?>" alt="MD-Bridge" />
        <img class="hidden dark:block" src="<?= base_url('assets/images/logo/logo-dark.svg') ?>" alt="MD-Bridge" />
      </span>
      <img class="logo-icon" :class="sidebarToggle ? 'lg:block' : 'hidden'" src="<?= base_url('assets/images/logo/logo-icon.svg') ?>" alt="MD-Bridge" />
    </a>
  </div>

  <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
    <nav>
      <h3 class="mb-4 text-xs uppercase leading-[20px] text-gray-400">
        <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">MD-BRIDGE</span>
        <span class="hidden text-center menu-group-icon" :class="sidebarToggle ? 'lg:block' : ''">•••</span>
      </h3>

      <ul class="flex flex-col gap-2">
        <?php
        $navigation = [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => base_url('/'), 'icon' => 'grid'],
            ['key' => 'monitoring', 'label' => 'Monitoring', 'href' => base_url('monitoring'), 'icon' => 'database'],
            ['key' => 'tasks', 'label' => 'API Task Registry', 'href' => base_url('tasks'), 'icon' => 'list'],
            ['key' => 'logs', 'label' => 'Execution Logs', 'href' => base_url('logs'), 'icon' => 'activity'],
        ];
        foreach ($navigation as $item):
            $active = ($page ?? 'dashboard') === $item['key'];
        ?>
          <li>
            <a href="<?= esc($item['href']) ?>" class="menu-item group <?= $active ? 'menu-item-active' : 'menu-item-inactive' ?>">
              <?php if ($item['icon'] === 'grid'): ?>
                <svg class="<?= $active ? 'menu-item-icon-active' : 'menu-item-icon-inactive' ?>" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 4.75A.75.75 0 0 1 4.75 4h5.5a.75.75 0 0 1 .75.75v5.5a.75.75 0 0 1-.75.75h-5.5A.75.75 0 0 1 4 10.25v-5.5ZM13 4.75a.75.75 0 0 1 .75-.75h5.5a.75.75 0 0 1 .75.75v5.5a.75.75 0 0 1-.75.75h-5.5a.75.75 0 0 1-.75-.75v-5.5ZM4 13.75a.75.75 0 0 1 .75-.75h5.5a.75.75 0 0 1 .75.75v5.5a.75.75 0 0 1-.75.75h-5.5a.75.75 0 0 1-.75-.75v-5.5ZM13 13.75a.75.75 0 0 1 .75-.75h5.5a.75.75 0 0 1 .75.75v5.5a.75.75 0 0 1-.75.75h-5.5a.75.75 0 0 1-.75-.75v-5.5Z" fill="currentColor"/></svg>
              <?php elseif ($item['icon'] === 'database'): ?>
                <svg class="<?= $active ? 'menu-item-icon-active' : 'menu-item-icon-inactive' ?>" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 3C7.58 3 4 4.34 4 6v12c0 1.66 3.58 3 8 3s8-1.34 8-3V6c0-1.66-3.58-3-8-3Zm0 1.5c3.93 0 6.5 1.13 6.5 1.5S15.93 7.5 12 7.5 5.5 6.37 5.5 6 8.07 4.5 12 4.5Zm0 15c-3.93 0-6.5-1.13-6.5-1.5v-2.09C7 16.61 9.36 17 12 17s5-.39 6.5-1.09V18c0 .37-2.57 1.5-6.5 1.5Zm0-4c-3.93 0-6.5-1.13-6.5-1.5v-2.09C7 12.61 9.36 13 12 13s5-.39 6.5-1.09V14c0 .37-2.57 1.5-6.5 1.5Zm0-4C8.07 11.5 5.5 10.37 5.5 10V7.91C7 8.61 9.36 9 12 9s5-.39 6.5-1.09V10c0 .37-2.57 1.5-6.5 1.5Z" fill="currentColor"/></svg>
              <?php elseif ($item['icon'] === 'list'): ?>
                <svg class="<?= $active ? 'menu-item-icon-active' : 'menu-item-icon-inactive' ?>" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 5.75A.75.75 0 0 1 5.75 5h12.5a.75.75 0 0 1 0 1.5H5.75A.75.75 0 0 1 5 5.75ZM5 11.75a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H5.75a.75.75 0 0 1-.75-.75ZM5 17.75a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H5.75a.75.75 0 0 1-.75-.75Z" fill="currentColor"/></svg>
              <?php else: ?>
                <svg class="<?= $active ? 'menu-item-icon-active' : 'menu-item-icon-inactive' ?>" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.75 17.5a.75.75 0 0 1-.75-.75v-9.5a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 .75.75v9.5a.75.75 0 0 1-.75.75H4.75ZM6 15.5h2.25v-3H6v3Zm4 0h2.25v-6H10v6Zm4 0h2.25v-4.5H14v4.5Z" fill="currentColor"/></svg>
              <?php endif; ?>
              <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''"><?= esc($item['label']) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</aside>
