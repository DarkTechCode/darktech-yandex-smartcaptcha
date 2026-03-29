(function () {
  var config = window.DarkTechYandexSmartCaptchaConfig || {};
  var observerStarted = false;
  var elementorHookAttached = false;
  var wrapperSelector =
    "[data-darktech-ysc-wrapper], [data-dt-ysc-wrapper]";
  var hiddenInputSelector =
    "[data-darktech-ysc-hidden-input], [data-dt-ysc-hidden-input]";
  var containerSelector =
    "[data-darktech-ysc-container], [data-dt-ysc-container]";

  function log() {
    if (
      !config.debug ||
      !window.console ||
      typeof window.console.info !== "function"
    ) {
      return;
    }

    var args = Array.prototype.slice.call(arguments);
    args.unshift("[DarkTech YSC]");
    window.console.info.apply(window.console, args);
  }

  function getScopeNode(scope) {
    if (scope && scope.jquery && scope.length) {
      return scope[0];
    }

    return scope && scope.nodeType === 1 ? scope : document;
  }

  function getWrappers(scope) {
    var root = getScopeNode(scope);

    if (root.matches && root.matches(wrapperSelector)) {
      return [root];
    }

    if (!root.querySelectorAll) {
      return [];
    }

    return Array.prototype.slice.call(
      root.querySelectorAll(wrapperSelector)
    );
  }

  function syncToken(wrapper, token) {
    var value = token || "";
    var inputs = wrapper.querySelectorAll(hiddenInputSelector);

    Array.prototype.forEach.call(inputs, function (input) {
      input.value = value;
    });
  }

  function getWidgetId(wrapper) {
    var widgetIdValue =
      wrapper.dataset.darktechYscWidgetId || wrapper.dataset.dtYscWidgetId;

    if (!widgetIdValue) {
      return undefined;
    }

    var widgetId = Number(widgetIdValue);

    return Number.isNaN(widgetId) ? widgetIdValue : widgetId;
  }

  function resetWrapper(wrapper) {
    syncToken(wrapper, "");

    if (
      window.smartCaptcha &&
      typeof window.smartCaptcha.reset === "function" &&
      (wrapper.dataset.darktechYscWidgetId || wrapper.dataset.dtYscWidgetId)
    ) {
      window.smartCaptcha.reset(getWidgetId(wrapper));
    }
  }

  function resetScope(scope) {
    getWrappers(scope).forEach(function (wrapper) {
      resetWrapper(wrapper);
    });
  }

  function renderWrapper(wrapper) {
    if (
      !wrapper ||
      wrapper.dataset.darktechYscInitialized === "1" ||
      !window.smartCaptcha ||
      typeof window.smartCaptcha.render !== "function"
    ) {
      return;
    }

    var container = wrapper.querySelector(containerSelector);

    if (!container) {
      return;
    }

    var sitekey = container.getAttribute("data-sitekey");
    var language = container.getAttribute("data-language");

    if (!sitekey) {
      log("Missing sitekey for wrapper", wrapper);
      return;
    }

    var widgetId = window.smartCaptcha.render(container, {
      sitekey: sitekey,
      hl: language || undefined,
      callback: function (token) {
        syncToken(wrapper, token);
      },
    });

    wrapper.dataset.darktechYscInitialized = "1";
    wrapper.dataset.darktechYscWidgetId = String(widgetId);
    wrapper.dataset.dtYscInitialized = "1";
    wrapper.dataset.dtYscWidgetId = String(widgetId);

    if (
      window.smartCaptcha &&
      typeof window.smartCaptcha.subscribe === "function"
    ) {
      window.smartCaptcha.subscribe(widgetId, "token-expired", function () {
        syncToken(wrapper, "");
      });

      window.smartCaptcha.subscribe(widgetId, "network-error", function () {
        syncToken(wrapper, "");
        log("SmartCaptcha network error");
      });

      window.smartCaptcha.subscribe(widgetId, "javascript-error", function (
        error
      ) {
        syncToken(wrapper, "");
        log("SmartCaptcha javascript error", error);
      });
    }

    if (
      window.smartCaptcha &&
      typeof window.smartCaptcha.getResponse === "function"
    ) {
      syncToken(wrapper, window.smartCaptcha.getResponse(widgetId));
    }
  }

  function init(scope) {
    if (
      !window.smartCaptcha ||
      typeof window.smartCaptcha.render !== "function"
    ) {
      return;
    }

    getWrappers(scope).forEach(function (wrapper) {
      renderWrapper(wrapper);
    });
  }

  function syncFormOnSubmit(event) {
    var form = event.target;

    if (!form || !form.querySelectorAll) {
      return;
    }

    getWrappers(form).forEach(function (wrapper) {
      if (
        window.smartCaptcha &&
        typeof window.smartCaptcha.getResponse === "function" &&
        (wrapper.dataset.darktechYscWidgetId || wrapper.dataset.dtYscWidgetId)
      ) {
        syncToken(wrapper, window.smartCaptcha.getResponse(getWidgetId(wrapper)));
      }
    });
  }

  function attachElementorHook() {
    if (
      elementorHookAttached ||
      !window.elementorFrontend ||
      !window.elementorFrontend.hooks ||
      typeof window.elementorFrontend.hooks.addAction !== "function"
    ) {
      return;
    }

    elementorHookAttached = true;
    window.elementorFrontend.hooks.addAction(
      "frontend/element_ready/global",
      function (scope) {
        init(scope);
      }
    );
  }

  function startObserver() {
    if (observerStarted || !window.MutationObserver) {
      return;
    }

    observerStarted = true;

    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        Array.prototype.forEach.call(mutation.addedNodes, function (node) {
          if (!node || node.nodeType !== 1) {
            return;
          }

          init(node);
        });
      });
    });

    observer.observe(document.documentElement, {
      childList: true,
      subtree: true,
    });
  }

  function bindEvents() {
    document.addEventListener("submit", syncFormOnSubmit, true);
    document.addEventListener(
      "reset",
      function (event) {
        resetScope(event.target);
      },
      true
    );
    document.addEventListener("wpcf7submit", function (event) {
      resetScope(event.target);
      window.setTimeout(function () {
        init(event.target);
      }, 50);
    });
    document.addEventListener("wpcf7reset", function (event) {
      resetScope(event.target);
      window.setTimeout(function () {
        init(event.target);
      }, 50);
    });
    document.addEventListener("darktech-yandex-smartcaptcha-loaded", function () {
      init(document);
    });
    document.addEventListener("dt-yandex-smartcaptcha-loaded", function () {
      init(document);
    });
    window.addEventListener("elementor/frontend/init", function () {
      attachElementorHook();
      init(document);
    });
  }

  window.DarkTechYandexSmartCaptcha = window.DarkTechYandexSmartCaptcha || {
    init: init,
    reset: resetScope,
  };

  function boot() {
    bindEvents();
    attachElementorHook();
    startObserver();

    if (
      window.darktechYandexSmartCaptchaLoaded ||
      window.dtYandexSmartCaptchaLoaded ||
      window.smartCaptcha
    ) {
      init(document);
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();

