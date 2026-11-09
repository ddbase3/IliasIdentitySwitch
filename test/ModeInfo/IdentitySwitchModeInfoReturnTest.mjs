import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {test} from 'node:test';

const script = readFileSync(new URL('../../assets/identity-switch.js', import.meta.url), 'utf8');

class IdentitySwitchModeInfoElementStub {
	constructor(control) {
		this.control = control;
	}

	closest(selector) {
		assert.equal(selector, 'button[data-action], a[href]');
		return this.control;
	}
}

test('ILIAS ModeInfo button and link use the existing protected return form', async () => {
	const original = {
		document: globalThis.document,
		Element: globalThis.Element,
		FormData: globalThis.FormData,
		fetch: globalThis.fetch,
		window: globalThis.window
	};

	try {
		const requests = [];
		const redirects = [];
		const listeners = new Map();
		const submitButton = {disabled: false};
		const error = {hidden: true, textContent: '', focus() {}};
		const form = {
			action: '/index.php?name=identityswitchcommandoutput&out=json',
			querySelector(selector) {
				if (selector === 'button[type="submit"]') return submitButton;
				if (selector === 'input[name="csrf"]') return {value: '0123456789abcdef'};
				throw new Error(`Unexpected form selector: ${selector}`);
			},
			addEventListener(name, handler) {
				assert.equal(name, 'submit');
				listeners.set('submit', handler);
			},
			requestSubmit() {
				listeners.get('submit')({preventDefault() {}});
			}
		};
		const menu = {
			dataset: {},
			querySelector(selector) {
				switch (selector) {
					case '[data-identity-switch-config]':
						return {textContent: JSON.stringify({active: true, labels: {action_error: 'Action failed'}})};
					case '[data-identity-switch-form]': return form;
					case '[data-identity-switch-error]': return error;
					case '[data-identity-switch-value]': return null;
					default: throw new Error(`Unexpected menu selector: ${selector}`);
				}
			}
		};
		let clickHandler = null;
		globalThis.Element = IdentitySwitchModeInfoElementStub;
		globalThis.FormData = class {
			constructor(source) { assert.equal(source, form); }
		};
		globalThis.document = {
			baseURI: 'https://example.org/ilias11/ilias.php',
			querySelectorAll(selector) {
				assert.equal(selector, '[data-identity-switch-menu]');
				return [menu];
			},
			addEventListener(name, handler, capture) {
				assert.equal(name, 'click');
				assert.equal(capture, true, 'ModeInfo exit must intercept before ILIAS navigation');
				clickHandler = handler;
			}
		};
		globalThis.window = {location: {assign(url) {redirects.push(url);}}};
		globalThis.fetch = async (url, request) => {
			assert.equal(url, form.action);
			assert.equal(request.method, 'POST');
			assert.equal(request.credentials, 'same-origin');
			requests.push(request);
			return {ok: true, json: async () => ({success: true, redirect: '/ilias.php?dashboard=1'})};
		};

		await import('data:text/javascript;base64,' + Buffer.from(script).toString('base64'));
		assert.equal(typeof clickHandler, 'function');

		function click(attributes) {
			const control = {getAttribute: name => attributes[name] ?? null};
			const event = {
				target: new IdentitySwitchModeInfoElementStub(control),
				prevented: false,
				stopped: false,
				preventDefault() { this.prevented = true; },
				stopImmediatePropagation() { this.stopped = true; }
			};
			clickHandler(event);
			return event;
		}

		const ilias11Button = click({'data-action': '/ilias11/ilias.php?baseClass=ildashboardgui&identity_switch_return=1'});
		assert.equal(ilias11Button.prevented, true);
		assert.equal(ilias11Button.stopped, true);
		await new Promise(resolve => setImmediate(resolve));
		assert.equal(requests.length, 1);
		assert.equal(redirects.length, 1);

		const legacyLink = click({href: '/ilias11/ilias.php?identity_switch_return=1'});
		assert.equal(legacyLink.prevented, true);
		assert.equal(legacyLink.stopped, true);
		await new Promise(resolve => setImmediate(resolve));
		assert.equal(requests.length, 2);
		assert.equal(redirects.length, 2);

		const unrelated = click({'data-action': '/ilias11/ilias.php?cmd=home'});
		assert.equal(unrelated.prevented, false);
		assert.equal(unrelated.stopped, false);
		assert.equal(requests.length, 2);
	} finally {
		for (const [name, value] of Object.entries(original)) {
			if (value === undefined) delete globalThis[name];
			else globalThis[name] = value;
		}
	}
});
