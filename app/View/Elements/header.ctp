<div class="header">
	<div class="container">
		<div class="row">
	  		<div class="col-md-4 col-sm-4 col-xs-4 site-logo"><?php 
	  		if(strpos(Router::url('/', true), 'humancatalyst') !== false || strpos(Router::url('/', true), 'localhost') !== false) {
	  			$image = $this->Html->image('HumanCatalystGrey.png');
	  		} else {
	  			$image = $this->Html->image('kissaah-logo-wht-03.png');
	  		}
	  		 
			if($this->Session->check('Auth.User')) {
	  			echo $this->Html->link($image, array('controller' => 'games', 'action' => 'index'), array('escape' => false));
			} else {
	  			echo $this->Html->link($image, $this->Session->read('Company.link'), array('escape' => false));
			}
	  		?> &nbsp;Beta</div>
	  		<div class="col-md-1 col-md-offset-0 col-md-push-7 col-sm-2 col-sm-offset-0 col-sm-push-6 col-xs-4 col-xs-offset-4 no-padding" id="tour-step-2"><?php 
			if($this->Session->check('Auth.User')) {
				$image = $this->Session->read('Auth.User.slug');
				$image = (empty($image))? 'profile.png': '../files/img/small/' . $image;
	
		        echo $this->Html->image($image, array('alt' => $this->Session->read('Auth.User.name'),
		        									  'data' => 'medium-36', 
		        									  'class' => 'img-responsive'));
				
			}
			?></div>
			<div class="col-md-7 col-md-pull-1 col-sm-6 col-sm-pull-2 col-xs-12"><?php
			if($this->Session->check('Auth.User')) {
	  			$name = ($this->Session->read('Auth.User.name') == '')? $this->Session->read('Auth.User.email'): $this->Session->read('Auth.User.name');
				echo $this->Html->tag('h2', $name, array('class' => 'font-white text-right'));
				echo $this->Html->tag('h4', $this->Session->read('ActiveGame.roadmap'), array('class' => 'font-white text-right'));
			}
	  		?></div>
	  	</div>
	</div>
</div>
<?php if($this->Session->check('Auth.User')) { ?>
<div class="header header-navigation margin-bottom-10">
	<div class="container">
		<div class="row">
	  		<div class="col-md-9 col-xs-8"><?php 
	  		$group = $this->Session->read('CompanyGroup');
	  		
	  		$tool  = $this->Html->div('tool-box-info', 'RoadMaps' . $this->Html->tag('span', null, array('class' => 'down-arrow')));
	  		$tool .= $this->Html->link($this->Html->tag('i', '', array('class' => 'fa fa-map-signs fa-2x')),
	  				array('controller' => 'users', 'action' => 'roadmaps'),
	  				array('class' => 'fbox-toolbox', 'data-width' => '680', 'escape' => false, 'data-type' => 'ajax'));
	  		echo $this->Html->div('pull-left icon-wrapper margin-right-10', $tool);
	  		
	  		$tool  = $this->Html->div('tool-box-info', 'Allies' . $this->Html->tag('span', null, array('class' => 'down-arrow')));
			$tool .= $this->Html->link($this->Html->tag('i', '', array('class' => 'fa fa-shield fa-2x')),
			    					array('controller' => 'allies', 'action' => 'allies'),
			    					array('class' => 'fbox-toolbox', 'escape' => false, 'data-type' => 'ajax'));
			echo $this->Html->div('pull-left icon-wrapper margin-right-10', $tool);
			
			$tool  = $this->Html->div('tool-box-info', 'Calendar' . $this->Html->tag('span', null, array('class' => 'down-arrow')));
			$tool .= $this->Html->link($this->Html->tag('i', '', array('class' => 'fa fa-calendar-check-o fa-2x')),
			                        array('controller' => 'challenges', 'action' => 'calendar'), 
			                        array('class' => 'fbox-challenges', 'escape' => false, 'data-type' => 'ajax'));
			echo $this->Html->div('pull-left icon-wrapper margin-right-10', $tool);

			$tool  = $this->Html->div('tool-box-info', 'Export Roadmap' . $this->Html->tag('span', null, array('class' => 'down-arrow')));
			$tool .= $this->Html->link( $this->Html->tag('i', '', array('class' => 'fa fa-file-word-o fa-2x', 'id' => 'tour-step-08')),
			    					array('controller' => 'games', 'action' => 'summary', 'export'), 
			    					array('escape' => false));
			echo $this->Html->div('pull-left icon-wrapper margin-right-10', $tool);
			
			$org_id = null;
			if(!empty($group)) {
				$org_id = $group['company_id'];
			}
			$tool  = $this->Html->div('tool-box-info', 'Organization Map' . $this->Html->tag('span', null, array('class' => 'down-arrow')));
			$tool .= $this->Html->link( $this->Html->tag('i', '', array('class' => 'fa fa-sitemap fa-2x', 'aria-hidden' => 'true')),
						array('controller' => 'organizations', 'action' => 'index', $org_id), array('escape' => false));
			echo $this->Html->div('pull-left icon-wrapper margin-right-10', $tool);
			
			$tool  = $this->Html->div('tool-box-info', 'Spark Board' . $this->Html->tag('span', null, array('class' => 'down-arrow')));
			$tool .= $this->Html->link( $this->Html->tag('i', '', array('class' => 'fa fa-table fa-2x')),
			    		array('controller' => 'games', 'action' => 'spark_board'), array('escape' => false));
			echo $this->Html->div('pull-left icon-wrapper margin-right-10', $tool);
			
			if(!empty($group) && ($group['role_id'] == 1 || $group['role_id'] == 3 || $group['role_id'] == 4)) {
				$tool  = $this->Html->div('tool-box-info', 'Summary Board' . $this->Html->tag('span', null, array('class' => 'down-arrow')));
				$tool .= $this->Html->link( $this->Html->tag('i', '', array('class' => 'fa fa-barcode fa-2x')),
						array('controller' => 'games', 'action' => 'summary_spark_board'), array('escape' => false));
				echo $this->Html->div('pull-left icon-wrapper margin-right-10', $tool);

			}
			?></div>
	  		<div class="col-md-3 col-xs-4">
				<ul class="nav navbar-nav pull-right">
				<?php 
					$allies_notification 	= $this->Session->read('allies_notification');
					$feedback_notification 	= $this->Session->read('feedback_notification');

					$number = 0;
					$list = array();
					if(!empty($allies_notification)) {
						foreach($allies_notification as $noti) {
							$text = '';
							if($noti['Ally']['user_id'] == $this->Session->read('Auth.User.id') && $noti['Ally']['ally_notification'] == 'Accepted') {
								$text = $noti['MyAlly']['name'] . ' accepted your request to be an ally';
								
							} elseif($noti['Ally']['ally'] == $this->Session->read('Auth.User.id') && $noti['Ally']['ally_notification'] == 'Requested') {
								$text = $noti['User']['name'] . ' requested you as an ally';
								
							}
							
							if($text != '') {
								$number++;
								$list[] = $this->Html->link($this->Html->tag('span', $text, array('class' => 'details')), 
											array('controller' => 'allies', 'action' => 'notification', 'ally_notification', $noti['Ally']['id']), 
											array('class' => 'noti', 'escape' => false));
							}
						}
					}
					if(!empty($feedback_notification)) {
						foreach($feedback_notification as $noti) {
							$text = '';
							if($noti['Ally']['user_id'] == $this->Session->read('Auth.User.id') && $noti['Ally']['feedback_notification'] == 'Feedback') {
								$text = $noti['MyAlly']['name'] . ' has given you a feedback';
							}
							
							if($text != '') {
								$number++;
								$list[] = $this->Html->link($this->Html->tag('span', $text, array('class' => 'details')), 
											array('controller' => 'allies', 'action' => 'notification', 'feedback_notification', $noti['Ally']['id']), 
											array('class' => 'noti', 'escape' => false));
							}
						}
					}
					?>
					<li class="dropdown dropdown-extended dropdown-notification" id="notification">
						<?php
						$badge = '';
						if($number > 0) {
							$badge = $this->Html->tag('span', $number, array('class' => 'badge badge-default'));
						}
						echo $this->Html->link( $this->Html->tag('i', '', array('class' => 'fa fa-bell fa-2x')) . $badge, '#',
												array('class' => 'dropdown-toggle', 'data-toggle' => 'dropdown',
													  'data-hover' => 'dropdown', 'data-close-others' => 'true', 'escape' => false));
						?>
						<ul class="dropdown-menu notification">
							<li class="external">
								<h3><span class="bold"><?php echo $number; ?></span> notifications</h3>
							</li>
							<li>
							<?php 
							echo $this->Html->nestedList($list, array('class' => 'dropdown-menu-list scroller', 
																	  'style' => 'height: 250px;', 'data-handle-color' => '#637283'));
							?>
							</li>
						</ul>
					</li>

					<li class="dropdown">
						<a id="tour-step-11" class="dropdown-toggle" href="#" 
							data-hover="dropdown" data-toggle="dropdown" data-close-others="true"> 
							<i class="fa fa-bars fa-2x"></i>
						</a>
						<?php
						$list = array();
						if($this->request->controller != 'users' || $this->request->action != 'profile') {
							$list[] = $this->Html->link($this->Html->tag('i', '', array('class' => 'fa fa-cog')) . ' Settings', 
													array('controller' => 'users', 'action' => 'profile'), 
													array('escape' => false, 'id' => 'tour-step-05'));
						}
						
						if($this->request->controller != 'games' || $this->request->action != 'index') {
							$list[] = $this->Html->link($this->Html->tag('i', '', array('class' => 'fa fa-trophy')) . ' Back to Game', 
													array('controller' => 'games', 'action' => 'index'), 
													array('escape' => false));
						}

						if($this->request->controller == 'games' || $this->request->action == 'index') {
							$list[] = $this->Html->link($this->Html->tag('i', '', array('class' => 'fa fa-home')) . ' Start Tour',
														'#', array('escape' => false, 'id' => 'game-tour'));
						}
						
						$list[] = $this->Html->link($this->Html->tag('i', '', array('class' => 'fa fa-wrench')) . ' Support', 
													array('controller' => 'users', 'action' => 'support'), 
													array('class' => 'fbox-support', 'escape' => false));
			
						$admin = $this->Session->read('Auth.User.role_id');
						$company_admin = $this->Session->read('AdminAccess.company');
						if($admin == 1) {
							$list[] = $this->Html->link($this->Html->tag('i', '', array('class' => 'fa fa-lock')) . ' Administrator', 
														array('controller' => 'users', 'action' => 'index', 'admin' => true),
														array('escape' => false, 'target' => '_blank'));
						} elseif(!empty($company_admin)) {
							$list[] = $this->Html->link($this->Html->tag('i', '', array('class' => 'fa fa-lock')) . ' Administrator',
									array('controller' => 'company_groups', 'action' => 'index', 'admin' => true),
									array('escape' => false, 'target' => '_blank'));
						}
						
						$list[] = $this->Html->link($this->Html->tag('i', '', array('class' => 'fa fa-sign-out')) . ' Logout',
								array('controller' => 'users', 'action' => 'logout'), array('escape' => false));
						
						if($this->Session->read('Auth.User')) {
							echo $this->Html->nestedList($list, array('class' => 'dropdown-menu dropdown-menu-default'));
						}
						?>
					</li>
				</ul>
			</div>
	  	</div>
	</div>
</div>
<?php } ?>