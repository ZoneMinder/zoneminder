import { SUB_MENU_ELS } from './constants';
import Popper from './popper';

class Poppers {
  subMenuPoppers = [];

  constructor() {
    this.init();
  }

  init() {
    SUB_MENU_ELS.forEach((element) => {
      this.subMenuPoppers.push(new Popper(element, element.lastElementChild));
      this.closePoppers();
    });
  }

  togglePopper(target) {
    if (window.getComputedStyle(target).visibility === 'hidden') {
      target.style.visibility = 'visible';
      target.style.height = '100%'; 
    } else {
      target.style.visibility = 'hidden';
      target.style.height = 0; 
    }
  }

  updatePoppers() {
    this.subMenuPoppers.forEach((element) => {
      if (!element.instance.state.elements.popper.parentElement.classList.contains('open')) {
        element.instance.state.elements.popper.style.display = 'none';
      }
      element.instance.update();
    });
  }

  closePoppers() {
    this.subMenuPoppers.forEach((element) => {
      element.hide();
    });
  }
}

export default Poppers;
