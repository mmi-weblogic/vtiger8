<?php
class Accounts_List_View extends Vtiger_List_View {

	function getHeaderScripts(Vtiger_Request $request) {
		$scripts = parent::getHeaderScripts($request);
		$scripts[] = Vtiger_JsScript_Model::getInstanceFromValues([
			'src' => 'layouts/v7/resources/EngagementScore.js',
		]);
		return $scripts;
	}
}
