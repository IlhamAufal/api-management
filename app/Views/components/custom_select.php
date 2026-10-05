<?php
/**
 * Komponen Reusable Custom Dropdown Select dengan Alpine.js
 *
 * Menggantikan tampilan native OS select dengan popover modern & minimalist
 * namun tetap membungkus native <select> di baliknya untuk kompatibilitas
 * 100% terhadap form POST, browser validation, dan manipulasi JavaScript.
 *
 * Style komponen didefinisikan scoped (.cs-select*) di
 * public/assets/css/utilities-patch.css (Batch 7).
 *
 * Cara pakai — semua argumen dikirim sebagai SATU array di key `cs`:
 *
 *   <?= view('components/custom_select', ['cs' => [
 *       'name'        => 'status',          // wajib: nama form field
 *       'value'       => $status ?? '',     // wajib: nilai terpilih
 *       'options'     => $statusOptions,    // wajib: list/assoc/[['value','label']]
 *       'placeholder' => 'Semua status',    // opsional
 *       'label'       => 'Nama Tabel <span class="text-error-500">*</span>', // opsional, HTML mentah
 *       'required'    => true,              // opsional (default false)
 *       'disabled'    => false,             // opsional (default false)
 *       'id'          => 'status',          // opsional (default = name)
 *       'size'        => 'sm',              // opsional: 'md' (default) | 'sm'
 *       'class'       => 'w-44',            // opsional: class wrapper (default w-full)
 *       'btnClass'    => '',                // opsional: class tambahan tombol
 *       'onchange'    => 'this.form.submit()',
 *       'attributes'  => 'aria-label="Filter source"',
 *       'searchable'  => true,              // opsional (default: otomatis bila opsi >= 8)
 *   ]) ?>
 *
 * Dipakai lewat satu key `cs` karena Config\View::$saveData = true: data yang
 * dikirim ke satu render ikut bocor ke render berikutnya, sehingga argumen
 * opsional yang tidak dikirim bisa mewarisi nilai dari render sebelumnya.
 */
$cs = is_array($cs ?? null) ? $cs : [];

$name        = (string) ($cs['name'] ?? '');
$id          = (string) ($cs['id'] ?? $name);
$value       = (string) ($cs['value'] ?? '');
$placeholder = $cs['placeholder'] ?? null;
$options     = $cs['options'] ?? [];
$label       = $cs['label'] ?? null;
$required    = ! empty($cs['required']);
$disabled    = ! empty($cs['disabled']);
$size        = (($cs['size'] ?? 'md') === 'sm') ? 'sm' : 'md';
$class       = (string) ($cs['class'] ?? 'w-full');
$btnClass    = (string) ($cs['btnClass'] ?? '');
$onchange    = $cs['onchange'] ?? null;
$attributes  = (string) ($cs['attributes'] ?? '');
$searchable  = array_key_exists('searchable', $cs) && $cs['searchable'] !== null
    ? (bool) $cs['searchable']
    : null;

// Ekspresi JS untuk x-data — di-esc ke konteks atribut agar kutipan JSON
// dari json_encode() tidak menutup atribut x-data lebih awal.
$jsConfig = 'customDropdownSelect({'
    . 'value: ' . json_encode($value)
    . ', placeholder: ' . json_encode((string) ($placeholder ?? ''))
    . ', disabled: ' . ($disabled ? 'true' : 'false')
    . ', searchable: ' . ($searchable === null ? 'null' : ($searchable ? 'true' : 'false'))
    . '})';

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
  x-data="<?= esc($jsConfig, 'attr') ?>"
  class="cs-select<?= $size === 'sm' ? ' cs-select--sm' : '' ?> <?= esc($class) ?>"
  @click.outside="open = false"
  @keydown.escape.window="onEscape()"
>
  <?php if ($label !== null && $label !== ''): ?>
    <!-- Label memicu click pada trigger button (bukan native select yang tersembunyi) -->
    <label for="<?= esc($id) ?>__trigger" class="cs-select__label"><?= $label ?></label>
  <?php endif; ?>

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
    id="<?= esc($id) ?>__trigger"
    @click="toggle()"
    :disabled="disabled"
    class="cs-select__btn<?= $btnClass !== '' ? ' ' . esc($btnClass) : '' ?>"
    :class="{ 'is-open': open }"
    aria-haspopup="listbox"
    :aria-expanded="open"
  >
    <span
      class="cs-select__value"
      :class="{ 'is-placeholder': !selectedLabel }"
      x-text="selectedLabel || placeholder || '— Pilih —'"
    ><?= esc($currentLabel !== '' ? $currentLabel : ($placeholder ?? '— Pilih —')) ?></span>

    <span class="cs-select__caret" :class="{ 'is-open': open }">
      <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
    </span>
  </button>

  <!-- Custom Popover Menu -->
  <div
    x-show="open"
    x-cloak
    x-transition:enter="cs-tr"
    x-transition:enter-start="cs-tr-out"
    x-transition:enter-end="cs-tr-in"
    x-transition:leave="cs-tr"
    x-transition:leave-start="cs-tr-in"
    x-transition:leave-end="cs-tr-out"
    class="cs-select__menu"
    role="listbox"
    style="display: none;"
  >
    <!-- Search Box (shown if searchable or items >= 8) -->
    <template x-if="isSearchable">
      <div class="cs-select__search">
        <i class="fa-solid fa-magnifying-glass cs-select__search-icon" aria-hidden="true"></i>
        <input
          type="text"
          x-model="search"
          placeholder="Cari..."
          aria-label="Cari opsi"
          @click.stop
          @keydown.down.prevent="focusOption(0)"
          @keydown.enter.prevent="selectFirst()"
        />
      </div>
    </template>

    <!-- Options List -->
    <ul class="cs-select__list">
      <template x-for="item in filteredItems" :key="item.value">
        <li
          class="cs-select__opt"
          :class="{ 'is-selected': isSelected(item.value) }"
          role="option"
          :aria-selected="isSelected(item.value)"
          tabindex="-1"
          @click="selectItem(item.value)"
          @keydown.enter.prevent="selectItem(item.value)"
          @keydown.space.prevent="selectItem(item.value)"
          @keydown.down.prevent="focusSibling(1)"
          @keydown.up.prevent="focusSibling(-1)"
          @keydown.tab="open = false"
        >
          <span class="cs-select__opt-text" x-text="item.label"></span>
          <span class="cs-select__check" x-show="isSelected(item.value)">
            <i class="fa-solid fa-check" aria-hidden="true"></i>
          </span>
        </li>
      </template>
      <template x-if="filteredItems.length === 0">
        <li class="cs-select__empty">Tidak ada opsi yang cocok</li>
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

          this.selectEl.addEventListener('invalid', (e) => {
            e.preventDefault();
            this.open = true;
            this.$nextTick(() => this.focusFirst());
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
          this.$nextTick(() => this.focusFirst());
        }
      },

      onEscape() {
        if (!this.open) return;
        this.open = false;
        this.focusTrigger();
      },

      focusTrigger() {
        const btn = this.$el.querySelector('.cs-select__btn');
        if (btn) btn.focus();
      },

      optionEls() {
        return Array.from(this.$el.querySelectorAll('.cs-select__opt'));
      },

      focusFirst() {
        const searchInput = this.$el.querySelector('.cs-select__search input');
        if (searchInput) {
          searchInput.focus();
          return;
        }
        const opts = this.optionEls();
        if (opts.length) opts[0].focus();
      },

      focusOption(index) {
        const opts = this.optionEls();
        if (!opts.length) return;
        const i = Math.max(0, Math.min(index, opts.length - 1));
        opts[i].focus();
      },

      focusSibling(delta) {
        const opts = this.optionEls();
        if (!opts.length) return;
        const current = opts.indexOf(document.activeElement);
        const next = current === -1 ? 0 : (current + delta + opts.length) % opts.length;
        opts[next].focus();
      },

      selectFirst() {
        if (this.filteredItems.length) {
          this.selectItem(this.filteredItems[0].value);
        }
      },

      selectItem(val) {
        this.selected = val;
        if (this.selectEl) {
          this.selectEl.value = val;
          // Event 'change' sudah memicu handler inline onchange di native select.
          this.selectEl.dispatchEvent(new Event('change', { bubbles: true }));
          this.selectEl.dispatchEvent(new Event('input', { bubbles: true }));
        }
        this.updateSelectedLabel();
        this.open = false;
        this.focusTrigger();
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
