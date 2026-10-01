<?php
/**
 * Read-only workflow visualization (n8n style) powered by Drawflow.
 *
 * Expected variable:
 *   $workflow = ['nodes' => [...], 'edges' => [...]]
 *
 * Each node: key, column, variant (source|process|table|sink), icon, title,
 * subtitle, status, meta[] ([label,value]). Purely visual - no editing.
 */
$workflow = $workflow ?? ['nodes' => [], 'edges' => []];
$workflowJson = json_encode(
    $workflow,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);
?>
<link rel="stylesheet" href="<?= base_url('assets/vendor/drawflow/drawflow.min.css') ?>">
<style>
  .wf-shell { position: relative; }
  .wf-canvas {
    position: relative;
    width: 100%;
    height: 480px;
    border-radius: 1rem;
    overflow: hidden;
    background-color: #f8fafc;
    background-image: radial-gradient(#d7dce5 1.1px, transparent 1.1px);
    background-size: 22px 22px;
    cursor: grab;
  }
  .wf-canvas:active { cursor: grabbing; }
  .dark .wf-canvas {
    background-color: #0f1621;
    background-image: radial-gradient(rgba(148, 163, 184, 0.18) 1.1px, transparent 1.1px);
  }

  /* Neutralise Drawflow default node chrome so our card owns the look */
  .wf-canvas .drawflow-node {
    background: transparent;
    border: 0;
    padding: 0;
    width: auto;
    min-height: 0;
    border-radius: 0;
    box-shadow: none;
  }
  .wf-canvas .drawflow-node:hover { cursor: move; }
  .wf-canvas .drawflow-node.selected { background: transparent; }
  .wf-canvas .drawflow-node.selected .wf-card {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.35), 0 6px 16px rgba(16, 24, 40, 0.08);
  }

  /* Connector dots (visual only - not interactive) */
  .wf-canvas .drawflow-node .input,
  .wf-canvas .drawflow-node .output {
    width: 12px;
    height: 12px;
    background: #ffffff;
    border: 2px solid #94a3b8;
    pointer-events: none;
  }
  .wf-canvas .drawflow-node .input { left: -7px; }
  .wf-canvas .drawflow-node .output { right: -1px; }
  .dark .wf-canvas .drawflow-node .input,
  .dark .wf-canvas .drawflow-node .output {
    background: #1e293b;
    border-color: #475569;
  }

  /* Connections (visual only - not interactive) */
  .wf-canvas .connection .main-path { stroke: #94a3b8; stroke-width: 2.5px; pointer-events: none; }
  .dark .wf-canvas .connection .main-path { stroke: #475569; }

  /* Node card */
  .wf-card {
    width: 232px;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #94a3b8;
    border-radius: 0.75rem;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.06), 0 6px 16px rgba(16, 24, 40, 0.06);
    padding: 12px 14px;
    font-family: inherit;
  }
  .dark .wf-card {
    background: #111827;
    border-color: #1f2937;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
  }
  .wf-card__head { display: flex; align-items: center; gap: 10px; }
  .wf-card__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    flex: 0 0 34px;
    border-radius: 9px;
    background: #f1f5f9;
    color: #475569;
  }
  .dark .wf-card__icon { background: #1f2937; color: #cbd5e1; }
  .wf-card__icon svg { width: 18px; height: 18px; }
  .wf-card__titles { min-width: 0; }
  .wf-card__title {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .dark .wf-card__title { color: #f1f5f9; }
  .wf-card__sub {
    margin-top: 1px;
    font-size: 11px;
    color: #94a3b8;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .wf-card__status { margin-top: 10px; }
  .wf-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 9px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
  }
  .wf-badge::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 9999px;
    background: currentColor;
  }
  .wf-card__metas {
    margin-top: 10px;
    padding-top: 9px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  .dark .wf-card__metas { border-top-color: #1f2937; }
  .wf-card__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 11px;
  }
  .wf-card__meta span { color: #94a3b8; }
  .wf-card__meta b {
    color: #475569;
    font-weight: 600;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  }
  .dark .wf-card__meta b { color: #cbd5e1; }

  /* Status accents (applied to the drawflow node wrapper) */
  .wf-status--success .wf-card { border-left-color: #22c55e; }
  .wf-status--failed  .wf-card { border-left-color: #ef4444; }
  .wf-status--running .wf-card,
  .wf-status--warning .wf-card { border-left-color: #f59e0b; }
  .wf-status--source  .wf-card { border-left-color: #3b82f6; }
  .wf-status--unknown .wf-card,
  .wf-status--not_connected .wf-card { border-left-color: #94a3b8; }

  .wf-badge--success { background: #dcfce7; color: #15803d; }
  .wf-badge--failed  { background: #fee2e2; color: #b91c1c; }
  .wf-badge--running,
  .wf-badge--warning { background: #fef3c7; color: #b45309; }
  .wf-badge--unknown,
  .wf-badge--not_connected { background: #f1f5f9; color: #475569; }
  .dark .wf-badge--success { background: rgba(34,197,94,0.15); color: #4ade80; }
  .dark .wf-badge--failed  { background: rgba(239,68,68,0.15); color: #f87171; }
  .dark .wf-badge--running,
  .dark .wf-badge--warning { background: rgba(245,158,11,0.15); color: #fbbf24; }
  .dark .wf-badge--unknown,
  .dark .wf-badge--not_connected { background: rgba(148,163,184,0.15); color: #cbd5e1; }

  /* Toolbar */
  .wf-toolbar {
    position: absolute;
    right: 12px;
    bottom: 12px;
    display: flex;
    gap: 6px;
    z-index: 5;
  }
  .wf-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 9px;
    border: 1px solid #e2e8f0;
    background: rgba(255, 255, 255, 0.9);
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
    backdrop-filter: blur(4px);
  }
  .wf-btn:hover { border-color: #93c5fd; color: #2563eb; }
  .dark .wf-btn {
    border-color: #1f2937;
    background: rgba(17, 24, 39, 0.85);
    color: #cbd5e1;
  }
  .dark .wf-btn:hover { border-color: #1d4ed8; color: #93c5fd; }
  .wf-btn svg { width: 16px; height: 16px; }

  .wf-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 12px;
  }
  .wf-legend__item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #64748b;
  }
  .dark .wf-legend__item { color: #94a3b8; }
  .wf-legend__dot { width: 9px; height: 9px; border-radius: 9999px; }
</style>

<section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
  <div class="mb-4 flex items-center justify-between gap-4">
    <div>
      <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Alur Workflow</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Seret kartu untuk mengatur posisi, seret area kosong untuk menggeser, Ctrl + scroll atau tombol untuk zoom. Muat ulang halaman untuk kembali ke tata letak awal.</p>
    </div>
  </div>

  <div class="wf-shell">
    <div id="workflow-canvas" class="wf-canvas"></div>
    <div class="wf-toolbar">
      <button type="button" class="wf-btn" data-wf-zoom="out" aria-label="Perkecil" title="Perkecil">
        <svg viewBox="0 0 24 24" fill="none"><path d="M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
      <button type="button" class="wf-btn" data-wf-zoom="reset" aria-label="Reset tampilan" title="Reset tampilan">
        <svg viewBox="0 0 24 24" fill="none"><path d="M4 4v6h6M20 20v-6h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 10a8 8 0 0 0-14.32-3.36M4 14a8 8 0 0 0 14.32 3.36" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
      <button type="button" class="wf-btn" data-wf-zoom="in" aria-label="Perbesar" title="Perbesar">
        <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
  </div>

  <div class="wf-legend">
    <span class="wf-legend__item"><span class="wf-legend__dot" style="background:#22c55e"></span>Success</span>
    <span class="wf-legend__item"><span class="wf-legend__dot" style="background:#f59e0b"></span>Running / Warning</span>
    <span class="wf-legend__item"><span class="wf-legend__dot" style="background:#ef4444"></span>Failed</span>
    <span class="wf-legend__item"><span class="wf-legend__dot" style="background:#94a3b8"></span>No data / Not connected</span>
  </div>
</section>

<script src="<?= base_url('assets/vendor/drawflow/drawflow.min.js') ?>"></script>
<script>
(function () {
  var mount = document.getElementById('workflow-canvas');
  if (!mount || typeof Drawflow === 'undefined') { return; }

  var workflow = <?= $workflowJson ?>;
  if (!workflow || !Array.isArray(workflow.nodes) || workflow.nodes.length === 0) { return; }

  var ICONS = {
    cloud: '<svg viewBox="0 0 24 24" fill="none"><path d="M7 18a4 4 0 0 1-.5-7.97 5.5 5.5 0 0 1 10.6-1.03A3.5 3.5 0 0 1 17.5 18H7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>',
    worker: '<svg viewBox="0 0 24 24" fill="none"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.7"/><path d="M19.4 13a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2V21a2 2 0 1 1-4 0v-.1A1.7 1.7 0 0 0 7 19.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0-1.2-2.9H3a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 4.7 7l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 10 4.6V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 2.9 1.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9Z" stroke="currentColor" stroke-width="1.3"/></svg>',
    table: '<svg viewBox="0 0 24 24" fill="none"><rect x="3.5" y="4.5" width="17" height="15" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 9.5h17M3.5 14.5h17M9 9.5v10" stroke="currentColor" stroke-width="1.7"/></svg>',
    database: '<svg viewBox="0 0 24 24" fill="none"><path d="M12 3c4.4 0 8 1.34 8 3s-3.6 3-8 3-8-1.34-8-3 3.6-3 8-3Z" stroke="currentColor" stroke-width="1.7"/><path d="M4 6v6c0 1.66 3.6 3 8 3s8-1.34 8-3V6M4 12v6c0 1.66 3.6 3 8 3s8-1.34 8-3v-6" stroke="currentColor" stroke-width="1.7"/></svg>'
  };

  var STATUS = {
    SUCCESS:       { label: 'Success',       cls: 'wf-badge--success' },
    FAILED:        { label: 'Failed',        cls: 'wf-badge--failed' },
    RUNNING:       { label: 'Running',       cls: 'wf-badge--running' },
    WARNING:       { label: 'Warning',       cls: 'wf-badge--warning' },
    UNKNOWN:       { label: 'No cron data',  cls: 'wf-badge--unknown' },
    NOT_CONNECTED: { label: 'Not connected', cls: 'wf-badge--not_connected' }
  };

  function esc(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function nodeHtml(node) {
    var info = STATUS[node.status] || STATUS.UNKNOWN;
    var badge = node.status === 'SOURCE'
      ? ''
      : '<div class="wf-card__status"><span class="wf-badge ' + info.cls + '">' + esc(info.label) + '</span></div>';

    var metas = '';
    if (Array.isArray(node.meta) && node.meta.length) {
      metas = '<div class="wf-card__metas">' + node.meta.map(function (m) {
        return '<div class="wf-card__meta"><span>' + esc(m.label) + '</span><b>' + esc(m.value) + '</b></div>';
      }).join('') + '</div>';
    }

    return '' +
      '<div class="wf-card">' +
        '<div class="wf-card__head">' +
          '<span class="wf-card__icon">' + (ICONS[node.icon] || '') + '</span>' +
          '<div class="wf-card__titles">' +
            '<div class="wf-card__title">' + esc(node.title) + '</div>' +
            '<div class="wf-card__sub">' + esc(node.subtitle) + '</div>' +
          '</div>' +
        '</div>' +
        badge +
        metas +
      '</div>';
  }

  var editor = new Drawflow(mount);
  editor.reroute = false;
  editor.editor_mode = 'edit'; // allow dragging cards (connections locked via CSS + guards)
  editor.zoom_max = 1.6;
  editor.zoom_min = 0.3;
  editor.zoom_value = 0.1;
  editor.start();

  // Keep it a visual guide: cards can be dragged, but block structural edits
  // (deleting nodes/connections or rewiring). Layout resets on page reload.
  mount.addEventListener('keydown', function (e) {
    if (e.key === 'Delete' || e.key === 'Backspace') { e.stopImmediatePropagation(); }
  }, true);
  mount.addEventListener('contextmenu', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
  }, true);

  // --- Layout constants ---
  var COL_X = { 0: 40, 1: 340, 2: 690, 3: 1070 };
  var CARD_W = 232;
  var GAP = 46;      // vertical gap between cards within a column
  var TOP_PAD = 36;  // top/bottom breathing room inside the canvas
  var moduleName = editor.module;

  function moveNode(id, x, y) {
    var el = document.getElementById('node-' + id);
    if (!el) { return; }
    el.style.left = x + 'px';
    el.style.top = y + 'px';
    var store = editor.drawflow.drawflow[moduleName].data[id];
    if (store) { store.pos_x = x; store.pos_y = y; }
    editor.updateConnectionNodes('node-' + id);
  }

  // --- Create nodes (temporary Y; final positions computed after measuring) ---
  var idMap = {};
  var nodesByColumn = {};
  workflow.nodes.forEach(function (node) {
    var col = node.column || 0;
    var x = (COL_X[col] != null) ? COL_X[col] : (40 + col * 350);
    var inputs = node.variant === 'source' ? 0 : 1;
    var outputs = node.variant === 'sink' ? 0 : 1;
    var status = (node.status || 'UNKNOWN').toLowerCase();
    var cls = 'wf-node wf-node--' + node.variant + ' wf-status--' + status;
    var id = editor.addNode(node.key, inputs, outputs, x, 0, cls, {}, nodeHtml(node));
    idMap[node.key] = id;
    node._id = id;
    node._x = x;
    (nodesByColumn[col] = nodesByColumn[col] || []).push(node);
  });

  // --- Create connections ---
  (workflow.edges || []).forEach(function (edge) {
    if (idMap[edge.from] && idMap[edge.to]) {
      editor.addConnection(idMap[edge.from], idMap[edge.to], 'output_1', 'input_1');
    }
  });

  // --- Measure real card heights, then stack each column with a fixed gap ---
  var columnHeights = {};
  Object.keys(nodesByColumn).forEach(function (col) {
    var arr = nodesByColumn[col];
    var total = 0;
    arr.forEach(function (node, i) {
      var el = document.getElementById('node-' + node._id);
      node._h = (el && el.offsetHeight) ? el.offsetHeight : 130;
      total += node._h + (i < arr.length - 1 ? GAP : 0);
    });
    columnHeights[col] = total;
  });

  var maxColumnHeight = Math.max.apply(null, Object.keys(columnHeights).map(function (k) {
    return columnHeights[k];
  }).concat([0]));

  var maxRight = 0;
  Object.keys(nodesByColumn).forEach(function (col) {
    var arr = nodesByColumn[col];
    var y = TOP_PAD + (maxColumnHeight - columnHeights[col]) / 2;
    arr.forEach(function (node) {
      moveNode(node._id, node._x, y);
      if (node._x + CARD_W > maxRight) { maxRight = node._x + CARD_W; }
      y += node._h + GAP;
    });
  });

  // Final pass so every connection path uses the settled positions
  Object.keys(idMap).forEach(function (key) {
    editor.updateConnectionNodes('node-' + idMap[key]);
  });

  // --- Size the canvas to the content height ---
  mount.style.height = Math.max(420, TOP_PAD * 2 + maxColumnHeight) + 'px';

  // --- Fit the diagram horizontally on first render ---
  function applyTransform(zoom, x, y) {
    editor.zoom = zoom;
    editor.zoom_last_value = zoom;
    editor.canvas_x = x;
    editor.canvas_y = y;
    editor.precanvas.style.transform = 'translate(' + x + 'px, ' + y + 'px) scale(' + zoom + ')';
    editor.dispatch('zoom', editor.zoom);
  }

  function fitToWidth() {
    var contentWidth = maxRight + 40;
    var containerWidth = mount.clientWidth;
    if (!containerWidth) { return; }
    var zoom = Math.min(1, containerWidth / contentWidth);
    zoom = Math.max(editor.zoom_min, zoom);
    applyTransform(zoom, 0, 0);
  }

  fitToWidth();

  // --- Toolbar wiring ---
  var shell = mount.parentElement;
  shell.querySelectorAll('[data-wf-zoom]').forEach(function (button) {
    button.addEventListener('click', function () {
      var action = button.getAttribute('data-wf-zoom');
      if (action === 'in') { editor.zoom_in(); }
      else if (action === 'out') { editor.zoom_out(); }
      else { fitToWidth(); }
    });
  });
})();
</script>
