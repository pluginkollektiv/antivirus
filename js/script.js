jQuery( document ).ready(
	function( $ ) {
		// Initialize.
		var avNonce = av_settings.nonce;
		var avTheme = av_settings.theme;
		var avMsg1 = av_settings.msg_1;
		var avMsg2 = av_settings.msg_2;
		var avMsg3 = av_settings.msg_3;
		var avMsg4 = av_settings.msg_4;
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
					var item = $( '#av_template_' + id );
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
						item.addClass( 'danger' );

						// Initialize lines of current file.
						lines = input.data;

						// Loop through lines.
						for ( i = 0; i < lines.length; i = i + 3 ) {
							md5 = lines[i + 2];
							line = lines[i + 1].replace( /@span@/g, '<span>' ).replace( /@\/span@/g, '</span>' );

							item.append( '<p><a href="#" id="' + md5 + '" class="button" title="' + avMsg4 + '">' + avMsg1 + '</a> <code>' + line + '</code></p>' );

							$( '#' + md5 ).click(
								function() {
									$.post(
										ajaxurl,
										{
											action: 'get_ajax_response',
											_ajax_nonce: avNonce,
											_file_md5: $( this ).attr( 'id' ),
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

											parent = $( '#' + res.data[0] ).parent();

											if ( parent.parent().children().length <= 1 ) {
												parent.parent().hide( 'slow' ).remove();
											}
											parent.hide( 'slow' ).remove();
										}
									);

									return false;
								}
							);
						}
					} else {
						item.addClass( 'done' );
					}

					// Increment counter.
					avFilesLoaded++;

					// Output notification.
					if ( avFilesLoaded >= avFiles.length ) {
						$( '#av_manual_scan .alert' ).text( avMsg3 ).fadeIn().fadeOut().fadeIn().fadeOut().fadeIn().animate( { opacity: 1.0 }, 500 ).fadeOut(
							'slow',
							function() {
								$( this ).empty();
							}
						);
					} else {
						checkThemeFile( id + 1 );
					}
				}
			);
		}

		// Check templates.
		$( '#av_manual_scan a.button' ).click(
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
						var output = '';

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
						jQuery.each(
							avFiles,
							function( i, val ) {
								output += '<div id="av_template_' + i + '">' + val + '</div>';
							}
						);

						// assign values.
						$( '#av_manual_scan .alert' ).empty();
						$( '#av_manual_scan .output' ).empty().append( output );

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
		function manageOptions() {
			var $$ = $( '#av_cronjob_enable' );
			var input = $$.parents( 'fieldset' ).find( ':text, :checkbox' ).not( $$ );

			if ( typeof $.fn.prop === 'function' ) {
				input.prop( 'disabled', ! $$.prop( 'checked' ) );
			} else {
				input.attr( 'disabled', ! $$.attr( 'checked' ) );
			}
		}

		// Watch checkboxes.
		$( '#av_cronjob_enable' ).click( manageOptions );

		// Handle initial checkbox values.
		manageOptions();
	}
);
