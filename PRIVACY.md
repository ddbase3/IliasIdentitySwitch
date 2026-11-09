# Privacy and security: IliasIdentitySwitch

## Purpose and processing

Authorized operators may temporarily use a different active ILIAS identity. The feature accesses account IDs, login/display names for an authorized user search, and native authentication session fields. These are personal data. After the switch, all normal ILIAS operations execute with the target account's native permissions.

## Storage and lifecycle

The existing ILIAS session is authoritative. A single plugin-owned BASE3 session record stores the original ID and selected ID while a switch is active. A second session key contains a random anti-CSRF token. No independent database table, identity map, publication store or external recipient is created. On a successful return, the switch record is deleted. ILIAS session expiry, deletion and native authentication lifecycle apply. The CSRF key is cleared when the BASE3 session keys are destroyed with the ILIAS session.

## Logs

The feature emits start/end events to the existing BASE3/ILIAS logger, recording the native account IDs involved. Unexpected command failures log exception class, message and source location to the same existing logger, not to the browser. Actual logger storage, access, rotation, retention, deletion and transfers depend on the active deployment and must be checked by the operator; this component does not impose or verify a retention period.

## Rights, recipients and security

Only users explicitly admitted by the existing feature-audience configuration may search and initiate a switch. No role, including administrators, is allowed by default. Actions are POST-only and protected by an anti-CSRF token. Return is allowed only when the original session record matches the live native ILIAS identity. Account lookup and role checks remain ILIAS-owned. No personal information is sent to a separate external service by this component.

## Deployment verification

Review the legal basis, feature-grant holders, informational duties, audit log retention, host backups and international transfer paths in the actual operator environment. Accessibility tests including keyboard operation and screen-reader feedback must be performed with the hosted ILIAS skin. This file documents technical processing, not a legal-compliance certification.
