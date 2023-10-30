<?php
use OCA\Text2ImageHelper\AppInfo\Application;
use OCP\Util;
// Load the dialog javascript
Util::addScript(Application::APP_ID, Application::APP_ID . '-dialog');
?>

<div id="text2image_helper_dialog"></div>