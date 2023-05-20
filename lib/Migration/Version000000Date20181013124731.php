<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Stefan Petersen <stefan@openelp.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\ClientManager\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version000000Date20181013124731 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('clientmanager_lists')) {
			$table = $schema->createTable('clientmanager_lists');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('listName', 'string', [
				'notnull' => true,
				'length' => 200
			]);
			$table->addColumn('archived', 'boolean', [
				'notnull' => false,
				'default' => false,
			]);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['listName'], 'clientmanager_lists_listName_index');
		}

		if (!$schema->hasTable('clientmanager_clients')) {
			$table = $schema->createTable('clientmanager_lists');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('listId', 'bigint', [
				'notnull' => true,
				'length' => 8,
			]);
			$table->addColumn('firstName', 'string', [
				'notnull' => true,
				'length' => 100,
			]);
			$table->addColumn('lastName', 'string', [
				'notnull' => true,
				'length' => 100,
			]);
			$table->addColumn('description', 'text', [
				'notnull' => false,
			]);
			$table->addColumn('last_modified', 'integer', [
				'notnull' => false,
				'default' => 0,
				'unsigned' => true,
			]);
			$table->addColumn('last_editor', 'string', [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('created_at', 'integer', [
				'notnull' => false,
				'default' => 0,
				'unsigned' => true,
			]);
			$table->addColumn('owner', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('deleted_at', 'bigint', [
				'notnull' => false,
				'length' => 8,
				'default' => 0,
				'unsigned' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['listId'], 'clientmanager_clients_listId_index');
		}
		return $schema;
	}
}
