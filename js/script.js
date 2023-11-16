jQuery(document).ready(($) => {
	// Initialize.
	const avNonce = av_settings.nonce;
	let avFiles = [];
	let avFilesLoaded;

	/**
	 * Scan a single file.
	 *
	 * @param {number} current File index to scan.
	 */
	function checkThemeFile(current) {
		// Sanitize ID.
		const id = parseInt(current || 0);

		// Get corresponding file.
		const file = avFiles[id];

		// Issue the request.
		$.post(
			ajaxurl,
			{
				action: 'get_ajax_response',
				_ajax_nonce: avNonce,
				_theme_file: file,
				_action_request: 'check_theme_file',
			},
			(input) => {
				// Data present?
				if (input) {
					// Verify nonce.
					if (!input.nonce || input.nonce !== avNonce) {
						return;
					}

					// Set highlighting color.
					const row = $('#av-scan-result-' + id);
					row.addClass('av-status-warning').removeClass(
						'av-status-pending'
					);
					row.find('td.av-status-column').text(
						av_settings.texts.warning
					);

					// Initialize lines of current file.
					const lines = input.data;

					// Loop through lines.
					for (let i = 0; i < lines.length; i = i + 3) {
						const md5 = lines[i + 2];
						const line = lines[i + 1]
							.replace(/@span@/g, '<span>')
							.replace(/@\/span@/g, '</span>');

						row.find('td.av-file-column').append(
							'<p><code>' +
								line +
								'</code> <a href="#" id="av-dismiss-' +
								md5 +
								'" class="button" title="' +
								av_settings.texts.dismiss +
								'">' +
								av_settings.labels.dismiss +
								'</a></p>'
						);

						$('#av-dismiss-' + md5).click((evt) => {
							$.post(
								ajaxurl,
								{
									action: 'get_ajax_response',
									_ajax_nonce: avNonce,
									_file_md5: evt.target.id.substring(11),
									_action_request: 'update_white_list',
								},
								(res) => {
									// No data received?
									if (!res) {
										return;
									}

									// Security check.
									if (!res.nonce || res.nonce !== avNonce) {
										return;
									}

									// Get table column above the dismiss button.
									const issue = $(
										'#av-dismiss-' + res.data[0]
									).parent();
									const col = issue.parent();

									// Hide code details and "dismiss" button.
									issue.hide('slow').remove();

									// Mark row as "OK", if no more issues are present.
									if (col.find('p').length === 0) {
										col.parent()
											.addClass('av-status-ok')
											.removeClass('av-status-warning');
										col.parent()
											.find('td.av-status-column')
											.text(av_settings.texts.ok);
									}
								}
							);

							return false;
						});
					}
				} else {
					const row = $('#av-scan-result-' + id);
					row.addClass('av-status-ok').removeClass(
						'av-status-pending'
					);
					row.find('td.av-status-column').text(av_settings.texts.ok);
				}

				// Increment counter.
				avFilesLoaded++;

				// Output notification.
				if (avFilesLoaded >= avFiles.length) {
					$('#av-scan-process')
						.html(
							'<span class="av-scan-complete">' +
								av_settings.labels.complete +
								'</span>'
						)
						.fadeOut()
						.fadeIn()
						.fadeOut()
						.fadeIn()
						.fadeOut()
						.fadeIn()
						.animate({ opacity: 1.0 }, 500);
				} else {
					checkThemeFile(id + 1);
				}
			}
		);
	}

	// Check templates.
	$('#av-scan-trigger').click(() => {
		// Request.
		$.post(
			ajaxurl,
			{
				action: 'get_ajax_response',
				_ajax_nonce: avNonce,
				_action_request: 'get_theme_files',
			},
			(input) => {
				// Initialize output value.
				let output =
					'<table class="wp-list-table widefat fixed striped table-view-list av-scan-results">' +
					'<thead><tr class="av-status-pending">' +
					'<td class="av-toggle-column check-column"></td>' +
					'<th class="av-file-column">Theme File</th>' +
					'<th class="av-status-column">Check Status</th>' +
					'</tr></thead>' +
					'<tbody>';

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

				// Visualize files.
				$.each(avFiles, (i, val) => {
					output +=
						'<tr id="av-scan-result-' +
						i +
						'">' +
						'<td class="av-toggle-column check-column"></td>' +
						'<td class="av-file-column">' +
						val +
						'</td>' +
						'<td class="av-status-column">' +
						av_settings.texts.pending +
						'</td>' +
						'</tr>';
				});

				output +=
					'</tbody><tfoot><tr>' +
					'<td class="av-toggle-column check-column"></td>' +
					'<th class="av-file-column">' +
					av_settings.labels.file +
					'</th><th class="av-status-column">' +
					av_settings.labels.status +
					'</th></tr></tfoot></table>';

				// assign values.
				$('#av-scan-process').html(
					'<span class="spinner is-active" title="running"></span>'
				);
				$('#av-scan-output').empty().append(output);

				// Start loop through files.
				checkThemeFile();
			}
		);

		return false;
	});

	/**
	 * Manage dependent option inputs.
	 */
	function manageOptions() {
		const cbSelectors = [
			'#av_cronjob_enable',
			'#av_safe_browsing',
			'#av_checksum_verifier',
		];
		let anyEnabled = false;

		cbSelectors.forEach((c) => {
			const cb = $(c);
			const inputs = cb
				.parents('fieldset')
				.find(':text, :checkbox')
				.not(cb);

			// Disable all other inputs of current fieldset, if unchecked.
			let enabled;
			if (typeof $.fn.prop === 'function') {
				enabled = !!cb.prop('checked');
				inputs.prop('disabled', !enabled);
			} else {
				enabled = !!cb.attr('checked');
				inputs.attr('disabled', !enabled);
			}

			anyEnabled = anyEnabled || enabled;
		});

		// Enable email notification if any module is enabled.
		if (typeof $.fn.prop === 'function') {
			$('#av_notify_email').prop('disabled', !anyEnabled);
		} else {
			$('#av_notify_email').attr('disabled', !anyEnabled);
		}
	}

	// Watch checkboxes.
	$('#av_settings input[type=checkbox]').click(manageOptions);

	// Handle initial checkbox values.
	manageOptions();
});
