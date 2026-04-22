/* global jQuery, flowreadDWC, wp, tinymce */
/**
 * FlowRead – Dynamic Word Counter (Admin Editor)
 *
 * Supports:
 *   - Gutenberg (block editor) via wp.data.subscribe
 *   - Classic Editor (plain textarea + TinyMCE)
 *
 * Injected only on post-type screens selected in settings.
 */
( function ( $ ) {
	'use strict';

	if ( typeof flowreadDWC === 'undefined' ) {
		return;
	}

	var cfg  = flowreadDWC;
	var $ui  = null;
	var uid  = 'flowread-dwc-admin';

	// ─── Word counting ────────────────────────────────────────────────────────

	function countWords( text ) {
		if ( cfg.excludeHtml ) {
			text = text.replace( /<[^>]*>/g, ' ' );
		}
		if ( cfg.excludeShortcodes ) {
			text = text.replace( /\[.*?\]/gs, ' ' );
		}
		if ( cfg.excludeNumbers ) {
			text = text.replace( /\b\d+\b/g, ' ' );
		}
		text = text.replace( /\s+/g, ' ' ).trim();
		if ( ! text ) {
			return 0;
		}
		return text.split( /\s+/ ).length;
	}

	// ─── Status helpers ───────────────────────────────────────────────────────

	function getStatus( count ) {
		var min = cfg.minWords || 0;
		var max = cfg.maxWords || 0;
		if ( min > 0 && count < min ) { return 'too-short'; }
		if ( max > 0 && count > max ) { return 'too-long';  }
		return 'ok';
	}

	function getProgressPercent( count ) {
		var max = cfg.maxWords || 0;
		var min = cfg.minWords || 0;
		if ( max > 0 ) {
			return Math.min( 100, Math.round( ( count / max ) * 100 ) );
		}
		if ( min > 0 ) {
			return Math.min( 100, Math.round( ( count / min ) * 100 ) );
		}
		return 0;
	}

	// ─── UI builder ──────────────────────────────────────────────────────────

	function buildUI() {
		var modeClass = 'flowread-dwc-' + ( cfg.displayMode || 'inline' );
		var posClass  = 'floating' === cfg.displayMode
			? 'flowread-dwc-pos-' + ( cfg.floatingPos || 'bottom-right' )
			: '';

		var html = '<div id="' + uid + '" class="flowread-dwc-wrap ' + modeClass + ' ' + posClass + '">';

		// Row 1: count + unit
		html += '<div class="flowread-dwc-row flowread-dwc-row-count">';
		html += '<span class="flowread-dwc-label">' + cfg.i18n.wordCount + ': </span>';
		html += '<span class="flowread-dwc-count">0</span> ';
		html += '<span class="flowread-dwc-unit">' + cfg.i18n.words + '</span>';

		if ( cfg.minWords > 0 || cfg.maxWords > 0 ) {
			html += '<span class="flowread-dwc-limits">';
			if ( cfg.minWords > 0 ) {
				html += '<span class="flowread-dwc-min">' + cfg.i18n.minLabel + ': ' + cfg.minWords + '</span>';
			}
			if ( cfg.maxWords > 0 ) {
				html += '<span class="flowread-dwc-max">' + cfg.i18n.maxLabel + ': ' + cfg.maxWords + '</span>';
			}
			html += '</span>';
		}

		html += '<span class="flowread-dwc-status"></span>';
		html += '</div>'; // .flowread-dwc-row-count

		// Row 2: progress bar
		if ( cfg.showProgressBar ) {
			html += '<div class="flowread-dwc-row flowread-dwc-row-progress">';
			html += '<div class="flowread-dwc-progress-bar-track">';
			html += '<div class="flowread-dwc-progress-bar-inner" style="width:0%"></div>';
			html += '</div>';
			html += '</div>';
		}

		html += '</div>'; // .flowread-dwc-wrap
		return html;
	}

	// ─── UI updater ──────────────────────────────────────────────────────────

	function updateUI( count ) {
		if ( ! $ui || ! $ui.length ) {
			return;
		}

		var status = getStatus( count );

		$ui.find( '.flowread-dwc-count' ).text( count );
		$ui.removeClass( 'flowread-dwc-ok flowread-dwc-too-short flowread-dwc-too-long' )
		   .addClass( 'flowread-dwc-' + status );

		var statusText = '';
		if ( 'too-short' === status ) {
			statusText = cfg.i18n.tooShort;
		} else if ( 'too-long' === status ) {
			statusText = cfg.i18n.tooLong;
		} else if ( cfg.minWords > 0 || cfg.maxWords > 0 ) {
			statusText = cfg.i18n.withinRange;
		}
		$ui.find( '.flowread-dwc-status' ).text( statusText );

		if ( cfg.showProgressBar ) {
			$ui.find( '.flowread-dwc-progress-bar-inner' ).css( 'width', getProgressPercent( count ) + '%' );
		}
	}

	// ─── Submission guard ─────────────────────────────────────────────────────

	function isWithinLimits( count ) {
		return 'ok' === getStatus( count );
	}

	// ─── Gutenberg (block editor) ─────────────────────────────────────────────

	function initGutenberg() {
		if ( typeof wp === 'undefined' || ! wp.data || ! wp.domReady ) {
			return false;
		}

		var coreEditor = wp.data.select( 'core/editor' );
		if ( ! coreEditor ) {
			return false;
		}

		function setupGutenbergListeners() {
			$ui = $( '#' + uid );

			// Subscribe to content changes
			wp.data.subscribe( function () {
				var content = wp.data.select( 'core/editor' ).getEditedPostContent();
				updateUI( countWords( content ) );
			} );

			// Intercept publish / update / save-draft
			$( document ).on(
				'click',
				'.editor-post-publish-button__button, .editor-post-publish-button, .editor-post-save-draft',
				function ( e ) {
					if ( ! cfg.minWords && ! cfg.maxWords ) {
						return;
					}
					var content = wp.data.select( 'core/editor' ).getEditedPostContent();
					if ( ! isWithinLimits( countWords( content ) ) ) {
						e.preventDefault();
						e.stopImmediatePropagation();
						// eslint-disable-next-line no-alert
						window.alert( cfg.i18n.submitError );
					}
				}
			);
		}

		wp.domReady( function () {
			if ( 'floating' === cfg.displayMode ) {
				$( 'body' ).append( buildUI() );
				setupGutenbergListeners();
				return;
			}

			/*
			 * Inline mode: inject into the right sidebar, at the top of the
			 * panel so it appears just below the Update / Publish button area.
			 * The sidebar is rendered asynchronously, so poll until it exists.
			 */
			var attempts = 0;
			var timer = setInterval( function () {
				var $panel = $(
					'.edit-post-sidebar .components-panel,' +
					'.interface-interface-skeleton__sidebar .components-panel'
				).first();

				if ( $panel.length || ++attempts >= 20 ) {
					clearInterval( timer );
					if ( $panel.length ) {
						$panel.prepend( buildUI() );
					} else {
						$( 'body' ).append( buildUI() );
					}
					setupGutenbergListeners();
				}
			}, 250 );
		} );

		return true;
	}

	// ─── Classic Editor ──────────────────────────────────────────────────────

	function initClassic() {
		var $textarea = $( '#content' );
		if ( ! $textarea.length ) {
			return false;
		}

		// Inject UI
		if ( 'floating' === cfg.displayMode ) {
			$( 'body' ).append( buildUI() );
		} else {
			/*
			 * Inline mode: place below the Publish / Update metabox on the
			 * right-hand sidebar (#submitdiv). Fall back to after the editor
			 * wrap if the submit metabox is not found.
			 */
			var $submitDiv = $( '#submitdiv' );
			if ( $submitDiv.length ) {
				$submitDiv.after( buildUI() );
			} else {
				var $editorWrap = $textarea.closest( '#wp-content-wrap, .wp-editor-wrap' );
				if ( ! $editorWrap.length ) {
					$editorWrap = $textarea.parent();
				}
				$editorWrap.after( buildUI() );
			}
		}

		$ui = $( '#' + uid );

		// Plain-textarea listener
		$textarea.on( 'input keyup', function () {
			updateUI( countWords( $( this ).val() ) );
		} );

		// TinyMCE listener
		if ( typeof tinymce !== 'undefined' ) {
			$( document ).on( 'tinymce-editor-init', function ( event, editor ) {
				if ( 'content' === editor.id ) {
					editor.on( 'keyup input NodeChange SetContent', function () {
						updateUI( countWords( editor.getContent() ) );
					} );
				}
			} );
		}

		// Submission guard
		$( '#publish, #save-post' ).on( 'click', function ( e ) {
			if ( ! cfg.minWords && ! cfg.maxWords ) {
				return;
			}
			var content = '';
			if ( typeof tinymce !== 'undefined' && tinymce.get( 'content' ) && ! tinymce.get( 'content' ).isHidden() ) {
				content = tinymce.get( 'content' ).getContent();
			} else {
				content = $textarea.val();
			}
			if ( ! isWithinLimits( countWords( content ) ) ) {
				e.preventDefault();
				e.stopImmediatePropagation();
				// eslint-disable-next-line no-alert
				window.alert( cfg.i18n.submitError );
			}
		} );

		// Initial count on load
		if ( typeof tinymce !== 'undefined' && tinymce.get( 'content' ) ) {
			updateUI( countWords( tinymce.get( 'content' ).getContent() ) );
		} else {
			updateUI( countWords( $textarea.val() ) );
		}

		return true;
	}

	// ─── Boot ─────────────────────────────────────────────────────────────────

	$( function () {
		if ( ! initGutenberg() ) {
			initClassic();
		}
	} );

} )( jQuery );
