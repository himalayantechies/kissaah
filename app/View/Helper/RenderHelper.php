<?php
App::uses('AppHelper', 'View/Helper');

class RenderHelper extends AppHelper {
	var $helpers = array ('Session');
	
	function display($activitytype, $game, $count, $summary = false) {
		$view = '';
		if($activitytype == 1) {
			$view = $this->_View->element('game_step/upload', array('selfdata' => $game, 'count' => $count, 'summary' => $summary));
			
		} elseif($activitytype == 2) {
			$view = $this->_View->element('game_step/date', array('selfdata' => $game, 'summary' => $summary));
				
		} elseif($activitytype == 3) {
			$view = $this->_View->element('game_step/add_more', array('selfdata' => $game, 'summary' => $summary));
				
		} elseif($activitytype == 4) {
			$view = $this->_View->element('game_step/sorting', array('selfdata' => $game, 'summary' => $summary));
				
		} elseif($activitytype == 5) {
			$view = $this->_View->element('game_step/textarea', array('selfdata' => $game, 'summary' => $summary));
				
		} elseif($activitytype == 7) {
			$view = $this->_View->element('game_step/text', array('selfdata' => $game, 'summary' => $summary));
				
		} elseif($activitytype == 9) {
			$view = $this->_View->element('game_step/allies', array('selfdata' => $game, 'summary' => $summary));
			
		} elseif($activitytype == 10) {
			$view = $this->_View->element('game_step/opp_rate', array('selfdata' => $game, 'parent' => $parent, 'summary' => $summary));
			
		} elseif($activitytype == 13) {
			if(!$summary) {
				$view = $this->_View->element('game_step/sorting_parent', array('selfdata' => $game, 'summary' => $summary));
			}
			
		} elseif($activitytype == 14) {
			$view = $this->_View->element('game_step/sorting_child', array('selfdata' => $game, 'summary' => $summary));
			
		} elseif($activitytype == 15) {
			$view = $this->_View->element('game_step/challenges', array('selfdata' => $game, 'summary' => $summary));
			
		} elseif($activitytype == 16) {
			$view = $this->_View->element('game_step/challenge_summary', array('selfdata' => $game, 'summary' => $summary));
			
		}
		
		return $view;
	}
}