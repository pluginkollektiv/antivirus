document.addEventListener('DOMContentLoaded', () => {
	// Initialize.
	const avNonce = av_settings.nonce;
	let avFiles = [];
	let avFilesLoaded;

	/**
	 * Scan a single file.
	 *
	 * @param {number} current File index to scan.
	 */
	function checkThemeFile(current = 0) {
		// Sanitize ID.
		const id = parseInt(current);

		// Get corresponding file.
		const file = avFiles[id];

		// Issue the request.
		ajaxRequest({
			_theme_file: file,
			_action_request: 'check_theme_file',
		})
			.then((res) => res.text())
			.then((input) => {
				// Data present?
				if (input) {
					input = JSON.parse(input);
					// Verify nonce.
					if (!input.nonce || input.nonce !== avNonce) {
						return;
					}

					// Set highlighting color.
					const row = document.getElementById(`av-scan-result-${id}`);
					row.classList.replace(
						'av-status-pending',
						'av-status-warning'
					);
					row.querySelector('td.av-status-column').innerText =
						wp.i18n.__('! Warning', 'antivirus');

					// Initialize lines of current file.
					const lines = input.data;

					// Loop through lines.
					for (let i = 0; i < lines.length; i = i + 3) {
						const md5 = lines[i + 2];
						const line = lines[i + 1]
							.replace(/@span@/g, '<span>')
							.replace(/@\/span@/g, '</span>');

						row.querySelector('td.av-file-column').innerHTML +=
							`<p><code>${line}</code> <a href="#" id="av-dismiss-${md5}" class="button" ` +
							`title="${wp.i18n.__('Dismiss false positive virus detection', 'antivirus')}">` +
							wp.i18n.__('Dismiss', 'antivirus') +
							'</a></p>';

						document
							.getElementById(`av-dismiss-${md5}`)
							?.addEventListener('click', handleDismiss);
					}
				} else {
					const row = document.getElementById(`av-scan-result-${id}`);
					row.classList.add('av-status-pending', 'av-status-ok');
					row.querySelector('td.av-status-column').innerText =
						wp.i18n.__('✔ OK', 'antivirus');
				}

				// Increment counter.
				avFilesLoaded++;

				if (avFilesLoaded >= avFiles.length) {
					scanSuccess();
				} else {
					// Continue with the next file.
					checkThemeFile(id + 1);
				}
			})
			.catch(scanFailed);
	}

	/**
	 * Event handler for the "dismiss" button.
	 * Notify the API and update the UI accordingly.
	 *
	 * @param {MouseEvent} evt Click event
	 * @return {boolean} false
	 */
	function handleDismiss(evt) {
		ajaxRequest({
			_file_md5: evt.target.id.substring(11),
			_action_request: 'update_white_list',
		})
			.then((res) => res.json())
			.then((res) => {
				// No data received or missing nonce?
				if (!res || !res.nonce || res.nonce !== avNonce) {
					return;
				}

				// Get table column above the dismiss button.
				const issue = document.getElementById(
					`av-dismiss-${res.data[0]}`
				)?.parentElement;
				const col = issue?.parentElement;

				// Hide code details and "dismiss" button.
				issue?.remove();

				// Mark row as "OK", if no more issues are present.
				if (col.getElementsByTagName('p').length === 0) {
					col.parentElement?.classList.replace(
						'av-status-warning',
						'av-status-ok'
					);
					col.parentElement.querySelector(
						'td.av-status-column'
					).innerText = wp.i18n.__('✔ OK', 'antivirus');
				}
			})
			.catch(scanFailed);

		return false;
	}

	/**
	 * Trigger a manual scan.
	 *
	 * @param {MouseEvent} evt Click event
	 * @return {boolean} false
	 */
	function triggerScan(evt) {
		// Lock the button.
		evt.target.disabled = true;

		ajaxRequest({
			_action_request: 'get_theme_files',
		})
			.then((res) => res.json())
			.then((input) => {
				// No data received?
				if (!input) {
					return;
				}

				// Security check.
				if (!input.nonce || input.nonce !== avNonce) {
					return;
				}

				// Update global values.
				avFiles = input.data;
				avFilesLoaded = 0;

				// assign values.
				let elem = document.getElementById('av-scan-process');
				if (elem) {
					elem.innerHTML =
						'<span class="spinner is-active" title="running"></span>';
				}
				elem = document.getElementById('av-scan-output');
				if (elem) {
					elem.innerHTML = generateThemeFileTable();
				}

				// Start loop through files.
				checkThemeFile();
			})
			.catch(scanFailed);
		return false;
	}

	/**
	 * Scan success handler.
	 * Display a success message and re-enable the trigger button.
	 */
	function scanSuccess() {
		document.getElementById('av-scan-process').innerHTML =
			`<span class="av-scan-complete">${wp.i18n.__('Scan finished', 'antivirus')}</span>`;
		document.getElementById('av-scan-trigger').disabled = false;
	}

	/**
	 * Scan error handler.
	 * Display an error message and re-enable the trigger button
	 */
	function scanFailed() {
		document.getElementById('av-scan-process').innerHTML =
			`<span class="av-scan-error">${wp.i18n.__('Scan failed', 'antivirus')}</span>`;
		document.getElementById('av-scan-trigger').disabled = false;
	}

	/**
	 * Generate an HTML table for theme files before scanning.
	 *
	 * @return {string} HTML table markup
	 */
	function generateThemeFileTable() {
		// Initialize output value.
		let output =
			'<table class="wp-list-table widefat fixed striped table-view-list av-scan-results">' +
			'<thead><tr>' +
			'<td class="av-toggle-column check-column"></td>' +
			`<th class="av-file-column">${wp.i18n.__('Theme File', 'antivirus')}</th>` +
			`<th class="av-status-column">${wp.i18n.__('Check Status', 'antivirus')}</th>` +
			'</tr></thead>' +
			'<tbody>';

		avFiles.forEach((val, i) => {
			output +=
				`<tr id="av-scan-result-${i}" class="av-status-pending">` +
				'<td class="av-toggle-column check-column"></td>' +
				`<td class="av-file-column">${val}</td>` +
				`<td class="av-status-column">${wp.i18n.__('pending', 'antivirus')}</td>` +
				'</tr>';
		});

		output +=
			'</tbody><tfoot><tr>' +
			'<td class="av-toggle-column check-column"></td>' +
			`<th class="av-file-column">${wp.i18n.__('Theme File', 'antivirus')}</th>` +
			`<th class="av-status-column">${wp.i18n.__('Check Status', 'antivirus')}</th>` +
			'</tr></tfoot></table>';

		return output;
	}

	/**
	 * Manage dependent option inputs.
	 */
	function manageOptions() {
		const cbIds = [
			'av_cronjob_enable',
			'av_safe_browsing',
			'av_checksum_verifier',
		];
		let anyEnabled = false;

		for (const c of cbIds) {
			const cb = document.getElementById(c);
			if (!cb) {
				continue;
			}

			const enabled = cb.checked;
			cb?.closest('fieldset')
				?.querySelectorAll('input[type="text"], input[type="checkbox"]')
				.forEach((input) => {
					if (input !== cb) {
						input.disabled = !enabled;
					}
				});

			anyEnabled = anyEnabled || enabled;
		}

		// Enable email notification if any module is enabled.
		const elem = document.getElementById('av_notify_email');
		if (elem) {
			elem.disabled = !anyEnabled;
		}
	}

	/**
	 * Make an AJAX request.
	 *
	 * @param {Record<string, string>} data Payload (action and nonce not included)
	 * @return {Promise<Response>} response promise
	 */
	function ajaxRequest(data) {
		return fetch(ajaxurl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'get_ajax_response',
				_ajax_nonce: avNonce,
				...data,
			}),
		});
	}

	// ----- initialize DOM elements  -----

	// Check templates.
	document
		.getElementById('av-scan-trigger')
		?.addEventListener('click', triggerScan);

	// Watch checkboxes.
	document
		.getElementById('av_settings')
		?.querySelectorAll('input[type=checkbox]')
		.forEach((cb) => cb.addEventListener('click', manageOptions));

	// Handle initial checkbox values.
	manageOptions();
});
