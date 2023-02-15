<?php


namespace app\common\logic;


class Extension extends Common{
	public function _initialize()
	{
		parent::_initialize();
	}
	
	/**
	 * @api   /giveInviScore/   邀请好友获取积分
	 * @apiName giveInviScore
	 *
	 * @auther oldx
	 * @data 19/12/27
	 * @time 10:19
	 *
	 * @apiParam {String} $uid  邀请码提供者
	 * @apiParam {String} $Invited_person_id  被邀请人的id,就是注册用户
	 *
	 */
	public function giveInviScore( $uid = '' ,$Invited_person_id = '' ) {
		$score = $this->getOperatorConfig('invite_friends_score') ?: config('invite_friends_score');
		$info['score'] = $score;
		$info['type'] = 0;
		$info['source'] = 2;
		$info['add_time'] = time();
		$info['uid'] = $uid;
		$info['Invited_person_id'] = $Invited_person_id;
		
		//插入记录
		$this->db->name('score')
		         ->insert($info);
		//更新用户表
		$this->db->name('user')
		         ->where(['id'=>$uid])
		         ->setInc('score',$score);
		
		$this->db->name('user')
		         ->where(['id'=>$Invited_person_id])
		         ->update(['lnvitation_uid'=>$uid]);
	}
}