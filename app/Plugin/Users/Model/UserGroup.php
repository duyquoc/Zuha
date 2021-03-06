<?php
App::uses('UsersAppModel', 'Users.Model');

/**
 * Extension Code
 * $refuseInit = true; require_once(ROOT.DS.'app'.DS.'Plugin'.DS.'Users'.DS.'Model'.DS.'UserGroup.php');
 * @property UsersUserGroup UsersUserGroup
 * @property User User
 */
class AppUserGroup extends UsersAppModel {

	public $name = 'UserGroup';

	public $displayField = 'title';
	
	public $hasAndBelongsToMany = array(
        'User' => array(
			'className' => 'Users.User',
            'joinTable' => 'users_user_groups',
			'foreignKey' => 'user_group_id',
			'associationForeignKey' => 'user_id'
			),
		);
    
    public $belongsTo = array(
		'Creator' => array(
			'className' => 'Users.User',
			'foreignKey' => 'creator_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
			),
		);
	
	public $hasMany = array(
		'UsersUserGroup'=>array(
			'className'     => 'Users.UsersUserGroup',
            'foreignKey'    => 'user_group_id',
			'dependent'		=> true
		),
		'UserGroupWallPost'=>array(
			'className'     => 'Users.UserGroupWallPost',
            'foreignKey'    => 'user_group_id',
			'dependent'		=> true
		)
	);
	
	public function findUserGroupsByModerator($type = 'list', $params = array('order' => 'UserGroup.title')) {
		// you must be a moderator to see groups
		$userRoleId = CakeSession::read('Auth.User.user_role_id');
		if ($userRoleId != 1) {
			$userId = CakeSession::read('Auth.User.id');
			$params['joins'] = array(array(
				'table' => 'users_user_groups',
				'alias' => 'UsersUserGroup',
				'type' => 'INNER',
				'conditions' => array(
					'UsersUserGroup.user_id' => $userId,
					'UsersUserGroup.is_moderator' => 1,
					),
				));
		}
		return $this->find($type, $params);
	}
	
	public function findUserGroupStatus($type = 'first', $params = null) {
		// you must be a moderator to see groups
		$userRoleId = CakeSession::read('Auth.User.user_role_id');
		if ($userRoleId == 1) {
			$status = 'moderator';
		} else {
			$params['conditions']['UsersUserGroup.user_id'] = CakeSession::read('Auth.User.id');
			$usersUserGroup = $this->UsersUserGroup->find($type, $params);
			if ($usersUserGroup['UsersUserGroup']['is_moderator'] == 1) {
				$status = 'moderator';
			} else if ($usersUserGroup['UsersUserGroup']['is_approved'] == 1) {
				$status = 'approved';
			} else if (!empty($usersUserGroup['UsersUserGroup'])) {
				$status = 'pending';
			} else {
				$status = null;
			}
		}
		return $status;
	}
	
	public function findRelated($model = null, $type = 'list', $params = array('order' => 'UserGroup.title')) {
		// groups can be assigned to only be available to certain other systems by associating a model to the group
		$params['conditions']['UserGroup.model'] = $model;
		return $this->find($type, $params);
	}

	public function approve($pendingId,$groupId,$userId){
		if(!empty($pendingId) && !empty($groupId) && !empty($userId)){
			$isMyGroup = $this->isMyGroup($groupId,$userId);
			if($isMyGroup){
				$row = $this->UsersUserGroup->read(null,$pendingId);

				if($row['UsersUserGroup']['user_group_id'] === $groupId){
					$row['UsersUserGroup']['is_approved'] = 1;
					$this->UsersUserGroup->save($row);
				}
			}

		}
	}
	public function isMyGroup($id,$userId){
		return $this->find('count',array('fields'=>array('Creator.id'),'conditions'=>array('UserGroup.id'=>$id,'Creator.id'=>$userId),'contain'=>array('Creator')))
			===1;
	}

/**
 * User method
 * 
 * Create a user and add to the provided group id
 * 
 * @param array $data
 */
	public function user($data) {
		return $this->User->procreate($data);
	}
	
/**
 * Process invite
 * 
 * Used as a callback from the Invite Model, and automatically adds the invited user to the group.
 * Must return true if it worked.
 */
 	public function processInvite($invite) {
 		$userGroup = $this->find('first', array('conditions' => array('UserGroup.id' => $invite['Invite']['foreign_key'])));
		if (!empty($userGroup)) {
			$data['UsersUserGroup']['user_id'] = CakeSession::read('Auth.User.id');
			$data['UsersUserGroup']['user_group_id'] = $invite['Invite']['foreign_key'];
			$data['UsersUserGroup']['is_approved'] = 1;
			if ($this->UsersUserGroup->save($data)) {
				return true;
			} else {
				throw new Exception(__('Auto add to user group failed.'));
			}
		} else {
			throw new Exception(__('Group does not exist.'));
		}
 	}
}

if (!isset($refuseInit)) {
	class UserGroup extends AppUserGroup {}
}