/**
 * Cart Management JavaScript
 * Handles cart page functionality with live preview
 * Version: 2.1.0 - Fixed AJAX initialization issues
 */

const APDCart = {
  cart: {},
  total: 0,
  count: 0,
  initialized: false,

  init: function () {
    if (this.initialized) {
      console.log("🔄 Cart already initialized, skipping");
      return;
    }
    this.initialized = true;
    // Inject lightweight styles for checkbox placement and selected state
    // Styling is provided by the project's stylesheet (`assets/css/cart.css`).
    // Avoid injecting runtime styles which may conflict with the theme's CSS.
    console.log("🚀 Initializing APDCart...");
    this.loadCart();
    this.bindEvents();
  },

  bindEvents: function () {
    const self = this;
    const jQuery = window.jQuery || window.$;
    if (!jQuery) {
      console.error("❌ jQuery not available for event binding");
      return;
    }

    // Clear cart button
    jQuery("#apd-clear-cart").on("click", function (e) {
      e.preventDefault();
      self.clearCart();
    });

    // Update quantity buttons
    jQuery(document).on("click", ".apd-qty-plus", function () {
      const itemId = jQuery(this).data("item-id");
      const currentQty = parseInt(jQuery("#qty-" + itemId).val());
      self.updateQuantity(itemId, currentQty + 1);
    });

    jQuery(document).on("click", ".apd-qty-minus", function () {
      const itemId = jQuery(this).data("item-id");
      const currentQty = parseInt(jQuery("#qty-" + itemId).val());
      if (currentQty > 1) {
        self.updateQuantity(itemId, currentQty - 1);
      }
    });

    // Quantity input change
    jQuery(document).on("change", ".apd-quantity-input", function () {
      const itemId = jQuery(this).data("item-id");
      const quantity = parseInt(jQuery(this).val());
      if (quantity > 0) {
        self.updateQuantity(itemId, quantity);
      }
    });

    // Remove item button
    jQuery(document).on("click", ".apd-remove-item", function (e) {
      e.preventDefault();
      const itemId = jQuery(this).data("item-id");
      self.removeItem(itemId);
    });

    // Per-item selection checkbox
    jQuery(document).on("change", ".apd-item-checkbox", function () {
      const id = jQuery(this).data("item-id");
      const checked = jQuery(this).is(":checked");

      // Immediately apply visual feedback
      const $cartItem = jQuery(this).closest(".apd-cart-item");
      if (checked) {
        $cartItem.addClass("apd-item-selected");
      } else {
        $cartItem.removeClass("apd-item-selected");
      }

      try {
        let selected = [];
        try {
          selected =
            JSON.parse(localStorage.getItem("apd_cart_selected") || "[]") || [];
        } catch (_) {
          selected = [];
        }
        selected = (selected || []).map((s) => String(s));
        const sid = String(id);
        if (checked && selected.indexOf(sid) === -1) selected.push(sid);
        if (!checked) selected = selected.filter((s) => s !== sid);
        localStorage.setItem("apd_cart_selected", JSON.stringify(selected));
      } catch (e) {
        console.warn("apd_cart_selected storage failed", e);
      }
      try {
        self.updateSelectedTotal();
      } catch (_) {}
    });

    // Header select-all checkbox
    jQuery(document).on(
      "change",
      "#apd-select-all-checkbox, #apd-header-select-checkbox",
      function () {
        const checked = jQuery(this).is(":checked");

        // Immediately apply visual feedback to all items
        jQuery(".apd-cart-item").each(function () {
          const $item = jQuery(this);
          const $checkbox = $item.find(".apd-item-checkbox");
          $checkbox.prop("checked", checked);
          if (checked) {
            $item.addClass("apd-item-selected");
          } else {
            $item.removeClass("apd-item-selected");
          }
        });

        try {
          const allIds = Object.values(self.cart || {}).map((i) =>
            String(i.id),
          );
          let selected = [];
          try {
            selected =
              JSON.parse(localStorage.getItem("apd_cart_selected") || "[]") ||
              [];
          } catch (_) {
            selected = [];
          }
          selected = (selected || []).map((s) => String(s));
          if (checked) {
            allIds.forEach((id) => {
              if (selected.indexOf(id) === -1) selected.push(id);
            });
          } else {
            selected = selected.filter((s) => allIds.indexOf(s) === -1);
          }
          localStorage.setItem("apd_cart_selected", JSON.stringify(selected));
        } catch (e) {
          console.warn("header select failed", e);
        }
        try {
          self.updateSelectedTotal();
        } catch (_) {}
      },
    );
  },

  loadCart: function () {
    const self = this;

    // Check if apd_ajax is available, if not try to get it from window
    let ajaxConfig;
    try {
      ajaxConfig =
        window.apd_ajax || (typeof apd_ajax !== "undefined" ? apd_ajax : null);
    } catch (e) {
      ajaxConfig = null;
    }

    if (!ajaxConfig || !ajaxConfig.ajax_url) {
      console.error(
        "❌ apd_ajax not available. Available globals:",
        Object.keys(window).filter((k) => k.includes("ajax")),
      );
      console.error("❌ window.apd_ajax:", window.apd_ajax);
      console.error("❌ typeof apd_ajax:", typeof apd_ajax);
      // Try fallback with hardcoded values
      self.loadCartFallback();
      return;
    }

    console.log("🔧 Using AJAX config:", ajaxConfig);

    // Make sure jQuery is available
    const jQuery = window.jQuery || window.$;
    if (!jQuery || typeof jQuery.ajax === "undefined") {
      console.error("❌ jQuery or jQuery.ajax not available");
      self.loadCartFallback();
      return;
    }

    jQuery.ajax({
      url: ajaxConfig.ajax_url,
      type: "POST",
      data: {
        action: "apd_get_cart",
        nonce: ajaxConfig.nonce,
      },
      success: function (response) {
        console.log("🔍 Raw cart response:", response);
        if (response.success) {
          self.cart = response.data.cart;
          self.total = response.data.total;
          self.count = response.data.count;
          // Default behavior: select all items on initial load
          try {
            const allIds = Object.values(self.cart || {}).map((i) =>
              String(i.id),
            );
            localStorage.setItem("apd_cart_selected", JSON.stringify(allIds));
            // Ensure header select-all checkbox reflects default
            const headerSel = jQuery(
              "#apd-select-all-checkbox, #apd-header-select-checkbox",
            );
            if (headerSel && headerSel.length) headerSel.prop("checked", true);
          } catch (_) {}
          try {
            console.log("APD Cart loaded:", {
              count: self.count,
              total: self.total,
              itemsKeys: self.cart ? Object.keys(self.cart) : [],
              firstItem: self.cart ? Object.values(self.cart)[0] : null,
            });
            self.renderCart();
          } catch (e) {
            console.error("Render cart failed:", e);
            self.renderFallback();
          }
        } else {
          console.error("Cart response failed:", response);
        }
      },
      error: function () {
        console.error("Error loading cart");
        self.loadCartFallback();
      },
    });
  },

  loadCartFallback: function () {
    console.log("🔄 Using fallback cart loading");
    // Create a simple fallback cart with sample data
    this.cart = {
      fallback_item: {
        id: "fallback_item",
        product_id: 1,
        product_name: "Sample Product",
        price: 29.99,
        quantity: 1,
        total: 29.99,
        customization_data: {
          print_color: "black",
          vinyl_material: "Solid",
        },
        added_at: new Date().toISOString(),
      },
    };
    this.total = 29.99;
    this.count = 1;
    // Default behavior: select all items on initial load (fallback path)
    try {
      const allIds = Object.values(this.cart || {}).map((i) => String(i.id));
      localStorage.setItem("apd_cart_selected", JSON.stringify(allIds));
      const jQuery = window.jQuery || window.$;
      if (jQuery) {
        jQuery("#apd-select-all-checkbox, #apd-header-select-checkbox").prop(
          "checked",
          true,
        );
      }
    } catch (_) {}
    this.renderCart();
  },

  renderCart: function () {
    // Use jQuery instead of $ to avoid conflicts
    const jQuery = window.jQuery || window.$;
    if (!jQuery) {
      console.error("❌ jQuery not available for rendering");
      return;
    }

    const $container = jQuery("#apd-cart-items");
    const $count = jQuery(".apd-cart-count");
    const $total = jQuery(".apd-cart-total");

    if (Object.keys(this.cart).length === 0) {
      $container.html(`
                <div class="apd-cart-empty">
                    <div class="apd-empty-icon">🛒</div>
                    <h3>Your cart is empty</h3>
                    <p>Add some products to get started!</p>
                    <a href="/product" class="apd-btn apd-btn-primary">Browse Products</a>
                </div>
            `);
      $count.text("0 items");
      $total.text("Total: $0.00");
      return;
    }

    let html = "";
    let clientTotal = 0;
    Object.values(this.cart).forEach((item) => {
      try {
        html += this.renderCartItem(item);
        const price = parseFloat(item.price || 0);
        const qty = parseInt(item.quantity || 0);
        if (!isNaN(price) && !isNaN(qty)) clientTotal += price * qty;
      } catch (e) {
        console.error("Failed to render cart item:", e, item);
      }
    });

    // If nothing rendered but we have items, try fallback
    if (!html && this.count > 0) {
      console.warn(
        "No HTML generated for cart items, using fallback renderer. Raw cart:",
        this.cart,
      );
      return this.renderFallback();
    }
    $container.html(html);
    $count.text(this.count + " item" + (this.count !== 1 ? "s" : ""));
    const totalToShow = this.total && this.total > 0 ? this.total : clientTotal;
    $total.text("Total: $" + Number(totalToShow || 0).toFixed(2));
    // Ensure selected total is present and updated
    if (jQuery(".apd-selected-total").length === 0) {
      jQuery(".apd-cart-summary").append(
        '<span class="apd-selected-total">Selected: $0.00</span>',
      );
    }
    try {
      this.updateSelectedTotal();
    } catch (_) {}
  },

  // Minimal fallback renderer to guarantee visibility
  renderFallback: function () {
    const jQuery = window.jQuery || window.$;
    if (!jQuery) {
      console.error("❌ jQuery not available for fallback rendering");
      return;
    }

    const $container = jQuery("#apd-cart-items");
    const itemsArray = [];
    try {
      for (const k in this.cart || {}) {
        itemsArray.push(this.cart[k]);
      }
    } catch (e) {}
    const html = itemsArray
      .map((item, idx) => {
        const name = (item && item.product_name) || "Item " + (idx + 1);
        const qty = (item && item.quantity) || 1;
        const price =
          item && item.price ? Number(item.price).toFixed(2) : "0.00";
        return `
                <div class="apd-cart-item" data-item-id="${item?.id || "unknown_" + idx}">
                    <div class="apd-cart-item-details">
                        <label class="apd-item-select-inline">
                            <input type="checkbox" class="apd-item-checkbox" data-item-id="${item?.id || "unknown_" + idx}" />
                            <span class="apd-item-select-ui"></span>
                        </label>
                        <h3 class="apd-item-title">${name}</h3>
                        <div class="apd-item-pricing">$${price} × ${qty}</div>
                    </div>
                </div>
            `;
      })
      .join("");
    $container.html(
      html || '<div class="apd-cart-empty"><p>No items to display.</p></div>',
    );
  },

  renderCartItem: function (item) {
    console.log("🎨 Rendering cart item:", item);
    try {
      const customizationData = item.customization_data || {};
      const previewImage =
        customizationData.preview_image_svg ||
        customizationData.preview_image_png ||
        customizationData.image_url ||
        customizationData.customization_image_url ||
        "";
      // Check localStorage for persisted selection so the UI can render selected state immediately
      let isSelected = false;
      try {
        const stored = JSON.parse(
          localStorage.getItem("apd_cart_selected") || "[]",
        );
        const mapped = (stored || []).map((s) => String(s));
        isSelected = mapped.indexOf(String(item.id)) !== -1;
      } catch (e) {
        isSelected = false;
      }

      const html = `
        <div class="${isSelected ? " apd-item-selected" : ""} apd-cart-item" data-item-id="${item.id || "unknown"}">
                    <div class="apd-cart-item-preview">
                        ${
                          previewImage
                            ? `
                        <img class="apd-preview-img" src="${previewImage}" alt="Preview of ${item.product_name || "Product"}"/>
                        `
                            : `
                        <div class="apd-live-preview">
                            <div class="apd-preview-title">Live Preview</div>
                            <div class="apd-preview-area">
                                <div class="apd-preview-content">
                                    ${this.renderLivePreview(item, customizationData)}
                                </div>
                            </div>
                        </div>
                        `
                        }
                    </div>
                    
                    <div class="apd-cart-item-details">
                        <div class="flex justify-between">
                            <h3 class="apd-item-title">${item.product_name || "Unknown Product"}</h3>
              <label class="apd-item-select-inline">
                <input type="checkbox" class="apd-item-checkbox" data-item-id="${item.id || "unknown"}" ${isSelected ? "checked" : ""} />
                <span class="apd-item-select-ui" aria-hidden="true"></span>
              </label>
                        </div>
                        
                        <div class="apd-item-customization">
                            <h4>Customization Details:</h4>
                            <ul class="apd-customization-list">
                                ${this.renderCustomizationDetails(customizationData)}
                            </ul>
                        </div>
                        
                        <div class="apd-item-pricing">
                            <div class="apd-item-price">$${(item.price || 0).toFixed(2)} each</div>
                            <div class="apd-item-total">Total: $${(item.total || 0).toFixed(2)}</div>
                        </div>
                        
                        <div class="apd-item-controls">
                            <div class="apd-quantity-controls">
                                <button class="apd-qty-btn apd-qty-minus" data-item-id="${item.id || "unknown"}">-</button>
                                <input type="number" id="qty-${item.id || "unknown"}" class="apd-quantity-input" 
                                       value="${item.quantity || 1}" min="1" max="100" data-item-id="${item.id || "unknown"}">
                                <button class="apd-qty-btn apd-qty-plus" data-item-id="${item.id || "unknown"}">+</button>
                            </div>
                            
                            <button class="apd-btn apd-btn-danger apd-remove-item" data-item-id="${item.id || "unknown"}">
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
            `;
      console.log("✅ Cart item rendered successfully");
      return html;
    } catch (e) {
      console.error("❌ Failed to render cart item:", e, item);
      return `<div class="apd-cart-item-error">Error rendering item: ${e.message}</div>`;
    }
  },

  renderLivePreview: function (item, customizationData) {
    // Create live preview similar to customizer
    const material = customizationData.vinyl_material || "Solid";
    const color = customizationData.print_color || "#000000";
    const materialUrl = customizationData.material_texture_url || "";

    let previewHtml = `
            <div class="apd-preview-canvas" style="background: ${color};">
        `;

    // Add material texture if available
    if (materialUrl && material !== "Solid") {
      previewHtml += `
                <div class="apd-material-overlay" style="background-image: url('${materialUrl}');"></div>
            `;
    }

    // Add text fields from customization
    if (customizationData.text_fields) {
      Object.entries(customizationData.text_fields).forEach(
        ([fieldId, text]) => {
          if (text && text.trim()) {
            previewHtml += `
                        <div class="apd-preview-text" data-field="${fieldId}">${text}</div>
                    `;
          }
        },
      );
    }

    // Add template data
    if (customizationData.template_data) {
      Object.entries(customizationData.template_data).forEach(
        ([fieldId, value]) => {
          if (value && value.toString().trim()) {
            previewHtml += `
                        <div class="apd-preview-field" data-field="${fieldId}">${value}</div>
                    `;
          }
        },
      );
    }

    previewHtml += "</div>";
    return previewHtml;
  },

  renderCustomizationDetails: function (customizationData) {
    let details = [];

    if (customizationData.print_color) {
      details.push(
        `<li><strong>Print Color:</strong> ${customizationData.print_color}</li>`,
      );
    }

    if (customizationData.vinyl_material) {
      details.push(
        `<li><strong>Material:</strong> ${customizationData.vinyl_material}</li>`,
      );
    }

    if (customizationData.text_fields) {
      Object.entries(customizationData.text_fields).forEach(
        ([fieldId, text]) => {
          if (text && text.trim()) {
            const label = fieldId
              .replace(/_/g, " ")
              .replace(/\b\w/g, (l) => l.toUpperCase());
            details.push(`<li><strong>${label}:</strong> ${text}</li>`);
          }
        },
      );
    }

    if (customizationData.template_data) {
      Object.entries(customizationData.template_data).forEach(
        ([fieldId, value]) => {
          if (value && value.toString().trim()) {
            const label = fieldId
              .replace(/_/g, " ")
              .replace(/\b\w/g, (l) => l.toUpperCase());
            details.push(`<li><strong>${label}:</strong> ${value}</li>`);
          }
        },
      );
    }

    return details.join("");
  },

  updateSelectedTotal: function () {
    const jQuery = window.jQuery || window.$;
    if (!jQuery) return;
    let selected = [];
    try {
      selected =
        JSON.parse(localStorage.getItem("apd_cart_selected") || "[]") || [];
    } catch (_) {
      selected = [];
    }
    selected = (selected || []).map((s) => String(s));
    let total = 0;
    const present = Object.values(this.cart || {}).map((i) => String(i.id));
    for (const it of Object.values(this.cart || {})) {
      if (!it || !it.id) continue;
      const id = String(it.id);
      if (selected.indexOf(id) !== -1) {
        const price = parseFloat(it.price || 0) || 0;
        const qty = parseInt(it.quantity || 0) || 0;
        if (!isNaN(price) && !isNaN(qty)) total += price * qty;
      }
      const $item = jQuery('.apd-cart-item[data-item-id="' + it.id + '"]');
      const $chk = jQuery('.apd-item-checkbox[data-item-id="' + it.id + '"]');
      if ($chk.length)
        $chk.prop("checked", selected.indexOf(String(it.id)) !== -1);
      if ($item.length) {
        if (selected.indexOf(String(it.id)) !== -1)
          $item.addClass("apd-item-selected");
        else $item.removeClass("apd-item-selected");
      }
    }
    const $sel = jQuery(".apd-selected-total");
    if ($sel.length) $sel.text("Selected: $" + Number(total || 0).toFixed(2));
    // sync header checkbox
    const $header = jQuery(
      "#apd-select-all-checkbox, #apd-header-select-checkbox",
    );
    if ($header.length) {
      const allSelected =
        present.length && present.every((id) => selected.indexOf(id) !== -1);
      $header.prop("checked", !!allSelected);
    }
    // cleanup storage
    try {
      localStorage.setItem(
        "apd_cart_selected",
        JSON.stringify(selected.filter((s) => present.indexOf(s) !== -1)),
      );
    } catch (_) {}
  },

  updateQuantity: function (itemId, quantity) {
    const self = this;
    const ajaxConfig = window.apd_ajax || apd_ajax;
    if (!ajaxConfig) return;

    const jQuery = window.jQuery || window.$;
    if (!jQuery) return;

    jQuery.ajax({
      url: ajaxConfig.ajax_url,
      type: "POST",
      data: {
        action: "apd_update_cart_item",
        nonce: ajaxConfig.nonce,
        cart_item_id: itemId,
        quantity: quantity,
      },
      success: function (response) {
        if (response.success) {
          self.loadCart(); // Reload cart to update totals
        } else {
          alert("Error updating quantity");
        }
      },
      error: function () {
        alert("Network error occurred");
      },
    });
  },

  removeItem: function (itemId) {
    if (!confirm("Are you sure you want to remove this item from your cart?")) {
      return;
    }

    const self = this;
    const ajaxConfig = window.apd_ajax || apd_ajax;
    if (!ajaxConfig) return;

    const jQuery = window.jQuery || window.$;
    if (!jQuery) return;

    jQuery.ajax({
      url: ajaxConfig.ajax_url,
      type: "POST",
      data: {
        action: "apd_remove_cart_item",
        nonce: ajaxConfig.nonce,
        cart_item_id: itemId,
      },
      success: function (response) {
        if (response.success) {
          self.loadCart(); // Reload cart
        } else {
          alert("Error removing item");
        }
      },
      error: function () {
        alert("Network error occurred");
      },
    });
  },

  clearCart: function () {
    if (!confirm("Are you sure you want to clear your entire cart?")) {
      return;
    }

    const self = this;
    const ajaxConfig = window.apd_ajax || apd_ajax;
    if (!ajaxConfig) return;

    const jQuery = window.jQuery || window.$;
    if (!jQuery) return;

    jQuery.ajax({
      url: ajaxConfig.ajax_url,
      type: "POST",
      data: {
        action: "apd_clear_cart",
        nonce: ajaxConfig.nonce,
      },
      success: function (response) {
        if (response.success) {
          self.loadCart(); // Reload cart
        } else {
          alert("Error clearing cart");
        }
      },
      error: function () {
        alert("Network error occurred");
      },
    });
  },

  proceedToCheckout: function(e) {
    // Ensure selected items are saved before redirecting
    const jQuery = window.jQuery || window.$;
    if (!jQuery) {
      console.warn('[APD Cart] jQuery not available, proceeding without saving selection');
      return true; // Allow default link behavior
    }

    try {
      // IMPORTANT: Clear any checkout payloads when proceeding from cart
      // This ensures cart checkout takes priority over instant checkout payloads
      try {
        localStorage.removeItem('apd_checkout_payload_oneclick');
        localStorage.removeItem('apd_checkout_payload');
        console.log('[APD Cart] Cleared checkout payloads to ensure cart checkout');
      } catch (clearErr) {
        console.warn('[APD Cart] Error clearing checkout payloads:', clearErr);
      }

      // Get current selected items
      let selected = [];
      try {
        selected = JSON.parse(localStorage.getItem("apd_cart_selected") || "[]") || [];
      } catch (_) {
        selected = [];
      }
      selected = (selected || []).map((s) => String(s));

      // If no items selected, select all items by default
      if (selected.length === 0 && this.cart && Object.keys(this.cart).length > 0) {
        const allIds = Object.values(this.cart || {}).map((i) => {
          // Use id first, then cart_item_id, then try to get from object key
          return String(i.id || i.cart_item_id || '');
        }).filter(id => id); // Remove empty IDs
        selected = allIds;
        localStorage.setItem("apd_cart_selected", JSON.stringify(selected));
        console.log('[APD Cart] No items selected, auto-selecting all items:', selected);
      }

      // Validate that we have at least one item selected
      if (selected.length === 0) {
        e.preventDefault();
        alert('Please select at least one item to checkout.');
        return false;
      }

      console.log('[APD Cart] Proceeding to checkout with selected items:', selected);
      console.log('[APD Cart] Cart items:', Object.values(this.cart || {}).map(i => ({ id: i.id, cart_item_id: i.cart_item_id, product_name: i.product_name })));
      
      // Allow default link behavior to proceed
      return true;
    } catch (err) {
      console.error('[APD Cart] Error in proceedToCheckout:', err);
      // Allow default link behavior even on error
      return true;
    }
  },
};

// Initialize when document is ready
jQuery(document).ready(function ($) {
  let attempts = 0;
  const maxAttempts = 50; // 5 seconds max wait

  // Wait for apd_ajax to be available
  function initCart() {
    attempts++;
    let ajaxConfig;
    try {
      ajaxConfig =
        window.apd_ajax || (typeof apd_ajax !== "undefined" ? apd_ajax : null);
    } catch (e) {
      ajaxConfig = null;
    }

    if (ajaxConfig && ajaxConfig.ajax_url) {
      console.log("✅ apd_ajax found, initializing cart");
      APDCart.init();
    } else if (attempts < maxAttempts) {
      console.log("⏳ Waiting for apd_ajax... attempt", attempts);
      console.log("⏳ window.apd_ajax:", window.apd_ajax);
      console.log("⏳ typeof apd_ajax:", typeof apd_ajax);
      setTimeout(initCart, 100);
    } else {
      console.error(
        "❌ apd_ajax not found after",
        maxAttempts,
        "attempts. Available globals:",
        Object.keys(window).filter((k) => k.includes("ajax")),
      );
      // Try to create a basic config as fallback
      window.apd_ajax = {
        ajax_url: "/wp-admin/admin-ajax.php",
        nonce: "fallback",
      };
      console.log("🔄 Using fallback AJAX config");
      APDCart.init();
    }
  }

  initCart();
});
