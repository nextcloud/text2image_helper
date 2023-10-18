<?php
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Text2ImageHelper\Service;

use Exception;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use OCP\TextToImage\IManager;
use OCP\TextToImage\Task;
use OCP\IImage;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Files\IAppData;

use OCA\Text2ImageHelper\AppInfo\Application;
use OCA\Text2ImageHelper\Db\PromptMapper;
use OCA\Text2ImageHelper\Db\ImageFileNameMapper;

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
     * @param ImageFileNameMapper $imageFileNameMapper
     * 
     */
    public function __construct(
        private IConfig $config,
        private LoggerInterface $logger,
        private IManager $textToImageManager,
        private ?string $userId,
        private PromptMapper $promptMapper,
        private ImageFileNameMapper $imageFileNameMapper,
        private IAppData $appData,
    ) {
    }
    
    /**
     * Process a prompt using ImageProcessingProvider and return nResults generated image links
     * 
     * For now just use a filler image returned by the ImageProcessingProvider
     */
    public function processPrompt(string $prompt, int $nResults, string $userId): array
    {
        
        if (!$this->textToImageManager->hasProviders()) {
            $this->logger->error('No text to image processing provider available');
            return [];
        }
        

        $result = [];
        # Generate nResults prompts
        for ($i = 0; $i < $nResults; $i++) {
            $imageId = (string) bin2hex(random_bytes(16));
            $promptTask = new Task($prompt, Application::APP_ID , $this->userId, $imageId);
            $this->textToImageManager->scheduleTask($promptTask);
            # Store the image id to the db:
            $this->imageFileNameMapper->createImageFileName($imageId, $imageId.'.jpg');
        }

        # Save the prompt to database
        $this->promptMapper->createPrompt($userId, $prompt);

        return $result;
    }

    /**
	 * @param string $userId
	 * @return array
	 * @throws \OCP\DB\Exception
	 */
	public function getPromptHistory(string $userId): array {
		return $this->promptMapper->getPromptsOfUser($userId);
	}

    /**
	 * Save image locally as jpg (to save space)
	 * @param IImage $iImage
     * @param string $imageId
     * @return void
	 */
	public function storeImage(IImage $iImage,string $imageId): void
	{
		$image = $iImage->resource();
		$imageDataFolder = $this->getImageDataFolder();

		if ($imageDataFolder === null || $image === false) {
            $this->logger->error('Image save error: could not retrieve folder or image resource');
			return;
		}

		// Generate the jpg image
		$quality = 90;
		ob_start();
		imagejpeg($image, null, $quality);
		$jpegData = ob_get_clean();
		unset($image); // Doesn't immediately destroy the image resource in php <8.0

		if($jpegData === false) {
			return;
		}

		try {
			$newFile = $imageDataFolder->newFile($imageId . '.jpg');
			$newFile->putContent($jpegData);
		} catch (Exception $e) {
			$this->logger->debug('Image save error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return;
		}
	}

	/**
	 * Get imageDataFolder
	 * @return ISimpleFolder
	 */
	private function getImageDataFolder(): ?ISimpleFolder
	{
		if ($this->imageDataFolder === null) {
			try {
				$directoryList = $this->appData->getDirectoryListing();
			} catch (Exception $e) {
				$this->logger->debug('Image data folder could not be listed: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			}

			foreach ($directoryList as $directory) {
				if ($directory->getName() === Application::IMAGE_FOLDER) {
					$this->imageDataFolder = $directory;
					break;
				}
			}

			if ($this->imageDataFolder === null) {
				try {
					$imageDataFolder = $this->appData->newFolder(Application::IMAGE_FOLDER);
				} catch (Exception $e) {
					$this->logger->debug('Image data folder could not be created: '
						. $e->getMessage(), ['app' => Application::APP_ID]);
					return null;
				}
			}
		}
		return $this->imageDataFolder;
	}
    /**
     * Get image based on imageId
     * @param string $imageId
     * @return array|null
     */
    public function getImage(string $imageId): ?array
    {        
        $imageDataFolder = $this->getImageDataFolder();
        if ($imageDataFolder === null) {
            return null;
        }

        try {
            $fileNameEntry = $this->imageFileNameMapper->getImageFileNameOfImageId($imageId);
        } catch (Exception $e) {
            $this->logger->debug('Image request error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
            return null;
        }

        // Load image from disk
        try {
            $imageFile = $imageDataFolder->getFile($fileNameEntry->getFileName());
            $imageContent = $imageFile->getContent();
            // Return image content and headers
            return [
                'body' => $imageContent,
                'headers' => [
                    'Content-Type' => ['image/jpeg'],
                ],
            ];
        } catch (Exception $e) {
            $this->logger->debug('Image request error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
        }
        return null;
    }
}