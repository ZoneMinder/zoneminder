function nextCycleView() {
  window.location.replace('?view=cycle&mid='+nextMid+'&mode='+mode, cycleRefreshTimeout);
}

function initCycle() {
  nextCycleView.periodical(cycleRefreshTimeout);
}

window.addEvent('domready', initCycle);
