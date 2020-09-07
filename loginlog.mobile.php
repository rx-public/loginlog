<?php
/**
 * @class loginlogMobile
 * @brief loginlog 모듈의 mobile class
 * @author XEPublic
 */
require_once(_XE_PATH_.'modules/loginlog/loginlog.view.php');

class loginlogMobile extends loginlogView
{
	function init()
	{
		// Get the member configuration
		$config = $this->getConfig();
		Context::set('loginlog_config', $config);

		$mskin = $config->design->mskin;
		// Set the template path
		$template_path = sprintf('%sm.skins/%s',$this->module_path, $mskin);
		if(!is_dir($template_path)||!$mskin)
		{
			$mskin = 'default';
			$template_path = sprintf('%sm.skins/%s', $this->module_path, $mskin);
		}
		else
		{
			$template_path = sprintf('%sm.skins/%s', $this->module_path, $mskin);
		}
		
		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($config->design->layout_srl);
		if($layout_info)
		{
			$this->module_info->layout_srl = $config->design->layout_srl;
			$this->setLayoutPath($layout_info->path);
		}

		$this->setTemplatePath($template_path);
	}

	function dispLoginlogHistories()
	{
		parent::dispLoginlogHistories();
	}
}
/* End of file member.mobile.php */
/* Location: ./modules/member/member.mobile.php */
