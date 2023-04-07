<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Stefan Petersen <stefan@openelp.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\ClientManager\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
	public const APP_ID = 'clientmanager';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}
}
