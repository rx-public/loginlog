<?php
/**
 * @class loginlogAdminView
 * @author XEPublic
 * @brief loginlog 모듈의 controller class
 **/

class loginlogAdminView extends loginlog
{
	/**
	 * @brief 초기화
	 */
	public function init()
	{
		// 템플릿 폴더 지정
		$this->setTemplatePath($this->module_path . 'tpl');

		$oLayoutModel = getModel('layout');

		// 레이아웃 목록을 가져와서 Context::set()
		Context::set('layout_list', $oLayoutModel->getLayoutList());
		$mlayout_list = $oLayoutModel->getLayoutList(0, 'M');

		Context::set('mlayout_list', $mlayout_list);

		$config = $this->getConfig();

		Context::set('config', $config);

		$layout_info = $oLayoutModel->getLayout($config->layout_srl);
		if ($layout_info)
		{
			$this->module_info->layout_srl = $config->layout_srl;
			$this->setLayoutPath($layout_info->path);
		}
	}

	/**
	 * @brief 로그인 기록 열람
	 */
	public function dispLoginlogAdminList()
	{
		if (!isset(self::$config->listSetting) || !is_array(self::$config->listSetting) || count(self::$config->listSetting) < 1)
		{
			self::$config->listSetting = array(
				'member.nick_name',
				'member.user_id',
				'member.email_address',
				'loginlog.ipaddress',
				'loginlog.regdate',
				'loginlog.platform',
				'loginlog.browser'
			);
		}

		$columnList = self::$config->listSetting;
		$columnList[] = 'loginlog.is_succeed';
		$columnList[] = 'loginlog.log_srl';
		$columnList[] = 'loginlog.member_srl';
		$columnList[] = 'loginlog.platform';
		$columnList[] = 'loginlog.browser';

		Context::set('loginlog_config', self::$config);
		// 목록을 구하기 위한 옵션
		$args = new stdClass;
		$args->page = Context::get('page'); ///< 페이지
		$args->list_count = 30; ///< 한페이지에 보여줄 기록 수
		$args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수
		$args->sort_index = 'loginlog.regdate';
		$args->order_type = 'desc';
		$args->daterange_start = (int)(str_replace('-', '', Context::get('daterange_start')) . '000000');
		$args->daterange_end = (int)(str_replace('-', '', Context::get('daterange_end')) . '235959');
		$args->isSucceed = Context::get('isSucceed');

		$ynList = array('Y' => 1, 'N' => 1);
		if (!isset($ynList[$args->isSucceed]))
		{
			unset($args->isSucceed);
		}

		$search_keyword = Context::get('search_keyword');
		$search_target = trim(Context::get('search_target'));

		if ($search_keyword)
		{
			switch ($search_target)
			{
				case 'member_srl':
					$args->member_srl = (int)$search_keyword;
					break;
				case 'user_id':
					$args->s_user_id = $search_keyword;
					array_push($columnList, 'member.user_id');
					break;
				case 'user_name':
					$args->s_user_name = $search_keyword;
					array_push($columnList, 'member.user_name');
					break;
				case 'nick_name':
					$args->s_nick_name = $search_keyword;
					array_push($columnList, 'member.nick_name');
					break;
				case 'ipaddress':
					$args->s_ipaddress = $search_keyword;
					array_push($columnList, 'loginlog.ipaddress');
					break;
				case 'platform':
					$args->s_platform = $search_keyword;
					break;
				case 'browser':
					$args->s_browser = $search_keyword;
					break;
			}
		}

		$columnList = array_unique($columnList);

		$output = executeQueryArray('loginlog.getLoginlogListWithinMember', $args, $columnList);

		// 템플릿에 쓰기 위해 Context::set
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('log_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		// 템플릿 파일 지정
		$this->setTemplateFile('list');
	}

	public function dispLoginlogAdminSetting()
	{
		// 생성된 그룹 목록을 가져옴
		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();

		Context::set('group_list', $group_list);
		Context::set('config', self::$config);

		$this->setTemplateFile('setting');
	}

	public function dispLoginlogAdminArrange()
	{
		$this->setTemplateFile('arrange');
	}

	public function dispLoginlogAdminDesign()
	{
		Context::set('config', self::$config);

		// 스킨 목록을 가져옴
		$skin_list = getModel('module')->getSkins($this->module_path);
		Context::set('skin_list', $skin_list);

		// 모바일 스킨 목록을 가져옴
		$mskin_list = getModel('module')->getSkins($this->module_path, 'm.skins');
		Context::set('mskin_list', $mskin_list);

		// 템플릿 파일 지정
		$this->setTemplateFile('design');
	}

	public function dispLoginlogAdminIpSearch()
	{
		$config = getModel('loginlog')->getModuleConfig();
		
		if (!isset($config->admin_kisa_key))
		{
			return $this->makeObject(-1, "msg_invalid_request");
		}
		
		$ip = Context::get('ipaddress');
		
		$this->setLayoutPath('./common/tpl/');
		$this->setLayoutFile('popup_layout');
		$this->setTemplatePath("$this->module_path/tpl");
		//TODO : 일부 XE 버전에서 getRemoteResource 함수가 정상적으로 작동하지 않을 가능성이 있음. 이 부분을 curl으로 대체해야함.
		//TODO : change XML to Json.
		$kisaXMLString = FileHandler::getRemoteResource("http://whois.kisa.or.kr/openapi/whois.jsp?query=$ip&key=$config->admin_kisa_key&answer=xml");
		$oXmlParser = new XmlParser();
		$content = $oXmlParser->parse($kisaXMLString);
		Context::set('content', $content);
		$this->setTemplateFile('ip_search');
	}

	/**
	 * 다중 계정 의심 목록
	 */
	public function dispLoginlogAdminMultipleConnectionList()
	{
		if (!isset(self::$config->listSetting) || !is_array(self::$config->listSetting) || count(self::$config->listSetting) < 1)
		{
			self::$config->listSetting = array(
				'member.nick_name',
				'member.user_id',
				'member.email_address',
				'loginlog.ipaddress',
				'loginlog.regdate',
				'loginlog.platform',
				'loginlog.browser'
			);
		}

		$columnList = self::$config->listSetting;
		$columnList[] = 'loginlog.is_succeed';
		$columnList[] = 'loginlog.log_srl';
		$columnList[] = 'loginlog.member_srl';
		$columnList[] = 'loginlog.platform';
		$columnList[] = 'loginlog.browser';

		// ??
		Context::set('loginlog_config', self::$config);
		// 목록을 구하기 위한 옵션
		$args = new stdClass;
		$args->page = Context::get('page'); ///< 페이지
		$args->list_count = 30; ///< 한페이지에 보여줄 기록 수
		$args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수
		$args->sort_index = 'loginlog.regdate';
		$args->order_type = 'desc';
		$args->daterange_start = (int)(str_replace('-', '', Context::get('daterange_start')) . '000000');
		$args->daterange_end = (int)(str_replace('-', '', Context::get('daterange_end')) . '235959');
		$args->isSucceed = Context::get('isSucceed');

		$ynList = array('Y' => 1, 'N' => 1);
		if (!isset($ynList[$args->isSucceed]))
		{
			unset($args->isSucceed);
		}

		$search_keyword = Context::get('search_keyword');
		$search_target = trim(Context::get('search_target'));

		if ($search_keyword)
		{
			switch ($search_target)
			{
				case 'member_srl':
					$args->member_srl = (int)$search_keyword;
					break;
				case 'user_id':
					$args->s_user_id = $search_keyword;
					array_push($columnList, 'member.user_id');
					break;
				case 'user_name':
					$args->s_user_name = $search_keyword;
					array_push($columnList, 'member.user_name');
					break;
				case 'nick_name':
					$args->s_nick_name = $search_keyword;
					array_push($columnList, 'member.nick_name');
					break;
				case 'ipaddress':
					$args->s_ipaddress = $search_keyword;
					array_push($columnList, 'loginlog.ipaddress');
					break;
				case 'platform':
					$args->s_platform = $search_keyword;
					break;
				case 'browser':
					$args->s_browser = $search_keyword;
					break;
			}
		}

		$columnList = array_unique($columnList);

		$output = executeQueryArray('loginlog.getMultipleConnectionList', $args, $columnList);
		
		// 템플릿에 쓰기 위해 Context::set
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('log_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('multiple_connection_list');
	}
}

/* End of file : loginlog.admin.view.php */
/* Location : ./modules/loginlog/loginlog.admin.view.php */
