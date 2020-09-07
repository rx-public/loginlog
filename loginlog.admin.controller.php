<?php
/**
 * @class loginlogAdminController
 * @author XEPublic
 * @brief loginlog 모듈의 admin controller class
 **/

class loginlogAdminController extends loginlog
{
	/**
	 * @brief 초기화
	 */
	public function init()
	{
	}

	/**
	 * @brief 모듈 설정 저장
	 */
	public function procLoginlogAdminInsertConfig()
	{
		$config = $this->getConfig();

		// 넘어온 설정값과 기존에 저장된 설정값을 합칩니다.
		$config->delete_logs = Context::get('delete_logs');
		$config->admin_user_log = Context::get('admin_user_log');
		$config->target_group = Context::get('target_group');
		$config->exportConfig = new stdClass;
		$config->exportConfig->exportType = Context::get('exportType');
		$config->exportConfig->listCount = Context::get('listCountForExport');
		$config->exportConfig->pageCount = Context::get('pageCountForExport');
		$config->exportConfig->includeGroup = Context::get('includeGroup');
		$config->exportConfig->excludeGroup = Context::get('excludeGroup');
		$config->exportConfig->includeAdmin = Context::get('includeAdmin');
		$config->admin_kisa_key = Context::get('admin_kisa_key');

		// 불필요한 값을 제거합니다.
		unset($config->body);
		unset($config->_filter);
		unset($config->error_return_url);
		unset($config->act);
		unset($config->module);

		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('loginlog', $config);


		// 저장 후 이동할 URL을 지정합니다
		$returnUrl = Context::get('success_return_url');
		if(!$returnUrl)
		{
			$returnUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispLoginlogAdminSetting');
		}

		$this->setMessage('success_saved');

		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief 표시 항목 설정 저장
	 */
	public function procLoginlogAdminSaveListSetting()
	{
		// GET 방식으로 접근하는 것을 방지합니다.
		if(Context::getRequestMethod() == 'GET') return $this->makeObject(-1, 'msg_invalid_request');

		$config = $this->getConfig();

		// 불필요한 값을 제거합니다.
		unset($config->body);
		unset($config->_filter);
		unset($config->error_return_url);
		unset($config->act);
		unset($config->module);
		unset($config->ruleset);

		$config->listSetting = Context::get('listSetting');

		// DB에 설정값을 저장합니다
		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('loginlog', $config);

		// 메시지 지정
		$this->setMessage('success_saved');

		// 저장 후 이동할 URL을 지정합니다
		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispLoginlogAdminList');
		$this->setRedirectUrl($returnUrl);
	}


	/**
	 * 디자인 설정 저장
	 */
	public function procLoginlogAdminInsertDesignConfig()
	{
		// GET 방식으로 접근하는 것을 방지합니다.
		if(Context::getRequestMethod() == 'GET') return $this->makeObject(-1, 'msg_invalid_request');

		$config = $this->getConfig();

		// 불필요한 값을 제거합니다.
		unset($config->body);
		unset($config->_filter);
		unset($config->error_return_url);
		unset($config->act);
		unset($config->module);
		unset($config->ruleset);

		$config->design = Context::gets('skin', 'mskin', 'layout_srl', 'mlayout_srl', 'hideLoginlogTab');

		// DB에 설정값을 저장합니다
		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('loginlog', $config);

		// 메시지 지정
		$this->setMessage('success_saved');

		// 저장 후 이동할 URL을 지정합니다
		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispLoginlogAdminDesign');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief 기록 정리하기
	 */
	public function procLoginlogAdminCleanLog()
	{
		$args = new stdClass();
		if(Context::get('expire_date'))
		{
			$args->expire_date = Context::get('expire_date');
		}

		$msg_code = 'success_clean_log';

		$output = executeQuery('loginlog.initLoginlogs', $args);
		if(!$output->toBool()) $msg_code = 'msg_failed_clean_logs';

		// 저장 후 이동할 URL을 지정합니다
		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispLoginlogAdminArrange');
		$this->setRedirectUrl($returnUrl);

		$this->setMessage($msg_code);
	}

	/**
	 * @brief 선택한 항목 삭제
	 */
	public function procLoginlogAdminDeleteChecked()
	{
		$log_srls= Context::get('cart');

		$log_count = count($log_srls);
		for($i=0; $i<$log_count; $i++)
		{
			$log_srl = $log_srls[$i];
			$this->deleteLog($log_srl);
		}

		// 저장 후 이동할 URL을 지정합니다
		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispLoginlogAdminList');
		$this->setRedirectUrl($returnUrl);

		$this->setMessage('success_deleted');
	}

	/**
	 * @brief 데이터 내보내기
	 */
	public function procLoginlogAdminExport()
	{
		/**
		* PHP 버전이 5.2 미만인 경우 데이터 내보내기 기능을 사용할 수 없습니다.
		*/
		if(version_compare(phpversion(), '5.2.0', '<'))
		{
			$phpWarning = sprintf(Context::getLang('php_version_warning_for_feature'), phpversion());
			return $this->makeObject(-1, $phpWarning);
		}

		@set_time_limit(0);

		Context::setRequestMethod('XMLRPC');

		require dirname(__FILE__) . '/libs/Export/Interface/Interface.php';
		require dirname(__FILE__) . '/libs/Export/Core.php';

		$exportFileType = Context::get('type');

		switch($exportFileType)
		{
			case 'html':
				// 라이브러리 파일명
				$classFile = 'HTML.php';
				// 파일 이름 (loginlog_yyyy-mm-dd.html)
				$filename = 'loginlog_' . date('Y-m-d'); // HTML 파일 이름
				break;
			case 'excel':
				// 라이브러리 파일명
				$classFile = 'Excel.php';
				// 파일 이름 (loginlog_yyyy-mm-dd.xls)
				$filename = 'loginlog_' . date('Y-m-d');
				// Set template path and template file
				$this->setTemplatePath($this->module_path.'tpl');
				$this->setTemplateFile('_exportToHTML');
				break;
		}

		require dirname(__FILE__) . '/libs/Export/Excel/' . $classFile;

		// 로그인 정보를 구함
		$logged_info = Context::get('logged_info');

		$title = '로그인 기록';

		$startDate = Context::get('startDate');
		$endDate = Context::get('endDate');

		if($startDate || $endDate)
		{
			$title .=' ( ';
			if($startDate)
			{
				$title .= $startDate . ' ~ ';
				if($endDate)
				{
					$title .= $endDate;
				}
			}
			else
			{
				if($endDate)
				{
					$title .= ' ~ ' . $endDate;
				}
			}

			$title .=' )';
		}

		/**
		 * Excel 문서의 속성을 지정합니다. (작성자를 로그인 한 회원의 닉네임으로 지정합니다)
		 */
		$options = array(
			'start_date' => $startDate,
			'end_date' => $endDate,
			'title' => $title,
			'filename' => $filename,
			'properties' =>
				array(
					'creator' => $logged_info->nick_name,
					'modifier' => $logged_info->nick_name
				),
			'font' =>
				array(
					'name' => '나눔고딕',
					'size' => 9
				)
		);

		// PHPExcel 객체 생성
		$object = new Export_Excel($options);

		$object->export();
	}
	/**
	 * @brief 선택한 데이터 내보내기
	 */
	public function procLoginlogAdminExportChecked()
	{
		/**
		* PHP 버전이 5.2 미만인 경우 데이터 내보내기 기능을 사용할 수 없습니다.
		*/
		if(version_compare(phpversion(), '5.2.0', '<'))
		{
			$phpWarning = sprintf(Context::getLang('php_version_warning_for_feature'), phpversion());
			return $this->makeObject(-1, $phpWarning);
		}

		@set_time_limit(0);

		Context::setRequestMethod('XMLRPC');

		require dirname(__FILE__) . '/libs/Export/Interface/Interface.php';
		require dirname(__FILE__) . '/libs/Export/Core.php';

		$exportFileType = Context::get('type');

		switch($exportFileType)
		{
			case 'excel':
				// 라이브러리 파일명
				$classFile = 'Excel.php';
				// 파일 이름 (loginlog_yyyy-mm-dd.xls)
				$filename = 'loginlog_' . date('Y-m-d');
				// Set template path and template file
				$this->setTemplatePath($this->module_path.'tpl');
				$this->setTemplateFile('_exportToHTML');
				break;
		}

		require dirname(__FILE__) . '/libs/Export/Excel/' . $classFile;

		// 로그인 정보를 구함
		$logged_info = Context::get('logged_info');

		/**
		 * Excel 문서의 속성을 지정합니다. (작성자를 로그인 한 회원의 닉네임으로 지정합니다)
		 */
		$options = array(
			'filename' => $filename,
			'properties' =>
				array(
					'creator' => $logged_info->nick_name,
					'modifier' => $logged_info->nick_name
				),
			'font' =>
				array(
					'name' => '나눔고딕',
					'size' => 9
				)
		);

		// PHPExcel 객체 생성
		$object = new Export_Excel($options);

		$object->export();
	}

	public function deleteLog($log_srl)
	{
		$args = new stdClass;
		$args->log_srl = $log_srl;
		return executeQuery('loginlog.deleteLog', $args);
	}
}
