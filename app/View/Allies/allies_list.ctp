<?php
if($message != ''){
	echo $this->Html->tag('h4', $message);
} else {
	echo $this->Html->div('col-xs-12 col-sm-12 col-md-12 col-lg-12', $this->Html->tag('h4', count($answers) . ' users found'));
	
	if(count($answers) == 0) {
		$link = '';
		$ajax = ' btn-ally';
		if(isset($this->request->query['st']) && isset($this->request->query['challenge'])) {
			$link = $this->request->query;
			$ajax = '';
		}
		
		echo $this->Html->div('col-xs-12 col-sm-12 col-md-12 col-lg-12', 
				$this->Html->div('col-xs-12 col-sm-12 col-md-12 col-lg-12',
						$this->Html->para(null, 'Enter your friends email to invite them to join ' . $this->Session->read('Company.name') . ' as your ally.')));
		
		echo $this->Html->div('col-xs-12 col-sm-12 col-md-12 col-lg-12', $this->Html->div('input-group',
				$this->Form->input('Email', array('label' => false, 'div' => false, 'class' => 'form-control', 'type' => 'text')) .
				$this->Html->tag('span', $this->Html->link('Invite Ally ' . $this->Html->tag('i', '', array('class' => 'fa fa-plus-square')),
						array('controller' => 'allies', 'action' => 'request', 'slug', '?' => $link),
						array('class' => 'btn btn-finished btn-invite' . $ajax, 'escape' => false, 'data-type' => 'ajax')), 
						array('class' => 'input-group-btn'))));
	} else {
		$my_allies = '';
		foreach($answers as $ally) {
			$icon = ' fa-plus-circle';
			$link = $class = '';

			$ally_name = ((empty($ally['User']['name']))? $ally['User']['email']: $ally['User']['name']);
			
			$image = (empty($ally['User']['slug']))? 'profile.png': '../files/img/medium/' . $ally['User']['slug'];
			$image = $this->Html->image($image, array('class' => 'img-responsive margin-top-10 margin-bottom-10'));
			
			$ajax = ' btn-ally';
			if(isset($this->request->query['st']) && isset($this->request->query['challenge'])) {
				$link = $this->request->query;
				$ajax = '';
			}
			
			$btndr = $this->Html->link('Invite Ally ' . $this->Html->tag('i', '', array('class' => 'fa fa-plus-square')), 
	  				array('controller' => 'allies', 'action' => 'request', $ally['User']['id'], '?' => $link),
					array('class' => 'btn btn-finished margin-bottom-5' . $ajax, 'escape' => false, 'data-type' => 'ajax'));
			
			$span  = $this->Html->tag('span', $ally_name . '<br />' . $btndr, array('id' => $ally['User']['id']));
			
			$my_allies .= $this->Html->div('col-md-4 col-sm-4 col-xs-6 text-015 margin-bottom-10 ally-box color-grey text-center', 
											$image . $span, array('data' => $ally['User']['id']));
		}

		echo $this->Html->div('col-xs-12 col-sm-12 col-md-12 col-lg-12 margin-bottom-20', $my_allies);
	}
}
?>
<script>
$(document).ready(function(){
	Allies.OpenPopup();
});
</script>