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
use OCA\Text2ImageHelper\Db\ImageGenerationMapper;
use Psr\Log\LoggerInterface;

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
        $this->logger->warning("TextToImageEvent received");

        if ($event instanceof TaskSuccessfulEvent) {

            //TODO: For now only support generating/receiving single images
            /** @var IImage $image */
            $image = $event->getTask()->getOutputImages()[0];
            //TODO: Notify user of success
            $this->text2ImageService->storeImage($image, $event->getTask()->getIdentifier());
        }

        if ($event instanceof TaskFailedEvent) {
            $this->logger->warning('Image generation task failed: ' . $event->getTask()->getIdentifier());
            //TODO: Notify user of error
            //$error = $event->getErrorMessage();
            //$userId = $event->getTask()->getUserId();            
            $this->imageGenerationMapper->setImageGenerationFileName($event->getTask()->getIdentifier(), '');
            $this->imageGenerationMapper->setImageGenerated($event->getTask()->getIdentifier(), true);
        }
    }
}