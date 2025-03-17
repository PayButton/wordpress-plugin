/* File: assets/js/paybutton-generator.js
*/
(function($) {
  "use strict";

  /* ==========================================================================
      ADMIN WALLET ADDRESS VALIDATION
     ========================================================================== 
  */
  if ($('#pbGenTo').length) {

    const $toField = $('#pbGenTo');
    let $validationMsg;

    if (!$('#pbGenToValidationResult').length) {
      $toField.after('<p id="pbGenToValidationResult"></p>');
    }
    $validationMsg = $('#pbGenToValidationResult');

    $toField.on('input', function() {
      const address = $toField.val().trim();

      if (!address) {
        $validationMsg.text('').css('color', '');
        return;
      }

      const valid = window.cashaddrExports && window.cashaddrExports.isValidCashAddress(address);
      if (typeof window.cashaddrExports === 'undefined') {
        console.error('[PayButton] addressValidator is missing or not loaded!');
      }

      if (valid) {
        $validationMsg.text('✅ Valid address').css('color', 'green');
      } else {
        $validationMsg.text('❌ Invalid address').css('color', 'red');
      }
    });

    // Trigger input event on page load to validate pre-set value (from Paywall Settings).
    $toField.trigger('input');
  }

  /* ==========================================================================
     BUTTON GENERATOR LOGIC
     ========================================================================== 
  */
  if ($('#pbGenTo').length) {
    function updateGenerator() {
      // Define default values for required options
      const defaults = {
        to:"",
        text: "🧋 Buy me a coffee",
        currency: "XEC",
        animation: "slide",
        successText: "Thank You",
        primary: "#0074C2",
        secondary: "#ffffff",
        tertiary: "#231F20"
      };

      const toVal = ($('#pbGenTo').val() || "").trim() || defaults.to;
      
      // If the TO field is empty, reset the currency dropdown to show all options.
      if (!toVal) {
        $('#pbGenCurrency').html('<option value="XEC">XEC</option><option value="BCH">BCH</option><option value="USD">USD</option><option value="CAD">CAD</option>');
      }
      
      // If the wallet address is invalid, clear preview and shortcode and exit.
      if (!toVal || !window.cashaddrExports.isValidCashAddress(toVal)) {
        const previewContainer = document.getElementById('pbGenPreview');
        if (previewContainer) {
          previewContainer.innerHTML = 'Please enter a valid wallet address to preview';
        }
        $('#pbGenShortcode').val('');
        $('.pb-generator-preview').find('h3:contains("SHORTCODE"), p.shortcode-note, #pbGenShortcode').hide();
        $('.shortcode-container .copy-btn').hide();
        return;
      } else {
        $('.pb-generator-preview').find('h3:contains("SHORTCODE"), p.shortcode-note, #pbGenShortcode').show();
        $('.shortcode-container .copy-btn').show();
      }
      
      // Use the validator lib to determine the address type and adjust primary color and currency accordingly.
      try {
        const decoded = window.cashaddrExports.decodeCashAddress(toVal);
        const $currency = $('#pbGenCurrency');
        const currentCurrency = $currency.val();
        const prefix = decoded.prefix ? decoded.prefix.toLowerCase() : "";
        if (prefix === 'bitcoincash') {
          $('#pbGenPrimary').val("#4BC846");
          // Remove XEC option from crypto choices.
          $currency.find('option[value="XEC"]').remove();
          if ($currency.find('option[value="BCH"]').length === 0) {
            $currency.append('<option value="BCH">BCH</option>');
          }

        } else if (prefix === 'ecash') {
          $('#pbGenPrimary').val("#0074C2");
          // Remove BCH option from crypto choices.
          $currency.find('option[value="BCH"]').remove();
          if ($currency.find('option[value="XEC"]').length === 0) {
            $currency.append('<option value="XEC">XEC</option>');
          }
          if (!currentCurrency || currentCurrency === "BCH") {
            $currency.val("XEC");
          }
        } else {
          $('#pbGenPrimary').val(defaults.primary);
          $currency.val(defaults.currency);
        }
      } catch (e) {
        // In case decoding fails, revert to default values.
        $('#pbGenPrimary').val(defaults.primary);
        $('#pbGenCurrency').val(defaults.currency);
      }

      const amountVal     = ($('#pbGenAmount').val() || "").trim();
      const currencyVal   = ($('#pbGenCurrency').val() || "").trim() || defaults.currency;
      const textVal       = ($('#pbGenText').val() || "").trim() || defaults.text;
      const hoverVal      = ($('#pbGenHover').val() || "").trim();
      const successVal    = ($('#pbGenSuccessText').val() || "").trim() || defaults.successText;
      const animationVal  = ($('#pbGenAnimation').val() || "").trim() || defaults.animation;
      const goalVal       = ($('#pbGenGoal').val() || "").trim();
      const primaryVal    = ($('#pbGenPrimary').val() || "").trim() || defaults.primary;
      const secondaryVal  = ($('#pbGenSecondary').val() || "").trim() || defaults.secondary;
      const tertiaryVal   = ($('#pbGenTertiary').val() || "").trim() || defaults.tertiary;
      const widgetChecked = $('#pbGenWidget').is(':checked');

      // Build PB's config object
      let config = {};
      if(toVal !== '') { config.to = toVal; }
      if(amountVal !== '') { config.amount = parseFloat(amountVal); }
      config.currency = currencyVal;
      if(textVal !== '') { config.text = textVal; }
      if(hoverVal !== '') { config.hoverText = hoverVal; }
      config.successText = successVal;
      config.animation = animationVal;
      if(goalVal !== '') { config.goalAmount = parseFloat(goalVal); }
      config.theme = {
        palette: {
          primary: primaryVal,
          secondary: secondaryVal,
          tertiary: tertiaryVal
        }
      };
      config.widget = widgetChecked;
    
      // Get the preview container
      const previewContainer = document.getElementById('pbGenPreview');
      if (previewContainer) {
        // Remove all child nodes
        while (previewContainer.firstChild) {
          previewContainer.removeChild(previewContainer.firstChild);
        }
        // Create a new inner container for rendering the button
        const innerContainer = document.createElement('div');
        previewContainer.appendChild(innerContainer);

        // Render the button using the local paybutton.js
        if (typeof PayButton !== 'undefined') {
          if (widgetChecked) {
            PayButton.renderWidget(innerContainer, config);
          } else {
            PayButton.render(innerContainer, config);
          }
        } else {
          previewContainer.innerHTML = '<p>Error: paybutton.js not loaded.</p>';
        }
      }
    
      // Generate shortcode with config attribute
      const shortcode = `[paybutton config='${JSON.stringify(config)}'][/paybutton]`;
      $('#pbGenShortcode').val(shortcode);
    }
  
    // Bind updates on input and change events
    $('#pbGenTo, #pbGenAmount, #pbGenCurrency, #pbGenText, #pbGenAnimation, #pbGenGoal, #pbGenHover, #pbGenPrimary, #pbGenSecondary, #pbGenTertiary, #pbGenSuccessText, #pbGenWidget')
      .on('input change', updateGenerator);
  
    // Initialize the generator on page load
    updateGenerator();
  }

  /* ==========================================================================
     SHORTCODE RENDERING LOGIC ON THE FRONT-END
     ========================================================================== 
  */
  function renderPayButtonShortcodes() {
    const containers = document.querySelectorAll('.paybutton-shortcode-container');
    containers.forEach(container => {
      const configStr = container.getAttribute('data-config');
      try {
        const config = JSON.parse(configStr);
        if (typeof PayButton !== 'undefined') {
          if (config.widget) {
            PayButton.renderWidget(container, config);
          } else {
            PayButton.render(container, config);
          }
        } else {
          container.innerHTML = '<p>Error: paybutton.js not loaded.</p>';
        }
      } catch (err) {
        console.error('Invalid PayButton config JSON:', err);
      }
    });
  }
  
  // Run shortcode rendering on DOMContentLoaded
  document.addEventListener('DOMContentLoaded', renderPayButtonShortcodes);

})(jQuery);

/* ==========================================================================
     CLIPBOARD FUNCTIONALITY
   ========================================================================== 
*/
document.addEventListener('DOMContentLoaded', function() {
  const copyOverlay = document.querySelector('.copy-overlay');
  if (copyOverlay) {
    copyOverlay.addEventListener('click', function() {
      const targetSelector = copyOverlay.getAttribute('data-target');
      const targetElement = document.getElementById(targetSelector.replace('#', ''));
      if (targetElement && navigator.clipboard) {
        navigator.clipboard.writeText(targetElement.value)
          .then(function() {
            const overlayText = copyOverlay.querySelector('.overlay-text');
            if (overlayText) {
              overlayText.textContent = 'Copied to clipboard!';
            }
            copyOverlay.classList.add('copied');
            setTimeout(function() {
              if (overlayText) {
                overlayText.textContent = 'Click to copy!';
              }
              copyOverlay.classList.remove('copied');
            }, 2000);
          })
          .catch(function(err) {
            console.error('Failed to copy text: ', err);
          });
      }
    });
  }
});