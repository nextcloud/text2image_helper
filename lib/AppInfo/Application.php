<?php

// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Text2ImageHelper\AppInfo;

use OCA\Text2ImageHelper\Listener\Text2ImageHelperReferenceListener;
use OCA\Text2ImageHelper\Listener\Text2ImageResultListener;
use OCA\Text2ImageHelper\Notification\Text2ImageHelperNotifier;
use OCA\Text2ImageHelper\Reference\Text2ImageHelperReferenceProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\TextToImage\Events\TaskFailedEvent;
use OCP\TextToImage\Events\TaskSuccessfulEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'text2image_helper';

	public const MAX_STORED_PROMPTS_PER_USER = 5;
	public const DEFAULT_MAX_GENERATION_IDLE_TIME = 60 * 60 * 24 * 90; // 90 days
	public const IMAGE_FOLDER = 'generated_images';

	public function __construct() {
		parent::__construct(self::APP_ID);

	}

	public function register(IRegistrationContext $context): void {
		$context->registerReferenceProvider(Text2ImageHelperReferenceProvider::class);
		$context->registerEventListener(RenderReferenceEvent::class, Text2ImageHelperReferenceListener::class);
		$context->registerEventListener(TaskSuccessfulEvent::class, Text2ImageResultListener::class);
		$context->registerEventListener(TaskFailedEvent::class, Text2ImageResultListener::class);
		$context->registerNotifierService(Text2ImageHelperNotifier::class);
	}

	public function boot(IBootContext $context): void {
	}

}
