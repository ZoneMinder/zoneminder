// Parse a datetime input value (ISO, or 'YYYY-MM-DD HH:mm:ss' from PHP).
function parseInputDateTime(value) {
  let dt = luxon.DateTime.fromISO(value);
  if (!dt.isValid) dt = luxon.DateTime.fromSQL(value);
  return dt;
}

function changeDateTime(e) {
  const minTime_element = $j('#minTime');
  const maxTime_element = $j('#maxTime');

  const minTime = parseInputDateTime(minTime_element.val());
  const maxTime = parseInputDateTime(maxTime_element.val());
  if ( minTime.isValid && maxTime.isValid && minTime > maxTime ) {
    maxTime_element.parent().addClass('has-error');
    return; // Don't reload because we have invalid datetime filter.
  } else {
    maxTime_element.parent().removeClass('has-error');
  }

  minTime_element[0].form.submit();
  return;
}

function datetime_change(newDate, oldData) {
  if (newDate !== oldData.lastVal) {
    changeDateTime();
  }
}

function initDatepickerReportEventAuditPage() {
  $j('#minTime').datetimepicker({
    timeFormat: "HH:mm:ss",
    dateFormat: "yy-mm-dd",
    maxDate: +0,
    constrainInput: false,
    onClose: datetime_change
  });

  $j('#maxTime').datetimepicker({
    timeFormat: "HH:mm:ss",
    dateFormat: "yy-mm-dd",
    minDate: $j('#minTime').val(),
    maxDate: +0,
    constrainInput: false,
    onClose: datetime_change
  });
}

function initPage() {
  if (navbar_type != 'left') {
    // If new menu is used, then Datepicker initialization occurs in main "skin.js"
    // Reinitialization is not allowed because the 'Destroy' method is missing.
    initDatepickerReportEventAuditPage();
  }
}

// Kick everything off
window.addEventListener( 'DOMContentLoaded', initPage );
