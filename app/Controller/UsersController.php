 <?php
App::uses('AppController', 'Controller');
App::import('Vendor', 'Uploader.Uploader');
Configure::load('linkedin');
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
		$this->Auth->allowedActions = array('oauth', 'forgetpassword', 'login', 'register', 'logout', 'verify', 'manualLogin', 'screen_size');
		$this->Uploader = new Uploader();
		$this->Uploader->setup(array('tempDir' => TMP));
               
		$this->Uploader->addMimeType('image', 'gif', 'image/gif');
		$this->Uploader->addMimeType('image', 'jpg', 'image/jpeg');
		$this->Uploader->addMimeType('image', 'jpe', 'image/jpeg');
		$this->Uploader->addMimeType('image', 'jpeg', 'image/jpeg');
		$this->Uploader->addMimeType('image', 'png', array('image/png', 'image/x-png'));
		$this->Uploader->addMimeType('image', 'PNG', array('image/png', 'image/x-png'));
	}
	
	public function screen_size() {
		if(isset($this->request->data['width']) && isset($this->request->data['height'])) {
			$this->Session->write('Screen.width', $this->request->data['width']);
			$this->Session->write('Screen.height', $this->request->data['height']);
			echo json_encode(array('outcome' => 'success'));
		} else {
			echo json_encode(array('outcome' => 'error', 'error' => 'Couldn\'t save dimension info'));
		}
		$this->autoRender = false;
	}
	
	public function index() {
		$this->autoRender = false;
		if ($this->Auth->user()) {
			$this->redirect(array('controller' => 'users', 'action' => 'profile'));
		} else {
			$this->redirect(array('controller' => 'pages'));
		}
	}

	public function register($admin = false){
		
		if ($this->Auth->user() && $admin === false) {
			$this->redirect(array('controller' => 'users', 'action' => 'profile'));
			
		} else {
			if(!empty($this->request->data)){
				if(!isset($this->request->data['User']['role_id']))  $this->request->data['User']['role_id']  = 2;
				if(!isset($this->request->data['User']['verified'])) $this->request->data['User']['verified'] = 0;
				if(!isset($this->request->data['User']['collage_status'])) $this->request->data['User']['collage_status'] = 1;
				
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
				if(strpos(Router::url('/', true), 'humancatalyst') !== false || strpos(Router::url('/', true), 'localhost') !== false) {
					$this->request->data['UserGameStatus'][0]['configuration_id'] = 192;
				} else {
					$this->request->data['UserGameStatus'][0]['configuration_id'] = 81;
				}
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
								'subject' 	=> 'Welcome to ' . $this->Session->read('Company.name'),
								'template' 	=> 'users_register',
								'to'		=> $this->request->data['User']['email']
						);
						$this->_sendEmail($options, $this->request->data);
							
						$options = array(
								'subject' 	=> $this->Session->read('Company.name') . ': New User Registration',
								'template' 	=> 'users_register_admin',
								'to'		=> array('bob@himalayantechies.com', 'support@kissaah.com', 'vic@kissaah.com', 'hello@humancatalyst.co')
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

	public function verify($email, $hash, $admin = 0) {
		$this->autoRender = false;
		if($this->Auth->login() && $admin == 0) {
			$this->redirect(array('controller' => 'users', 'action' => 'afterLogin'));
			
		} else {
				
			if(Security::hash($email) == $hash) {
				$options['contain'] = false;
				$options['conditions'] = array('User.email' => $email);
				$data = $this->User->find('first', $options);
				
				if(!empty($data)) {
					$this->User->Ally->updateAll(array('Ally.ally' => $data['User']['id']),
												 array('Ally.ally_email' => $email));
					$this->User->id = $data['User']['id'];
					$this->User->saveField('verified', 1);
					if($admin == 1) {
						$options = array(
								'subject' 	=> $this->Session->read('Company.name') . ': Your account is verified',
								'template' 	=> 'verified',
								'to'		=>  $email
						);
						$this->_sendEmail($options, $data);
					}
					
					$this->Session->setFlash('Your account is validated. Thank you for signing up with ' . $this->Session->read('Company.name'), 'default',
											 array('class' => 'flashSuccess margin-bottom-20'));
				} else {
					/* User does not exist */
					$this->Session->setFlash('You have not yet registered with ' . $this->Session->read('Company.name') . '. Please register to continue.', 'default',
							array('class' => 'flashError margin-bottom-20'));
				}
			} else {
				//not validate with email
				$this->Session->setFlash('Could not validate. Please try again.', 'default',
						array('class' => 'flashError margin-bottom-20'));
			}
		}
		if($admin == 0) {
			$this->redirect(array('controller' => 'pages', 'action' => 'display'));
		} else {
			$this->redirect(array('controller' => 'users', 'action' => 'view', 'admin' => true));
		}
		
	}
	
	public function login() {
		if($this->Auth->user('id')){
			$this->redirect(array('controller' => 'games'));
		}
		$isLogin = false;
		if(!empty($this->request->data)) {
			$isLogin = $this->Auth->login();
			if($isLogin) {
				if(isset($this->request->data['User']['remember_me']) && $this->request->data['User']['remember_me']) {
					//$this->Cookie->write('Auth.User', $this->request->data['User'], true, '2 weeks');
				}
				$this->Session->write('Narration', 0);
				$this->redirect(array('controller' => 'users', 'action' => 'afterLogin'));
			} else {
				$user = $this->User->find('first', array(
								'contain' 	 => false,
								'conditions' => array('User.email' => $this->request->data['User']['email'])));
				if(!empty($user) && $user['User']['verified'] == 0){
					$data['User']['email'] 		= $this->request->data['User']['email'];
					$data['User']['hash'] 		= Security::hash($this->request->data['User']['email']);
					$options = array(
						'subject' 	=> 'Welcome to ' . $this->Session->read('Company.name'),
						'template' 	=> 'users_register',
						'to'		=> $this->request->data['User']['email']
					);
					if($this->_sendEmail($options, $data)){
						$this->Session->setFlash(__('Your account has not been verified yet.', true), 
												 'default', array('class' => 'flashError margin-bottom-20'));
						$this->redirect($this->referer());
					}
				}
				$loginAttempts = $this->Session->read('loginAttempts');
				if(isset($loginAttempts)) {
					$this->Session->write('loginAttempts', $loginAttempts + 1);
					
				} else {
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
							'contain' 	 => array('Configuration'),
							'conditions' => array('UserGameStatus.user_id' => $this->Auth->user('id'),
												  'UserGameStatus.active' => 1)));
							
		if(empty($active_game)) {
			$active_game['UserGameStatus']['user_id'] = $this->Auth->user('id');
			$active_game['UserGameStatus']['roadmap'] = '';
			$active_game['UserGameStatus']['level']   = 0;
			$active_game['UserGameStatus']['game'] 	  = 0;
			$active_game['UserGameStatus']['points']  = 0;
			$active_game['UserGameStatus']['active']  = 1;
			if(strpos(Router::url('/', true), 'humancatalyst') !== false || strpos(Router::url('/', true), 'localhost') !== false) {
				$active_game['UserGameStatus']['configuration_id'] = 192;
			} else {
				$active_game['UserGameStatus']['configuration_id'] = 81;
			}
			if($this->User->UserGameStatus->save($active_game)) {
				$active_game['UserGameStatus']['id']  = $this->User->UserGameStatus->getLastInsertID();
			}
		}
		
		$this->loadModel('CompanyGroupsUser');
		$options['contain'] 	= false;
		$options['conditions'] 	= array('CompanyGroupsUser.user_id' => $this->Auth->user('id'));
		$options['fields'] 		= array('id', 'company_group_id', 'role_id');
		$group = $this->CompanyGroupsUser->find('first', $options);
		if(!empty($group)) {
			$company = $this->CompanyGroupsUser->CompanyGroup->getPath($group['CompanyGroupsUser']['company_group_id']);
			$group['CompanyGroupsUser']['company_id'] = $company[0]['CompanyGroup']['id'];
		} else {
			$group['CompanyGroupsUser']['role_id'] 			= null;
			$group['CompanyGroupsUser']['company_group_id'] = null;
			$group['CompanyGroupsUser']['company_id'] 		= null;
		}
		
		$this->Session->write('ActiveGame', 	$active_game['UserGameStatus']);
		$this->Session->write('Configuration', 	$active_game['Configuration']);
		$this->Session->write('CompanyGroup', 	$group['CompanyGroupsUser']);
		$this->Session->write('Game.query_all', 0);
		
		$this->redirect(array('controller' => 'games'));
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
						
						$options['subject']  = $this->Session->read('Company.name') . ': Password has been reset';
						$options['to']  	 = $resetUser[$this->modelClass]['email'];
						$options['template'] = 'resetpassword_h';
						
						if(strpos(Router::url('/', true), 'kissaah') !== false) {
							$options['template'] = 'resetpassword_k';
						}
						
						$this->_sendEmail($options, $resetUser);
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
	
	/*
	 * $render = profile or invitation
	 */
	public function profile($render = 'profile') {
		if($this->request->is('put') || $this->request->is('post')) {
			if(!empty($this->request->data['User']['dob'])) {
				$this->request->data['User']['dob'] = DateTime::createFromFormat('m/d/Y', $this->request->data['User']['dob'])->format('Y-m-d');
			}
			
			$this->request->data['User']['id'] = $this->Session->read('ActiveGame.user_id');
			if($this->request->data['User']['newpassword'] != '' && $this->request->data['User']['confirmpassword'] != '') {
				$n_password = $this->Auth->password($this->request->data['User']['newpassword']);
				$c_password = $this->Auth->password($this->request->data['User']['confirmpassword']);
				
				if($n_password == $c_password) {
					$this->request->data['User']['password'] = $this->request->data['User']['newpassword'];
				}
			} else {
				unset($this->request->data['User']['newpassword']);
				unset($this->request->data['User']['confirmpassword']);
			}
			if($this->User->save($this->request->data)) {
				$this->Session->read('ActiveGame');
				$options['contain'] = false;
				$options['conditions'] = array('User.id' => $this->Session->read('ActiveGame.user_id'));
				$user = $this->User->find('first', $options);
				$this->Session->write('Auth.User.name', $user['User']['name']);
				$this->Session->write('Auth.User.gender', $user['User']['gender']);
				$this->Session->write('Auth.User.collage_status', $user['User']['collage_status']);
				
				if($this->request->is('ajax')) {
					$return['Success'] 		= 1;
					$return['ScreenName'] 	= $user['User']['name'];
					$return['Email'] 		= $user['User']['email'];
					return json_encode($return);
					
				} else {
					$this->Session->setFlash('User Profile has been updated.');
				}
			} else {
				$this->Session->setFlash('User Profile could not be updated.');
			}
		} else {
			$options['contain'] 	= false;
			$options['conditions']  = array('User.id' => $this->Session->read('ActiveGame.user_id'));
			$options['fields'] 		= array('id', 'name', 'email', 'collage_status', 'city', 'country', 'gender', 'dob');
			$this->request->data 	= $this->User->find('first', $options);
			if(!empty($this->request->data['User']['dob'])) {
				$this->request->data['User']['dob'] = DateTime::createFromFormat('Y-m-d', $this->request->data['User']['dob'])->format('m/d/Y');
			}
		}
		$this->render($render);
	}

	#Start 3562
	public function createcollage(){
		$this->autoRender = false;
		if($this->request->is('ajax')){
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
			$parent = $this->Acl->Aro->field('id', array('foreign_key' =>'role_id', 'model' => 'Role'));
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

	public function get_user_info($id) {
		$options['contain'] = false;
		$options['conditions'] = array('User.id' => $id);
		return $this->User->find('first', $options);
	}
	
	//For RoadMaps
	public function roadmaps() {
		$roadmaps = $this->User->UserGameStatus->find('all', array(
						'contain' 	 => false,
						'conditions' => array('UserGameStatus.user_id' => $this->Session->read('ActiveGame.user_id'))));
		$configurations = $this->User->UserGameStatus->Configuration->find('list', array('conditions' => array('parent_id' => null, 'status' => 1)));
		$this->set(compact('roadmaps', 'configurations'));
	}
	
	public function roadmap_save(){
		$this->autoRender 	= false;
		$data 				= $this->request->data;
		$data['user_id'] 	= $this->Session->read('ActiveGame.user_id');
		$roadmap 			= array();
		
		$return['success'] = 0;
		$return['update'] = 1;
		if((isset($data['roadmap']) && $data['roadmap'] != '') || (isset($data['configuration_id'])) && $data['configuration_id'] != '') {
			if($data['id'] == '') {
				$return['update'] = $data['level'] = $data['active'] = 0;
			}
			if($this->User->UserGameStatus->save($data)) {
				$return['id'] 			= $id = $this->User->UserGameStatus->id;
				$options['contain'] 	= false;
				$options['conditions']  = array('UserGameStatus.id' => $id);
				$roadmap = $this->User->UserGameStatus->find('first', $options);

				if($roadmap['UserGameStatus']['active']) {
					$this->Session->write('ActiveGame.roadmap', $roadmap['UserGameStatus']['roadmap']);
					$return['delete'] = '';
				} else {
					$return['delete'] = Router::url(array('controller' => 'users', 'action' => 'roadmap_delete', $id), true);
				}
				$return['active'] = Router::url(array('controller' => 'users', 'action' => 'roadmap_edit_active', $id), true);
				$return['success'] = 1;
			}
		}
		return(json_encode($return));
	}
	
	public function start_vision() {
		$this->autoRender = false;
		$vision_date = date('Y-m-d H:i:s', strtotime('+90 Days'));
		$this->User->UserGameStatus->id = $this->Session->read('ActiveGame.id');
		$this->User->UserGameStatus->saveField('vision_date', $vision_date);
		$this->Session->write('ActiveGame.vision_date', $vision_date);
		
		$this->redirect(array('controller' => 'games', 'action' => 'game_step', '?' => array('st' => $this->request->query['st'])));
	}
	
	public function roadmap_delete($user_game_status_id){
		$this->_reset_roadmap($user_game_status_id);
		$this->User->UserGameStatus->delete(array('UserGameStatus.id' => $user_game_status_id), true);
		$this->redirect(array('controller' => 'games', 'action' => 'index'));
	}
	
	//To toggle the active RoadMap
	public function roadmap_edit_active($user_game_status_id = null) {
		if(!is_null($user_game_status_id)) {

			$this->User->UserGameStatus->updateAll(
					array('UserGameStatus.active' => false),
					array('UserGameStatus.active' => true, 'UserGameStatus.user_id' => $this->Session->read('ActiveGame.user_id')));
				
			$this->User->UserGameStatus->id = $user_game_status_id;
			$this->User->UserGameStatus->saveField('active', 1);
		}

		$options['contain'] 	= array('Configuration');
		$options['conditions'] 	= array('UserGameStatus.user_id' => $this->Session->read('ActiveGame.user_id'), 'UserGameStatus.active' => 1);
		$active_game = $this->User->UserGameStatus->find('first', $options);
			
		if(empty($active_game)) {
			$active_game['UserGameStatus']['user_id'] = $this->Session->read('ActiveGame.user_id');
			$active_game['UserGameStatus']['level']   = 0;
			$active_game['UserGameStatus']['game'] 	  = 0;
			$active_game['UserGameStatus']['points']  = 0;
			$active_game['UserGameStatus']['active']  = 1;
			$active_game['UserGameStatus']['configuration_id']  = 192;
			if($this->User->UserGameStatus->save($active_game)) {
				$active_game['UserGameStatus']['id']  = $this->User->UserGameStatus->getLastInsertID();
			}
		}
		$this->Session->write('ActiveGame', 	$active_game['UserGameStatus']);
		$this->Session->write('Configuration',  $active_game['Configuration']);
			
		$this->redirect(array('controller' => 'games', 'action' => 'index'));
	}
	
	//For Support Pages
	public function support() {
		$user_email = $this->Auth->User('email');
		$this->set(compact('user_email'));
	}
	
	//To send Support Mails
	public function send_to_support() {
		$this->autoRender =false;
		$ticket_no = mt_rand(10000000, 99999999);
		$support_data = $this->request->data['User'];
		$data['user']		= $this->Auth->User('email');
		$data['department']	= $support_data['department'];
		$data['priority']	= $support_data['priority'];
		$data['subject']	= $support_data['subject'];
		$data['issue']		= $support_data['issue'];
		$data['ticket_no']  = $ticket_no;
		$images = array();
		
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
		$to = array('bob@himalayantechies.com', 'support@kissaah.com', 'hello@humancatalyst.co', 'vic@kissaah.com');
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

	public function edit_notification_preferences() {
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
		$totalUsers = $this->User->find('count', array('contain' => false));
		
		$this->loadModel('Configuration');
		$totalImagesUploaded = $this->Configuration->find('count', array(
				'conditions' => array('Configuration.status' => 1, 'Configuration.title' => 'Image Activity')));
		
		$img_answers = $this->User->Game->find('all', array(
				'conditions' => array('Configuration.type' => 1), 
				'order' => 'Game.id DESC', 'limit' => 10));
		
		$answers = array();
		foreach($img_answers as $cnt => $img){
			$answers[$cnt]['type']				= 'image';
		 	$answers[$cnt]['user_id']				= ($img['User']['id']);
		 	$answers[$cnt]['user_email']			= ($img['User']['email']);
		 	$answers[$cnt]['GameConfigure_title']	= ($img['Configuration']['title']);
		}
		 
		$users = $this->User->find('all', array('contain' => false, 'order' => 'User.id DESC', 'limit' => 20));
		foreach($users as $cnt => $user){
			$UserList[$cnt]['name']	 	= $user['User']['name'];
			$UserList[$cnt]['email']	= $user['User']['email'];
			$UserList[$cnt]['created'] 	= $user['User']['created'];
		}
		$this->set(compact('totalUsers', 'totalImagesUploaded', 'totalComments', 'answers', 'UserList'));
	}

	function admin_view() {
		$companyAdmin = $this->Session->read('AdminAccess.company');
		$isAdmin = ($this->Auth->user('role_id') == 1) ? true : false;
		if(empty($companyAdmin) && !$isAdmin) {
			$this->redirect($this->referer());
		} else {
			$actions = array('edit' => true, 'delete' => true, 'login' => true, 'verify' => true);
		}
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
		if(!$isAdmin) {
			$this->loadModel('CompanyGroupsUser');
			if(empty($companyAdmin)) $companyAdmin = 0; 
			$companyList = $this->CompanyGroupsUser->CompanyGroup->find('list', array('fields' => array('id', 'id'), 'conditions' => array('OR' => array('CompanyGroup.id' => $companyAdmin, 'CompanyGroup.parent_id' => $companyAdmin)), 'contain' => false));
			$conditions['User.id'] = $this->CompanyGroupsUser->find('list', array('fields' => array('user_id', 'user_id'), 'conditions' => array('company_group_id' => $companyList), 'contain' => false));
			//if(empty($conditions['User.id'])) unset($conditions['User.id']);
			$actions = array('edit' => true, 'delete' => false, 'login' => false, 'verify' => true);
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
		$this->set('actions', 	$actions);
		$this->set('sString', 	$sString);
		$this->set('userlist', 	$userlist);
	}

	public function admin_detail($id = null) {
		if(!empty($this->request->data)) {
			if($this->User->save($this->request->data)) {
				$this->Session->setFlash('User updated Successfully');
				$this->redirect(array('controller' => 'users', 'action' => 'view'));
			} else {
				$this->Session->setFlash('User could not be updated');
			}
		}

		$this->request->data = $this->User->find('first', array(
				'conditions' => array('User.id' => $id), 'contain' => false
		));

		if(empty($this->request->data)){
			$this->Session->setFlash("Invalid User");
			$this->redirect($this->referer());
		}
		
		$roles = $this->User->Role->find('list', array('fields' => array('id', 'name')));
		$this->set('roles', $roles);
	}
	
	//2014-10-21, Badri, This function allows  admin to login as any user
	public function admin_login($id = null) {
		$this->Session->write('Profile_admin', $this->Session->read('Profile'));
		$this->Session->write('ActiveGame_admin', $this->Session->read('ActiveGame'));
		$user_activeGame = $this->User->UserGameStatus->find('first', array(
				'contain'		=> array('Configuration'),
				'conditions'	=> array('UserGameStatus.user_id' => $id,
										 'UserGameStatus.active'  => 1)));
		$user_profile = $this->User->Game->find('first', array(
				'contain' 	 => false,
				'fields'	 => array('Game.id','Game.answer','Game.configuration_id'),
				'conditions' => array('Game.configuration_id' => 36)));
		
		$this->Session->write('ActiveGame', $user_activeGame['UserGameStatus']);
		$this->Session->write('Configuration', $user_activeGame['Configuration']);
		$this->Session->write('Profile', $user_profile);
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
	
	public function admin_bulk_upload($filename = null) {
		if($this->request->is('post') || $this->request->is('put')) {
			$file = $this->request->data['User']['bulk_file'];
			
			$this->Uploader->addMimeType('text', 'csv', 'text/csv');
			$this->Uploader->addMimeType('application', 'csv', 'application/vnd.ms-excel');
			
			if(Uploader::checkMimeType(strtolower(Uploader::ext($file['name'])), $file['type']) != 'application'){
				$allowedExts = Configure::read('Uploader.mimeTypes');
				$allowedImageExts = $allowedExts['image'];
				$allowed = '';
				foreach($allowedImageExts as $a) {
					if(is_array($a)) {
						$a = implode(', ', $a);
					}
					$allowed = $allowed . ',' . $a;
				}
				$this->Session->setFlash('Files of type :' . $file['type'] . ', can not be uploaded ' . ' Allowed Types :' . $allowed);
			} else {
				$this->Uploader->uploadDir = '/files/users';
				$filename = md5(date('Ymdhis') . rand());
				$uploadimage = $this->Uploader->upload($file, array(
						'overwrite' => false,
						'name' 		=> $filename,
						'multiple' 	=> false));
				
				if(!($uploadimage)){
					$this->Session->setFlash('Error');
				} else {
					$filename = $filename . '.' . Uploader::ext($file['name']);
					$this->set(compact('filename'));
				}
			}
		} elseif($filename != null) {
			//$this->set(compact('filename'));
			$filename = WWW_ROOT . DS . 'files/users/' . $filename;
			if (file_exists($filename) && ($handle = fopen($filename, 'r')) !== FALSE) {
				while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
					if(isset($data[0]) && isset($data[1]) && isset($data[2])) {
						$this->request->data['User']['name'] = $data[0];
						$this->request->data['User']['email'] = $data[1];
						$this->request->data['User']['password'] = $data[2];
						
						$return = $this->register(true);
						
						if($return === 0) {
							$data[2] = $this->User->validationErrors;
							$faild[] = $data;
							$this->set(compact('faild'));
						}
					}
				}
				fclose($handle);
			}
		}
	}
	
	public function oauth($source = null) {
		$this->autoRender = false;
		if($source == 'linkedin') {
			$linkedInConfig = Configure::read('LinkedIn');
			if(isset($this->request->query['code'])) {
				if(isset($this->request->query['state']) && $this->request->query['state'] != $linkedInConfig['state']) {
					throw new NotFoundException(__('Invalid request'));
				}
				$post = array(
						'grant_type' => 'authorization_code',
						'code' => $this->request->query['code'],
						'client_id' => $linkedInConfig['clientID'],
						'client_secret' => $linkedInConfig['clientSecret'],
						//'state' => $linkedInConfig['state'],
						'redirect_uri' => Router::url(array('controller' => 'users', 'action' => 'oauth', 'linkedin'), true)
				);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,"https://www.linkedin.com/oauth/v2/accessToken");
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,
				          http_build_query($post)); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$server_output = curl_exec ($ch);
				curl_close ($ch);
				$response = json_decode($server_output, true);
				if(isset($response['access_token'])) {
					$this->request->data['User']['linked_in_token'] = $response['access_token'];
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL,"https://api.linkedin.com/v1/people/~:(id,first-name,last-name,picture-url,email-address)?oauth2_access_token=".$response['access_token']."&format=json");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$server_output = curl_exec ($ch);
					curl_close ($ch);
					$response = json_decode($server_output, true);
					if(!isset($response['status']) || $response['status'] != 401) {
						$this->request->data['User']['email'] = $response['emailAddress'];
						$linkedInUser = $this->User->find('first', array(
								'contain' => false,
								'conditions' => array('email' => $response['emailAddress'])));
						if(!empty($linkedInUser)) {
							$this->User->id = $linkedInUser['User']['id'];
							$this->User->saveField('linked_in_token', $this->request->data['User']['linked_in_token']);
							$this->Auth->login($linkedInUser['User']);
							$this->redirect(array('controller' => 'users', 'action' => 'afterLogin'));
						} else {
							$this->request->data['User']['name'] = $response['firstName'].' '.$response['lastName'];
							if($this->register(false)) {
								$linkedInUser = $this->User->find('first', array('conditions' => array('email' => $this->request->data['User']['email'])));
								if(!empty($linkedInUser)) {
									$this->Auth->login($linkedInUser['User']);
									$this->redirect(array('controller' => 'users', 'action' => 'afterLogin'));
								}
							}
						}
					} else $this->redirect('/');
				} else $this->redirect('/');
			}
		}
	}
}
