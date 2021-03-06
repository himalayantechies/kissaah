<?php
$offset = 'col-md-6 col-md-offset-3';
if($this->request->is('ajax')) {
	$offset = 'data-width-600';
}
?>
<div class="<?php echo $offset; ?>">
<?php
foreach($step_games as $key => $games) {
	if(isset($games[$key]['children'])) {
		foreach($games[$key]['children'] as $game) {
			$summary = true;
			$each_step = $all_games_each_step = '';
			
			if($game['Configuration']['type'] != 12 && $game['Configuration']['id'] != 224) {

				if($game['Configuration']['title'] != '' && $game['Configuration']['title'] != '**NH**') {
					$all_games_each_step .= $this->Html->tag('h3', $game['Configuration']['title'], array('class' => 'activitytitle'));
				}
				if ($game['Configuration']['sub_txt'] != '') {
					$all_games_each_step .= $this->Html->tag('h5', nl2br($game['Configuration']['sub_txt']), array('class' => 'activitytitle'));
				}
					
				if($game['Configuration']['type'] == 16) {
					$summary = false;
					$all_games_each_step .= $this->Html->tag('h5', $game['Configuration']['sub_txt'], array('class' => 'activitytitle'));
				}
				
				if(isset($game['children'])) {
					$count = count($game['children']);
					if($game['Configuration']['type'] == 4) {
						$each_step .= $this->Render->display($game['Configuration']['type'], $game, $count, $summary);
						
					} else {
						foreach($game['children'] as $item) {
							if($item['Configuration']['status']) {
								$each_step .= $this->Render->display($item['Configuration']['type'], $item, $count, $summary);
							}
						}
					}
					
					$all_games_each_step .= $this->Html->div('col-md-12 col-sm-12', $this->Html->div('row no-margin margin-bottom-15', $each_step));
				}
					
				$all_games_each_step .= $this->Html->div('col-md-offset-3 col-md-6 border-bottom col-sm-offset-3 col-sm-6', '');
				
				echo $this->Html->div('row no-margin margin-bottom-20', $all_games_each_step);
			}
		}
	}
}
?>
</div>
<script>
$(document).ready(function(){
	Game.handleRating();
	Game.SaveGame();
	Game.SaveAndCloseGame();
});
</script>