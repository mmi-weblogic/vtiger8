<?php
class Settings_LoginHistory_Dashboard_View extends Settings_LoginHistory_List_View {

	function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('Dashboard.tpl', $request->getModule(false));
	}

	function getPageTitle(Vtiger_Request $request) {
		return 'Login Analytics Dashboard';
	}
}
