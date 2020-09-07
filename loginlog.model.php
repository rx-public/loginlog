<?php
/**
 * @class loginlogModel
 * @brief loginlog 모듈의 model class
 * @author XEPublic
 **/

class loginlogModel extends loginlog
{
	/**
	 * @brief 초기화
	 */
	public function init()
	{
	}

	/**
	 * @brief 선택한 회원의 로그인 기록을 가져옵니다
	 */
	public function getLoginlogListByMemberSrl($memberSrl, $searchObj = NULL, $columnList = array())
	{
		$args = new stdClass;

		if($searchObj != NULL)
		{
			$args->daterange_start = $searchObj->daterange_start;
			$args->daterange_end = $searchObj->daterange_end;
			$args->s_browser = $searchObj->s_browser;
			$args->s_platform = $searchObj->s_platform;
		}

		$args->member_srl = $memberSrl;

		return executeQueryArray('loginlog.getLoginlogListByMemberSrl', $args, $columnList);
	}
}
