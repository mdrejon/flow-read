/* global jQuery, flowreadDWC */
/**
 * FlowRead – Dynamic Word Counter (Frontend)
 *
 * Attaches to CSS selectors configured in the plugin settings.
 *
 * Display modes:
 *   inline   → counter inserted directly after the textarea (below it)
 *   floating → counter badge anchored inside a wrapper around the textarea,
 *              positioned at the configured corner (position: absolute)
 */
( function ( $ ) {
	'use strict';

	if ( typeof flowreadDWC === 'undefined' ) {
		return;
	}

	var cfg = flowreadDWC;

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

	/**
	 * @param {string} id      Unique badge ID suffix.
	 * @param {string} mode    'inline' or 'floating'.
	 * @param {string} posKey  Corner key for floating (e.g. 'bottom-right').
	 */
	function buildUI( id, mode, posKey ) {
		var modeClass = 'flowread-dwc-' + ( mode || 'inline' );
		var posClass  = 'floating' === mode
			? 'flowread-dwc-pos-' + ( posKey || 'bottom-right' )
			: '';

		var html = '<div id="flowread-dwc-fe-' + id + '" class="flowread-dwc-wrap ' + modeClass + ' ' + posClass + '">';

		html += '<div class="flowread-dwc-row flowread-dwc-row-count">';
		html += '<span class="flowread-dwc-label">' + cfg.i18n.wordCount + ': </span>';
		html += '<span class="flowread-dwc-count">0</span>';
		html += ' <span class="flowread-dwc-unit">' + cfg.i18n.words + '</span>';

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
		html += '</div>';

		if ( cfg.showProgressBar ) {
			html += '<div class="flowread-dwc-row flowread-dwc-row-progress">';
			html += '<div class="flowread-dwc-progress-bar-track">';
			html += '<div class="flowread-dwc-progress-bar-inner" style="width:0%"></div>';
			html += '</div>';
			html += '</div>';
		}

		html += '</div>';
		return html;
	}

	// ─── UI updater ──────────────────────────────────────────────────────────

	function updateUI( $wrap, count ) {
		if ( ! $wrap || ! $wrap.length ) {
			return;
		}

		var status = getStatus( count );

		$wrap.find( '.flowread-dwc-count' ).text( count );
		$wrap.removeClass( 'flowread-dwc-ok flowread-dwc-too-short flowread-dwc-too-long' )
		     .addClass( 'flowread-dwc-' + status );

		var statusText = '';
		if ( 'too-short' === status ) {
			statusText = cfg.i18n.tooShort;
		} else if ( 'too-long' === status ) {
			statusText = cfg.i18n.tooLong;
		} else if ( cfg.minWords > 0 || cfg.maxWords > 0 ) {
			statusText = cfg.i18n.withinRange;
		}
		$wrap.find( '.flowread-dwc-status' ).text( statusText );

		if ( cfg.showProgressBar ) {
			$wrap.find( '.flowread-dwc-progress-bar-inner' ).css( 'width', getProgressPercent( count ) + '%' );
		}
	}

	// ─── Per-field init ───────────────────────────────────────────────────────

	function initField( $field, id ) {
		var mode    = cfg.displayMode  || 'inline';
		var posKey  = cfg.floatingPos  || 'bottom-right';
		var $uiWrap;

		if ( 'floating' === mode ) {
			/*
			 * Floating: wrap the field in a position:relative container so we
			 * can anchor the badge to the chosen corner of the textarea.
			 * Guard against double-wrapping (e.g. multiple matching selectors).
			 */
			if ( ! $field.parent().hasClass( 'flowread-dwc-field-wrapper' ) ) {
				$field.wrap( '<div class="flowread-dwc-field-wrapper"></div>' );
			}
			var $wrapper = $field.closest( '.flowread-dwc-field-wrapper' );
			$wrapper.append( buildUI( id, 'floating', posKey ) );
			$uiWrap = $wrapper.find( '#flowread-dwc-fe-' + id );

		} else {
			/*
			 * Inline: insert the counter block directly after the field.
			 */
			$field.after( buildUI( id, 'inline', '' ) );
			$uiWrap = $( '#flowread-dwc-fe-' + id );
		}

		// Live count on every keystroke / paste
		$field.on( 'input keyup', function () {
			updateUI( $uiWrap, countWords( $( this ).val() ) );
		} );

		// Submission guard
		if ( cfg.minWords > 0 || cfg.maxWords > 0 ) {
			var $form = $field.closest( 'form' );
			if ( $form.length ) {
				$form.on( 'submit.flowreadDWC', function ( e ) {
					if ( 'ok' !== getStatus( countWords( $field.val() ) ) ) {
						e.preventDefault();
						// eslint-disable-next-line no-alert
						window.alert( cfg.i18n.submitError );
						$field.trigger( 'focus' );
						return false;
					}
				} );
			}
		}

		// Initial count on page load
		updateUI( $uiWrap, countWords( $field.val() ) );
	}

	// ─── Boot ─────────────────────────────────────────────────────────────────

	$( function () {
		if ( ! cfg.selectors ) {
			return;
		}

		var selectors = cfg.selectors
			.split( ',' )
			.map( function ( s ) { return s.trim(); } )
			.filter( Boolean );

		selectors.forEach( function ( selector, si ) {
			$( selector ).each( function ( fi ) {
				initField( $( this ), si + '-' + fi );
			} );
		} );
	} );

} )( jQuery );
