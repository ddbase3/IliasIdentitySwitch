// Bind the existing ClientStack ModularSelect inside the native ILIAS MetaBar slate.
for (const menu of document.querySelectorAll('[data-identity-switch-menu]')) {
	if (menu.dataset.bound === 'true') continue;
	menu.dataset.bound = 'true';

	const configuration = JSON.parse(menu.querySelector('[data-identity-switch-config]').textContent);
	const phrases = configuration.labels;
	const form = menu.querySelector('[data-identity-switch-form]');
	const error = menu.querySelector('[data-identity-switch-error]');
	const button = form.querySelector('button[type="submit"]');
	const token = form.querySelector('input[name="csrf"]').value;
	const chosen = menu.querySelector('[data-identity-switch-value]');

	function showError(message) {
		error.textContent = message;
		error.hidden = false;
		error.focus();
	}

	async function submit() {
		error.hidden = true;
		if (chosen && !chosen.value) {
			showError(phrases.no_selection);
			return;
		}
		button.disabled = true;
		try {
			const response = await fetch(form.action, {
				method: 'POST',
				body: new FormData(form),
				credentials: 'same-origin',
				cache: 'no-store',
				headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
			});
			const result = await response.json();
			if (!response.ok || result.success !== true || !result.redirect) {
				throw new Error(phrases.action_error);
			}
			window.location.assign(result.redirect);
		} catch (_) {
			showError(phrases.action_error);
			button.disabled = false;
		}
	}

	form.addEventListener('submit', event => {
		event.preventDefault();
		void submit();
	});

	if (configuration.active) {
		// ILIAS renders the ModeInfo close action as a button with data-action or as a link.
		// Capture the click before ILIAS navigates, then submit the existing protected form.
		document.addEventListener('click', event => {
			const control = event.target instanceof Element
				? event.target.closest('button[data-action], a[href]')
				: null;
			if (!control) return;
			const action = control.getAttribute('data-action') ?? control.getAttribute('href');
			if (!action) return;
			const destination = new URL(action, document.baseURI);
			if (destination.searchParams.get('identity_switch_return') !== '1') return;
			event.preventDefault();
			event.stopImmediatePropagation();
			form.requestSubmit();
		}, true);
		continue;
	}

	// AssetResolver returns a document-relative path; resolve it against the page,
	// not against this external JavaScript module's asset directory.
	const widgetUrl = new URL(configuration.widgetJs, document.baseURI).href;
	try {
		const {ModularSelect} = await import(widgetUrl);
		new ModularSelect(menu.querySelector('[data-identity-switch-select]'), {
			ariaLabel: phrases.search_label,
			required: true,
			minChars: 2,
			limit: 20,
			strings: {
				placeholder: phrases.search_placeholder,
				options: phrases.options,
				loading: phrases.loading,
				noResults: phrases.no_results,
				loadMore: phrases.load_more,
				required: phrases.required,
				searchError: phrases.search_error,
				showOptions: phrases.show_options,
				hideOptions: phrases.hide_options,
				remove: phrases.remove_item,
				removed: phrases.removed_item,
				backspaceHint: phrases.backspace_hint,
				minChars: phrases.min_chars,
				results: phrases.results_count
			},
			source: async ({query, limit, offset, signal}) => {
				const body = new URLSearchParams({query, limit: String(limit), offset: String(offset), csrf: token});
				const response = await fetch(configuration.searchUrl, {
					method: 'POST',
					body,
					signal,
					credentials: 'same-origin',
					cache: 'no-store',
					headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
				});
				if (!response.ok) throw new Error(phrases.search_error);
				const data = await response.json();
				if (!Array.isArray(data.items) || typeof data.hasMore !== 'boolean') {
					throw new Error(phrases.search_error);
				}
				return data;
			},
			onChange(value) {
				chosen.value = value ?? '';
			}
		}).init();
	} catch (failure) {
		showError(phrases.search_error);
		button.disabled = true;
	}
}
