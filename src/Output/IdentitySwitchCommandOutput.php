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
use Base3\CoreFoundation\Logger\Api\ILogger;
use Base3\IliasIdentitySwitch\Exception\IdentitySwitchDeniedException;
use Base3\IliasIdentitySwitch\Service\IdentitySwitchService;
use InvalidArgumentException;
use Throwable;
use ILIAS\DI\Container as IliasContainer;

/** Single, CSRF-protected mutation endpoint; all authorization remains in IdentitySwitchService. */
final class IdentitySwitchCommandOutput implements IOutput {

	public function __construct(
		private readonly IdentitySwitchService $switch,
		private readonly IRequest $request,
		private readonly IliasContainer $ilias,
		private readonly ILogger $logger
	) {}

	public static function getName(): string {
		return 'identityswitchcommandoutput';
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		if ($out !== 'json') {
			http_response_code(404);
			return '';
		}
		if (!headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
			header('Cache-Control: private, no-store, max-age=0');
			header('X-Content-Type-Options: nosniff');
		}
		if ((string)$this->request->server('REQUEST_METHOD', '') !== 'POST') return $this->reply(405, false);
		try {
			$this->switch->assertCsrf($this->request->post('csrf', null));
			$command = $this->request->post('operation', null);
			if ($command === 'begin') {
				$rawId = $this->request->post('identity', null);
				if ((!is_int($rawId) && !is_string($rawId)) || !preg_match('/^[1-9][0-9]*$/D', (string)$rawId)) {
					throw new InvalidArgumentException('Invalid user identifier.');
				}
				$id = filter_var((string)$rawId, FILTER_VALIDATE_INT);
				if ($id === false) throw new InvalidArgumentException('Invalid user identifier.');
				$this->switch->begin($id);
			} elseif ($command === 'finish') {
				$this->switch->finish();
			} else {
				throw new InvalidArgumentException('Invalid switch operation.');
			}
			return $this->reply(200, true, $this->ilias->ctrl()->getLinkTargetByClass(\ilDashboardGUI::class, 'jumpToSelectedItems'));
		} catch (InvalidArgumentException) {
			return $this->reply(422, false);
		} catch (IdentitySwitchDeniedException) {
			return $this->reply(403, false);
		} catch (Throwable $error) {
			$this->logger->error('ILIAS identity switch action failed.', [
				'exception_class' => $error::class,
				'exception_message' => $error->getMessage(),
				'file' => $error->getFile(),
				'line' => $error->getLine()
			]);
			return $this->reply(500, false);
		}
	}

	private function reply(int $status, bool $success, string $redirect = ''): string {
		http_response_code($status);
		return json_encode(['success' => $success, 'redirect' => $redirect], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
	}
}
