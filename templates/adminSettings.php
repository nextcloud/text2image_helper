<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$appId = OCA\Text2ImageHelper\AppInfo\Application::APP_ID;
\OCP\Util::addScript($appId, $appId . '-adminSettings');
?>

<div id="text2image_helper_prefs"></div>
