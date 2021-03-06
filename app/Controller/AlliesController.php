<?php 
class AlliesController extends AppController{
	
	public $paginate = array(
		'limit'		=> 25,
		'contain'	=> false
	);
	
	function beforeFilter(){
		parent::beforeFilter();
		$this->Auth->allowedActions = array('accept_ally');
	}
	
	/*
	 * Ally Status
	 *  0 : Sent Ally request, but not approved
	 *  1 : Request Sent and approved
	 * -1 : Blocked User
	 */
	public function allies() {
		$current_allies = $this->Ally->find('all', array(
									'contain' => array('MyAlly', 'UserGameStatus'),
									'conditions' => array('Ally.user_id' => $this->Session->read('ActiveGame.user_id'),
														  'Ally.status != -1')));
		$allies_of = $this->Ally->find('all', array(
									'contain' => array('User', 'UserGameStatus'),
									'conditions' => array('Ally.ally' => $this->Session->read('ActiveGame.user_id'),
														  'Ally.status != -1')));
		/*
		$this->Ally->updateAll(array('Ally.ally_notification' => ''),
							   array('Ally.ally_notification' => 'Requested', 
							   		 'Ally.user_id' => $this->Session->read('ActiveGame.user_id')));
		
		$this->Ally->updateAll(array('Ally.ally_notification' => ''),
							   array('Ally.ally_notification' => 'Accepted',
									 'Ally.ally' => $this->Session->read('ActiveGame.user_id')));
		*/
		$this->Session->write('Game.query_all', 1);
		foreach($allies_of as $key => $ally) {
			$options = array(
					'Game.user_id' 				=> $ally['UserGameStatus']['user_id'], 
					'Game.user_game_status_id' 	=> $ally['UserGameStatus']['id'],
					'Game.configuration_id'		=> 59
			);
			$development = $this->Ally->User->Game->field('id', $options);
			if($development != '') {
				$options['contain'] = false;
				$options['fields'] = array('name', 'complete_by');
				$options['conditions'] = array('Challenge.goal_id' => $development);
				$allies_of[$key]['Challenge'] = $this->Ally->User->Game->Challenge->find('first', $options);
			} else {
				$allies_of[$key]['Challenge']['Challenge'] = array('name' => '', 'complete_by' => '');
			}
		}
		$this->Session->write('Game.query_all', 0);
		
		$this->set('current_allies', $current_allies);
		$this->set('allies_of', $allies_of);
		
		if(isset($this->request->params['requested']) && $this->request->params['requested']) {
			return $current_allies;
		}
	}
	
	public function allies_list($action) {
		$searchText = $this->request->data['search'];
		$users 		= array();
		$message	= '';
		
		if(!empty($searchText)) {
			
			if($searchText == $this->Auth->User('email') || $searchText == $this->Auth->User('name')) {
				$message = 'This is you. Please add your friends as ally.';
				
			} else {
				
				$options['fields']		= array('Ally.ally', 'Ally.ally_email');
				$options['conditions']  = array('OR' => 
											array('Ally.user_id' => $this->Session->read('ActiveGame.user_id'),
												  'AND' => array('Ally.ally' => $this->Session->read('ActiveGame.user_id'),
																 'Ally.status' => -1)));
				$allies = $this->Ally->find('list', $options);
				
				if(!empty($allies)) {
					$allies = array('User.email NOT' => $allies);
				}

				$options['contain']		= false;
				$options['fields']		= array('User.id', 'User.name', 'User.city', 'User.email', 'User.slug');
				$options['conditions']  = array(
							'OR' => array('User.email LIKE' => '%' . $searchText . '%', 'User.name LIKE' => '%' . $searchText . '%'),
							'User.id !=' => $this->Session->read('ActiveGame.user_id'), $allies);
					
				$users = $this->Ally->User->find('all', $options);
			}
			
		}
		
		$this->set('message', $message);
		$this->set('answers', $users);
	}
	
	public function request_action($action, $id) {
		
		$this->loadModel('Feedback');
		$this->autoRender = false;
		if($action == 'delete') {
			$user_id = $this->Ally->field('ally', array('Ally.id' => $id));
			
			if($this->Ally->delete($id, true)) {
			
				$condition = array('Feedback.user_id' => $user_id, 'Feedback.user_game_status_id' => $this->Session->read('ActiveGame.id'));
				$this->Feedback->deleteAll($condition, false);
				$return['success']   = 1;
				$return['condition'] = 'delete';
				$return['id']		 = $id;
				
			} else {
				$return['success'] = 0;
			}
			
		} elseif($action == 'accept') {
			$ally = array('id' => $id, 'status' => 1, 'ally_notification' => 'Accepted');
			
			if($this->Ally->save($ally)) {
				$return['success'] 	 = 1;
				$return['condition'] = 'accept';
				$return['id']		 = $id;
				
			} else {
				$return['success'] = 0;
			}
			
		} elseif($action == 'block') {
			$ally = array('id' => $id, 'status' => -1, 'ally_notification' => '', 'feedback_notification' => '', 'blocked_reason' => $this->request->data['Ally']['blocked_reason']);
			if(!empty($this->request->data['Ally']['bother_how'])){
				$ally['bother_how'] = $this->request->data['Ally']['bother_how'];
			}
			if($this->Ally->save($ally)) {
			   return $this->redirect($this->referer());
			// 	$return['success'] 	 = 1;
			// 	$return['condition'] = 'block';
			// 	$return['id']		 = $id;
			
			// } else {
			//	$return['success'] = 0;
			}
		}
		
		//return(json_encode($return));
	}

	public function accept_ally($span) {
		
		$update = array('status' => 1, 'ally_notification' => "'Accepted'", 'span' => null);
		$conditions = array('span' => $span);
		
		if($this->Ally->updateAll($update, $conditions)) {
			$this->Session->setFlash('Ally request accepted');
		} else {
			$this->Session->setFlash('Ally request could not be accepted');
		}
		
		$this->redirect(array('controller' => 'users', 'action' => 'profile'));
	}
	
	/*2014-10-29, Badri
	 * Allies Notification
	 * 'ally_notification' : 0 => no notification
	 * 					   : 1 => notification for user
	 * 					   : 2 => notification for ally	
	 */
	
	public function request($term = null, $search = 'id') {
		
		if($this->request->is('post')) {
			$data = $this->request->data;
			$data['Ally']['user_id']			= $this->Session->read('ActiveGame.user_id');
			$data['Ally']['status'] 			= 0;
			$data['Ally']['ally_notification'] 	= 'Requested';
			$return['success'] = 0;
			
			$options['contain'] = false;
			$options['conditions'] = array('user_id' => $data['Ally']['user_id'], 'ally_email' => $data['Ally']['ally_email']);
			$exists = $this->Ally->find('first', $options);
			
			if(empty($exists)) {
				$this->Ally->create();
				$data['Ally']['span'] = $data['Ally']['user_id'] . $data['Ally']['user_game_status_id'] . $data['Ally']['ally_email'];
				$data['Ally']['span'] = Security::hash($data['Ally']['span']);
				
				if($this->Ally->save($data['Ally'])) {
					$this->request->data['Ally']['id']= $this->Ally->id;
					$template = ($data['Ally']['ally'] != '')? 'ally_request': 'ally_new_request';
					$options = array(
						'subject' 	=> 'You\'re a Human Catalyst Ally!',
						'template' 	=> $template,
						'to'		=>  $data['Ally']['ally_email']);
				
					$data['name'] 	 = $this->Auth->User('name');
					$data['roadmap'] = $this->Session->read('ActiveGame.roadmap');
					$data['span'] 	 = $data['Ally']['span'];
					
					$this->_sendEmail($options, $data);
					$this->Session->setFlash('Congratulations! Ally request sent.');
					$return['success'] = 1;
				}
			}
			
			if(isset($this->request->query['challenge']) && isset($this->request->query['st'])) {
				$this->redirect(array(
						'controller' => 'challenges', 'action' => 'set_challenge_user',
						'add', $this->request->query['challenge'], $this->request->data['Ally']['id'], $this->request->data['Ally']['ally'],
						'?' => array('st' => $this->request->query['st'])));
				
			} else {
				$this->autoRender = false;
				return json_encode($return);
			}
		} else {
			if(!empty($term)) {
				$options['contain'] 	= false;
				$options['conditions']	= array($search => $term);
				$this->request->data = $this->Ally->User->find('first', $options);
				if(empty($this->request->data)) {
					$this->request->data['Ally']['ally_email'] = $this->request->data['Ally']['ally_name'] = $term;
				} else {
					$this->request->data['Ally']['ally'] 		= $this->request->data['User']['id'];
					$this->request->data['Ally']['ally_email'] 	= $this->request->data['User']['email'];
					$this->request->data['Ally']['ally_name'] 	= $this->request->data['User']['name'];
				}
			}
		}
		
		$options['fields'] 		= array('id', 'roadmap');
		$options['conditions'] 	= array('user_id' => $this->Session->read('ActiveGame.user_id'));
		$user_game_statuses = $this->Ally->UserGameStatus->find('list', $options);
		$this->set('user_game_status', $user_game_statuses);
	}

	public function invite() {
		$email = $this->request->data['email'];
		if(!empty($email)) {
			$user_exists = $this->Ally->User->field('email',array('User.email'=> $email));
			if($user_exists == false){
				$ally = $this->Ally->User->find('first', array(
						'contain'	 => false,
						'conditions' => array('User.email' => $email)));
					
				if(empty($ally)) {
					$ally['User']['id'] = '';
					$ally['User']['name'] = '';
					$ally['User']['city'] = '';
					$ally['User']['email'] = $email;
				}
				$this->set('ally', $ally);
				$this->render('request');
			} else {
				$this->Session->setFlash('This User has already registered with ' . $this->Session->read('Company.name') . '. Please Search again.');
				$this->redirect(array('controller' => 'allies', 'action' => 'allies'));
			}
		}
	}

	public function ally_detail($id) {
		return $this->Ally->User->find('first', array(
												'contain'	 => false,
												'conditions' => array('User.id' => $id)));
	}
	public function block($id){
		$this->set('id', $id);
	}
	public function notification($field, $id) {
		$this->autoRender = false;
		$this->Ally->id = $id;
		$this->Ally->saveField($field, null);
	}
	
	public function notify_ally() {
		$this->autoRender = false;
		
		$summary_items 	= $this->requestAction(array('controller' => 'games', 'action' => 'summary', 'summary', 181));
		$data['answers'] = $summary_items;
		
		$allies = $this->Ally->find('all', array(
				'contain' => array('MyAlly'),
				'conditions' => array(
						'Ally.user_id' => $this->Session->read('ActiveGame.user_id'), 
						'Ally.user_game_status_id' => $this->Session->read('ActiveGame.id'))));
		
		foreach($allies as $ally) {
			$data['Ally'] = $ally;
			$options = array(
					'subject' 	=> 'Human Catalyst 100 : ' . $this->Auth->User('name') . ' wants you to help',
					'template' 	=> 'notify_ally',
					'from'		=> $this->Auth->User('email'),
					'to'		=> $ally['MyAlly']['email']
			);
			$this->_sendEmail($options, $data);
		}
		$return['success'] = 1;
		return json_encode($return);
	}
}
?>