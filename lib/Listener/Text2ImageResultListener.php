<?php

namespace OCA\Text2ImageHelper\Listener;

use OCA\Text2ImageHelper\AppInfo\Application;
use OCA\Text2ImageHelper\Db\ImageGenerationMapper;
use OCA\Text2ImageHelper\Service\Text2ImageHelperService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IImage;
use OCP\TextToImage\Events\AbstractTextToImageEvent;
use OCP\TextToImage\Events\TaskFailedEvent;
use OCP\TextToImage\Events\TaskSuccessfulEvent;
use Psr\Log\LoggerInterface;

/**
 * @implements IEventListener<AbstractTextToImageEvent>
 */
class Text2ImageResultListener implements IEventListener {
	/**
	 * Constructor
	 * @param Text2ImageHelperService $text2ImageService
	 * @param ImageGenerationMapper $imageGenerationMapper
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		private Text2ImageHelperService $text2ImageService,
		private ImageGenerationMapper $imageGenerationMapper,
		private LoggerInterface $logger
	) {
	}

	/**
	 * @param Event $event
	 * @return void
	 */
	public function handle(Event $event): void {
		if (!$event instanceof AbstractTextToImageEvent || $event->getTask()->getAppId() !== Application::APP_ID) {
			return;
		}
		$this->logger->debug('TextToImageEvent received');

		$imageGenId = $event->getTask()->getIdentifier();

		if ($imageGenId === null) {
			$this->logger->warning('Image generation task has no identifier');
			return;
		}

		if ($event instanceof TaskSuccessfulEvent) {
			$this->logger->debug('TextToImageEvent succeeded');
			/** @var IImage $image */

			$images = $event->getTask()->getOutputImages();

			$this->text2ImageService->storeImages($images, $imageGenId);
		}

		if ($event instanceof TaskFailedEvent) {


			$this->logger->warning('Image generation task failed: ' . $imageGenId);
			//TODO: Notify user of error
			//$error = $event->getErrorMessage();
			//$userId = $event->getTask()->getUserId();
			$this->imageGenerationMapper->setFailed($imageGenId, true);
		}
	}
}
