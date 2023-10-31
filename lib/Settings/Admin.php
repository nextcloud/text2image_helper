<?php
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\Text2ImageHelper\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCA\Text2ImageHelper\AppInfo\Application;

class Admin implements ISettings
{

	public function __construct(
		private IConfig $config,
		private IInitialState $initialStateService
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse
	{
		$maxGenerationIdleTime = $this->config->getAppValue(
			Application::APP_ID,
			'max_generation_idle_time',
			Application::DEFAULT_MAX_GENERATION_IDLE_TIME
		) ?: Application::DEFAULT_MAX_GENERATION_IDLE_TIME;

		
		$adminConfig = [			
			'max_generation_idle_time' => $maxGenerationIdleTime,			
		];

		$this->initialStateService->provideInitialState('admin-config', $adminConfig);

		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	public function getSection(): string
	{
		return 'ai';
	}

	public function getPriority(): int
	{
		return 10;
	}
}