const styledSelectSelector =
  "select:not(.posts-filters__select):not([multiple]):not(.styled-select-field)";
let openStyledSelect = null;

document.addEventListener("DOMContentLoaded", () => {
  initializeStyledSelects(document);

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          initializeStyledSelects(node);
        }
      });
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });
});

document.addEventListener("click", (event) => {
  const wrapper = event.target.closest(".styled-select");
  if (openStyledSelect && wrapper !== openStyledSelect.wrapper) {
    openStyledSelect.close();
  }
});

document.addEventListener("focusin", (event) => {
  if (
    openStyledSelect &&
    !openStyledSelect.wrapper.contains(event.target)
  ) {
    openStyledSelect.close();
  }
});

function initializeStyledSelects(root) {
  const selectFields = [];

  if (root.matches?.(styledSelectSelector)) selectFields.push(root);
  selectFields.push(...root.querySelectorAll(styledSelectSelector));

  selectFields.forEach(createStyledSelect);
}

function createStyledSelect(select) {
  const wrapper = document.createElement("div");
  const trigger = document.createElement("button");
  const value = document.createElement("span");
  const arrow = document.createElement("span");
  const listbox = document.createElement("div");
  const selectId = select.id || `styled-select-${createStyledSelect.id++}`;
  const listboxId = `${selectId}-options`;
  let options = [];
  let activeIndex = -1;
  let searchText = "";
  let searchTimer;

  wrapper.className = "styled-select";
  trigger.type = "button";
  trigger.className = "styled-select__trigger";
  trigger.setAttribute("role", "combobox");
  trigger.setAttribute("aria-haspopup", "listbox");
  trigger.setAttribute("aria-expanded", "false");
  trigger.setAttribute("aria-controls", listboxId);
  value.className = "styled-select__value";
  arrow.className = "styled-select__arrow";
  arrow.setAttribute("aria-hidden", "true");
  trigger.append(value, arrow);
  listbox.id = listboxId;
  listbox.className = "styled-select__dropdown";
  listbox.setAttribute("role", "listbox");
  listbox.hidden = true;

  select.classList.add("styled-select-field");
  select.tabIndex = -1;
  select.setAttribute("aria-hidden", "true");
  select.parentNode.insertBefore(wrapper, select);
  wrapper.append(select, trigger, listbox);

  const labels = Array.from(select.labels || []);
  const labelText =
    select.getAttribute("aria-label") ||
    labels.map((label) => label.textContent.trim()).join(" ") ||
    select.name ||
    "Selecciona una opción";
  listbox.setAttribute("aria-label", labelText);

  function enabledOptionFrom(start, direction) {
    if (!options.length) return -1;

    for (let offset = 0; offset < options.length; offset += 1) {
      const index = (start + offset * direction + options.length) % options.length;
      if (!options[index].disabled) return index;
    }

    return -1;
  }

  function setActive(index) {
    if (index < 0 || options[index]?.disabled) return;

    if (activeIndex >= 0) options[activeIndex].element.classList.remove("is-active");
    activeIndex = index;
    const activeOption = options[activeIndex].element;
    activeOption.classList.add("is-active");
    trigger.setAttribute("aria-activedescendant", activeOption.id);
    activeOption.scrollIntoView({ block: "nearest" });
  }

  function syncValue() {
    const selectedOption = select.options[select.selectedIndex];
    const selectedText = selectedOption?.textContent.trim() || "";
    value.textContent = selectedText || "Selecciona una opción";
    trigger.setAttribute(
      "aria-label",
      selectedText ? `${labelText}: ${selectedText}` : labelText,
    );
    trigger.disabled = select.disabled;
    trigger.setAttribute("aria-required", String(select.required));

    options.forEach(({ element, index }) => {
      element.setAttribute(
        "aria-selected",
        String(index === select.selectedIndex),
      );
    });
  }

  function renderOptions() {
    listbox.replaceChildren();
    options = [];
    let currentGroup = null;

    Array.from(select.options).forEach((option, index) => {
      if (option.hidden) return;

      const group = option.parentElement.tagName === "OPTGROUP"
        ? option.parentElement
        : null;

      if (group && group !== currentGroup) {
        const heading = document.createElement("div");
        heading.className = "styled-select__group";
        heading.setAttribute("role", "presentation");
        heading.textContent = group.label;
        listbox.append(heading);
      }
      currentGroup = group;

      const item = document.createElement("div");
      const disabled = option.disabled || Boolean(group?.disabled);
      item.id = `${listboxId}-option-${index}`;
      item.className = "styled-select__option";
      item.dataset.optionIndex = index;
      item.setAttribute("role", "option");
      item.setAttribute("aria-selected", String(index === select.selectedIndex));
      item.setAttribute("aria-disabled", String(disabled));
      item.textContent = option.textContent.trim();
      listbox.append(item);
      options.push({ element: item, index, disabled });
    });

    syncValue();
    if (activeIndex >= 0 && !options[activeIndex]) activeIndex = -1;
  }

  function closeMenu(restoreFocus = false) {
    listbox.hidden = true;
    trigger.setAttribute("aria-expanded", "false");
    trigger.removeAttribute("aria-activedescendant");
    wrapper.classList.remove("is-open");
    if (openStyledSelect?.wrapper === wrapper) openStyledSelect = null;
    if (restoreFocus) trigger.focus();
  }

  function openMenu() {
    if (trigger.disabled) return;
    if (openStyledSelect && openStyledSelect.wrapper !== wrapper) {
      openStyledSelect.close();
    }

    listbox.hidden = false;
    trigger.setAttribute("aria-expanded", "true");
    wrapper.classList.add("is-open");
    openStyledSelect = { wrapper, close: closeMenu };

    const selectedIndex = options.findIndex(
      (option) => option.index === select.selectedIndex && !option.disabled,
    );
    setActive(selectedIndex >= 0 ? selectedIndex : enabledOptionFrom(0, 1));
  }

  function chooseOption(index) {
    const option = options.find((item) => item.index === index);
    if (!option || option.disabled) return;

    select.selectedIndex = index;
    select.dispatchEvent(new Event("input", { bubbles: true }));
    select.dispatchEvent(new Event("change", { bubbles: true }));
    closeMenu(true);
  }

  trigger.addEventListener("click", () => {
    if (listbox.hidden) openMenu();
    else closeMenu();
  });

  trigger.addEventListener("keydown", (event) => {
    const isOpen = !listbox.hidden;

    if (event.key === "ArrowDown" || event.key === "ArrowUp") {
      event.preventDefault();
      if (!isOpen) {
        openMenu();
        return;
      }
      const direction = event.key === "ArrowDown" ? 1 : -1;
      setActive(enabledOptionFrom(activeIndex + direction, direction));
    } else if (event.key === "Home" || event.key === "End") {
      event.preventDefault();
      if (!isOpen) openMenu();
      const start = event.key === "Home" ? 0 : options.length - 1;
      const direction = event.key === "Home" ? 1 : -1;
      setActive(enabledOptionFrom(start, direction));
    } else if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      if (!isOpen) openMenu();
      else chooseOption(options[activeIndex]?.index);
    } else if (event.key === "Escape" && isOpen) {
      event.preventDefault();
      closeMenu(true);
    } else if (event.key === "Tab" && isOpen) {
      closeMenu();
    } else if (
      event.key.length === 1 &&
      !event.ctrlKey &&
      !event.altKey &&
      !event.metaKey
    ) {
      searchText += event.key.toLocaleLowerCase();
      window.clearTimeout(searchTimer);
      searchTimer = window.setTimeout(() => {
        searchText = "";
      }, 700);
      const start = activeIndex >= 0 ? activeIndex + 1 : 0;
      const match = options.find((option, offset) => {
        const candidate = options[(start + offset) % options.length];
        return (
          !candidate.disabled &&
          candidate.element.textContent.toLocaleLowerCase().startsWith(searchText)
        );
      });
      if (match) {
        event.preventDefault();
        if (!isOpen) openMenu();
        setActive(options.indexOf(match));
      }
    }
  });

  listbox.addEventListener("click", (event) => {
    const item = event.target.closest("[role='option']");
    if (item) chooseOption(Number(item.dataset.optionIndex));
  });

  listbox.addEventListener("pointerover", (event) => {
    const item = event.target.closest("[role='option']");
    if (item) setActive(options.findIndex(({ element }) => element === item));
  });

  select.addEventListener("change", syncValue);
  select.addEventListener("invalid", () => {
    trigger.focus();
    openMenu();
  });

  labels.forEach((label) => {
    label.addEventListener("click", (event) => {
      event.preventDefault();
      trigger.focus();
    });
  });

  if (select.form) {
    select.form.addEventListener("reset", () => {
      window.requestAnimationFrame(() => {
        renderOptions();
        closeMenu();
      });
    });
  }

  const optionsObserver = new MutationObserver(renderOptions);
  optionsObserver.observe(select, {
    attributes: true,
    attributeFilter: ["disabled", "hidden", "label"],
    childList: true,
    subtree: true,
  });

  renderOptions();
}

createStyledSelect.id = 0;