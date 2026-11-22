// Builds the tags table from ajax/tags.php. Events counts are links to the
// events list filtered to that tag.
function tagEventsUrl(tag) {
  return '?view=events&filter[Query][terms][0][attr]=Tags&filter[Query][terms][0][op]=%3D%5B%5D&filter[Query][terms][0][val]=' + encodeURIComponent(tag.Id);
}

function tagRow(tag) {
  const row = $j('<tr>').data('tag', tag);
  const nameCell = $j('<td>');
  const eventCount = parseInt(tag.Events);
  if (canEdit.Events) {
    nameCell.append($j('<input type="text" class="form-control tagName" maxlength="64">').val(tag.Name));
  } else {
    nameCell.text(tag.Name);
  }
  row.append(nameCell);
  const countCell = $j('<td>');
  if (eventCount > 0) {
    countCell.append($j('<a>').attr('href', tagEventsUrl(tag)).text(eventCount));
  } else {
    countCell.text(eventCount);
  }
  row.append(countCell);
  const actions = $j('<td>');
  if (canEdit.Events) {
    actions.append($j('<button type="button" class="btn btn-normal tagSave" title="' + tagStrings.Save + '"><i class="fa fa-save"></i></button>'));
    // Only a tag on no event can be deleted.
    if (eventCount < 1) {
      actions.append(' ', $j('<button type="button" class="btn btn-danger tagDelete" title="' + tagStrings.Delete + '"><i class="fa fa-trash"></i></button>'));
    }
  }
  row.append(actions);
  return row;
}

function loadTags() {
  $j.getJSON(thisUrl + '?request=tags&action=gettags')
      .done(function(data) {
        const body = $j('#tagsBody').empty();
        data.response.forEach(function(tag) {
          body.append(tagRow(tag));
        });
      })
      .fail(logAjaxFail);
}

function tagRequest(action, tag, extra) {
  return $j.getJSON(thisUrl, Object.assign({request: 'tags', action: action, tid: tag.Id}, extra));
}

function initPage() {
  document.getElementById('backBtn').addEventListener('click', function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });
  $j('#backBtn').prop('disabled', !document.referrer.length);
  document.getElementById('refreshBtn').addEventListener('click', function onRefreshClick(evt) {
    evt.preventDefault();
    loadTags();
  });

  $j('#tagsBody').on('click', '.tagSave', function() {
    const row = $j(this).closest('tr');
    tagRequest('renametag', row.data('tag'), {tname: row.find('.tagName').val()})
        .done(function(data) {
          if (data.result == 'Error') {
            alert(data.message);
          }
          loadTags();
        })
        .fail(logAjaxFail);
  });
  $j('#tagsBody').on('click', '.tagDelete', function() {
    if (!confirm(tagStrings.ConfirmDeleteTag)) return;
    tagRequest('deletetag', $j(this).closest('tr').data('tag'), {})
        .done(function(data) {
          if (data.result == 'Error') {
            alert(data.message);
          }
          loadTags();
        })
        .fail(logAjaxFail);
  });
  loadTags();
}

$j(document).ready(initPage);
