<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Stefan Petersen <stefan@openelp.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\ClientManager\Controller;

use Exception;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Services\IInitialState;
use OCP\Constants;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IServerContainer;
use OCP\IURLGenerator;
use OCP\PreConditionNotMetException;
use Psr\Log\LoggerInterface;
use Throwable;
use OCA\ClientManager\Service\ImageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

use OCA\ClientManager\AppInfo\Application;
use OCA\ClientManager\Db\Client;
use OCA\ClientManager\Service\ClientService;

class PageController extends Controller {

	public const FIXED_GIF_SIZE_CONFIG_KEY = 'fixed_gif_size';

	public const CONFIG_KEYS = [
		self::FIXED_GIF_SIZE_CONFIG_KEY,
	];

	/**
	 * @var IInitialState
	 */
	private $initialStateService;
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var string|null
	 */
	private $userId;

	private ClientService $service;

	public function __construct(string $appName,
								IRequest $request,
								ClientService $clientService,
								IInitialState $initialStateService,
								IConfig $config,
								?string $userId) {
		parent::__construct($appName, $request);
		$this->initialStateService = $initialStateService;
		$this->config = $config;
		$this->userId = $userId;
		$this->service = $clientService;
	}

	/**
	 * This returns the template of the main app's page
	 * It adds some initialState data (file list and fixed_gif_size config value)
	 * and also provide some data to the template (app version)
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function mainPage(): TemplateResponse {
		$clients = $this->service->findAll($this->userId);
		$fixedGifSize = $this->config->getUserValue($this->userId, Application::APP_ID, self::FIXED_GIF_SIZE_CONFIG_KEY);
		$myInitialState = [
			'userid' => $userId,
			'clientList' => $clients,
			self::FIXED_GIF_SIZE_CONFIG_KEY => $fixedGifSize,
		];
		$this->initialStateService->provideInitialState('tutorial_initial_state', $myInitialState);

		$appVersion = $this->config->getAppValue(Application::APP_ID, 'installed_version');
		return new TemplateResponse(
			Application::APP_ID,
			'main',
			[
				'app_version' => $appVersion,
			]
		);
	}

	/**
	 * Get the names of files stored in apps/my_app/img/gifs/
	 *
	 * @return array
	 */
	private function getGifFilenameList(): array {
		$path = dirname(__DIR__, 2) . '/img/gifs';
		$names = array_filter(scandir($path), static function ($name) {
			return $name !== '.' && $name !== '..';
		});
		return array_values($names);
	}

	/**
	 * This is an API endpoint to set a user config value
	 * It returns a simple DataResponse: a message to be displayed
	 *
	 * @NoAdminRequired
	 *
	 * @param string $key
	 * @param string $value
	 * @return DataResponse
	 * @throws PreConditionNotMetException
	 */
	public function saveConfig(string $key, string $value): DataResponse {
		if (in_array($key, self::CONFIG_KEYS, true)) {
			$this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
			return new DataResponse([
				'message' => 'Everything went fine',
			]);
		}
		return new DataResponse([
			'error_message' => 'Invalid config key',
		], Http::STATUS_FORBIDDEN);
	}
}