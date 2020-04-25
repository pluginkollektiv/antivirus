jQuery( document ).ready(
	function( $ ) {
		/* Init */
		var avNonce = av_settings.nonce;
		var avTheme = av_settings.theme;
		var avMsg1 = av_settings.msg_1;
		var avMsg2 = av_settings.msg_2;
		var avMsg3 = av_settings.msg_3;
		var avMsg4 = av_settings.msg_4;
		var avFiles = [];
		var avFilesLoaded;

		/* Einzelne Datei prüfen */
		function checkThemeFile( current ) {
			/* ID umwandeln */
			var id = parseInt( current || 0 );

			/* File ermitteln */
			var file = avFiles[id];

			/* Request starten */
			$.post(
				ajaxurl,
				{
					action: 'get_ajax_response',
					_ajax_nonce: avNonce,
					_theme_file: file,
					_action_request: 'check_theme_file',
				},
				function( input ) {
					/* Wert initialisieren */
					var item = $( '#av_template_' + id );
					var i;
					var lines;
					var line;
					var md5;

					/* Daten vorhanden? */
					if ( input ) {
						/* Sicherheitscheck */
						if ( ! input.nonce || input.nonce !== avNonce ) {
							return;
						}

						/* Farblich anpassen */
						item.addClass( 'danger' );

						/* Init */
						lines = input.data;

						/* Zeilen loopen */
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

											/* Keine Daten? */
											if ( ! res ) {
												return;
											}

											/* Sicherheitscheck */
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

					/* Counter erhöhen */
					avFilesLoaded++;

					/* Hinweis ausgeben */
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

		/* Tempates Check */
		$( '#av_manual_scan a.button' ).click(
			function() {
				/* Request */
				$.post(
					ajaxurl,
					{
						action: 'get_ajax_response',
						_ajax_nonce: avNonce,
						_action_request: 'get_theme_files',
					},
					function( input ) {
						/* Wert initialisieren */
						var output = '';

						/* Keine Daten? */
						if ( ! input ) {
							return;
						}

						/* Sicherheitscheck */
						if ( ! input.nonce || input.nonce !== avNonce ) {
							return;
						}

						/* Globale Werte */
						avFiles = input.data;
						avFilesLoaded = 0;

						/* Files visualisieren */
						jQuery.each(
							avFiles,
							function( i, val ) {
								output += '<div id="av_template_' + i + '">' + val + '</div>';
							}
						);

						/* Werte zuweisen */
						$( '#av_manual_scan .alert' ).empty();
						$( '#av_manual_scan .output' ).empty().append( output );

						/* Files loopen */
						checkThemeFile();
					}
				);

				return false;
			}
		);

		/* Checkboxen markieren */
		function manageOptions() {
			var $$ = $( '#av_cronjob_enable' );
			var input = $$.parents( 'fieldset' ).find( ':text, :checkbox' ).not( $$ );

			if ( typeof $.fn.prop === 'function' ) {
				input.prop( 'disabled', ! $$.prop( 'checked' ) );
			} else {
				input.attr( 'disabled', ! $$.attr( 'checked' ) );
			}
		}

		/* Checkbox überwachen */
		$( '#av_cronjob_enable' ).click( manageOptions );

		/* Fire! */
		manageOptions();
	}
);
