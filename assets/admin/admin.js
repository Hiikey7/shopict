/* Shop ICT Gadgets admin dashboard behaviour. */
(function () {
  "use strict";

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  // Banner product search: type a product name, then choose its matching suggestion.
  const bannerProductSearch = $("[data-banner-product-search]");
  const bannerProductId = $("[data-banner-product-id]");
  if (bannerProductSearch && bannerProductId) {
    const options = $$("option", document.getElementById("banner_product_options"));
    const updateBannerProduct = () => {
      const match = options.find((option) => option.value === bannerProductSearch.value);
      bannerProductId.value = match?.dataset.productId || "";
    };
    bannerProductSearch.addEventListener("input", updateBannerProduct);
    bannerProductSearch.addEventListener("change", updateBannerProduct);
    $("[data-clear-banner-product]")?.addEventListener("click", () => {
      bannerProductSearch.value = "";
      bannerProductId.value = "";
      bannerProductSearch.focus();
    });
  }

  // Menu: opens from the hamburger button; closes with ✕, a click outside, or Escape.
  const sidebar = $("#adminSidebar");
  const burger = $("[data-toggle-sidebar]");
  const backdrop = $(".admin-backdrop");
  const setMenu = (open) => {
    if (!sidebar) return;
    sidebar.classList.toggle("open", open);
    if (backdrop) backdrop.hidden = !open;
    burger?.setAttribute("aria-expanded", String(open));
    document.body.classList.toggle("admin-menu-open", open);
    if (open) ($(".admin-nav a.active", sidebar) || $(".admin-nav a", sidebar))?.focus();
    else burger?.focus();
  };
  burger?.addEventListener("click", () => setMenu(!sidebar.classList.contains("open")));
  $$("[data-close-sidebar]").forEach((el) => el.addEventListener("click", () => setMenu(false)));
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && sidebar?.classList.contains("open")) setMenu(false);
  });

  // Confirmation for destructive buttons
  document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-confirm]");
    if (btn && !window.confirm(btn.dataset.confirm)) {
      e.preventDefault();
      e.stopImmediatePropagation();
    }
  }, true);

  // Image preview for file inputs
  $$("[data-preview-input]").forEach((input) => {
    input.addEventListener("change", () => {
      const img = $(`[data-preview="${input.dataset.previewInput}"]`);
      const file = input.files && input.files[0];
      if (!img || !file) return;
      img.src = URL.createObjectURL(file);
      img.classList.remove("d-none");
    });
  });

  const popupAction = $("[data-popup-action-type]");
  const popupActionLabel = $("[data-popup-action-label]");
  if (popupAction && popupActionLabel) {
    const defaults = { product: "Shop now", promo_code: "Copy code" };
    let previous = popupAction.value;
    const syncPopupAction = () => {
      $$("[data-popup-action-target]").forEach((field) => {
        field.classList.toggle("d-none", field.dataset.popupActionTarget !== popupAction.value);
      });
      if (popupActionLabel.value === defaults[previous]) {
        popupActionLabel.value = defaults[popupAction.value];
      }
      previous = popupAction.value;
    };
    popupAction.addEventListener("change", syncPopupAction);
    syncPopupAction();
  }

  // Hero banner feature rows
  const features = $("[data-features]");
  if (features) {
    $("[data-add-feature]").addEventListener("click", () => {
      features.append($("[data-feature-template]").content.cloneNode(true));
    });
    features.addEventListener("click", (e) => {
      if (e.target.closest("[data-remove-feature]")) e.target.closest("[data-feature-row]").remove();
    });
  }

  // Banner colour pickers: choosing a colour switches off "Use the default" and shows the code.
  $$("[data-colour-input]").forEach((input) => {
    input.addEventListener("input", () => {
      const name = input.dataset.colourInput;
      const def = document.getElementById(`${name}_default`);
      if (def) def.checked = false;
      const code = $(`[data-colour-value="${name}"]`);
      if (code) code.textContent = input.value;
    });
  });

  // Product form: variants table and specification rows.
  const productForm = $("[data-product-form]");
  if (productForm) {
    const table = $("[data-variant-table]", productForm);
    const rows = $("[data-variant-rows]", productForm);
    const nameInputs = $$("[data-option-name]", productForm);
    let newKey = 0;

    const syncVariants = () => {
      const count = $$("[data-variant-row]", rows).length;
      table.classList.toggle("d-none", count === 0);
      // Only the options that have a name get a column (the first one always shows).
      nameInputs.forEach((input, c) => {
        const name = input.value.trim();
        const show = c === 0 || name !== "";
        $$(`[data-opt-col="${c}"]`, table).forEach((cell) => cell.classList.toggle("d-none", !show));
        $(`[data-opt-head="${c}"]`, table).textContent = name || `Option ${c + 1}`;
      });
      // With variants, the product price follows the default variant.
      $$("[data-base-price]", productForm).forEach((input) => (input.readOnly = count > 0));
      $("[data-price-note]", productForm).classList.toggle("d-none", count === 0);
      if (count && !$('input[name="default_variant"]:checked', rows)) $('input[name="default_variant"]', rows).checked = true;
    };

    $("[data-add-variant]", productForm).addEventListener("click", () => {
      const html = $("[data-variant-template]", productForm).innerHTML.replaceAll("__KEY__", `new${++newKey}`);
      const tpl = document.createElement("template");
      tpl.innerHTML = html.trim();
      const row = tpl.content.firstElementChild;
      // Start from the last row's values so similar variants are quick to enter.
      const last = $$("[data-variant-row]", rows).pop();
      if (last) {
        const lastInputs = $$("input:not([type=radio]):not([type=hidden])", last);
        $$("input:not([type=radio]):not([type=hidden])", row).forEach((input, i) => (input.value = lastInputs[i].value));
      }
      rows.append(row);
      syncVariants();
      $("input", row).focus();
    });
    rows.addEventListener("click", (e) => {
      if (e.target.closest("[data-remove-variant]")) {
        e.target.closest("[data-variant-row]").remove();
        syncVariants();
      }
    });
    nameInputs.forEach((input) => input.addEventListener("input", syncVariants));
    syncVariants();

  }
  // Promotion form: show the field for the chosen target and filter the product list.
  const promoForm = $("[data-promo-form]");
  if (promoForm) {
    const sync = () => {
      const target = $('input[name="applies_to"]:checked', promoForm)?.value;
      $$("[data-applies]", promoForm).forEach((box) => box.classList.toggle("d-none", box.dataset.applies !== target));
      const type = $('select[name="discount_type"]', promoForm).value;
      $("[data-value-unit]", promoForm).textContent = type === "percent" ? "%" : "KSh";
      const picked = $$('input[name="product_ids[]"]:checked', promoForm).length;
      $("[data-picked-count]", promoForm).textContent = picked;
    };
    promoForm.addEventListener("change", sync);
    const filter = $("[data-product-filter]", promoForm);
    filter?.addEventListener("input", () => {
      const term = filter.value.trim().toLowerCase();
      $$("[data-product-option]", promoForm).forEach((row) => row.classList.toggle("d-none", term !== "" && !row.dataset.search.includes(term)));
    });
    sync();
  }

  // Analytics chart: crosshair snaps to the nearest day; one tooltip lists both series.
  $$("[data-viz-chart]").forEach((chart) => {
    const data = JSON.parse($("[data-chart-data]", chart).textContent);
    const svg = $("svg", chart);
    const hit = $("[data-hit]", chart);
    const cross = $("[data-crosshair]", chart);
    const tip = $("[data-tooltip]", chart);
    const dots = { views: $('[data-dot="views"]', chart), visitors: $('[data-dot="visitors"]', chart) };
    const n = data.points.length;
    const xOf = (i) => data.padL + (n > 1 ? (i * data.plotW) / (n - 1) : data.plotW / 2);
    const yOf = (v) => data.padT + data.plotH - (v / data.yMax) * data.plotH;
    let current = n - 1;

    const show = (i) => {
      current = Math.max(0, Math.min(n - 1, i));
      const p = data.points[current];
      const x = xOf(current);
      cross.setAttribute("x1", x);
      cross.setAttribute("x2", x);
      cross.setAttribute("visibility", "visible");
      for (const key of ["views", "visitors"]) {
        dots[key].setAttribute("cx", x);
        dots[key].setAttribute("cy", yOf(p[key]));
        dots[key].setAttribute("visibility", "visible");
      }
      tip.replaceChildren();
      const head = document.createElement("div");
      head.className = "viz-tooltip-head";
      head.textContent = p.label;
      tip.append(head);
      [["views", "Page views", "series-1"], ["visitors", "Unique visitors", "series-2"]].forEach(([key, label, cls]) => {
        const row = document.createElement("div");
        row.className = "viz-tooltip-row";
        const keyLine = document.createElement("i");
        keyLine.className = `legend-line ${cls}`;
        const value = document.createElement("strong");
        value.textContent = p[key].toLocaleString();
        const name = document.createElement("span");
        name.textContent = label;
        row.append(keyLine, value, name);
        tip.append(row);
      });
      tip.hidden = false;
      const scale = svg.getBoundingClientRect().width / data.W;
      const left = x * scale;
      const width = tip.offsetWidth;
      tip.style.left = `${Math.min(Math.max(left - width / 2, 0), svg.clientWidth - width)}px`;
      tip.style.top = `${Math.max(0, yOf(p.views) * scale - tip.offsetHeight - 12)}px`;
    };
    const hide = () => {
      tip.hidden = true;
      cross.setAttribute("visibility", "hidden");
      Object.values(dots).forEach((d) => d.setAttribute("visibility", "hidden"));
    };

    hit.addEventListener("pointermove", (e) => {
      const rect = svg.getBoundingClientRect();
      const x = ((e.clientX - rect.left) / rect.width) * data.W;
      show(n > 1 ? Math.round(((x - data.padL) / data.plotW) * (n - 1)) : 0);
    });
    hit.addEventListener("pointerleave", hide);
    hit.addEventListener("focus", () => show(current));
    hit.addEventListener("blur", hide);
    hit.addEventListener("keydown", (e) => {
      if (e.key === "ArrowLeft" || e.key === "ArrowRight") {
        e.preventDefault();
        show(current + (e.key === "ArrowLeft" ? -1 : 1));
      }
    });
  });

  // Section product editor: add from the picker, reorder and remove rows.
  const editor = $("[data-section-editor]");
  if (editor && $("[data-rows]", editor)) {
    const rows = $("[data-rows]", editor);
    const empty = $("[data-empty]", rows);
    const picker = $("[data-picker]", editor);
    const search = $("[data-picker-search]", editor);
    const products = JSON.parse($("[data-picker-data]", editor).textContent);
    const limit = editor.dataset.limit ? Number(editor.dataset.limit) : null;

    const currentIds = () => $$('input[name="product_id[]"]', rows).map((i) => Number(i.value));

    const refresh = () => {
      const items = $$(".sortable-row", rows);
      items.forEach((row, i) => ($("[data-pos]", row).textContent = i + 1));
      empty.classList.toggle("d-none", items.length > 0);
      $("[data-count]", editor).textContent = items.length;
      renderPicker();
    };

    const renderPicker = () => {
      const term = search.value.trim().toLowerCase();
      const ids = currentIds();
      const full = limit !== null && ids.length >= limit;
      picker.innerHTML = "";
      products
        .filter((p) => !term || p.title.toLowerCase().includes(term) || p.category.toLowerCase().includes(term) || String(p.id) === term)
        .slice(0, 100)
        .forEach((p) => {
          const btn = document.createElement("button");
          btn.type = "button";
          btn.disabled = ids.includes(p.id) || full;
          btn.title = full ? "This section is full" : "Add to section";
          btn.innerHTML = `<img class="thumb" width="36" height="36" alt=""><span class="flex-grow-1 small"><span class="d-block text-truncate-2"></span><span class="text-muted"></span></span><span class="text-danger fw-bold">+</span>`;
          $("img", btn).src = p.img;
          $(".text-truncate-2", btn).textContent = p.title;
          $(".text-muted", btn).textContent = `${p.price}${p.category ? " · " + p.category : ""}`;
          btn.addEventListener("click", () => addRow(p));
          picker.append(btn);
        });
      if (!picker.children.length) picker.innerHTML = '<p class="text-muted small p-3 mb-0">No matching products.</p>';
    };

    const addRow = (p) => {
      const row = $("[data-row-template]", editor).content.firstElementChild.cloneNode(true);
      $('input[name="product_id[]"]', row).value = p.id;
      $("img", row).src = p.img;
      $("[data-title]", row).textContent = p.title;
      $("[data-price]", row).textContent = p.price;
      // New rows reuse the animation of the last row so they blend in.
      const last = $$(".sortable-row", rows).pop();
      if (last) {
        $("select", row).value = $("select", last).value;
        $('input[name="wow_delay[]"]', row).value = $('input[name="wow_delay[]"]', last).value;
      }
      rows.insertBefore(row, empty);
      refresh();
    };

    rows.addEventListener("click", (e) => {
      const row = e.target.closest(".sortable-row");
      if (!row) return;
      if (e.target.closest("[data-remove]")) {
        row.remove();
        refresh();
        return;
      }
      const move = e.target.closest("[data-move]");
      if (move) {
        if (move.dataset.move === "-1" && row.previousElementSibling) rows.insertBefore(row, row.previousElementSibling);
        if (move.dataset.move === "1" && row.nextElementSibling?.classList.contains("sortable-row")) rows.insertBefore(row.nextElementSibling, row);
        row.classList.remove("flash");
        void row.offsetWidth;
        row.classList.add("flash");
        refresh();
      }
    });
    search.addEventListener("input", renderPicker);
    refresh();
  }
})();

// Orders: changing a filter dropdown applies it straight away.
document.querySelectorAll("[data-auto-filter] select").forEach((select) => {
  select.addEventListener("change", () => select.form.submit());
});

// Order lists: clicking anywhere on a row opens the order (Ctrl/⌘ or middle click opens it in a new tab).
// Links and buttons inside the row keep working as usual; the order number link is the keyboard target.
document.addEventListener("click", (e) => {
  const row = e.target.closest("tr[data-href]");
  if (!row || e.target.closest("a, button, input, select, textarea, label")) return;
  if (window.getSelection && String(window.getSelection()).length) return; // selecting text, not clicking
  if (e.ctrlKey || e.metaKey) window.open(row.dataset.href, "_blank");
  else window.location.href = row.dataset.href;
});
document.addEventListener("auxclick", (e) => {
  const row = e.target.closest("tr[data-href]");
  if (row && e.button === 1 && !e.target.closest("a, button")) window.open(row.dataset.href, "_blank");
});

// Product form: category tree. A ticked subcategory ticks its main category; unticking a main category
// clears its subcategories. The arrow shows or hides a category's subcategories.
(function () {
  const tree = document.querySelector("[data-cat-tree]");
  if (!tree) return;
  const setOpen = (group, open) => {
    const subs = group.querySelector(".subcat-group");
    const btn = group.querySelector("[data-cat-toggle]");
    if (subs) subs.hidden = !open;
    btn?.setAttribute("aria-expanded", String(open));
  };
  tree.addEventListener("change", (e) => {
    const group = e.target.closest("[data-cat-group]");
    if (!group) return;
    const main = group.querySelector("[data-cat-main]");
    if (e.target.matches("[data-cat-sub]") && e.target.checked) main.checked = true;
    if (e.target === main) {
      if (!main.checked) group.querySelectorAll("[data-cat-sub]").forEach((b) => (b.checked = false));
      else setOpen(group, true);
    }
    group.classList.toggle("is-on", main.checked);
  });
  tree.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-cat-toggle]");
    if (btn) setOpen(btn.closest("[data-cat-group]"), btn.getAttribute("aria-expanded") !== "true");
  });
})();

// Product form: pictures list. Drag (or ‹ ›) to reorder, delete, add new ones. The first is the main image,
// the second the hover image. New files are kept in the file input in the order they appear in the list.
(function () {
  const list = document.querySelector("[data-pic-list]");
  const input = document.querySelector("[data-pic-input]");
  if (!list || !input) return;
  const items = () => [...list.querySelectorAll("[data-pic]")];
  const sync = () => {
    const dt = new DataTransfer();
    items().forEach((li, i) => {
      if (li._file) {
        li.querySelector("input").value = "n:" + dt.files.length;
        dt.items.add(li._file);
      }
      const badge = li.querySelector("[data-pic-badge]");
      badge.textContent = i === 0 ? "Main" : i === 1 ? "Hover" : "";
      badge.hidden = i > 1;
      li.querySelector('[data-pic-move="-1"]').disabled = i === 0;
      li.querySelector('[data-pic-move="1"]').disabled = i === items().length - 1;
    });
    input.files = dt.files;
  };
  const template = (src) => {
    const li = document.createElement("li");
    li.className = "pic-item";
    li.draggable = true;
    li.dataset.pic = "";
    li.innerHTML = list.querySelector("[data-pic]")?.innerHTML ?? `<img alt=""><span class="pic-badge" data-pic-badge></span><div class="pic-actions"><button type="button" data-pic-move="-1" aria-label="Move earlier">‹</button><button type="button" data-pic-move="1" aria-label="Move later">›</button><button type="button" class="pic-delete" data-pic-delete aria-label="Delete picture">✕</button></div><input type="hidden" name="image_order[]">`;
    li.querySelector("img").src = src;
    li.querySelector("img").alt = "New picture";
    return li;
  };
  input.addEventListener("change", () => {
    const already = items().filter((li) => li._file).map((li) => li._file);
    [...input.files].forEach((file) => {
      if (already.includes(file)) return;
      const li = template(URL.createObjectURL(file));
      li._file = file;
      li.classList.add("is-new");
      list.append(li);
    });
    sync();
  });
  list.addEventListener("click", (e) => {
    const li = e.target.closest("[data-pic]");
    if (!li) return;
    if (e.target.closest("[data-pic-delete]")) {
      if (li._file) URL.revokeObjectURL(li.querySelector("img").src);
      li.remove();
      sync();
      return;
    }
    const move = e.target.closest("[data-pic-move]");
    if (move) {
      const step = Number(move.dataset.picMove);
      const target = step < 0 ? li.previousElementSibling : li.nextElementSibling?.nextElementSibling;
      list.insertBefore(li, target || null);
      if (step > 0 && !li.nextElementSibling && target === undefined) list.append(li);
      sync();
      move.focus();
    }
  });
  // Drag and drop
  let dragged = null;
  list.addEventListener("dragstart", (e) => {
    dragged = e.target.closest("[data-pic]");
    dragged?.classList.add("is-dragging");
    e.dataTransfer.effectAllowed = "move";
  });
  list.addEventListener("dragover", (e) => {
    if (!dragged) return;
    e.preventDefault();
    const over = e.target.closest("[data-pic]");
    if (!over || over === dragged) return;
    const r = over.getBoundingClientRect();
    const after = e.clientX > r.left + r.width / 2;
    list.insertBefore(dragged, after ? over.nextElementSibling : over);
  });
  list.addEventListener("dragend", () => {
    dragged?.classList.remove("is-dragging");
    dragged = null;
    sync();
  });
  sync();
})();

// Products: Product finder pop-up (same behaviour as the shop page). Switching item shows its brands/specs
// (the others are not sent), "Any" clears a group, and empty amount boxes are left out of the link.
document.querySelectorAll("[data-finder]").forEach((form) => {
  const showItem = (slug) => {
    form.querySelectorAll("[data-finder-group]").forEach((g) => {
      const on = g.dataset.finderGroup === slug;
      g.hidden = !on;
      g.querySelectorAll("fieldset").forEach((f) => (f.disabled = !on));
    });
  };
  form.querySelectorAll("[data-finder-item]").forEach((r) => r.addEventListener("change", () => showItem(r.value)));
  form.querySelectorAll("[data-finder-any]").forEach((btn) => {
    btn.addEventListener("click", () => {
      btn.closest("fieldset").querySelectorAll(`input[name="${btn.dataset.finderAny}[]"]`).forEach((r) => (r.checked = false));
    });
  });
  form.addEventListener("submit", () => {
    form.querySelectorAll("input[name=min], input[name=max]").forEach((i) => (i.disabled = i.value.trim() === ""));
  });
});

// SEO: "Use suggestion" buttons copy the automatic text into a field (asking first if the field has text).
document.addEventListener("click", (e) => {
  const btn = e.target.closest("[data-fill]");
  if (!btn) return;
  const field = document.querySelector(btn.dataset.fill);
  if (!field) return;
  if (field.value.trim() !== "" && field.value !== btn.dataset.value && !window.confirm(btn.dataset.fillConfirm || "Replace what is in this field with the suggestion?")) return;
  field.value = btn.dataset.value;
  field.dispatchEvent(new Event("input", { bubbles: true }));
  field.focus();
});

// SEO: character counters for search titles and descriptions (the number turns red past the limit).
document.querySelectorAll("[data-count]").forEach((field) => {
  const max = Number(field.dataset.count);
  const out = document.createElement("div");
  out.className = "seo-counter";
  out.setAttribute("aria-live", "polite");
  field.insertAdjacentElement("afterend", out);
  const update = () => {
    const n = (field.value || field.placeholder || "").length;
    out.textContent = `${n} / ${max} characters${field.value ? "" : " (automatic)"}`;
    out.classList.toggle("is-over", n > max);
  };
  field.addEventListener("input", update);
  update();
});

// SEO: Google preview follows the title and description fields (empty = the automatic text).
document.querySelectorAll("[data-serp]").forEach((serp) => {
  const title = serp.querySelector("[data-serp-title]");
  const desc = serp.querySelector("[data-serp-desc]");
  document.querySelectorAll("[data-serp-input]").forEach((field) => {
    field.addEventListener("input", () => {
      const target = field.dataset.serpInput === "title" ? title : desc;
      target.textContent = field.value.trim() || (field.dataset.serpInput === "title" ? serp.dataset.autoTitle : serp.dataset.autoDesc) || field.placeholder;
    });
  });
});

// Product editor: links to "#seo" open the SEO card.
if (location.hash === "#seo") {
  const card = document.getElementById("seo");
  if (card && card.tagName === "DETAILS") {
    card.open = true;
    setTimeout(() => card.scrollIntoView({ block: "start" }), 50);
  }
}

// SEO list: select all / count of ticked products.
document.querySelectorAll("[data-seo-bulk]").forEach((form) => {
  const all = form.querySelector("[data-check-all]");
  const boxes = () => [...form.querySelectorAll("[data-row-check]")];
  const count = form.querySelector("[data-selected-count]");
  const update = () => {
    const n = boxes().filter((b) => b.checked).length;
    if (count) count.textContent = `${n} selected`;
    if (all) all.checked = n > 0 && n === boxes().length;
  };
  all?.addEventListener("change", () => {
    boxes().forEach((b) => (b.checked = all.checked));
    update();
  });
  form.addEventListener("change", (e) => {
    if (e.target.matches("[data-row-check]")) update();
  });
});
