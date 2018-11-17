
$j( window ).load(function() {

  $j( "#generateButton" ).click(function(e) {
    e.preventDefault(); 
    var form = $j("#contentForm").serializeArray();
    $j( "#result" ).val( "Loading..." );
    $j.post("ajax/generate.php", form, function( data ) {
      $j( "#result" ).val( data );
    });
  });

});
