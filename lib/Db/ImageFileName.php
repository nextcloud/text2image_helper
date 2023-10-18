<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Text2ImageHelper\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getImageId()
 * @method void setImageId(int $imageId)
 * @method string getFileName()
 * @method void setFileName(string $fileName)
 */
class ImageFileName extends Entity implements \JsonSerializable
{

	/** @var string */
	protected $imageId;
	/** @var string */
	protected $fileName;

	public function __construct()
	{
		$this->addType('image_id', 'string');
		$this->addType('file_name', 'string');
	}

	#[\ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return [
			'id' => $this->id,
			'image_id' => $this->imageId,
			'file_name' => $this->fileName,
		];
	}
}