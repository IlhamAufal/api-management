<?php
/**
 * Komponen Reusable Custom Dropdown Select dengan Alpine.js
 *
 * Menggantikan tampilan native OS select dengan popover modern & minimalist
 * namun tetap membungkus native <select> di baliknya untuk kompatibilitas
 * 100% terhadap form POST, browser validation, dan manipulasi JavaScript.
 *
 * @var string            $name        Nama form field (required)
 * @var string|null       $id          ID elemen (default sama dengan $name)
 * @var mixed             $value       Nilai terpilih saat ini
 * @var array             $options     Array opsi (bisa list [val], assoc [val => label], atau [['value'=>..., 'label'=>...]])
 * @var string|null       $placeholder Placeholder teks bila kosong (default null)
 * @var bool|null         $required    Wajib diisi atau tidak (default false)
 * @var bool|null         $disabled    Disabled atau tidak (default false)
 * @var string|null       $class       Class CSS container tambahan (misal 'w-full' atau 'w-44')
 * @var string|null       $btnClass    Class CSS khusus trigger button (opsional override)
 * @var string|null       $onchange    Atribut onchange pada native select (misal 'this.form.submit()')
 * @var string|null       $attributes  Atribut HTML mentah tambahan (misal 'aria-label="..."')
 * @var bool|null         $searchable  Paksa aktifkan pencarian (otomatis true bila opsi >= 8)
 */

$id          = $id ?? $name;
$value       = (string) ($value ?? '');
$required    = ! empty($required);
$disabled    = ! empty($disabled);
$placeholder = $placeholder ?? null;
$options     = $options ?? [];
$class       = $class ?? 'w-full';
$onchange    = $onchange ?? null;
$attributes  = $attributes ?? '';
$searchable  = isset($searchable) ? (bool) $searchable : null;

// Normalisasi opsi ke struktur [['value' => ..., 'label' => ...]]
$normalizedOptions = [];
foreach ($options as $k => $opt) {
    if (is_array($opt)) {
        $optVal = (string) ($opt['value'] ?? $k);
        $optLbl = (string) ($opt['label'] ?? $optVal);
    } else {
        $optVal = is_int($k) ? (string) $opt : (string) $k;
        $optLbl = (string) $opt;
    }
    $normalizedOptions[] = [
        'value' => $optVal,
        'label' => $optLbl,
    ];
}

// Cari label untuk nilai yang aktif saat render awal server-side
$currentLabel = '';
if ($placeholder !== null && $value === '') {
    $currentLabel = $placeholder;
} else {
    foreach ($normalizedOptions as $no) {
        if ($no['value'] === $value) {
            $currentLabel = $no['label'];
            break;
        }
    }
    if ($currentLabel === '' && $placeholder !== null) {
        $currentLabel = $placeholder;
    }
}
?>
<div
  x-data="customDropdownSelect({
    value: <?= json_encode($value) ?>,
    placeholder: <?= json_encode($placeholder ?? '') ?>,
    disabled: <?= $disabled ? 'true' : 'false' ?>,
    searchable: <?= $searchable !== null ? ($searchable ? 'true' : 'false') : 'null' ?>
  })"
  class="relative custom-select-wrapper <?= esc($class) ?>"
  @click.outside="open = false"
  @keydown.escape.window="open = false"
>
  <!-- Native select (invisible to user, but functional for form submission & JS access) -->
  <select
    id="<?= esc($id) ?>"
    name="<?= esc($name) ?>"
    class="custom-select-native"
    tabindex="-1"
    aria-hidden="true"
    <?= $required ? 'required' : '' ?>
    <?= $disabled ? 'disabled' : '' ?>
    <?= $onchange ? 'onchange="' . esc($onchange, 'attr') . '"' : '' ?>
    <?= $attributes ?>
  >
    <?php if ($placeholder !== null): ?>
      <option value="" <?= $value === '' ? 'selected' : '' ?>><?= esc($placeholder) ?></option>
    <?php endif; ?>
    <?php foreach ($normalizedOptions as $opt): ?>
      <option value="<?= esc($opt['value'], 'attr') ?>" <?= $opt['value'] === $value ? 'selected' : '' ?>>
        <?= esc($opt['label']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <!-- Custom Trigger Button -->
  <button
    type="button"
    @click="toggle()"
    :disabled="disabled"
    class="relative flex w-full items-center justify-between gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-left text-sm text-gray-800 transition duration-150 hover:border-gray-300 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-500/10 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900/60 dark:text-gray-200 dark:hover:border-gray-600 dark:disabled:bg-gray-800/40 <?= esc($btnClass ?? '') ?>"
    :class="{ 'border-brand-500 ring-4 ring-brand-500/10 dark:border-brand-500': open }"
    aria-haspopup="listbox"
    :aria-expanded="open"
  >
    <span
      class="truncate"
      :class="{ 'text-gray-400 dark:text-gray-500': (!selected || selected === '') && placeholder }"
      x-text="selectedLabel || placeholder || '— Pilih —'"
    ><?= esc($currentLabel !== '' ? $currentLabel : ($placeholder ?? '— Pilih —')) ?></span>

    <span
      class="pointer-events-none flex shrink-0 items-center text-gray-400 transition-transform duration-200 dark:text-gray-500"
      :class="{ 'rotate-180': open }"
    >
      <i class="fa-solid fa-chevron-down text-xs" aria-hidden="true"></i>
    </span>
  </button>

  <!-- Custom Popover Menu -->
  <div
    x-show="open"
    x-cloak
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0 translate-y-1 scale-98"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-1 scale-98"
    class="absolute left-0 top-full z-[100] mt-1.5 w-full min-w-[200px] overflow-hidden rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl dark:border-gray-700 dark:bg-gray-800"
    role="listbox"
    style="display: none;"
  >
    <!-- Search Box (shown if searchable or items >= 8) -->
    <template x-if="isSearchable">
      <div class="mb-1 border-b border-gray-100 p-1 pb-1.5 dark:border-gray-700/60">
        <div class="relative">
          <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400"></i>
          <input
            type="text"
            x-model="search"
            placeholder="Cari..."
            class="w-full rounded-lg bg-gray-50 py-1.5 pl-8 pr-2 text-xs text-gray-700 placeholder-gray-400 outline-none focus:bg-white focus:ring-1 focus:ring-brand-500 dark:bg-gray-900/60 dark:text-gray-200 dark:placeholder-gray-500"
            @click.stop
          />
        </div>
      </div>
    </template>

    <!-- Options List -->
    <ul class="max-h-60 overflow-y-auto space-y-0.5 no-scrollbar">
      <template x-for="item in filteredItems" :key="item.value">
        <li
          @click="selectItem(item.value)"
          class="group flex cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-sm transition"
          :class="isSelected(item.value)
            ? 'bg-brand-50 font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400'
            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.06] dark:hover:text-white'"
          role="option"
          :aria-selected="isSelected(item.value)"
        >
          <span class="truncate" x-text="item.label"></span>
          <span x-show="isSelected(item.value)" class="ml-2 shrink-0 text-brand-600 dark:text-brand-400">
            <i class="fa-solid fa-check text-xs" aria-hidden="true"></i>
          </span>
        </li>
      </template>
      <template x-if="filteredItems.length === 0">
        <li class="px-3 py-3 text-center text-xs text-gray-400 dark:text-gray-500">
          Tidak ada opsi yang cocok
        </li>
      </template>
    </ul>
  </div>
</div>

<script>
if (typeof window.customDropdownSelect === 'undefined') {
  window.customDropdownSelect = function(config) {
    return {
      open: false,
      selected: config.value || '',
      selectedLabel: '',
      placeholder: config.placeholder || '',
      disabled: !!config.disabled,
      search: '',
      items: [],
      selectEl: null,
      observer: null,

      get isSearchable() {
        if (config.searchable !== null && typeof config.searchable !== 'undefined') {
          return config.searchable;
        }
        return this.items.length >= 8;
      },

      init() {
        this.selectEl = this.$el.querySelector('select');
        if (this.selectEl) {
          this.syncFromSelect();

          this.selectEl.addEventListener('change', () => {
            this.selected = this.selectEl.value;
            this.updateSelectedLabel();
          });

          this.selectEl.addEventListener('invalid', () => {
            this.open = true;
          });

          this.observer = new MutationObserver(() => {
            this.syncFromSelect();
          });
          this.observer.observe(this.selectEl, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['disabled']
          });
        }
      },

      destroy() {
        if (this.observer) {
          this.observer.disconnect();
        }
      },

      syncFromSelect() {
        if (!this.selectEl) return;
        this.disabled = this.selectEl.disabled;

        const newItems = [];
        const opts = this.selectEl.options;
        for (let i = 0; i < opts.length; i++) {
          const opt = opts[i];
          newItems.push({
            value: opt.value,
            label: opt.text || opt.textContent || opt.value,
            disabled: opt.disabled
          });
        }
        this.items = newItems;
        this.selected = this.selectEl.value;
        this.updateSelectedLabel();
      },

      updateSelectedLabel() {
        if (!this.selectEl) return;
        const selectedIndex = this.selectEl.selectedIndex;
        if (selectedIndex >= 0 && this.selectEl.options[selectedIndex]) {
          const opt = this.selectEl.options[selectedIndex];
          this.selectedLabel = opt.text || opt.textContent;
        } else {
          const matched = this.items.find(i => String(i.value) === String(this.selected));
          this.selectedLabel = matched ? matched.label : (this.placeholder || '');
        }
      },

      toggle() {
        if (this.disabled) return;
        this.open = !this.open;
        if (this.open) {
          this.search = '';
          this.$nextTick(() => {
            const searchInput = this.$el.querySelector('input[type="text"]');
            if (searchInput) searchInput.focus();
          });
        }
      },

      selectItem(val) {
        this.selected = val;
        if (this.selectEl) {
          this.selectEl.value = val;
          this.selectEl.dispatchEvent(new Event('change', { bubbles: true }));
          this.selectEl.dispatchEvent(new Event('input', { bubbles: true }));
          if (typeof this.selectEl.onchange === 'function') {
            this.selectEl.onchange();
          }
        }
        this.updateSelectedLabel();
        this.open = false;
      },

      isSelected(val) {
        return String(this.selected) === String(val);
      },

      get filteredItems() {
        if (!this.search || this.search.trim() === '') {
          return this.items;
        }
        const q = this.search.toLowerCase();
        return this.items.filter(item => item.label.toLowerCase().includes(q));
      }
    };
  };
}
</script>
