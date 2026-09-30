// Encoder Templates editor — wires the table buttons to the REST API,
// reuses the lint pure functions from window.ZM_EncoderTemplates for
// the in-modal Params textarea diagnostics.
(function() {
  'use strict';

  function api(method, url, data, onSuccess, onError) {
    const opts = {
      url: url,
      method: method,
      dataType: 'json',
    };
    if (data) opts.data = data;
    $j.ajax(opts).done(onSuccess).fail(function(jqXHR) {
      onError(jqXHR.responseJSON || {message: jqXHR.statusText});
    });
  }

  function showModal(id) {
    const url = thisUrl + '?view=request&request=modal&modal=encoderTemplate' + (id ? '&id=' + id : '');
    $j.getJSON(url).done(function(resp) {
      if (!resp || !resp.html) return;
      $j('#encoderTemplateModalContainer').html(resp.html);
      const $modal = $j('#EncoderTemplateModal');
      $modal.modal('show');
      bindModal(id);
    });
  }

  function bindModal(editingId) {
    $j('#EtParams').on('input', runLint);
    $j('#EtEncoder').on('change', runLint);
    $j('#EtSave').off('click').on('click', function() {
      saveModal(editingId);
    });
    runLint();
  }

  function runLint() {
    const encoder = $j('#EtEncoder').val();
    const text = $j('#EtParams').val();
    const parsed = ZM_EncoderTemplates.parseParams(text);
    const unknown = ZM_EncoderTemplates.lint(parsed, encoder, window.ZM_ENCODER_TEMPLATES);
    const $diag = $j('#EtParamsDiagnostics');
    if (!unknown.length) {
      $diag.hide().text('');
      return;
    }
    $diag.show().text('Note: ' + unknown.map(function(k) {
      return '`' + k + '`';
    }).join(', ')
        + ' are not recognised options for `' + encoder + '` and will be ignored at runtime.');
  }

  function saveModal(editingId) {
    const data = {
      'EncoderTemplate[Encoder]': $j('#EtEncoder').val(),
      'EncoderTemplate[Name]': $j('#EtName').val(),
      'EncoderTemplate[Description]': $j('#EtDescription').val(),
      'EncoderTemplate[Params]': $j('#EtParams').val(),
    };
    const url = Servers[serverId].urlToApi() + '/encoder_templates' + (editingId ? '/' + editingId : '') + '.json';
    const method = editingId ? 'PUT' : 'POST';
    api(method, url, data, function(resp) {
      if (resp.message === 'Saved') {
        $j('#EncoderTemplateModal').modal('hide');
        location.reload();
      } else {
        showError(resp);
      }
    }, showError);
  }

  function showError(resp) {
    const $err = $j('#encoderTemplateError');
    if (resp && resp.errors) {
      const lines = [];
      for (const field in resp.errors) {
        if (Object.prototype.hasOwnProperty.call(resp.errors, field)) {
          const fieldErrs = resp.errors[field];
          if (Array.isArray(fieldErrs)) {
            for (const msg of fieldErrs) lines.push(field + ': ' + msg);
          } else {
            lines.push(field + ': ' + fieldErrs);
          }
        }
      }
      $err.text(lines.join('\n')).show();
    } else {
      $err.text((resp && resp.message) || 'Unknown error').show();
    }
  }

  function deleteRow(id, name, encoder) {
    if (!window.confirm("Delete template '" + name + "' for " + encoder + '?')) return;
    api('DELETE', Servers[serverId].urlToApi() + '/encoder_templates/' + id + '.json', null, function(resp) {
      if (resp.message === 'Deleted') location.reload();
      else showError(resp);
    }, showError);
  }

  function init() {
    $j('#NewEncoderTemplateBtn').on('click', function() {
      showModal(null);
    });
    $j('#contentTable').on('click', '.btn-edit', function() {
      showModal(parseInt($j(this).data('tid'), 10));
    });
    $j('#contentTable').on('click', '.btn-copy', function() {
      const id = parseInt($j(this).data('tid'), 10);
      showModal(id);
      $j('#encoderTemplateModalContainer').one('shown.bs.modal', '#EncoderTemplateModal', function() {
        $j('#EtId').val('');
        $j('#EtName').val($j('#EtName').val() + ' Copy');
        $j('#EtEncoder').prop('disabled', false);
        $j('#EtSave').off('click').on('click', function() {
          saveModal(null);
        });
      });
    });
    $j('#contentTable').on('click', '.btn-delete', function() {
      const $row = $j(this).closest('tr');
      deleteRow(parseInt($row.data('tid'), 10), $row.data('name'), $row.data('encoder'));
    });
    $j('#encoderFilter').on('change', function() {
      const enc = $j(this).val();
      $j('#contentTable tbody tr').each(function() {
        $j(this).toggle(!enc || $j(this).data('encoder') === enc);
      });
    });
  }

  window.addEventListener('DOMContentLoaded', init);
})();
