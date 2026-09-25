document.addEventListener("DOMContentLoaded", selectFieldsInit);

function selectFieldsInit() {
  const selectFields = document.querySelectorAll(
    "select:not(.posts-filters__select)",
  );

  if (!selectFields.length) return;

  selectFields.forEach((el) => {
    new TomSelect(el, {
      controlInput: null,
    });
  });
}
