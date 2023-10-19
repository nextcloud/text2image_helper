<?php

declare(strict_types=1);

namespace OCA\Text2ImageHelper\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010000Date20231018153846 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('t2ih_prompts')) {
			$table = $schema->createTable('t2ih_prompts');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('value', Types::STRING, [
				'notnull' => true,
				'length' => 1000,
			]);
			$table->addColumn('timestamp', Types::INTEGER, [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['user_id'], 't2ih_prompt_uid');
		}

		// Create 't2ih_generations'
		if (!$schema->hasTable('t2ih_generations')) {
			$table = $schema->createTable('t2ih_generations');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('image_id', Types::STRING, [
				'notnull' => true,
			]);
			$table->addColumn('file_name', Types::STRING, [
				'notnull' => true,
			]);
			$table->addColumn('prompt', Types::STRING, [
				'notnull' => true,
			]);
			$table->addColumn('timestamp', Types::INTEGER, [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['image_id'], 't2ih_gen_image_id');
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}
}