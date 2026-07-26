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
  for (let i = 0; i < x.length; i++) x[i].style.display = "none";

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
      if (
        overflowParams.has(axisProp) ||
        overflowParams.has(overflow)
      )
        return parent;

      parent = parent.parentElement;
    }

    return document.body;
  }

  document.querySelectorAll(".tooltip-wrapper").forEach((wrapper) => {
    const tooltip = wrapper.querySelector(".tooltip-box");

    wrapper.addEventListener("mouseenter", () => {
      tooltip.classList.remove("flip", "flip-right");

      const rect = tooltip.getBoundingClientRect();

      const parentY = getClippingParent(tooltip, "y");
      if (rect.top < Math.max(parentY.getBoundingClientRect().top, 10)) {
        tooltip.classList.add("flip");
      }

      const parentX = getClippingParent(tooltip, "x");
      if (rect.left < Math.max(parentX.getBoundingClientRect().left, 10)) {
        tooltip.classList.add("flip-right");
      }
    });
  });
});
