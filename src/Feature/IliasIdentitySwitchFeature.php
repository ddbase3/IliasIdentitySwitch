<?php declare(strict_types=1);

namespace IliasIdentitySwitch\Feature;

use Base3\Authorization\Api\IFeature;
use Base3\Translation\Api\ITranslation;
use Base3\Usermanager\Permission;

/** The only permission owner for interactive ILIAS identity switching. */
final class IliasIdentitySwitchFeature implements IFeature {

	public function __construct(private readonly ITranslation $translation) {}

	public static function getName(): string {
		return 'iliasidentityswitchfeature';
	}

	public function getLabel(): string {
		return $this->translation->translate('IdentitySwitch', self::getName(), 'label');
	}

	public function getDescription(): string {
		return $this->translation->translate('IdentitySwitch', self::getName(), 'description');
	}

	public function getPermissions(): array {
		return [Permission::for('feature', self::getName())];
	}

	public function getDefaultAudience(): array {
		return [];
	}
}
