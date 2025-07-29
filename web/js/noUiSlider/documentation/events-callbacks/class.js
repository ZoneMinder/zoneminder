function addClassFor(element, className, duration) {
    element.classList.add(className);
    setTimeout(function () {
        element.classList.remove(className);
    }, duration);
}
