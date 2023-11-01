<?php
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Text2ImageHelper\Controller;

use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;

use OCA\Text2ImageHelper\AppInfo\Application;

class ConfigController extends Controller
{

	public function __construct(
		string $appName,
		IRequest $request,
		private IConfig $config,
		private ?string $userId
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Set admin config values
	 *
	 * @param array<string> $values key/value pairs to store in app config
	 * @return DataResponse
	 */
	public function setAdminConfig(array $values): DataResponse
	{
		foreach ($values as $key => $value) {
			switch ($key) {
				case 'max_generation_idle_time':
					$value = (int) $value;
					if ($value < 1) {
						return new DataResponse(['message' => 'Invalid value for max_generation_idle_time'], Http::STATUS_BAD_REQUEST);
					}
					$value = strval($value);
					break;
				default:
					return new DataResponse(['message' => 'Invalid config key'], Http::STATUS_BAD_REQUEST);
			}

			$this->config->setAppValue(Application::APP_ID, $key, $value);
		}
		return new DataResponse(1);
	}
}