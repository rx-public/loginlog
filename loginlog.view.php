<?php
/**
 * @class loginlogView
 * @brief loginlog 모듈의 view class
 * @author XEPublic
 */
class loginlogView extends loginlog
{
	/**
	 * 초기화
	 */
	public function init()
	{
		$config = $this->getConfig();
		
		$template_path = sprintf("%sskins/%s/",$this->module_path, $config->design->skin);
		if(!is_dir($template_path)||!$config->design->skin)
		{
			$config->design->skin = 'default';
			$template_path = sprintf("%sskins/%s/",$this->module_path, $config->design->skin);
		}
		$this->setTemplatePath($template_path);

		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($config->design->layout_srl);
		if($layout_info)
		{
			$this->module_info->layout_srl = $config->design->layout_srl;
			$this->setLayoutPath($layout_info->path);
		}
	}

	/**
	 * 로그인 기록
	 */
	public function dispLoginlogHistories()
	{
		$logged_info = Context::get('logged_info');
		if(!Rhymix\Framework\Session::getMemberSrl())
		{
			return $this->makeObject();
		}
		
		if(self::$config->hideLoginlogTab === 'N')
		{
			return $this->makeObject();
		}

		// 목록을 구하기 위한 옵션
		$args = new stdClass;
		$args->page = Context::get('page'); ///< 페이지
		$args->list_count = 30; ///< 한페이지에 보여줄 기록 수
		$args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수
		$args->sort_index = 'log_srl';
		$args->order_type = 'desc';
		$args->member_srl = $logged_info->member_srl;

		$output = executeQueryArray('loginlog.getLoginlogList', $args);

		// 템플릿에 쓰기 위해 Context::set
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('histories', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('histories');
	}
}
