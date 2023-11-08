<?php

declare(strict_types=1);

namespace OCA\Text2ImageHelper\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010000Date20231018153853 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return void
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
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

		if (!$schema->hasTable('t2ih_generations')) {
			$table = $schema->createTable('t2ih_generations');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('image_gen_id', Types::STRING, [
				'notnull' => true,
			]);
			$table->addColumn('is_generated', Types::BOOLEAN, [
				'notnull' => false, 'default' => false,
			]);
			$table->addColumn('failed', Types::BOOLEAN, [
				'notnull' => false, 'default' => false,
			]);
			$table->addColumn('notify_ready', Types::BOOLEAN, [
				'notnull' => false, 'default' => false,
			]);
			$table->addColumn('prompt', Types::STRING, [
				'notnull' => true,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
			]);
			$table->addColumn('timestamp', Types::INTEGER, [
				'notnull' => true,
			]);
			$table->addColumn('exp_gen_time', Types::INTEGER, [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['image_gen_id'], 't2ih_image_gen_id');
		}

		if (!$schema->hasTable('t2ih_i_files')) {
			$table = $schema->createTable('t2ih_i_files');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('generation_id', Types::INTEGER, [
				'notnull' => true,
			]);
			$table->addColumn('file_name', Types::STRING, [
				'notnull' => true,
			]);
			$table->addColumn('hidden', Types::BOOLEAN, [
				'notnull' => false, 'default' => false,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['generation_id'], 't2ih_gen_id');
		}

		if (!$schema->hasTable('t2ih_stale_gens')) {
			$table = $schema->createTable('t2ih_stale_gens');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('image_gen_id', Types::STRING, [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['image_gen_id'], 't2ih_i_gen_id');
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return void
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
