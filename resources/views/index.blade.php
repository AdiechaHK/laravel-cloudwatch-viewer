<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CloudWatch Viewer</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
/* ── Reset ─────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
[hidden] { display: none !important; }

/* ── Design tokens ─────────────────────────────────────────────── */
:root {
    --bg:           #07090f;
    --surface:      #0d1017;
    --surface2:     #131720;
    --surface3:     #1a1f2e;
    --surface4:     #222840;
    --border:       #222640;
    --border-dim:   #181c2e;
    --accent:       #00d4aa;
    --accent-dim:   #00b390;
    --accent-glow:  rgba(0,212,170,.14);
    --text:         #e4eaf5;
    --text-dim:     #8894a8;
    --text-muted:   #3f4960;
    --error:        #ff6b6b;
    --error-bg:     rgba(255,107,107,.1);
    --error-border: rgba(255,107,107,.25);
    --warning:      #fbbf24;
    --warning-bg:   rgba(251,191,36,.1);
    --warning-border:rgba(251,191,36,.25);
    --info:         #4ea8ff;
    --info-bg:      rgba(78,168,255,.1);
    --info-border:  rgba(78,168,255,.25);
    --debug:        #b48eff;
    --debug-bg:     rgba(180,142,255,.1);
    --debug-border: rgba(180,142,255,.25);
    --radius:       8px;
    --radius-sm:    5px;
    --radius-lg:    12px;
    --shadow-sm:    0 2px 8px rgba(0,0,0,.35);
    --shadow:       0 8px 32px rgba(0,0,0,.5);
    --font-ui:      'Inter', system-ui, sans-serif;
    --font-mono:    'IBM Plex Mono', 'Fira Code', monospace;
}

/* ── Base ───────────────────────────────────────────────────────── */
html, body { height: 100%; background: var(--bg); color: var(--text); font-family: var(--font-ui); font-size: 13px; line-height: 1.5; -webkit-font-smoothing: antialiased; }

/* ── Scrollbars ─────────────────────────────────────────────────── */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--surface4); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

/* ── Header ─────────────────────────────────────────────────────── */
.header {
    position: sticky; top: 0; z-index: 200;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 20px; height: 54px;
    background: rgba(13,16,23,.92);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
}
.header-brand { display: flex; align-items: center; gap: 10px; }
.header-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--accent); flex-shrink: 0;
    box-shadow: 0 0 0 2px var(--accent-glow), 0 0 8px var(--accent);
    animation: breathe 2.4s ease-in-out infinite;
}
@keyframes breathe {
    0%,100% { box-shadow: 0 0 0 2px var(--accent-glow), 0 0 8px var(--accent); }
    50%      { box-shadow: 0 0 0 4px var(--accent-glow), 0 0 16px var(--accent); }
}
.header-name {
    font-family: var(--font-mono); font-size: 13px; font-weight: 600;
    letter-spacing: .04em; color: var(--text);
}
.header-name em { color: var(--accent); font-style: normal; }
.header-right { display: flex; align-items: center; gap: 12px; }

/* ── Layout ─────────────────────────────────────────────────────── */
.layout { display: flex; height: calc(100vh - 54px); overflow: hidden; }

/* ── Sidebar ─────────────────────────────────────────────────────── */
.sidebar {
    width: 296px; flex-shrink: 0;
    background: var(--surface); border-right: 1px solid var(--border);
    overflow-y: auto; display: flex; flex-direction: column; gap: 0;
}
.sidebar-section { padding: 16px; border-bottom: 1px solid var(--border-dim); }
.sidebar-section:last-of-type { border-bottom: none; }
.sidebar-footer { padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; margin-top: auto; border-top: 1px solid var(--border-dim); }

.section-label {
    display: flex; align-items: center; gap: 6px;
    font-size: 10px; font-weight: 600; letter-spacing: .1em;
    text-transform: uppercase; color: var(--text-muted);
    margin-bottom: 10px;
}
.section-label svg { width: 11px; height: 11px; opacity: .7; }

/* ── Log group checkboxes ──────────────────────────────────────── */
.group-list { display: flex; flex-direction: column; gap: 4px; }
.group-item { display: flex; align-items: flex-start; gap: 9px; padding: 5px 7px; border-radius: var(--radius-sm); cursor: pointer; transition: background .12s; }
.group-item:hover { background: var(--surface2); }
.group-item input[type="checkbox"] {
    appearance: none; flex-shrink: 0; width: 15px; height: 15px; margin-top: 1px;
    border: 1.5px solid var(--border); border-radius: 4px;
    background: var(--surface2); cursor: pointer; position: relative;
    transition: all .15s;
}
.group-item input[type="checkbox"]:checked { background: var(--accent); border-color: var(--accent); }
.group-item input[type="checkbox"]:checked::after {
    content: ''; position: absolute; left: 4px; top: 1px;
    width: 4px; height: 8px; border: 1.5px solid #000;
    border-left: none; border-top: none; transform: rotate(45deg);
}
.group-item label { cursor: pointer; display: flex; flex-direction: column; gap: 1px; }
.group-item .group-name { font-size: 12px; font-weight: 500; color: var(--text); }
.group-item .group-path { font-family: var(--font-mono); font-size: 9px; color: var(--text-muted); word-break: break-all; }
.group-empty { font-size: 11px; color: var(--text-muted); line-height: 1.6; }
.group-empty code { color: var(--accent); font-family: var(--font-mono); }

/* ── Level pills ─────────────────────────────────────────────────── */
.level-grid { display: flex; flex-wrap: wrap; gap: 5px; }
.level-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 9px; border-radius: 20px; cursor: pointer;
    font-family: var(--font-mono); font-size: 10px; font-weight: 600; letter-spacing: .05em;
    border: 1.5px solid var(--border); background: transparent; color: var(--text-muted);
    transition: all .15s;
}
.level-pill::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
.level-pill:hover { border-color: var(--text-dim); color: var(--text-dim); }
.level-pill.active[data-level="ALL"]     { background: rgba(228,234,245,.08); border-color: var(--text-dim); color: var(--text); }
.level-pill.active[data-level="ERROR"]   { background: var(--error-bg);   border-color: var(--error-border);   color: var(--error);   }
.level-pill.active[data-level="WARNING"] { background: var(--warning-bg); border-color: var(--warning-border); color: var(--warning); }
.level-pill.active[data-level="INFO"]    { background: var(--info-bg);    border-color: var(--info-border);    color: var(--info);    }
.level-pill.active[data-level="DEBUG"]   { background: var(--debug-bg);   border-color: var(--debug-border);   color: var(--debug);   }

/* ── Form controls ───────────────────────────────────────────────── */
.field-stack { display: flex; flex-direction: column; gap: 7px; }
.field-label { font-size: 11px; color: var(--text-dim); font-weight: 500; }

.input-wrap { position: relative; }
.input-icon {
    position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); width: 13px; height: 13px; pointer-events: none;
}
.form-input {
    width: 100%; background: var(--surface2); border: 1.5px solid var(--border);
    border-radius: var(--radius-sm); color: var(--text); outline: none;
    font-family: var(--font-mono); font-size: 11px;
    padding: 7px 10px; transition: border-color .15s, box-shadow .15s;
}
.form-input.has-icon { padding-left: 30px; }
.form-input::placeholder { color: var(--text-muted); }
.form-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
select.form-input { cursor: pointer; color-scheme: dark; }
input[type="datetime-local"].form-input { color-scheme: dark; }

/* ── Toggle switch ───────────────────────────────────────────────── */
.toggle-row { display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 4px 0; }
.toggle-row input { position: absolute; opacity: 0; width: 0; height: 0; }
.toggle-track {
    width: 30px; height: 17px; border-radius: 9px; flex-shrink: 0;
    background: var(--surface3); border: 1.5px solid var(--border);
    position: relative; transition: background .2s, border-color .2s;
}
.toggle-thumb {
    position: absolute; top: 2px; left: 2px;
    width: 11px; height: 11px; border-radius: 50%;
    background: var(--text-muted); transition: transform .2s, background .2s;
}
.toggle-row input:checked ~ .toggle-track { background: var(--accent-glow); border-color: var(--accent); }
.toggle-row input:checked ~ .toggle-track .toggle-thumb { transform: translateX(13px); background: var(--accent); }
.toggle-row span { font-size: 12px; color: var(--text-dim); }

/* ── Buttons ─────────────────────────────────────────────────────── */
.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    width: 100%; padding: 9px 14px; border-radius: var(--radius-sm);
    font-family: var(--font-ui); font-size: 12px; font-weight: 600;
    letter-spacing: .01em; border: 1.5px solid transparent;
    cursor: pointer; transition: all .15s; white-space: nowrap;
}
.btn:disabled { opacity: .45; cursor: not-allowed; }
.btn svg { width: 14px; height: 14px; flex-shrink: 0; }

.btn-primary { background: var(--accent); color: #021a14; border-color: var(--accent); }
.btn-primary:hover:not(:disabled) { background: var(--accent-dim); border-color: var(--accent-dim); }
.btn-primary:active:not(:disabled) { transform: scale(.98); }

.btn-ghost {
    background: transparent; color: var(--text-muted);
    border-color: var(--border); font-weight: 500;
}
.btn-ghost:hover:not(:disabled) { color: var(--text-dim); border-color: var(--border); background: var(--surface2); }

/* ── Mode segmented control (header) ─────────────────────────────── */
.mode-seg {
    display: inline-flex; align-items: center;
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 20px; padding: 3px; gap: 2px;
}
.mode-opt {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 13px; border-radius: 16px; border: none;
    font-family: var(--font-ui); font-size: 11px; font-weight: 600; letter-spacing: .04em;
    color: var(--text-muted); background: transparent;
    cursor: pointer; transition: color .15s, background .15s, box-shadow .15s;
    white-space: nowrap;
}
.mode-opt:hover:not(.active) { color: var(--text-dim); }
.mode-opt.active {
    background: var(--surface4); color: var(--text);
    box-shadow: 0 1px 3px rgba(0,0,0,.4);
}
.mode-opt[data-mode="live"].active {
    background: var(--error-bg); color: var(--error);
    box-shadow: 0 1px 3px rgba(0,0,0,.3);
}
.mode-opt svg { width: 11px; height: 11px; flex-shrink: 0; }
.mode-live-dot {
    width: 7px; height: 7px; border-radius: 50%; background: currentColor; flex-shrink: 0;
}
.mode-opt[data-mode="live"].active .mode-live-dot { animation: breathe 1s ease-in-out infinite; }

/* ── Sidebar section — muted (disabled in live mode) ────────────── */
.sidebar-section.muted {
    opacity: .3; pointer-events: none; user-select: none;
    transition: opacity .2s;
}

/* ── Main ────────────────────────────────────────────────────────── */
.main { flex: 1; overflow-y: auto; display: flex; flex-direction: column; min-width: 0; }

/* ── Progress bar ────────────────────────────────────────────────── */
.progress-bar { height: 2px; background: var(--border-dim); flex-shrink: 0; overflow: hidden; }
.progress-fill {
    height: 100%; width: 40%; background: linear-gradient(90deg, transparent, var(--accent), var(--accent-dim), transparent);
    transform: translateX(-100%);
    transition: opacity .2s;
}
.progress-fill.running { animation: progress-slide 1.1s linear infinite; }
@keyframes progress-slide { from { transform: translateX(-100%); } to { transform: translateX(300%); } }

/* ── Toolbar ─────────────────────────────────────────────────────── */
.toolbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 9px 20px; border-bottom: 1px solid var(--border-dim);
    background: var(--surface); flex-shrink: 0; gap: 12px;
}
.toolbar-count { font-size: 12px; color: var(--text-dim); }
.toolbar-count strong { color: var(--text); font-weight: 600; }
.toolbar-meta { font-family: var(--font-mono); font-size: 10px; color: var(--text-muted); }

/* ── Alert banner ────────────────────────────────────────────────── */
.alert {
    margin: 12px 16px; padding: 9px 13px; border-radius: var(--radius-sm);
    font-family: var(--font-mono); font-size: 11px; display: flex; align-items: flex-start; gap: 8px;
}
.alert svg { width: 14px; height: 14px; flex-shrink: 0; margin-top: 1px; }
.alert-error { background: var(--error-bg); border: 1px solid var(--error-border); color: var(--error); }
.alert-warn  { background: var(--warning-bg); border: 1px solid var(--warning-border); color: var(--warning); }

/* ── Table ───────────────────────────────────────────────────────── */
.table-wrap { overflow-x: auto; flex: 1; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
thead th {
    padding: 9px 14px; text-align: left; white-space: nowrap;
    font-size: 10px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase;
    color: var(--text-muted); background: var(--surface);
    border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 10;
    overflow: hidden; text-overflow: ellipsis;
}
tbody tr {
    border-bottom: 1px solid var(--border-dim);
    border-left: 2px solid transparent;
    transition: background .1s, border-left-color .1s;
    cursor: pointer;
}
tbody tr:hover { background: var(--surface2); }
tbody tr[data-level="ERROR"]    { border-left-color: var(--error);   }
tbody tr[data-level="WARNING"]  { border-left-color: var(--warning); }
tbody tr[data-level="INFO"]     { border-left-color: var(--info);    }
tbody tr[data-level="DEBUG"]    { border-left-color: var(--debug);   }
tbody td { padding: 8px 14px; font-size: 12px; vertical-align: middle; overflow: hidden; }

/* ── Cell types ──────────────────────────────────────────────────── */
.cell-ts { font-family: var(--font-mono); font-size: 10.5px; color: var(--text-dim); white-space: nowrap; }
.cell-msg { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 0; color: var(--text); font-size: 12px; }
.cell-msg:hover { color: var(--accent); }
.cell-uid { font-family: var(--font-mono); font-size: 10.5px; color: var(--text-dim); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cell-url { font-size: 11px; color: var(--text-dim); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cell-rid {
    font-family: var(--font-mono); font-size: 10.5px; color: var(--info);
    white-space: nowrap; cursor: pointer;
    text-underline-offset: 3px; text-decoration: underline dotted;
}
.cell-rid:hover { color: #7dc4ff; }
.cell-default { font-size: 11.5px; color: var(--text-dim); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* ── Badges ──────────────────────────────────────────────────────── */
.badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 7px; border-radius: 20px;
    font-family: var(--font-mono); font-size: 10px; font-weight: 600; letter-spacing: .04em;
    white-space: nowrap;
}
.badge::before { content: ''; width: 4px; height: 4px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
.badge-ERROR   { background: var(--error-bg);   color: var(--error);   border: 1px solid var(--error-border);   }
.badge-WARNING { background: var(--warning-bg); color: var(--warning); border: 1px solid var(--warning-border); }
.badge-INFO    { background: var(--info-bg);    color: var(--info);    border: 1px solid var(--info-border);    }
.badge-DEBUG   { background: var(--debug-bg);   color: var(--debug);   border: 1px solid var(--debug-border);   }
.badge-DEFAULT { background: rgba(255,255,255,.05); color: var(--text-dim); border: 1px solid var(--border); }

/* ── Empty state ─────────────────────────────────────────────────── */
.empty-state {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 14px;
    padding: 60px 24px; text-align: center;
}
.empty-icon { color: var(--text-muted); opacity: .5; }
.empty-icon svg { width: 40px; height: 40px; }
.empty-title { font-size: 15px; font-weight: 600; color: var(--text-dim); }
.empty-sub { font-size: 12px; color: var(--text-muted); max-width: 300px; line-height: 1.7; }
.empty-sub kbd {
    display: inline-block; padding: 1px 5px; border-radius: 4px;
    background: var(--surface3); border: 1px solid var(--border);
    font-family: var(--font-mono); font-size: 10px; color: var(--text-dim);
}

/* ── Pagination ──────────────────────────────────────────────────── */
.pagination {
    display: flex; align-items: center; justify-content: center; gap: 4px;
    padding: 12px 20px; border-top: 1px solid var(--border-dim);
    background: var(--surface); flex-shrink: 0;
}
.page-btn {
    min-width: 30px; height: 28px; padding: 0 6px;
    background: transparent; border: 1.5px solid var(--border);
    border-radius: var(--radius-sm); color: var(--text-dim);
    font-family: var(--font-mono); font-size: 11px; cursor: pointer;
    transition: all .15s; display: flex; align-items: center; justify-content: center;
}
.page-btn:hover:not(:disabled) { border-color: var(--accent); color: var(--accent); }
.page-btn.active { background: var(--accent); border-color: var(--accent); color: #021a14; font-weight: 700; }
.page-btn:disabled { opacity: .3; cursor: default; }
.page-dot { color: var(--text-muted); font-size: 13px; padding: 0 2px; }

/* ── Slide-over Drawer ───────────────────────────────────────────── */
.drawer-backdrop {
    position: fixed; inset: 0; z-index: 200;
    background: rgba(0,0,0,.62); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
    opacity: 0; pointer-events: none;
    transition: opacity .28s ease;
}
.drawer-backdrop.open { opacity: 1; pointer-events: auto; }

.drawer {
    position: fixed; top: 0; right: 0; bottom: 0; z-index: 201;
    width: min(540px, 100vw);
    background: var(--surface); border-left: 1px solid var(--border);
    box-shadow: -12px 0 48px rgba(0,0,0,.55);
    display: flex; flex-direction: column;
    transform: translateX(100%);
    transition: transform .3s cubic-bezier(.4, 0, .2, 1);
    will-change: transform;
}
.drawer.open { transform: translateX(0); }

.drawer-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid var(--border);
    flex-shrink: 0; background: var(--surface2);
}
.drawer-title-wrap { display: flex; align-items: center; gap: 10px; }
.drawer-title { font-size: 13px; font-weight: 600; color: var(--text); font-family: var(--font-ui); }
.drawer-close {
    width: 28px; height: 28px; background: var(--surface3); border: 1.5px solid var(--border);
    border-radius: var(--radius-sm); color: var(--text-muted); cursor: pointer;
    display: flex; align-items: center; justify-content: center; transition: all .15s; flex-shrink: 0;
}
.drawer-close:hover { border-color: var(--error-border); color: var(--error); background: var(--error-bg); }
.drawer-close svg { width: 13px; height: 13px; }
.drawer-body { overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 16px; flex: 1; }
.drawer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 20px; }
.drawer-field { display: flex; flex-direction: column; gap: 4px; }
.drawer-field.full { grid-column: 1 / -1; }
.drawer-field label {
    font-size: 9px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase;
    color: var(--text-muted); font-family: var(--font-ui);
}
.drawer-field .val {
    font-family: var(--font-mono); font-size: 11.5px; color: var(--text);
    word-break: break-all; line-height: 1.5;
}
.drawer-divider { border: none; border-top: 1px solid var(--border); }
.drawer-section-label { font-size: 9px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--text-muted); }
.drawer-raw {
    background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm);
    padding: 14px; font-family: var(--font-mono); font-size: 11px;
    color: var(--text-dim); overflow-x: auto; white-space: pre; line-height: 1.65;
    flex: 1; min-height: 0; tab-size: 2;
}
/* JSON syntax highlight */
.json-key     { color: var(--text-dim); }
.json-str     { color: #86efac; }
.json-num     { color: #fda4af; }
.json-bool    { color: #f9a8d4; }
.json-null    { color: var(--text-muted); }
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="header-brand">
        <div class="header-dot"></div>
        <span class="header-name">Cloud<em>Watch</em> Viewer</span>
    </div>
    <div class="header-right">
        <div class="mode-seg" role="group" aria-label="Viewing mode">
            <button class="mode-opt active" id="queryModeBtn" data-mode="query" type="button">
                <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="5.5" cy="5.5" r="4"/><line x1="8.5" y1="8.5" x2="11" y2="11"/></svg>
                Query
            </button>
            <button class="mode-opt" id="liveBtn" data-mode="live" type="button">
                <span class="mode-live-dot"></span>
                Live
            </button>
        </div>
    </div>
</header>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <!-- Log Groups -->
        <div class="sidebar-section">
            <div class="section-label">
                <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="1" width="4" height="4" rx="1"/><rect x="7" y="1" width="4" height="4" rx="1"/><rect x="1" y="7" width="4" height="4" rx="1"/><rect x="7" y="7" width="4" height="4" rx="1"/></svg>
                Log Groups
            </div>
            <div class="group-list" id="groupList">
                @forelse ($logGroups as $group)
                <div class="group-item">
                    <input type="checkbox" id="grp_{{ $loop->index }}" value="{{ $group['value'] }}" checked>
                    <label for="grp_{{ $loop->index }}">
                        <span class="group-name">{{ $group['name'] }}</span>
                        <span class="group-path">{{ $group['value'] }}</span>
                    </label>
                </div>
                @empty
                <p class="group-empty">No log groups configured.<br>Edit <code>config/cloudwatch-viewer.php</code>.</p>
                @endforelse
            </div>
        </div>

        <!-- Log Level -->
        <div class="sidebar-section">
            <div class="section-label">
                <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 10L4 5l3 3 2-4 2 4"/></svg>
                Level
            </div>
            <div class="level-grid">
                @foreach (['ALL','ERROR','WARNING','INFO','DEBUG'] as $lvl)
                <button class="level-pill {{ $lvl === 'ALL' ? 'active' : '' }}" data-level="{{ $lvl }}" type="button">{{ $lvl }}</button>
                @endforeach
            </div>
        </div>

        <!-- Timezone -->
        <div class="sidebar-section">
            <div class="section-label">
                <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="6" cy="6" r="5"/><path d="M6 1c-1.5 1.5-2.5 3-2.5 5S4.5 9.5 6 11M6 1c1.5 1.5 2.5 3 2.5 5S7.5 9.5 6 11M1 6h10"/></svg>
                Timezone
            </div>
            <select id="tzSelect" class="form-input"></select>
        </div>

        <!-- Date Range -->
        <div class="sidebar-section" id="dateRangeSection">
            <div class="section-label">
                <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="2" width="10" height="9" rx="1"/><path d="M1 5h10M4 1v2M8 1v2"/></svg>
                Date Range
            </div>
            <div class="field-stack">
                <div>
                    <div class="field-label" style="margin-bottom:4px;">From</div>
                    <input type="datetime-local" id="startDate" class="form-input">
                </div>
                <div>
                    <div class="field-label" style="margin-bottom:4px;">To</div>
                    <input type="datetime-local" id="endDate" class="form-input">
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="sidebar-section">
            <div class="section-label">
                <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="5" cy="5" r="4"/><line x1="8.5" y1="8.5" x2="11" y2="11"/></svg>
                Search
            </div>
            <div class="field-stack">
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="6" cy="6" r="4.5"/><line x1="9.5" y1="9.5" x2="12.5" y2="12.5"/></svg>
                    <input type="text" id="filterMessage" class="form-input has-icon" placeholder="Message contains…">
                </div>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7" cy="4.5" r="3"/><path d="M1.5 13c0-3 2.5-5 5.5-5s5.5 2 5.5 5"/></svg>
                    <input type="text" id="filterUserId" class="form-input has-icon" placeholder="User ID">
                </div>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="5" y1="1.5" x2="3.5" y2="12.5"/><line x1="10.5" y1="1.5" x2="9" y2="12.5"/><line x1="1.5" y1="5" x2="12.5" y2="5"/><line x1="1.5" y1="9" x2="12.5" y2="9"/></svg>
                    <input type="text" id="filterRequestId" class="form-input has-icon" placeholder="Request ID">
                </div>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5.5 7a3 3 0 004.24 0l2.12-2.12a3 3 0 00-4.24-4.24L6.5 1.76"/><path d="M8.5 7a3 3 0 00-4.24 0L2.14 9.12a3 3 0 004.24 4.24L7.5 12.24"/></svg>
                    <input type="text" id="filterUrl" class="form-input has-icon" placeholder="URL / Endpoint">
                </div>
                <label class="toggle-row" style="margin-top:4px;">
                    <input type="checkbox" id="filterHasContext">
                    <span class="toggle-track"><span class="toggle-thumb"></span></span>
                    <span>Hide logs without context</span>
                </label>
            </div>
        </div>

        <!-- Actions -->
        <div class="sidebar-footer">
            <button class="btn btn-primary" id="searchBtn" type="button">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="4.5"/><line x1="9.5" y1="9.5" x2="12.5" y2="12.5"/></svg>
                Search Logs
            </button>
            <button class="btn btn-ghost" id="resetBtn" type="button">Reset Filters</button>
        </div>

    </aside>

    <!-- MAIN -->
    <main class="main" id="mainArea">

        <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>

        <div class="alert alert-error" id="errorBanner" hidden>
            <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7" cy="7" r="6"/><line x1="7" y1="4" x2="7" y2="7.5"/><circle cx="7" cy="10" r=".5" fill="currentColor"/></svg>
            <span id="errorText"></span>
        </div>

        <div class="toolbar" id="toolbar" hidden>
            <div class="toolbar-count" id="toolbarCount"></div>
            <div class="toolbar-meta" id="toolbarMeta"></div>
        </div>

        <div class="table-wrap" id="tableWrapper" hidden>
            <table>
                <thead>
                    @php
                        $colWidths = [
                            '@timestamp'         => '158px',
                            'level_name'         => '92px',
                            'message'            => '',
                            'context.request_id' => '100px',
                            'context.user_id'    => '108px',
                            'context.url'        => '160px',
                            '@logStream'         => '140px',
                        ];
                    @endphp
                    <tr>
                        @foreach ($columns as $col)
                            @php $w = $colWidths[$col['field']] ?? '120px'; @endphp
                            <th style="{{ $w ? 'width:'.$w.';' : '' }}">{{ $col['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody id="logTableBody"></tbody>
            </table>
        </div>

        <div class="pagination" id="paginationBar" hidden></div>

        <div class="empty-state" id="emptyState">
            <div class="empty-icon">
                <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.2" opacity=".6">
                    <rect x="4" y="8" width="32" height="26" rx="3"/>
                    <path d="M4 14h32M12 8v6M28 8v6"/>
                    <line x1="10" y1="22" x2="22" y2="22" stroke-linecap="round"/>
                    <line x1="10" y1="27" x2="18" y2="27" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="empty-title" id="emptyTitle">No logs loaded</div>
            <div class="empty-sub" id="emptySub">Select log groups, configure your filters, then press <kbd>Search Logs</kbd> or hit <kbd>↵</kbd> in any field.</div>
        </div>

    </main>
</div>

<!-- SLIDE-OVER DRAWER -->
<div class="drawer-backdrop" id="drawerBackdrop"></div>
<div class="drawer" id="logDrawer" role="dialog" aria-modal="true" aria-label="Log Entry" tabindex="-1">
    <div class="drawer-header">
        <div class="drawer-title-wrap">
            <span id="drawerBadge"></span>
            <span class="drawer-title">Log Entry</span>
        </div>
        <button class="drawer-close" id="drawerClose" type="button" aria-label="Close">
            <svg viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="1" y1="1" x2="12" y2="12"/><line x1="12" y1="1" x2="1" y2="12"/></svg>
        </button>
    </div>
    <div class="drawer-body">
        <div class="drawer-grid" id="drawerFields"></div>
        <hr class="drawer-divider">
        <div class="drawer-section-label">Raw JSON</div>
        <pre class="drawer-raw" id="drawerRaw"></pre>
    </div>
</div>

<script>
(function () {
    'use strict';

    // ── Constants ──────────────────────────────────────────────
    const PAGE_SIZE  = 25;
    const FETCH_URL  = '{{ route('cloudwatch-viewer.fetch') }}';
    const STREAM_URL = '{{ route('cloudwatch-viewer.live') }}';
    const COLUMNS    = @json($columns);

    // ── State ──────────────────────────────────────────────────
    let activeTimezone  = Intl.DateTimeFormat().resolvedOptions().timeZone;
    let activeLevel     = 'ALL';
    let allLogs         = [];
    let currentPage     = 1;
    let liveInterval    = null;
    let liveNextStartMs = null;
    let fetchController = null;

    // ── DOM refs ───────────────────────────────────────────────
    const $ = id => document.getElementById(id);
    const toolbar      = $('toolbar');
    const toolbarCount = $('toolbarCount');
    const toolbarMeta  = $('toolbarMeta');
    const tableWrapper = $('tableWrapper');
    const paginationBar= $('paginationBar');
    const emptyState   = $('emptyState');
    const tbody        = $('logTableBody');
    const progressFill = $('progressFill');
    const errorBanner  = $('errorBanner');
    const errorText    = $('errorText');
    const drawer       = $('logDrawer');
    const drawerBackdrop = $('drawerBackdrop');
    const searchBtn    = $('searchBtn');
    const liveBtn      = $('liveBtn');
    const queryModeBtn = $('queryModeBtn');
    const dateRangeSec = $('dateRangeSection');

    // ── Security: XSS escape ───────────────────────────────────
    const escHtml = str => str == null ? '' : String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');

    // ── JSON syntax highlighter ────────────────────────────────
    function highlightJson(raw) {
        return escHtml(raw)
            .replace(/("(?:[^"\\]|\\.)*")\s*:/g, '<span class="json-key">$1</span>:')
            .replace(/:\s*("(?:[^"\\]|\\.)*")/g, ': <span class="json-str">$1</span>')
            .replace(/:\s*(-?\d+\.?\d*(?:[eE][+-]?\d+)?)/g, ': <span class="json-num">$1</span>')
            .replace(/:\s*(true|false)/g, ': <span class="json-bool">$1</span>')
            .replace(/:\s*(null)/g, ': <span class="json-null">$1</span>');
    }

    // ── Level badge ────────────────────────────────────────────
    function levelBadge(level) {
        const map = { ERROR: 'badge-ERROR', WARNING: 'badge-WARNING', INFO: 'badge-INFO', DEBUG: 'badge-DEBUG' };
        const cls = map[(level ?? '').toUpperCase()] ?? 'badge-DEFAULT';
        return `<span class="badge ${cls}">${escHtml(level || '—')}</span>`;
    }

    // ── Timestamp formatter ────────────────────────────────────
    function formatTs(ts) {
        if (!ts) return '—';
        try {
            const iso = (ts.includes('T') ? ts : ts.replace(' ', 'T')).replace(/(\.\d+)?Z?$/, 'Z').replace('ZZ', 'Z');
            const d = new Date(iso);
            if (isNaN(d)) return escHtml(ts);
            return new Intl.DateTimeFormat('sv', {
                timeZone: activeTimezone,
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit',
            }).format(d);
        } catch { return escHtml(ts); }
    }

    // ── Timezone ↔ datetime-local converters ───────────────────
    function utcSecToDatetimeLocal(unixSec, tz) {
        return new Intl.DateTimeFormat('sv', {
            timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit',
        }).format(new Date(unixSec * 1000)).replace(' ', 'T');
    }

    function datetimeLocalToUnixSec(str, tz) {
        if (!str) return null;
        const base = new Date(str + ':00Z');
        const inTz = new Intl.DateTimeFormat('sv', {
            timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit',
        }).format(base).replace(' ', 'T');
        const diff = base.getTime() - new Date(inTz + 'Z').getTime();
        return Math.floor((base.getTime() + diff) / 1000);
    }

    // ── Cell renderer ──────────────────────────────────────────
    function renderCell(field, val, idx) {
        const v = val ?? '';
        switch (field) {
            case '@timestamp':
                return `<td class="cell-ts">${escHtml(formatTs(v))}</td>`;
            case 'level_name':
                return `<td>${levelBadge((v).toUpperCase())}</td>`;
            case 'message':
                return `<td class="cell-msg" data-idx="${idx}" title="${escHtml(v)}">${escHtml(v || '—')}</td>`;
            case 'context.request_id': {
                const rid = v;
                return `<td class="cell-rid" data-rid="${escHtml(rid)}" title="${escHtml(rid)}">${escHtml(rid ? rid.slice(0, 8) : '—')}</td>`;
            }
            case 'context.user_id':
                return `<td class="cell-uid" title="${escHtml(v)}">${escHtml(v || '—')}</td>`;
            case 'context.url':
                return `<td class="cell-url" title="${escHtml(v)}">${escHtml(v || '—')}</td>`;
            default:
                return `<td class="cell-default" title="${escHtml(v)}">${escHtml(v || '—')}</td>`;
        }
    }

    // ── Default dates ──────────────────────────────────────────
    function setDefaultDates() {
        const nowSec = Math.floor(Date.now() / 1000);
        $('startDate').value = utcSecToDatetimeLocal(nowSec - 24 * 3600, activeTimezone);
        $('endDate').value   = utcSecToDatetimeLocal(nowSec, activeTimezone);
    }

    // ── Timezone selector ──────────────────────────────────────
    function buildTimezoneSelect() {
        const zones = [
            'UTC',
            'America/New_York','America/Chicago','America/Denver','America/Los_Angeles',
            'America/Anchorage','Pacific/Honolulu','America/Phoenix',
            'America/Toronto','America/Vancouver','America/Sao_Paulo',
            'America/Argentina/Buenos_Aires','America/Mexico_City','America/Bogota',
            'Europe/London','Europe/Dublin','Europe/Lisbon',
            'Europe/Paris','Europe/Berlin','Europe/Rome','Europe/Madrid',
            'Europe/Amsterdam','Europe/Stockholm','Europe/Helsinki',
            'Europe/Athens','Europe/Istanbul','Europe/Moscow',
            'Africa/Cairo','Africa/Johannesburg','Africa/Lagos','Africa/Nairobi',
            'Asia/Dubai','Asia/Riyadh','Asia/Tehran',
            'Asia/Karachi','Asia/Kolkata','Asia/Dhaka',
            'Asia/Bangkok','Asia/Singapore','Asia/Shanghai',
            'Asia/Hong_Kong','Asia/Tokyo','Asia/Seoul',
            'Australia/Perth','Australia/Adelaide','Australia/Sydney',
            'Pacific/Auckland','Pacific/Fiji',
        ];
        const now = new Date();
        const entries = zones.map(tz => {
            try {
                const localStr = new Intl.DateTimeFormat('sv', {
                    timeZone: tz, year:'numeric', month:'2-digit', day:'2-digit',
                    hour:'2-digit', minute:'2-digit', second:'2-digit',
                }).format(now).replace(' ', 'T');
                const offsetMin = Math.round((new Date(localStr + 'Z') - now) / 60000);
                const sign = offsetMin >= 0 ? '+' : '-';
                const abs  = Math.abs(offsetMin);
                const label = `(UTC${sign}${String(Math.floor(abs/60)).padStart(2,'0')}:${String(abs%60).padStart(2,'0')}) ${tz.replace(/_/g,' ')}`;
                return { tz, label, offsetMin };
            } catch { return { tz, label: tz, offsetMin: 0 }; }
        }).sort((a, b) => a.offsetMin - b.offsetMin || a.tz.localeCompare(b.tz));

        const sel = $('tzSelect');
        if (!zones.includes(activeTimezone)) {
            const opt = new Option(activeTimezone.replace(/_/g,' '), activeTimezone, true, true);
            sel.appendChild(opt);
        }
        entries.forEach(({ tz, label }) => {
            const opt = new Option(label, tz, false, tz === activeTimezone);
            sel.appendChild(opt);
        });
    }

    function updateDateInputsForTimezone(newTz) {
        const startEl = $('startDate'), endEl = $('endDate');
        const sTs = startEl.value ? datetimeLocalToUnixSec(startEl.value, activeTimezone) : null;
        const eTs = endEl.value   ? datetimeLocalToUnixSec(endEl.value,   activeTimezone) : null;
        activeTimezone = newTz;
        if (sTs) startEl.value = utcSecToDatetimeLocal(sTs, newTz);
        if (eTs) endEl.value   = utcSecToDatetimeLocal(eTs, newTz);
    }

    // ── Build request params ───────────────────────────────────
    function buildParams() {
        const p = new URLSearchParams();
        document.querySelectorAll('#groupList input[type="checkbox"]:checked')
            .forEach(cb => p.append('log_groups[]', cb.value));
        p.set('level',      activeLevel);
        p.set('message',    $('filterMessage').value.trim());
        p.set('user_id',    $('filterUserId').value.trim());
        p.set('request_id', $('filterRequestId').value.trim());
        p.set('url',        $('filterUrl').value.trim());
        p.set('has_context', $('filterHasContext').checked ? '1' : '0');
        const sTs = datetimeLocalToUnixSec($('startDate').value, activeTimezone);
        const eTs = datetimeLocalToUnixSec($('endDate').value,   activeTimezone);
        if (sTs != null) p.set('start_ts', sTs);
        if (eTs != null) p.set('end_ts',   eTs);
        return p;
    }

    // ── Fetch (Insights search) ────────────────────────────────
    async function fetchLogs() {
        if (liveInterval) stopLive();
        const params = buildParams();
        if (!params.getAll('log_groups[]').length) { showError('Select at least one log group.'); return; }

        fetchController?.abort();
        fetchController = new AbortController();

        setLoading(true);
        hideError();
        hideResults();

        try {
            const url = new URL(FETCH_URL, location.origin);
            url.search = params.toString();
            const t0  = performance.now();
            const res = await fetch(url, { signal: fetchController.signal });
            const ms  = Math.round(performance.now() - t0);
            const data = await res.json();

            if (!res.ok) { showError(data.error ?? `Server error (${res.status})`); return; }

            allLogs     = data.logs ?? [];
            currentPage = 1;
            renderTable();
            if (allLogs.length) toolbarMeta.textContent = `${ms}ms`;
        } catch (err) {
            if (err.name !== 'AbortError') showError('Network error: ' + err.message);
        } finally {
            setLoading(false);
        }
    }

    // ── Render table ───────────────────────────────────────────
    function renderTable() {
        if (!allLogs.length) {
            toolbar.hidden = tableWrapper.hidden = paginationBar.hidden = true;
            emptyState.hidden = false;
            $('emptyTitle').textContent = 'No results found';
            $('emptySub').textContent = 'Try broadening your filters or extending the date range.';
            return;
        }

        emptyState.hidden = true;
        toolbar.hidden    = false;
        tableWrapper.hidden = false;

        const total     = Math.ceil(allLogs.length / PAGE_SIZE);
        currentPage     = Math.max(1, Math.min(currentPage, total));
        const start     = (currentPage - 1) * PAGE_SIZE;
        const page      = allLogs.slice(start, start + PAGE_SIZE);

        if (!liveInterval) {
            toolbarCount.innerHTML = `Showing <strong>${start + 1}–${start + page.length}</strong> of <strong>${allLogs.length}</strong> results`;
        }

        tbody.innerHTML = page.map((log, i) => {
            const level = (log.level_name ?? '').toUpperCase();
            const cells = COLUMNS.map(col => renderCell(col.field, log[col.field], start + i)).join('');
            return `<tr data-level="${escHtml(level)}">${cells}</tr>`;
        }).join('');

        renderPagination(total);
    }

    // ── Event delegation on tbody ──────────────────────────────
    tbody.addEventListener('click', e => {
        const msg = e.target.closest('.cell-msg');
        if (msg) { openDrawer(allLogs[+msg.dataset.idx]); return; }

        const rid = e.target.closest('.cell-rid');
        if (rid?.dataset.rid) {
            $('filterRequestId').value = rid.dataset.rid;
            fetchLogs();
        }
    });

    // ── Pagination ─────────────────────────────────────────────
    function renderPagination(total) {
        if (total <= 1) { paginationBar.hidden = true; return; }
        paginationBar.hidden = false;

        const pages = smartPages(currentPage, total);
        paginationBar.innerHTML = [
            `<button class="page-btn" id="pgPrev" ${currentPage===1 ? 'disabled' : ''}>‹</button>`,
            ...pages.map(p => p === '…'
                ? `<span class="page-dot">…</span>`
                : `<button class="page-btn ${p===currentPage?'active':''}" data-p="${p}">${p}</button>`),
            `<button class="page-btn" id="pgNext" ${currentPage===total ? 'disabled' : ''}>›</button>`,
        ].join('');

        paginationBar.querySelector('#pgPrev').addEventListener('click', () => goPage(currentPage - 1, total));
        paginationBar.querySelector('#pgNext').addEventListener('click', () => goPage(currentPage + 1, total));
        paginationBar.querySelectorAll('[data-p]').forEach(b => b.addEventListener('click', () => goPage(+b.dataset.p, total)));
    }

    function smartPages(cur, total) {
        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
        const p = [1];
        if (cur > 3) p.push('…');
        for (let i = Math.max(2, cur-1); i <= Math.min(total-1, cur+1); i++) p.push(i);
        if (cur < total - 2) p.push('…');
        p.push(total);
        return p;
    }

    function goPage(p, total) {
        currentPage = Math.max(1, Math.min(p, total));
        renderTable();
        document.getElementById('mainArea').scrollTop = 0;
    }

    // ── Modal ──────────────────────────────────────────────────
    const FIELD_LABELS = {
        '@timestamp':'Timestamp','level_name':'Level','message':'Message',
        'context.user_id':'User ID','context.request_id':'Request ID',
        'context.method':'Method','context.url':'URL',
        'context.ip':'IP Address','context.environment':'Environment',
        '@logStream':'Log Stream',
    };

    function openDrawer(log) {
        $('drawerBadge').innerHTML = levelBadge((log.level_name ?? '').toUpperCase());
        $('drawerFields').innerHTML = Object.entries(log)
            .filter(([, v]) => v != null && v !== '')
            .map(([k, v]) => {
                const label = FIELD_LABELS[k] ?? k;
                return `<div class="drawer-field${k==='message'?' full':''}">
                    <label>${escHtml(label)}</label>
                    <div class="val">${escHtml(String(v))}</div>
                </div>`;
            }).join('');
        $('drawerRaw').innerHTML = highlightJson(JSON.stringify(log, null, 2));
        drawerBackdrop.classList.add('open');
        drawer.classList.add('open');
        drawer.focus();
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        drawerBackdrop.classList.remove('open');
    }

    $('drawerClose').addEventListener('click', closeDrawer);
    drawerBackdrop.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });

    // ── Live streaming ─────────────────────────────────────────
    function startLive() {
        if (liveInterval) return;
        if (!document.querySelectorAll('#groupList input:checked').length) {
            showError('Select at least one log group.'); return;
        }
        allLogs = []; currentPage = 1;
        liveNextStartMs = Date.now() - 60_000;
        hideError(); setLiveActive(true);
        pollLive();
        liveInterval = setInterval(pollLive, 5000);
    }

    function stopLive() {
        clearInterval(liveInterval); liveInterval = null;
        setLiveActive(false);
    }

    async function pollLive() {
        const params = buildParams();
        params.delete('start_ts'); params.delete('end_ts');
        params.set('start_ts_ms', liveNextStartMs);
        try {
            const url = new URL(STREAM_URL, location.origin);
            url.search = params.toString();
            const res  = await fetch(url);
            const data = await res.json();

            if (!res.ok) { showError(data.error ?? `Poll error (${res.status})`); stopLive(); return; }

            if (data.warnings?.length) showError('Warning: ' + data.warnings.join(' | '));
            else hideError();

            if (data.next_start_ms) liveNextStartMs = data.next_start_ms;

            if (data.events?.length) {
                const n = data.events.length;
                allLogs = [...data.events, ...allLogs].slice(0, 2000);
                currentPage = 1;
                renderTable();
                flashRows(n);
            }
            toolbarCount.innerHTML = `<strong>${allLogs.length}</strong> logs captured`;
        } catch (err) {
            console.warn('Live poll:', err.message);
        }
    }

    function flashRows(count) {
        Array.from(tbody.rows).slice(0, count).forEach(row => {
            row.getAnimations().forEach(a => a.cancel());
            row.animate(
                [{ backgroundColor: 'rgba(0,212,170,.1)' }, { backgroundColor: 'transparent' }],
                { duration: 1600, easing: 'ease-out', fill: 'forwards' }
            );
        });
    }

    function setLiveActive(on) {
        liveBtn.classList.toggle('active', on);
        queryModeBtn.classList.toggle('active', !on);
        searchBtn.disabled = on;
        dateRangeSec.classList.toggle('muted', on);
        if (on) { emptyState.hidden = true; toolbar.hidden = false; tableWrapper.hidden = false; }
    }

    liveBtn.addEventListener('click',      () => { if (!liveInterval) startLive(); });
    queryModeBtn.addEventListener('click', () => { if (liveInterval)  stopLive();  });

    // ── Loading state ──────────────────────────────────────────
    function setLoading(on) {
        progressFill.classList.toggle('running', on);
        searchBtn.disabled = on;
        searchBtn.textContent = on ? 'Searching…' : '';
        if (!on) {
            searchBtn.insertAdjacentHTML('afterbegin',
                `<svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="4.5"/><line x1="9.5" y1="9.5" x2="12.5" y2="12.5"/></svg> Search Logs`);
        }
    }

    function showError(msg) { errorText.textContent = msg; errorBanner.hidden = false; }
    function hideError()          { errorBanner.hidden = true; }
    function hideResults()        { toolbar.hidden = tableWrapper.hidden = paginationBar.hidden = true; }

    // ── Level pill selection ───────────────────────────────────
    document.querySelectorAll('.level-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('.level-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            activeLevel = pill.dataset.level;
        });
    });

    // ── Timezone change ────────────────────────────────────────
    $('tzSelect').addEventListener('change', function () {
        updateDateInputsForTimezone(this.value);
        if (allLogs.length) renderTable();
    });

    // ── Reset ──────────────────────────────────────────────────
    $('resetBtn').addEventListener('click', () => {
        if (liveInterval) stopLive();
        document.querySelectorAll('#groupList input[type="checkbox"]').forEach(cb => cb.checked = true);
        document.querySelectorAll('.level-pill').forEach(p => p.classList.toggle('active', p.dataset.level === 'ALL'));
        activeLevel = 'ALL';
        setDefaultDates();
        ['filterMessage','filterUserId','filterRequestId','filterUrl'].forEach(id => $(id).value = '');
        $('filterHasContext').checked = false;
    });

    // ── Search button + Enter key ──────────────────────────────
    searchBtn.addEventListener('click', fetchLogs);
    ['filterMessage','filterUserId','filterRequestId','filterUrl','startDate','endDate'].forEach(id => {
        $(id).addEventListener('keydown', e => { if (e.key === 'Enter') fetchLogs(); });
    });

    // ── Init ───────────────────────────────────────────────────
    buildTimezoneSelect();
    setDefaultDates();

})();
</script>
</body>
</html>
