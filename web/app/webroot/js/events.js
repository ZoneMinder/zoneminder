$(document).ready(function() {
   $( "#selectable" ).selectable({
     stop: function() {
       $base_url = '/events/index/';
       $( ".ui-selected", this ).each(function() {
         var index = $( "#selectable li" ).index( this );
         $monitor_id = $(this).attr('id').split('_');
         $base_url = $base_url + 'MonitorId:'+$monitor_id[1]+'/';
       });
       $('#Events').load($base_url + ' #Events');
     }
   });
});
