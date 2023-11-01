<?php

namespace OCA\Text2ImageHelper\Notification;

use OCP\IURLGenerator;
use OCA\Text2ImageHelper\AppInfo\Application;
use OCP\Notification\IAction;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\IL10N;
use OCP\Util;

class Text2ImageHelperNotifier implements INotifier {
 

    public function __construct(private IURLGenerator $urlGenerator,
                                private IL10N $il10n)
    {

    }

    /**
     * Identifier of the notifier, only use [a-z0-9_]
     * @return string
     */
    public function getID(): string {
        return 'text2image_helper';
    }

    /**
     * Human readable name describing the notifier
     * @return string
     */
    public function getName(): string {
        return 'Text2Image Generation';
    }

    /**
     * @param INotification $notification
     * @param string $languageCode The code of the language that should be used to prepare the notification
     */
    public function prepare(INotification $notification, string $languageCode): INotification {
        if ($notification->getApp() !== Application::APP_ID) {
            // Not this app
            throw new \InvalidArgumentException();
        }
        
        $notification->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('text2image_helper', 'app-dark.svg')));

        $parameters = $notification->getMessageParameters();
       
        $subject = $this->il10n->t('Image generation ready');
        $message = '"' . strval($parameters['prompt']) . '"'; 
        $notification->setParsedSubject($subject);
        $notification->setParsedMessage($message);
        
        foreach ($notification->getActions() as $action) {
            switch ($action->getLabel()) {
                case 'view':
                    $action->setParsedLabel($this->il10n->t('View'))
                        ->setLink($this->urlGenerator->linkToRouteAbsolute(Application::APP_ID . '.Text2ImageHelper.showGenerationPage' , ['imageGenId' => $parameters['imageGenId'], 'forceEditMode' => true]), 'WEB');
                    break;
                case 'delete':
                    $action->setParsedLabel($this->il10n->t('Delete'))
                        ->setLink($this->urlGenerator->linkToRouteAbsolute(Application::APP_ID . '.Text2ImageHelper.cancelGeneration', ['imageGenId' => $parameters['imageGenId']]), 'POST');
                    break;
            }

            $notification->addParsedAction($action);
        }
        return $notification;
    }
}