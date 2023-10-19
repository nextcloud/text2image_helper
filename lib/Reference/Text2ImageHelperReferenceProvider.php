<?php
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\Text2ImageHelper\Reference;

use OC\Collaboration\Reference\ReferenceManager;
use OCP\Collaboration\Reference\Reference;
use OCA\Text2ImageHelper\AppInfo\Application;
use OCA\Text2ImageHelper\Service\Text2ImageHelperService;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\IL10N;
use OCP\IURLGenerator;

class Text2ImageHelperReferenceProvider extends ADiscoverableReferenceProvider 
{
	private const RICH_OBJECT_TYPE = Application::APP_ID . '_image';

	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private ReferenceManager $referenceManager,
		private Text2ImageHelperService $text2ImageHelperService,
		private ?string $userId
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string	{
		return 'text2image_helper';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('AI image generation');		
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int	{
		return 10;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')
		);
	}

	/**
	 * @inheritDoc
	 */
	public function matchReference(string $referenceText): bool {
		return $this->getImageId($referenceText) !== null;		
	}

	/**
	 * @inheritDoc
	 */
	public function resolveReference(string $referenceText): ?IReference
	{
		$imageId = $this->getImageId($referenceText);
		if ($imageId === null) {
			return null;
		}

		$reference = new Reference($referenceText);
		$imageUrl = $this->urlGenerator->linkToRouteAbsolute(
			Application::APP_ID . '.Text2ImageHelper.getImage',
			[
				'imageId' => $imageId,				
			]
		);

		$reference->setImageUrl($imageUrl);
		
		$richObjectInfo = ['imageId' => $imageId];
		$reference->setRichObject(
			self::RICH_OBJECT_TYPE,
			$richObjectInfo,
		);
		return $reference;
		
	}

	/**
	 * @param string $url
	 * @return array|null
	 */
	private function getImageId(string $url): ?string {
		$start = $this->urlGenerator->getAbsoluteURL('/apps/' . Application::APP_ID);
		$startIndex = $this->urlGenerator->getAbsoluteURL('/index.php/apps/' . Application::APP_ID);
		
		// link example: https://nextcloud.local/index.php/apps/text2image_helper/i/c3b80f5a758d2ba5ecae2531764c4a0c
		preg_match('/^' . preg_quote($start, '/') . '\/i\/([0-9a-f]+)$/i', $url, $matches);
		if (count($matches) > 1) {
			return $matches[1];
		}

		preg_match('/^' . preg_quote($startIndex, '/') . '\/i\/([0-9a-f]+)$/i', $url, $matches);
		if (count($matches) > 1) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getCachePrefix(string $referenceId): string {
		return $this->userId ?? '';
	}

	/**
	 * @inheritDoc
	 */
	public function getCacheKey(string $referenceId): ?string
	{
		return $referenceId;
	}

	/**
	 * @param string $userId
	 * @return void
	 */
	public function invalidateUserCache(string $userId): void {
		$this->referenceManager->invalidateCache($userId);
	}

	
}