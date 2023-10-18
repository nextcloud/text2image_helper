<?php
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Text2ImageHelper\Controller;

use OCA\Text2ImageHelper\Service\Text2ImageHelperService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
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
     * @return DataResponse
     */
    public function processPrompt(string $prompt, int $nResults =1): DataResponse
    {
        $result = $this->text2ImageHelperService->processPrompt($prompt, $nResults, $this->userId);
        return new DataResponse($result);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
    public function getPromptHistory(): DataResponse {
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
     * @return DataResponse
     */
    public function getImage(string $imageId): DataResponse {
        
		$image = $this->text2ImageHelperService->getImage($imageId);

		return isset($image['error']) ? new DataResponse($image, Http::STATUS_BAD_REQUEST) : new DataResponse($image);
	}
}
