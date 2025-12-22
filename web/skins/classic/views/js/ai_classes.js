function validateForm(form) {
  var errors = [];
  if (!form.elements['newAIClass[ModelId]'].value) {
    errors[errors.length] = 'You must select a model';
  }
  if (!form.elements['newAIClass[ClassName]'].value) {
    errors[errors.length] = 'You must supply a class name';
  }
  var classIndexValue = form.elements['newAIClass[ClassIndex]'].value;
  if (!classIndexValue && classIndexValue !== '0') {
    errors[errors.length] = 'You must supply a class index';
  }
  if (errors.length) {
    alert(errors.join("\n"));
    return false;
  }
  return true;
}
