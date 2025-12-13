<!-- File: templates/admin/paywall-settings.php -->
<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="wrap">
    <div class="pb-header">
        <img class="paybutton-logo" src="<?php echo esc_url( PAYBUTTON_PLUGIN_URL . 'assets/paybutton-logo.png' ); ?>" alt="PayButton Logo">
    </div>
    <h1>Paywall Settings</h1>
    <?php if ( $settings_saved ): ?>
        <div class="updated"><p>Settings saved.</p></div>
    <?php endif; ?>
    <form method="post">
        <?php wp_nonce_field( 'paybutton_paywall_settings', 'paybutton_settings_nonce' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="paybutton_admin_wallet_address">Wallet Address (required)</label></th>
                <td>
                    <!-- Using the new $paybutton_admin_wallet_address variable -->
                    <input type="text" name="paybutton_admin_wallet_address" id="paybutton_admin_wallet_address" class="regular-text" value="<?php echo esc_attr( $paybutton_admin_wallet_address ); ?>" required>
                    <!-- This span will be populated by our bundled address validator JS -->
                    <span id="adminAddressValidationResult"></span>
                    <p class="description">Enter your wallet address to receive paywall payments.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="default_price">Default Price</label></th>
                <td><input type="number" step="any" name="default_price" id="default_price" class="regular-text" value="<?php echo esc_attr( $default_price ); ?>">
                <p class="description">Minimum 5.5 if using XEC.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="unit">Unit</label></th>
                <td>
                    <select name="unit" id="unit">
                        <option value="XEC" <?php selected( $current_unit, 'XEC' ); ?>>XEC</option>
                        <option value="USD" <?php selected( $current_unit, 'USD' ); ?>>USD</option>
                        <option value="CAD" <?php selected( $current_unit, 'CAD' ); ?>>CAD</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="paybutton_text">PayButton Text</label></th>
                <td><input type="text" name="paybutton_text" id="paybutton_text" class="regular-text" value="<?php echo esc_attr( $btn_text ); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="paybutton_hover_text">PayButton Hover Text</label></th>
                <td><input type="text" name="paybutton_hover_text" id="paybutton_hover_text" class="regular-text" value="<?php echo esc_attr( $hvr_text ); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="paybutton_color_primary">Primary Color</label></th>
                <td><input type="color" name="paybutton_color_primary" id="paybutton_color_primary" value="<?php echo esc_attr( $clr_primary ); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="paybutton_color_secondary">Secondary Color</label></th>
                <td><input type="color" name="paybutton_color_secondary" id="paybutton_color_secondary" value="<?php echo esc_attr( $clr_secondary ); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="paybutton_color_tertiary">Tertiary Color</label></th>
                <td><input type="color" name="paybutton_color_tertiary" id="paybutton_color_tertiary" value="<?php echo esc_attr( $clr_tertiary ); ?>"></td>
            </tr>
            <tr>
                <th scope="row">Paywall Comments</th>
                <td>
                    <label>
                        <input type="checkbox" name="paybutton_hide_comments_until_unlocked" value="1" <?php checked( $hide_comments_checked, true ); ?>>
                        <span>Don't allow comments until the paywalled content is unlocked by the user.</span>
                    </label>
                </td>
            </tr>
            <!--NEW Unlocked Content Indicator Checkbox-->
            <tr>
                <th scope="row">Unlocked Content Indicator</th>
                <td>
                    <label>
                        <input type="checkbox" name="paybutton_scroll_to_unlocked" value="1" <?php checked( get_option('paybutton_scroll_to_unlocked', '1'), '1' ); ?>>
                        <span>After payment, show the unlocked content indicator and scroll to it</span>
                    </label>
                </td>
            </tr>
            <tbody id="unlockedIndicatorColors">
                <tr>
                    <th scope="row">
                        <label for="paybutton_unlocked_indicator_color">Indicator Color</label>
                    </th>
                    <td>
                        <input type="color" name="paybutton_unlocked_indicator_color" id="paybutton_unlocked_indicator_color"
                            value="<?php echo esc_attr( get_option('paybutton_unlocked_indicator_color', '#000000') ); ?>">
                        <button type="button"
                            onclick="document.getElementById('paybutton_unlocked_indicator_color').value = '#000000';">
                            Reset
                        </button>
                        <p class="description">Controls the text and line colors of the unlocked content indicator.</p>
                    </td>
                </tr>
            </tbody>
            <!-- Show Unlock Count on Front‐end -->
            <tr>
                <th scope="row">Show Unlock Count on Front‐end</th>
                <td>
                    <label>
                        <input 
                            type="checkbox" 
                            name="paybutton_enable_frontend_unlock_count" 
                            id="paybutton_enable_frontend_unlock_count"
                            value="1"
                            <?php checked( get_option( 'paybutton_enable_frontend_unlock_count', '0' ), '1' ); ?>
                        >
                        <span>Enable unlock‐count label above PayButton on public posts</span>
                    </label>
                </td>
            </tr>

            <!-- Unlock Count Color Picker (hidden until above box is checked) -->
            <tr id="paybutton_frontend_unlock_color_row">
                <th scope="row">
                    <label for="paybutton_frontend_unlock_color">Unlock Count Label Color</label>
                </th>
                <td>
                    <input 
                    type="color" 
                    name="paybutton_frontend_unlock_color" 
                    id="paybutton_frontend_unlock_color"
                    value="<?php echo esc_attr( get_option( 'paybutton_frontend_unlock_color', '#0074C2' ) ); ?>"
                    >
                    <button type="button"
                    onclick="document.getElementById('paybutton_frontend_unlock_color').value = '#0074C2';">
                    Reset
                    </button>
                    <p class="description">
                    Pick the hex color for the unlock count label.
                    </p>
                </td>
            </tr>
            <!-- Sticky Header Settings -->
            <tr>
                <th colspan="2"><h2>Sticky Header Settings</h2></th>
            </tr>
            <tr>
                <th scope="row"><label for="sticky_header_bg_color">Sticky Header Background Color</label></th>
                <td>
                    <input type="color" name="sticky_header_bg_color" id="sticky_header_bg_color" value="<?php echo esc_attr( $sticky_header_bg_color ); ?>">
                    <button type="button" onclick="document.getElementById('sticky_header_bg_color').value = '#007bff';">Reset</button>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="sticky_header_text_color">Sticky Header Text Color</label></th>
                <td>
                    <input type="color" name="sticky_header_text_color" id="sticky_header_text_color" value="<?php echo esc_attr( $sticky_header_text_color ); ?>">
                    <button type="button" onclick="document.getElementById('sticky_header_text_color').value = '#fff';">Reset</button>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="profile_button_bg_color">Profile Button Background Color</label></th>
                <td>
                    <input type="color" name="profile_button_bg_color" id="profile_button_bg_color" value="<?php echo esc_attr( $profile_button_bg_color ); ?>">
                    <button type="button" onclick="document.getElementById('profile_button_bg_color').value = '#ffc107';">Reset</button>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="profile_button_text_color">Profile Button Text Color</label></th>
                <td>
                    <input type="color" name="profile_button_text_color" id="profile_button_text_color" value="<?php echo esc_attr( $profile_button_text_color ); ?>">
                    <button type="button" onclick="document.getElementById('profile_button_text_color').value = '#000';">Reset</button>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="logout_button_bg_color">Logout Button Background Color</label></th>
                <td>
                    <input type="color" name="logout_button_bg_color" id="logout_button_bg_color" value="<?php echo esc_attr( $logout_button_bg_color ); ?>">
                    <button type="button" onclick="document.getElementById('logout_button_bg_color').value = '#d9534f';">Reset</button>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="logout_button_text_color">Logout Button Text Color</label></th>
                <td>
                    <input type="color" name="logout_button_text_color" id="logout_button_text_color" value="<?php echo esc_attr( $logout_button_text_color ); ?>">
                    <button type="button" onclick="document.getElementById('logout_button_text_color').value = '#fff';">Reset</button>
                </td>
            </tr>
            <!--NEW Advanced Settings tab-->
            <tr>
                <th colspan="2"><h2>Advanced Settings</h2></th>
            </tr>
            <!-- Login & Content Unlock Cookie Expiry Setting -->
            <tr>
                <th scope="row">
                    <label for="paybutton_cookie_ttl_days">
                        Login &amp; Content Unlock Cookie Expiry (optional)
                    </label>
                </th>
                <td>
                    <input
                        type="number"
                        name="paybutton_cookie_ttl_days"
                        id="paybutton_cookie_ttl_days"
                        class="regular-text"
                        min="0"
                        step="1"
                        value="<?php echo esc_attr( (int) $paybutton_cookie_ttl_days ); ?>"
                    />
                    <p class="description">
                        Controls how long login <code>paybutton_user_wallet_address</code> and unlocked content <code>paybutton_paid_content</code> cookies stay valid, in days.
                        <br />Use <strong>0</strong> (default) to keep users logged in indefinitely.
                    </p>
                </td>
            </tr>
            <!--blacklist Field -->
            <tr>
                <th scope="row"><label for="paybutton_blacklist">Blacklisted Addresses (optional)</label></th>
                <td>
                    <textarea name="paybutton_blacklist" id="paybutton_blacklist" rows="4" cols="50"><?php
                        // Convert the blacklist array into a comma-separated string for display
                        echo esc_textarea( isset($blacklist) ? implode(', ', (array) $blacklist ) : '' );
                    ?></textarea>
                    <p class="description">Enter comma-separated wallet addresses to block from logging in via Cashtab.</p>
                </td>
            </tr>
            <!--NEW Public Key input field-->
            <tr>
                <th scope="row">
                    <label for="paybutton_public_key">PayButton Public Key (required)</label>
                </th>
                <td>
                    <input type="text" name="paybutton_public_key" id="paybutton_public_key" class="regular-text" value="<?php echo esc_attr( $paybutton_public_key ); ?>" required>
                    <p class="description">
                        Enter your PayButton public key to verify Payment Trigger requests.
                    </p>
                    <!-- User-Friendly Setup Guide -->
                    <div class="paybutton-guide">
                        <p><strong>Guide to Setup your PayButton Public Key:</strong></p>
                        <p>
                            1. Create an account on 
                            <a href="https://paybutton.org/signup" target="_blank" rel="noopener noreferrer">PayButton.org</a> 
                            and copy your public key from the <a href="https://paybutton.org/account" target="_blank" rel="noopener noreferrer">account page</a> and paste it in the Public Key field above.
                        </p>
                        <p>
                            2. <a href="https://paybutton.org/buttons" target="_blank" rel="noopener noreferrer">Create a button</a> 
                            for your paywall receiving wallet address.
                        </p>
                        <p>
                            3. Scroll down on the buttons page to the section <em>"When a Payment is Received..."</em>.
                        </p>
                        <p>
                            4. In the <em>URL</em> field, paste the following:
                        </p>
                        <pre class="pre-box"><?php echo esc_url( admin_url( 'admin-ajax.php?action=payment_trigger' ) ); ?></pre>
                        <p>
                            5. In the <em>Post Data</em> field, paste the following code as is:
                        </p>
                        <pre class="pre-box">
{
"signature": &lt;signature&gt;,
"post_id": &lt;opReturn&gt;,
"tx_hash": &lt;txId&gt;,
"tx_amount": &lt;amount&gt;,
"tx_timestamp": &lt;timestamp&gt;,
"user_address": &lt;inputAddresses&gt;,
"currency": &lt;currency&gt;
}</pre>
                            <p>
                                6. Save your button settings after pasting these values, and you're all set!
                            </p>
                            <p>
                                <strong>Note:</strong> Enabling this feature is required as it improves payment reliability, leveraging secure server-to-server messaging to record paywall and login transactions to your database.
                            </p>
                    </div>
                </td>
            </tr>
        </table>
        <p class="submit">
            <button type="submit" name="paybutton_paywall_save_settings" class="button button-primary">Save Changes</button>
        </p>
    </form>
    <hr>
    <h2>Shortcode Usage</h2>
    <h3>Simple:</h3>
    <p>Just wrap your content using the shortcode below and your content will get paywalled with the default settings:</p>
    <p><code>[paywalled_content]Hidden content[/paywalled_content]</code></p>
    <h3>Advanced:</h3>
    <p>If you want to paywall your content with custom options, use the following shortcode:</p>
    <p><code>[paywalled_content price="10" address="ecash:qrEXAMPLE" unit="XEC" button_text="Pay to Unlock" hover_text="Send Payment"]Hidden content[/paywalled_content]</code></p>
    <p>You can customize any shortcode attributes, and any unspecified ones will use their default values.</p>
    <p><code>[paywalled_content button_text="Pay to Unlock Comments"]Comments unlocked[/paywalled_content]</code></p>
</div>