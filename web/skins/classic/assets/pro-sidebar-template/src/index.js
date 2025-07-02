import './styles/styles.scss';
import { slideToggle, slideUp, slideDown } from './libs/slide';
import {
  ANIMATION_DURATION,
  FIRST_SUB_MENUS_BTN,
  INNER_SUB_MENUS_BTN,
  SIDEBAR_EL,
} from './libs/constants';
import Poppers from './libs/poppers';

const PoppersInstance = new Poppers();

/**
 * wait for the current animation to finish and update poppers position
 */
const updatePoppersTimeout = () => {
  setTimeout(() => {
    PoppersInstance.updatePoppers();
  }, ANIMATION_DURATION);
};

/**
 * sidebar collapse handler
 */
const btnCollapse = document.getElementById('btn-collapse');
if (btnCollapse) {
  btnCollapse.addEventListener('click', () => {
    SIDEBAR_EL.classList.toggle('collapsed');
    PoppersInstance.closePoppers();
    if (SIDEBAR_EL.classList.contains('collapsed'))
      FIRST_SUB_MENUS_BTN.forEach((element) => {
        element.parentElement.classList.remove('open');
      });

    updatePoppersTimeout();
  });
}

/**
 * sidebar toggle handler (on break point )
 */
const btnToggle = document.getElementById('btn-toggle');
if (btnToggle) {
  btnToggle.addEventListener('click', () => {
    SIDEBAR_EL.classList.toggle('toggled');

    updatePoppersTimeout();
  });
}

/**
 * toggle sidebar on overlay click
 */
const btnOverlay = document.getElementById('overlay');
if (btnOverlay) {
  btnOverlay.addEventListener('click', () => {
    SIDEBAR_EL.classList.toggle('toggled');
  });
}

const defaultOpenMenus = document.querySelectorAll('.menu-item.sub-menu.open');

defaultOpenMenus.forEach((element) => {
  element.lastElementChild.style.display = 'block';
});

/**
 * handle top level submenu click
 */
FIRST_SUB_MENUS_BTN.forEach((element) => {
  element.addEventListener('click', () => {
    if (SIDEBAR_EL.classList.contains('collapsed'))
      PoppersInstance.togglePopper(element.nextElementSibling);
    else {
      /**
       * if menu has "open-current-only" class then only one submenu opens at a time
       */
      const parentMenu = element.closest('.menuLeft.open-current-submenu');
      if (parentMenu)
        parentMenu
          .querySelectorAll(':scope > ul > .menu-item.sub-menu > a')
          .forEach(
            (el) =>
              window.getComputedStyle(el.nextElementSibling).display !==
                'none' && slideUp(el.nextElementSibling)
          );
      slideToggle(element.nextElementSibling);
    }
  });
});

/**
 * handle inner submenu click
 */
INNER_SUB_MENUS_BTN.forEach((element) => {
  element.addEventListener('click', () => {
    slideToggle(element.nextElementSibling);
  });
});
