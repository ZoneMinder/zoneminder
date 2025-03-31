function destroyChosen(selector = '') {
  if (typeof selector === 'string') {
    $j(selector + '.chosen').chosen('destroy');
  } else {
    if ($j(selector).hasClass('chosen')) {
      $j(selector).chosen('destroy');
    }
  }
}

function applyChosen(selector = '') {
  const limit_search_threshold = 10;
  var [obj_1, obj_2, obj_3] = '';
  destroyChosen(selector);
  if (typeof selector === 'string') {
    obj_1 = $j(selector + '.chosen').not('.chosen-full-width, .chosen-auto-width');
    obj_2 = $j(selector + '.chosen.chosen-full-width');
    obj_3 = $j(selector + '.chosen.chosen-auto-width');
  } else {
    if (!$j(selector).hasClass('chosen')) return;
    obj_1 = $j(selector).not('.chosen-full-width, .chosen-auto-width');
    obj_2 = $j(selector).hasClass('chosen-full-width') ? $j(selector) : '';
    obj_3 = $j(selector).hasClass('chosen-auto-width') ? $j(selector) : '';
  }
  if (obj_1) {
    obj_1.chosen({allow_single_deselect: true, disable_search_threshold: limit_search_threshold, search_contains: true});
  }
  if (obj_2) {
    obj_2.chosen({allow_single_deselect: true, disable_search_threshold: limit_search_threshold, search_contains: true, width: "100%"});
  }
  if (obj_3) {
    obj_3.chosen({allow_single_deselect: true, disable_search_threshold: limit_search_threshold, search_contains: true, width: "auto"});
  }
}
