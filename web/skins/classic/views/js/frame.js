function changeScale() {
  let scale = $j('#scale').val();
  let img = $j('#frameImg');
  let controlsLinks = {
      next: $j('#nextLink'),
      prev: $j('#prevLink'),
      first: $j('#firstLink'),
      last: $j('#lastLink')
      }

  if ( img ) {
    let baseWidth = $j('#base_width').val();
    let baseHeight = $j('#base_height').val();
    if ( scale == 'auto' ) {
      let newSize = scaleToFit(baseWidth, baseHeight, img, $j('#controls'));
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
  Cookie.write( 'zmWatchScale', scale, { duration: 10*365 } );
  $j.each(controlsLinks, function(k, anchor) {  //Make frames respect scale choices
    anchor.prop('href', anchor.prop('href').replace(/scale=.*&/, 'scale=' + scale + '&'));

  });
}

if (scale == 'auto') $j(document).ready(changeScale);
