<!-- File: templates/admin/paywall-settings.php -->
<div class="wrap">
    <h1>Paywall Settings</h1>
    <?php if ( $settings_saved ): ?>
        <div class="updated"><p>Settings saved.</p></div>
    <?php endif; ?>
    <form method="post">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="ecash_address">eCash Address</label></th>
                <td><input type="text" name="ecash_address" id="ecash_address" class="regular-text" value="<?php echo esc_attr( $ecash_address ); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="default_price">Default Price</label></th>
                <td><input type="number" step="1" name="default_price" id="default_price" class="regular-text" value="<?php echo esc_attr( $default_price ); ?>">
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
                <th scope="row">Hide Comments Until Unlocked</th>
                <td>
                    <label>
                        <input type="checkbox" name="paybutton_hide_comments_until_unlocked" value="1" <?php checked( $hide_comments_checked, true ); ?>>
                        <span>Hide the comment box on paywalled posts until the content is unlocked.</span>
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
            <!--Blocklist Field -->
            <tr>
                <th scope="row"><label for="paybutton_blocklist">Blacklisted eCash Addresses (optional)</label></th>
                <td>
                    <textarea name="paybutton_blocklist" id="paybutton_blocklist" rows="4" cols="50"><?php
                        // Convert the blocklist array into a comma-separated string for display
                        echo esc_textarea( isset($blocklist) ? implode(', ', (array) $blocklist ) : '' );
                    ?></textarea>
                    <p class="description">Enter comma-separated eCash addresses to block from logging in via Cashtab.</p>
                </td>
            </tr>
            <!--NEW Public Key input field-->
            <tr>
                <th scope="row">
                    <label for="paybutton_public_key">PayButton Public Key (optional)</label>
                </th>
                <td>
                    <input type="text" name="paybutton_public_key" id="paybutton_public_key" class="regular-text" value="<?php echo esc_attr( get_option('paybutton_public_key', '') ); ?>">
                    <p class="description">
                        Enter your PayButton public key to verify Payment Trigger requests.
                    </p>
                    <!-- User-Friendly Setup Guide -->
                    <div class="paybutton-guide" style="margin-top: 15px; background: #f7f7f7; padding: 15px; border-left: 4px solid #0073aa;">
                        <p><strong>Guide to Setup your PayButton Public Key:</strong></p>
                        <p>
                            1. Create an account on 
                            <a href="https://paybutton.org/signup" target="_blank" rel="noopener noreferrer">PayButton.org</a> 
                            and copy your public key from the <a href="https://paybutton.org/account" target="_blank" rel="noopener noreferrer">account page</a> and past it in the Public Key field above.
                        </p>
                        <p>
                            2. <a href="https://paybutton.org/buttons" target="_blank" rel="noopener noreferrer">Create a button</a> 
                            for your paywall receiving eCash address.
                        </p>
                        <p>
                            3. Scroll down on the button page to the section <em>"When a Payment is Received..."</em>.
                        </p>
                        <p>
                            4. In the URL field, paste the following:
                        </p>
                        <pre style="background: #eaeaea; padding: 10px; border: 1px solid #ddd;"><?php echo esc_url( home_url( '/wp-admin/admin-ajax.php?action=payment_trigger' ) ); ?></pre>
                        <p>
                            5. In the <em>Post Data</em> field, paste the following code as is:
                        </p>
                        <pre style="background: #eaeaea; padding: 10px; border: 1px solid #ddd;">
{
"signature": &lt;signature&gt;,
"post_id": &lt;opReturn&gt;,
"tx_hash": &lt;txId&gt;,
"tx_amount": &lt;amount&gt;,
"tx_timestamp": &lt;timestamp&gt;,
"user_address": &lt;inputAddresses&gt;
}</pre>
                            <p>
                                6. Save your button settings after pasting these values, and you're all set!
                            </p>
                            <p>
                                <strong>Note:</strong> Enabling this feature is strongly recommended as it improves payment reliability, leveraging secure server-to-server messaging to record paywall transactions to your database.
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
