function validateForm( form ) {
  const errors = new Array();

  // If "Can Move" is enabled, then the end user must also select at least one of the other check boxes (excluding Can Move Diagonally)
  if ( form.elements['Control[CanMove]'].checked ) {
    if ( !(
      form.elements['Control[CanMoveCon]'].checked ||
      form.elements['Control[CanMoveRel]'].checked ||
      form.elements['Control[CanMoveAbs]'].checked ||
      form.elements['Control[CanMoveMap]'].checked
    ) ) {
      errors[errors.length] = 'In addition to "Can Move", you also must select at least one of: "Can Move Mapped", "Can Move Absolute", "Can Move Relative", or "Can Move Continuous"';
    }
  } else {
    // Now lets check for the opposite condition. If any of the boxes below Can Move are checked, but Can Move is not checked then signal an error

    if ( form.elements['Control[CanMoveCon]'].checked ||
      form.elements['Control[CanMoveRel]'].checked ||
      form.elements['Control[CanMoveAbs]'].checked ||
      form.elements['Control[CanMoveMap]'].checked ||
      form.elements['Control[CanMoveDiag]'].checked
    ) {
      errors[errors.length] = '"Can Move" must also be selected if any one of the movement types are selected.';
    }
  }
  // If "Can Zoom" is enabled, then the end user must also select at least one of the other check boxes
  if ( form.elements['Control[CanZoom]'].checked ) {
    if ( !(
      form.elements['Control[CanZoomCon]'].checked ||
      form.elements['Control[CanZoomRel]'].checked ||
      form.elements['Control[CanZoomAbs]'].checked
    ) ) {
      errors[errors.length] = 'In addition to "Can Zoom", you also must select at least one of: "Can Zoom Absolute", "Can Zoom Relative", or "Can Zoom Continuous"';
    }
  } else {
    // Now lets check for the opposite condition. If any of the boxes below Can Zoom are checked, but Can Zoom is not checked then signal an error

    if ( form.elements['Control[CanZoomCon]'].checked ||
      form.elements['Control[CanZoomRel]'].checked ||
      form.elements['Control[CanZoomAbs]'].checked
    ) {
      errors[errors.length] = '"Can Move" must also be selected if any one of the zoom types are selected.';
    }
  }
  // If "Can Zoom" is enabled, then the end user must also select at least one of the other check boxes
  if ( form.elements['Control[CanFocus]'].checked ) {
    if ( !(
      form.elements['Control[CanFocusCon]'].checked ||
      form.elements['Control[CanFocusRel]'].checked ||
      form.elements['Control[CanFocusAbs]'].checked
    ) ) {
      errors[errors.length] = 'In addition to "Can Focus", you also must select at least one of: "Can Focus Absolute", "Can Focus Relative", or "Can Focus Continuous"';
    }
  } else {
    // Now lets check for the opposite condition. If any of the boxes below Can Zoom are checked, but Can Zoom is not checked then signal an error

    if ( form.elements['Control[CanFocusCon]'].checked ||
      form.elements['Control[CanFocusRel]'].checked ||
      form.elements['Control[CanFocusAbs]'].checked
    ) {
      errors[errors.length] = '"Can Focus" must also be selected if any one of the focus types are selected.';
    }
  }
  // If "Can White" is enabled, then the end user must also select at least one of the other check boxes
  if ( form.elements['Control[CanWhite]'].checked ) {
    if ( !(
      form.elements['Control[CanWhiteCon]'].checked ||
      form.elements['Control[CanWhiteRel]'].checked ||
      form.elements['Control[CanWhiteAbs]'].checked
    ) ) {
      errors[errors.length] = 'In addition to "Can White Balance", you also must select at least one of: "Can White Bal Absolute", "Can White Bal Relative", or "Can White Bal Continuous"';
    }
  } else {
    // Now lets check for the opposite condition. If any of the boxes below Can Zoom are checked, but Can Zoom is not checked then signal an error

    if ( form.elements['Control[CanWhiteCon]'].checked ||
      form.elements['Control[CanWhiteRel]'].checked ||
      form.elements['Control[CanWhiteAbs]'].checked
    ) {
      errors[errors.length] = '"Can White Balance" must also be selected if any one of the white balance types are selected.';
    }
  }

  // If "Can Iris" is enabled, then the end user must also select at least one of the other check boxes
  if ( form.elements['Control[CanIris]'].checked ) {
    if ( !(
      form.elements['Control[CanIrisCon]'].checked ||
      form.elements['Control[CanIrisRel]'].checked ||
      form.elements['Control[CanIrisAbs]'].checked
    ) ) {
      errors[errors.length] = 'In addition to "Can Iris", you also must select at least one of: "Can Iris Absolute", "Can Iris Relative", or "Can Iris Continuous"';
    }
  } else {
    // Now lets check for the opposite condition. If any of the boxes below Can Zoom are checked, but Can Zoom is not checked then signal an error

    if ( form.elements['Control[CanIrisCon]'].checked ||
      form.elements['Control[CanIrisRel]'].checked ||
      form.elements['Control[CanIrisAbs]'].checked
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

function initPage() {
  // Manage the BACK button
  document.getElementById("backBtn").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Disable the back button if there is nothing to go back to
  $j('#backBtn').prop('disabled', !document.referrer.length);

  // Manage the REFRESH Button
  document.getElementById("refreshBtn").addEventListener("click", function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });

  // Manage the CANCEL Button
  document.getElementById("cancelBtn").addEventListener("click", function onCancelClick(evt) {
    evt.preventDefault();
    window.location.assign('?view=controlcaps');
  });
}

$j(document).ready(function() {
  initPage();
});
