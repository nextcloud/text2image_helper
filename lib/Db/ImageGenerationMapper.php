<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Text2ImageHelper\Db;

use DateTime;
use OCA\Text2ImageHelper\AppInfo\Application;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class ImageGenerationMapper extends QBMapper
{
	public function __construct(IDBConnection $db)
	{
		parent::__construct($db, 't2ih_generations', ImageGeneration::class);
	}

	/**
	 * @param string $imageId
	 * @return array|Entity
	 * @throws Exception
	 */
	public function getImageGenerationsOfImage(string $imageId): array
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('image_id', $qb->createNamedParameter($imageId, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntities($qb);
	}

	/**
	 * @param string $imageId
	 * @param int $fileNameId
	 * @return ImageGeneration
	 * @throws Exception
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 */
	public function getImageGenerationOfImageId(string $imageId): ImageGeneration
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('image_id', $qb->createNamedParameter($imageId, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntity($qb);
	}

	/**
	 * @param string $imageId
	 * @param string $fileName
	 * @param string $prompt
	 * @return ImageGeneration
	 * @throws Exception
	 */
	public function createImageGeneration(string $imageId, string $fileName, string $prompt = ''): ImageGeneration
	{
		$imageGeneration = new ImageGeneration();
		$imageGeneration->setImageId($imageId);
		$imageGeneration->setFileName($fileName);
		$imageGeneration->setTimestamp((new DateTime())->getTimestamp());
		$imageGeneration->setPrompt($prompt);
		return $this->insert($imageGeneration);
	}

	/**
	 * Touch timestamp of image generation
	 * @param string $imageId
	 * @return int
	 * @throws Exception
	 */
	public function touchImageGeneration(string $imageId): int
	{
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('ts', $qb->createNamedParameter((new DateTime())->getTimestamp(), IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->eq('image_id', $qb->createNamedParameter($imageId, IQueryBuilder::PARAM_STR))
			);
		$count = $qb->executeStatement();
		$qb->resetQueryParts();
		return $count;
	}

	/**
	 * @param string $imageId
	 * @return void
	 * @throws Exception
	 */
	public function deleteImageGenerations(string $imageId): void
	{
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('image_id', $qb->createNamedParameter($imageId, IQueryBuilder::PARAM_STR))
			);
		$qb->executeStatement();
		$qb->resetQueryParts();
	}

	/**
	 * @param int $maxAge
	 * @return array # list of file names
	 * @throws Exception
	 */
	public function cleanupFileNames(int $maxAge = Application::DEFAULT_MAX_IMAGE_IDLE_TIME): array
	{
		$ts = (new DateTime())->getTimestamp();
		$maxTimestamp = $ts - $maxAge;

		$qb = $this->db->getQueryBuilder();

        $qb->select('id')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->lt('timestamp', $qb->createNamedParameter($maxTimestamp, IQueryBuilder::PARAM_INT))
            );

        $fileNames = $this->findEntities($qb);
        $fileNames = array_map(function($fileName) {
            return $fileName->getFileName();
        }, $fileNames);

        # Delete the database entries
        $qb->resetQueryParts();
        $qb->delete($this->getTableName())
            ->where(
                $qb->expr()->lt('timestamp', $qb->createNamedParameter($maxTimestamp, IQueryBuilder::PARAM_INT))
            );
        $qb->executeStatement();
        $qb->resetQueryParts();

		return $fileNames;
	}
}