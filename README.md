# IliasIdentitySwitch

A BASE3 component that temporarily changes the authenticated ILIAS user of an existing native session. It is independently implemented. No UserTakeOver classes, settings, session markers, or routes are reused.

## Dependencies and installation

- BASE3 and the existing Base3Ilias integration
- ClientStack for the ModularSelect component and Bootstrap SVG icon
- The existing ILIAS UIHook adapter with native ModeInfo registration, ILIAS 9 to 12

Deactivate the former UserTakeOver ILIAS plugin before activating this component so that only one feature controls the native identity transition. Install the patch from the ILIAS project root. The UI correction only changes files of `components/Base3/IliasIdentitySwitch` and does not modify host adapters or plugin versions. The original standalone `IdentitySwitchPanelOutput.php` is retired and must be removed using the accompanying narrow removal script; rebuild the existing BASE3 class map in its normal way. The package requires no database migration.

## Authorization

The discoverable `IliasIdentitySwitchFeature` owns one permission: `feature/iliasidentityswitchfeature`. Its default audience is `[]`, so **no user is implicitly allowed**, including ILIAS system administrators. Grant this feature explicitly to selected trusted users in the existing BASE3 Features administration, which uses the native host authorization authority.

The MetaBar entry, user directory output, and switch action validate that same permission. Only the original-session return operation is available without the current account's feature permission, because that identity may not have been granted access. That operation validates the plugin-owned session record and its CSRF token.

**Grant scope:** Selected users can act as any active non-anonymous ILIAS user, including administrative accounts. Such grants permit exercising the target account's effective native ILIAS rights. Restrict grants to trusted operators. Existing ILIAS RBAC continues to govern each operation after the switch.

## User flow

1. Open **Switch account** in the native ILIAS upper-right MetaBar. The person-outline icon uses the native medium icon size, approximately 70% of the earlier large icon.
2. ILIAS opens a native MetaBar slate with the existing ClientStack ModularSelect. While the search suggestions are open, the plugin selectively removes the native slate overflow limit to avoid a second scrollbar; the normal slate layout is preserved when suggestions close. The user search stays a POST-only BASE3 IOutput endpoint. No standalone selection page is used.
3. The protected action verifies CSRF, feature permission and target existence/activation.
4. The native ILIAS session receives the effective account identity and is saved. The original identity is held in one session-local BASE3 record. A redirect forces a new ILIAS user context.
5. The ILIAS ModeInfo is visible on ordinary pages while the session record remains valid. Its close action uses the same CSRF-protected POST as the return button in the MetaBar slate. No GET request alters the session.

There is no nested switching and no time-based automatic restoration. Session expiry and logout remain ILIAS-owned.

## Existing integration points

- `Base3\Base3Ilias\Api\IMetaBarItemProvider` is unchanged.
- `Base3\Base3Ilias\Api\IModeInfoProvider` is a small discoverable ILIAS-specific extension point.
- The existing `Base3IliasAdapter` registers exactly one native layout modification provider and discovers BASE3 mode notices through the class map.
- `ISession` remains namespaced to BASE3, and is used only for the plugin's own CSRF and reversible state. Native ILIAS authentication values are updated through `ilSession`.
- `IUsermanager` provides identities; `IAuthorizationService` alone makes the access decision.

## Languages

The same 12 language codes as ClientStack are supported: `ar`, `bg`, `de`, `en`, `es`, `fr`, `hi`, `it`, `pl`, `pt`, `ru`, `zh`. All 31 keys per language, including the feature description, native ILIAS MetaBar and ModeInfo labels, command errors and the complete ClientStack ModularSelect string set, are maintained in this plugin's own `lang/IdentitySwitch/` catalogs. The original `ITranslation` and `IMvcView` mechanisms select the effective host language; no second language resolver is provided.

## Validation

Run `php -l` over modified PHP source, and test all behavior on the actual ILIAS host. Essential integration checks: selected user / unselected user / anonymous, administrative target, inactive target, missing target, cross-site POST, repeat switch, correct return, feature revocation during an active switch, expired native session, native write failure, other active ILIAS modes, and the actual target role's permissions after redirect. Source inspection alone does not establish successful ILIAS 9 to 12 runtime behavior.

### Isolated tests and coverage

The PHPUnit suite covers all seven production classes and the rendered template. Its tests exercise the plugin DI bindings, the discoverable permission, both JSON outputs (including input validation, CSRF and denial), the native MetaBar and ModeInfo integration seams, user identity validation, native session persistence, rollback, restoration after permission revocation, and malformed or stale session state. Test-only aliases are registered in `test/bootstrap.php`, while the fixture classes have distinct names in `test/Fixture/`. No global ILIAS class declaration is included in the plugin, and no ILIAS runtime is bootstrapped during unit tests.

The test suite uses PHPUnit APIs common to 11.5 and 12.5. With the corresponding PHPUnit executable and a supported PHP runtime, run from the ILIAS project root:

```bash
php /path/to/phpunit-11.5.phar -c components/Base3/IliasIdentitySwitch/phpunit.xml.dist
php /path/to/phpunit-12.5.phar -c components/Base3/IliasIdentitySwitch/phpunit.xml.dist
```

To obtain a measured code-coverage report, enable Xdebug or PCOV in the test PHP environment and add `--coverage-text` or `--coverage-html <directory>` to each command. The `<source>` section in `phpunit.xml.dist` restricts the coverage denominator to the plugin's `src/` tree. Near-complete coverage is the target, not an assertion that an unmeasured suite has achieved exactly 100%. Only an actual run against both PHPUnit versions and a coverage driver can establish precise figures.

The existing native ModeInfo browser-handler regression check runs separately with:

```bash
node --test components/Base3/IliasIdentitySwitch/test/ModeInfo/IdentitySwitchModeInfoReturnTest.mjs
```

These isolated checks do not replace host-level verification on all supported ILIAS 9 to 12 versions, including effective target rights after switching, safe restoration, and native GlobalScreen rendering.

The native ILIAS active-account lookup uses `ilObjUser::_lookupActive()` (not the removed `getStoredActive()`). ILIAS 9/10 and 11/12 legacy UI factory differences are handled solely by `Base3\Base3Ilias\Ilias\Base3IliasLegacyContentAdapter` using the existing `ISystemService`; this feature does not contain version-specific UI branches. Native session persistence uses `session_encode()` instead of the human-readable `ilSession::dumpToString()`. Expected identity-switch rejections return HTTP 403, while unexpected native exceptions return HTTP 500 and are logged by the existing BASE3 logger. Neither response reveals internal exception details to the browser.
