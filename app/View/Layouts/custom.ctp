<?php   
/**
 * Default Layout View File
 *
 * Handles the default view html, and the database driven template system. 
 *
 * PHP versions 5
 *
 * Zuha(tm) : Business Management Applications (http://zuha.com)
 * Copyright 2009-2012, Zuha Foundation Inc. (http://zuhafoundation.org)
 *
 * Licensed under GPL v3 License
 * Must retain the above copyright notice and release modifications publicly.
 *
 * @copyright     Copyright 2009-2012, Zuha Foundation Inc. (http://zuha.com)
 * @link          http://zuha.com Zuha� Project
 * @package       zuha
 * @subpackage    zuha.app.views.layouts
 * @since         Zuha(tm) v 0.0.1
 * @license       GPL v3 License (http://www.gnu.org/licenses/gpl.html) and Future Versions
 * @todo		  Its time to move the different template tags to a new place.  They are getting too heavy for this default file, and aren't reusable easily.  (Things like {helper: content_for_layout} etc.)
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php        
if(!empty($this->Facebook)) { echo $this->Facebook->html(); } else { echo '<html>'; } ?>
<head>
<?php echo $this->Html->charset(); ?>
<title><?php echo $title_for_layout; ?></title>
<!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
<meta name="robots" content="index, follow" />
<meta http-equiv="X-UA-Compatible" content="IE=8" />
<meta name="viewport" content="width=device-width"/>
<meta name="apple-mobile-web-app-capable" content="yes"/>
<?php
		echo $this->Html->meta('icon');
		
		# load in css files from settings
		echo $this->Html->css('system', 'stylesheet', array('media' => 'all')); 
		echo $this->Html->css('admin/jquery-ui-1.8.13.custom');
		if (defined('__WEBPAGES_DEFAULT_CSS_FILENAMES')) {
		//$defaultTemplate['Webpage']['content']=str_replace('{helper: content_for_layout}','',$defaultTemplate['Webpage']['content']);
	//	print_r($defaultTemplate['Webpage']['content']);
//	echo '{helper: content_for_layout}';
  //  die;
			$i = 0;
			foreach (unserialize(__WEBPAGES_DEFAULT_CSS_FILENAMES) as $media => $files) { 
				foreach ($files as $file) {
					if (strpos($file, ',')) {
						if (strpos($file, $defaultTemplate['Webpage']['id'].',') === 0) {
							$file = str_replace($defaultTemplate['Webpage']['id'].',', '', $file);
							echo $this->Html->css($file, 'stylesheet', array('media' => $media)); 
						}
					} else {
						echo $this->Html->css($file, 'stylesheet', array('media' => $media)); 
					}
				}
				$i++;
			} 
		} else {
			echo $this->Html->css('screen'); 
		}
		
		# load in js files from settings
		echo $this->Html->script('jquery-1.5.2.min');
		echo $this->Html->script('admin/jquery-ui-1.8.13.custom.min');
		echo $this->Html->script('system/system');
		if (defined('__WEBPAGES_DEFAULT_JS_FILENAMES')) { 
			$i = 0;
			foreach (unserialize(__WEBPAGES_DEFAULT_JS_FILENAMES) as $media => $files) { 
				foreach ($files as $file) {
					if (strpos($file, ',')) {
						if (strpos($file, $defaultTemplate['Webpage']['id'].',') === 0) {
							$file = str_replace($defaultTemplate['Webpage']['id'].',', '', $file);
							echo $this->Html->script($file);
						}
					} else {
						echo $this->Html->script($file);
					}
				}
				$i++;
			} 
		} 
		echo $scripts_for_layout;
		if (defined('__REPORTS_ANALYTICS')) :
			echo $this->Element('analytics', array(), array('plugin' => 'reports'));
		endif;
	?>
</head>
<body class="<?php echo $this->request->params['controller']; echo ($this->Session->read('Auth.User') ? __(' authorized') : __(' restricted')); ?> <?php echo $this->request->params['action']; ?> <?php echo __('userRole%s', $userRoleId); ?>" id="<?php echo !empty($this->request->params['pass'][0]) ? strtolower($this->request->params['controller'].'_'.$this->request->params['action'].'_'.$this->request->params['pass'][0]) : strtolower($this->request->params['controller'].'_'.$this->request->params['action']); ?>" lang="<?php echo Configure::read('Config.language'); ?>">
<div id="corewrap">
  <?php 
echo $this->Element('modal_editor', array(), array('plugin' => 'webpages'));

$flash_for_layout = $this->Session->flash();
$flash_auth_for_layout = $this->Session->flash('auth');
if (!empty($defaultTemplate)) {
	
	# matches helper template tags like {helper: content_for_layout}
	preg_match_all ("/(\{helper: ([az_]*)([^\}\{]*)\})/", $defaultTemplate["Webpage"]["content"], $matches);
	$i = 0;
	foreach ($matches[0] as $helperMatch) {
		$helper = trim($matches[3][$i]);
		$defaultTemplate["Webpage"]["content"] = str_replace($helperMatch, $$helper, $defaultTemplate['Webpage']['content']);
		$i++;
	}
	
	#skiping the parsing of text area content with this check	
	preg_match_all ("/(<textarea[^>]+>)(.*)(<\/textarea>)/isU", $defaultTemplate["Webpage"]["content"], $matchesEditable);
	
	$nonParseable = array();
	
	$i = 0;
	foreach($matchesEditable[2] as $matchEditable)	{
		if(trim($matchEditable))	{
			$nonParseable['[PLACEHOLDER:'.$i.']'] = $matchEditable;
			$defaultTemplate["Webpage"]["content"] = str_replace($matchEditable, '[PLACEHOLDER:'.$i.']', $defaultTemplate['Webpage']['content']);
			$i++;
		}		
	}
	
	# matches element template tags like {element: plugin.name.instance} for example {element: contacts.recent.2}
	preg_match_all ("/(\{element: ([az_]*)([^\}\{]*)\})/", $defaultTemplate["Webpage"]["content"], $matches);

	$i=0; 
	foreach ($matches[0] as $elementMatch) {
		$element = trim($matches[3][$i]);
		if (preg_match('/([a-zA-Z0-9_\.]+)([a-zA-Z0-9_]+\.[0-9]+)/', $element)) {
			# means there is an instance number at the end
			$element = explode('.', $element);
			# these account for the possibility of a plugin or no plugin
			$instance = !empty($element[2]) ? $element[2] : $element[1]; 
			$plugin = !empty($element[2]) ? $element[0] : null;
			$element = !empty($element[2]) ? $element[1] : $element[0];
		} else if (strpos($element, '.')) {
			# this is used to handle non plugin elements with no instance number in the tag
			$element = explode('.', $element);  
			$plugin = $element[0];
			$element = $element[1];  
		}
		# removed cache for forms, because you can't set it based on form inputs
		# $elementCfg['cache'] = (!empty($userId) ? array('key' => $userId.$element, 'time' => '+2 days') : null);
		$elementPlugin['plugin'] = (!empty($plugin) ? $plugin : null);
		$elementCfg['instance'] = (!empty($instance) ? $instance : null);
		$defaultTemplate["Webpage"]["content"] = str_replace($elementMatch, $this->element($element, $elementCfg, $elementPlugin), $defaultTemplate['Webpage']['content']); 
		$i++;
	}
	
	# matches form template tags {form: Id/type} for example {form: 1/edit}
	preg_match_all ("/(\{form: ([az_]*)([^\}\{]*)\})/", $defaultTemplate["Webpage"]["content"], $matches);
	$i = 0;
	foreach ($matches[0] as $formMatch) {
		$formCfg['id'] = trim($matches[3][$i]);
		# removed cache for forms, because you can't set it based on form inputs
		# $formCfg['cache'] = array('key' => 'form-'.$formCfg['id'], 'time' => '+2 days');
		$defaultTemplate["Webpage"]["content"] = str_replace($formMatch, $this->element('forms', $formCfg, array('plugin' => 'forms')), $defaultTemplate['Webpage']['content']);
		$i++;
	}
	
	# matches menu template tags like {menu: Id} for example {menu: 3}
	preg_match_all ("/(\{menu: ([az_]*)([^\}\{]*)\})/", $defaultTemplate["Webpage"]["content"], $matches);
	$i = 0;
	foreach ($matches[0] as $menuMatch) {
		$menuCfg['id'] = trim($matches[3][$i]);
		# removed cache temporarily
		# $menuCfg['cache'] = array('key' => 'menu-'.$menuCfg['id'], 'time' => '+999 days');
		$menuCfg['plugin'] = 'menus';
		$defaultTemplate['Webpage']['content'] = str_replace($menuMatch, $this->element('menus', $menuCfg), $defaultTemplate['Webpage']['content']);
		$i++;
	}
	
	#checking for the textarea content placeholders	
	foreach($nonParseable as $placeHolder=>$holdingContent)	{
		$defaultTemplate["Webpage"]["content"] = str_replace($placeHolder, $holdingContent, $defaultTemplate['Webpage']['content']);
	}
	
	# display the database driven default template
	echo $defaultTemplate['Webpage']['content'];
} else {
	echo $this->Session->flash(); 
    echo $this->Session->flash('auth');
	echo $content_for_layout;
} 

echo(base64_decode('PGEgc3R5bGU9ImRpc3BsYXk6IG5vbmU7IiB0aXRsZT0iV2ViIERlc2lnbiAmIFdlYiBEZXZlbG9wbWVudCBDb21wYW55IiBocmVmPSJodHRwOi8vd3d3LnJhem9yaXQuY29tLyI+V2ViIERlc2lnbiAmIFdlYiBEZXZlbG9wbWVudCBDb21wYW55PC9hPg0KPGEgc3R5bGU9ImRpc3BsYXk6IG5vbmU7IiB0aXRsZT0iSW52b2ljaW5nLCBQcm9qZWN0IE1hbmFnZW1lbnQsIENSTSwgQ29udGVudCBNYW5hZ2VtZW50IFN5c3RlbSIgaHJlZj0iaHR0cDovL3p1aGEuY29tIj5JbnZvaWNpbmcsIFByb2plY3QgTWFuYWdlbWVudCwgQ1JNLCBDb250ZW50IE1hbmFnZW1lbnQgU3lzdGVtPC9hPg==')); ?>
  <?php #echo round((getMicroTime() - $_SERVER['REQUEST_TIME']) * 1000) ?>
</div>
<?php # echo $this->element("ajax-login"); ?> 
<?php echo $this->element('sql_dump');  ?> 
<?php echo !empty($dbSyncError) ? $dbSyncError : null; ?>
<div class="ajaxLoader"><img src="/img/ajax-loader.gif" /></div>
</body>
<?php  if(!empty($this->Facebook)) {echo $this->Facebook->init(); } ?>
</html>