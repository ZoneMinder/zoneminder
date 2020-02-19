function changeScale() {
  var scale = $j('#scale').val();
  var img = $j('#frameImg');
  var controlsLinks = {
    next: $j('#nextLink'),
    prev: $j('#prevLink'),
    first: $j('#firstLink'),
    last: $j('#lastLink')
  };

  if ( img ) {
    var baseWidth = $j('#base_width').val();
    var baseHeight = $j('#base_height').val();
    if ( scale == 'auto' ) {
      var newSize = scaleToFit(baseWidth, baseHeight, img, $j('#controls'));
      newWidth = newSize.width;
      newHeight = newSize.height;
      autoScale = newSize.autoScale;
    } else {
      $j(window).off('resize', endOfResize); //remove resize handler when Scale to Fit is not active
      newWidth = baseWidth * scale / SCALE_BASE;
      newHeight = baseHeight * scale / SCALE_BASE;
    }
    img.css('width', newWidth + 'px');
    img.css('height', newHeight + 'px');
  }
  Cookie.write( 'zmWatchScale', scale, {duration: 10*365} );
  $j.each(controlsLinks, function(k, anchor) { //Make frames respect scale choices
    if ( anchor ) {
      anchor.prop('href', anchor.prop('href').replace(/scale=.*&/, 'scale=' + scale + '&'));
    }
  });
}

if ( scale == 'auto' ) {
  $j(document).ready(changeScale);
}

document.addEventListener('DOMContentLoaded', function onDCL() {
  document.getElementById('scale').addEventListener('change', changeScale);
});
