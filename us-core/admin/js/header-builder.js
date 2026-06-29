if ( window.$ushb === undefined ) {
	window.$ushb = {};
}

$ushb.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test( navigator.userAgent );

! function( $, _undefined ) {

	if ( window.$ushb.mixins === _undefined ) {
		window.$ushb.mixins = {};
	}

	// TODO: replace AJAX URL
	$ushb.ajaxUrl = $( '.us-bld' ).data( 'ajaxurl' );

	/**
	 * $ushb.Tabs class.
	 *
	 * @param container
	 */
	$ushb.Tabs = function( container ) {
		const self = this;
		self.$container = $( container );
		self.$list = $( '.usof-tabs-list:first', self.$container );
		self.$items = self.$list.children( '.usof-tabs-item' );
		self.$sections = $( '.usof-tabs-section', self.$container );
		self.items = self.$items.toArray().map( $ );
		self.sections = self.$sections.toArray().map( $ );
		self.active = 0;
		self.items.forEach( ( $item, index ) => {
			$item.on( 'click', self.open.bind( self, index ) );
		} );
	};
	$.extend( $ushb.Tabs.prototype, $usof.mixins.Events, {
		open: function( index ) {
			if ( index == this.active || this.sections[ index ] == _undefined ) {
				return;
			}
			if ( this.sections[ this.active ] !== _undefined ) {
				this.trigger( 'beforeHide', this.active, this.sections[ this.active ], this.items[ this.active ] );
				this.sections[ this.active ].hide();
				this.items[ this.active ].removeClass( 'active' );
				this.trigger( 'afterHide', this.active, this.sections[ this.active ], this.items[ this.active ] );
			}
			this.trigger( 'beforeShow', index, this.sections[ index ], this.items[ index ] );
			this.sections[ index ].show();
			this.items[ index ].addClass( 'active' );
			this.trigger( 'afterShow', index, this.sections[ index ], this.items[ index ] );
			this.active = index;
		}
	} );

	/**
	 * $ushb.EForm class
	 *
	 * @param container
	 */
	$ushb.EForm = function( container ) {
		const self = this;

		self.$container = $( container );
		self.$tabs = $( '.usof-tabs', self.$container );
		if ( self.$tabs.length ) {
			self.tabs = new $ushb.Tabs( self.$tabs );
		}

		self.initFields( self.$container );
		self.initDataIndicator();

		// Delete all fields that are in design_options since they will be initialized by design_options,
		// otherwise there will be duplication events on different parent objects
		for ( var k in self.fields ) {
			if (
				self.fields[ k ].type === 'color'
				&& self.fields[ k ].$row.closest( '.type_design_options' ).length
			) {
				delete self.fields[ k ];
			}
		}

		// Toggles the data indicator on the tab
		self.on( 'dataIndicatorChanged', $ush.debounce( ( hasValues ) => {
			if ( self.tabs instanceof $ushb.Tabs ) {
				for ( const index in hasValues ) if ( self.tabs.$items[ index ] ) {
					$( self.tabs.$items[ index ] ).toggleClass( 'has_values', hasValues[ index ] === true );
				}
			}
		}, 1 ) );
	};
	$.extend( $ushb.EForm.prototype, $usof.mixins.Fieldset );

	/**
	 * $ushb.Elist class: A popup with elements list to choose from. Behaves as a singleton.
	 */
	$ushb.EList = function() {
		const self = this;
		if ( $ushb.elist !== _undefined ) {
			return $ushb.elist;
		}
		self.$container = $( '.us-bld-window.for_adding' );
		if ( self.$container.length > 0 ) {
			self.$container.appendTo( $( document.body ) );
			self.init();
		}
	};
	$.extend( $ushb.EList.prototype, $usof.mixins.Events, {
		init: function() {
			this.$closer = $( '.us-bld-window-closer', this.$container );
			this.$list = $( '.us-bld-window-list', this.$container );
			this._events = {
				select: function( event ) {
					var $item = $( event.target ).closest( '.us-bld-window-item' );
					this.hide();
					this.trigger( 'select', $item.data( 'name' ) );
				}.bind( this ),
				hide: this.hide.bind( this )
			};
			this.$closer.on( 'click', this._events.hide );
			this.$list.on( 'click', '.us-bld-window-item', this._events.select );
		},
		show: function() {
			if ( this.$container.length == 0 ) {
				// Loading elements list html via ajax
				$.ajax( {
					type: 'post',
					url: $ushb.ajaxUrl,
					data: {
						action: 'us_ajax_hb_get_elist_html'
					},
					success: function( html ) {
						this.$container = $( html ).css( 'display', 'none' ).appendTo( $( document.body ) );
						this.init();
						this.show();
					}.bind( this )
				} );
				return;
			}

			this.trigger( 'beforeShow' );
			this.$container.css( 'display', 'block' );
			this.trigger( 'afterShow' );
		},
		hide: function() {
			this.trigger( 'beforeHide' );
			this.$container.css( 'display', 'none' );
			this.trigger( 'afterHide' );
		}
	} );
	// Singleton instance
	$ushb.elist = new $ushb.EList;

	/**
	 * $ushb.EBuilder class: A popup with loadable elements forms.
	 */
	$ushb.EBuilder = function() {
		const self = this;
		self.$container = $( '.us-bld-window.for_editing' );
		self.loaded = false;
		if ( self.$container.length > 0 ) {
			self.$container.appendTo( $( document.body ) );
			self.init();
		}
	};

	$.extend( $ushb.EBuilder.prototype, $usof.mixins.Events, {

		init: function() {
			this.$title = $( '.us-bld-window-title', this.$container );
			this.titles = this.$title[0].onclick() || {};
			this.$title.removeAttr( 'onclick' );
			this.$closer = $( '.us-bld-window-closer', this.$container );
			this.$header = $( '.us-bld-window-header', this.$container );
			// EForm containers and class instances
			this.$eforms = {};
			this.eforms = {};
			// Set of default values for each elements form
			this.defaults = {};
			$( '.usof-form', this.$container ).each( function( index, eform ) {
				var $eform = $( eform ).css( 'display', 'none' ),
					name = $eform.usMod( 'for' );
				this.$eforms[ name ] = $eform;
			}.bind( this ) );
			this.$btnSave = $( '.usof-button.type_save', this.$container );
			// Actve element
			this.active = false;
			this._events = {
				hide: this.hide.bind( this ),
				save: this.save.bind( this ),
			};
			this.$closer.on( 'click', this._events.hide );
			this.$btnSave.on( 'click', this._events.save );
		},

		/**
		 * Show element form for a specified element name and initial values.
		 *
		 * @param {String} name
		 * @param {{}} values
		 */
		show: function( name, values ) {
			if ( this.$container.css( 'display' ) == 'block' ) {
				// If some other form is already shown, hiding it before proceeding
				this.hide();
			}
			if ( ! this.loaded ) {
				this.$title.html( this.titles[ name ] || '' );
				this.$container.css( 'display', 'block' );
				// Loading ebuilder and initial form's html
				$.ajax( {
					type: 'post',
					url: $ushb.ajaxUrl,
					data: {
						action: 'us_ajax_hb_get_ebuilder_html'
					},
					success: function( html ) {
						if ( html == '' ) {
							return;
						}
						// Removing additionally appended assets
						var regexp = /(\<link rel=\'stylesheet\' id=\'([^\']+)\'[^\>]+?\>)|(\<style type\=\"text\/css\"\>([^\<]*)\<\/style\>)|(\<script type=\'text\/javascript\' src=\'([^\']+)\'\><\/script\>)|(\<script type\=\'text\/javascript\'\>([^`]*?)\<\/script\>)/g;
						html = html.replace( regexp, '' );
						this.$container.remove();
						this.$container = $( html ).css( 'display', 'none' ).addClass( 'loaded' ).appendTo( $( document.body ) );
						this.loaded = true;
						this.init();
						this.show( name, values );
					}.bind( this )
				} );
				return;
			}
			if ( this.eforms[ name ] === _undefined ) {
				// Initializing EForm on the first show
				if ( this.$eforms[ name ] === _undefined ) {
					return;
				}
				this.eforms[ name ] = new $ushb.EForm( this.$eforms[ name ] );
				this.defaults[ name ] = this.eforms[ name ].getValues();
			}

			// Filling missing values with defaults
			values = $.extend( {}, this.defaults[ name ], values );
			this.eforms[ name ].setValues( values );
			if ( this.eforms[ name ].tabs !== _undefined ) {
				this.eforms[ name ].tabs.$list.appendTo( this.$header );
				this.eforms[ name ].tabs.open( 0 );
			}
			this.$container.toggleClass( 'with_tabs', this.eforms[ name ].tabs !== _undefined );
			this.$eforms[ name ].css( 'display', 'block' );
			this.$title.html( this.titles[ name ] || '' );
			this.active = name;
			this.trigger( 'beforeShow' );
			this.$container.css( 'display', 'block' );
			this.trigger( 'afterShow' );
		},

		hide: function() {
			this.trigger( 'beforeHide' );
			this.$container.css( 'display', 'none' );
			if ( this.$eforms[ this.active ] !== _undefined ) {
				this.$eforms[ this.active ].css( 'display', 'none' );
			}
			this.trigger( 'afterHide' );
			if ( this.eforms[ this.active ].tabs !== _undefined ) {
				this.eforms[ this.active ].tabs.$list.prependTo( this.eforms[ this.active ].$tabs );
			}
		},

		/**
		 * Get values of the active form.
		 *
		 * @return {{}}
		 */
		getValues: function() {
			return ( this.eforms[ this.active ] !== _undefined ) ? this.eforms[ this.active ].getValues() : {};
		},

		/**
		 * Get default values of the active form.
		 *
		 * @return {{}}
		 */
		getDefaults: function() {
			return ( this.defaults[ this.active ] || {} );
		},

		save: function() {
			this.hide();
			this.trigger( 'save', this.getValues(), this.getDefaults() );
		}
	} );
	// Singleton instance
	$ushb.ebuilder = new $ushb.EBuilder;

	/**
	 * $ushb.ExportImport class: a popup with Export/Import dialog.
	 */
	$ushb.ExportImport = function() {
		const self = this;
		self.$body = $( document.body );
		self.$container = $( '.us-bld-window.for_export_import' );
		if ( self.$container.length > 0 ) {
			self.$container.appendTo( self.$body );
			self.init();
		}
	};
	$.extend( $ushb.ExportImport.prototype, $usof.mixins.Events, {

		init: function() {
			this.$closer = $( '.us-bld-window-closer', this.$container );
			this.$importButton = $( '.usof-button.type_save', this.$container );
			this.$row = $( '.usof-form-row', this.$container ).first();
			this.$rowState = $( '.usof-form-row-state', this.$row );
			this.$textarea = $( 'textarea', this.$row );
			this.error = false;

			this._events = {
				import: function( event ) {
					var data = this.$textarea.val();
					if ( data.charAt( 0 ) == '{' ) {
						try {
							data = JSON.parse( data );
							if ( data ) {
								this.trigger( 'import', 'import', data );
								this.hide();
							}
						}
						catch ( error ) {
							this.error = true;
						}

					} else {
						this.error = true;
					}

					if ( this.error ) {
						this.$row.addClass( 'validate_error' );
					}
				}.bind( this ),
				hide: this.hide.bind( this )
			};


			this.$closer.on( 'click', this._events.hide );
			this.$importButton.on( 'click', this._events.import );
		},

		show: function( value, elmsDefaults, optionsDefaults ) {
			// Removing elements default values from export
			for ( var elmId in value.data ) {
				var elmType = elmId.split( ':' )[ 0 ],
					elmParams = value.data[ elmId ] || {},
					elmDefaults = elmsDefaults[ elmType ] || {};

				for ( var param in elmDefaults ) {
					if ( elmParams[ param ] == elmDefaults[ param ] ) {
						delete value.data[ elmId ][ param ];
					}
				}
			}

			// Removing options default values and empty layout cells from export
			var layoutCellsWithValues = [];
			// At first check which layout cells have items in any of responsive states
			for ( var state in value ) {
				if ( state == 'data' ) {
					continue;
				}
				var stateData = value[ state ] || {},
					stateLayout = stateData.layout || {};

				for ( var cellName in stateLayout ) {
					if ( stateLayout[ cellName ].length > 0 && ! $.inArray( cellName, layoutCellsWithValues ) ) {
						layoutCellsWithValues.push( cellName );
					}
				}
			}
			// Then delete empty layout cells and default options
			for ( var state in value ) {
				if ( state == 'data' ) {
					continue;
				}
				var stateData = value[ state ] || {},
					stateOptions = stateData.options || {},
					stateLayout = stateData.layout || {};

				// Layout
				for ( var cellName in stateLayout ) {
					if ( stateLayout[ cellName ].length === 0 && ! $.inArray( cellName, layoutCellsWithValues ) ) {
						delete value[ state ][ 'layout' ][ cellName ];
					}
				}

				// Options
				for ( var optionName in stateOptions ) {
					if ( stateOptions[ optionName ] == optionsDefaults[ optionName ] ) {
						delete value[ state ][ 'options' ][ optionName ];
					}
				}
			}


			this.$textarea.val( JSON.stringify( value ) );
			this.trigger( 'beforeShow' );
			this.$container.css( 'display', 'block' );
			this.trigger( 'afterShow' );
		},

		hide: function() {
			this.trigger( 'beforeHide' );
			this.$row.removeClass( 'validate_error' );
			this.$container.css( 'display', 'none' );
			this.trigger( 'afterHide' );
		}
	} );
	// Singleton instance
	$ushb.exportimport = new $ushb.ExportImport;

	/**
	 * $ushb.HTemplates class: a popup with header templates.
	 */
	$ushb.HTemplates = function() {
		const self = this;
		self.$body = $( document.body );
		self.$container = $( '.us-bld-window.for_templates' );
		self.loaded = false;
		if ( self.$container.length > 0 ) {
			self.$container.appendTo( self.$body );
			self.init();
		}
	};
	$.extend( $ushb.HTemplates.prototype, $usof.mixins.Events, {

		init: function() {
			this.$closer = $( '.us-bld-window-closer', this.$container );
			this.$list = $( '.us-bld-window-list', this.$container );
			this._events = {
				select: function( e ) {
					var $item = $( e.target ).closest( '.us-bld-window-item' );
					if (
						$ushb.instance.value.data
						&& Object.keys( $ushb.instance.value.data ).length
						&& ! confirm( $ushb.instance.translations[ 'template_replace_confirm' ] )
					) {
						return;
					}
					this.hide();
					var data = $( '.us-bld-window-item-data', $item )[0].onclick();
					this.trigger( 'select', $item.data( 'name' ), data );
				}.bind( this ),
				hide: this.hide.bind( this )
			};
			this.$closer.on( 'click', this._events.hide );
			this.$list.on( 'click', '.us-bld-window-item', this._events.select );
		},

		show: function() {
			if ( ! this.loaded ) {
				this.$container.css( 'display', 'block' );
				// Loading elements list html via ajax
				$.ajax( {
					type: 'post',
					url: $ushb.ajaxUrl,
					data: {
						action: 'us_ajax_hb_get_htemplates_html'
					},
					success: function( html ) {
						this.$container.remove();
						this.$container = $( html ).css( 'display', 'none' ).addClass( 'loaded' ).appendTo( $( document.body ) );
						this.loaded = true;
						this.init();
						this.show();
					}.bind( this )
				} );
				return;
			}

			this.trigger( 'beforeShow' );
			this.$container.css( 'display', 'block' );
			this.$body.addClass( 'us-popup' );
			this.trigger( 'afterShow' );
		},

		hide: function() {
			this.trigger( 'beforeHide' );
			this.$body.removeClass( 'us-popup' );
			this.$container.css( 'display', 'none' );
			this.trigger( 'afterHide' );
		}
	} );
	// Singleton instance
	$ushb.htemplates = new $ushb.HTemplates;

	/**
	 * Side settings.
	 */
	function HBOptions( container ) {
		const self = this;
		self.$container = $( container );
		self.$sections = $( '.us-bld-options-section', self.$container );
		self.$sections.not( '.active' ).children( '.us-bld-options-section-content' ).slideUp();
		$( '.us-bld-options-section-title', self.$container ).click( ( event ) => {
			var $parentSection = $( event.target ).parent();
			if ( $parentSection.hasClass( 'active' ) ) {
				return;
			}
			var $previousActive = self.$sections.filter( '.active' );
			self.fireFieldEvent( $previousActive, 'beforeHide' );
			$previousActive.removeClass( 'active' ).children( '.us-bld-options-section-content' ).slideUp( () => {
				self.fireFieldEvent( $previousActive, 'afterHide' );
			} );
			self.fireFieldEvent( $parentSection, 'beforeShow' );
			$parentSection.addClass( 'active' ).children( '.us-bld-options-section-content' ).slideDown( () => {
				self.fireFieldEvent( $parentSection, 'afterShow' );
			} );
		} );

		$( '.usof-subform-row, .usof-subform-wrapper', self.$container ).each( ( index, node ) => {
			node.className = node.className.replace( 'usof-subform-', 'usof-form-' );
		} );

		self.initFields( self.$container );

		var activeSection = self.$sections.filter( '.active' );
		self.fireFieldEvent( activeSection, 'beforeShow' );
		self.fireFieldEvent( activeSection, 'afterShow' );
	};
	$.extend( HBOptions.prototype, $usof.mixins.Fieldset, {
		getValue: function( id ) {
			const self = this;
			if ( id == 'state' ) {
				return $ushb.instance.state;
			}
			if ( self.fields[ id ] === _undefined ) {
				return _undefined;
			}
			return self.fields[ id ].getValue();
		}
	} );

	/**
	 * Change old Twitter icon to the "X" via svg until Font Awesome 5 updates it
	 *
	 * @param {String} iconHtml
	 */
	function modifyTwitterIconHtml( iconHtml ) {
		if ( $ush.toString( iconHtml ).includes( '"fab fa-twitter"' ) ) {
			return `
				<i class="fab fa-x-twitter">
					<svg style="width:1em; margin-bottom:-.1em;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" role="presentation">
						<path fill="currentColor" d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/>
					</svg>
				</i>
			`;
		}
		return iconHtml;
	};

	/**
	 * USOF Field: Header Builder.
	 */
	$usof.field[ 'header_builder' ] = {

		init: function( opts ) {
			const self = this;

			$ushb.instance = self;
			self.parentInit( opts );

			// Elements
			self.$window = $( window );
			self.$body = $( document.body );
			self.$container = $( '.us-bld', self.$row );
			self.$workspace = $( '.us-bld-workspace', self.$container );
			self.$editor = $( '.us-bld-editor' );
			self.$dragshadow = $( '<div class="us-bld-editor-dragshadow"></div>' );
			self.$rows = $( '.us-bld-editor-row', self.$container );
			self.$stateTabs = $( '.us-bld-state', self.$container );

			// Import data from backend.
			var data = {}, $builderData = $( '.us-bld-data', self.$container );
			if ( $builderData.is( '[onclick]' ) ) {
				data = $builderData[0].onclick() || {};
			}

			// Private "Variables"
			self.state = 'default';
			self.admin_labels = data['admin_labels'] || {};
			self.predefined_dynamic_values = data['predefined_dynamic_values'] || {};
			self.elms_titles = data['elms_titles'] || {};
			self.elms_defaults = data['elms_defaults'] || {};
			self.elms_icons = data['elms_icons'] || {};
			self.value = data['value'] || {};
			self.params = data['params'] || {};
			self.opts_defaults = data['options_defaults'] || {};
			self.translations = data['translations'] || {};
			self.states = data['states'] || [ 'default' ];

			// Bindable events.
			self._events = {
				dragEnd: self._dragEnd.bind( self ),
				dragMove: self._dragMove.bind( self ),
				dragStart: self._dragStart.bind( self ),
				maybeDragMove: self._maybeDragMove.bind( self ),

				showExportImportBtnClick: self._showExportImportBtnClick.bind( self ),
				showTemplatesBtnClick: self._showTemplatesBtnClick.bind( self ),

				addBtnClick: self._addBtnClick.bind( self ),
				cloneBtnClick: self._cloneBtnClick.bind( self ),
				deleteBtnClick: self._deleteBtnClick.bind( self ),
				editBtnClick: self._editBtnClick.bind( self ),
			};

			self.$places = { hidden: $( '.us-bld-editor-row.for_hidden > .us-bld-editor-row-h', self.$editor ) };
			$( '.us-bld-editor-cell', self.$editor ).each( ( index, cell ) => {
				const $cell = $( cell );
				self.$places[ $cell.parent().parent().usMod( 'at' ) + '_' + $cell.usMod( 'at' ) ] = $cell;
			} );

			self.$wrappers = {};
			$( '.us-bld-editor-wrapper', self.$editor ).each( ( index, wrapper ) => {
				const $wrapper = $( wrapper );
				self.$wrappers[ $wrapper.data( 'id' ) ] = $wrapper;
			} );

			self.$elms = {};
			$( '.us-bld-editor-elm', self.$editor ).each( ( index, element ) => {
				const $element = $( element );
				self.$elms[ $element.data( 'id' ) ] = $element;
			} );

			self.$templatesBtn = $( '.usof-control.for_templates' )
				.on( 'click', self._events.showTemplatesBtnClick );

			$( '.usof-control.for_import' )
				.on( 'click', self._events.showExportImportBtnClick );

			// Elements modification events
			self.$container
				.on( 'click', '.us-bld-editor-add, .us-bld-editor-control.type_add, .us-bld-editor-wrapper-content:empty', self._events.addBtnClick )
				.on( 'click', '.us-bld-editor-control.type_edit', self._events.editBtnClick )
				.on( 'click', '.us-bld-editor-control.type_clone', self._events.cloneBtnClick )
				.on( 'mousedown', '.us-bld-editor-elm, .us-bld-editor-wrapper', self._events.dragStart )
				.on( 'click', '.us-bld-editor-control.type_delete', self._events.deleteBtnClick )

				// Preventing browser native drag event
				.on( 'dragstart', ( e ) => { e.preventDefault() } );

			// Options that has no responsive values
			self.sharedOpts = [ 'top_fullwidth', 'middle_fullwidth', 'bottom_fullwidth' ];

			self.sideOpts = new HBOptions( $( '.us-bld-options:first', self.$container ) );

			$.each( self.sideOpts.fields, ( fieldId, field ) => {
				field.on( 'change', self._optionChanged.bind( self ) );
			} );

			$( 'input', self.sideOpts.fields.orientation.$row ).on( 'click', ( e ) => {
				const $target = $( e.target );
				// Fix for Safari, not to lose active button state
				if ( $target.val() == self.value[ self.state ].options.orientation ) {
					$target.attr( 'checked', 'checked' );
					return false;
				}
				if ( ! confirm( self.translations[ 'orientation_change_confirm' ] ) ) {
					e.preventDefault();
					e.stopPropagation();
				}
			} );

			// State togglers
			self.$stateTabs.on( 'click', ( e ) => {
				self.setState( $( e.target ).usMod( 'ui-icon_devices' ) );
			} );

			// Highlight rows on side options hover
			$( '.us-bld-options-section', self.$container ).each( ( _, section ) => {
				var $section = $( section ),
					id = $section.data( 'id' );
				$section.hover(
					() => { self.$editor.addClass( `highlight_${id}` ); },
					() => { self.$editor.removeClass( `highlight_${id}` ); }
				);
			} );

			// Showing templates for empty case
			if ( ! self.value.data || ! Object.keys( self.value.data ).length ) {
				self.$templatesBtn.addClass( 'start' );
			}
		},

		setValue: function( value ) {
			// Fixing missing datas
			if ( ! value ) {
				value = {};
			}
			if ( value.data === _undefined ) {
				value.data = {};
			}
			if ( value.default === _undefined ) {
				value.default = {};
			}
			if ( value.default.options === _undefined ) {
				value.default.options = {};
			}
			this.value = $.extend( {}, value );
			this.setState( 'default', true );
		},

		getValue: function() {
			return this.value;
		},

		_addBtnClick: function( event ) {
			var $target = $( event.target ),
				placeType, place;
			if ( $target.hasClass( 'us-bld-editor-add' ) ) {
				var $cell = $target.closest( '.us-bld-editor-cell' );
				place = $cell.parent().parent().usMod( 'at' ) + '_' + $cell.usMod( 'at' );
				placeType = 'cell';
			} else {
				place = $target.closest( '.us-bld-editor-wrapper' ).data( 'id' );
				placeType = place.split( ':' )[ 0 ];
			}
			$ushb.elist.off( 'beforeShow' ).on( 'beforeShow', function() {
				$ushb.elist.$container
					.toggleClass( 'hide_search', this.value.data[ 'search:1' ] !== _undefined )
					.toggleClass( 'hide_cart', this.value.data[ 'cart:1' ] !== _undefined )
					.usMod( 'orientation', this.value[ this.state ].options.orientation )
					.usMod( 'addto', placeType );
			}.bind( this ) );
			$ushb.elist.off( 'select' ).on( 'select', function( elist, type ) {
				var elmId = this.createElement( place, type );
				// Opening editing form for standard elements
				if ( type.substr( 1 ) != 'wrapper' ) {
					$( '.us-bld-editor-control.type_edit', this.$elms[ elmId ] ).trigger( 'click' );
				}
			}.bind( this ) );
			$ushb.elist.show();
		},

		_editBtnClick: function( event ) {
			var $target = $( event.target ),
				$elm = $target.closest( '.us-bld-editor-elm, .us-bld-editor-wrapper' ),
				id = $elm.data( 'id' ),
				type = id.split( ':' )[ 0 ],
				values = ( this.value.data[ id ] || {} );
			$ushb.ebuilder.off( 'save' ).on( 'save', function( ebuilder, values, defaults ) {
				this.updateElement( id, values );
			}.bind( this ) );
			$ushb.ebuilder.show( type, values );
		},

		_cloneBtnClick: function( event ) {
			var $target = $( event.target ),
				$elm = $target.closest( '.us-bld-editor-elm, .us-bld-editor-wrapper' ),
				id = $elm.data( 'id' ),
				type = id.split( ':' )[ 0 ];
			// createElement: function(place, type, index, values){
			var newId = this.createElement( 'top_left', type, _undefined, this.value.data[ id ] || {} );
			this.states.forEach( function( state ) {
				this.moveElement( newId, id, 'after', state );
			}.bind( this ) );
		},

		_deleteBtnClick: function( event ) {
			var $target = $( event.target );
			if ( ! confirm( this.translations[ 'element_delete_confirm' ] ) ) {
				return;
			}
			var id = $target.parent().parent().data( 'id' );
			this.deleteElement( id );
		},

		_showTemplatesBtnClick: function( event ) {
			if ( event !== _undefined ) {
				event.preventDefault();
			}
			$ushb.htemplates.off( 'select' ).on( 'select', function( dialog, name, data ) {
				this.setValue( data );
				this.trigger( 'change', this.value );
			}.bind( this ) );
			$ushb.htemplates.show();
			this.$templatesBtn.removeClass( 'start' );
		},

		_showExportImportBtnClick: function( event ) {
			event.preventDefault();
			$ushb.exportimport.off( 'import' ).on( 'import', function( dialog, name, value ) {
				// Fill missing default options
				for ( var state in value ) {
					if ( state == 'data' ) {
						continue;
					}

					for ( var optionName in this.opts_defaults ) {
						if (
							value[ state ][ 'options' ] !== _undefined
							&& value[ state ][ 'options' ][ optionName ] === _undefined
						) {
							value[ state ][ 'options' ][ optionName ] = this.opts_defaults[ optionName ];
						}
					}
				}

				this.setValue( value );
				this.trigger( 'change', this.value );
			}.bind( this ) );
			$ushb.exportimport.show( this.getValue(), this.elms_defaults, this.opts_defaults );
		},

		_hasClass: function( elm, cls ) {
			return ( ' ' + elm.className + ' ' ).indexOf( ' ' + cls + ' ' ) > - 1;
		},

		_isShadow: function( elm ) {
			return this._hasClass( elm, 'us-bld-editor-dragshadow' );
		},

		_isSortable: function( elm ) {
			return this._hasClass( elm, 'us-bld-editor-elm' ) || this._hasClass( elm, 'us-bld-editor-wrapper' );
		},

		_isWrapperContent: function( elm ) {
			return this._hasClass( elm, 'us-bld-editor-wrapper-content' );
		},

		_isControls: function( elm ) {
			return this._hasClass( elm, 'us-bld-editor-add' );
		},

		setState: function( newState, force ) {
			if ( newState == this.state && ! force ) {
				return;
			}
			// Changing the active tab setting
			this.$stateTabs.removeClass( 'active' ).filter( '.ui-icon_devices_' + newState ).addClass( 'active' );
			this.$workspace.usMod( 'for', newState );
			this.state = newState;
			// Changing side options view
			if ( this.value[ newState ].options !== _undefined ) {
				var options = $.extend( {}, this.value[ newState ].options );
				if ( newState != 'default' ) {
					for ( var i = 0; i < this.sharedOpts.length; i ++ ) {
						options[ this.sharedOpts[ i ] ] = this.value.default.options[ this.sharedOpts[ i ] ];
					}
				}
				this.setOptions( options );
			}
			this.renderLayout();
		},

		/**
		 * Create element at the end of the specified place.
		 *
		 * @param {String} place Place Cell name or wrapper ID
		 * @param {String} type Element type Element type
		 * @param {Number} [index] Element index, starting from 1. If not set will be generated automatically.
		 * @param {{}} [values] Element values
		 *
		 * @return {String} New element ID
		 */
		createElement: function( place, type, index, values ) {
			if ( index === _undefined ) {
				// If index is not defined generating a spare one
				index = 1;
				while ( this.value.data[ type + ':' + index ] !== _undefined ) {
					index ++;
				}
			}
			var id = type + ':' + index;
			for ( var i = 0, state = this.states[ i ]; i < this.states.length; state = this.states[ ++ i ] ) {
				if ( this.value[ state ] === _undefined ) {
					this.value[ state ] = {};
				}
				if ( this.value[ state ].layout === _undefined ) {
					this.value[ state ].layout = {};
				}
				if ( this.value[ state ].layout[ place ] === _undefined ) {
					this.value[ state ].layout[ place ] = [];
				}
				this.value[ state ].layout[ place ].push( id );
				if ( type.substr( 1 ) == 'wrapper' ) {
					this.value[ state ].layout[ id ] = [];
				}
			}
			this.value.data[ id ] = $.extend( {}, this.elms_defaults[ type ] || {}, values || {} );
			this.renderLayout();
			this.trigger( 'change', this.value );
			return id;
		},

		/**
		 * Move a specified element to a specified place.
		 *
		 * @param {String} id Element ID
		 * @param {String} place Cell name or element ID
		 * @param {String} [position] Relation to place: "last_child" / "first_child" / "before" / "after"
		 *
		 * @param {String} [state] If not specified, the current active state will be used
		 */
		moveElement: function( id, place, position, state ) {
			if ( this.value.data[ id ] === _undefined ) {
				return;
			}
			position = position || 'last_child';
			state = state || this.state;
			if ( this.value[ state ] === _undefined ) {
				this.value[ state ] = {};
			}
			if ( this.value[ state ].layout === _undefined ) {
				this.value[ state ].layout = {};
			}
			// Cropping out the element from the previous place ...
			var plc, elmPos;
			for ( plc in this.value[ state ].layout ) {
				if ( ! this.value[ state ].layout.hasOwnProperty( plc ) ) {
					continue;
				}
				elmPos = this.value[ state ].layout[ plc ].indexOf( id );
				if ( elmPos != - 1 ) {
					this.value[ state ].layout[ plc ].splice( elmPos, 1 );
				}
			}
			// ... and placing it to the new one
			if ( position == 'first_child' || position == 'last_child' ) {
				if ( this.value[ state ].layout[ place ] === _undefined ) {
					this.value[ state ].layout[ place ] = [];
				}
				this.value[ state ].layout[ place ][ ( position == 'first_child' ) ? 'unshift' : 'push' ]( id );
			} else if ( position == 'before' || position == 'after' ) {
				for ( plc in this.value[ state ].layout ) {
					if ( ! this.value[ state ].layout.hasOwnProperty( plc ) ) {
						continue;
					}
					elmPos = this.value[ state ].layout[ plc ].indexOf( place );
					if ( elmPos != - 1 ) {
						this.value[ state ].layout[ plc ].splice( elmPos + ( ( position == 'after' ) ? 1 : 0 ), 0, id );
						break;
					}
				}
			}
			this.renderLayout();
			this.trigger( 'change', this.value );
		},

		/**
		 * Update the specified element's values.
		 *
		 * @param {String} id Element ID
		 * @param {{}} values Element values
		 */
		updateElement: function( id, values ) {
			var type = id.split( ':' )[0];
			this.value.data[ id ] = $.extend( {}, this.elms_defaults[ type ] || {}, values );
			var $elm = this[ ( type.substr( 1 ) == 'wrapper' ) ? '$wrappers' : '$elms' ][ id ];
			if ( $elm !== _undefined ) {
				this.updateElementPlaceholder( $elm, id, this.value.data[ id ] );
			}
			this.trigger( 'change', this.value );
		},

		/**
		 * Delete the specified element.
		 *
		 * @param {String} id Element ID
		 */
		deleteElement: function( id ) {
			var type = id.split( ':' )[0];
			for ( var i = 0, state = this.states[ i ]; i < this.states.length; state = this.states[ ++ i ] ) {
				if ( this.value[ state ] === _undefined ) {
					this.value[ state ] = {};
				}
				if ( this.value[ state ].layout === _undefined ) {
					this.value[ state ].layout = {};
				}
				if ( this.value[ state ].layout.hidden === _undefined ) {
					this.value[ state ].layout.hidden = [];
				}
				if ( id.substr( 1, 7 ) == 'wrapper' && this.value[ state ].layout[ id ] !== _undefined ) {
					// Moving wrapper's inner elements to hidden block
					this.value[ state ].layout.hidden = this.value[ state ].layout.hidden.concat( this.value[ state ].layout[ id ] );
					delete this.value[ state ].layout[ id ];
				}
				for ( var plc in this.value[ state ].layout ) {
					if ( ! this.value[ state ].layout.hasOwnProperty( plc ) ) {
						continue;
					}
					var elmPos = this.value[ state ].layout[ plc ].indexOf( id );
					if ( elmPos != - 1 ) {
						this.value[ state ].layout[ plc ].splice( elmPos, 1 );
						break;
					}
				}
			}
			if ( this.value.data[ id ] !== _undefined ) {
				delete this.value.data[ id ];
			}
			this.renderLayout();
			this.trigger( 'change', this.value );
		},

		/**
		 * Load attachments withing the given jQuery DOM object.
		 *
		 * @param {jQuery} $html
		 */
		_loadAttachments: function( $html ) {
			$( 'img[data-wpattachment]', $html ).each( function( index, elm ) {
				var $elm = $( elm ),
					id = $elm.data( 'wpattachment' ),
					attachment = wp.media.attachment( id );
				if ( ! attachment || ! attachment.attributes.id ) {
					return '';
				}
				var renderAttachmentImage = function() {
					var src = attachment.attributes.url;
					if ( attachment.attributes.sizes !== _undefined ) {
						var size = ( attachment.attributes.sizes.medium !== _undefined ) ? 'medium' : 'full';
						src = attachment.attributes.sizes[ size ].url;
					}
					$elm.attr( 'src', src ).removeAttr( 'data-wpattachment' );
				};
				if ( attachment.attributes.url !== _undefined ) {
					renderAttachmentImage();
				} else {
					// Loading missing data via ajax
					attachment.fetch( { success: renderAttachmentImage } );
				}
			}.bind( this ) );
		},

		/**
		 * Create a base part of elements DOM placeholder: the one that doesn't depend on values.
		 *
		 * @param {String} id
		 *
		 * @return {jQuery} Created (but not placed to document) placeholder's DOM element
		 */
		createElementPlaceholderBase: function( id ) {
			const self = this;
			const elmType = id.split( ':' )[0];

			var html = '';

			// Wrappers
			if ( elmType.substr( 1 ) == 'wrapper' ) {
				html = `
					<div class="us-bld-editor-wrapper type_${ elmType == 'hwrapper' ? 'horizontal' : 'vertical' } empty">
						<div class="us-bld-editor-wrapper-content"></div>
						<div class="us-bld-editor-wrapper-controls">
							<a title="${self.translations[ 'add_element' ]}" class="us-bld-editor-control type_add" href="javascript:void(0)"></a>
							<a title="${self.translations[ 'edit_wrapper' ]}" class="us-bld-editor-control type_edit" href="javascript:void(0)"></a>
							<a title="${self.translations[ 'delete_wrapper' ]}" class="us-bld-editor-control type_delete" href="javascript:void(0)"></a>
						</div>
					</div>
				`;
				self.$wrappers[ id ] = $( html ).data( 'id', id );
				return self.$wrappers[ id ];

				// Standard elements
			} else {

				html += `
					<div class="us-bld-editor-elm type_${elmType}">
						<div class="us-bld-editor-elm-content">
							${ ( elmType == 'btn' ) ? '<button type="button">' : '' }
								<span class="us-bld-editor-elm-icon"></span>
								<span class="us-bld-editor-elm-value"></span>
							${ ( elmType == 'btn' ) ? '</button>' : '' }
						</div>
						<div class="us-bld-editor-elm-controls">
							<a href="javascript:void(0)" class="us-bld-editor-control type_edit" title="${self.translations[ 'edit_element' ]}"></a>
							<a href="javascript:void(0)" class="us-bld-editor-control type_clone" title="${self.translations[ 'clone_element' ]}"></a>
							<a href="javascript:void(0)" class="us-bld-editor-control type_delete" title="${self.translations[ 'delete_element' ]}"></a>
						</div>
					</div>
				`;
				self.$elms[ id ] = $( html ).data( 'id', id );
				return self.$elms[ id ];
			}
		},

		/**
		 * Updates a DOM element's placeholder with current values.
		 *
		 * @param {jQuery} $element
		 * @param {String} elementId
		 * @param {{}} values
		 */
		updateElementPlaceholder: function ( $element, elementId, values ) {
			const self = this;

			// Skip wrapper elements
			if ( elementId.startsWith( 'wrapper:', 1 ) ) {
				return;
			}

			const elementType = elementId.split(':')[0];

			// Merge defaults with provided values
			values = $.extend( {}, self.elms_defaults[ elementType ] || {}, values || {} );

			var iconHtml = '',
				labelHtml = '';
			const adminLabels = self.admin_labels[ elementType ] || {};
			const popupAsButton = ( elementType === 'popup' && values.show_on === 'btn' );
			const popupAsIcon = ( elementType === 'popup' && values.show_on === 'icon' );
			const popupTriggerNotButton = ( elementType === 'popup' && values.show_on !== 'btn' );

			// Determine icon
			if ( values.icon ) {
				iconHtml = $usof.instance.prepareIconTag( values.icon );
			} else if ( popupAsButton ) {
				iconHtml = $usof.instance.prepareIconTag( $ush.toString( values.btn_icon ) );
			} else if ( ! [ 'text', 'btn' ].includes( elementType ) ) {
				iconHtml = `<i class="${self.elms_icons[ elementType ] || ''}"></i>`;
			}

			// Determine admin label
			const paramName = adminLabels.param_name;
			const paramOptions = adminLabels.param_options || {};

			if ( paramName && ! $ush.isUndefined( values[ paramName ] ) ) {
				labelHtml = values[ paramName ];
			}

			// Show Popup via "Icon"
			if ( popupAsIcon ) {
				if ( values.btn_icon ) {
					iconHtml = $usof.instance.prepareIconTag( values.btn_icon );
				}
				labelHtml = '';
			}

			// Show label "Popup"
			if ( popupTriggerNotButton ) {
				labelHtml = '';
			}

			// IMAGE
			if ( elementType === 'image' ) {
				if ( $ush.parseInt( values[ paramName ] ) ) {
					iconHtml = `<img src="" data-wpattachment="${values[ paramName ]}" alt=""/>`;
					labelHtml = '';

					// If value is url put it directly (template import case)
				} else if (
					typeof values[ paramName ] === 'string'
					&& (
						values[ paramName ].startsWith( 'http://' )
						|| values[ paramName ].startsWith( 'https://' )
					)
				) {
					iconHtml = `<img src="${values[ paramName ]}" alt=""/>`;
					labelHtml = '';
				}
			}
			// MENU / ADDITIONAL_MENU
			else if ( [ 'menu', 'additional_menu' ].includes( elementType ) && values.source ) {
				labelHtml = self.params.navMenus[ values.source ] || values.source;
			}
			// DROPDOWN
			else if ( elementType === 'dropdown' && values.source ) {
				if ( values.link_icon ) {
					iconHtml = $usof.instance.prepareIconTag( values.link_icon );
				}
				labelHtml = (
					values.source === 'own'
						? values.link_title
						: paramOptions[ values.source ] || labelHtml
				);
			}
			// SEARCH
			else if ( elementType === 'search' && values.text ) {
				labelHtml = $ush.stripTags( values.text );
			}
			// SOCIALS
			else if ( elementType === 'socials' ) {
				var socialsHtml = '';
				const items = values.items || [];
				for ( var i = 0, len = items.length; i < len; i++ ) {
					const item = items[ i ];
					if ( item.type === 'custom' ) {
						const str = item.icon.trim();
						const sepIndex = str.indexOf('|');
						const iconSet = sepIndex !== -1 ? str.substring( 0, sepIndex ) : str;
						const iconName = sepIndex !== -1 ? str.substring( sepIndex + 1 ) : '';

						if ( iconName ) {
							socialsHtml += ( iconSet === 'material' )
								? `<i class="material-icons">${iconName}</i>`
								: `<i class="${iconSet} fa-${iconName}"></i>`;
						}
					} else {
						socialsHtml += modifyTwitterIconHtml( `<i class="fab fa-${item.type}"></i>` );
					}
				}
				if ( values.custom_icon && values.custom_url ) {
					socialsHtml += $usof.instance.prepareIconTag( values.custom_icon );
				}
				if ( socialsHtml ) {
					iconHtml = '';
				}
				labelHtml += socialsHtml || self.translations.social_links;
			}

			// Resolve dynamic variables
			labelHtml = paramOptions[ labelHtml ] || self.predefined_dynamic_values[ labelHtml ] || labelHtml;
			if ( self.isDynamicVariable( labelHtml ) ) {
				const varName = labelHtml.replace( /^{{([\dA-z\/\|\-_]+)}}$/, '$1' );
				labelHtml = self.predefined_dynamic_values[ varName ] || labelHtml;
			}

			// Fallback label
			if (
				! [ 'text', 'btn' ].includes( elementType )
				&& ! popupAsButton
				&& ! popupAsIcon
				&& ! labelHtml
				&& ! iconHtml.includes( '<img' )
			) {
				labelHtml = $ush.toString( self.elms_titles[ elementType ] );
			}

			// Update DOM
			const $content = $( '.us-bld-editor-elm-content', $element );
			$( '.us-bld-editor-elm-value', $content ).html( labelHtml );

			// Popup preview as button
			if ( elementType === 'popup' ) {
				const $button = $( '> button:first', $content );
				if ( popupAsButton && $button.length === 0 ) {
					$content.wrapInner( '<button type="button"></button>' );
				} else if ( ! popupAsButton && $button.length ) {
					$content.html( $button.html() );
				}
			}

			// Icon placement
			const $icon = $( '.us-bld-editor-elm-icon', $content ).html( iconHtml );
			const iconPosition = values.iconpos || values.btn_iconpos;

			if ( iconPosition ) {
				const $target = ( elementType === 'btn' || popupAsButton )
					? $( 'button', $content )
					: $content;
				$target[ iconPosition === 'right' ? 'append' : 'prepend']( $icon );
			}

			self._loadAttachments( $content );
		},

		/**
		 * Create DOM placeholder element for the specified header builder element / wrapper.
		 *
		 * @param {String} id Element ID
		 * @param {{}} [values]
		 *
		 * @return {jQuery} Created (but not yet placed to document) jQuery object with the element's DOMElement
		 */
		_createElementPlaceholder: function( id, values ) {
			const self = this;
			const type = id.split( ':' )[0];
			const $element = self.createElementPlaceholderBase( id );
			self.updateElementPlaceholder( $element, id, values );
			return $element;
		},

		/**
		 * Delete DOM placeholder for the specified header element / wrapper.
		 *
		 * @param {String} id
		 */
		_removeElementPlaceholder: function( id ) {
			const self = this;
			const container = ( id.substr( 1, 7 ) == 'wrapper' ) ? '$wrappers' : '$elms';
			if ( self[ container ][ id ] === _undefined ) {
				return;
			}
			self[ container ][ id ].remove();
			delete self[ container ][ id ];
		},

		/**
		 * Render current layout based on current value and state.
		 */
		renderLayout: function() {
			// Making sure the provided data is consistent
			if ( this.value.data === _undefined || this.value.data instanceof Array ) {
				this.value.data = {};
			}
			if ( this.value[ this.state ].layout === _undefined ) {
				this.value[ this.state ].layout = {};
			}
			if ( this.value[ this.state ].layout.hidden === _undefined ) {
				this.value[ this.state ].layout.hidden = [];
			}
			var elmsInNextLayout = [],
				plc, i, elmId;
			for ( plc in this.value[ this.state ].layout ) {
				if ( ! this.value[ this.state ].layout.hasOwnProperty( plc ) ) {
					continue;
				}
				for ( i = 0; i < this.value[ this.state ].layout[ plc ].length; i ++ ) {
					var id = this.value[ this.state ].layout[ plc ][ i ],
						type = id.split( ':' )[0];
					if ( this.value.data[ id ] === _undefined ) {
						this.value.data[ id ] = $.extend( {}, this.elms_defaults[ type ] || {} );
					}
					elmsInNextLayout.push( this.value[ this.state ].layout[ plc ][ i ] );
				}
			}
			for ( elmId in this.value.data ) {
				if ( ! this.value.data.hasOwnProperty( elmId ) ) {
					continue;
				}
				if ( elmsInNextLayout.indexOf( elmId ) == - 1 ) {
					this.value[ this.state ].layout.hidden.push( elmId );
				}
			}
			// Retrieving the currently shown layout structure
			var prevLayout = {},
				parsePlace = function( place, $place ) {
					if ( $place.hasClass( 'us-bld-editor-wrapper' ) ) {
						$place = $place.children( '.us-bld-editor-wrapper-content' );
					}
					prevLayout[ place ] = [];
					$place.children().each( function( index, elm ) {
						var $elm = $( elm ),
							id = $elm.data( 'id' );
						if ( ! id ) {
							return;
						}
						prevLayout[ place ].push( id );
					} );
				};
			$.each( this.$places, parsePlace );
			$.each( this.$wrappers, parsePlace );
			// Iteratively looping through the needed structure
			for ( plc in this.value[ this.state ].layout ) {
				if ( ! this.value[ this.state ].layout.hasOwnProperty( plc ) ) {
					continue;
				}
				if ( plc.indexOf( ':' ) != - 1 && prevLayout[ plc ] === _undefined ) {
					// Creating the missing wrapper
					if ( this.$wrappers[ plc ] === _undefined ) {
						this._createElementPlaceholder( plc, this.value.data[ plc ] );
					}
					prevLayout[ plc ] = [];
				}
				var $place = ( plc.indexOf( ':' ) == - 1 )
					? this.$places[ plc ]
					: this.$wrappers[ plc ].children( '.us-bld-editor-wrapper-content' );
				for ( i = 0; i < this.value[ this.state ].layout[ plc ].length; i ++ ) {
					elmId = this.value[ this.state ].layout[ plc ][ i ];
					var $elm = this[ ( elmId.substr( 1, 7 ) == 'wrapper' ) ? '$wrappers' : '$elms' ][ elmId ];
					if ( $elm === _undefined ) {
						$elm = this._createElementPlaceholder( elmId, this.value.data[ elmId ] );
					}
					if ( prevLayout[ plc ][ i ] != elmId ) {
						if ( i == 0 ) {
							$elm.prependTo( $place );
						} else {
							var prevElmId = this.value[ this.state ].layout[ plc ][ i - 1 ],
								$prevElm = this[ ( prevElmId.substr( 1, 7 ) == 'wrapper' ) ? '$wrappers' : '$elms' ][ prevElmId ];
							$elm.insertAfter( $prevElm );
						}
						prevLayout[ plc ].splice( i, 0, elmId );
					}
				}
			}
			// Removing excess elements
			for ( plc in prevLayout ) {
				if ( ! prevLayout.hasOwnProperty( plc ) ) {
					continue;
				}
				for ( i = 0, elmId = prevLayout[ plc ][ i ]; i < prevLayout[ plc ].length; i ++, elmId = prevLayout[ plc ][ i ] ) {
					if ( this.value.data[ elmId ] === _undefined ) {
						this._removeElementPlaceholder( elmId );
					}
				}
			}
			// Updating elements' placeholders contents
			for ( elmId in this.$elms ) {
				if ( ! this.$elms.hasOwnProperty( elmId ) ) {
					continue;
				}
				this.updateElementPlaceholder( this.$elms[ elmId ], elmId, this.value.data[ elmId ] );
			}
			// Fixing wrappers
			$( '.us-bld-editor-wrapper', this.$container )
				.removeClass( 'empty' )
				.find( '.us-bld-editor-wrapper-content:empty' )
				.parent()
				.addClass( 'empty' );
		},

		/**
		 * Event that is called on manual side option change.
		 *
		 * @param {$usof.Field} field
		 */
		_optionChanged: function( field ) {
			if ( this.ignoreOptionsChanges ) {
				return;
			}
			var fieldId = field.name,
				value = field.getValue(),
				state = ( $.inArray( fieldId, this.sharedOpts ) != - 1 )
					? 'default'
					: this.state;
			if ( this.value[ state ] === _undefined ) {
				this.value[ state ] = {};
			}
			if ( this.value[ state ].options === _undefined ) {
				this.value[ state ].options = {};
			}

			if ( this.value[ state ].options[ fieldId ] != value ) {
				this.value[ state ].options[ fieldId ] = value;
				this.renderOptions();
				this.trigger( 'change', this.value );
			}
		},

		/**
		 * Change side options.
		 *
		 * @param {{}} opts
		 */
		setOptions: function( opts ) {
			const self = this;
			self.ignoreOptionsChanges = true;
			self.sideOpts.setValues( opts );
			self.ignoreOptionsChanges = false;
			self.renderOptions();
		},

		/**
		 * Render current options.
		 */
		renderOptions: function() {
			var prevOrientation = this.$editor.usMod( 'type' ),
				nextOrientation = this.value[ this.state ].options.orientation || 'hor';
			if ( nextOrientation != prevOrientation ) {
				this.$editor.usMod( 'type', nextOrientation );
				if ( nextOrientation == 'ver' ) {
					// Moving elements from removed cells to remaining ones
					if ( this.value[ this.state ].layout.hidden === _undefined ) {
						this.value[ this.state ].layout.hidden = [];
					}
					for ( var place in this.value[ this.state ].layout ) {
						if ( ! this.value[ this.state ].layout.hasOwnProperty( place ) ) {
							continue;
						}
						if ( place.indexOf( ':' ) != - 1 || place == 'hidden' || place.substr( place.length - 5 ) == '_left' ) {
							continue;
						}
						var align = place.split( '_' ),
							newPlace = ( align.length == 2 )
								? ( align[0] + '_left' )
								: 'hidden';
						if ( this.value[ this.state ].layout[ newPlace ] === _undefined ) {
							this.value[ this.state ].layout[ newPlace ] = [];
						}
						this.value[ this.state ].layout[ newPlace ] = this.value[ this.state ].layout[ newPlace ].concat( this.value[ this.state ].layout[ place ] );
						this.value[ this.state ].layout[ place ] = [];
					}
					this.renderLayout();
				}
			}
			$.each( [ 'top', 'bottom' ], function( index, vpos ) {
				var $row = this.$rows.filter( '.at_' + vpos ),
					prevShown = ! $row.hasClass( 'disabled' ),
					nextShown = ! ! parseInt( this.value[ this.state ].options[ vpos + '_show' ] * 1 );
				if ( prevShown != nextShown ) {
					$row.toggleClass( 'disabled', ! nextShown );
				}
			}.bind( this ) );
		}
	};

	// Drag & drop functions
	$.extend( $usof.field[ 'header_builder' ], {
		_dragStart: function( e ) {
			e.stopPropagation();
			this.$draggedElm = $( e.target ).closest( '.us-bld-editor-elm, .us-bld-editor-wrapper' );
			this.elmType = this.$draggedElm.data( 'id' ).split( ':' )[0];
			this.detached = false;
			this._updateBlindSpot( e );
			this.elmPointerOffset = [ parseInt( e.pageX ), parseInt( e.pageY ) ];
			this.$body.on( 'mousemove', this._events.maybeDragMove );
			this.$window.on( 'mouseup', this._events.dragEnd );
		},

		_updateBlindSpot: function( e ) {
			this.blindSpot = [ e.pageX, e.pageY ];
		},

		_isInBlindSpot: function( e ) {
			return Math.abs( e.pageX - this.blindSpot[ 0 ] ) <= 20 && Math.abs( e.pageY - this.blindSpot[ 1 ] ) <= 20;
		},

		_maybeDragMove: function( e ) {
			e.stopPropagation();
			if ( this._isInBlindSpot( e ) ) {
				return;
			}
			this.$body.off( 'mousemove', this._events.maybeDragMove );
			this._detach();
			this.$body.on( 'mousemove', this._events.dragMove );
		},

		_dragMove: function( e ) {
			e.stopPropagation();
			this.$draggedElm.css( {
				left: e.pageX - this.elmPointerOffset[0],
				top: e.pageY - this.elmPointerOffset[1]
			} );
			if ( this._isInBlindSpot( e ) ) {
				return;
			}
			var elm = e.target;
			// Checking two levels up
			for ( var level = 0; level <= 2; level ++, elm = elm.parentNode ) {
				if ( this._isShadow( elm ) ) {
					return;
				}

				var parentType;
				if ( this._isSortable( elm ) ) {
					parentType = this._isWrapperContent( elm.parentNode ) ? ( $( elm ).parent().parent().usMod( 'type' )[0] + 'wrapper' ) : 'cell';

					// Dropping element before or after sortables based on their relative position in DOM
					var nextElm = elm.previousSibling,
						shadowAtLeft = false;
					while ( nextElm ) {
						if ( nextElm == this.$dragshadow[0] ) {
							shadowAtLeft = true;
							break;
						}
						nextElm = nextElm.previousSibling;
					}
					this.$dragshadow[ shadowAtLeft ? 'insertAfter' : 'insertBefore' ]( elm );
					this._dragDrop( e );
					break;
				} else if ( this._isWrapperContent( elm ) ) {
					if ( $.contains( elm, this.$dragshadow[0] ) ) {
						break;
					}
					parentType = $( elm ).parent().usMod( 'type' )[0] + 'wrapper';

					// Cannot drop a wrapper to the wrapper of the same type
					this.$dragshadow.appendTo( elm );
					this._dragDrop( e );
					break;
				} else if ( this._isControls( elm ) ) {

					// Always dropping element before controls
					this.$dragshadow.insertBefore( elm );
					this._dragDrop( e );
					break;
				} else if ( this._hasClass( elm, 'us-bld-editor-cell' ) ) {

					// If not already in this cell, moving to it
					var $shadowCell = this.$dragshadow.closest( '.us-bld-editor-cell' );
					if ( $shadowCell.length == 0 || $shadowCell[0] != elm ) {
						this.$dragshadow.insertBefore( $( '.us-bld-editor-add', elm ) );
						this._dragDrop( e );
					}
					break;
				} else if ( this._hasClass( elm, 'us-bld-editor-row for_hidden' ) ) {
					// Moving to hidden elements container directly
					if ( ! this.$dragshadow.closest( '.us-bld-editor-row' ).hasClass( 'for_hidden' ) ) {
						this.$dragshadow.appendTo( $( elm ).children( '.us-bld-editor-row-h' ) );
						this._dragDrop( e );
					}
					break;
				}
			}
		},

		_detach: function() {
			var offset = this.$draggedElm.offset();
			this.elmPointerOffset[0] -= offset.left;
			this.elmPointerOffset[1] -= offset.top;
			this.$dragshadow.css( {
				width: this.$draggedElm.outerWidth(),
				height: this.$draggedElm.outerHeight()
			} ).insertBefore( this.$draggedElm );
			this.$draggedElm.css( {
				position: 'absolute',
				'pointer-events': 'none',
				zIndex: 10000,
				width: this.$draggedElm.width(),
				height: this.$draggedElm.height()
			} ).css( offset ).appendTo( this.$body );
			this.$editor.addClass( 'dragstarted' );
			this.detached = true;
		},

		_dragDrop: function( e ) {
			$( '.us-bld-editor-wrapper', this.$container )
				.removeClass( 'empty' )
				.find( '.us-bld-editor-wrapper-content:empty' )
				.parent()
				.addClass( 'empty' );
			this._updateBlindSpot( e );
		},

		_dragEnd: function() {
			this.$body.off( 'mousemove', this._events.maybeDragMove ).off( 'mousemove', this._events._dragMove );
			this.$window.off( 'mouseup', this._events.dragEnd );
			if ( this.detached ) {
				this.$draggedElm.removeAttr( 'style' ).insertBefore( this.$dragshadow );
				this.$dragshadow.detach();
				this.$editor.removeClass( 'dragstarted' );
				// Getting the new element position and performing the actual drag
				var elmId = this.$draggedElm.data( 'id' ),
					$prev = this.$draggedElm.prev();
				if ( $prev.length == 0 ) {
					var $parent = this.$draggedElm
							.parent()
							.closest( '.us-bld-editor-cell, .us-bld-editor-wrapper, .us-bld-editor-row.for_hidden' ),
						place = 'hidden';
					if ( $parent.hasClass( 'us-bld-editor-cell' ) ) {
						place = $parent.parent().parent().usMod( 'at' ) + '_' + $parent.usMod( 'at' );
					} else if ( $parent.hasClass( 'us-bld-editor-wrapper' ) ) {
						place = $parent.data( 'id' );
					}
					this.moveElement( elmId, place, 'first_child' )
				} else {
					this.moveElement( elmId, $prev.data( 'id' ), 'after' );
				}
			}
		},
	} );

}( jQuery );

;jQuery( function( $, _undefined ) {

	function USHB( container ) {
		this.$container = $( container );
		if ( ! this.$container.length ) {
			return;
		}
		this.initFields( this.$container );

		this.fireFieldEvent( this.$container, 'beforeShow' );
		this.fireFieldEvent( this.$container, 'afterShow' );

		// Save action
		this.$saveControl = $( '.usof-control.for_save', this.$container );
		this.$saveBtn = $( '.usof-button', this.$saveControl ).on( 'click', this.save.bind( this ) );
		this.$saveMessage = $( '.usof-control-message', this.$saveControl );
		this.valuesChanged = {};
		this.saveStateTimer = null;
		for ( var fieldId in this.fields ) {
			if ( ! this.fields.hasOwnProperty( fieldId ) ) {
				continue;
			}
			this.fields[ fieldId ].on( 'change', function( field, value ) {
				if ( $.isEmptyObject( this.valuesChanged ) ) {
					clearTimeout( this.saveStateTimer );
					this.$saveControl.usMod( 'status', 'notsaved' );
				}
				this.valuesChanged[ field.name ] = value;
			}.bind( this ) );
		}

		// Events
		$( window ).on( 'keydown', function( e ) {
			if ( e.ctrlKey || e.metaKey ) {
				if ( String.fromCharCode( e.which ).toLowerCase() == 's' ) {
					e.preventDefault();
					this.save();
				}
			}
		}.bind( this ) );
	};
	$.extend( USHB.prototype, $usof.mixins.Fieldset, {
		/**
		 * Save the new values
		 */
		save: function() {
			if ( $.isEmptyObject( this.valuesChanged ) ) {
				return;
			}
			clearTimeout( this.saveStateTimer );
			this.$saveMessage.html( '' );
			this.$saveControl.usMod( 'status', 'loading' );
			var data = {
				action: 'us_ajax_hb_save',
				ID: this.$container.data( 'id' ),
				post_title: this.getValue( 'post_title' ),
				post_content: JSON.stringify( this.getValue( 'post_content' ) ),
				_wpnonce: $( '[name="_wpnonce"]', this.$container ).val(),
				_wp_http_referer: $( '[name="_wp_http_referer"]', this.$container ).val()
			};

			// Inject polylang data from AJAX request
			$.each( $('form#post').serializeArray() || {}, function( _, param ) {
				$.each( [ 'post_lang_', 'post_tr_' ], function( _, param_prefix ) {
					if ( param.name.indexOf( param_prefix ) !== -1 ) {
						data[ param.name ] = param.value;
					}
				} );
			} );

			$.ajax( {
				type: 'POST',
				url: $usof.ajaxUrl,
				dataType: 'json',
				data: data,
				success: function( result ) {
					if ( result.success ) {
						this.valuesChanged = {};
						this.$saveMessage.html( result.data.message );
						this.$saveControl.usMod( 'status', 'success' );
						this.saveStateTimer = setTimeout( function() {
							this.$saveMessage.html( '' );
							this.$saveControl.usMod( 'status', 'clear' );
						}.bind( this ), 4000 );
					} else {
						this.$saveMessage.html( result.data.message );
						this.$saveControl.usMod( 'status', 'error' );
						this.saveStateTimer = setTimeout( function() {
							this.$saveMessage.html( '' );
							this.$saveControl.usMod( 'status', 'notsaved' );
						}.bind( this ), 4000 );
					}
				}.bind( this )
			} );
		}
	} );

	new USHB( '.usof-container.type_builder' );

	// Pencil icon hear the header edit
	var $headerTitle = $( 'input[name="post_title"] + input' ),
		$headerEditIcon = $( '<span class="usof-form-row-control-icon"></span>' )
			.text( $headerTitle.val() )
			.insertAfter( $headerTitle );

	$headerTitle.on( 'change keyup', () => {
		$headerEditIcon.text( $headerTitle.val() || $headerTitle.attr( 'placeholder' ) );
	} );
} );
