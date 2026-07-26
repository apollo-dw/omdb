$(document).ready(function () {
  $("#osuLink").on("click", function () {
    setGameMode(0);
  });

  $("#taikoLink").on("click", function () {
    setGameMode(1);
  });

  $("#catchLink").on("click", function () {
    setGameMode(2);
  });

  $("#maniaLink").on("click", function () {
    setGameMode(3);
  });
});

function setGameMode(mode) {
  var expirationDate = new Date();
  expirationDate.setFullYear(expirationDate.getFullYear() + 1);

  var cookieValue =
    "mode=" + mode + "; expires=" + expirationDate.toUTCString() + ";path=/;";
  document.cookie = cookieValue;
  location.reload();
}

let searchDebounceTimer;
let searchController;
let lastSearchQuery = null;

function showResult(str) {
  const query = str.trim();
  if (query === lastSearchQuery) return;
  lastSearchQuery = query;

  clearTimeout(searchDebounceTimer);
  if (searchController) searchController.abort();

  const results = document.getElementById("topBarSearchResults");

  if (query.length === 0) {
    results.innerHTML = "";
    results.style.display = "none";
    return;
  }

  searchDebounceTimer = setTimeout(function () {
    searchController = new AbortController();

    fetch("/beatmapSearch.php?q=" + encodeURIComponent(query), {
      signal: searchController.signal,
    })
      .then(function (response) {
        return response.ok ? response.text() : Promise.reject(response.status);
      })
      .then(function (html) {
        results.innerHTML = html;
        results.style.display = "block";
      })
      .catch(function () {});
  }, 150);
}

function searchFocus() {
  document.getElementById("topBarSearchResults").style.display = "block";
}

function openTab(name) {
  let x = document.getElementsByClassName("tab");
  for (let i = 0; i < x.length; i++)
    x[i].style.display = "none";

  let buttons = document
    .getElementsByClassName("tabbed-container-nav")[0]
    .getElementsByTagName("button");
  for (let i = 0; i < buttons.length; i++)
    buttons[i].classList.remove("active");

  document.getElementById(name).style.display = "block";
  event.target.classList.add("active");
}

document.addEventListener("DOMContentLoaded", () => {
  const overflowParams = new Set(["scroll", "auto", "hidden", "clip"]);
  function getClippingParent(el, axis) {
    const prop = axis === "y" ? "overflowY" : "overflowX";
    let parent = el.parentElement;

    while (parent) {
      const { overflow, [prop]: axisProp } = window.getComputedStyle(parent);
      if (overflowParams.has(axisProp) || overflowParams.has(overflow))
        return parent;

      parent = parent.parentElement;
    }

    return document.body;
  }

  function positionTooltip(tooltip) {
    tooltip.classList.remove("flip", "flip-right");
    const rect = tooltip.getBoundingClientRect();
    const parentX = getClippingParent(tooltip, "x");
    const parentY = getClippingParent(tooltip, "y");
  
    if (rect.top < Math.max(parentY.getBoundingClientRect().top, 10))
      tooltip.classList.add("flip");

    if (rect.left < Math.max(parentX.getBoundingClientRect().left, 10))
      tooltip.classList.add("flip-right");
  }

  const isTouch = window.matchMedia("(hover: none)").matches;

  function closeOpenTooltips(except) {
    document.querySelectorAll(".tooltip-wrapper.tooltip-open").forEach((w) => {
      if (w !== except)
        w.classList.remove("tooltip-open");
    });
  }

  document.querySelectorAll(".tooltip-wrapper").forEach((wrapper) => {
    const tooltip = wrapper.querySelector(".tooltip-box");
    if (!tooltip)
      return;

    wrapper.addEventListener("mouseenter", () => positionTooltip(tooltip));

    if (!isTouch)
      return;

    // <a> wrappers first tap reveals tooltip, and second follows link
    const link = wrapper.querySelector("a");
    const wrapsLink = link && !link.closest(".tooltip-box");

    wrapper.addEventListener("click", (event) => {
      if (event.target.closest(".tooltip-box"))
        return;

      const isOpen = wrapper.classList.contains("tooltip-open");

      if (wrapsLink) {
        if (isOpen)
          return;
        event.preventDefault();
      }

      closeOpenTooltips(wrapper);
      wrapper.classList.toggle("tooltip-open", wrapsLink ? true : !isOpen);
      if (wrapper.classList.contains("tooltip-open"))
        positionTooltip(tooltip);
    });
  });

  // Add dynamic tooltip stuff for things with title HTML attr
  if (isTouch) {
    const tapTip = document.createElement("div");
    tapTip.className = "tooltip-box tapTooltip";
    document.body.appendChild(tapTip);
    let tipTarget = null;

    function hideTapTooltip() {
      tapTip.classList.remove("open");
      tipTarget = null;
    }

    function showTapTooltip(el, text) {
      tapTip.textContent = text;
      tapTip.classList.add("open");
      tipTarget = el;

      const gap = 8;
      const rect = el.getBoundingClientRect();
      const box = tapTip.getBoundingClientRect();
      const left = rect.left + rect.width / 2 - box.width / 2;
      const above = rect.top - box.height - gap;

      tapTip.style.left = Math.min(Math.max(left, gap), window.innerWidth - box.width - gap) + "px";
      tapTip.style.top = (above >= gap ? above : rect.bottom + gap) + "px";
    }

    document.addEventListener("click", (event) => {
      if (!event.target.closest(".tooltip-wrapper"))
        closeOpenTooltips(null);

      const titled = event.target.closest("[title]");
      const text = titled ? titled.getAttribute("title").trim() : "";

      if (!text || titled.closest(".tooltip-wrapper")) {
        hideTapTooltip();
        return;
      }

      if (tipTarget === titled) {
        hideTapTooltip();
        return;
      }

      if (titled.closest("a"))
        event.preventDefault();
      showTapTooltip(titled, text);
    });

    window.addEventListener("scroll", () => tipTarget && hideTapTooltip(), { passive: true });
    window.addEventListener("resize", () => tipTarget && hideTapTooltip());
  }

  setupHeaderMenus();
});

function setupHeaderMenus() {
  const topBar = document.querySelector(".topBar");
  if (!topBar) return;

  const menuToggle = topBar.querySelector(".hamburgerToggle");
  const hamburger = topBar.querySelector(".hamburgerLabel");

  const dropdowns = Array.prototype.filter.call(
    topBar.querySelectorAll(".topBarDropDown"),
    (dropdown) => !dropdown.closest(".mobileMenuPanel")
  );

  function closeMenus(except) {
    dropdowns.forEach((dropdown) => {
      if (dropdown !== except) dropdown.classList.remove("open");
    });
    if (menuToggle && except !== menuToggle) menuToggle.checked = false;
  }

  dropdowns.forEach((dropdown) => {
    const button = dropdown.querySelector(".topBarDropDownButton");
    if (!button) return;

    button.addEventListener("click", (event) => {
      if (!window.matchMedia("(hover: none)").matches) return;

      event.preventDefault();
      const wasOpen = dropdown.classList.contains("open");
      closeMenus(dropdown);
      dropdown.classList.toggle("open", !wasOpen);
    });
  });

  if (hamburger) {
    hamburger.addEventListener("click", () => closeMenus(menuToggle));
  }

  document.addEventListener("click", (event) => {
    if (
      event.target.closest(".topBarDropDown") ||
      event.target.closest(".hamburgerLabel") ||
      event.target.closest(".mobileMenuPanel") ||
      event.target === menuToggle
    )
      return;

    closeMenus(null);
  });
}
