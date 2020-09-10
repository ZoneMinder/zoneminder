<?php

// Returns the html representing the Options menu item
function getOptionHelpHTML($ZMoptionHelpIndex) {
  $result = '';
  $optionHelpIndex = preg_replace('/^ZM_/', '', $ZMoptionHelpIndex);
  
  if ( !empty($OLANG[$optionHelpIndex]) ) {
    $optionHelpText = $OLANG[$optionHelpIndex]['Help'];
  } else {
    $optionHelpText = dbFetchOne('SELECT Help FROM Config WHERE Name=?', 'Help', array($ZMoptionHelpIndex));
  }
  $optionHelpText = validHtmlStr($optionHelpText);
  $optionHelpText = preg_replace('/~~/', '<br/>', $optionHelpText );
  $optionHelpText = preg_replace('/\[(.+)\]\((.+)\)/', '<a href="$2" target="_blank">$1</a>', $optionHelpText);

  $result .= '<div id="optionhelp" class="modal" tabindex="-1" role="dialog">'.PHP_EOL;
    $result .= '<div class="modal-dialog" role="document">'.PHP_EOL;
      $result .= '<div class="modal-content">'.PHP_EOL
        $result .= '<div class="modal-header">'.PHP_EOL
          $result .= '<h5 class="modal-title">' .translate('OptionHelp'). '</h5>'.PHP_EOL;
          $result .= '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'.PHP_EOL;
            $result .= '<span aria-hidden="true">&times;</span>'.PHP_EOL;
          $result .= '</button>'.PHP_EOL;
        $result .= '</div>'.PHP_EOL;
        $result .= '<div class="modal-body">'.PHP_EOL;
          $result .= '<h3>' .validHtmlStr($ZMoptionHelpIndex). '</h3>'.PHP_EOL;
          $result .= '<p class="textblock">' .$optionHelpText. '</p>'.PHP_EOL;
        $result .= '</div>'.PHP_EOL;
        $result .= '<div class="modal-footer">'.PHP_EOL;
          $result .= '<button type="button" id="ohCloseBtn" class="btn btn-secondary" data-dismiss="modal">Close</button>'.PHP_EOL;
        $result .= '</div>'.PHP_EOL;
      $result .= '</div>'.PHP_EOL;
    $result .= '</div>'.PHP_EOL;
  $result .= '</div>'.PHP_EOL;
  
  return $result;
}

