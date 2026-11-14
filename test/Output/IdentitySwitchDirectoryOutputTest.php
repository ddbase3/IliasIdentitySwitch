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


namespace Base3\IliasIdentitySwitch\Test\Output;

use Base3\CoreFoundation\Usermanager\User;
use Base3\IliasIdentitySwitch\Output\IdentitySwitchDirectoryOutput;
use Base3\IliasIdentitySwitch\Test\Fixture\AbstractIdentitySwitchRuntimeFixture;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class IdentitySwitchDirectoryOutputTest extends AbstractIdentitySwitchRuntimeFixture {

	private function endpoint(): IdentitySwitchDirectoryOutput {
		return new IdentitySwitchDirectoryOutput($this->switch, $this->users, $this->request);
	}

	public function testRegisteredNameAndJsonOnly(): void {
		self::assertSame('identityswitchdirectoryoutput', IdentitySwitchDirectoryOutput::getName());
		self::assertSame('', $this->endpoint()->getOutput('html'));
		self::assertSame(404, http_response_code());
	}

	public function testSearchRequiresPostAndExplicitFeatureGrant(): void {
		$this->httpMethod = 'GET';
		self::assertSame(['success' => false], json_decode($this->endpoint()->getOutput('json'), true));
		self::assertSame(405, http_response_code());
		$this->httpMethod = 'POST';
		self::assertSame(['success' => false], json_decode($this->endpoint()->getOutput('json'), true));
		self::assertSame(403, http_response_code());
		self::assertSame(0, $this->searchCalls);
	}

	public function testInvalidCsrfAndSessionAreRejectedWithoutDirectoryAccess(): void {
		$this->granted = true;
		$this->postData = ['query' => 'test', 'csrf' => 'wrong'];
		self::assertSame(['success' => false], json_decode($this->endpoint()->getOutput('json'), true));
		self::assertSame(403, http_response_code());
		$this->liveSession = false;
		self::assertSame(['success' => false], json_decode($this->endpoint()->getOutput('json'), true));
		self::assertSame(403, http_response_code());
		self::assertSame(0, $this->searchCalls);
	}

	public function testInvalidQueryAndPaginationParametersAreRejected(): void {
		$this->granted = true;
		$token = $this->switch->csrfToken();
		$cases = [
			['query' => ['unexpected'], 'offset' => 0],
			['query' => str_repeat('x', 257), 'offset' => 0],
			['query' => 'Ab', 'offset' => -1],
			['query' => 'Ab', 'offset' => 'abc'],
			['query' => 'Ab', 'offset' => '100001'],
			['query' => 'Ab', 'offset' => '9999999999999999999999999'],
			['query' => 'Ab', 'offset' => []]
		];
		foreach ($cases as $case) {
			$this->postData = $case + ['csrf' => $token];
			self::assertSame(['success' => false], json_decode($this->endpoint()->getOutput('json'), true));
			self::assertSame(422, http_response_code());
		}
		self::assertSame(0, $this->searchCalls);
	}

	public function testEmptyQueryReturnsEmptyResultsWithoutSearching(): void {
		$this->granted = true;
		$this->postData = ['csrf' => $this->switch->csrfToken(), 'query' => '   '];
		self::assertSame(['items' => [], 'hasMore' => false], json_decode($this->endpoint()->getOutput('json'), true));
		self::assertSame(0, $this->searchCalls);
	}

	public function testSearchNormalizesLabelsFiltersInvalidAndOwnAccountAndPaginates(): void {
		$this->granted = true;
		$this->postData = ['csrf' => $this->switch->csrfToken(), 'query' => '  anna  ', 'offset' => '5'];
		$this->searchResults = [
			User::fromArray(['id' => 101, 'name' => 'Self', 'userid' => 'self']),
			User::fromArray(['id' => 102, 'name' => 'Anna Adams', 'userid' => 'a.adams']),
			User::fromArray(['id' => 103, 'name' => 'Bruno', 'userid' => 'Bruno']),
			User::fromArray(['id' => 104, 'name' => '', 'userid' => 'loginonly']),
			User::fromArray(['id' => 'nonnumeric', 'name' => 'Invalid']),
			User::fromArray(['id' => 0, 'name' => 'Zero']),
			User::fromArray(['id' => 105, 'name' => '', 'userid' => ''])
		];
		$result = json_decode($this->endpoint()->getOutput('json'), true);
		self::assertSame([
			['value' => '102', 'label' => 'Anna Adams (a.adams)'],
			['value' => '103', 'label' => 'Bruno'],
			['value' => '104', 'label' => 'loginonly'],
			['value' => '105', 'label' => '']
		], $result['items']);
		self::assertFalse($result['hasMore']);
		self::assertSame(['anna', 21, 5], $this->searchArguments);
		self::assertSame(1, $this->searchCalls);
	}

	public function testTwentyFirstResultSignalsMorePagesWithoutReturningIt(): void {
		$this->granted = true;
		$this->postData = ['csrf' => $this->switch->csrfToken(), 'query' => 'a'];
		$this->searchResults = array_map(
			static fn(int $id): User => User::fromArray(['id' => $id, 'name' => 'Account ' . $id, 'userid' => 'a' . $id]),
			range(200, 220)
		);
		$result = json_decode($this->endpoint()->getOutput('json'), true);
		self::assertCount(20, $result['items']);
		self::assertTrue($result['hasMore']);
		self::assertSame('219', $result['items'][19]['value']);
	}

	public function testInvalidAccountTypeIsIgnoredWithoutDisclosingTheDirectory(): void {
		$this->granted = true;
		$this->postData = ['csrf' => $this->switch->csrfToken(), 'query' => 'Anna'];
		$this->searchResults = [new \stdClass(), User::fromArray(['id' => null])];
		self::assertSame(['items' => [], 'hasMore' => false], json_decode($this->endpoint()->getOutput('json'), true));
	}
	public function testDirectoryFailureIsDeniedWithoutRevealingNativeErrorDetails(): void {
		$this->granted = true;
		$this->postData = ['csrf' => $this->switch->csrfToken(), 'query' => 'Anna'];
		$this->failSearch = true;
		self::assertSame(['success' => false], json_decode($this->endpoint()->getOutput('json'), true));
		self::assertSame(403, http_response_code());
		self::assertSame(1, $this->searchCalls);
	}

}
