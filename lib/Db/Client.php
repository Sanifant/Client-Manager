<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Stefan Petersen <stefan@openelp.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\ClientManager\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

/**
 * @method getId(): int
 * @method getTitle(): string
 * @method setTitle(string $title): void
 * @method getContent(): string
 * @method setContent(string $content): void
 * @method getUserId(): string
 * @method setUserId(string $userId): void
 */
class Client extends Entity implements JsonSerializable {
	protected string $name = '';
	protected string $content = '';
	protected string $Id = '';

	public function jsonSerialize(): array {
		$client = [
			'id' => $this->id,
			'name' => $this->name,
			'content' => $this->content
		];

		for ($i=0; $i<2; $i++) { 
			//array_push($client, "Key".$i, "Wert");

			$client["key".$i] = "Value";
		}

		return $client;
	}
}
