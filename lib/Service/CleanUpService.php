<?php
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Text2ImageHelper\Service;

use Exception;
use OCA\Text2ImageHelper\AppInfo\Application;
use OCA\Text2ImageHelper\Db\ImageGenerationMapper;
use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFolder;
use Psr\Log\LoggerInterface;
use OCP\IConfig;


/**
 * Service to make requests to OpenAI REST API
 */
class CleanUpService
{

    public function __construct(
        private LoggerInterface $logger,
        private ImageGenerationMapper $imageGenerationMapper,
        private IAppData $appData,
        private IConfig $config
    ) {


    }

    /**
     * @param int|null $maxAge
     * @return array
     */
    public function cleanupGenerationsAndFiles(?int $maxAge = null): array
    {
        if ($maxAge === null) {
            $maxAge = $this->config->getUserValue(
                Application::APP_ID,
                'max_generation_idle_time',
                Application::DEFAULT_MAX_GENERATION_IDLE_TIME
            ) ?: Application::DEFAULT_MAX_GENERATION_IDLE_TIME;
        }
        $cleanedUp = $this->imageGenerationMapper->cleanupImageGenerations($maxAge);

        if ($cleanedUp['deleted_generations'] === 0) {
            $this->logger->debug('No idle generations to delete');
            return ['error' => 'No idle generations to delete.'];
        }

        try {
            $directoryList = $this->appData->getDirectoryListing();
        } catch (Exception $e) {
            $this->logger->debug('Image data folder could not be listed: ' . $e->getMessage(), ['app' => Application::APP_ID]);
            return ['error' => 'Image data folder could not be listed.'];
        }
        /** @var ISimpleFolder $imageFataFolder */
        $imageDataFolder = null;
        foreach ($directoryList as $directory) {
            if ($directory->getName() === Application::IMAGE_FOLDER) {
                $imageDataFolder = $directory;
                break;
            }
        }

        if ($imageDataFolder === null) {
            $e_msg = 'Deleted ' . $cleanedUp['deleted_generations'] . ' idle generations, but could not delete 
            idle generation associated files: image data folder could not be accessed';
            $this->logger->warning($e_msg);
            return ['error' => $e_msg];
        }

        $deletedFiles = 0;
        $deletionErrors = 0;
        foreach ($cleanedUp['file_names'] as $fileName) {
            try {
                $imageDataFolder->getFile($fileName)->delete();
                $deletedFiles++;
            } catch (Exception $e) {
                $this->logger->debug('Image file could not be deleted: ' . $e->getMessage(), ['app' => Application::APP_ID]);
                $deletionErrors++;
            }
        }

        $this->logger->debug('Deleted ' . $deletedFiles . ' files associated with ' . $cleanedUp['deleted_generations'] .
            ' idle generations. Failed to delete ' . $deletionErrors . ' files.');

        return ['deleted_files' => $deletedFiles, 'file_deletion_errors' => $deletionErrors, 'deleted_generations' => $cleanedUp['deleted_generations']];

    }


}