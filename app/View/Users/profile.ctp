<?php 
echo $this->Html->script('../plugins/jquery.pwstrength/jquery.pwstrength.js', array('inline' => false));

$offset = ' col-md-6 col-md-offset-3';
if ($this->request->is('ajax')) $offset = ' data-width-600';
?>
<div class="<?php echo $offset; ?>">
	<?php echo $this->Html->tag('h3', 'General Settings', array('class' => 'activitytitle')); ?>
	<div class="col-md-3 col-sm-5 col-xs-4 padding-left-0"><?php
		$image = $this->Session->read('Auth.User.slug');
		if(empty($image)) {
			$image = 'http://placehold.it/220x220&text=Profile';
		} else {
			$image = '/files/img/medium/' . $image;
		}
		
		echo $this->Html->image($image, array('alt' => $this->Session->read('Auth.User.name'), 
							'class' => 'img-responsive', 'data' => 'medium-' . $this->Session->read('Auth.User.id')));

		echo $this->Form->create('User', array('class' => 'btn-file fileupload margin-top-10',
				'data-save' => $this->Html->url(array('controller' => 'games', 'action' => 'upload', 'image'))));
		echo $this->Html->tag('i', '', array('class' => 'fa fa-lg fa-pencil-square-o', 'id' => 'upl' . $this->Session->read('Auth.User.id')));
		echo $this->Html->tag('span', ' Profile Picture', array('class' => ''));
		echo $this->Form->input($this->Session->read('Auth.User.id'), array(
				'type' => 'file', 'label' => false,	'class' => 'default', 'div' => false));
		echo $this->Form->end();

		if ($this->request->is('ajax') === false) {
			echo $this->Html->div('col-md-12 margin-top-10 small no-padding', $this->Html->link('Deactivate Account',
				array('controller' => 'users', 'action' => 'deactivate_account'), array('id' =>'deactivate-account')));
		}
	?></div>
	<div class="col-md-9 col-sm-7 col-xs-8 no-padding">
		<?php echo $this->Form->create('User', array(
				'class' => 'form-horizontal form-bordered form-row-stripped',
				'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'form-control'))); ?>
		<?php echo $this->Form->input('id', array('type' => 'hidden')); ?>
		<div class="form-group no-margin margin-bottom-5">
			<label class="col-md-4 no-padding"><?php echo __('Screen Name'); ?></label>
			<div class="col-md-8 padding-right-0"><?php echo $this->Form->input('name', array('placeholder' => 'Name'));?></div>
		</div>
		<div class="form-group no-margin margin-bottom-5">
			<label class="col-md-4 no-padding">Email</label>
			<div class="col-md-8 padding-right-0"><?php echo $this->Form->input('email', array('placeholder' => 'Email')); ?></div>
		</div>
		<div class="form-group no-margin margin-bottom-5">
			<label class="col-md-4 no-padding">Date for birth</label>
			<?php echo $this->Form->input('dob', array( 'type' => 'text', 'class' => 'form-control date-mask', 
					'placeholder' => 'Date Of Birth', 'div' => 'col-md-8 padding-right-0 padding-left-15')); ?>
		</div>
		<div class="form-group no-margin margin-bottom-5">
			<label class="col-md-4 no-padding">Gender</label>
			<div class="col-md-8 padding-right-0 radio-list"><?php 
				echo $this->Form->radio('gender', array('M' => 'Male', 'F' => 'Female'), 
						array('label'	=> false, 'legend' => false, 'div' => false, 'separator' => '&nbsp;&nbsp;&nbsp;')); ?></div>
		</div>
		<div class="form-group no-margin margin-bottom-5">
			<label class="col-md-4 no-padding">Current City</label>
			<div class="col-md-8 padding-right-0"><?php echo $this->Form->input('city', array('placeholder' => 'Current City')); ?></div>
		</div>
		<div class="form-group no-margin margin-bottom-5">
			<label class="col-md-4 no-padding">Current Country</label>
			<div class="col-md-8 padding-left-15 input-group">
				<span class="input-group-addon"><i class="fa fa-search"></i></span>
				<?php echo $this->Form->input('country', array('placeholder' => 'Current Country')); ?>
			</div>
		</div>
		<br/>
		<input id="change" class="btn green fa fa-edit form-actions right" type="button" value="Change Password">
			<br>
			<br/>
		<div id="new" hidden class="form-group no-margin margin-bottom-5">
			<label class="col-md-4 no-padding">New Password</label>
			<div class="col-md-8 padding-right-0"><?php echo $this->Form->input('newpassword', array('type' => 'password',
					'placeholder' => 'New Password', 'data-indicator' => 'pwindicator' , 'required' => false)); ?></div>
			<div id="pwindicator" class="col-md-offset-4 col-md-8">
	            <div class="bar"></div>
	            <div class="label"></div>
        	</div>
		</div>
		<div id="confirm" hidden class="form-group no-margin margin-bottom-5">
			<label class="col-md-4 no-padding">Confirm Password</label>
			<div class="col-md-8 padding-right-0"><?php echo $this->Form->input('confirmpassword', array('type' => 'password',
					'placeholder' => 'Confirm Password', 'required' => false)); ?></div>
		</div>
		<div class="form-group no-margin margin-bottom-5">
			<label class="check">
				<span><?php echo $this->Form->input('collage_status', array('type' => 'checkbox', 'class' => false, 'div' => false)); ?></span> 
				Contribute to Image Bank
			</label>
		</div>
		<div class="form-actions right">
			<button id="Test" class="btn green" type="submit"><i class="fa fa-check"></i>Submit</button>
			<?php 
			if ($this->request->is('ajax') === false) {
				echo $this->Html->div('col-md-12 margin-top-10 text-center', $this->Html->link('Back to Game',
						array('controller' => 'games', 'action' => 'index'),
						array('class' => 'btn btn-primary')));
			}
			?>
		</div>
		<?php echo $this->Form->end(); ?>
	</div>
</div>
<script type="text/javascript">
$(document).ready(function() {
	FileUpload.UploadFileImage();
	Profile.DeactivateAccount();
	$('.date-mask').inputmask('mm/dd/yyyy', {
		'placeholder' : 'mm/dd/yyyy'
	});
	ProfileCountries.init();
});
$("#change").click(function() {
	if(!$("#new input").is(':required') && !$("#confirm input").is(':required')){
		$("#new").show();
		$("#confirm").show();
		$("#new input").attr('required', true);
		$("#confirm input").attr('required', true);
	}
	else{
		$("#new").hide();
		$("#confirm").hide();
		$("#new input").removeAttr('required');
		$("#confirm input").removeAttr('required');
	}
});
jQuery(document).ready(function($) { 
        $('#UserNewpassword').pwstrength(); 
    });
</script>