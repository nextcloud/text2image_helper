<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Text2ImageHelper\Cron;

use OCA\Text2ImageHelper\Db\ImageGenerationMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;
use OCA\Text2ImageHelper\Service\CleanUpService;

class CleanupImageGenerations extends TimedJob
{

	public function __construct(
		ITimeFactory $time,
		private ImageGenerationMapper $imageGenerationMapper,
		private LoggerInterface $logger,
		private CleanUpService $cleanUpService
	) {
		parent::__construct($time);
		$this->setInterval(60 * 60 * 24);
	}

	protected function run($argument): void
	{
		$this->logger->debug('Run cleanup job for image generations');

		$this->cleanUpService->cleanupGenerationsAndFiles();

		return;
	}
}