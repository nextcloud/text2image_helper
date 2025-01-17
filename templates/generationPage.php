<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCA\Text2ImageHelper\AppInfo\Application;
use OCP\Util;

// Load the dialog javascript
Util::addScript(Application::APP_ID, Application::APP_ID . '-generationPage');
?>

<div id="text2image_helper_generation_page"></div>