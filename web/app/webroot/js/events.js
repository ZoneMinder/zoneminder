$(document).ready(function() {
  $('[id^=Monitor_]').click(function() {
    $('h2').append('<img src="/img/loading.gif" alt="loading" width="25px" />');
    $base_url = '/events/index/';

    if ($(this).attr('class') == 'selected') {
      $(this).toggleClass('selected');
    } else {
      $(this).toggleClass('selected');
    }

    $('[id^=Monitor_]').each(function() {
      if ($(this).attr('class') == 'selected' ){
        $monitor_id = $(this).attr('id').split('_');
        $base_url = $base_url + 'MonitorId:'+$monitor_id[1]+'/';
      }
    });

    $('#Events').load($base_url + ' #Events', function(){
      $('h2 img').detach();
    });
  });
});
