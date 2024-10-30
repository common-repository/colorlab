(function ($) {
  'use strict';

  // Product data
  var data = null; // Data retrieved from the back-end
  var initialAddToCartText = ''; // Variable to store the initial ATC button text when the product page is loaded before the UI is binded
  var variationId = ''; // Variable to store the active product variant ID

  // Helper to check if personalization is enabled for the currently selected variant on the product page
  function isPrintlaneActiveForCurrentVariant() {
    // Check if the parent element of the add to cart button contains a variation_id el.
    // We only check the parent el because in case of a product of type bundle the child products can be variable products.
    // In that case a variant selection is possible but we should not act on it as we only support linking printlane to the bundle product itself not the childs.
    const variantIdEl = $(data.button_selector).parent().find('.variation_id');

    // exit early in case no variation id, this is a simple product with variations
    if (!variantIdEl.length) return true;

    // Update the active variant id
    variationId = variantIdEl.val();

    // Return
    return !!(data.variations && typeof data.variations === 'object' && data.variations[variationId]);
  }

  // Helpers to update design thumbnails
  function updateThumbnail(link) {
    // Exit early if no link is given
    if (!link) return;

    // Get the id & token
    var id = link.getAttribute('data-colorlab-id');
    var token = link.getAttribute('data-colorlab-token');

    // Exit early if no id & token are present
    if (!id || !token || !printlane.loadThumbnail) return;

    // Get an URL to the thumbnail
    printlane.loadThumbnail(id + '.' + token)
      .then((url) => {
        // Exit early if no url
        if (!url) return;

        // Get the row closest to the link
        var cartItemEl = $(link).closest('tr');
        if (!cartItemEl.length) cartItemEl = $(link).closest('.wc-block-components-order-summary-item');
        if (!cartItemEl.length) cartItemEl = $(link).closest('li');

        // Exit early if no row is found
        if (!cartItemEl.length) return;

        // Get the image
        var img = cartItemEl.find('img');

        // Exit early if no image is found
        if (!img.length) return;

        // Remove the srcset, data-src and data-srcset attribute
        img[0].getAttribute('srcset');
        img[0].removeAttribute('srcset');
        img[0].getAttribute('data-src');
        img[0].removeAttribute('data-src');
        img[0].getAttribute('data-srcset');
        img[0].removeAttribute('data-srcset');

        // Replace the image source with the blob
        img[0].src = url;
      })
      .catch((err) => {
        console.error('Printlane: unable to load thumbnail due to error', err);
      });
  }

  // Triggered when clicking on a link to edit a design
  function onEditDesign(designId, designToken) {
    printlane.open({
      shop: data.shop,
      id: designId,
      token: designToken,
      language: data.language,
      options: {
        generateThumbnail: data.cart_thumbnails === 'yes',
        generateThumbnailType: 'base64'
      },
      callback: function () {
        // Close the designer immediately
        printlane.close();

        // Update thumbnails
        var els = document.querySelectorAll('.colorlab-edit-personalisation');
        for (var i = 0; i < els.length; i++) {
          updateThumbnail(els[i]);
        }
      }
    });
  }

  // Triggered when clicking on the personalize button
  function onCreateDesign($form) {
    var productId = data.product;
    if (variationId && data.variations[variationId] !== undefined) {
      productId = data.variations[variationId];
    }

    printlane.open({
      shop: data.shop,
      product: {
        id: productId
      },
      language: data.language,
      options: {
        generateThumbnail: data.cart_thumbnails === 'yes',
        generateThumbnailType: 'base64'
      },
      callback: function (id, token) {
        // Close the designer
        printlane.close();

        // Check for the add-to-cart value
        var adcEls = $form[0].querySelectorAll('input[name="add-to-cart"]');
        if (!adcEls.length) {
          adcEls = $form[0].querySelectorAll('*[name="add-to-cart"]');
          if (adcEls.length) {
            $("<input type='hidden' name='add-to-cart' />").val($(adcEls[0]).val()).appendTo($form);
          }
        }

        $("<input type='hidden' name='colorlab_id'/>").val(id).appendTo($form);
        $("<input type='hidden' name='colorlab_token'/>").val(token).appendTo($form);

        // use the raw dom element, to not trigger recursion
        $form[0].submit();
      },
      events: {
        beforeOpen: function() {
          try {
            // Run woocommerce-product-add-ons-ultimate validation before opening the Designer and check if form is valid.
            const isValid = $(document).triggerHandler('pewc_trigger_js_validation');
            if (typeof isValid === 'boolean') return isValid;
            // Plugin not installed, proceed opening the designer
          } catch(e) { /* proceed opening the designer */ }
        }
      },
    });
  }

  // Observes if the mini cart is shown to the user and binds the printlane cart ui
  function replacePrintlaneLinksOnCartAndMinicart(items) {
    function whenPrintlaneLinkElementReplaceValue(node) {
      const targetClasses = ['variation-printlane-design-link', 'wc-block-components-product-details__printlane-design-link'];
      if (targetClasses.some(targetClass => node.classList.contains(targetClass))) {
        if (node.nodeName === "LI") {
          const children = node.children;
          if (children.length === 2) {
            // Remove first item as it contains the label
            const firstChild = children[0];
            firstChild.parentNode.removeChild(firstChild);
            node.text = children[0].textContent;
          }
          replaceElementValueWithChangeCustomizationLink(node);
        }
        if (node.nodeName === 'DD') replaceElementValueWithChangeCustomizationLink(node);
        if (node.nodeName === "DT") node.style.display = "none";
        bindCartUI();
      } else {
        for (const child of node.children) {
          whenPrintlaneLinkElementReplaceValue(child);
        }
      }
    }

    // Helper to replace an elements value with a change customization link
    const replaceElementValueWithChangeCustomizationLink = (el) => {
      const idAndToken = el.textContent.replace(/\s/g, "").split(':');
      el.innerHTML = `<a href="#" class="colorlab-edit-personalisation" data-colorlab-id="${idAndToken[0]}" data-colorlab-token="${idAndToken[1]}">${data['change_customization_text']}</a>`
    }

    // Observe dom and check if elements are added in which we need to replace the change customization link
    const observer = new MutationObserver((mutationsList, observer) => {
      for (const mutation of mutationsList) {
        for (const addedNode of mutation.addedNodes) {
          if (addedNode.nodeType === Node.ELEMENT_NODE) {
            whenPrintlaneLinkElementReplaceValue(addedNode); // Check the added node and its descendants
          }
        }
      }
    });

    // Start observing
    const targetNode = document.body; // Observe the entire document
    const config = { childList: true, subtree: true };
    observer.observe(targetNode, config);
  }

  // Helper to bind/unbind the UI to the Printlane Designer on product pages
  function bindProductPageUI() {
    const btn = $(data.button_selector);

    // Set configured text value (only if a value is set, else we keep the default text)
    if (data.add_to_cart_text) btn.text(data.add_to_cart_text);

    // Attach click event for opening the Printlane Designer
    btn.closest('form').on('submit', function (e) {
      e.preventDefault();
      onCreateDesign($(this));
    });
  }
  function unbindProductPageUI() {
    const btn = $(data.button_selector);
    btn.html(initialAddToCartText);
    btn.closest('form').off('submit');
  }

  // Helper to bind the UI to the Printlane Designer on the shopping cart page
  function bindCartUI() {
    // If thumbnails are active, update thumbnails
    if (data.cart_thumbnails === 'yes') {
      var els = document.querySelectorAll('.colorlab-edit-personalisation');
      for (var i = 0; i < els.length; i++) {
        updateThumbnail(els[i]);
      }
    }

    $('.colorlab-edit-personalisation').on('click', function (e) {
      e.preventDefault();

      const designId = $(this).data('colorlab-id');
      const designToken = $(this).data('colorlab-token');

      onEditDesign(designId, designToken);
    });
  }

  // Initialize when the document is ready
  function init() {
    if (typeof window.printlane === 'undefined') {
      console.error('Printlane library is not available');
      return false;
    }
    if (typeof window.woocommerce_printlane_data === 'undefined') {
      console.warn('Printlane data is not set');
      return false;
    }

    data = window.woocommerce_printlane_data;
    if (!data.shop || !data.language) {
      console.warn('Printlane shop or language are not set');
      return false;
    }

    // Bind product page UI
    var onProductPage = !!document.querySelectorAll(data.button_selector).length;
    if (onProductPage && data['enable_on_product_page']) {
      if (isPrintlaneActiveForCurrentVariant()) bindProductPageUI();

      // Store initial add to cart text
      initialAddToCartText = $(data.button_selector).html();

      // Bind or unbind personalization UI dynamically based on active variant
      $( ".single_variation_wrap" ).on( "show_variation", function (event, variation) {
        if (isPrintlaneActiveForCurrentVariant()) {
          bindProductPageUI();
        } else {
          unbindProductPageUI();
        }
      });
    }

    // Replace printlane links in cart and minicart
    if (data['has_block_layout']) replacePrintlaneLinksOnCartAndMinicart();

    // Bind default cart
    var onCartPage = !!$('.colorlab-edit-personalisation').length;
    if (onCartPage) bindCartUI();
  }
  function onReady(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }
  onReady(init);

  // Initialize when cart totals are updated on updated cart totals
  $(document.body).on('updated_cart_totals', function(){
    init();
  });
})(jQuery);
