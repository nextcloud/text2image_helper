<?php
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Text2ImageHelper\Controller;

use Exception;
use OCA\Text2ImageHelper\Service\Text2ImageHelperService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use OCA\Text2ImageHelper\AppInfo\Application;

class Text2ImageHelperController extends Controller
{
	public function __construct(
		string $appName,
		IRequest $request,
		private Text2ImageHelperService $text2ImageHelperService,
		private IInitialState $initialStateService,
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
		$nResults = min(10, max(1, $nResults));
		$result = $this->text2ImageHelperService->processPrompt($prompt, $nResults, $displayPrompt);
		
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
		try {
			$response = $this->text2ImageHelperService->getPromptHistory($this->userId);
		} catch (Exception $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_BAD_REQUEST);
		}
		
		return new DataResponse($response);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $imageGenId
	 * @param int $fileNameId
	 * @return DataDisplayResponse | DataResponse
	 */
	public function getImage(string $imageGenId, int $fileNameId): DataDisplayResponse | DataResponse
	{

		try {
			$result = $this->text2ImageHelperService->getImage($imageGenId, $fileNameId);
		} catch (Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
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
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * 
	 * @param string $imageGenId
	 * @return DataResponse
	 */
	public function getGenerationInfo(string $imageGenId): DataResponse
	{
		try {
			$result = $this->text2ImageHelperService->getGenerationInfo($imageGenId,true);
		} catch (Exception $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_NOT_FOUND);
		}
		
		return new DataResponse($result, Http::STATUS_OK);
	}	

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * 
	 * @param string $imageGenId
	 * @param array $fileVisStatusArray
	 */
	public function setVisibilityOfImageFiles(string $imageGenId, array $fileVisStatusArray): DataResponse
	{	
		if (count($fileVisStatusArray)<1) {
			return new DataResponse('File visibility array empty', Http::STATUS_BAD_REQUEST);
		}

		try {
			$this->text2ImageHelperService->setVisibilityOfImageFiles($imageGenId, $fileVisStatusArray);
		} catch (Exception $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_NOT_FOUND);
		}
		
		return new DataResponse('success', Http::STATUS_OK);
	}

	/**
	 * Notify when image generation is ready
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function notifyWhenReady(string $imageGenId): DataResponse
	{
		try {
			$this->text2ImageHelperService->notifyWhenReady($imageGenId);
		} catch (Exception $e) {
			return new DataResponse($e->getMessage(), Http::STATUS_BAD_REQUEST);
		}
		return new DataResponse('success', Http::STATUS_OK);
	}
	/**
	 * Cancel image generation
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $imageGenId
	 * @return DataResponse
	 */
	public function cancelGeneration(string $imageGenId): DataResponse
	{
		$this->text2ImageHelperService->cancelGeneration($imageGenId);
		return new DataResponse('success', Http::STATUS_OK);
	}

	/**
	 * Show visibility dialog
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string|null $imageGenId
	 * @return TemplateResponse
	 */
	public function showGenerationPage(?string $imageGenId, ?bool $forceEditMode = false): TemplateResponse
	{
		if ($forceEditMode === null) {
			$forceEditMode = false;
		}
		if ($imageGenId === null) {
			return new TemplateResponse(Application::APP_ID, 'generationPage', ['imageGenId' => '']);
		}
	
		$this->initialStateService->provideInitialState('generation-page-inputs', ['image_gen_id' => $imageGenId, 'force_edit_mode' => $forceEditMode]);
	
		return new TemplateResponse(Application::APP_ID, 'generationPage');
	}
}
