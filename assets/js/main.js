/*
 * Shop ICT Gadgets front end: sliders, animations, header, dropdowns, cart, wishlist,
 * Quick View, the product page, the cart page and WhatsApp ordering. Server state
 * comes from window.ONSUS (rendered by partials/page-end.php) and the JSON endpoints in api/.
 */
(function () {
  "use strict";

  const ONSUS = window.ONSUS || { products: {}, cart: [], cartKeys: [], wishlist: [] };
  const state = {
    cart: new Set(ONSUS.cart), // product ids in the cart (any option)
    cartKeys: new Set(ONSUS.cartKeys), // "productId:variantId" of each cart item
    wishlist: new Set(ONSUS.wishlist),
  };
  const qvSwipers = { main: null, thumbs: null };

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  // Clean storefront card prices, including legacy markup and dynamically inserted cards.
  function cleanLeakedProductId(root = document) {
    const prices = root.matches?.(".price-wrap") ? [root] : [];
    prices.push(...(root.querySelectorAll?.(".price-wrap") || []));
    prices.forEach((price) => {
      const walker = document.createTreeWalker(price, NodeFilter.SHOW_TEXT);
      const textNodes = [];
      while (walker.nextNode()) textNodes.push(walker.currentNode);
      textNodes.forEach((node) => {
        node.nodeValue = node.nodeValue.replace(/(^|\s)262145(?=\s+(?:KES|KSh|[\d,]))\s*/g, "$1");
      });
      price.querySelectorAll(".new-price, .old-price").forEach((amount) => {
        const value = amount.textContent.trim().replace(/^(?:KES|KSh)\s*/i, "");
        if (/^[\d,]+(?:\.\d+)?$/.test(value)) amount.textContent = `KES\u00a0${value}`;
      });
    });
  }

  cleanLeakedProductId();
  new MutationObserver((records) => {
    records.forEach((record) => record.addedNodes.forEach((node) => {
      if (node.nodeType === Node.ELEMENT_NODE) cleanLeakedProductId(node);
    }));
  }).observe(document.documentElement, { childList: true, subtree: true });

  function initImageSkeletons() {
    const finish = (image) => image.removeAttribute("data-image-skeleton");
    const watch = (image) => {
      if (image.complete) {
        finish(image);
        return;
      }
      image.addEventListener("load", () => finish(image), { once: true });
      image.addEventListener("error", () => image.removeAttribute("data-image-skeleton"), { once: true });
    };
    $$("img[data-image-skeleton]").forEach(watch);
    document.addEventListener("load", (event) => {
      if (event.target instanceof HTMLImageElement && event.target.hasAttribute("data-image-skeleton")) finish(event.target);
    }, true);
    document.addEventListener("error", (event) => {
      if (event.target instanceof HTMLImageElement && event.target.hasAttribute("data-image-skeleton")) event.target.removeAttribute("data-image-skeleton");
    }, true);
  }

  async function api(url, body) {
    const res = await fetch(url, {
      method: body ? "POST" : "GET",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json", "X-CSRF-Token": ONSUS.csrf },
      body: body ? JSON.stringify(body) : undefined,
    });
    const data = await res.json().catch(() => ({ ok: false }));
    if (!res.ok || !data.ok) throw new Error(data.error || "Something went wrong");
    return data;
  }

  // ------------------------------------------------------------- sliders

  const products5 = {
    spaceBetween: 15,
    breakpoints: {
      0: { slidesPerView: 2 },
      575: { slidesPerView: 3 },
      768: { slidesPerView: 4, spaceBetween: 20 },
      992: { slidesPerView: 5, spaceBetween: 30 },
    },
  };

  // Same options as the original <Swiper> components, keyed by data-swiper.
  const sliderConfigs = {
    deal: {
      spaceBetween: 15,
      breakpoints: {
        0: { slidesPerView: 1 },
        575: { slidesPerView: 2 },
        768: { slidesPerView: 2, spaceBetween: 20 },
        992: { slidesPerView: 2, spaceBetween: 30 },
      },
      nav: "35",
    },
    categories: {
      breakpoints: { 0: { slidesPerView: 3 }, 575: { slidesPerView: 4 }, 992: { slidesPerView: 6 } }, // six main categories
      pagination: ".spd2",
    },
    "products-5": products5,
    "featured-banners": {
      spaceBetween: 15,
      breakpoints: {
        0: { slidesPerView: 1 },
        575: { slidesPerView: 2 },
        768: { slidesPerView: 2 },
        992: { slidesPerView: 3, spaceBetween: 16 },
      },
      pagination: ".spd4",
    },
    "smart-home": {
      spaceBetween: 15,
      breakpoints: { 0: { slidesPerView: 2 }, 575: { slidesPerView: 3 }, 768: { slidesPerView: 4 }, 992: { slidesPerView: 4 } },
      grid: { rows: 3, fill: "row" },
      nav: "38",
    },
    // Promo slider: endless loop that moves by itself (paused on hover, still for reduced motion).
    // On desktop the slider is the middle two-thirds of the row with visible overflow, so half an ad shows on each side.
    "banner-laptops": {
      loop: true,
      loopAdditionalSlides: 1,
      speed: 900,
      spaceBetween: 15,
      autoplay: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? false : { delay: 3500, disableOnInteraction: false, pauseOnMouseEnter: true },
      breakpoints: {
        0: { slidesPerView: 1 },
        768: { slidesPerView: 2, spaceBetween: 20 },
        1200: { slidesPerView: 3, spaceBetween: 20 }, // 3 ad-widths, shifted half an ad left in CSS: half + 2 full + half
      },
    },
    "top-list": { spaceBetween: 20 },
    // Hero main banner: one slide at a time, auto-advancing (paused on hover, off for reduced motion).
    "hero-main": {
      slidesPerView: 1,
      spaceBetween: 0,
      speed: 700,
      rewind: true,
      autoplay: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? false : { delay: 6000, disableOnInteraction: false, pauseOnMouseEnter: true },
      pagination: ".hero-main-dots",
    },
    features: {
      spaceBetween: 15,
      breakpoints: {
        0: { slidesPerView: 1 },
        575: { slidesPerView: 2 },
        768: { slidesPerView: 3, spaceBetween: 20 },
        1200: { slidesPerView: 5, spaceBetween: 20 },
      },
      pagination: ".spf2",
    },
  };

  function thumbsOptions(breakpoints) {
    return {
      direction: "horizontal",
      spaceBetween: 10,
      slidesPerView: "auto",
      freeMode: true,
      watchSlidesProgress: true,
      observer: true,
      observeParents: true,
      nested: true,
      breakpoints,
    };
  }

  function initSliders() {
    $$("[data-swiper]").forEach((el) => {
      const { nav: fixedNav, pagination, ...options } = sliderConfigs[el.dataset.swiper] || {};
      const nav = el.dataset.nav || fixedNav;
      if (nav) {
        options.navigation = { prevEl: `.snbp${nav}`, nextEl: `.snbn${nav}` };
      }
      const paginationEl = pagination || (nav ? `.spd${nav}` : null);
      if (paginationEl) {
        options.pagination = { clickable: true, el: paginationEl };
      }
      new Swiper(el, options);
    });

    // Product cards with a main image slider and a vertical thumbnail strip.
    $$("[data-thumb-slider]").forEach((el) => {
      const thumbs = new Swiper($(".tf-product-view-thumbs", el), thumbsOptions({ 0: { direction: "horizontal" }, 576: { direction: "vertical" } }));
      new Swiper($(".tf-product-view-main", el), { thumbs: { swiper: thumbs } });
    });

    if ($("[data-qv-main]")) {
      qvSwipers.thumbs = new Swiper("[data-qv-thumbs]", thumbsOptions({ 0: { direction: "horizontal" } }));
      qvSwipers.main = new Swiper("[data-qv-main]", { navigation: { prevEl: ".snbpqv", nextEl: ".snbnqv" }, thumbs: { swiper: qvSwipers.thumbs } });
    }

    // Product page gallery: main image slider with a thumbnail strip below.
    $$("[data-pv-gallery]").forEach((gallery) => {
      const thumbs = new Swiper($(".tf-product-view-thumbs", gallery), { ...thumbsOptions({ 0: { direction: "horizontal" } }), spaceBetween: 8, slidesPerView: 5, freeMode: false });
      // Pictures change every 5 seconds (paused while the pointer is over them; off for reduced motion).
      const still = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
      new Swiper($(".tf-product-view-main", gallery), {
        navigation: { prevEl: ".snbppv", nextEl: ".snbnpv" },
        thumbs: { swiper: thumbs },
        rewind: true,
        autoplay: still || $$(".swiper-slide", gallery.querySelector(".tf-product-view-main")).length < 2 ? false : { delay: 5000, disableOnInteraction: false, pauseOnMouseEnter: true },
      });
    });
  }

  // ------------------------------------------------------------- page effects

  /** Hides the header while scrolling down and brings it back (with a background) when scrolling up. */
  /** The contact bar, header and red category bar stay pinned at the top; a shadow appears once the page scrolls. */
  function initHeader() {
    const head = $("[data-site-head]");
    if (!head) return;
    const update = () => head.classList.toggle("is-scrolled", (window.scrollY || document.documentElement.scrollTop) > 4);
    // Expose the pinned block's height so sticky panels and anchor jumps stay below it.
    const measure = () => document.documentElement.style.setProperty("--site-head-h", `${head.offsetHeight}px`);
    window.addEventListener("scroll", update, { passive: true });
    window.addEventListener("resize", measure);
    if (window.ResizeObserver) new ResizeObserver(measure).observe(head);
    update();
    measure();
  }


  function initParallax() {
    if (!window.SimpleParallax) return;
    $$(".effect-paralax").forEach(
      (el) =>
        new SimpleParallax(el, {
          delay: 0.5,
          orientation: "up",
          scale: 1.3,
          transition: "cubic-bezier(0.2, 0.8, 1, 1)",
          customContainer: "",
          customWrapper: "",
        })
    );
  }

  function initPromoPopup() {
    const root = document.getElementById("promoPopup");
    if (!root || !window.bootstrap) return;
    const modal = bootstrap.Modal.getOrCreateInstance(root);
    window.setTimeout(() => modal.show(), 700);

    const copyButton = $("[data-promo-copy]", root);
    if (!copyButton) return;
    copyButton.addEventListener("click", async (event) => {
      event.preventDefault();
      event.stopPropagation();
      const code = copyButton.dataset.code || "";
      const status = $("[data-promo-copy-status]", root);
      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(code);
        } else {
          const field = document.createElement("textarea");
          field.value = code;
          field.setAttribute("readonly", "");
          field.style.position = "fixed";
          field.style.opacity = "0";
          document.body.append(field);
          field.select();
          const copied = document.execCommand("copy");
          field.remove();
          if (!copied) throw new Error("Clipboard access was denied");
        }
        copyButton.textContent = "Copied!";
        if (status) {
          status.textContent = "Promo code copied to your clipboard.";
          status.hidden = false;
        }
      } catch {
        if (status) {
          status.textContent = "Copy failed. Please try again.";
          status.hidden = false;
        }
      }
    });
  }

  function initSeasonalEffect() {
    const effect = ONSUS.seasonalEffect;
    if (!ONSUS.isHomepage || !["black_november", "christmas"].includes(effect) || window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    const sessionKey = `onsus-seasonal-effect-${effect}-played`;
    try {
      if (window.sessionStorage.getItem(sessionKey)) return;
      window.sessionStorage.setItem(sessionKey, "1");
    } catch {
      // Storage can be unavailable in restricted browser contexts; still show the effect for this load.
    }
    const play = () => {
      const container = document.createElement("div");
      container.className = `seasonal-effect seasonal-effect--${effect}`;
      container.setAttribute("aria-hidden", "true");
      const count = effect === "black_november" ? 40 : 36;
      const decorations = effect === "christmas" ? ["✦", "❄", "●", "✧"] : [];
      for (let i = 0; i < count; i += 1) {
        const item = document.createElement("span");
        if (effect === "black_november") {
          item.className = `seasonal-balloon seasonal-balloon--${i % 2 === 0 ? "red" : "black"}`;
          item.append(document.createElement("i"));
        } else {
          const type = ["star", "snowflake", "ornament"][i % 3];
          item.className = `seasonal-decoration seasonal-decoration--${type}`;
          item.textContent = type === "ornament" ? "" : decorations[i % decorations.length];
        }
        item.style.left = `${(i * 43 + 7) % 96}%`;
        item.style.setProperty("--delay", `${(i % 10) * 0.16}s`);
        item.style.setProperty("--duration", `${4 + (i % 4) * 0.45}s`);
        item.style.setProperty("--drift", `${(i % 2 ? 1 : -1) * (18 + (i % 4) * 14)}px`);
        container.append(item);
      }
      document.body.append(container);
      window.setTimeout(() => container.remove(), 9000);
    };

    play();
  }

  /** Deal countdowns. Shows "Time's up!" once the deal end date (settings.deal_ends_at) has passed. */
  function initCountdowns() {
    const boxes = $$(".js-countdown");
    if (!boxes.length) return;
    const pad = (label, value, i) =>
      `<span class="countdown__item"><span class="countdown__value countdown__value--${i} js-countdown__value--${i}">${value}</span><span class="countdown__label">${label}</span></span>`;
    const tick = () => {
      const diff = ONSUS.dealEndsAt ? ONSUS.dealEndsAt - Date.now() : 0;
      const html =
        diff > 0
          ? '<div aria-hidden="true" class="countdown__timer">' +
            pad("Days", Math.floor(diff / 86400000), 0) +
            pad("Hours", Math.floor((diff / 3600000) % 24), 1) +
            pad("Mins", Math.floor((diff / 60000) % 60), 2) +
            pad("Secs", Math.floor((diff / 1000) % 60), 3) +
            "</div>"
          : "<div>Time&#x27;s up!</div>";
      boxes.forEach((box) => (box.innerHTML = html));
    };
    tick();
    setInterval(tick, 1000);
  }

  // ------------------------------------------------------------- language / currency

  function initSelects() {
    $$("[data-select]").forEach((root) => {
      const isCurrency = root.dataset.select === "currency";
      const toggle = (open) => {
        $(".dropdown-toggle", root).classList.toggle("show", open);
        root.querySelector(":scope > .dropdown-menu").classList.toggle("show", open);
      };
      root.addEventListener("click", () => toggle(!$(".dropdown-toggle", root).classList.contains("show")));
      document.addEventListener("click", (e) => {
        if (!root.contains(e.target)) toggle(false);
      });
      $$(".inner li", root).forEach((li) => {
        li.addEventListener("click", () => {
          $$(".inner li", root).forEach((other) => {
            const active = other === li;
            $(".dropdown-item", other).classList.toggle("active", active);
            $(".dropdown-item", other).classList.toggle("selected", active);
            if (!isCurrency) other.classList.toggle("active", active);
          });
          $(".filter-option-inner-inner", root).textContent = isCurrency ? li.dataset.value : li.textContent.trim();
        });
      });
    });
  }

  function initMobileSearch() {
    const form = $("[data-mobile-search-form]");
    const input = $("[data-search-suggest-input]", form || document);
    const activity = $("[data-mobile-search-activity]");
    const recentList = $("[data-search-recent]");
    const recentEmpty = $("[data-search-recent-empty]");
    const clearRecent = $("[data-search-clear]");
    const suggestions = $("[data-search-suggestions]");
    const suggestionList = $("[data-search-suggestion-list]");
    const suggestionStatus = $("[data-search-suggestion-status]");
    if (!form || !input || !activity || !recentList || !suggestions || !suggestionList || !suggestionStatus) return;

    const storageKey = "sictRecentSearches";
    let timer = null;
    let activeRequest = null;

    const readSearches = () => {
      try {
        const searches = JSON.parse(localStorage.getItem(storageKey) || "[]");
        return Array.isArray(searches) ? searches.filter((value) => typeof value === "string").slice(0, 6) : [];
      } catch {
        return [];
      }
    };
    const saveSearch = (raw) => {
      const value = raw.trim().slice(0, 100);
      if (!value) return;
      try {
        const searches = [value, ...readSearches().filter((item) => item.toLocaleLowerCase() !== value.toLocaleLowerCase())].slice(0, 6);
        localStorage.setItem(storageKey, JSON.stringify(searches));
      } catch {
        // Search still works when browser storage is unavailable.
      }
      renderRecent();
    };
    const renderRecent = () => {
      const searches = readSearches();
      recentList.replaceChildren();
      searches.forEach((value) => {
        const item = el("li");
        const button = el("button", "mobile-search-recent-item", value);
        button.type = "button";
        button.addEventListener("click", () => {
          input.value = value;
          saveSearch(value);
          form.requestSubmit();
        });
        item.append(button);
        recentList.append(item);
      });
      recentEmpty.hidden = searches.length > 0;
      clearRecent.hidden = searches.length === 0;
    };
    const clearSuggestions = () => {
      suggestionList.replaceChildren();
      suggestionStatus.textContent = "";
    };
    const renderSuggestions = (products) => {
      suggestionList.replaceChildren();
      products.forEach((product, index) => {
        const item = el("li");
        item.setAttribute("role", "option");
        const link = el("a", "search-suggestion");
        link.href = product.url;
        const image = el("img", "search-suggestion-image");
        image.src = product.image;
        image.alt = "";
        image.loading = "lazy";
        const detail = el("span", "search-suggestion-detail");
        detail.append(el("span", "search-suggestion-title", product.title));
        detail.append(el("span", "search-suggestion-meta", [product.category, product.price].filter(Boolean).join(" · ")));
        link.append(image, detail);
        link.addEventListener("click", () => saveSearch(input.value));
        item.append(link);
        suggestionList.append(item);
      });
      suggestionStatus.textContent = products.length ? "" : "No matching products found.";
    };
    const searchProducts = async (value) => {
      activeRequest?.abort();
      activeRequest = new AbortController();
      suggestions.hidden = false;
      suggestionStatus.textContent = "Searching…";
      suggestionList.replaceChildren();
      try {
        const url = new URL(input.dataset.suggestUrl, window.location.href);
        url.searchParams.set("q", value);
        const response = await fetch(url, { credentials: "same-origin", signal: activeRequest.signal });
        const data = await response.json();
        if (!response.ok || !data.ok || !Array.isArray(data.products)) throw new Error(data.error || "Could not load product suggestions.");
        renderSuggestions(data.products);
      } catch (error) {
        if (error.name === "AbortError") return;
        suggestionStatus.textContent = "Suggestions are unavailable. Submit your search to see results.";
      }
    };
    const refresh = () => {
      const value = input.value.trim();
      activity.hidden = value.length > 0;
      if (value.length < 2) {
        clearTimeout(timer);
        activeRequest?.abort();
        suggestions.hidden = true;
        clearSuggestions();
        return;
      }
      clearTimeout(timer);
      timer = setTimeout(() => searchProducts(value), 220);
    };

    renderRecent();
    input.addEventListener("input", refresh);
    form.addEventListener("submit", () => saveSearch(input.value));
    clearRecent?.addEventListener("click", () => {
      try {
        localStorage.removeItem(storageKey);
      } catch {
        // The empty state remains available if browser storage is unavailable.
      }
      renderRecent();
    });
    $("#search")?.addEventListener("shown.bs.offcanvas", () => {
      renderRecent();
      input.focus();
    });
  }

  // ------------------------------------------------------------- formatting

  /** "KES 12,499" — same format as money() in PHP. */
  const money = (n) => `KES ${Math.round(Number(n) || 0).toLocaleString("en-US")}`;

  const el = (tag, className, text) => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  };

  // ------------------------------------------------------------- product views
  // A "view" is the Quick View modal or the product page. Each keeps its own product,
  // selected variant and quantity; elements inside it use the data-qv-* hooks.

  const views = [];
  const viewOf = (node) => views.find((v) => v.root.contains(node)) || null;
  const q = (view, sel) => view.root.querySelector(sel);

  function selectedVariant(view) {
    const p = ONSUS.products[view.productId];
    return p?.variants.find((v) => v.id === view.variant) || null;
  }

  function renderImages(view, p) {
    const { main, thumbs } = view.swipers;
    if (!main || !thumbs) return;
    const slide = (src, kind) => {
      const img = el("img");
      img.alt = p.title;
      img.loading = "lazy";
      img.width = 900;
      img.height = 1000;
      img.src = src;
      const wrap = kind === "main" ? el("a", "d-block tf-image-view") : el("div", "item");
      if (kind === "main") wrap.href = p.url;
      wrap.append(img);
      const s = el("div", "swiper-slide");
      s.append(wrap);
      return s;
    };
    const images = p.images.length ? p.images : [p.imgSrc];
    main.removeAllSlides();
    thumbs.removeAllSlides();
    main.appendSlide(images.map((src) => slide(src, "main")));
    thumbs.appendSlide(images.map((src) => slide(src, "thumb")));
    main.slideTo(0, 0);
  }

  function renderPrice(view, p) {
    const v = selectedVariant(view);
    const price = v ? v.price : p.price;
    const oldPrice = v ? v.oldPrice : p.oldPrice;
    const box = q(view, "[data-qv-price]");
    if (box) {
      box.innerHTML = "";
      box.append(el("h4", "text-primary", money(price)));
      if (oldPrice && oldPrice > price) {
        box.append(el("span", "price-text text-main-2 old-price", money(oldPrice)));
        // Product page: "% off" badge beside the prices.
        if (box.closest(".pv-price-box")) box.append(el("span", "pv-off", `${Math.round((1 - price / oldPrice) * 100)}% off`));
      }
    }
    const meta = q(view, "[data-qv-meta]");
    if (!meta) return;
    meta.innerHTML = "";
    const addMeta = (text, cls = "caption text-main-2") => {
      const li = el("li");
      li.append(el("p", cls, text));
      meta.append(li);
    };
    if (p.sold !== null) addMeta(`Sold: ${p.sold}`);
    const sku = v?.sku || p.sku;
    if (sku) addMeta(`SKU: ${sku}`);
    if (v && v.stock !== null) addMeta(v.stock > 0 ? `In stock: ${v.stock}` : "Out of stock", v.stock > 0 ? "caption text-main-2" : "caption text-primary");
  }

  /** Option buttons (e.g. RAM: 8GB | 16GB). Combinations that don't exist are shown faded. */
  function renderOptions(view, p) {
    const box = q(view, "[data-qv-options]");
    if (!box) return;
    box.innerHTML = "";
    const v = selectedVariant(view);
    if (!v || !p.optionNames.length) return;
    p.optionNames.forEach((name) => {
      const values = [...new Set(p.variants.map((x) => x.options[name]).filter((x) => x !== undefined && x !== ""))];
      if (!values.length) return;
      const group = el("div", "qv-variant-group");
      const label = el("p", "body-md-2 fw-semibold qv-variant-label", `${name}: `);
      label.append(el("span", "fw-normal text-main-2", v.options[name] ?? ""));
      const list = el("div", "qv-variant-list");
      values.forEach((value) => {
        const btn = el("button", "qv-variant-btn", value);
        btn.type = "button";
        btn.dataset.qvOption = name;
        btn.dataset.qvValue = value;
        const wanted = { ...v.options, [name]: value };
        const exists = p.variants.some((x) => p.optionNames.every((n) => (x.options[n] ?? "") === (wanted[n] ?? "")));
        btn.classList.toggle("active", v.options[name] === value);
        btn.classList.toggle("unavailable", !exists);
        // An option whose variant exists but has no stock left gets a "Sold out" tag.
        const match = p.variants.find((x) => p.optionNames.every((n) => (x.options[n] ?? "") === (wanted[n] ?? "")));
        const withValue = p.variants.filter((x) => x.options[name] === value);
        const soldOut = (x) => x.stock !== null && x.stock < 1;
        if (match ? soldOut(match) : withValue.length && withValue.every(soldOut)) {
          btn.classList.add("sold-out");
          btn.append(el("span", "qv-sold-tag", "Sold out"));
        }
        btn.setAttribute("aria-pressed", String(v.options[name] === value));
        list.append(btn);
      });
      group.append(label, list);
      box.append(group);
    });
  }

  function selectOption(view, name, value) {
    const p = ONSUS.products[view.productId];
    const v = selectedVariant(view);
    if (!p || !v) return;
    const wanted = { ...v.options, [name]: value };
    const match =
      p.variants.find((x) => p.optionNames.every((n) => (x.options[n] ?? "") === (wanted[n] ?? ""))) ||
      p.variants.find((x) => x.options[name] === value);
    if (!match) return;
    view.variant = match.id;
    if (!view.modal && history.replaceState) {
      const link = new URL(location.href);
      link.searchParams.set("v", match.id);
      history.replaceState(history.state, "", link);
    }
    renderOptions(view, p);
    renderPrice(view, p);
    refreshCartButtons();
  }

  function setQuantity(view, qty) {
    view.quantity = Math.max(1, qty);
    const input = q(view, "[data-qv-qty]");
    if (input) input.value = view.quantity;
  }

  /** Points a view at a product. Quick View also redraws its text and images; the product page is rendered by PHP. */
  function showProduct(view, id, track = true) {
    const p = ONSUS.products[id];
    if (!p) return;
    view.productId = id;
    view.variant = (p.variants.find((v) => v.isDefault) || p.variants[0])?.id ?? null;
    if (!view.modal) {
      const asked = Number(new URLSearchParams(location.search).get("v"));
      const initial = asked && p.variants.find((v) => v.id === asked);
      // The server renders the ?v= price before JavaScript starts. Preserve the
      // same variant so the initial option buttons, price and cart selection agree.
      if (initial) view.variant = initial.id;
    }
    setQuantity(view, 1);

    if (view.modal) {
      const title = q(view, "[data-qv-title]");
      title.textContent = p.title;
      title.href = p.url;
      q(view, "[data-qv-category]").textContent = p.category || "";
      q(view, "[data-qv-category-wrap]").classList.toggle("d-none", !p.category);
      const promo = q(view, "[data-qv-promo]");
      promo.textContent = p.promo || "";
      promo.classList.toggle("d-none", !p.promo);

      const specs = q(view, "[data-qv-specs]");
      specs.innerHTML = "";
      const rows = p.brand ? [["Brand", p.brand], ...p.specs] : p.specs;
      rows.forEach(([label, value]) => {
        const li = el("li");
        li.append(el("p", "body-md-2 fw-semibold", label), el("span", "body-text-3", value));
        specs.append(li);
      });
      specs.classList.toggle("d-none", !rows.length);

      const bullets = q(view, "[data-qv-bullets]");
      bullets.innerHTML = "";
      p.bullets.forEach((line) => {
        const li = el("li");
        li.append(el("p", "body-text-3", line));
        bullets.append(li);
      });
      q(view, "[data-qv-about]").classList.toggle("d-none", !p.bullets.length);
      const details = q(view, "[data-qv-details]");
      if (details) details.href = p.url;
      renderImages(view, p);
    }
    renderOptions(view, p);
    renderPrice(view, p);
    refreshCartButtons();

    if (track) api("api/track.php", { type: "product_view", product_id: id, path: location.pathname }).catch(() => {});
  }

  // ------------------------------------------------------------- cart

  const cartKey = (id, variantId) => `${id}:${variantId || 0}`;

  function refreshCartButtons() {
    $$("[data-cart-add]").forEach((btn) => {
      $(".tooltip", btn).textContent = state.cart.has(Number(btn.dataset.cartAdd)) ? "Already Added" : "Add to Cart";
    });
    views.forEach((view) => {
      const add = q(view, "[data-qv-add]");
      if (!add || !view.productId) return;
      const v = selectedVariant(view);
      const soldOut = v && v.stock !== null && v.stock < 1;
      // Out-of-stock options can't be added, so the button must not open the cart either.
      if (soldOut) add.removeAttribute("data-bs-toggle");
      else add.setAttribute("data-bs-toggle", "offcanvas");
      add.classList.toggle("is-disabled", !!soldOut);
      const pre = ONSUS.products[view.productId]?.preorder;
      $("span", add).textContent = soldOut ? "Out Of Stock" : state.cartKeys.has(cartKey(view.productId, v?.id)) ? "Already Added" : pre ? "Pre-order" : "Add To Cart";
      const buy = q(view, "[data-qv-buy]");
      if (buy) {
        buy.classList.toggle("is-disabled", !!soldOut);
        buy.setAttribute("aria-disabled", String(!!soldOut));
      }
    });
  }

  function applyCart(data) {
    state.cart = new Set(data.ids);
    state.cartKeys = new Set(data.keys);
    $$("[data-cart-count]").forEach((node) => (node.textContent = data.count));
    $$("[data-cart-subtotal]").forEach((node) => (node.textContent = data.subtotal));
    const header = $("#shoppingCart .popup-header");
    const body = header?.nextElementSibling;
    if (body && !body.classList.contains("popup-footer")) {
      const tpl = document.createElement("template");
      tpl.innerHTML = data.html.trim();
      body.replaceWith(tpl.content);
    }
    const page = $("[data-cart-page]");
    if (page && data.pageHtml !== undefined) page.innerHTML = data.pageHtml;
    $("[data-cart-footer]")?.classList.toggle("d-none", data.count === 0);
    const wa = $("[data-cart-whatsapp]");
    if (wa && data.whatsapp) wa.href = data.whatsapp;
    if (data.delivery && $("[data-delivery]")) {
      $("[data-delivery-bar]").style.width = `${data.delivery.percent}%`;
      $("[data-delivery] .progress").setAttribute("aria-valuenow", data.delivery.percent);
      const text = $("[data-delivery-text]");
      const icon = $("i", text);
      text.replaceChildren(icon);
      const strong = el("span", "fw-bold");
      if (data.delivery.left > 0) {
        strong.textContent = data.delivery.remaining;
        text.append("Add ", strong, " more for free delivery");
      } else {
        strong.textContent = "free delivery";
        text.append("Your order qualifies for ", strong);
      }
    }
    refreshCartButtons();
  }

  /** Cart API call; the cart page also asks for its own redrawn contents. */
  const cartApi = (body) => api("api/cart.php", { ...body, page: !!$("[data-cart-page]") });

  /**
   * Adds a product to the cart. Card buttons add the default option (variantId null) and do
   * nothing once the product is in the cart; Quick View and the product page add the selected option.
   */
  function addToCart(id, quantity = 1, variantId = null, exactOption = false) {
    if (!id) return;
    if (exactOption ? state.cartKeys.has(cartKey(id, variantId)) : state.cart.has(id)) return; // already added
    const hadProduct = state.cart.has(id);
    state.cart.add(id);
    if (exactOption) state.cartKeys.add(cartKey(id, variantId));
    refreshCartButtons();
    cartApi({ action: "add", product_id: id, variant_id: variantId, quantity })
      .then(applyCart)
      .catch((err) => {
        if (!hadProduct) state.cart.delete(id);
        state.cartKeys.delete(cartKey(id, variantId));
        refreshCartButtons();
        console.error(err);
      });
  }

  function removeFromCart(itemId, row) {
    row?.remove();
    cartApi({ action: "remove", item_id: itemId }).then(applyCart).catch(console.error);
  }

  function updateCartQuantity(itemId, quantity) {
    if (quantity < 1) return;
    cartApi({ action: "update", item_id: itemId, quantity }).then(applyCart).catch(console.error);
  }

  // ------------------------------------------------------------- wishlist

  function refreshWishlistButtons(id) {
    $$(`[data-wishlist="${id}"]`).forEach((btn) => {
      const added = state.wishlist.has(id);
      if (btn.hasAttribute("data-wishlist-heart")) {
        // Product page heart: filled when saved.
        btn.classList.toggle("is-on", added);
        btn.setAttribute("aria-pressed", String(added));
        btn.setAttribute("aria-label", added ? "Remove from wishlist" : "Add to wishlist");
        return;
      }
      $(".icon", btn).className = `icon ${added ? "icon-trash" : "icon-heart2"} `;
      $(".tooltip", btn).textContent = ` ${added ? "Remove Wishlist" : "Add to Wishlist"}`;
    });
    $$("[data-wishlist-count]").forEach((node) => (node.textContent = state.wishlist.size));
    // On the wishlist page, a removed product leaves the grid.
    const item = $(`[data-wishlist-item="${id}"]`);
    if (item && !state.wishlist.has(id)) {
      item.remove();
      const left = $$("[data-wishlist-item]").length;
      const summary = $("[data-wishlist-summary]");
      if (summary) summary.textContent = `${left} saved product${left === 1 ? "" : "s"}`;
      $("[data-wishlist-empty]")?.classList.toggle("d-none", left > 0);
    }
  }

  function toggleWishlist(id) {
    const wasAdded = state.wishlist.has(id);
    wasAdded ? state.wishlist.delete(id) : state.wishlist.add(id);
    refreshWishlistButtons(id);
    api("api/wishlist.php", { action: "toggle", product_id: id })
      .then((data) => {
        state.wishlist = new Set(data.ids);
        refreshWishlistButtons(id);
      })
      .catch((err) => {
        wasAdded ? state.wishlist.add(id) : state.wishlist.delete(id);
        refreshWishlistButtons(id);
        console.error(err);
      });
  }

  // ------------------------------------------------------------- events

  document.addEventListener("click", (e) => {
    const cartBtn = e.target.closest("[data-cart-add]");
    if (cartBtn) return addToCart(Number(cartBtn.dataset.cartAdd));

    const wishBtn = e.target.closest("[data-wishlist]");
    if (wishBtn) {
      e.preventDefault();
      return toggleWishlist(Number(wishBtn.dataset.wishlist));
    }

    // Product page: copy a promo code.
    const codeBtn = e.target.closest("[data-copy-code]");
    if (codeBtn) {
      const code = codeBtn.dataset.copyCode;
      const status = codeBtn.closest(".pv-row-value")?.querySelector("[data-copy-status]");
      const done = (ok) => {
        $$("[data-copy-code]").forEach((b) => b.classList.toggle("is-copied", ok && b === codeBtn));
        if (status) status.textContent = ok ? `Code ${code} copied. Enter it at checkout.` : `Your code is ${code}. Enter it at checkout.`;
      };
      navigator.clipboard?.writeText(code).then(() => done(true), () => done(false)) ?? done(false);
      return;
    }

    // Product page: share the link (phone share sheet, or copy the link).
    const shareBtn = e.target.closest("[data-share]");
    if (shareBtn) {
      const url = location.href.split("#")[0];
      if (navigator.share) {
        navigator.share({ title: shareBtn.dataset.shareTitle || document.title, url }).catch(() => {});
      } else {
        navigator.clipboard?.writeText(url).then(() => {
          shareBtn.classList.add("is-copied");
          shareBtn.setAttribute("aria-label", "Link copied");
          setTimeout(() => {
            shareBtn.classList.remove("is-copied");
            shareBtn.setAttribute("aria-label", "Share this product");
          }, 2000);
        });
      }
      return;
    }

    const qvBtn = e.target.closest("[data-quickview]");
    if (qvBtn && quickView) return showProduct(quickView, Number(qvBtn.dataset.quickview));

    const remove = e.target.closest("[data-cart-remove]");
    if (remove) {
      e.preventDefault();
      return removeFromCart(Number(remove.dataset.cartRemove), remove.closest("[data-cart-row]") || remove.closest("li"));
    }

    const step = e.target.closest("[data-cart-step]");
    if (step) {
      e.preventDefault();
      return updateCartQuantity(Number(step.dataset.item), Number(step.dataset.qty) + Number(step.dataset.cartStep));
    }

    const view = viewOf(e.target);
    if (!view) return;

    const option = e.target.closest("[data-qv-option]");
    if (option) return selectOption(view, option.dataset.qvOption, option.dataset.qvValue);
    if (e.target.closest("[data-qv-minus]")) return setQuantity(view, view.quantity - 1);
    if (e.target.closest("[data-qv-plus]")) return setQuantity(view, view.quantity + 1);

    const wa = e.target.closest("[data-qv-whatsapp]");
    if (wa && ONSUS.whatsapp) {
      // Build the message at click time so it has the chosen option and quantity.
      const p = ONSUS.products[view.productId];
      const v = selectedVariant(view);
      if (p) {
        const price = v ? v.price : p.price;
        const label = v ? Object.values(v.options).join(" / ") : "";
        const text = `Hi! I would like to order:\n• ${p.title}${label ? ` (${label})` : ""} × ${view.quantity} — ${money(price * view.quantity)}\n${new URL(p.url, location.href).href}`;
        wa.href = `https://wa.me/${ONSUS.whatsapp}?text=${encodeURIComponent(text)}`;
      }
      return;
    }

    // Buy Now: add the chosen option (unless it is already in the cart), then go to checkout.
    const buy = e.target.closest("[data-qv-buy]");
    if (buy) {
      e.preventDefault();
      if (buy.classList.contains("is-disabled") || buy.classList.contains("is-busy")) return;
      const v = selectedVariant(view);
      const go = () => (location.href = buy.getAttribute("href"));
      if (state.cartKeys.has(cartKey(view.productId, v?.id))) return go();
      buy.classList.add("is-busy");
      cartApi({ action: "add", product_id: view.productId, variant_id: view.variant, quantity: view.quantity })
        .then(go)
        .catch((err) => {
          buy.classList.remove("is-busy");
          console.error(err);
        });
      return;
    }

    const add = e.target.closest("[data-qv-add]");
    if (add) {
      if (add.classList.contains("is-disabled")) return e.preventDefault();
      return addToCart(view.productId, view.quantity, view.variant, true);
    }
  });

  // Category mega menu in the red bar: opens on hover or keyboard focus; on touch screens the first tap
  // opens it and a second tap follows the link. Escape closes it.
  $$("[data-mega]").forEach((item) => {
    const link = $(".mega-link", item);
    if (!$(".mega-panel", item) || !link) return;
    const setOpen = (open) => {
      item.classList.toggle("is-open", open);
      link.setAttribute("aria-expanded", String(open));
    };
    item.addEventListener("mouseenter", () => { item.classList.remove("is-closed"); setOpen(true); });
    item.addEventListener("mouseleave", () => { item.classList.remove("is-closed"); setOpen(false); });
    item.addEventListener("focusin", () => setOpen(true));
    item.addEventListener("focusout", (e) => {
      if (!item.contains(e.relatedTarget)) {
        setOpen(false);
        item.classList.remove("is-closed");
      }
    });
    link.addEventListener("click", (e) => {
      if (window.matchMedia("(hover: none)").matches && !item.classList.contains("is-open")) {
        e.preventDefault();
        $$("[data-mega].is-open").forEach((other) => other !== item && other.classList.remove("is-open"));
        setOpen(true);
      }
    });
    item.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        setOpen(false);
        item.classList.add("is-closed");
        link.focus();
      }
    });
  });
  document.addEventListener("click", (e) => {
    $$("[data-mega].is-open").forEach((item) => { if (!item.contains(e.target)) item.classList.remove("is-open"); });
  });

  // Featured-product carousel inside each category panel: one product at a time, every 3 seconds
  // while the panel is open (not for reduced motion); arrows and dots change it by hand.
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  $$("[data-mega-feature]").forEach((box) => {
    const slides = $$("[data-mf-slide]", box);
    const dots = $$("[data-mf-dot]", box);
    if (slides.length < 2) return;
    let index = 0;
    const show = (i) => {
      index = (i + slides.length) % slides.length;
      slides.forEach((s, k) => {
        const on = k === index;
        s.classList.toggle("is-active", on);
        s.setAttribute("aria-hidden", String(!on));
        s.tabIndex = on ? 0 : -1;
      });
      dots.forEach((d, k) => d.classList.toggle("is-active", k === index));
    };
    $("[data-mf-prev]", box)?.addEventListener("click", (e) => { e.preventDefault(); show(index - 1); });
    $("[data-mf-next]", box)?.addEventListener("click", (e) => { e.preventDefault(); show(index + 1); });
    dots.forEach((d) => d.addEventListener("click", (e) => { e.preventDefault(); show(Number(d.dataset.mfDot)); }));
    if (!reduceMotion) {
      const item = box.closest("[data-mega], [data-cat-sub]");
      setInterval(() => {
        if (item && (item.classList.contains("is-open") || item.matches(":hover, :focus-within")) && !box.matches(":hover")) show(index + 1);
      }, 3000);
    }
  });

  // "All Categories" menu in the red nav bar.
  const catMenu = $("[data-cat-menu]");
  if (catMenu) {
    const toggle = $("[data-cat-menu-toggle]", catMenu);
    const list = $(".cat-menu-list", catMenu);
    const setOpen = (open) => {
      list.hidden = !open;
      toggle.setAttribute("aria-expanded", String(open));
      catMenu.classList.toggle("open", open);
      if (!open) closeSubs();
    };
    // Each main category in the list opens its sub-menu on hover, keyboard focus or click
    // (a click opens it instead of following the link; "View all …" inside goes to the category).
    const subs = $$("[data-cat-sub]", catMenu);
    const closeSubs = (except) => {
      subs.forEach((s) => {
        if (s === except) return;
        s.classList.remove("is-open");
        $(".cat-menu-main", s).setAttribute("aria-expanded", "false");
      });
      if (!except) list.style.minHeight = "";
    };
    // The list and the open sub-menu are kept the same height (whichever is taller sets it).
    const syncHeight = (item) => {
      list.style.minHeight = "";
      if (!item) return;
      const fly = $(".cat-flyout", item);
      fly.style.minHeight = "0";
      const h = Math.max(list.offsetHeight, fly.offsetHeight);
      list.style.minHeight = `${h}px`;
      fly.style.minHeight = `${h}px`;
    };
    const openSub = (item) => {
      closeSubs(item);
      item.classList.add("is-open");
      $(".cat-menu-main", item).setAttribute("aria-expanded", "true");
      syncHeight(item);
    };
    subs.forEach((item) => {
      const link = $(".cat-menu-main", item);
      item.addEventListener("mouseenter", () => openSub(item));
      item.addEventListener("focusin", () => openSub(item));
      link.addEventListener("click", (e) => {
        e.preventDefault();
        item.classList.contains("is-open") ? closeSubs() : openSub(item);
      });
    });
    // Leaving the list (and its sub-menus) closes the open sub-menu.
    list.addEventListener("mouseleave", () => closeSubs());
    toggle.addEventListener("click", () => setOpen(list.hidden));
    document.addEventListener("click", (e) => {
      if (!catMenu.contains(e.target)) setOpen(false);
    });
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && !list.hidden) {
        setOpen(false);
        toggle.focus();
      }
    });
  }

  // Shop sidebar: show or hide a main category's subcategories.
  $$("[data-subcat-toggle]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const list = document.getElementById(btn.getAttribute("aria-controls"));
      if (!list) return;
      const open = list.hidden;
      list.hidden = !open;
      btn.setAttribute("aria-expanded", String(open));
      const name = btn.getAttribute("aria-label").replace(/^(Show|Hide) /, "");
      btn.setAttribute("aria-label", `${open ? "Hide" : "Show"} ${name}`);
      btn.closest(".shop-cat-main")?.classList.toggle("is-open", open);
    });
  });

  // Product finder pop-up: switching item shows its brands/specs (the others are not sent),
  // a chosen chip can be clicked again to unselect it, "Any" clears a spec group, and empty
  // amount boxes are left out of the search link.
  $$("[data-finder]").forEach((form) => {
    const showItem = (slug) => {
      $$("[data-finder-group]", form).forEach((g) => {
        const on = g.dataset.finderGroup === slug;
        g.hidden = !on;
        $$("fieldset", g).forEach((f) => (f.disabled = !on));
      });
    };
    $$("[data-finder-item]", form).forEach((r) => r.addEventListener("change", () => showItem(r.value)));
    $$("[data-finder-toggle]", form).forEach((r) => {
      r.addEventListener("pointerdown", () => (r.dataset.wasChecked = r.checked ? "1" : ""));
      r.addEventListener("click", () => {
        if (r.dataset.wasChecked === "1") r.checked = false;
        r.dataset.wasChecked = "";
      });
    });
    $$("[data-finder-any]", form).forEach((btn) => {
      btn.addEventListener("click", () => {
        $$(`input[name="${btn.dataset.finderAny}[]"]`, btn.closest("fieldset")).forEach((r) => (r.checked = false));
      });
    });
    form.addEventListener("submit", () => {
      $$("input[name=min], input[name=max]", form).forEach((i) => (i.disabled = i.value.trim() === ""));
    });
  });

  // Typing a quantity on the cart page.
  document.addEventListener("change", (e) => {
    const input = e.target.closest("[data-cart-qty]");
    if (input) updateCartQuantity(Number(input.dataset.item), Math.max(1, parseInt(input.value, 10) || 1));
  });

  initSliders();
  initImageSkeletons();

  const qvRoot = document.getElementById("quickView");
  const quickView = qvRoot ? { root: qvRoot, modal: true, productId: null, variant: null, quantity: 1, swipers: qvSwipers } : null;
  if (quickView) {
    views.push(quickView);
    const id = Number(qvRoot.dataset.productId);
    if (id) showProduct(quickView, id, false); // fill in the default product's options
  }
  $$("[data-product-page]").forEach((root) => {
    const view = { root, modal: false, productId: null, variant: null, quantity: 1, swipers: {} };
    views.push(view);
    showProduct(view, Number(root.dataset.productId), false);
  });

  initHeader();
  initParallax();
  initCountdowns();
  initPromoPopup();
  initSeasonalEffect();
  initSelects();
  initMobileSearch();
  new WOW({ mobile: false, live: false }).init();
})();

// Keyboard: elements with role="button" that are not real buttons (quantity steppers) respond to Enter and Space.
document.addEventListener("keydown", (e) => {
  if ((e.key === "Enter" || e.key === " ") && e.target.matches?.('span[role="button"][tabindex]')) {
    e.preventDefault();
    e.target.click();
  }
});

// Product page on phones & tablets: open the Specifications tab first.
if (window.matchMedia("(max-width: 991.98px)").matches) {
  const specTab = document.getElementById("tab-specifications");
  if (specTab && window.bootstrap) bootstrap.Tab.getOrCreateInstance(specTab).show();
}

// Checkout: switching between pickup and delivery updates the Delivery and Total lines; town/city is needed only for delivery.
document.querySelectorAll("[data-delivery-method]").forEach((radio) => {
  radio.addEventListener("change", () => {
    const method = radio.value;
    document.querySelectorAll("[data-delivery-cost], [data-order-total]").forEach((node) => (node.textContent = node.dataset[method]));
    const city = document.getElementById("city");
    if (city) city.required = method === "delivery";
    document.querySelector("[data-city-required]")?.toggleAttribute("hidden", method !== "delivery");
  });
});

// Shop sidebar: tick one or more categories; the list updates straight away.
document.querySelectorAll("[data-cat-form]").forEach((form) => {
  form.querySelector("[data-cat-apply]")?.setAttribute("hidden", "");
  form.addEventListener("change", (e) => {
    const box = e.target;
    if (box.matches("[data-cat-main]")) {
      // A main category covers all of its subcategories, so they show ticked and can't be changed separately.
      form.querySelectorAll(`[data-cat-sub="${box.dataset.catMain}"]`).forEach((sub) => {
        sub.checked = box.checked;
        sub.disabled = box.checked;
      });
    }
    form.classList.add("is-loading");
    form.requestSubmit ? form.requestSubmit() : form.submit();
  });
});

// ---------------------------------------------------------------- app (PWA)
// Offline support: register the service worker (needs https, or localhost while testing).
if ("serviceWorker" in navigator && (location.protocol === "https:" || location.hostname === "localhost")) {
  window.addEventListener("load", () => navigator.serviceWorker.register("sw.js").catch(() => {}));
}
// Installing the app: the "Install our app" button in the phone menu, and a message at the top of the page
// after 10 seconds on the site (counted across pages). Shown only when the browser can install it (Android and
// desktop Chrome / Edge) or with "Add to Home Screen" steps on iPhone; hidden once installed, and for 7 days
// after "Not now".
(function () {
  const standalone = window.matchMedia("(display-mode: standalone)").matches || navigator.standalone;
  if (standalone) return;
  const menuBtn = document.querySelector("[data-install-app]");
  const toast = document.querySelector("[data-install-toast]");
  const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) && !/crios|fxios/i.test(navigator.userAgent);
  const DELAY = 10000;
  const SNOOZE = 7 * 24 * 60 * 60 * 1000;
  let prompt = null;

  const store = (area, key, value) => { try { value === undefined ? null : window[area].setItem(key, value); return window[area].getItem(key); } catch (e) { return null; } };
  const snoozed = () => Number(store("localStorage", "sictInstallSnooze") || 0) > Date.now();
  let start = Number(store("sessionStorage", "sictVisitStart") || 0);
  if (!start) start = Number(store("sessionStorage", "sictVisitStart", String(Date.now())));

  const install = async () => {
    if (!prompt) return;
    prompt.prompt();
    await prompt.userChoice.catch(() => null);
    prompt = null;
    if (menuBtn) menuBtn.hidden = true;
    hideToast();
  };
  const hideToast = () => {
    if (!toast || toast.hidden) return;
    toast.classList.remove("is-in");
    setTimeout(() => (toast.hidden = true), 250);
  };
  const showToast = () => {
    if (!toast || snoozed() || (!prompt && !isIos)) return;
    if (isIos) {
      toast.querySelector("[data-install-toast-line]").textContent = "Tap Share, then “Add to Home Screen”.";
      toast.querySelector("[data-install-toast-go]").hidden = true;
    }
    toast.hidden = false;
    requestAnimationFrame(() => toast.classList.add("is-in"));
  };
  const showAfterDelay = () => setTimeout(showToast, Math.max(0, start + DELAY - Date.now()));

  window.addEventListener("beforeinstallprompt", (e) => {
    e.preventDefault();
    prompt = e;
    if (menuBtn) menuBtn.hidden = false;
    showAfterDelay();
  });
  window.addEventListener("appinstalled", () => {
    if (menuBtn) menuBtn.hidden = true;
    hideToast();
  });
  menuBtn?.addEventListener("click", install);
  toast?.querySelector("[data-install-toast-go]")?.addEventListener("click", install);
  toast?.querySelector("[data-install-toast-close]")?.addEventListener("click", () => {
    store("localStorage", "sictInstallSnooze", String(Date.now() + SNOOZE));
    hideToast();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && toast && !toast.hidden) hideToast();
  });
  if (isIos) {
    document.querySelector("[data-install-ios]")?.removeAttribute("hidden");
    showAfterDelay();
  }
})();
