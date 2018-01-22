function validateForm( form ) {
  var errors = new Array();

  // If "Can Move" is enabled, then the end user must also select at least one of the other check boxes (excluding Can Move Diagonally)
  if ( form.elements['newControl[CanMove]'].checked ) {
    if ( !(form.elements['newControl[CanMoveCon]'].checked || form.elements['newControl[CanMoveRel]'].checked || form.elements['newControl[CanMoveAbs]'].checked || form.elements['newControl[CanMoveMap]'].checked) ) {
      errors[errors.length] = "In addition to \"Can Move\", you also must select at least one of: \"Can Move Mapped\", \"Can Move Absolute\", \"Can Move Relative\", or \"Can Move Continuous\"";
    }
  } else {
    // Now lets check for the opposite condition. If any of the boxes below Can Move are checked, but Can Move is not checked then signal an error

    if ( form.elements['newControl[CanMoveCon]'].checked || form.elements['newControl[CanMoveRel]'].checked || form.elements['newControl[CanMoveAbs]'].checked || form.elements['newControl[CanMoveMap]'].checked || form.elements['newControl[CanMoveDiag]'].checked ) {
      errors[errors.length] = "\"Can Move\" must also be selected if any one of the movement types are sleceted";
    }
  }

  if ( errors.length ) {
    alert( errors.join( "\n" ) );
    return( false );
  }
  return( true );
}

