<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en" class="no-js">
<!--<![endif]-->
<head>
<?php 
if(strpos(Router::url('/', true), 'humancatalyst') !== false) {
	$title = 'Human Catalyst';
	$favicon = 'hcfavicon.gif';
} else {
	$title = 'Kissaah';
	$favicon = 'favicon.ico';
}
?>
	<meta charset="utf-8">
	<title><?php echo $title; ?></title>
	<meta name="description" content="">
	<meta name="author" content="Kissaah by Victoria Woo">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<script type="text/javascript">var host_url = '<?php echo Router::url('/', true); ?>';</script>

	<?php
	echo $this->Html->meta($favicon, '/' . $favicon, array('type' => 'icon'));
	//echo $this->Html->css('http://fonts.googleapis.com/css?family=Signika:400,300,600,700&subset=all');
	//echo $this->Html->css('http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&subset=all');
	echo $this->Html->css('http://fonts.googleapis.com/css?family=Josefin+Sans:400,400i,700,700i');
	
    echo $this->Html->css(array('../plugins/font-awesome/css/font-awesome.min',
    							'../plugins/simple-line-icons/simple-line-icons.min',
								'../plugins/bootstrap/css/bootstrap.min',
                                '../plugins/uniform/css/uniform.default',
                                '../plugins/bootstrap-switch/css/bootstrap-switch.min',
								'../plugins/bootstrap-fileinput/bootstrap-fileinput',
								'../plugins/bootstrap-datepicker/css/datepicker',
								'../plugins/fancybox/source/jquery.fancybox',
								'../plugins/jquery-tourbus/dist/jquery-tourbus.min',
								'../plugins/typeahead/typeahead',
								'../plugins/fullcalendar/fullcalendar.min',
								'../plugins/raty/lib/jquery.raty'));
								
	echo $this->Html->css(array('components', 'plugins', 'style', 'challenges', 'custom'));
    
	echo '<!--[if lt IE 9]>';
	echo $this->Html->css(array('ie'));
    echo $this->Html->script(array( '../plugins/respond.min', '../plugins/excanvas.min'));
	echo '<![endif]-->';

    echo $this->Html->script(array( '../plugins/jquery.min',
    								'../plugins/jquery-migrate.min',
    								'../plugins/moment.min',
    								'../plugins/jquery-ui/jquery-ui.min',
    								'../plugins/jquery-ui-touch-punch/jquery.ui.touch-punch.min',
    								'../plugins/jquery.form',
									'../plugins/bootstrap/js/bootstrap.min',
									'../plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min',
                                    '../plugins/jquery-slimscroll/jquery.slimscroll.min',
                                    '../plugins/jquery.blockui.min',
                                    '../plugins/jquery.cokie.min',
                                    '../plugins/uniform/jquery.uniform.min',
                                    '../plugins/bootstrap-switch/js/bootstrap-switch.min',
                                    '../plugins/select2/select2.min',
                                    '../plugins/datatables/media/js/jquery.dataTables.min',
                                    '../plugins/datatables/plugins/bootstrap/dataTables.bootstrap',
									'../plugins/bootstrap-daterangepicker/moment.min',
									'../plugins/bootstrap-datepicker/js/bootstrap-datepicker',
									'../plugins/fancybox/source/jquery.fancybox',
									'../plugins/fancybox/source/jquery.fancybox.pack',
									'../plugins/fancybox/source/helpers/jquery.fancybox-buttons',
									'../plugins/flot/jquery.flot.min', 
									'../plugins/flot/jquery.flot.JUMlib', 
									'../plugins/flot/jquery.flot.spider',
									'../plugins/bootstrap-fileinput/bootstrap-fileinput',
									'../plugins/jquery.scrollTo/jquery.scrollTo.min',
									'../plugins/jquery-tourbus/dist/jquery-tourbus.min',
									'../plugins/jquery-inputmask/jquery.inputmask.bundle.min',
									'../plugins/typeahead/handlebars.min',
									'../plugins/typeahead/typeahead.bundle.min',
									'../plugins/fullcalendar/fullcalendar.min',
									'../plugins/raty/lib/jquery.raty'));
	
	echo $this->Html->script(array('metronic', 'pages/game', 'pages/allies', 'pages/challenges', 'pages/profile'));

	echo $this->fetch('css');
	echo $this->fetch('script');
?>
</head>
<script>
	(function(i, s, o, g, r, a, m) {
		i['GoogleAnalyticsObject'] = r;
		i[r] = i[r] ||
		function() {
			(i[r].q = i[r].q || []).push(arguments)
		}, i[r].l = 1 * new Date();
		a = s.createElement(o), m = s.getElementsByTagName(o)[0];
		a.async = 1;
		a.src = g;
		m.parentNode.insertBefore(a, m)
	})(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

	ga('create', 'UA-19274647-23', 'kissaah.org');
	ga('send', 'pageview');
</script>
<?php 
$screen_width = $this->Session->read('Screen.width');
if(is_null($screen_width)) {
	$screen_width = 768;
}
?>
<body class="for-line">
	<div class="mask"></div>
    <?php echo $this->element('header'); ?>
    <div class="main">
		<div class="container"><?php 
			echo $this->Session->flash('email');
			echo $content_for_layout;
		?></div>
    </div>
    <?php 
    if(!$this->Session->check('Auth.User')) {
		echo $this->element('footer');
	}
    echo $this->element('sql_dump');
    ?>
    
<script type="text/javascript">
$(window).bind('load', function() {
	Game.TourGame();
	Game.ToolBoxLoadLink();
});

jQuery(document).ready(function() {
	Metronic.init();
	Game.UpdateNotification();

	screen_width = <?php echo ($screen_width != '')? 800: $screen_width; ?>;
	if(screen_width > 767) {
		Game.StartGame();
		Game.Support();
	} 
});
</script>
</body>
</html>