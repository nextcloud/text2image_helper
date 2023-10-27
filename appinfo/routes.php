<?php
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\GptFreePrompt\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
	'routes' => [
		['name' => 'Text2ImageHelper#processPrompt', 'url' => '/process_prompt', 'verb' => 'POST'],			
		['name' => 'Text2ImageHelper#getPromptHistory', 'url' => '/prompt_history', 'verb' => 'GET'],
		['name' => 'Text2ImageHelper#getGenerationInfo', 'url' => '/i/{imageGenId}', 'verb' => 'GET'],
		['name' => 'Text2ImageHelper#getImage', 'url' => '/g/{imageGenId}/{fileNameId}', 'verb' => 'GET'],
		['name' => 'Text2ImageHelper#cancelGeneration', 'url' => '/cancel_generation', 'verb' => 'POST'],
		['name' => 'Text2ImageHelper#setVisibilityOfImageFiles', 'url' => '/v/{imageGenId}', 'verb' => 'POST'],
		['name' => 'Text2ImageHelper#notifyWhenReady', 'url' => '/n/{imageGenId}', 'verb' => 'POST'],

		['name' => 'config#setAdminConfig', 'url' => '/admin-config', 'verb' => 'PUT'],
	],
];
