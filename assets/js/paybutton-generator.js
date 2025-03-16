/* File: assets/js/paybutton-generator.js
*/
(function($) {
  "use strict";

  /* ==========================================================================
     BUTTON GENERATOR LOGIC
     ========================================================================== */
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

      const toVal         = $('#pbGenTo').val().trim() || defaults.to;
      const amountVal     = $('#pbGenAmount').val().trim();
      const currencyVal   = $('#pbGenCurrency').val().trim() || defaults.currency;
      const textVal       = $('#pbGenText').val().trim() || defaults.text;
      const hoverVal      = $('#pbGenHover').val().trim();
      const successVal    = $('#pbGenSuccessText').val().trim() || defaults.successText;
      const animationVal  = $('#pbGenAnimation').val().trim() || defaults.animation;
      const goalVal       = $('#pbGenGoal').val().trim();
      const primaryVal    = $('#pbGenPrimary').val().trim() || defaults.primary;
      const secondaryVal  = $('#pbGenSecondary').val().trim() || defaults.secondary;
      const tertiaryVal   = $('#pbGenTertiary').val().trim() || defaults.tertiary;
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
     ========================================================================== */
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