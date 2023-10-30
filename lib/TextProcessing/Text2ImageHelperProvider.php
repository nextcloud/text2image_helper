<?php
/**
 * @copyright Copyright (c) 2023 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Marcel Klehr <mklehr@gmx.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Text2ImageHelper\TextProcessing;


use OCP\TextToImage\IProvider;

class Text2ImageHelperProvider implements IProvider {

	public function getName(): string {
		return 'Fake Text2Image provider';
	}

    public function getId(): string {
        return 'fake';
    }

	public function generate(string $prompt, array $resources): void {
        //throw new \Exception('Not implemented');
		sleep(10);
		foreach ($resources as $resource) {
			$read = fopen(__DIR__ . '/../../img/logo.png', 'r');
			stream_copy_to_stream($read, $resource);
		}
	}

	public function getExpectedRuntime(): int {
		return 1;
	}
}