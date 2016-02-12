<?php
App::uses('AppController', 'Controller');
App::import('Vendor', 'Uploader.Uploader');
/**
 * Users Controller
 *
 * @property User $User
*/
class UsersController extends AppController {
	var $name = 'Users';

	/**
	 * Helpers
	 *
	 * @var array
	 */

	/**
	 * index method
	 *
	 * @return void
	 */
	public $paginate = array('limit' => 25, 'contain' => false);
	
	function beforeFilter(){
		parent::beforeFilter();
		$this->Auth->allowedActions = array('forgetpassword', 'login', 'register', 'logout', 'verify', 'master_login', 'manualLogin');
		$this->Uploader = new Uploader();
		$this->Uploader->setup(array('tempDir' => TMP));
		/*
		$this->Uploader->addMimeType('image', 'gif', 'image/gif');
		$this->Uploader->addMimeType('image', 'jpg', 'image/jpeg');
		$this->Uploader->addMimeType('image', 'jpe', 'image/jpeg');
		$this->Uploader->addMimeType('image', 'jpeg', 'image/jpeg');
		$this->Uploader->addMimeType('image', 'png', array('image/png', 'image/x-png'));
		$this->Uploader->addMimeType('image', 'PNG', array('image/png', 'image/x-png'));
		*/
	}
	
	function index() {
		$this->autoRender = false;
		if ($this->Auth->user()) {
			$this->redirect(array('controller' => 'users', 'action' => 'profile'));
		} else {
			$this->redirect(array('controller' => 'pages'));
		}
	}

	public function register($admin = false){
		
		if ($this->Auth->user() && !$admin) {
			$this->redirect(array('controller' => 'users', 'action' => 'profile'));
			
		} else {
			if(!empty($this->request->data)){
				$this->request->data['User']['role_id'] = 2;
				if(!isset($this->request->data['User']['verified'])) {
					$this->request->data['User']['verified'] = 0;
				}
				$this->request->data['User']['login_ip'] = $this->_getRealIpAddr();
				if($admin) {
					$tmp_password = $this->request->data['User']['confirmpassword'] = $this->request->data['User']['password'];
				} else {
					$tmp_password = $this->request->data['User']['password'] = $this->request->data['User']['confirmpassword'] = substr(uniqid(mt_rand(), true), 0, 9);
				}

				$this->request->data['UserGameStatus'][0]['level'] 	= 0;
				$this->request->data['UserGameStatus'][0]['game'] 	= 0;
				$this->request->data['UserGameStatus'][0]['points'] = 0;
				$this->request->data['UserGameStatus'][0]['active'] = 1;
				$this->request->data['UserGameStatus'][0]['roadmap'] = '';
				$this->request->data['User']['hash'] = Security::hash($this->request->data['User']['email']);
				
				if($this->User->saveAll($this->request->data)){
					$this->User->Ally->updateAll(array('Ally.ally' => $this->User->id), 
												 array('Ally.ally_email' => $this->request->data['User']['email']));

					$this->request->data['User']['password'] = $tmp_password;
					$this->Session->setFlash('Your request has been submitted. Please wait for the request to be approved.', 'default', 
											 array('class' => 'flashError margin-bottom-20'));
					
					if($admin) {
						return 1;
					} else {
						$options = array(
								'subject' 	=> 'Kissaah: Welcome To Kissaah ',
								'template' 	=> 'users_register',
								'to'		=>  $this->request->data['User']['email']
						);
						$this->_sendEmail($options, $this->request->data);
							
						$this->redirect(array('action' => 'login'));
					}
					
				} else{
					//debug($this->User->validationErrors);
					$this->Session->setFlash('Account Creation Failed!!!', 'default', array('class' => 'flashError margin-bottom-20'));
					$this->request->data['User']['password'] = '';
					$this->request->data['User']['confirmpassword'] = '';
					if($admin) {
						return 0;
					}
				}
			}
		}
		$this->render('/Pages/home');
	}

	public function verify(){
		if($this->Auth->login()) {
			$this->redirect(array('controller' => 'users', 'action' => 'afterLogin'));
		} else {
			if( isset($this->request['pass'][0]) && !empty($this->request['pass'][0]) &&
				isset($this->request['pass'][1]) && !empty($this->request['pass'][1])) {
				
					if(Security::hash($this->request['pass'][0]) == $this->request['pass'][1]){
					$user_id = $this->User->field('id', array('User.email' => $this->request->pass[0]));
					if($user_id){
						//check ally_email in allies table if exists then update ally with the newly userId
						$ally_id = $this->User->Ally->find('list', array(
												'conditions' => array('ally_email' => $this->request->pass[0]),
												'fields'	 => array('id')));
						if(!empty($ally_id)){
							foreach($ally_id as $allyID){
								$allies['Ally']['id'] 	= $allyID;
								$allies['Ally']['ally'] = $user_id;
								$this->User->Ally->save($allies);
							}
						}
						
						$data['User']['id'] = $user_id ;
						$data['User']['verified'] = 1;
						if($this->User->save($data)){
							$this->Session->setFlash('Your email is validated. Thank you for signing up with Kissaah.', 'default',
									array('class' => 'flashSuccess margin-bottom-20'));
						}
					} else {
						/* User does not exist */
						$this->Session->setFlash('You have not yet registered with kissaah. Please register to continue.', 'default',
								array('class' => 'flashError margin-bottom-20'));
					}
				} else {
					//not validate with email
					$this->Session->setFlash('Could not validate. Please try again.', 'default',
							array('class' => 'flashError margin-bottom-20'));
				}
			} else {
				//not set email and hash key
				$this->Session->setFlash('Could not validate. Please try again.', 'default',
						array('class' => 'flashError margin-bottom-20'));
			}
		}
		$this->redirect(array('controller' => 'pages', 'action' => 'display'));
	}
	
	public function facebook_login() {}

	public function login() {
		if($this->Auth->user('id')){
			$this->redirect(array('controller' => 'games'));
		}
		$isLogin = false;
		if(!empty($this->request->data)) {
			$isLogin = $this->Auth->login();
			if($isLogin) {
				if(isset($this->request->data['User']['remember_me']) && $this->request->data['User']['remember_me']) {
					$this->Cookie->write('Auth.User', $this->request->data['User'], true, '2 weeks');
				}
				$this->redirect(array('controller' => 'users', 'action' => 'afterLogin'));
			} else {
				$user = $this->User->find('first', array(
								'contain' 	 => false,
								'conditions' => array('User.email' => $this->request->data['User']['email'])));
				if(!empty($user) && $user['User']['verified'] == 0){
					$data['User']['email'] 		= $this->request->data['User']['email'];
					$data['User']['hash'] 		= Security::hash($this->request->data['User']['email']);
					$options = array(
						'subject' 	=> 'Kissaah: Email Verification',
						'template' 	=> 'users_register',
						'to'		=> $this->request->data['User']['email']
					);
					if($this->_sendEmail($options, $data)){
						$this->Session->setFlash(__('Verification email has been sent. Please check your email and verify.', true), 
												 'default', array('class' => 'flashError margin-bottom-20'));
						$this->redirect($this->referer());
					}
				}
				$loginAttempts = $this->Session->read('loginAttempts');
				if(isset($loginAttempts)){
					$this->Session->write('loginAttempts',$loginAttempts + 1);
				}else{
					$this->Session->write('loginAttempts',1);
				}
				$this->set('loginAttempts',$loginAttempts);
				$this->Session->setFlash(__('Username or Password is incorrect. Please try again.', true), 'default', 
										array('class' => 'flashError margin-bottom-20'));
			}
		}
		$this->render('/Pages/home');
	}
	
	public function manualLogin() {
		if(isset($this->request->query['e']) && isset($this->request->query['p'])) {
			$options['contain'] = false;
			$options['fields'] = array('id', 'name', 'email');
			$options['conditions'] = array('email' => $this->request->query['e'], 'password' => $this->request->query['p']);
			$this->request->data = $this->User->find('first', $options);
			if(empty($this->request->data)) {
				$this->Session->setFlash('The link has expired. Please retry to reset your password.', 'default', array('class' => 'flashError margin-bottom-20'));
				$this->redirect(array('action' => 'forgetpassword'));
				
			}
			
		} elseif(!empty($this->request->data)) {
			$this->request->data['User']['login_ip'] = $this->_getRealIpAddr();
			$tmp_password = $this->request->data['User']['password'];
			if($this->User->save($this->request->data)) {
				$this->Session->write('start-tour', 1);
				$this->login();
			
			} else{
				$this->Session->setFlash('Profile Update Failed. Please try again.', 'default', array('class' => 'flashError margin-bottom-20'));
				$this->request->data['User']['password'] = '';
				$this->request->data['User']['confirmpassword'] = '';
			}
			
		} else {
			$this->redirect(array('action' => 'login'));
			
		}
		$this->render('/Pages/home');
		
	}
	public function afterLogin() {
		$this->User->id = $this->Auth->user('id');
		$this->request->data['User']['last_login'] = date('Y-m-d h:i:s');
		$this->request->data['User']['login_ip'] = $this->_getRealIpAddr();
		$this->User->save($this->request->data);
		
		$isFacebook = $this->Session->read('Facebook');
		if(is_null($isFacebook)) {
			$this->Session->write('Facebook', 0);
		}
		
		$active_game = $this->User->UserGameStatus->find('first', array(
							'contain' 	 => false,
							'conditions' => array('UserGameStatus.user_id' => $this->Auth->user('id'),
												  'UserGameStatus.active' => 1)));
		if(empty($active_game)) {
			$active_game['UserGameStatus']['user_id'] = $this->Auth->user('id');
			$active_game['UserGameStatus']['roadmap'] = '';
			$active_game['UserGameStatus']['level']   = 0;
			$active_game['UserGameStatus']['game'] 	  = 0;
			$active_game['UserGameStatus']['points']  = 0;
			$active_game['UserGameStatus']['active']  = 1;
			if($this->User->UserGameStatus->save($active_game)) {
				$active_game['UserGameStatus']['id']  = $this->User->UserGameStatus->getLastInsertID();
			}
		}
		$this->Session->write('ActiveGame', $active_game['UserGameStatus']);
		$this->Session->write('ActiveGame.user_email', $this->Auth->user('email'));
		$profile = $this->User->Game->find('first', array(
							'contain' 	 => false,
							'conditions' => array('Game.configure_id' => 36)));
		if(empty($profile)) {
			$profile['Game']['configure_id'] = 36;
			$this->Session->write('Profile', $profile);
		} else {
			$this->Session->write('Profile', $profile);
		}
		
		if ($this->request->isMobile() || array_shift(explode('.', $_SERVER['HTTP_HOST'])) == 'm') {
			//$this->redirect(array('controller' => 'users', 'action' => 'additional_user_info'));
			$this->redirect(array('controller' => 'games'));
		} else {
			$this->redirect(array('controller' => 'games'));
		}
	}

	public function forgetpassword(){
		if ($this->Auth->user()) {
			$this->redirect(array('action' => 'profile'));
		} else {
			if(!empty($this->request->data)){
				$resetUser = $this->User->find('first', array(
										'contain' 		=> false,
										'fields' 		=> array('User.id', 'User.email'),
										'conditions' 	=> array('User.email' => $this->request->data['User']['email'])));
				
				if (!empty($resetUser)) {
					$this->User->id = $resetUser['User']['id'];
					$resetPassword = substr(uniqid(mt_rand(), true), 0, 9);
					if ($this->User->saveField('password', $resetPassword)) {
						$resetUser['User']['password'] = $resetPassword;
						$options = array(
								'subject' 	=> 'Kissaah: Reset Password',
								'template' 	=> 'resetpassword',
								'to'		=> $resetUser[$this->modelClass]['email']
						);
						$this->_sendEmail($options,$resetUser);
						
						$this->Session->setFlash('Your new password has been sent to your email account.', 'default', 
												 array('class' => 'flashSuccess margin-bottom-20'));
						$this->redirect(array('action' => 'login'));
					}
				} else {
					$this->Session->setFlash('Your email does not exist!', 'default', array('class' => 'flashError margin-bottom-20'));
				}
			}
		}
		$this->render('/Pages/home');
	}

	public function logout() {
		if($this->Session->check('ActiveGame_admin')){
			$this->Session->write('ActiveGame', $this->Session->read('ActiveGame_admin'));
			$this->Session->write('Profile', $this->Session->read('Profile_admin'));
			$this->Session->delete('ActiveGame_admin');
			$this->redirect('/');
		} else {
			//$this->Connect->FB->destroysession();
			unset($_SESSION['fb_1460023040899261_code']);
			unset($_SESSION['fb_1460023040899261_access_token']);
			unset($_SESSION['fb_1460023040899261_user_id']);
			unset($_SESSION['FB']);
			$this->Cookie->destroy();
			$this->Auth->logout();
			$this->Session->destroy();
			$this->redirect(array('controller' => 'pages', 'admin' => false));
		}
	}
	
	public function profile(){
		$userdetail = $this->User->find('first', array('conditions' => array('User.id' => $this->Session->read('ActiveGame.user_id'))));
		$this->set('userdetail', $userdetail);
	}

	#Start 3562
	public function createcollage(){
		$this->autoRender = false;
		if($this->request->isAjax()){
			if(($this->request->data['is_collage']==1) ||($this->request->data['is_collage']==0)){
				$this->User->id = $this->Session->read('ActiveGame.user_id');
				$this->request->data['User']['collage_status'] = $this->request->data['is_collage'];
				$return['imagetype'] = $this->request->data['is_collage'];
				if($this->User->save($this->request->data)){
					$return['success'] = 1;
				}else{
					$return['success'] = 0;
				}
				return json_encode($return);
			}else{
				$return['error'] = 'Invalid Data';
				return json_encode($return);
			}
		}
	}

	public function createAro() {
		$this->autoRender = false;
		$users = $this->User->find('all', array('contain' => false));
		foreach($users as $user) {
			$parent = $this->Acl->Aro->field('id', array('foreign_key' => 2, 'model' => 'Role'));
			$has_aro = $this->Acl->Aro->field('id', array('foreign_key' => $user['User']['id'], 'model' => 'User'));
			if(!$has_aro) {
				$this->Acl->Aro->id = null;
				debug($this->Acl->Aro->save(array('parent_id' => $parent, 'foreign_key' => $user['User']['id'], 'model' => 'User', 'alias' => 'U:'.$user['User']['id'])));
			} else {
				$this->Acl->Aro->id = null;
				debug($this->Acl->Aro->save(array('id' => $has_aro, 'parent_id' => $parent, 'foreign_key' => $user['User']['id'], 'model' => 'User', 'alias' => 'U:'.$user['User']['id'])));
			}
		}
	}

	public function edit($action = ''){
		$this->layout = null;
		if(($this->request->is('put') || $this->request->is('post')) && $this->request->is('ajax')) {
			$is_save = true;
			if(!empty($this->request->data['User']['dob'])) {
				$this->request->data['User']['dob'] = DateTime::createFromFormat('d/m/Y', $this->request->data['User']['dob'])->format('Y-m-d');
			}
			$this->request->data['User']['id'] = $this->Session->read('ActiveGame.user_id');
			if(isset($this->request->data['User']['current_password'])) {
				$current_password = $this->Auth->password($this->request->data['User']['current_password']);
				$password = $this->User->field('password', array('User.id' => $this->Session->read('ActiveGame.user_id')));
				
				if($current_password != $password) {
					$this->User->validationErrors['current_password'] = 'Current Password did not match';
					$is_save = false;
					$action = 'password';
				}
			}
			if($is_save && $this->User->save($this->request->data)) {
				$this->autoRender = false;
				$user = $this->User->find('first', array(
										'contain' 	 => false,
										'conditions' => array('User.id' => $this->Session->read('ActiveGame.user_id'))));
				$this->Session->write('Auth.User.name', $user['User']['name']);
				$this->Session->write('Auth.User.gender', $user['User']['gender']);
				$this->Session->write('Auth.User.collage_status', $user['User']['collage_status']);
				
				$return['Success'] = 1;
				$return['ScreenName'] = isset($user['User']['name']) ? $user['User']['name'] : '';
				$return['Email'] = isset($user['User']['email']) ? $user['User']['email'] : '';
				return json_encode($return);
			}
		} else {
			$this->request->data = $this->User->find('first', array(
										'contain' 	 => false,
										'conditions' => array('User.id' => $this->Session->read('ActiveGame.user_id'))));
			$this->request->data['User']['password'] = '';
			if(!is_null($this->request->data['User']['dob'])) {
				$this->request->data['User']['dob'] = DateTime::createFromFormat('Y-m-d', $this->request->data['User']['dob'])->format('d/m/Y');
			}
		}
		$this->set('action', $action);
		
		/* $server_http = explode('.',$_SERVER['HTTP_HOST']);
		$server = array_shift($server_http)  ;
		if ($this->request->isMobile() || $server  == 'm') {
			$this->layout = 'other';
			if((isset($data['gender']))&&(isset($data['city']))&&(isset($data['dob']))){
				$this->redirect(array('controller' => 'games', 'action' => 'index'));
			}
		} */
	}
	
	public function self_notes($action = null, $type = null) {
		$this->loadModel('SelfNote');
		
		if($this->request->is('put') || $this->request->is('post')) {
			$this->autoRender = false;
			
			$this->request->data['SelfNote']['user_game_status_id'] = $this->Session->read('ActiveGame.id') ;
			if($this->SelfNote->save($this->request->data['SelfNote'])) {
				$result['success'] = 1;
			} else {
				$result['success'] = 0;
			}
			return json_encode($result);
			
		} elseif($action == 'new') {
			$this->request->data['SelfNote']['text'] = '';
			$this->request->data['SelfNote']['complete_by'] = '';
			
			$this->request->data['SelfNote']['type'] = $type;
			$this->request->data['SelfNote']['user_game_status_id'] = $this->Session->read('ActiveGame.id');
			$this->request->data['SelfNote']['user_id'] = $this->Session->read('ActiveGame.user_id');
			$self_note = $this->SelfNote->save($this->request->data['SelfNote']);
			
			$this->set('self_note', $self_note);
				
			$this->render('self_notes_new');
			
		} elseif($action == 'delete') {
			
		} else {
			$options['contain'] = false;
			$options['order'] 	= array('type ASC', 'id ASC');
			$this->request->data = $this->SelfNote->find('all', $options);
			$self_notes = $this->SelfNote->find('all', $options);
			$this->set('self_notes', $self_notes);
		}
	}
	
	//This Function mails selfNotes and Reminder to the User
	public function self_note_email_me(){
		$this->autoRender = false;
		$this->loadModel('SelfNote');
		$my_note = $this->request->data = $this->SelfNote->find('all', array('order' => array('SelfNote.id ASC')));
		$i = 0;
		foreach($my_note as $note){
			$data[$note['SelfNote']['type']][$i]['text'] = $note['SelfNote']['text'];
			$data[$note['SelfNote']['type']][$i++]['complete_by'] = $note['SelfNote']['complete_by'];
		}
		
		$options = array(
				'subject' 	=> 'Kissaah : Self Notes',
				'template' 	=> 'self_note',
				'to'		=> $this->Auth->User('email'),
				'setFlash'	=> false
		);
		if($this->_sendEmail($options,$data)){
			$return['success'] = 1;
		} else {
			$return['success'] = 0;
		}
		return(json_encode($return));
	}
	
	//This is to export SelfNotes to word
	public function export_to_word(){
		$this->autorender = false;
		$this->loadModel('SelfNote');
		$my_note = $this->request->data = $this->SelfNote->find('all', array('order' => array('SelfNote.id ASC')));
		$i = 0;
		foreach($my_note as $note){
			$data[$note['SelfNote']['type']][$i]['text'] = $note['SelfNote']['text'];
			$data[$note['SelfNote']['type']][$i++]['complete_by'] = $note['SelfNote']['complete_by'];
		}
		$this->set(compact('data'));
	}

	//For RoadMaps
	public function roadmaps(){
		$roadmaps = $this->User->UserGameStatus->find('all', array(
						'contain' 	 => false,
						'conditions' => array('UserGameStatus.user_id' => $this->Session->read('ActiveGame.user_id'))));
		$this->set(compact('roadmaps'));
	}
	
	public function roadmap_save(){
		$data = $this->request->data;
		$data['user_id'] = $this->Session->read('ActiveGame.user_id');
		$id = 0;
		if(!isset($data['id'])) {
			$data['active']  = 0;
			$data['level']   = 0;
		} else {
			$id = $data['id'];
		}
		if($data['roadmap'] != null) {
			if($this->User->UserGameStatus->save($data)) {
				$id = $this->User->UserGameStatus->id;
				$roadmap = $this->User->UserGameStatus->find('first', array(
								'contain' 	 => false,
								'conditions' => array('UserGameStatus.id' => $id)));
				if($roadmap['UserGameStatus']['active']) {
					$this->Session->write('ActiveGame.roadmap', $roadmap['UserGameStatus']['roadmap']);
				}
				$this->set(compact('roadmap'));
			}
		}
	}
	
	
	public function roadmap_delete($user_game_status_id){
		$this->_reset_roadmap($user_game_status_id);
		$this->User->UserGameStatus->delete(array('UserGameStatus.id' => $user_game_status_id), true);
		$this->redirect(array('controller' => 'games', 'action' => 'index'));
	}
	
	//To toggle the active RoadMap
	public function roadmap_edit_active($user_game_status_id){
		if(isset($user_game_status_id)){
			$ActiveRoadMaps = $this->User->UserGameStatus->find('all',array(
					'conditions' => array(
							'UserGameStatus.user_id' => $this->Session->read('ActiveGame.user_id'),
							'UserGameStatus.active'	 => 1	),
					'contain' => false));
			
			foreach($ActiveRoadMaps as $ActiveRoadMap){
				$this->User->UserGameStatus->create();
				$this->User->UserGameStatus->id = $ActiveRoadMap['UserGameStatus']['id'];
				$this->User->UserGameStatus->saveField('active', 0);
			}
			
			$this->User->UserGameStatus->id = $user_game_status_id;
			$this->User->UserGameStatus->saveField('active', 1);
				
			$active_game = $this->User->UserGameStatus->find('first', array(
					'contain' 	 => false,
					'conditions' => array('UserGameStatus.user_id' => $this->Session->read('ActiveGame.user_id'),
							'UserGameStatus.active' => 1)));
			if(empty($active_game)) {
				$active_game['UserGameStatus']['user_id'] = $this->Session->read('ActiveGame.user_id');
				$active_game['UserGameStatus']['level']   = 0;
				$active_game['UserGameStatus']['game'] 	  = 0;
				$active_game['UserGameStatus']['points']  = 0;
				$active_game['UserGameStatus']['active']  = 1;
				if($this->User->UserGameStatus->save($active_game)) {
					$active_game['UserGameStatus']['id']  = $this->User->UserGameStatus->getLastInsertID();
				}
			}
			$this->Session->write('ActiveGame', $active_game['UserGameStatus']);
		}
		$this->redirect(array('controller' => 'games', 'action' => 'index'));
	}
	
	//For Support Pages
	public function support(){
		$user_email = $this->Auth->User('email');
		$this->set(compact('user_email'));
	}
	
	//To send Support Mails
	public function send_to_support(){
		$this->autoRender =false;
		$ticket_no = mt_rand(10000000, 99999999);
		$support_data = $this->request->data['User'];
		$data['user']		= $this->Auth->User('email');
		$data['department']	= $support_data['department'];
		$data['priority']	= $support_data['priority'];
		$data['subject']	= $support_data['subject'];
		$data['issue']		= $support_data['issue'];
		$data['ticket_no']  = $ticket_no;
		$images=array();
		
		if(isset($support_data['image1']) && $support_data['image1'] != ''){
			$images[]		= $support_data['image1'];
		}
		if(isset($support_data['image2']) && $support_data['image2'] != ''){
			$images[]		= $support_data['image2'];
		}
		if(isset($support_data['image3']) && $support_data['image3'] != ''){
			$images[]		= $support_data['image3'];
		}
		if(count($images) > 0){
			foreach($images as $image){
			if(Uploader::checkMimeType(strtolower(Uploader::ext($image['name'])), $image['type']) != 'image'){
				$allowedExts = Configure::read('Uploader.mimeTypes');
				$allowedImageExts = $allowedExts['image'];
				$allowed = '';
				foreach($allowedImageExts as $a){
					$allowed = $allowed . ',' . $a;
				}
				$return['flash'] = 'Files of type :' . $image['type'] . ', can not be uploaded ' . ' Allowed Image Types :' . $allowed;
			} else {
					$this->Uploader->uploadDir = '/files/supportimages';
					$filename = md5(date('Ymdhis') . rand());
					$data['attachment'][] = WWW_ROOT . DS . 'files' . DS . 'supportimages' . DS . $filename . '.' . Uploader::ext($image['name']);
					$uploadimage = $this->Uploader->upload($image, array(
							'overwrite' => false,
							'name' 		=> $filename,
							'multiple' 	=> false));
						
					if(!($uploadimage)){
						$return['success'] = 0;
					}
				}
			}
		}
		$to = array('bob@himalayantechies.com', 'sagrawal@himalayantechies.com', 
					'support@kissaah.com', 'bguragain@himalayantechies.com', 'vic@kissaah.com');
		if(count($images) > 0){
			$options = array(
					'subject' 	=> $data['subject'],
					'template' 	=> 'support',
					'to'		=> $to,
					'attachment'=> true
			);
		} else {
			$options = array(
					'subject' 	=> $data['subject'],
					'template' 	=> 'support',
					'to'		=> $to
			);
		}
		
		if($this->_sendEmail($options, $data)){
			$return['success'] = 1;
			$return['flash'] = 'Your ticket number: #' . $ticket_no;
		} else {
			$return['success'] = 0;
			$return['flash'] = "Something's wrong . Please try again ";
		}
		return(json_encode($return));
	}

	function edit_notification_preferences(){
		$this->autoRender = false;
		$return['success'] = 0;
		if(isset($this->request->data['chk_notify']) && !empty($this->request->data['chk_notify']) &&
			isset($this->request->data['id']) && isset($this->request->data['id'])){
				$this->User->id = $this->Session->read('ActiveGame.user_id');
				if($this->User->saveField($this->request->data['id'] , $this->request->data['chk_notify'])){
					$return['success'] = 1;
				}
		}
		return(json_encode($return));
	}
	
	//To Deactivate User Accounts
	public function deactivate_account(){
		$delete_user = $this->admin_delete($this->Session->read('ActiveGame.user_id'));
		$this->redirect(array('controller' => 'games'));
	}
	
	/******** Admin Function ********/

	function admin_index(){
		$this->set('title_for_layout', 'Dashboard');
		$totalUsers = $this->User->find('count');
		$this->loadModel('Configuration');
		$totalImagesUploaded = $this->Configuration->find('count',array(
																'conditions'=>array(
																		'Configuration.status'=>1,
																		'Configuration.title'=>'Image Activity')));
		$Img_Answers = $this->User->Game->find('all',array(
		 										'conditions'=>array('Configuration.type'=>1),
		 										'order'=>'Game.id DESC',
		 										'limit'=>10));
		
		$i=1;
		 foreach($Img_Answers as $img){
		 	$Answers[$i]['type']='image';
		 	$Answers[$i]['user_id']=($img['User']['id']);
		 	$Answers[$i]['user_email']=($img['User']['email']);
		 	$Answers[$i]['GameConfigure_title']=($img['Configuration']['title']);
		 	$i++;
		 }
		 shuffle($Answers);
		 
		$Users=$this->User->find('all',array(
											'conditions'=>array(),
											'order'=>'User.id DESC',
											'limit'=>20));
		$i = 1;
		foreach($Users as $User){
			$UserList[$i]['name']	 = $User['User']['name'];
			$UserList[$i]['email']	 = $User['User']['email'];
			$UserList[$i]['created'] = $User['User']['created'];
			$i++;
		}
		//debug($UserList);		 
		$this->set(compact('totalUsers','totalImagesUploaded','totalComments','Answers','UserList'));
	}

	function admin_view() {
		$sString = '';
		$this->set('title_for_layout', 'User List');
		$conditions = array();
		
		if($this->request->is('ajax')) {
			if(isset($this->request->data['search'])) $sString = $this->request->data['search'];
			elseif(isset($this->request->params['named']['sString'])) $sString = $this->request->params['named']['sString'];
		}
		
		if(isset($sString) && !empty($sString)){
			$conditions =  array('OR' => array(
										'User.name LIKE' => '%' . $sString . '%',
										'User.city LIKE' => '%' . $sString . '%',
										'User.country LIKE' => '%' . $sString . '%',
										'User.company LIKE' => '%' . $sString . '%',
										'User.email LIKE' => '%' . $sString . '%'));
		}
		$this->paginate = array('conditions'=> $conditions,
								'contain'	=> false,
								'limit'		=> 50);
		
		$userlist =  $this->paginate();
		
		$this->Session->write('Game.query_all', 1);
		foreach($userlist as $key => $user) {
			$userlist[$key]['User']['UserGameStatus'] = $this->User->UserGameStatus->find('count', array(
								'contain' 	 => false,
								'conditions' => array('UserGameStatus.user_id' => $user['User']['id'])));
			$userlist[$key]['User']['Game'] = $this->User->Game->find('count', array(
								'contain' 	 => false,
								'conditions' => array('Game.user_id' => $user['User']['id'])));
			$userlist[$key]['User']['Files'] = $this->User->Game->find('count', array(
								'contain' 	 => array('Configuration'),
								'conditions' => array('Game.user_id' => $user['User']['id'],
													  'Configuration.type' => 1)));
		
		}
		$this->Session->delete('Game.query_all');
		
		$this->set('sString', $sString);
		$this->set('userlist', $userlist);
	}

	function admin_detail($id = null){
		if(!empty($this->request->data)){
			if($this->User->save($this->request->data)){
				$this->Session->setFlash('User updated Successfully');
				$this->redirect(array('controller' => 'users', 'action' => 'view'));
			}else{
				$this->Session->setFlash('User could not be updated');
			}
		}
		
		$this->request->data = $this->User->findById($id);
		if(empty($this->request->data)){
			$this->Session->setFlash("Invalid User");
			$this->redirect($this->referer());
		}
		$roles = $this->User->Role->find('list', array('fields' => array('id', 'name')));
		$this->set('roles', $roles);
	}
	
	//2014-10-21, Badri, This function allows  admin to login as any user
	public function admin_login($id = null) {
		$this->Session->write('Profile_admin',$this->Session->read('Profile'));
		$this->Session->write('ActiveGame_admin',$this->Session->read('ActiveGame'));
		$user_activeGame = $this->User->UserGameStatus->find('all',array(
				'conditions'	=>array ('UserGameStatus.user_id'   => $id,
										 'UserGameStatus.active'	=> 1)));
		$user_profile = $this->User->Game->find('first', array(
				'contain' 	 => false,
				'fields'	 =>array('Game.id','Game.answer','Game.configure_id'),
				'conditions' => array('Game.configure_id' => 36)));
		$this->Session->write('ActiveGame',$user_activeGame[0]['UserGameStatus']);
		$this->Session->write('Profile',$user_profile);
		$this->redirect('/');
	}
	
	public function admin_logout() {
		$this->logout();
	}

	public function admin_delete($id = null){
		$this->User->id = $id;
			if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid User'));
		} else {
			$this->autoRender = false;
			$this->Session->write('Game.query_all', 1);
			$files = $this->User->Game->find('all', array(
								'contain'	 => array('Configuration'),
								'conditions' => array('Configuration.type' => 1, 'Game.user_id' => $id)));
			
			foreach($files as $filename){
				$filename = $filename['Game']['answer'];
				$path	  = WWW_ROOT . 'files' . DS . 'img' . DS;
				if (file_exists($path . 'large'  . DS . $filename)) {unlink($path . 'large'  . DS . $filename);}
				if (file_exists($path . 'medium' . DS . $filename)) {unlink($path . 'medium' . DS . $filename);}
				if (file_exists($path . 'small'  . DS . $filename)) {unlink($path . 'small'  . DS . $filename);}
			}
			
			$this->User->Ally->deleteAll(array('Ally.ally' => $id), true);
			$this->User->delete(array('User.id' => $id), true);
			
			$this->Session->setFlash('User has been deleted', 'default', array('class' => 'flashSuccess margin-bottom-20'));
			$this->Session->delete('Game.query_all');
				
			$this->redirect($this->referer());
		}
	}
	
	public function admin_add() {
		if ($this->request->is(array('post', 'put'))) {
			$success = $this->register(true);
			if($success == 1) {
				$this->redirect(array('controller' => 'users', 'action' => 'view'));
			}
		}
		
		$roles = $this->User->Role->find('list', array('fields' => array('id', 'name')));
		$this->set('roles', $roles);
	} 
	
	public function admin_verify($user_id) {
		if ($this->request->is(array('post', 'put'))) {
			$success = $this->register(true);
			if($success == 1) {
				$this->redirect(array('controller' => 'users', 'action' => 'view'));
			}
		}
		
	} 
	
	public function master_login() {
		if($this->Auth->user('id')){
			$this->redirect(array('controller' => 'games'));
		}
		if($this->Session->check('MasterLogin')) {
			$this->redirect(array('controller' => 'users', 'action' => 'login'));
		}
		$this->layout = 'ajax';
		if($this->request->is('post')) {
			$is_verified = $this->User->find('first', array(
					'contain'	 => false,
					'conditions' => array('User.email' => $this->request->data['User']['email'],
										  'User.password' => Security::hash($this->request->data['User']['password'], 'sha1', true))));
			if(!empty($is_verified)) {
				$this->Session->write('MasterLogin', true);
				$this->redirect(array('controller' => 'users', 'action' => 'login'));
			}
		}
		$this->render('/Pages/home', 'master_login');
	}
}