<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Text2ImageHelper\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getImageId()
 * @method void setImageId(string $imageId)
 * @method string getFileName()
 * @method string getPrompt()
 * @method void setPrompt(string $prompt)
 * @method void setFileName(string $fileName)
 * @method void setTimestamp(int $timestamp)
 * @method int getTimestamp()
 */
class ImageGeneration extends Entity implements \JsonSerializable
{

	/** @var string */
	protected $imageId;
	/** @var string */
	protected $fileName;
	/** @var string */
	protected $prompt;
	/** @var int */
	protected $timestamp;

	public function __construct()
	{
		$this->addType('image_id', 'string');
		$this->addType('file_name', 'string');
		$this->addType('prompt', 'string');
		$this->addType('timestamp', 'int');
	}

	#[\ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return [
			'id' => $this->id,
			'image_id' => $this->imageId,
			'file_name' => $this->fileName,
			'prompt' => $this->prompt,
			'timestamp' => $this->timestamp,
		];
	}
}