var restartWarning = <?php echo empty($restartWarning)?'false':'true' ?>;
if ( restartWarning )
{
    alert( "<?php echo translate('OptionRestartWarning') ?>" );
}

function toggleAdvanced() {
    var rows = document.getElementsByClassName('advanced');
    var text = document.querySelector('#btnToggleAdvanced');

    if (text.value === 'Show Advanced') {
      var r = Array.prototype.filter.call(rows, function(row) {
        row.style.display = 'table-row';
      });

      text.value = 'Hide Advanced';
    } else {
      var r = Array.prototype.filter.call(rows, function(row) {
        row.style.display = 'none';
      });

      text.value = 'Show Advanced';
    }
}
