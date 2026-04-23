{strip}
<style>
    body {
        background: url(layouts/v7/resources/Images/login-background.jpg);
        background-position: center;
        background-size: cover;
        width: 100%;
        background-repeat: no-repeat;
    }
    .twofa-container {
        max-width: 420px;
        margin: 120px auto 0;
        background: #fff;
        border-radius: 6px;
        padding: 40px 36px 32px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.18);
    }
    .twofa-container .logo {
        text-align: center;
        margin-bottom: 28px;
    }
    .twofa-container .logo img {
        height: 48px;
    }
    .twofa-container h4 {
        text-align: center;
        margin-bottom: 6px;
        color: #333;
        font-size: 18px;
    }
    .twofa-container p.hint {
        text-align: center;
        color: #777;
        font-size: 13px;
        margin-bottom: 24px;
    }
    .twofa-container .code-input {
        text-align: center;
        font-size: 28px;
        letter-spacing: 8px;
        width: 100%;
        border: 2px solid #ddd;
        border-radius: 4px;
        padding: 10px 0;
        outline: none;
        transition: border-color 0.2s;
    }
    .twofa-container .code-input:focus {
        border-color: #e85b00;
    }
    .twofa-container .btn-verify {
        width: 100%;
        margin-top: 18px;
        background: #e85b00;
        color: #fff;
        border: none;
        padding: 11px;
        border-radius: 4px;
        font-size: 15px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .twofa-container .btn-verify:hover {
        background: #c44d00;
    }
    .twofa-container .back-link {
        display: block;
        text-align: center;
        margin-top: 16px;
        color: #888;
        font-size: 13px;
        text-decoration: none;
    }
    .twofa-container .back-link:hover { color: #e85b00; }
    .error-msg {
        background: #fdecea;
        color: #c0392b;
        border-radius: 4px;
        padding: 10px 14px;
        margin-bottom: 18px;
        font-size: 13px;
        text-align: center;
    }
</style>

<div class="twofa-container">
    <div class="logo">
        <img src="layouts/v7/resources/Images/vtiger-crm-logo.png" alt="vtiger CRM" onerror="this.style.display='none'">
    </div>
    <h4>Two-Factor Authentication</h4>
    <p class="hint">Enter the 6-digit code from your authenticator app.</p>

    {if $ERROR eq 'invalid'}
    <div class="error-msg">Invalid code. Please try again.</div>
    {/if}

    <form method="post" action="index.php" autocomplete="off">
        <input type="hidden" name="module" value="Users">
        <input type="hidden" name="action" value="Verify2FA">
        <input type="text" name="totp_code" class="code-input" maxlength="6"
               placeholder="000000" autofocus inputmode="numeric" pattern="[0-9]*">
        <button type="submit" class="btn-verify">Verify</button>
    </form>
    <a href="index.php?module=Users&parent=Settings&view=Login" class="back-link">← Back to login</a>
</div>
{/strip}
