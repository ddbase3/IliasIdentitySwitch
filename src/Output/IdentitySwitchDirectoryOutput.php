<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of IliasIdentitySwitch for BASE3 Framework.
 *
 * IliasIdentitySwitch manages temporary native ILIAS identities.
 * It provides authorized session switching and safe restoration.
 * Native ILIAS permissions continue to govern every operation.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0-or-later
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 ***********************************************************************/


namespace Base3\IliasIdentitySwitch\Output;

use Base3\CoreFoundation\Api\IOutput;
use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Usermanager\Api\IUsermanager;
use Base3\CoreFoundation\Usermanager\User;
use Base3\IliasIdentitySwitch\Service\IdentitySwitchService;
use InvalidArgumentException;
use Throwable;

/** Server-authorized, paginated native user directory for ClientStack ModularSelect. */
final class IdentitySwitchDirectoryOutput implements IOutput {

	private const LIMIT = 20;
	private const MAX_OFFSET = 100000;

	public function __construct(
		private readonly IdentitySwitchService $switch,
		private readonly IUsermanager $usermanager,
		private readonly IRequest $request
	) {}

	public static function getName(): string {
		return 'identityswitchdirectoryoutput';
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		if ($out !== 'json') {
			http_response_code(404);
			return '';
		}
		$this->jsonHeaders();
		if ((string)$this->request->server('REQUEST_METHOD', '') !== 'POST') {
			return $this->error(405);
		}
		try {
			$this->switch->assertMayStart();
			$this->switch->assertCsrf($this->request->post('csrf', null));
			$query = $this->request->post('query', '');
			$offset = $this->request->post('offset', 0);
			if (!is_string($query) || strlen($query) > 256) throw new InvalidArgumentException('Invalid search term.');
			if ((!is_int($offset) && !is_string($offset)) || !preg_match('/^[0-9]+$/D', (string)$offset)) {
				throw new InvalidArgumentException('Invalid directory offset.');
			}
			$position = filter_var((string)$offset, FILTER_VALIDATE_INT);
			if ($position === false || $position > self::MAX_OFFSET) throw new InvalidArgumentException('Invalid directory offset.');
			$query = trim($query);
			if ($query === '') return $this->response(['items' => [], 'hasMore' => false]);
			$users = $this->usermanager->searchUsers($query, self::LIMIT + 1, $position);
			$items = [];
			foreach (array_slice($users, 0, self::LIMIT) as $user) {
				if (!$user instanceof User || (!is_int($user->id) && !is_string($user->id))) continue;
				$id = filter_var($user->id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
				if ($id === false || $id === $this->switch->currentId()) continue;
				$display = trim((string)$user->name);
				$login = trim((string)$user->userid);
				$items[] = [
					'value' => (string)$id,
					'label' => ($display !== '' ? $display : $login) . ($display !== '' && $login !== '' && $login !== $display ? ' (' . $login . ')' : '')
				];
			}
			return $this->response(['items' => $items, 'hasMore' => count($users) > self::LIMIT]);
		} catch (InvalidArgumentException) {
			return $this->error(422);
		} catch (Throwable) {
			return $this->error(403);
		}
	}

	private function jsonHeaders(): void {
		if (headers_sent()) return;
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: private, no-store, max-age=0');
		header('X-Content-Type-Options: nosniff');
	}

	/** @param array<string,mixed> $data */
	private function response(array $data): string {
		return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	private function error(int $status): string {
		http_response_code($status);
		return $this->response(['success' => false]);
	}
}
