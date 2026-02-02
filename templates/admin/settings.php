<!-- File: templates/admin/settings.php -->
<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="wrap">
    <div class="pb-header">
        <img class="paybutton-logo" src="<?php echo esc_url( PAYBUTTON_PLUGIN_URL . 'assets/paybutton-logo.png' ); ?>" alt="PayButton Logo">
    </div>
    <h1>PayButton Settings</h1>
    <?php if ( ! empty( $settings_saved ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p>Settings saved successfully.</p>
        </div>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field( 'paybutton_settings_save', 'paybutton_settings_nonce' ); ?>

        <table class="form-table">

            <!--NEW Public Key input field-->
            <tr>
                <th scope="row">
                    <label for="paybutton_public_key">PayButton Public Key (required)</label>
                </th>
                <td>
                    <input
                        type="text"
                        name="paybutton_public_key"
                        id="paybutton_public_key"
                        class="regular-text"
                        value="<?php echo esc_attr( $paybutton_public_key ); ?>"
                        required
                    >
                    <p class="description">
                        Enter your PayButton public key to verify Payment Trigger requests.
                    </p>

                    <!-- User-Friendly Setup Guide -->
                    <div class="paybutton-guide">
                        <p><strong>Guide to Setup your PayButton Public Key:</strong></p>
                        <p>
                            1. Create an account on
                            <a href="https://paybutton.org/signup" target="_blank" rel="noopener noreferrer">
                                PayButton.org
                            </a>
                            and copy your public key from the
                            <a href="https://paybutton.org/account" target="_blank" rel="noopener noreferrer">
                                account page
                            </a>
                            and paste it in the Public Key field above.
                        </p>
                        <p>
                            2.
                            <a href="https://paybutton.org/buttons" target="_blank" rel="noopener noreferrer">
                                Create a button
                            </a>
                            for your paywall receiving wallet address.
                        </p>
                        <p>
                            3. Scroll down on the buttons page to the section
                            <em>"When a Payment is Received..."</em>.
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
"value": &lt;value&gt;,
"currency": &lt;currency&gt;
}</pre>
                        <p>
                            6. Save your button settings after pasting these values, and you're all set!
                        </p>
                        <p>
                            <strong>Note:</strong> Enabling this feature is required as it improves payment reliability,
                            leveraging secure server-to-server messaging to record paywall and login transactions to your database.
                        </p>
                    </div>
                </td>
            </tr>

        </table>

        <p class="submit">
            <button
                type="submit"
                name="paybutton_settings_save"
                class="button button-primary">
                Save Settings
            </button>
        </p>
    </form>
</div>