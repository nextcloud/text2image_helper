<?php
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Text2ImageHelper\Controller;

use OCA\Text2ImageHelper\Service\Text2ImageHelperService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\IRequest;

class Text2ImageHelperController extends Controller
{
	public function __construct(
		string $appName,
		IRequest $request,
		private Text2ImageHelperService $text2ImageHelperService,
		private ?string $userId
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string|null $prompt
	 * @param int $nResults
	 * @param bool $displayPrompt
	 * @return DataResponse
	 */
	public function processPrompt(string $prompt, int $nResults = 1, ?bool $displayPrompt = false): DataResponse
	{
		$displayPrompt === null ? false : $displayPrompt;
		$result = $this->text2ImageHelperService->processPrompt($prompt, $nResults, $this->userId, $displayPrompt);
		
		if (isset($result['error'])) {
			return new DataResponse($result, Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($result);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */
	public function getPromptHistory(): DataResponse
	{
		$response = $this->text2ImageHelperService->getPromptHistory($this->userId);
		if (isset($response['error'])) {
			return new DataResponse($response, Http::STATUS_BAD_REQUEST);
		}
		return new DataResponse($response);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $imageId
	 * @return DataDisplayResponse | DataResponse
	 */
	public function getImage(string $imageId): DataDisplayResponse | DataResponse
	{

		$result = $this->text2ImageHelperService->getImage($imageId, true);

		if (isset($result['error']) || $result === null) {
			return new DataResponse($result, Http::STATUS_NOT_FOUND);
		}

		if (isset($result['processing'])) {
			return new DataResponse($result, Http::STATUS_OK);
		}

		return new DataDisplayResponse(
			$result['image'],
			Http::STATUS_OK,
			['Content-Type' => $result['headers']['Content-Type'][0] ?? 'image/jpeg']
		);
	}

	/**
	 * Cancel image generation
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $imageId
	 * @return DataResponse
	 */
	public function cancelGeneration(string $imageId): DataResponse
	{
		// We also supply the userId to prevent users from cancelling other users' image generations
		$this->text2ImageHelperService->cancelGeneration($imageId, $this->userId);
		return new DataResponse('success', Http::STATUS_OK);
	}
}
