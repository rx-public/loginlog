<?php
/**
 * @class loginlog
 * @author XEPublic
 * @brief loginlog 모듈의 high class
 **/

class loginlog extends ModuleObject
{
	private $triggers = array(
		array('member.doLogin'		, 'loginlog', 'controller', 'triggerBeforeLogin',		'before'),
		array('member.doLogin'		, 'loginlog', 'controller', 'triggerAfterLogin',		'after'),
		array('member.deleteMember'	, 'loginlog', 'controller', 'triggerDeleteMember',		'after'),
		array('moduleHandler.init'	, 'loginlog', 'controller', 'triggerBeforeModuleInit',	'after'),
		array('moduleHandler.proc'	, 'loginlog', 'controller', 'triggerBeforeModuleProc',	'after')
	);

	protected static $config;
	/**
	 * @brief 모듈의 global 설정 구함
	 */
	protected function getConfig()
	{
		if(!isset(self::$config))
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('loginlog');

			// $config 변수 초기화
			if(!isset($config))
			{
				$config = new stdClass;
			}

			if(!$config->admin_user_log) $config->admin_user_log = 'N';

			// 로그인 기록 대상 그룹이 설정되어 있지 않은 경우 변수 초기화
			if(!isset($config->target_group))
			{
				$config->target_group = array();
			}

			// 표시 항목 설정값
			if(!is_array($config->listSetting))
			{
				if($config->listSetting) $config->listSetting = explode('|@|', $config->listSetting);
				else $config->listSetting = array();
			}

			// 엑셀 파일(XLS) 내보내기 설정값
			if(!isset($config->exportConfig)) $config->exportConfig = new stdClass;
			if(!$config->exportConfig->listCount) $config->exportConfig->listCount = 100;
			if(!$config->exportConfig->pageCount) $config->exportConfig->pageCount = 10;

			if(!$config->exportConfig->includeGroup || !is_array($config->exportConfig->includeGroup))
			{
				if($config->exportConfig->includeGroup) $config->exportConfig->includeGroup = explode('|@|', $config->exportConfig->includeGroup);
				else $config->exportConfig->includeGroup = array();
			}

			if(!is_array($config->exportConfig->excludeGroup))
			{
				if($config->exportConfig->excludeGroup) $config->exportConfig->excludeGroup = explode('|@|', $config->exportConfig->excludeGroup);
				else $config->exportConfig->excludeGroup = array();
			}

			if(!isset($config->design))
			{
				$config->design = new stdClass;
			}

			if(!$config->design->hideLoginlogTab) $config->design->hideLoginlogTab = 'Y';
			self::$config = $config;
		}

		return self::$config;
	}
	
	/**
	 * @brief 모듈 설치
	 */
	public function moduleInstall()
	{
		$this->insertTrigger();

		return $this->makeObject();
	}

	/**
	 * @brief 모듈 삭제
	 */
	public function moduleUninstall()
	{
		$oModuleController = getController('module');

		foreach($this->triggers as $trigger)
		{
			$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
		}

		return $this->makeObject();
	}

	/**
	 * @brief 업데이트가 필요한지 확인
	 **/
	public function checkUpdate()
	{
		if(!$this->checkTrigger())
		{
			return true;
		}

		// 로그인 성공 여부를 기록하는 is_succeed 칼럼 추가 (2010.09.13)
		$oDB = DB::getInstance();
		if(!$oDB->isColumnExists('member_loginlog', 'is_succeed'))
		{
			return true;
		}

		// log_srl 칼럼 추가 (2014.11.09)
		if(!$oDB->isColumnExists('member_loginlog', 'log_srl'))
		{
			return true;
		}

		// platform, browser 칼럼 추가 (2013.12.25)
		if(!$oDB->isColumnExists('member_loginlog', 'platform'))
		{
			return true;
		}
		if(!$oDB->isColumnExists('member_loginlog', 'browser'))
		{
			return true;
		}

		// user_id, email_address 칼럼 추가 (2014.07.06)
		if(!$oDB->isColumnExists('member_loginlog', 'user_id'))
		{
			return true;
		}
		if(!$oDB->isColumnExists('member_loginlog', 'email_address'))
		{
			return true;
		}

		return false;
	}

	/**
	 * 모든 트리거가 등록되었는지 확인
	 *
	 * @return boolean
	 */
	public function checkTrigger()
	{
		$oModuleModel = getModel('module');

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return false;
			}
		}

		return true;
	}

	public function insertTrigger()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}
	}

	/**
	 * @brief 모듈 업데이트
	 **/
	public function moduleUpdate()
	{
		// db가 큰 경우 시간 초과로 모듈 업데이트가 되지 않는 경우를 방지
		@set_time_limit(0);

		$this->insertTrigger();

		// 로그인 성공 여부를 기록하는 is_succeed 칼럼 추가 (2010.09.13)
		$oDB = DB::getInstance();
		if(!$oDB->isColumnExists('member_loginlog', 'is_succeed'))
		{
			$oDB->addColumn('member_loginlog', 'is_succeed', 'char', 1, 'Y', true);
			$oDB->addIndex('member_loginlog', 'idx_is_succeed', 'is_succeed', false);
		}

		// log_srl 칼럼 추가 (2014.11.09)
		if(!$oDB->isColumnExists('member_loginlog', 'log_srl'))
		{
			$oDB->addColumn('member_loginlog', 'log_srl', 'number', 11, '', true);
		}

		// platform, browser 칼럼 추가 (2013.12.25)
		if(!$oDB->isColumnExists('member_loginlog', 'platform'))
		{
			$oDB->addColumn('member_loginlog', 'platform', 'varchar', 50, '', true);
			$oDB->addIndex('member_loginlog', 'idx_platform', 'platform', false);
		}
		if(!$oDB->isColumnExists('member_loginlog', 'browser'))
		{
			$oDB->addColumn('member_loginlog', 'browser', 'varchar', 50, '', true);
			$oDB->addIndex('member_loginlog', 'idx_browser', 'browser', false);
		}

		// user_id, email_address 칼럼 추가 (2014.07.06)
		if(!$oDB->isColumnExists('member_loginlog', 'user_id'))
		{
			$oDB->addColumn('member_loginlog', 'user_id', 'varchar', 80, '', true);
			$oDB->addIndex('member_loginlog', 'idx_user_id', 'user_id', false);
		}
		if(!$oDB->isColumnExists('member_loginlog', 'email_address'))
		{
			$oDB->addColumn('member_loginlog', 'email_address', 'varchar', 250, '', true);
			$oDB->addIndex('member_loginlog', 'idx_email_address', 'email_address', false);
		}

		return $this->makeObject(0, 'success_updated');
	}

	/**
	 * @brief 캐시 파일 재생성
	 **/
	public function recompileCache()
	{
	}

    public function makeObject($code = 0, $msg = 'success')
    {
        return class_exists('BaseObject') ? new BaseObject($code, $msg) : new Object($code, $msg);
    }
}

/* End of file : loginlog.class.php */
/* Location : ./modules/loginlog/loginlog.class.php */
