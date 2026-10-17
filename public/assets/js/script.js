/* script.js — ONE script for every page (sign up, log in, main page).
   Each section only runs if its page elements exist.
   Frontend only: the backend handles data, login and saving. */
(function () {
  "use strict";

  /* ==========================================================
     A. SIGN UP PAGE: both passwords must match
     ========================================================== */
  const password = document.getElementById("password");
  const confirmPassword = document.getElementById("confirmPassword");

  if (password && confirmPassword) {
    const checkMatch = function () {
      confirmPassword.setCustomValidity(
        password.value !== confirmPassword.value
          ? "Passwords do not match."
          : "",
      );
    };
    password.addEventListener("input", checkMatch);
    confirmPassword.addEventListener("input", checkMatch);
  }

  /* ==========================================================
     B. MAIN PAGE (only runs when the profile button exists)
     ========================================================== */
  const topbar = document.querySelector(".topbar");
  if (!topbar) return;

  const STORAGE_KEY = "platform-theme";
  const root = document.documentElement;
  const profileToggle = document.getElementById("profile-toggle");
  const profilePanel = document.getElementById("profile-panel");
  const themeToggle = document.getElementById("theme-toggle");
  const settingsToggle = document.getElementById("settings-toggle");
  const settingsMenu = document.getElementById("settings-menu");

  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = csrfMeta ? csrfMeta.getAttribute("content") : "";

  function makeEl(tag, className, text) {
    const el = document.createElement(tag);
    if (className) el.className = className;
    if (text !== undefined) el.textContent = text;
    return el;
  }

  /* ---------- Toast message ---------- */
  let toastEl = null;
  let toastTimer = null;

  function showToast(message) {
    if (!toastEl) {
      toastEl = makeEl("div", "toast");
      toastEl.setAttribute("role", "status");
      document.body.appendChild(toastEl);
    }
    toastEl.textContent = message;
    toastEl.classList.remove("toast--visible");
    void toastEl.offsetWidth; // restart the fade-in
    toastEl.classList.add("toast--visible");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () {
      toastEl.classList.remove("toast--visible");
    }, 2600);
  }

  /* ---------- 1. DARK MODE ---------- */
  function saveTheme(theme) {
    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch (error) {
      /* storage blocked: theme still applies for this visit */
    }
  }

  function applyTheme(theme) {
    root.setAttribute("data-theme", theme);
    if (themeToggle)
      themeToggle.setAttribute("aria-pressed", String(theme === "dark"));
  }

  applyTheme(root.getAttribute("data-theme") === "dark" ? "dark" : "light");

  if (themeToggle) {
    themeToggle.addEventListener("click", function () {
      const next =
        root.getAttribute("data-theme") === "dark" ? "light" : "dark";
      applyTheme(next);
      saveTheme(next);
    });
  }

  /* ---------- 2. SETTINGS MENU ---------- */
  function setSettingsOpen(open) {
    if (!settingsToggle || !settingsMenu) return;
    settingsMenu.hidden = !open;
    settingsToggle.setAttribute("aria-expanded", String(open));
  }

  if (settingsToggle && settingsMenu) {
    settingsToggle.addEventListener("click", function () {
      setSettingsOpen(settingsMenu.hidden);
    });
  }

  /* ---------- 3. PROFILE PANEL (circle button) ---------- */
  if (profileToggle && profilePanel) {
    const isPanelOpen = function () {
      return !profilePanel.hidden;
    };

    const setPanelOpen = function (open) {
      profilePanel.hidden = !open;
      profileToggle.setAttribute("aria-expanded", String(open));
      profileToggle.setAttribute(
        "aria-label",
        open ? "Close profile" : "Open profile",
      );
      if (!open) setSettingsOpen(false);
    };

    profileToggle.addEventListener("click", function () {
      setPanelOpen(!isPanelOpen());
    });

    document.addEventListener("click", function (event) {
      if (!isPanelOpen()) return;
      if (
        profilePanel.contains(event.target) ||
        profileToggle.contains(event.target)
      )
        return;
      setPanelOpen(false);
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && isPanelOpen()) {
        setPanelOpen(false);
        profileToggle.focus();
      }
    });
  }

  /* ---------- 4. COMPOSER: show the chosen file name ---------- */
  const fileInput = document.getElementById("composer-files");
  const fileName = document.getElementById("composer-file-name");
  if (fileInput && fileName) {
    fileInput.addEventListener("change", function () {
      fileName.textContent = fileInput.files.length
        ? fileInput.files[0].name
        : "Insert photo (optional)";
    });
  }

  /* ---------- 5. CONFIRM before deleting ---------- */
  document.addEventListener("submit", function (event) {
    const form = event.target.closest("form[data-confirm]");
    if (form && !window.confirm(form.getAttribute("data-confirm"))) {
      event.preventDefault();
    }
  });

  /* ---------- 6. POSTS: Like / Comment / Share ----------
     Posts, comment lists and counts are rendered by PHP. This only
     toggles panels and talks to the like endpoint.                   */
  function closeShareMenus() {
    document.querySelectorAll(".share-menu").forEach(function (menu) {
      menu.hidden = true;
    });
    document
      .querySelectorAll('[data-action="share"]')
      .forEach(function (button) {
        button.setAttribute("aria-expanded", "false");
      });
  }

  async function handleLike(button) {
    if (button.disabled) return; // ignore double-clicks while a request is running
    const postId = button.dataset.postId;
    if (!postId) return;

    button.disabled = true;
    try {
      const body = new URLSearchParams();
      body.set("post_id", postId);
      body.set("csrf_token", csrfToken);

      const response = await fetch(button.dataset.url, {
        method: "POST",
        headers: {
          "X-CSRF-Token": csrfToken,
          Accept: "application/json",
        },
        body: body,
        credentials: "same-origin",
      });

      let data = null;
      try {
        data = await response.json();
      } catch (parseError) {
        data = null;
      }

      if (!response.ok || !data || data.ok !== true) {
        showToast(
          (data && data.message) ||
            "Could not update your like. Please try again.",
        );
        return;
      }

      // Update the screen ONLY with what the server reported
      button.setAttribute("aria-pressed", String(data.liked));
      const icon = button.querySelector(".bi");
      if (icon) {
        icon.classList.toggle("bi-heart-fill", data.liked);
        icon.classList.toggle("bi-heart", !data.liked);
      }
      const count = button.querySelector(".post__count");
      if (count) count.textContent = String(data.count);
    } catch (networkError) {
      showToast("Network problem. Your like was not saved.");
    } finally {
      button.disabled = false;
    }
  }

  function handleComment(button, post) {
    const section = post.querySelector(".post__comments");
    if (!section) return;
    const open = section.hidden; // hidden now means it is about to open
    section.hidden = !open;
    button.setAttribute("aria-expanded", String(open));
    if (open) {
      const input = section.querySelector(".post__comment-input");
      if (input) input.focus();
    }
  }

  function handleShare(button, post) {
    const menu = post.querySelector(".share-menu");
    if (!menu) return;
    const open = menu.hidden;
    closeShareMenus();
    menu.hidden = !open;
    button.setAttribute("aria-expanded", String(open));
  }

  async function handleShareChoice(item) {
    const post = item.closest(".post");
    if (!post) return;
    closeShareMenus();

    if (item.dataset.share === "copy") {
      const url = window.location.href.split("#")[0] + "#" + post.id;
      try {
        await navigator.clipboard.writeText(url);
        showToast("Link copied");
      } catch (error) {
        window.prompt("Copy this link:", url);
      }
    }
  }

  // One listener for the whole page (works for the feed, profile and search)
  document.addEventListener("click", function (event) {
    const shareItem = event.target.closest(".share-menu__item");
    if (shareItem) {
      handleShareChoice(shareItem);
      return;
    }

    const button = event.target.closest(".post__action");
    if (button) {
      const post = button.closest(".post");
      if (!post) return;
      switch (button.dataset.action) {
        case "like":
          handleLike(button);
          break;
        case "comment":
          handleComment(button, post);
          break;
        case "share":
          handleShare(button, post);
          break;
      }
      return;
    }

    if (!event.target.closest(".share-menu")) closeShareMenus();
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") closeShareMenus();
  });

  // Coming back from a comment action: scroll to the post that was changed
  if (window.location.hash.length > 1) {
    const target = document.getElementById(window.location.hash.slice(1));
    if (target) target.scrollIntoView({ block: "start" });
  }
})();

(function () {
  document.addEventListener("click", function (e) {
    document.querySelectorAll("details.post__more[open]").forEach(function (d) {
      if (!d.contains(e.target)) d.removeAttribute("open");
    });
  });

  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    document.querySelectorAll("details.post__more[open]").forEach(function (d) {
      d.removeAttribute("open");
    });
  });
})();
