function validateForm( form ) {
  var errors = new Array();

  // If "Can Move" is enabled, then the end user must also select at least one of the other check boxes (excluding Can Move Diagonally)
  if ( form.elements['newControl[CanMove]'].checked ) {
    if ( !(
      form.elements['newControl[CanMoveCon]'].checked
      ||
      form.elements['newControl[CanMoveRel]'].checked
      ||
      form.elements['newControl[CanMoveAbs]'].checked
      ||
      form.elements['newControl[CanMoveMap]'].checked
    ) ) {
      errors[errors.length] = 'In addition to "Can Move", you also must select at least one of: "Can Move Mapped", "Can Move Absolute", "Can Move Relative", or "Can Move Continuous"';
    }
  } else {
    // Now lets check for the opposite condition. If any of the boxes below Can Move are checked, but Can Move is not checked then signal an error

    if ( form.elements['newControl[CanMoveCon]'].checked
      ||
      form.elements['newControl[CanMoveRel]'].checked
      ||
      form.elements['newControl[CanMoveAbs]'].checked
      ||
      form.elements['newControl[CanMoveMap]'].checked
      ||
      form.elements['newControl[CanMoveDiag]'].checked
    ) {
      errors[errors.length] = '"Can Move" must also be selected if any one of the movement types are selected.';
    }
  }
  // If "Can Zoom" is enabled, then the end user must also select at least one of the other check boxes
  if ( form.elements['newControl[CanZoom]'].checked ) {
    if ( !(
      form.elements['newControl[CanZoomCon]'].checked
      ||
      form.elements['newControl[CanZoomRel]'].checked
      ||
      form.elements['newControl[CanZoomAbs]'].checked
    ) ) {
      errors[errors.length] = 'In addition to "Can Zoom", you also must select at least one of: "Can Zoom Absolute", "Can Zoom Relative", or "Can Zoom Continuous"';
    }
  } else {
    // Now lets check for the opposite condition. If any of the boxes below Can Zoom are checked, but Can Zoom is not checked then signal an error

    if ( form.elements['newControl[CanZoomCon]'].checked
      ||
      form.elements['newControl[CanZoomRel]'].checked
      ||
      form.elements['newControl[CanZoomAbs]'].checked
    ) {
      errors[errors.length] = '"Can Move" must also be selected if any one of the zoom types are selected.';
    }
  }
  // If "Can Zoom" is enabled, then the end user must also select at least one of the other check boxes
  if ( form.elements['newControl[CanFocus]'].checked ) {
    if ( !(
      form.elements['newControl[CanFocusCon]'].checked
      ||
      form.elements['newControl[CanFocusRel]'].checked
      ||
      form.elements['newControl[CanFocusAbs]'].checked
    ) ) {
      errors[errors.length] = 'In addition to "Can Focus", you also must select at least one of: "Can Focus Absolute", "Can Focus Relative", or "Can Focus Continuous"';
    }
  } else {
    // Now lets check for the opposite condition. If any of the boxes below Can Zoom are checked, but Can Zoom is not checked then signal an error

    if ( form.elements['newControl[CanFocusCon]'].checked
      ||
      form.elements['newControl[CanFocusRel]'].checked
      ||
      form.elements['newControl[CanFocusAbs]'].checked
    ) {
      errors[errors.length] = '"Can Focus" must also be selected if any one of the focus types are selected.';
    }
  }
  // If "Can White" is enabled, then the end user must also select at least one of the other check boxes
  if ( form.elements['newControl[CanWhite]'].checked ) {
    if ( !(
      form.elements['newControl[CanWhiteCon]'].checked
      ||
      form.elements['newControl[CanWhiteRel]'].checked
      ||
      form.elements['newControl[CanWhiteAbs]'].checked
    ) ) {
      errors[errors.length] = 'In addition to "Can White Balance", you also must select at least one of: "Can White Bal Absolute", "Can White Bal Relative", or "Can White Bal Continuous"';
    }
  } else {
    // Now lets check for the opposite condition. If any of the boxes below Can Zoom are checked, but Can Zoom is not checked then signal an error

    if ( form.elements['newControl[CanWhiteCon]'].checked
      ||
      form.elements['newControl[CanWhiteRel]'].checked
      ||
      form.elements['newControl[CanWhiteAbs]'].checked
    ) {
      errors[errors.length] = '"Can White Balance" must also be selected if any one of the white balance types are selected.';
    }
  }

  // If "Can Iris" is enabled, then the end user must also select at least one of the other check boxes
  if ( form.elements['newControl[CanIris]'].checked ) {
    if ( !(
      form.elements['newControl[CanIrisCon]'].checked
      ||
      form.elements['newControl[CanIrisRel]'].checked
      ||
      form.elements['newControl[CanIrisAbs]'].checked
    ) ) {
      errors[errors.length] = 'In addition to "Can Iris", you also must select at least one of: "Can Iris Absolute", "Can Iris Relative", or "Can Iris Continuous"';
    }
  } else {
    // Now lets check for the opposite condition. If any of the boxes below Can Zoom are checked, but Can Zoom is not checked then signal an error

    if ( form.elements['newControl[CanIrisCon]'].checked
      ||
      form.elements['newControl[CanIrisRel]'].checked
      ||
      form.elements['newControl[CanIrisAbs]'].checked
    ) {
      errors[errors.length] = '"Can Iris" must also be selected if any one of the iris types are selected.';
    }
  }

  if ( errors.length ) {
    alert(errors.join("\n"));
    return false;
  }
  return true;
}

