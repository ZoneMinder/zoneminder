//
// ZoneMinder Monitor Encoder Templates
//
// UMD-pattern module: pure functions are exposed as named members on
// window.ZM_EncoderTemplates in browsers, and via module.exports in Node
// for unit testing. The DOM wiring (init) only runs in the browser.
//
(function(global) {
  'use strict';

  // Pairs separated by any of \n, , or #, matching av_dict_parse_string
  // in src/zm_videostore.cpp:115. Each pair is "key=value" split on the
  // first =. Whitespace around key and value is trimmed. Pairs without
  // = and pairs with empty key are dropped.
  function parseParams(text) {
    if (!text) return [];
    const out = [];
    const pairs = String(text).split(/[#,\n]/);
    for (const p of pairs) {
      const idx = p.indexOf('=');
      if (idx < 0) continue;
      const key = p.slice(0, idx).trim();
      const value = p.slice(idx + 1).trim();
      if (key) out.push({key: key, value: value});
    }
    return out;
  }

  // Merge templateParams (object) into existing (parseParams output).
  // Keys present in both: existing position retained, value replaced.
  // Keys only in template: appended at the end.
  // Keys only in existing: kept as-is.
  // Values are String()-coerced so numeric template values (e.g. crf: 23)
  // serialize cleanly via the existing key=value text format.
  // Returns a new array; does not mutate either input.
  function mergeParams(existing, templateParams) {
    const result = existing.map(function(e) {
      return {key: e.key, value: e.value};
    });
    const positions = Object.create(null);
    for (let i = 0; i < result.length; i++) positions[result[i].key] = i;
    for (const k in templateParams) {
      if (!Object.prototype.hasOwnProperty.call(templateParams, k)) continue;
      const v = String(templateParams[k]);
      if (k in positions) {
        result[positions[k]].value = v;
      } else {
        positions[k] = result.length;
        result.push({key: k, value: v});
      }
    }
    return result;
  }

  // Inverse of parseParams. Joins entries with \n in "key=value" form,
  // one per line. Empty array serializes to empty string.
  function serializeParams(arr) {
    if (!arr || !arr.length) return '';
    return arr.map(function(e) {
      return e.key + '=' + e.value;
    }).join('\n');
  }

  // Returns the list of keys present in `parsed` that are not in
  // `valid_keys` for the given encoder. Returns [] when the encoder has
  // no entry in `templates` (no opinion). Each unknown key reported once.
  // Uses Object.create(null)-backed maps so prototype-named keys
  // (constructor, toString, etc.) don't false-positive via inheritance.
  function lint(parsed, encoder, templates) {
    if (!encoder || !templates || !templates[encoder]) return [];
    const valid = templates[encoder].valid_keys || [];
    const validSet = Object.create(null);
    for (let i = 0; i < valid.length; i++) validSet[valid[i]] = true;
    const seen = Object.create(null);
    const out = [];
    for (let i = 0; i < parsed.length; i++) {
      const k = parsed[i].key;
      if (!validSet[k] && !seen[k]) {
        seen[k] = true;
        out.push(k);
      }
    }
    return out;
  }

  // ---- DOM wiring (browser only) -----------------------------------------

  let lastAppliedName = null;

  function getTemplates() {
    return (typeof window !== 'undefined' && window.ZM_ENCODER_TEMPLATES) || {};
  }

  function findTemplate(encoder, templateId) {
    const t = getTemplates()[encoder];
    if (!t || !Array.isArray(t.templates)) return null;
    // Coerce to Number — DB ids are integers but DOM <select>.value is always
    // a string. Strict === would silently miss matches.
    const target = Number(templateId);
    for (let i = 0; i < t.templates.length; i++) {
      if (Number(t.templates[i].id) === target) return t.templates[i];
    }
    return null;
  }

  function findTemplateByName(encoder, name, templatesArg) {
    if (!name) return null;
    const all = templatesArg || getTemplates();
    const t = all[encoder];
    if (!t || !Array.isArray(t.templates)) return null;
    const target = String(name).toLowerCase();
    for (let i = 0; i < t.templates.length; i++) {
      if (String(t.templates[i].name).toLowerCase() === target) return t.templates[i];
    }
    return null;
  }

  function repopulateTemplates() {
    const encoderSel = document.getElementById('ZmEncoder');
    const templateSel = document.getElementById('EncoderTemplate');
    const row = document.getElementById('EncoderTemplateRow');
    if (!encoderSel || !templateSel || !row) return;

    const encoder = encoderSel.value;
    const t = getTemplates()[encoder];
    templateSel.innerHTML = '';
    if (!t || !Array.isArray(t.templates) || !t.templates.length) {
      row.style.display = 'none';
      return;
    }
    row.style.display = '';
    for (let i = 0; i < t.templates.length; i++) {
      const template = t.templates[i];
      const opt = document.createElement('option');
      opt.value = template.id;
      opt.textContent = template.name;
      if (template.description) opt.title = template.description;
      templateSel.appendChild(opt);
    }
  }

  function applyTemplate(templateId) {
    const encoderSel = document.getElementById('ZmEncoder');
    const textarea = document.getElementById('EncoderParameters');
    if (!encoderSel || !textarea || !templateId) return;
    const template = findTemplate(encoderSel.value, templateId);
    if (!template) return;
    const merged = mergeParams(parseParams(textarea.value), template.params);
    textarea.value = serializeParams(merged);
    lastAppliedName = template.name;
    runLint();
  }

  function runLint() {
    const encoderSel = document.getElementById('ZmEncoder');
    const textarea = document.getElementById('EncoderParameters');
    const diag = document.getElementById('EncoderParameterDiagnostics');
    if (!encoderSel || !textarea || !diag) return;
    const unknown = lint(parseParams(textarea.value), encoderSel.value, getTemplates());
    if (!unknown.length) {
      diag.textContent = '';
      diag.style.display = 'none';
      return;
    }
    diag.style.display = '';
    diag.textContent = 'Note: ' + unknown.map(function(k) {
      return '`' + k + '`';
    }).join(', ') + ' are not recognised options for `' + encoderSel.value +
        '` and will be ignored at runtime.';
  }

  function onEncoderChange() {
    const encoderSel = document.getElementById('ZmEncoder');
    if (!encoderSel) return;
    const previousName = lastAppliedName;
    repopulateTemplates();
    runLint();
    if (!previousName) return;
    const match = findTemplateByName(encoderSel.value, previousName);
    if (!match) return;
    const msg = 'You had "' + previousName + '" applied. ' +
                'Apply ' + match.name + ' for ' + encoderSel.value + '?';
    if (window.confirm(msg)) {
      applyTemplate(match.id);
    }
  }

  function init() {
    if (typeof document === 'undefined') return;
    const encoderSel = document.getElementById('ZmEncoder');
    const templateSel = document.getElementById('EncoderTemplate');
    const applyBtn = document.getElementById('ApplyEncoderTemplate');
    const textarea = document.getElementById('EncoderParameters');
    if (!encoderSel || !templateSel || !applyBtn || !textarea) return;

    encoderSel.addEventListener('change', onEncoderChange);
    applyBtn.addEventListener('click', function() {
      applyTemplate(templateSel.value);
    });
    templateSel.addEventListener('change', function() {
      const opt = templateSel.options[templateSel.selectedIndex];
      if (opt) lastAppliedName = opt.textContent;
    });
    textarea.addEventListener('input', runLint);

    repopulateTemplates();
    runLint();
  }

  const api = {
    parseParams: parseParams,
    mergeParams: mergeParams,
    serializeParams: serializeParams,
    lint: lint,
    findTemplateByName: findTemplateByName,
    init: init,
  };

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = api;
  } else {
    global.ZM_EncoderTemplates = api;
  }

  if (typeof document !== 'undefined' && document.addEventListener) {
    document.addEventListener('DOMContentLoaded', init);
  }
})(typeof window !== 'undefined' ? window : globalThis);
