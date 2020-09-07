<?php
/**
 * @class loginlogController
 * @author XEPublic
 * @brief loginlog 모듈의 controller class
 **/

class loginlogController extends loginlog
{
	/**
	 * @brief 초기화
	 */
	public function init()
	{
	}

	/**
	 * @brief 기간 내의 로그인 기록 삭제 (Cron 호출용)
	 */
	public function deleteLogsByCron($type = 'ALL', $period = 1)
	{
		$args = new stdClass;

		switch($type)
		{
			/**
			 * 모든 기록 삭제 시 별도의 매개변수 전달이 필요하지 않음
			 */
			case 'ALL':
				break;
			case 'DAILY':
				$str = '-'. $period. ' day';
				$args->expire_date = date('Ymd000000', strtotime($str)); // -1 day
				break;
			case 'WEEKLY':
				$str = '-'. $period. ' week';
				$args->expire_date = date('Ymd000000', strtotime($str)); // -1 week
				break;
			case 'MONTHLY':
				$str = '-'. $period. ' month';
				$args->expire_date = date('Ymd000000', strtotime($str)); // -1 month
				break;
			case 'YEARLY':
				$str = '-'. $period. ' year';
				$args->expire_date = date('Y00000000', strtotime($str)); // -1 year
				break;
		}

		executeQuery('loginlog.initLoginlogs', $args);
	}

	/**
	 * @brief 주어진 기간 내의 로그인 기록 삭제
	 */
	public function deleteLogsByCronUsingDate($start_date, $end_date)
	{
		$args = new stdClass;
		$args->start_date = $start_date;
		$args->expire_date = $end_date;

		executeQuery('loginlog.initLoginlogs', $args);
	}

	/**
	 * @brief 로그인 전에 실행되는 트리거
	 */
	public function triggerBeforeLogin(&$obj)
	{
		// 넘어온 아이디가 없다면 실행 중단
		if(!$obj->user_id)
		{
			return $this->makeObject();
		}

		if(!$obj->password)
		{
			return $this->makeObject();
		}

		$oMemberModel = memberModel::getInstance();
		$memberConfig = $oMemberModel->getMemberConfig();
		$args = new stdClass();
		if($memberConfig->identifier == 'email_address')
		{
			$args->email_address = $obj->user_id;
		}
		else
		{
			$args->user_id = $obj->user_id;
		}

		$loginMemberInfo = executeQuery('loginlog.getMemberPassword', $args)->data;
		
		// 존재하지 않는 회원이라면 기록하지 않음
		if(!$loginMemberInfo)
		{
			return $this->makeObject();
		}

		$member_srl = $loginMemberInfo->member_srl;

		// 대상 회원의 비밀번호
		$password = $loginMemberInfo->password;

		// 비밀번호가 맞다면 기록하지 않음
		if($oMemberModel->isValidPassword($password, $obj->password))
		{
			return $this->makeObject();
		}

		// 로그인 기록 대상 그룹이 설정되어 있다면...
		if(!$this->checkTheGroupConfig($member_srl))
		{
			return $this->makeObject();
		}
		
		require _XE_PATH_ . 'modules/loginlog/libs/Browser.php';

		$browser = new Browser();
		$browserName = $browser->getBrowser();
		$browserVersion = $browser->getVersion();
		$platform = $browser->getPlatform();

		$user_id = $loginMemberInfo->user_id;
		$email_address = $loginMemberInfo->email_address;

		// 로그인 기록을 남깁니다
		$log_info = new stdClass;
		$log_info->member_srl = $member_srl;
		$log_info->platform = $platform;
		$log_info->browser = $browserName . ' ' . $browserVersion;
		$log_info->user_id = $user_id;
		$log_info->email_address = $email_address;
		$this->insertLoginlog($log_info, false);

		return $this->makeObject();
	}

	/**
	 * @brief 로그인 성공 후 실행되는 트리거
	 */
	public function triggerAfterLogin($member_info)
	{
		if(!$member_info->member_srl)
		{
			return $this->makeObject();
		}

		// 로그인 기록 모듈의 설정값을 구함
		$oLoginlogModel = getModel('loginlog');
		$config = $oLoginlogModel->getModuleConfig();

		// 최고관리자는 기록하지 않는다면 패스~
		if($config->admin_user_log != 'Y' && $member_info->is_admin == 'Y')
		{
			return $this->makeObject();
		}


		// 로그인 기록 대상 그룹이 설정되어 있다면...
		if(!$this->checkTheGroupConfig($member_info->member_srl))
		{
			return $this->makeObject();
		}

		require _XE_PATH_ . 'modules/loginlog/libs/Browser.php';

		$browser = new Browser();
		$browserName = $browser->getBrowser();
		$browserVersion = $browser->getVersion();
		$platform = $browser->getPlatform();

		// 로그인 기록을 남깁니다
		$log_info = new stdClass;
		$log_info->member_srl = $member_info->member_srl;
		$log_info->platform = $platform;
		$log_info->browser = $browserName . ' ' . $browserVersion;
		$log_info->user_id = $member_info->user_id;
		$log_info->email_address = $member_info->email_address;
		$this->insertLoginlog($log_info);

		return $this->makeObject();
	}

	/**
	 * @param int|string $member_srl
	 * @return Boolean
	 */
	public function checkTheGroupConfig($member_srl)
	{
		if(!$member_srl)
		{
			return false;
		}
		
		if(!is_numeric($member_srl))
		{
			return false;
		}
		
		$config = loginlogModel::getInstance()->getModuleConfig();
		if(is_array($config->target_group) && count($config->target_group) > 0)
		{
			// memberModel 객체 생성
			$oMemberModel = getModel('member');

			// 소속된 그룹을 구합니다
			$group_list = $oMemberModel->getMemberGroups($member_srl);

			// loop를 돌면서 해당 그룹에 소속되어 있는 지 확인합니다
			foreach($group_list as $group_srl => $group_title)
			{
				if(in_array($group_srl, $config->target_group))
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @brief 회원 탈퇴 시 로그인 기록 삭제
	 */
	public function triggerDeleteMember(&$obj)
	{
		if(!$obj->member_srl)
		{
			return $this->makeObject();
		}

		$oModel = getModel('loginlog');
		$config = $oModel->getModuleConfig();

		if($config->delete_logs != 'Y')
		{
			return $this->makeObject();
		}

		executeQuery('loginlog.deleteMemberLoginlogs', $obj);

		return $this->makeObject();
	}

	public function triggerBeforeModuleInit(&$obj)
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info)
		{
			return $this->makeObject();
		}
		
		$config = getModel('loginlog')->getModuleConfig();

		/**
	 	* 로그인 기록 메뉴 추가
	 	*/	
		if($config->design->hideLoginlogTab === 'A' || ($config->design->hideLoginlogTab === 'N') && $logged_info->is_admin === 'Y')
		{
			
			getController('member')->addMemberMenu('dispLoginlogHistories', 'cmd_view_loginlog');
		}
		
	}

	public function triggerBeforeModuleProc()
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info)
		{
			return $this->makeObject();
		}

		/**
		 * 관리자로 로그인 한 경우 회원 메뉴에 로그인 기록 추적 메뉴 추가
		 */
		if($this->act == 'getMemberMenu' && $logged_info->is_admin == 'Y')
		{
			$oMemberController = getController('member');

			$member_srl = Context::get('target_srl');
			$url = getUrl('', 'module', 'admin', 'act', 'dispLoginlogAdminList', 'search_target', 'member_srl', 'search_keyword', $member_srl);

			$oMemberController->addMemberPopupMenu($url, Context::getLang('cmd_trace_loginlog'), '', '_blank');
		}
	}

	public function insertLoginlog($log_info, $isSucceed = true)
	{
		$args = new stdClass;
		$args->log_srl = getNextSequence();
		$args->member_srl = &$log_info->member_srl;
		$args->is_succeed = $isSucceed ? 'Y' : 'N';
		$args->regdate = date('YmdHis');
		$args->platform = &$log_info->platform;
		$args->browser = &$log_info->browser;
		$args->user_id = &$log_info->user_id;
		$args->email_address = &$log_info->email_address;

		// 클라우드플레어 사용 시 실제 사용자 IP를 기록하도록 한다
		if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
		{
			$args->ipaddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		return executeQuery('loginlog.insertLoginlog', $args);
	}
}

/* End of file : loginlog.controller.php */
/* Location : ./modules/loginlog/loginlog.controller.php */
