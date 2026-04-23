{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{* modules/Vtiger/views/Detail.php *}

{* START YOUR IMPLEMENTATION FROM BELOW. Use {debug} for information *}
{strip}
	{assign var=NAME_FIELDS value=array('first_name', 'last_name')}
	{if $MODULE_MODEL}
		{assign var=NAME_FIELDS value=$MODULE_MODEL->getNameFields()}
	{/if}
    <form id="detailView" data-name-fields='{ZEND_JSON::encode($NAME_FIELDS)}' method="POST">
        {include file='DetailViewBlockView.tpl'|@vtemplate_path:$MODULE_NAME RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME}
    </form>

    <div style="margin-top:24px;">
        <h4>Two-Factor Authentication (2FA)</h4>
        <hr>
        <div id="twofa-status-block">
            <span class="text-muted"><i class="fa fa-spinner fa-spin"></i> Checking 2FA status...</span>
        </div>
    </div>

{literal}
<script>
jQuery(document).ready(function() {
    jQuery.post('index.php', {module:'Users', action:'TwoFactorSetup', mode:'status'}, function(res) {
        var enabled = res && res.result && res.result.enabled;
        var html;
        if (enabled) {
            html = '<div class="alert alert-success" style="max-width:520px;">' +
                   '<i class="fa fa-lock"></i> <strong>2FA is enabled</strong> on your account. ' +
                   '<a href="index.php?module=Users&view=TwoFactorSetup" class="btn btn-sm btn-default" style="margin-left:12px;">' +
                   '<i class="fa fa-cog"></i> Manage 2FA</a></div>';
        } else {
            html = '<div class="alert alert-warning" style="max-width:520px;">' +
                   '<i class="fa fa-unlock"></i> <strong>2FA is not enabled.</strong> ' +
                   'Protect your account with a one-time code from an authenticator app. ' +
                   '<a href="index.php?module=Users&view=TwoFactorSetup" class="btn btn-sm btn-primary" style="margin-left:12px;">' +
                   '<i class="fa fa-qrcode"></i> Set Up 2FA</a></div>';
        }
        jQuery('#twofa-status-block').html(html);
    }, 'json').fail(function() {
        jQuery('#twofa-status-block').html(
            '<a href="index.php?module=Users&view=TwoFactorSetup" class="btn btn-default">' +
            '<i class="fa fa-lock"></i> Manage Two-Factor Authentication</a>'
        );
    });
});
</script>
{/literal}
{/strip}
