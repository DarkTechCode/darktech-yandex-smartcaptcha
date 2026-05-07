(function () {
  if (
    window.DarkTechYandexSmartCaptcha &&
    window.DarkTechYandexSmartCaptcha.loaded
  ) {
    window.DarkTechYandexSmartCaptcha.init(document);
    return;
  }

  var config = window.DarkTechYandexSmartCaptchaConfig || {};
  var observerStarted = false;
  var elementorHookAttached = false;
  var wrapperSelector = "[data-darktech-ysc-wrapper], [data-dt-ysc-wrapper]";
  var hiddenInputSelector =
    "[data-darktech-ysc-hidden-input], [data-dt-ysc-hidden-input]";
  var containerSelector =
    "[data-darktech-ysc-container], [data-dt-ysc-container]";
  var nativeTokenSelector = 'input[name="smart-token"]';
  var staleWidgetDelay = 3000;

  function log() {
    if (!config.debug || !window.console || !window.console.info) {
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

    return root.querySelectorAll
      ? Array.prototype.slice.call(root.querySelectorAll(wrapperSelector))
      : [];
  }

  function isVisibleForRender(element) {
    var current = element;

    if (!document.documentElement.contains(element)) {
      return false;
    }

    while (current && current.nodeType === 1) {
      var style = window.getComputedStyle
        ? window.getComputedStyle(current)
        : null;

      if (
        current.hidden ||
        (style && (style.display === "none" || style.visibility === "hidden"))
      ) {
        return false;
      }

      current = current.parentElement;
    }

    return true;
  }

  function getWidgetId(wrapper) {
    var value =
      wrapper.dataset.darktechYscWidgetId || wrapper.dataset.dtYscWidgetId;
    var numberValue = Number(value);

    return value && !Number.isNaN(numberValue) ? numberValue : value;
  }

  function hasWidgetId(wrapper) {
    return !!(
      wrapper.dataset.darktechYscWidgetId || wrapper.dataset.dtYscWidgetId
    );
  }

  function syncToken(wrapper, token) {
    Array.prototype.forEach.call(
      wrapper.querySelectorAll(hiddenInputSelector),
      function (input) {
        input.value = token || "";
      }
    );
  }

  function getNativeToken(wrapper) {
    var input = wrapper.querySelector(nativeTokenSelector);

    return input ? input.value || "" : "";
  }

  function syncCurrentToken(wrapper) {
    var token = "";

    if (window.smartCaptcha && window.smartCaptcha.getResponse && hasWidgetId(wrapper)) {
      token = window.smartCaptcha.getResponse(getWidgetId(wrapper));
    }

    syncToken(wrapper, token || getNativeToken(wrapper));
  }

  function clearWrapperState(wrapper, destroyWidget) {
    if (
      destroyWidget &&
      hasWidgetId(wrapper) &&
      window.smartCaptcha &&
      window.smartCaptcha.destroy
    ) {
      try {
        window.smartCaptcha.destroy(getWidgetId(wrapper));
      } catch (error) {
        log("SmartCaptcha destroy error", error);
      }
    }

    delete wrapper.dataset.darktechYscInitialized;
    delete wrapper.dataset.darktechYscWidgetId;
    delete wrapper.dataset.darktechYscRenderedAt;
    delete wrapper.dataset.dtYscInitialized;
    delete wrapper.dataset.dtYscWidgetId;
    syncToken(wrapper, "");
  }

  function hasRenderedWidget(wrapper) {
    var container = wrapper.querySelector(containerSelector);

    return !!(
      container &&
      (container.children.length > 0 ||
        container.querySelector("iframe") ||
        container.querySelector(nativeTokenSelector))
    );
  }

  function hasFreshRenderAttempt(wrapper) {
    var renderedAt = Number(wrapper.dataset.darktechYscRenderedAt || 0);

    return renderedAt > 0 && Date.now() - renderedAt < staleWidgetDelay;
  }

  function resetWrapper(wrapper) {
    syncToken(wrapper, "");

    if (window.smartCaptcha && window.smartCaptcha.reset && hasWidgetId(wrapper)) {
      window.smartCaptcha.reset(getWidgetId(wrapper));
    }
  }

  function resetScope(scope) {
    getWrappers(scope).forEach(resetWrapper);
  }

  function subscribe(widgetId, wrapper) {
    if (!window.smartCaptcha.subscribe) {
      return;
    }

    window.smartCaptcha.subscribe(widgetId, "success", function (token) {
      syncToken(wrapper, token);
    });
    window.smartCaptcha.subscribe(widgetId, "token-expired", function () {
      syncToken(wrapper, "");
    });
    window.smartCaptcha.subscribe(widgetId, "network-error", function () {
      syncToken(wrapper, "");
      log("SmartCaptcha network error");
    });
    window.smartCaptcha.subscribe(widgetId, "javascript-error", function (error) {
      syncToken(wrapper, "");
      log("SmartCaptcha javascript error", error);
    });
  }

  function renderWrapper(wrapper) {
    if (!wrapper || !window.smartCaptcha || !window.smartCaptcha.render) {
      return;
    }

    if (!isVisibleForRender(wrapper)) {
      return;
    }

    if (wrapper.dataset.darktechYscInitialized === "1") {
      if (hasRenderedWidget(wrapper) || hasFreshRenderAttempt(wrapper)) {
        syncCurrentToken(wrapper);
        return;
      }

      clearWrapperState(wrapper, true);
    }

    var container = wrapper.querySelector(containerSelector);
    var sitekey = container ? container.getAttribute("data-sitekey") : "";

    if (!container || !sitekey) {
      log("Missing SmartCaptcha container or sitekey", wrapper);
      return;
    }

    try {
      var widgetId = window.smartCaptcha.render(container, {
        sitekey: sitekey,
        hl: container.getAttribute("data-language") || undefined,
        callback: function (token) {
          syncToken(wrapper, token);
        },
      });

      wrapper.dataset.darktechYscInitialized = "1";
      wrapper.dataset.darktechYscWidgetId = String(widgetId);
      wrapper.dataset.darktechYscRenderedAt = String(Date.now());
      wrapper.dataset.dtYscInitialized = "1";
      wrapper.dataset.dtYscWidgetId = String(widgetId);
      subscribe(widgetId, wrapper);
      syncCurrentToken(wrapper);
    } catch (error) {
      clearWrapperState(wrapper, false);
      log("SmartCaptcha render error", error);
    }
  }

  function init(scope) {
    if (!window.smartCaptcha || !window.smartCaptcha.render) {
      return;
    }

    getWrappers(scope).forEach(renderWrapper);
  }

  function scheduleInit(scope) {
    init(scope);
    window.setTimeout(function () {
      init(scope);
    }, 150);
    window.setTimeout(function () {
      init(scope);
    }, 500);
  }

  function refreshScope(scope) {
    resetScope(scope);
    window.setTimeout(function () {
      init(scope);
    }, 50);
  }

  function getPopupScope(event, instance) {
    if (instance && instance.$element && instance.$element.length) {
      return instance.$element;
    }

    return event && event.target ? event.target : document;
  }

  function syncFormOnSubmit(event) {
    getWrappers(event.target).forEach(syncCurrentToken);
  }

  function attachElementorHook() {
    if (
      elementorHookAttached ||
      !window.elementorFrontend ||
      !window.elementorFrontend.hooks ||
      !window.elementorFrontend.hooks.addAction
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
    new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        Array.prototype.forEach.call(mutation.addedNodes, function (node) {
          if (node && node.nodeType === 1) {
            init(node);
          }
        });
      });
    }).observe(document.documentElement, { childList: true, subtree: true });
  }

  function bindEvents() {
    document.addEventListener("submit", syncFormOnSubmit, true);
    document.addEventListener("reset", function (event) {
      refreshScope(event.target);
    }, true);
    document.addEventListener("wpcf7submit", function (event) {
      refreshScope(event.target);
    });
    document.addEventListener("wpcf7reset", function (event) {
      refreshScope(event.target);
    });
    document.addEventListener("submit_success", function (event) {
      refreshScope(event.target);
    });
    document.addEventListener("elementor/popup/show", function (event) {
      scheduleInit(event.target);
    });
    document.addEventListener("elementor/popup/hide", function (event) {
      resetScope(event.target);
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

    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.on) {
      window.jQuery(document).on("submit_success", function (event) {
        refreshScope(event.target);
      });
      window.jQuery(document).on("elementor/popup/show", function (
        event,
        id,
        instance
      ) {
        scheduleInit(getPopupScope(event, instance));
      });
      window.jQuery(document).on("elementor/popup/hide", function (
        event,
        id,
        instance
      ) {
        resetScope(getPopupScope(event, instance));
      });
    }
  }

  window.DarkTechYandexSmartCaptcha = {
    init: init,
    loaded: true,
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
