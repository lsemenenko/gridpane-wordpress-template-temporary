window.addEventListener("breakdanceFiltersAPIReady", function(_e) {
  window.Breakdance.filtersAPI.addFilter("elementShouldShow", function(
    shouldShow,
    elementProperties,
    meta
  ) {
    const breakpointsToHide = elementProperties?.settings?.hide_on_breakpoint?.hide || [];
    const previewMode = elementProperties?.settings?.hide_on_breakpoint?.builder_preview;

    if (breakpointsToHide.length && previewMode === 'hide') {
      return !breakpointsToHide.includes(meta.currentBreakpoint.id);
    }

    return shouldShow;
  });
});
