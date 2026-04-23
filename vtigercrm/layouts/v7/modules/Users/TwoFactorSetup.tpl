<div class="main-container">
<div class="col-sm-8 col-sm-offset-2" style="margin-top:40px;">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-lock"></i> Two-Factor Authentication (2FA)</h3>
        </div>
        <div class="panel-body">

        {if $TOTP_ENABLED}
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i> <strong>2FA is enabled</strong> on your account.
            </div>
            <p>Your account is protected with an authenticator app. To disable 2FA, click below.</p>
            <button id="btn-disable-2fa" class="btn btn-danger"><i class="fa fa-unlock"></i> Disable 2FA</button>

        {else}
            <p>Protect your account with a one-time code from an authenticator app
               (Google Authenticator, Authy, Microsoft Authenticator, etc.).</p>
            <hr>

            <div id="setup-step1">
                <button id="btn-generate" class="btn btn-primary"><i class="fa fa-qrcode"></i> Set Up 2FA</button>
            </div>

            <div id="setup-step2" style="display:none;">
                <h4>Step 1 &mdash; Scan this QR code</h4>
                <p>Open your authenticator app and scan the QR code below.</p>
                <div id="qrcode" style="margin:16px 0;"></div>
                <p><small>Can't scan? Enter this key manually: <strong id="secret-text"></strong></small></p>
                <hr>
                <h4>Step 2 &mdash; Enter the 6-digit code to verify</h4>
                <div class="input-group" style="max-width:240px;">
                    <input type="text" id="totp-verify-code" class="form-control input-lg"
                           maxlength="6" placeholder="000000" inputmode="numeric">
                    <span class="input-group-btn">
                        <button id="btn-enable" class="btn btn-success btn-lg">Verify &amp; Enable</button>
                    </span>
                </div>
                <div id="enable-error" class="text-danger" style="margin-top:8px;display:none;"></div>
            </div>
        {/if}

        </div>
    </div>
    <a href="index.php?module=Users&parent=Settings&view=Detail&record={$RECORD_ID}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to My Preferences</a>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
{literal}
<script>
jQuery(document).ready(function() {

    // Generate secret & show QR code
    jQuery('#btn-generate').on('click', function() {
        jQuery.post('index.php', {module:'Users', action:'TwoFactorSetup', mode:'generate'}, function(res) {
            if (res && res.result) {
                jQuery('#secret-text').text(res.result.secret);
                jQuery('#qrcode').empty();
                new QRCode(document.getElementById('qrcode'), {
                    text: res.result.otpauth_url,
                    width: 200, height: 200
                });
                jQuery('#setup-step1').hide();
                jQuery('#setup-step2').show();
            }
        }, 'json');
    });

    // Verify code and enable
    jQuery('#btn-enable').on('click', function() {
        var code = jQuery('#totp-verify-code').val().trim();
        jQuery.post('index.php', {module:'Users', action:'TwoFactorSetup', mode:'enable', code: code}, function(res) {
            if (res && res.result && res.result.success) {
                location.reload();
            } else {
                jQuery('#enable-error').text(res.error && res.error.message ? res.error.message : 'Invalid code. Try again.').show();
            }
        }, 'json');
    });

    // Disable 2FA
    jQuery('#btn-disable-2fa').on('click', function() {
        if (!confirm('Are you sure you want to disable Two-Factor Authentication?')) return;
        jQuery.post('index.php', {module:'Users', action:'TwoFactorSetup', mode:'disable'}, function(res) {
            if (res && res.result && res.result.success) location.reload();
        }, 'json');
    });

    // Allow Enter key on code input
    jQuery('#totp-verify-code').on('keypress', function(e) {
        if (e.which === 13) jQuery('#btn-enable').trigger('click');
    });
});
</script>
{/literal}
