<?php
namespace OCA\Text2ImageHelper\Listener;

use OCA\Text2ImageHelper\AppInfo\Application;
use OCA\Text2ImageHelper\Service\Text2ImageHelperService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\TextToImage\Events\AbstractTextToImageEvent;
use OCP\TextToImage\Events\TaskSuccessfulEvent;
use OCP\TextToImage\Events\TaskFailedEvent;
use OCP\IImage;

class Text2ImageResultListener implements IEventListener {
    /**
     * Constructor
     * @param Text2ImageHelperService $text2ImageService
     */
    public function __construct(
        private Text2ImageHelperService $text2ImageService
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

        if ($event instanceof TaskSuccessfulEvent) {
            /** @var IImage $image */
            $image = $event->getTask()->getOutputImage();
            
            $this->text2ImageService->storeImage($image, $event->getTask()->getIdentifier());
        }

        if ($event instanceof TaskFailedEvent) {
            $error = $event->getErrorMessage();
            $userId = $event->getTask()->getUserId();
            // TODO: Notify relevant user about failure
        }
    }
}