jQuery( document ).ready(
	function( $ ) {
		// Initialize.
		var avNonce = av_settings.nonce;
		var avFiles = [];
		var avFilesLoaded;

		/**
		 * Scan a single file.
		 *
		 * @param {number} current File index to scan.
		 */
		function checkThemeFile( current ) {
			// Sanitize ID.
			var id = parseInt( current || 0 );

			// Get corresponding file.
			var file = avFiles[id];

			// Issue the request.
			$.post(
				ajaxurl,
				{
					action: 'get_ajax_response',
					_ajax_nonce: avNonce,
					_theme_file: file,
					_action_request: 'check_theme_file',
				},
				function( input ) {
					// Initialize value.
					var row = $( '#av-scan-result-' + id );
					var i;
					var lines;
					var line;
					var md5;

					// Data present?
					if ( input ) {
						/* Sicherheitscheck */
						if ( ! input.nonce || input.nonce !== avNonce ) {
							return;
						}

						// Set highlighting color.
						row.addClass( 'av-status-warning' ).removeClass( 'av-status-pending' );
						row.find( 'td.av-status-column' ).text( av_settings.texts.warning );

						// Initialize lines of current file.
						lines = input.data;

						// Loop through lines.
						for ( i = 0; i < lines.length; i = i + 3 ) {
							md5 = lines[i + 2];
							line = lines[i + 1].replace( /@span@/g, '<span>' ).replace( /@\/span@/g, '</span>' );

							row.find( 'td.av-file-column' )
								.append( '<p><code>' + line + '</code> <a href="#" id="av-dismiss-' + md5 + '" class="button" title="' + av_settings.texts.dismiss + '">' + av_settings.labels.dismiss + '</a></p>' );

							$( '#av-dismiss-' + md5 ).click(
								function() {
									$.post(
										ajaxurl,
										{
											action: 'get_ajax_response',
											_ajax_nonce: avNonce,
											_file_md5: $( this ).attr( 'id' ).substr( 11 ),
											_action_request: 'update_white_list',
										},
										function( res ) {
											var parent;

											// No data received?
											if ( ! res ) {
												return;
											}

											// Security check.
											if ( ! res.nonce || res.nonce !== avNonce ) {
												return;
											}

											// Get table column above the dismiss button.
											parent = $( '#av-dismiss-' + res.data[0] ).parent().parent();

											// Hide code details and mark row as "OK".
											parent.find( 'p' ).hide( 'slow' ).remove();
											parent.parent().addClass( 'av-status-ok' ).removeClass( 'av-status-warning' );
											parent.parent().find( 'td.av-status-column' ).text( av_settings.texts.ok );
										}
									);

									return false;
								}
							);
						}
					} else {
						row.addClass( 'av-status-ok' ).removeClass( 'av-status-pending' );
						row.find( 'td.av-status-column' ).text( av_settings.texts.ok );
					}

					// Increment counter.
					avFilesLoaded++;

					// Output notification.
					if ( avFilesLoaded >= avFiles.length ) {
						$( '#av-scan-process' ).html( '<span class="av-scan-complete">' + av_settings.labels.complete + '</span>' )
							.fadeOut().fadeIn().fadeOut().fadeIn().fadeOut().fadeIn()
							.animate( { opacity: 1.0 }, 500 );
					} else {
						checkThemeFile( id + 1 );
					}
				}
			);
		}

		// Check templates.
		$( '#av-scan-trigger' ).click(
			function() {
				// Request.
				$.post(
					ajaxurl,
					{
						action: 'get_ajax_response',
						_ajax_nonce: avNonce,
						_action_request: 'get_theme_files',
					},
					function( input ) {
						// Initialize output value.
						var output = '<table class="wp-list-table widefat fixed striped table-view-list av-scan-results">' +
							'<thead><tr class="av-status-pending">' +
							'<td class="av-toggle-column"></td>' +
							'<th class="av-file-column">Theme File</th>' +
							'<th class="av-status-column">Check Status</th>' +
							'</tr></thead>' +
							'<tbody>';

						// No data received?
						if ( ! input ) {
							return;
						}

						// Security check.
						if ( ! input.nonce || input.nonce !== avNonce ) {
							return;
						}

						// Update global values.
						avFiles = input.data;
						avFilesLoaded = 0;

						// Visualize files.
						$.each(
							avFiles,
							function( i, val ) {
								output += '<tr id="av-scan-result-' + i + '">' +
									'<td class="av-toggle-column"></td>' +
									'<td class="av-file-column">' + val + '</td>' +
									'<td class="av-status-column">' + av_settings.texts.pending + '</td>' +
									'</tr>';
							}
						);

						output += '</tbody><tfoot><tr>' +
							'<td class="av-toggle-column"></td>' +
							'<th class="av-file-column">' + av_settings.labels.file + '</th>' +
							'<th class="av-status-column">' + av_settings.labels.status + '</th>' +
							'</tr></tfoot></table>';

						// assign values.
						$( '#av-scan-process' ).html( '<span class="spinner is-active" title="running"></span>' );
						$( '#av-scan-output' ).empty().append( output );

						// Start loop through files.
						checkThemeFile();
					}
				);

				return false;
			}
		);

		/**
		 * Manage dependent option inputs.
		 */
		function manageOptions( ) {
			var cbSelectors = [ '#av_cronjob_enable', '#av_safe_browsing', '#av_checksum_verifier' ];
			var anyEnabled = false;

			cbSelectors.forEach( function( c ) {
				var cb = $( c );
				var inputs = cb.parents( 'fieldset' ).find( ':text, :checkbox' ).not( cb );
				var enabled;

				// Disable all other inputs of current fieldset, if unchecked.
				if ( typeof $.fn.prop === 'function' ) {
					enabled = !! cb.prop( 'checked' );
					inputs.prop( 'disabled', ! enabled );
				} else {
					enabled = !! cb.attr( 'checked' );
					inputs.attr( 'disabled', ! enabled );
				}

				anyEnabled = anyEnabled || enabled;
			} );

			// Enable email notification if any module is enabled.
			if ( typeof $.fn.prop === 'function' ) {
				$( '#av_notify_email' ).prop( 'disabled', ! anyEnabled );
			} else {
				$( '#av_notify_email' ).attr( 'disabled', ! anyEnabled );
			}
		}

		// Watch checkboxes.
		$( '#av_settings input[type=checkbox]' ).click( manageOptions );

		// Handle initial checkbox values.
		manageOptions();
	}
);
