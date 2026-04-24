<?php
class Contacts_List_View extends Vtiger_List_View {

	function getHeaderScripts(Vtiger_Request $request) {
		$scripts = parent::getHeaderScripts($request);
		$extra   = $this->checkAndConvertJsScripts(['~layouts/v7/resources/EngagementScore.js']);
		return array_merge($scripts, $extra);
	}
}
