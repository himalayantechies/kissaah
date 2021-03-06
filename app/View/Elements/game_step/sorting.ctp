<?php
$imageRow = $li = $id = '';
$action = $selfdata['Configuration']['naration_txt'];
foreach($selfdata['children'] as $key => $value) {
	$id = $value['Configuration']['parent_id'];
	
	if(empty($value['Dependent'][0]['answer'])){
		$image = 'http://placehold.it/100x100&text=No Image';
	} else {
		$image = '/files/img/small/' . $value['Dependent'][0]['answer'];
	}
	$image = $this->Html->image($image, array('class' => 'img-responsive', 
											  'data'  => 'small-' . $value['Configuration']['dependent_id']));
	$r =  $this->Html->div('col-md-3 col-sm-3 col-xs-4 no-padding hidden-xs', $image);
	
	$r .= '<div class="row no-margin hidden-lg hidden-md hidden-sm">';
	$r .= $this->Html->div('col-xs-6 no-padding', $this->Html->div('btn-label light-blue sorting-list-header', 'Inspiration'));
	$r .=  $this->Html->div('col-xs-4 col-xs-offset-1 no-padding', $image);
	$r .= '</div>';
	
	if(isset($value['children'])) {
		foreach($value['children'] as $k => $l) {
			
			$heading[$l['Configuration']['title']] = $this->Html->div('col-md-3 col-sm-3 col-xs-4 hidden-xs no-padding', 
																	  $this->Html->div('btn-label light-blue sorting-list-header', 
																	  $l['Configuration']['title']));
			$headingxs[$l['Configuration']['title']] = $this->Html->div('col-xs-6 no-padding',
					$this->Html->div('btn-label-xs light-blue sorting-list-header',
							$l['Configuration']['title']));
			
			if(empty($l['Game'][0]['Game']['answer'])) {
				$l['Game'][0]['Game']['id'] = 0;
				$answer = $this->Html->tag('li', 'Drop <br />Here', array('class' => 'draggable-drop-here'));
				$answerxs = $this->Html->tag('li', 'Drop Here', array('class' => 'draggable-drop-here'));
			} else {
				$answer	= $this->Html->tag('li', $l['Game'][0]['Game']['answer'], array('class' => 'draggable-list'));
				$answerxs	= $this->Html->tag('li', $l['Game'][0]['Game']['answer'], array('class' => 'draggable-list'));
			}
			
			$r .= $this->Html->div('col-md-3 col-sm-3 col-xs-3 droppable-answer hidden-xs', $answer, array(
											'data-conf' => $l['Configuration']['id'], 
											'data-id' 	=> $l['Game'][0]['Game']['id'], 
											'name' 		=> 'data[Game][' . $l['Configuration']['id'] . '][' . $l['Game'][0]['Game']['id'] . ']'));
			$r .= '<div class="row no-margin hidden-lg hidden-md hidden-sm">';
			$r .= $headingxs[$l['Configuration']['title']];
			$r .= $this->Html->div('col-xs-6 droppable-answer-xs no-padding', $answerxs, array(
					'data-conf' => $l['Configuration']['id'],
					'data-id' 	=> $l['Game'][0]['Game']['id'],
					'name' 		=> 'data[Game][' . $l['Configuration']['id'] . '][' . $l['Game'][0]['Game']['id'] . ']'));
			$r .= '</div>';
		}
	}
	
	$imageRow .= $this->Html->div('row no-margin margin-bottom-5', $r);
}

if(!$summary) {
	$data = $this->requestAction(array('controller' => 'games', 'action' => 'get_sortlist', $selfdata['Configuration']['id']));
	
	foreach($data as $key => $list) {
		$li .= $this->Html->tag('li', $list, array('class' => 'draggable-list col-xs-4', 'id' => $key));
		
	}
	$ul = $this->Html->div('col-md-12 col-sm-12 col-xs-12 value-list', $li);
	$ulxs = $this->Html->div('col-md-12 col-sm-12 col-xs-12 value-list-xs', $li);
	
	$discard = $this->Html->div('col-md-5 col-sm-5 col-xs-12 droppable-discard', 'Drop Here to Discard');
	$wildCrd = $this->Html->div('col-md-5 col-sm-5 col-sm-offset-2 col-xs-12 add-wild-card', 
									$this->Html->link('Add your own ' . $action . ' >', '#') . 
									$this->Form->input('WildCard', array('class' => 'form-control', 'div' => false,
																		 'label' => false, 'placeholder' => 'My ' . $action)) .
									$this->Html->tag('li', '', array('class' => 'draggable-list')));
		
	$actions = $this->Html->div('row no-margin', $discard . $wildCrd);
	
	$final = $this->Html->div('col-md-8 col-sm-8 col-xs-12 no-padding drop-here-box hidden-xs', $imageRow . $actions) .
			 $this->Html->div('col-md-4 col-sm-4 col-xs-4 no-padding padding-left-10 hidden-xs', $ul);
	$final .= $this->Html->div('col-md-8 col-sm-8 col-xs-8 no-padding drop-here-box hidden-lg hidden-md hidden-sm', $imageRow .$actions ) .
			 $this->Html->div('col-md-4 col-sm-4 col-xs-4 no-padding hidden-lg hidden-md hidden-sm', $ulxs);
			 
} else {
	$final = $this->Html->div('col-md-8 col-md-offset-2 col-sm-8 col-sm-offset-2 no-padding', $imageRow);
	
}

$vheading = $this->Html->div('col-md-3 col-sm-3 col-xs-3 no-padding', $this->Html->div('btn-label light-blue sorting-list-header', 'Inspiration'));
foreach($heading as $h) {
	$vheading .= $h;
}
if(!$summary) {
	$vheading = $this->Html->div('col-md-8 col-sm-8 col-xs-12 no-padding', $vheading) .
	$this->Html->div('col-md-4 col-sm-4 hidden-xs no-padding padding-left-10',
			$this->Html->div('col-md-12 no-padding', $this->Html->div('btn-label light-blue sorting-list-header', 'Values')));
} else {
	$vheading = $this->Html->div('col-md-8 col-md-offset-2 col-sm-8 col-sm-offset-2 no-padding', $vheading);

}

if($id != 77) {
	echo $this->Html->div('row no-margin margin-bottom-10 hidden-xs', $vheading);
}
echo $this->Html->div('row no-margin', $final);
?>
