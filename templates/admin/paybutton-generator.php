<!-- File: templates/admin/paybutton-generator.php -->
<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    //Get admin's wallet address from paywall settings
    $admin_address = get_option( 'pb_paywall_admin_wallet_address', '' );
?>

<div class="wrap">
  <h1>Button Generator</h1>
  <h2>Build your custom PayButton and begin accepting payments!</h2>

  <div class="pb-generator-container">
    <div class="pb-generator-form">
      <label for="pbGenTo">TO
      <span class="pbTooltip" data-tooltip="Where the money will be sent to">?</span>
      </label>
      <input 
        type="text" 
        id="pbGenTo" 
        placeholder="Your Wallet Address (XEC or BCH)"
        value="<?php echo esc_attr( $admin_address ); ?>"
        class="pb-generator-input"
      >

      <label for="pbGenAmount">AMOUNT
      <span class="pbTooltip" data-tooltip="How much money to request">?</span>
      </label>
      <input 
        type="number" 
        step="any" 
        id="pbGenAmount" 
        value="" 
        class="pb-generator-input"
        min="0"
      >

      <label for="pbGenCurrency">CURRENCY</label>
      <select id="pbGenCurrency" class="pb-generator-input">
        <option value="XEC" selected>XEC</option>
        <option value="BCH">BCH</option>
        <option value="USD">USD</option>
        <option value="CAD">CAD</option>
      </select>

      <label for="pbGenText">TEXT
      <span class="pbTooltip" data-tooltip="The text displayed on the button">?</span>
      </label>
      <input 
        type="text" 
        id="pbGenText" 
        placeholder="🧋 Buy me a coffee"
        value=""
        class="pb-generator-input"
      >

      <label for="pbGenHover">HOVER TEXT
      <span class="pbTooltip" data-tooltip="The text displayed on the button on hover">?</span>
      </label>
      <input 
        type="text" 
        id="pbGenHover" 
        placeholder="Send payment"
        class="pb-generator-input"
      >

      <label for="pbGenSuccessText">SUCCESS TEXT
      <span class="pbTooltip" data-tooltip="The text displayed upon successful payment">?</span>
      </label>
      <input 
        type="text" 
        id="pbGenSuccessText" 
        placeholder="Thanks for your support" 
        class="pb-generator-input"
      >

      <label for="pbGenAnimation">ANIMATION
      <span class="pbTooltip" data-tooltip="The button hover animation">?</span>  
      </label>
      <select id="pbGenAnimation" class="pb-generator-input">
        <option value="slide" selected>Slide</option>
        <option value="invert">Invert</option>
        <option value="none">None</option>
      </select>

      <label for="pbGenGoal">GOAL AMOUNT
      <span class="pbTooltip" data-tooltip="Specifies a funding goal amount, indicated with a progress bar">?</span>
      </label>
      <input 
        type="number" 
        step="any" 
        id="pbGenGoal" 
        placeholder="Goal Amount" 
        class="pb-generator-input"
        min="0"
      >

      <label>Colors
      <span class="pbTooltip" data-tooltip="The primary, secondary, and tertiary are color options that allow for custom themeing">?</span>  
      </label>
      <div class="pb-generator-colors">
        <div class="pb-generator-color">
          <label for="pbGenPrimary">PRIMARY</label>
          <input type="color" id="pbGenPrimary" value="#0074C2">
        </div>
        <div class="pb-generator-color">
          <label for="pbGenSecondary">SECONDARY</label>
          <input type="color" id="pbGenSecondary" value="#ffffff">
        </div>
        <div class="pb-generator-color">
          <label for="pbGenTertiary">TERTIARY</label>
          <input type="color" id="pbGenTertiary" value="#231F20">
        </div>
      </div>

      <div class="pb-generator-widget">
        <label for="pbGenWidget">Widget
        <span class="pbTooltip" data-tooltip="Creates an always-visible PayButton Widget">?</span>  
        </label>
        <input type="checkbox" id="pbGenWidget">
      </div>

    </div>

    <div class="pb-generator-preview">
      <h3>PREVIEW</h3>
      <div id="pbGenPreview">
        Enter an address
      </div>
      <h3>SHORTCODE</h3>
      <p class="shortcode-note">
      Click the shortcode below to copy it, then paste it anywhere you'd like the PayButton to appear on your site.
      </p>
      <div class="shortcode-container">
        <textarea id="pbGenShortcode" rows="5" readonly></textarea>
        <div class="copy-overlay" data-target="#pbGenShortcode">
          <span class="overlay-text">Click to copy!</span>
        </div>
      </div>
    </div>
  </div>
</div>