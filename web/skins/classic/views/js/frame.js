function changeScale() {
  var scale = $('scale').get('value');
  var img = $('frameImg');
  if ( img ) {
    var baseWidth = $('base_width').value;
    var baseHeight = $('base_height').value;
    var newWidth = ( baseWidth * scale ) / SCALE_BASE;
    var newHeight = ( baseHeight * scale ) / SCALE_BASE;

    img.style.width = newWidth + "px";
    img.style.height = newHeight + "px";
  }
  Cookie.write( 'zmWatchScale', scale, { duration: 10*365 } );
}

