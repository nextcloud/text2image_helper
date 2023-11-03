<?php
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Text2ImageHelper\Service;

use GdImage;
use Exception as BaseException;
use RuntimeException;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use OCP\TextToImage\IManager;
use OCP\TextToImage\Task;

use OCP\IImage;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Files\IAppData;
use OCP\IURLGenerator;
use DateTime;
use OCA\Text2ImageHelper\AppInfo\Application;
use OCA\Text2ImageHelper\Db\PromptMapper;
use OCP\Notification\IManager as INotificationManager;
use OCA\Text2ImageHelper\Db\ImageGenerationMapper;
use OCA\Text2ImageHelper\Db\ImageFileNameMapper;
use OCA\Text2ImageHelper\Db\StaleGenerationMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IL10N;
use OCP\Db\Exception;
use OCP\Files\NotPermittedException;

class Text2ImageHelperService
{
	/**
	 * @var ISimpleFolder|null
	 */
	private ?ISimpleFolder $imageDataFolder = null;

	/**
	 * @param IConfig $config
	 * @param LoggerInterface $logger
	 * @param IManager $textToImageManager
	 * @param string|null $userId
	 * @param PromptMapper $promptMapper
	 * @param ImageGenerationMapper $imageGenerationMapper
	 * @param ImageFileNameMapper $imageFileNameMapper
	 * @param StaleGenerationMapper $staleGenerationMapper
	 * @param IAppData $appData
	 * @param IURLGenerator $urlGenerator
	 * @param INotificationManager $notificationManager
	 * @param IL10N $l10n
	 */
	public function __construct(
		private IConfig $config,
		private LoggerInterface $logger,
		private IManager $textToImageManager,
		private ?string $userId,
		private PromptMapper $promptMapper,
		private ImageGenerationMapper $imageGenerationMapper,
		private ImageFileNameMapper $imageFileNameMapper,
		private StaleGenerationMapper $staleGenerationMapper,
		private IAppData $appData,
		private IURLGenerator $urlGenerator,
		private INotificationManager $notificationManager,
		private IL10N $l10n
	) {
	}

	/**
	 * Process a prompt using ImageProcessingProvider and return a link to the generated image(s)
	 * 
	 * @param string $prompt
	 * @param int $nResults
	 * @param bool $storePrompt
	 * @return array
	 * @throws \Exception
	 * @throws \OCP\TextToImage\Exception\TaskFailureException;
	 */
	public function processPrompt(string $prompt, int $nResults, bool $displayPrompt): array
	{
		if (!$this->textToImageManager->hasProviders()) {
			$this->logger->error('No text to image processing provider available');
			throw new BaseException($this->l10n->t('No text to image processing provider available'));
		}

		// Generate nResults prompts
		$imageGenId = bin2hex(random_bytes(16));
		/** @var Task $promptTask */
		$promptTask = new Task($prompt, Application::APP_ID, $nResults, $this->userId, $imageGenId);

		$this->textToImageManager->runOrScheduleTask($promptTask);
		
		$taskExecuted = false;

		/** @var IImage[]|null $images */
		$images = [];
		$expCompletionTime = new DateTime('now');

		if ($promptTask->getStatus() === Task::STATUS_SUCCESSFUL || $promptTask->getStatus() === Task::STATUS_FAILED) {
			$taskExecuted = true;			            
            $images = $promptTask->getOutputImages();   
		} else {
			$expCompletionTime = $promptTask->getCompletionExpectedAt() ?? $expCompletionTime;
			$this->logger->info('Task scheduled. Expected completion time: ' . $expCompletionTime->format('Y-m-d H:i:s'));
		}

		// Store the image id to the db:      
		$this->imageGenerationMapper->createImageGeneration($imageGenId, $displayPrompt ? $prompt : '', $this->userId ?? '', $expCompletionTime->getTimestamp());

		if ($taskExecuted) {
			$this->storeImages($images, $imageGenId);
		}

		$infoUrl = $this->urlGenerator->linkToRouteAbsolute(
			Application::APP_ID . '.Text2ImageHelper.getGenerationInfo',
			[
				'imageGenId' => $imageGenId,
			]
		);

		$referenceUrl = $this->urlGenerator->linkToRouteAbsolute(
			Application::APP_ID . '.Text2ImageHelper.showGenerationPage',
			[
				'imageGenId' => $imageGenId,
			]
		);

		// Save the prompt to database
		if($this->userId !== null) {
			$this->promptMapper->createPrompt($this->userId, $prompt);
		}		

		return ['url' => $infoUrl, 'reference_url' => $referenceUrl, 'image_gen_id' => $imageGenId, 'prompt' => $prompt];
	}

	/**
	 * @return array
	 * @throws \OCP\DB\Exception
	 */
	public function getPromptHistory(): array
	{
		if ($this->userId === null) {
			return [];
		} else {
			return $this->promptMapper->getPromptsOfUser($this->userId);
		}		
	}

	/**
	 * Save image locally as jpg (to save space)
	 * @param array<IImage>|null $iImages
	 * @param string $imageGenId
	 * @return void
	 */
	public function storeImages(?array $iImages, string $imageGenId): void
	{
		if ($iImages === null || count($iImages) === 0) {
			return;
		}
		try {
			$imageDataFolder = $this->getImageDataFolder();
		} catch (BaseException $e) {
			$this->logger->error('Image save error: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return;
		}

		try {
			$imageGeneration = $this->imageGenerationMapper->getImageGenerationOfImageGenId($imageGenId);
		} catch (Exception | DoesNotExistException | MultipleObjectsReturnedException $e) {
			$this->logger->error('Image save error: image generation not found in db');
			return;
		}


		$quality = 90;
		$n = 0;

		foreach ($iImages as $iImage) {
			$image = $iImage->resource();

			if (!($image instanceof GdImage)) {
				$this->logger->warning('Image save error: could not retrieve image resource');
				continue;
			}

			ob_start();
			imagejpeg($image, null, $quality);
			$jpegData = ob_get_clean();
			unset($image);

			if ($jpegData === false) {
				continue;
			}

			$fileName = strval($imageGenId) . '_' . strval($n++) . '.jpg';

			try {
				$newFile = $imageDataFolder->newFile($fileName);
				$newFile->putContent($jpegData);
			} catch (NotPermittedException | NotFoundException $e) {
				$this->logger->warning('Image save error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
				continue;
			}

			try {
				$this->imageFileNameMapper->createImageFileName($imageGeneration->getId(), $fileName);
			} catch (Exception $e) {
				$this->logger->warning('Image save error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
				continue;
			}

		}
		$this->imageGenerationMapper->setImagesGenerated($imageGenId, true);

		// If the notifications were enabled for this generation, send them now:
		if ($imageGeneration->getNotifyReady()) {
			$this->notifyUser($imageGenId);
		}
		
	}

	/**
	 * Notify user of generation being ready
	 * @param string $imageGenId
	 * @return void
	 */
	 public function notifyUser(string $imageGenId): void {
		
		try {
			$imageGeneration = $this->imageGenerationMapper->getImageGenerationOfImageGenId($imageGenId);
		} catch (Exception | DoesNotExistException | MultipleObjectsReturnedException $e) {
			$this->logger->warning('Generation notification error: image generation not found in db');
			return;
		}

		$this->logger->warning($imageGenId);

		$notification = $this->notificationManager->createNotification();

		$viewAction = $notification->createAction();
		$viewAction->setLabel('view')
			->setLink(Application::APP_ID, 'WEB');

		$deleteAction = $notification->createAction();
		$deleteAction->setLabel('delete')
				->setLink(Application::APP_ID, 'POST');		
		
		$notification->setApp(Application::APP_ID)
			->setUser($imageGeneration->getUserId())
			->setDateTime(new DateTime('now'))
			->setObject('text2image', $imageGenId)
			->setSubject('text2image_helper')
			->setMessage('text2image_helper', ['imageGenId' => $imageGenId, 'prompt' => $imageGeneration->getPrompt()])
			->addAction($deleteAction)
			->addAction($viewAction);
			
		$this->notificationManager->notify($notification);
	 
		return;
	 }

	/**
	 * Get imageDataFolder
	 * @return ISimpleFolder
	 * @throws \Exception
	 */
	public function getImageDataFolder(): ISimpleFolder
	{
		if ($this->imageDataFolder === null) {
			/** @var ISimpleFolder|null $imageFataFolder */
			try {
				$this->imageDataFolder = $this->appData->getFolder(Application::IMAGE_FOLDER);
			} catch (NotFoundException | RuntimeException $e) {
				$this->logger->debug('Image data folder could not be accessed: ' . $e->getMessage(), ['app' => Application::APP_ID]);
				$this->imageDataFolder = null;
			}

			if ($this->imageDataFolder === null) {
				try {
					$this->imageDataFolder = $this->appData->newFolder(Application::IMAGE_FOLDER);
				} catch (NotPermittedException | RuntimeException $e) {
					$this->logger->debug('Image data folder could not be created: '
						. $e->getMessage(), ['app' => Application::APP_ID]);
					throw new Exception('Image data folder could not be created: ' . $e->getMessage());
				}
			}
		}
		return $this->imageDataFolder;
	}

	/**
	 * Get image generation info. 
	 * @param string $imageGenId
	 * @param bool $updateTimestamp
	 * @param string|null $userId
	 * @return array
	 * @throws \Exception
	 */
	public function getGenerationInfo(string $imageGenId, bool $updateTimestamp = true): array
	{
		// Check whether the task has completed:
		try {
			$imageGeneration = $this->imageGenerationMapper->getImageGenerationOfImageGenId($imageGenId);
		} catch (Exception | DoesNotExistException | MultipleObjectsReturnedException $e) {
			if ($e instanceof DoesNotExistException) {
				if ($this->staleGenerationMapper->genIdExists($imageGenId)) {
					throw new BaseException($this->l10n->t('Image generation has been deleted.'),0);
				}				
			}

			$this->logger->debug('Image request error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			// Set error code to one to limit brute force attacks
			throw new BaseException($this->l10n->t('Image generation not found.'),1);
		}
		
		$isOwner = ($imageGeneration->getUserId() === $this->userId);

		if ($imageGeneration->getFailed() === true) {
			throw new BaseException($this->l10n->t('Image generation failed.'), 0);
		}

		if ($imageGeneration->getIsGenerated() === false) {
			// The image is being generated.
			// Return the expected completion time as UTC timestamp
			$completionExpectedAt = $imageGeneration->getExpGenTime();
			return ['processing' => $completionExpectedAt];
		}

		// Prevent the image generation from going stale if it's being viewed
		if ($updateTimestamp) {
			try {
				$this->imageGenerationMapper->touchImageGeneration($imageGenId);
			} catch (Exception $e) {
				$this->logger->warning('Image generation timestamp update failed: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			}
		}

		try {
			if ($isOwner) {
				$fileNameEntities = $this->imageFileNameMapper->getImageFileNamesOfGenerationId($imageGeneration->getId());
			} else {				
				$fileNameEntities = $this->imageFileNameMapper->getVisibleImageFileNamesOfGenerationId($imageGeneration->getId());	
			}
		} catch (Exception $e) {
			$this->logger->warning('Fetching image filenames from db failed: ' . $e->getMessage());
			throw new BaseException($this->l10n->t('Image file names could not be fetched from database'),0);
		}

		$fileIds = [];
		foreach ($fileNameEntities as $fileNameEntity) {
			
			if ($isOwner) {
				$fileIds[] = ['id' => $fileNameEntity->getId(), 'visible' => !$fileNameEntity->getHidden()];
			} else {
				$fileIds[] = ['id' =>$fileNameEntity->getId()];
			}
		}

		return ['files' => $fileIds, 'prompt' => $imageGeneration->getPrompt(), 'image_gen_id' => $imageGenId, 'is_owner' => $isOwner];
	}

	/**
	 * Get image based on imageFileNameId (imageGenId is used to prevent guessing image ids)
	 * @param string $imageGenId
	 * @param int $imageFileNameId
	 * @return array('image' => string, 'content-type' => string)
	 * @throws BaseException
	 */
	public function getImage(string $imageGenId, int $imageFileNameId): ?array
	{
		try {
			$generationId = $this->imageGenerationMapper->getImageGenerationOfImageGenId($imageGenId)->getId();
			$imageFileName = $this->imageFileNameMapper->getImageFileNameOfGenerationId($generationId, $imageFileNameId);
		} catch (Exception | DoesNotExistException | MultipleObjectsReturnedException $e) {
			$this->logger->debug('Image request error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			// Set error code to one for rate limiting (brute force protection)
			throw new BaseException($this->l10n->t('Image request error'),1);
		}

		if ($imageFileName === null) {
			throw new BaseException($this->l10n->t('Image file not found in database'),0);
		}

		// No need to catch here, since we'd be throwing BaseException anyways:
		$imageDataFolder = $this->getImageDataFolder();
		
		// Load image from disk
		try {
			$imageFile = $imageDataFolder->getFile($imageFileName->getFileName());
			$imageContent = $imageFile->getContent();

		} catch (NotFoundException $e) {
			$this->logger->debug('Image file reading failed: ' . $e->getMessage(), ['app' => Application::APP_ID]);

			throw new BaseException($this->l10n->t('Image file not found'),0);
		}

		// Return image content and type
		return [
			'image' => $imageContent,
			'content-type' => ['image/jpeg'],			
		];
	}

	/**
	 * Cancel image generation
	 * @param string $imageGenId
	 * @return void
	 */
	public function cancelGeneration(string $imageGenId): void
	{
		// Get the task if it exists
		try {
			$task = $this->textToImageManager->getUserTasksByApp($this->userId, Application::APP_ID, $imageGenId);
		} catch (Exception $e) {
			$this->logger->warning('Task cancellation failed or it does not exist: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			$task = [];
		}

		if (count($task) > 0) {
			// Cancel the task
			$this->textToImageManager->deleteTask($task[0]);
		}

		// If the generation completed, delete the image file:
		try {
			$imageGeneration = $this->imageGenerationMapper->getImageGenerationOfImageGenId($imageGenId);
		} catch (Exception $e) {
			$this->logger->warning('Image generation not in db: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			$imageGeneration = null;
		}

		if ($imageGeneration) {
			// Make sure the user is associated with the image generation
			if ($imageGeneration->getUserId() !== $this->userId) {
				$this->logger->warning('User attempted deleting another user\'s image generation!', ['app' => Application::APP_ID]);
				return;
			}

			// See if there is a notification associated with this generation:
			$notification = $this->notificationManager->createNotification();
			$notification->setApp(Application::APP_ID)
				->setUser($imageGeneration->getUserId())
				->setObject('text2image', $imageGenId);

			$this->notificationManager->markProcessed($notification);

			if ($imageGeneration->getIsGenerated()) {
				$imageDataFolder = null;
				try {
					$imageDataFolder = $this->getImageDataFolder();
				} catch (BaseException $e) {
					$this->logger->debug('Error deleting image files associated with a generation: ' . $e->getMessage(), ['app' => Application::APP_ID]);
				}
				if ($imageDataFolder !== null) {
					try {
						$fileNames = $this->imageFileNameMapper->getImageFileNamesOfGenerationId($imageGeneration->getId());
					} catch (BaseException $e) {
						$this->logger->debug('No files to delete could be retrieved: ' . $e->getMessage());
						$fileNames = [];
					}

					foreach ($fileNames as $fileName) {
						try {
							$imageFile = $imageDataFolder->getFile($fileName->getFileName());
							$imageFile->delete();
						} catch (NotFoundException $e) {
							$this->logger->debug('Image deletion error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
						}
					}
				}
			}
		}

		// Remove the image generation from the database:
		try {
			$this->imageGenerationMapper->deleteImageGeneration($imageGenId);
		} catch (Exception $e) {
			$this->logger->warning('Deleting image generation db entry failed: ' . $e->getMessage());
		}

		// Add the generation to the stale generation table:
		try {
			$this->staleGenerationMapper->createStaleGeneration($imageGenId);
		} catch (Exception $e) {
			$this->logger->warning('Adding stale generation to db failed: ' . $e->getMessage());
		}

	}

	/**
	 * Hide/show image files of a generation. UserId must match the assigned user of the image generation.
	 * @param string $imageGenId
	 * @param array $fileVisSatusArray
	 * @return void
	 */
	public function setVisibilityOfImageFiles(string $imageGenId, array $fileVisSatusArray): void {
		try {
			$imageGeneration = $this->imageGenerationMapper->getImageGenerationOfImageGenId($imageGenId);
		} catch (Exception | DoesNotExistException | MultipleObjectsReturnedException $e) {
			$this->logger->debug('Image request error : ' . $e->getMessage());
			throw new BaseException('Image generation not found; it may have been cleaned up due to not being viewed for a long time.');
		}

		if ($imageGeneration->getUserId() !== $this->userId) {
			$this->logger->warning('User attempted deleting another user\'s image generation!');
			throw new BaseException('Unauthorized.');
		}
		/** @var array $fileVisStatus */
		foreach ($fileVisSatusArray as $fileVisStatus) {
			try {
				$this->imageFileNameMapper->setFileNameHidden(intval($fileVisStatus['id']), !((bool) $fileVisStatus['visible']));
			} catch (Exception | DoesNotExistException | MultipleObjectsReturnedException $e) {
				$this->logger->error('Error setting image file visibility: ' . $e->getMessage());
				throw new BaseException('Image file or files not found in database');
			}
		}
	}

	/**
	 * Notify when image generation is ready
	 * @param string $imageGenId
	 */
	public function notifyWhenReady(string $imageGenId): void
	{
		try {
			$imageGeneration = $this->imageGenerationMapper->getImageGenerationOfImageGenId($imageGenId);
		} catch (Exception | DoesNotExistException | MultipleObjectsReturnedException $e) {
			$this->logger->debug('Image request error : ' . $e->getMessage());
			throw new BaseException('Image generation not found; it may have been cleaned up due to not being viewed for a long time.');
		}

		if ($imageGeneration->getUserId() !== $this->userId) {
			$this->logger->warning('User attempted enabling notifications of another user\'s image generation!');
			throw new BaseException('Unauthorized.');
		}

		$this->imageGenerationMapper->setNotifyReady($imageGenId, true);

		// Just in case the image generation is already ready, notify the user immediately so that the result is not lost:
		if ($imageGeneration->getIsGenerated()) {
			$this->notifyUser($imageGenId);
		}
	}

	/**
	 * Get raw image page
	 * @param string $imageGenId
	 * @return array
	 */
	public function getRawImagePage(string $imageGenId): array
	{
		$generationInfo = $this->getGenerationInfo($imageGenId, true);

		/** @var array $imageFiles */
		$imageFiles = $generationInfo['files'];

		// Generate a HTML link to each image
		/** @var string[] $links */
		$links = [];
		/** @var array $imageFile */
		foreach ($imageFiles as $imageFile) {
			$links[] = $this->urlGenerator->linkToRouteAbsolute(
				Application::APP_ID . '.Text2ImageHelper.getImage',
				[
					'imageGenId' => $imageGenId,
					'fileNameId' => intval($imageFile['id']),
				]
			);
		}

		// Create a simple http page in the response:
		$body = '<html><body>';
		foreach ($links as $link) {
			$body .= '<img src="' . $link . '" alt="image">';
			$body .= '<br>';
		}
		$body .= '</body></html>';
		return ['body' => $body,
				'headers' => [
					'Content-Type' => ['text/html'],
				],];
	}
}