<?php
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Text2ImageHelper\Service;

use Exception;
use RuntimeException;
use OCA\Text2ImageHelper\AppInfo\Application;
use OCA\Text2ImageHelper\Db\ImageGenerationMapper;
use OCA\Text2ImageHelper\Service\Text2ImageHelperService;
use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Files\NotFoundException;
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
        private Text2ImageHelperService $text2ImageHelperService,
        private IAppData $appData,
        private IConfig $config
    ) {


    }

    /**
     * @param int|null $maxAge
     * @return array
     * @throws Exception
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
            throw new Exception('No idle generations to delete');            
        }

        /** @var ISimpleFolder $imageFataFolder */
        $imageDataFolder = null;
        try {
            $imageDataFolder = $this->text2ImageHelperService->getImageDataFolder();
        } catch (NotFoundException | RuntimeException $e) {
            $this->logger->debug('Image data folder could not be accessed: ' . $e->getMessage(), ['app' => Application::APP_ID]);
            throw new Exception('Image data folder could not be accessed');
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