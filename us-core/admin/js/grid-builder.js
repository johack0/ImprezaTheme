if ( window.$usgb === undefined ) {
	window.$usgb = {};
}
$usgb.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test( navigator.userAgent );

jQuery( document ).ready( function( $, _undefined ) {

	if ( window.$usof.mixins === _undefined ) {
		window.$usof.mixins = {};
	}

	$usof.ajaxUrl = $( '.us-bld' ).data( 'ajaxurl' );

	/**
	 * $usgb.Tabs class.
	 *
	 * @param container
	 */
	$usgb.Tabs = function( container ) {
		this.$container = $( container );
		this.$list = $( '.usof-tabs-list:first', this.$container );
		this.$items = this.$list.children( '.usof-tabs-item' );
		this.$sections = $( '.usof-tabs-section', this.$container );
		this.items = this.$items.toArray().map( $ );
		this.sections = this.$sections.toArray().map( $ );
		this.active = 0;
		this.items.forEach( function( $elm, index ) {
			$elm.on( 'click', this.open.bind( this, index ) );
		}.bind( this ) );
	};
	$.extend( $usgb.Tabs.prototype, $usof.mixins.Events, {
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
	 * $usgb.EForm class.
	 *
	 * @param container
	 */
	$usgb.EForm = function( container ) {
		const self = this;

		self.$container = $( container );
		self.$tabs = $( '.usof-tabs', self.$container );
		if ( self.$tabs.length ) {
			self.tabs = new $usgb.Tabs( self.$tabs );
		}

		self.initFields( self.$container );
		self.initDataIndicator();

		// Delete all fields that are in design_options since they will be initialized by design_options,
		// otherwise there will be duplication events on different parent objects
		for ( const k in self.fields ) {
			if (
				self.fields[ k ].type === 'color'
				&& self.fields[ k ].$row.closest( '.type_design_options' ).length
			) {
				delete self.fields[ k ];
			}
		}

		// Toggles the data indicator on the tab
		self.on( 'dataIndicatorChanged', $ush.debounce( ( hasValues ) => {
			if ( self.tabs instanceof $usgb.Tabs ) {
				for ( const index in hasValues ) if ( self.tabs.$items[ index ] ) {
					$( self.tabs.$items[ index ] ).toggleClass( 'has_values', hasValues[ index ] === true );
				}
			}
		}, 1 ) );
	};
	$.extend( $usgb.EForm.prototype, $usof.mixins.Fieldset );

	/**
	 * $usgb.Elist class: A popup with elements list to choose from. Behaves as a singleton.
	 */
	$usgb.EList = function() {
		if ( $usgb.elist !== _undefined ) {
			return $usgb.elist;
		}
		this.$container = $( '.us-bld-window.for_adding' );
		if ( this.$container.length > 0 ) {
			this.$container.appendTo( $( document.body ) );
			this.init();
		}
	};
	$.extend( $usgb.EList.prototype, $usof.mixins.Events, {
		init: function() {
			this.$closer = $( '.us-bld-window-closer', this.$container );
			this.$list = $( '.us-bld-window-list', this.$container );
			this._events = {
				select: function( e ) {
					var $item = $( e.target ).closest( '.us-bld-window-item' );
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
					url: $usgb.ajaxUrl,
					data: {
						action: 'usgb_get_elist_html'
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
	$usgb.elist = new $usgb.EList;

	/**
	 * $usgb.EBuilder class: A popup with loadable elements forms.
	 */
	$usgb.EBuilder = function() {
		this.$container = $( '.us-bld-window.for_editing' );
		this.loaded = false;
		if ( this.$container.length != 0 ) {
			this.$container.appendTo( $( document.body ) );
			this.init();
		}
	};
	$.extend( $usgb.EBuilder.prototype, $usof.mixins.Events, {
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
				save: this.save.bind( this )
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
					url: $usof.ajaxUrl,
					data: {
						action: 'usgb_get_ebuilder_html'
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
				this.eforms[ name ] = new $usgb.EForm( this.$eforms[ name ] );
				this.defaults[ name ] = this.eforms[ name ].getValues();
			}

			// Filling missing values with defaults
			values = $.extend( {}, this.defaults[ name ], values );
			this.eforms[ name ].setValues( values );
			if ( this.eforms[ name ].tabs !== _undefined ) {
				this.eforms[ name ].tabs.$list.appendTo( this.$header );
				this.eforms[ name ].tabs.open(0);
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
	$usgb.ebuilder = new $usgb.EBuilder;

	/**
	 * $usgb.ExportImport class: a popup with Export/Import dialog.
	 */
	$usgb.ExportImport = function() {
		this.$body = $( document.body );
		this.$container = $( '.us-bld-window.for_export_import' );
		if ( this.$container.length != 0 ) {
			this.$container.appendTo( this.$body );
			this.init();
		}
	};
	$.extend( $usgb.ExportImport.prototype, $usof.mixins.Events, {
		init: function() {
			this.$closer = $( '.us-bld-window-closer', this.$container );
			this.$importButton = $( '.usof-button.type_save', this.$container );
			this.$row = $( '.usof-form-row', this.$container ).first();
			this.$textarea = $( 'textarea', this.$row );
			this.error = false;

			this._events = {
				import: function() {
					var data = this.$textarea.val();
					if ( data.charAt(0) == '{' ) {
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
		show: function( value, default_values ) {
			for ( var elmId in value.data ) {
				var elmType = elmId.split( ':' )[0],
					elmParams = value.data[ elmId ] || {},
					elmDefaults = default_values[ elmType ] || {};

				for ( var param in elmDefaults ) {
					if( elmParams[ param ] == elmDefaults[ param ] ) {
						delete value.data[ elmId ][ param ];
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
	$usgb.exportimport = new $usgb.ExportImport;

	/**
	 * $usgb.GTemplates class: a popup with header templates.
	 */
	$usgb.GTemplates = function() {
		this.$body = $( document.body );
		this.$container = $( '.us-bld-window.for_templates' );
		this.loaded = false;
		if ( this.$container.length != 0 ) {
			this.$container.appendTo( this.$body );
			this.init();
		}
	};
	$.extend( $usgb.GTemplates.prototype, $usof.mixins.Events, {
		init: function() {
			this.$closer = $( '.us-bld-window-closer', this.$container );
			this.$list = $( '.us-bld-window-list', this.$container );
			this._events = {
				select: function( e ) {
					var $item = $( e.target ).closest( '.us-bld-window-item' );
					if ( $usgb.instance.value.data && Object.keys( $usgb.instance.value.data ).length && ! confirm( $usgb.instance.translations[ 'template_replace_confirm' ] ) ) {
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
					url: $usof.ajaxUrl,
					data: {
						action: 'usgb_get_gtemplates_html'
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
	$usgb.gtemplates = new $usgb.GTemplates;

	/**
	 * Side settings
	 */
	function GBOptions( container ) {
		this.$container = $( container );
		this.$sections = $( '.us-bld-options-section', this.$container );
		this.$sections.not( '.active' ).children( '.us-bld-options-section-content' ).slideUp();
		$( '.us-bld-options-section-title', this.$container ).click( function( e ) {
			var $parentSection = $( e.target ).parent();
			if ( $parentSection.hasClass( 'active' ) ) {
				return;
			}
			var $previousActive = this.$sections.filter( '.active' );
			this.fireFieldEvent( $previousActive, 'beforeHide' );
			$previousActive.removeClass( 'active' ).children( '.us-bld-options-section-content' ).slideUp( function() {
				this.fireFieldEvent( $previousActive, 'afterHide' );
			}.bind( this ) );
			this.fireFieldEvent( $parentSection, 'beforeShow' );
			$parentSection.addClass( 'active' ).children( '.us-bld-options-section-content' ).slideDown( function() {
				this.fireFieldEvent( $parentSection, 'afterShow' );
			}.bind( this ) );
		}.bind( this ) );

		$( '.usof-subform-row, .usof-subform-wrapper', this.$container ).each( function( index, elm ) {
			elm.className = elm.className.replace( 'usof-subform-', 'usof-form-' );
		} );

		this.initFields( this.$container );

		var activeSection = this.$sections.filter( '.active' );
		this.fireFieldEvent( activeSection, 'beforeShow' );
		this.fireFieldEvent( activeSection, 'afterShow' );
	};
	$.extend( GBOptions.prototype, $usof.mixins.Fieldset, {
		getValue: function( id ) {
			if ( id == 'state' ) {
				return $usgb.instance.state;
			}
			if ( this.fields[ id ] === _undefined ) {
				return _undefined;
			}
			return this.fields[ id ].getValue();
		}
	} );

	/**
	 * USOF Field: Grid Layout Builder
	 */
	$usof.field[ 'grid_builder' ] = {

		init: function( options ) {
			const self = this;

			$usgb.instance = self;
			self.parentInit( options );

			// Elements
			self.$window = $( window );
			self.$body = $( document.body );
			self.$container = $( '.us-bld', self.$row );
			self.$dragshadow = $( '<div class="us-bld-editor-dragshadow"></div>' );
			self.$editor = $( '.us-bld-editor' );
			self.$rows = $( '.us-bld-editor-row', self.$container );
			self.$stateTabs = $( '.us-bld-state', self.$container );
			self.$workspace = $( '.us-bld-workspace', self.$container );

			// Import data from backend.
			var data = {};
			if ( $( '.us-bld-data', self.$container ).is( '[onclick]' ) ) {
				data = $( '.us-bld-data', self.$container )[0].onclick() || {};
			}

			// Private "Variables"
			self.state = 'default';
			self.admin_labels = data['admin_labels'] || {}
			self.predefined_dynamic_values = data['predefined_dynamic_values'] || {};
			self.default_values = data['default_values'] || {};
			self.element_icons = data['element_icons'] || {};
			self.elms_titles = data['elms_titles'] || {};
			self.translations = data['translations'] || {};
			self.value = data['value'] || {};
			self.params = data['params'] || {}; // TODO: Check relevance
			self.states = [ self.state ];

			// Bindable events.
			self._events = {
				maybeDragMove: self.maybeDragMove.bind( self ),
				dragMove: self.dragMove.bind( self ),
				dragEnd: self.dragEnd.bind( self )
			};

			self.$places = {};
			$( '.us-bld-editor-cell', self.$editor ).each( ( _, cell ) => {
				const $cell = $( cell );
				self.$places[ $cell.parent().parent().usMod( 'at' ) + '_' + $cell.usMod( 'at' ) ] = $cell;
			} );

			self.$wrappers = {};
			$( '.us-bld-editor-wrapper', self.$editor ).each( ( _, wrapper ) => {
				const $wrapper = $( wrapper );
				self.$wrappers[ $wrapper.data( 'id' ) ] = $wrapper;
			} );

			self.$elms = {};
			$( '.us-bld-editor-elm', self.$editor ).each( ( _, element ) => {
				const $element = $( element );
				self.$elms[ $element.data( 'id' ) ] = $element;
			} );

			self.$templatesBtn = $( '.usof-control.for_templates' ).on( 'click', self._showTemplatesBtnClick.bind( self ) );
			$( '.usof-control.for_import' ).on( 'click', self._showExportImportBtnClick.bind( self ) );

			// Elements modification events
			self.$container
				.on( 'click', '.us-bld-editor-add, .us-bld-editor-control.type_add, .us-bld-editor-wrapper-content:empty', self._addBtnClick.bind( self ) )
				.on( 'click', '.us-bld-editor-control.type_edit', self._editBtnClick.bind( self ) )
				.on( 'click', '.us-bld-editor-control.type_clone', self._cloneBtnClick.bind( self ) )
				.on( 'mousedown', '.us-bld-editor-elm, .us-bld-editor-wrapper', self._dragStart.bind( self ) )
				.on( 'click', '.us-bld-editor-control.type_delete', self._deleteBtnClick.bind( self ) )
				.on( 'dragstart', ( e ) => { e.preventDefault() } ); // Preventing browser native drag event

			// Options that has no responsive values
			self.sharedOptions = [ 'top_fullwidth', 'middle_fullwidth', 'bottom_fullwidth' ];

			self.sideOptions = new GBOptions( $( '.us-bld-options:first', self.$container ) );
			$.each( self.sideOptions.fields, ( fieldId, field ) => {
				field.on( 'change', self._optionChanged.bind( self ) );
			} );

			// State togglers
			self.$stateTabs.on( 'click', ( e ) => {
				self.setState( $( e.target ).usMod( 'ui-icon_devices' ) );
			} );

			// Highlight rows on side options hover
			$( '.us-bld-options-section', self.$container ).each( ( _, section ) => {
				const $section = $( section );
				const id = $section.data( 'id' );
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
			const self = this;
			// Fixing missing data
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
			self.value = $.extend( {}, value );
			self.setState( 'default', true );
		},

		getValue: function() {
			return this.value;
		},

		/**
		 * Buttons events
		 */
		_addBtnClick: function( e ) {
			const self = this;
			var $target = $( e.target ),
				placeType, place;
			if ( $target.hasClass( 'us-bld-editor-add' ) ) {
				var $cell = $target.closest( '.us-bld-editor-cell' );
				place = $cell.parent().parent().usMod( 'at' ) + '_' + $cell.usMod( 'at' );
				placeType = 'cell';
			} else {
				place = $target.closest( '.us-bld-editor-wrapper' ).data( 'id' );
				placeType = place.split( ':' )[0];
			}
			$usgb.elist.off( 'beforeShow' ).on( 'beforeShow', () => {
				$usgb.elist.$container
					.toggleClass( 'hide_search', self.value.data[ 'search:1' ] !== _undefined )
					.toggleClass( 'hide_cart', self.value.data[ 'cart:1' ] !== _undefined )
					.usMod( 'addto', placeType );
			} );
			$usgb.elist.off( 'select' ).on( 'select', ( elist, type ) => {
				var elmId = self.createElement( place, type );
				// Opening editing form for standard elements
				if ( type.substr(1) != 'wrapper' ) {
					$( '.us-bld-editor-control.type_edit', self.$elms[ elmId ] ).trigger( 'click' );
				}
			} );
			$usgb.elist.show();
		},

		_editBtnClick: function( e ) {
			const self = this;
			var $target = $( e.target ),
				$elm = $target.closest( '.us-bld-editor-elm, .us-bld-editor-wrapper' ),
				id = $elm.data( 'id' ),
				type = id.split( ':' )[0],
				values = ( self.value.data[ id ] || {} );
			$usgb.ebuilder.off( 'save' ).on( 'save', ( ebuilder, values, defaults ) => {
				self.updateElement( id, values );
			} );
			$usgb.ebuilder.show( type, values );
		},

		_cloneBtnClick: function( e ) {
			const self = this;
			var $target = $( e.target ),
				$elm = $target.closest( '.us-bld-editor-elm, .us-bld-editor-wrapper' ),
				id = $elm.data( 'id' ),
				type = id.split( ':' )[0];

			// createElement: function(place, type, index, values){
			var newId = self.createElement( 'middle_center', type, _undefined, self.value.data[ id ] || {} );

			// Copy wrapper with all elements
			if ( type.substr(1) == 'wrapper' && self.value.default.layout[ id ].length ) {
				self._cloneWrapperElms.call( self, id, newId );
			}

			self.states.forEach( ( state ) => {
				self.moveElement( newId, id, 'after', state );
			} );
		},

		/**
		 * Copy wrapper with all elements.
		 *
		 * @param {string} id The identifier
		 * @param {string} newId The new identifier
		 */
		_cloneWrapperElms: function( id, newId ) {
			const self = this;
			var elms = self.value.default.layout[ id ] || [];
			for ( const i in elms ) {
				var elmId = elms[ i ],
					elmType = elmId.split( ':' )[0],
					newElmId = self.createElement( 'middle_center', elmType, _undefined, self.value.data[ elmId ] || {} );
					self.moveElement( newElmId, newId );
				if ( elmType.substr(1) == 'wrapper' && self.value.default.layout[ elmId ].length ) {
					self._cloneWrapperElms.call( self, elmId, newElmId );
				}
			}
		},

		_deleteBtnClick: function( e ) {
			const self = this;
			var $target = $( e.target );
			if ( ! confirm( self.translations[ 'element_delete_confirm' ] ) ) {
				return;
			}
			var id = $target.parent().parent().data( 'id' );
			self.deleteElement( id );
		},

		_showTemplatesBtnClick: function( e ) {
			const self = this;
			if ( e !== _undefined ) {
				e.preventDefault();
			}
			$usgb.gtemplates.off( 'select' ).on( 'select', ( dialog, name, data ) => {
				self.setValue( data );
				self.trigger( 'change', self.value );
			} );
			$usgb.gtemplates.show();
			self.$templatesBtn.removeClass( 'start' );
		},

		_showExportImportBtnClick: function( e ) {
			const self = this;
			e.preventDefault();
			$usgb.exportimport.off( 'import' ).on( 'import', ( dialog, name, data ) => {
				self.setValue( data );
				self.trigger( 'change', self.value );
			} );
			$usgb.exportimport.show( self.getValue(), self.default_values );
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
			const self = this;
			if ( newState == self.state && ! force ) {
				return;
			}
			// Changing the active tab setting
			self.$stateTabs.removeClass( 'active' ).filter( `.ui-icon_devices_${newState}` ).addClass( 'active' );
			self.$workspace.usMod( 'for', newState );
			self.state = newState;
			// Changing side options view
			if ( self.value[ newState ].options !== _undefined ) {
				var options = $.extend( {}, self.value[ newState ].options );
				if ( newState != 'default' ) {
					for ( var i = 0; i < self.sharedOptions.length; i ++ ) {
						options[ self.sharedOptions[ i ] ] = self.value.default.options[ self.sharedOptions[ i ] ];
					}
				}
				self.setOptions( options );
			}
			self.renderLayout();
		},

		/**
		 * Create element at the end of the specified place
		 *
		 * @param {String} place Place Cell name or wrapper ID
		 * @param {String} type Element type Element type
		 * @param {Number} [index] Element index, starting from 1. If not set will be generated automatically.
		 * @param {{}} [values] Element values
		 *
		 * @returns {String} New element ID
		 */
		createElement: function( place, type, index, values ) {
			const self = this;
			if ( index === _undefined ) {
				// If index is not defined generating a spare one
				index = 1;
				while ( self.value.data[ type + ':' + index ] !== _undefined ) {
					index ++;
				}
			}
			var id = type + ':' + index;
			for ( var i = 0, state = self.states[ i ]; i < self.states.length; state = self.states[ ++ i ] ) {
				if ( self.value[ state ] === _undefined ) {
					self.value[ state ] = {};
				}
				if ( self.value[ state ].layout === _undefined ) {
					self.value[ state ].layout = {};
				}
				if ( self.value[ state ].layout[ place ] === _undefined ) {
					self.value[ state ].layout[ place ] = [];
				}
				self.value[ state ].layout[ place ].push( id );
				if ( type.substr(1) == 'wrapper' ) {
					self.value[ state ].layout[ id ] = [];
				}
			}
			self.value.data[ id ] = $.extend( {}, self.default_values[ type ] || {}, values || {} );
			self.renderLayout();
			self.trigger( 'change', self.value );
			return id;
		},

		/**
		 * Move a specified element to a specified place.
		 *
		 * @param {String} id Element ID
		 * @param {String} place Cell name or element ID
		 * @param {String} [position] Relation to place: "last_child" / "first_child" / "before" / "after"
		 * @param {String} [state] If not specified, the current active state will be used
		 */
		moveElement: function( id, place, position, state ) {
			const self = this;
			if ( self.value.data[ id ] === _undefined ) {
				return;
			}
			position = position || 'last_child';
			state = state || self.state;
			if ( self.value[ state ] === _undefined ) {
				self.value[ state ] = {};
			}
			if ( self.value[ state ].layout === _undefined ) {
				self.value[ state ].layout = {};
			}
			// Cropping out the element from the previous place ...
			var plc, elmPos;
			for ( plc in self.value[ state ].layout ) {
				if ( ! self.value[ state ].layout.hasOwnProperty( plc ) ) {
					continue;
				}
				elmPos = self.value[ state ].layout[ plc ].indexOf( id );
				if ( elmPos != - 1 ) {
					self.value[ state ].layout[ plc ].splice( elmPos, 1 );
					break;
				}
			}
			// ... and placing it to the new one
			if ( position == 'first_child' || position == 'last_child' ) {
				if ( self.value[ state ].layout[ place ] === _undefined ) {
					self.value[ state ].layout[ place ] = [];
				}
				self.value[ state ].layout[ place ][ ( position == 'first_child' ) ? 'unshift' : 'push' ]( id );
			} else if ( position == 'before' || position == 'after' ) {
				for ( plc in self.value[ state ].layout ) {
					if ( ! self.value[ state ].layout.hasOwnProperty( plc ) ) {
						continue;
					}
					elmPos = self.value[ state ].layout[ plc ].indexOf( place );
					if ( elmPos != - 1 ) {
						self.value[ state ].layout[ plc ].splice( elmPos + ( ( position == 'after' ) ? 1 : 0 ), 0, id );
						break;
					}
				}
			}
			self.renderLayout();
			self.trigger( 'change', self.value );
		},

		/**
		 * Update the specified element's values.
		 *
		 * @param {String} id Element ID
		 * @param {{}} values Element values
		 */
		updateElement: function( id, values ) {
			const self = this;
			const type = id.split( ':' )[0];
			self.value.data[ id ] = $.extend( {}, self.default_values[ type ] || {}, values );
			var $elm = self[ ( type.substr(1) == 'wrapper' ) ? '$wrappers' : '$elms' ][ id ];
			if ( $elm !== _undefined ) {
				self._updateElementPlaceholder( $elm, id, self.value.data[ id ] );
			}
			self.trigger( 'change', self.value );
		},

		/**
		 * Delete the specified element.
		 *
		 * @param {String} id Element ID
		 */
		deleteElement: function( id ) {
			const self = this;
			var type = id.split( ':' )[0];
			for ( var i = 0, state = self.states[ i ]; i < self.states.length; state = self.states[ ++ i ] ) {
				if ( self.value[ state ] === _undefined ) {
					self.value[ state ] = {};
				}
				if ( self.value[ state ].layout === _undefined ) {
					self.value[ state ].layout = {};
				}
				if ( id.substr( 1, 7 ) == 'wrapper' && self.value[ state ].layout[ id ] !== _undefined ) {
					// Deleting all wrapper's elements
					var layoutLength = self.value[ state ].layout[ id ].length;
					for ( var wrapperElmIndex = layoutLength - 1; wrapperElmIndex >= 0; wrapperElmIndex -- ) {
						var wrapperElm = self.value[ state ].layout[ id ][ wrapperElmIndex ];
						if ( self.value.data[ wrapperElm ] !== _undefined ) {

							self.deleteElement( wrapperElm ); //delete self.value.data[wrapperElm];
						}
					}
					delete self.value[ state ].layout[ id ];
				}
				for ( var plc in self.value[ state ].layout ) {
					if ( ! self.value[ state ].layout.hasOwnProperty( plc ) ) {
						continue;
					}
					var elmPos = self.value[ state ].layout[ plc ].indexOf( id );
					if ( elmPos != - 1 ) {
						self.value[ state ].layout[ plc ].splice( elmPos, 1 );
						break;
					}
				}
			}
			if ( self.value.data[ id ] !== _undefined ) {
				delete self.value.data[ id ];
			}
			self.renderLayout();
			self.trigger( 'change', self.value );
		},

		/**
		 * Load attachments withing the given jQuery DOM object.
		 *
		 * @param {$} $html
		 */
		_loadAttachments: function( $html ) {
			$( 'img[data-wpattachment]', $html ).each( ( index, element ) => {
				var $element = $( element ),
					id = $element.data( 'wpattachment' ),
					attachment = wp.media.attachment( id );
				if ( ! attachment || ! attachment.attributes.id ) {
					return '';
				}
				const renderAttachmentImage = () => {
					var src = attachment.attributes.url;
					if ( attachment.attributes.sizes !== _undefined ) {
						var size = ( attachment.attributes.sizes.medium !== _undefined ) ? 'thumbnail' : 'full';
						src = attachment.attributes.sizes[ size ].url;
					}
					$element.attr( 'src', src ).removeAttr( 'data-wpattachment' );
				};
				if ( attachment.attributes.url !== _undefined ) {
					renderAttachmentImage();
				} else {
					// Loading missing data via ajax
					attachment.fetch( { success: renderAttachmentImage } );
				}
			} );
		},

		/**
		 * Create a base part of elements DOM placeholder: the one that doesn't depend on values.
		 *
		 * @param {String} id
		 *
		 * @return {$} Created (but not placed to document) placeholder's DOM element
		 */
		_createElementPlaceholderBase: function( id ) {
			const self = this;
			const elmType = id.split( ':' )[0];

			var html = '';

			// Wrappers
			if ( elmType.substr(1) == 'wrapper' ) {
				html = `
					<div class="us-bld-editor-wrapper type_${ elmType == 'hwrapper' ? 'horizontal' : 'vertical' } empty">
						<div class="us-bld-editor-wrapper-content"></div>
						<div class="us-bld-editor-wrapper-controls">
							<a title="${self.translations['add_element']}" class="us-bld-editor-control type_add" href="javascript:void(0)"></a>
							<a title="${self.translations['edit_wrapper']}" class="us-bld-editor-control type_edit" href="javascript:void(0)"></a>
							<a title="${self.translations['edit_clone']}" class="us-bld-editor-control type_clone" href="javascript:void(0)"></a>
							<a title="${self.translations['delete_wrapper']}" class="us-bld-editor-control type_delete" href="javascript:void(0)"></a>
						</div>
					</div>
				`;
				self.$wrappers[ id ] = $( html ).data( 'id', id );
				return self.$wrappers[ id ];

				// Standard elements
			} else {

				html = `
					<div class="us-bld-editor-elm type_${elmType}">
						<div class="us-bld-editor-elm-content">
							${ ( elmType == 'btn' ) ? '<button type="button">' : '' }
								<span class="us-bld-editor-elm-icon"></span>
								<span class="us-bld-editor-elm-value"></span>
							${ ( elmType == 'btn' ) ? '</button>' : '' }
						</div>
						<div class="us-bld-editor-elm-controls">
							<a href="javascript:void(0)" class="us-bld-editor-control type_edit" title="${self.translations['edit_element']}"></a>
							<a href="javascript:void(0)" class="us-bld-editor-control type_clone" title="${self.translations['clone_element']}"></a>
							<a href="javascript:void(0)" class="us-bld-editor-control type_delete" title="${self.translations['delete_element']}"></a>
						</div>
					</div>
				`;
				self.$elms[ id ] = $( html ).data( 'id', id );
				return self.$elms[ id ];
			}
		},

		/**
		 * Update element DOM placeholder with the current values.
		 *
		 * @param {$} $elm
		 * @param {String} id
		 * @param {{}} values
		 */
		_updateElementPlaceholder: function( $elm, id, values ) {
			const self = this;

			var isAbs = false;
			$.each( self.states, ( _, state ) => {
				if ( values['css'] && values['css'][ state ] !== _undefined && values['css'][ state ]['position'] === 'absolute' ) {
					isAbs = true;
				}
			} );
			$elm.toggleClass( 'is_absolute_pos', isAbs );

			if ( id.substr( 1, 7 ) == 'wrapper' ) {
				return;
			}

			const elementType = id.split( ':' )[0];

			values = $.extend( {}, self.default_values[ elementType ] || {}, values || {} );

			var iconHtml = '',
				labelHtml = '',
				afterLabelHtml = '',
				adminLabels = self.admin_labels[ elementType ] || {};

			const popupAsButton = ( elementType === 'popup' && values.show_on === 'btn' );
			const popupAsIcon = ( elementType === 'popup' && values.show_on === 'icon' );
			const popupTriggerNotButton = ( elementType === 'popup' && values.show_on !== 'btn' );

			// Output icon if set
			if ( values.icon ) {
				iconHtml = $usof.instance.prepareIconTag( values.icon );
			} else if ( popupAsButton ) {
				iconHtml = $usof.instance.prepareIconTag( $ush.toString( values.btn_icon ) );

				// Set default icon for all elements except button
			} else if ( elementType != 'btn' ) {
				iconHtml = `<i class="${self.element_icons[ elementType ] || ''}"></i>`;
			}

			const paramName = adminLabels['param_name'];
			const paramOptions = adminLabels['param_options'] || {};

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

			// Post Custom Field element
			if ( elementType == 'post_custom_field' && labelHtml == 'custom' ) {
				if ( ! $ush.isUndefined( values[ 'custom_key' ] ) ) {
					labelHtml = values[ 'custom_key' ];
				}

			// Product Data element
			} else if ( elementType == 'product_field' && labelHtml == 'sale_badge' ) {
				if ( ! $ush.isUndefined( values['sale_text'] ) ) {
					afterLabelHtml = `: "${values['sale_text']}"`;
				}

				// User Data element
			} else if ( elementType == 'user_data' && labelHtml == 'custom' ) {
				if ( ! $ush.isUndefined( values[ 'custom_field' ] ) ) {
					labelHtml = values[ 'custom_field' ];
				}

				// Image element
			} else if ( elementType == 'image' && $ush.parseInt( values[ paramName ] ) ) {
				iconHtml = `<img src="" data-wpattachment="${values[ paramName ]}" alt=""/>`;
				labelHtml = '';
			}

			labelHtml = paramOptions[ labelHtml ] || self.predefined_dynamic_values[ labelHtml ] || labelHtml;

			if ( self.isDynamicVariable( labelHtml ) ) {
				const varName = labelHtml.replace( /^{{([\dA-z\/\|\-_]+)}}$/, '$1' );
				labelHtml = self.predefined_dynamic_values[ varName ] || labelHtml;
			}

			if ( labelHtml ) {
				labelHtml += afterLabelHtml;

			} else if ( elementType == 'btn' || elementType == 'text' || popupAsIcon || popupAsButton ) {
				// The button may contain only an icon and no text.

			} else if ( ! iconHtml.includes( '<img' ) ) {
				labelHtml = $ush.toString( self.elms_titles[ elementType ] );
			}

			const $content = $( '.us-bld-editor-elm-content', $elm );
			const $icon = $( '.us-bld-editor-elm-icon', $content ).html( iconHtml );

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

			// Icon position
			if ( values.iconpos ) {
				if ( elementType == 'btn' || popupAsButton ) {
					$( 'button', $content )[ values['iconpos'] == 'right' ? 'append' : 'prepend' ]( $icon );
				} else {
					$content[ values['iconpos'] == 'right' ? 'append' : 'prepend' ]( $icon );
				}
			}

			self._loadAttachments( $content );
		},

		/**
		 * Create DOM placeholder element for the specified header builder element / wrapper.
		 *
		 * @param {String} id Element ID
		 * @param {{}} [values]
		 *
		 * @return {$} Created (but not yet placed to document) jQuery object with the element's DOMElement
		 */
		_createElementPlaceholder: function( id, values ) {
			const self = this;
			var $element = self._createElementPlaceholderBase( id );
			self._updateElementPlaceholder( $element, id, values );
			return $element;
		},

		/**
		 * Delete DOM placeholder for the specified header element / wrapper.
		 *
		 * @param {String} id
		 */
		_removeElementPlaceholder: function( id ) {
			const self = this;
			var container = ( id.substr( 1, 7 ) == 'wrapper' ) ? '$wrappers' : '$elms';
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
			const self = this;
			// Making sure the provided data is consistent
			if ( self.value.data === _undefined || self.value.data instanceof Array ) {
				self.value.data = {};
			}
			if ( self.value[ self.state ].layout === _undefined ) {
				self.value[ self.state ].layout = {};
			}
			var elmsInNextLayout = [],
				plc, i, elmId;
			for ( plc in self.value[ self.state ].layout ) {
				if ( ! self.value[ self.state ].layout.hasOwnProperty( plc ) ) {
					continue;
				}
				for ( i = 0; i < self.value[ self.state ].layout[ plc ].length; i ++ ) {
					var id = self.value[ self.state ].layout[ plc ][ i ],
						type = id.split( ':' )[0];
					if ( self.value.data[ id ] === _undefined ) {
						self.value.data[ id ] = $.extend( {}, self.default_values[ type ] || {} );
					}
					elmsInNextLayout.push( self.value[ self.state ].layout[ plc ][ i ] );
				}
			}
			// Retrieving the currently shown layout structure
			var prevLayout = {},
				parsePlace = function( place, $place ) {
					if ( $place.hasClass( 'us-bld-editor-wrapper' ) ) {
						$place = $place.children( '.us-bld-editor-wrapper-content' );
					}
					prevLayout[ place ] = [];
					$place.children().each( ( index, elm ) => {
						var $elm = $( elm ),
							id = $elm.data( 'id' );
						if ( ! id ) {
							return;
						}
						prevLayout[ place ].push( id );
					} );
				};
			$.each( self.$places, parsePlace );
			$.each( self.$wrappers, parsePlace );
			// Iteratively looping through the needed structure
			for ( plc in self.value[ self.state ].layout ) {
				if ( ! self.value[ self.state ].layout.hasOwnProperty( plc ) ) {
					continue;
				}
				if ( plc.indexOf( ':' ) != - 1 && prevLayout[ plc ] === _undefined ) {
					// Creating the missing wrapper
					if ( self.$wrappers[ plc ] === _undefined ) {
						self._createElementPlaceholder( plc, self.value.data[ plc ] );
					}
					prevLayout[ plc ] = [];
				}
				var $place = ( plc.indexOf( ':' ) == - 1 ) ? self.$places[ plc ] : self.$wrappers[ plc ].children( '.us-bld-editor-wrapper-content' );
				for ( i = 0; i < self.value[ self.state ].layout[ plc ].length; i ++ ) {
					elmId = self.value[ self.state ].layout[ plc ][ i ];
					var $elm = self[ ( elmId.substr( 1, 7 ) == 'wrapper' ) ? '$wrappers' : '$elms' ][ elmId ];
					if ( $elm === _undefined ) {
						$elm = self._createElementPlaceholder( elmId, self.value.data[ elmId ] );
					}
					if ( prevLayout[ plc ] != _undefined && prevLayout[ plc ][ i ] != elmId ) {
						if ( i == 0 ) {
							$elm.prependTo( $place );
						} else {
							var prevElmId = self.value[ self.state ].layout[ plc ][ i - 1 ],
								$prevElm = self[ ( prevElmId.substr( 1, 7 ) == 'wrapper' ) ? '$wrappers' : '$elms' ][ prevElmId ];
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
					if ( self.value.data[ elmId ] === _undefined ) {
						self._removeElementPlaceholder( elmId );
					}
				}
			}
			// Updating elements' placeholders contents
			for ( elmId in self.$elms ) {
				if ( ! self.$elms.hasOwnProperty( elmId ) ) {
					continue;
				}
				self._updateElementPlaceholder( self.$elms[ elmId ], elmId, self.value.data[ elmId ] );
			}
			// Updating wrappers' placeholders contents
			for ( elmId in self.$wrappers ) {
				if ( ! self.$wrappers.hasOwnProperty( elmId ) ) {
					continue;
				}
				self._updateElementPlaceholder( self.$wrappers[ elmId ], elmId, self.value.data[ elmId ] );
			}
			// Fixing wrappers
			$( '.us-bld-editor-wrapper', self.$container )
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
			const self = this;
			if ( self.ignoreOptionsChanges ) {
				return;
			}
			var fieldId = field.name,
				value = field.getValue(),
				state = ( $.inArray( fieldId, self.sharedOptions ) != - 1 ) ? 'default' : self.state;
			if ( self.value[ state ] === _undefined ) {
				self.value[ state ] = {};
			}
			if ( self.value[ state ].options === _undefined ) {
				self.value[ state ].options = {};
			}
			self.value[ state ].options[ fieldId ] = value;
			self.renderOptions();
			// If the Set Aspect Ratio switch is changed,
			// we need to adjust positioning in design options of the layout elements
			if ( fieldId === 'fixed' ) {
				$.each( self.value[ state ].layout[ 'middle_center' ], ( _, elmId ) => {
					// Design options is stored in a property named "css"
					// Check if it is empty and set as an object in that case
					if ( ! self.value.data[ elmId ].css ) {
						self.value.data[ elmId ].css = {};
					}
					var designOptions = self.value.data[ elmId ].css,
						// Checking if element is already absolutely positioned
						isAbs = ( designOptions.default !== _undefined && designOptions.default.position === 'absolute' ),
						// Translating string values to int to properly handle the '0' value
						shouldBeAbs = + value;
					if ( designOptions.default === _undefined ) {
						designOptions.default = {};
					}
					if ( isAbs && ! shouldBeAbs ) {
						designOptions.default.position = 'static';
					}
					else if ( ! isAbs && shouldBeAbs ) {
						designOptions.default.position = 'absolute';
					}
				} );
				self.renderLayout();
			}
			self.trigger( 'change', self.value );
		},

		/**
		 * Change side options.
		 *
		 * @param options
		 */
		setOptions: function( options ) {
			const self = this;
			self.ignoreOptionsChanges = true;
			self.sideOptions.setValues( options );
			self.ignoreOptionsChanges = false;
			self.renderOptions();
		},

		/**
		 * Render current options.
		 */
		renderOptions: function() {
			const self = this;
			$.each( [ 'top', 'bottom' ], function( index, vpos ) {
				var $row = self.$rows.filter( `.at_${vpos}` ),
					prevShown = ! $row.hasClass( 'disabled' ),
					nextShown = !! parseInt( self.value[ self.state ].options[ `${vpos}_show` ] );
				if ( prevShown != nextShown ) {
					$row.toggleClass( 'disabled', ! nextShown );
				}
			} );
		}
	};

	// Drag & drop functions
	$.extend( $usof.field[ 'grid_builder' ], {
		_dragStart: function( e ) {
			const self = this;
			e.stopPropagation();
			self.$draggedElm = $( e.target ).closest( '.us-bld-editor-elm, .us-bld-editor-wrapper' );
			self.elmType = self.$draggedElm.data( 'id' ).split( ':' )[0];
			self.detached = false;
			self._updateBlindSpot( e );
			self.elmPointerOffset = [ parseInt( e.pageX ), parseInt( e.pageY ) ];
			self.$body.on( 'mousemove', self._events.maybeDragMove );
			self.$window.on( 'mouseup', self._events.dragEnd );
		},

		_updateBlindSpot: function( e ) {
			this.blindSpot = [ e.pageX, e.pageY ];
		},

		_isInBlindSpot: function( e ) {
			return Math.abs( e.pageX - this.blindSpot[0] ) <= 20 && Math.abs( e.pageY - this.blindSpot[1] ) <= 20;
		},

		maybeDragMove: function( e ) {
			const self = this;
			e.stopPropagation();
			if ( self._isInBlindSpot( e ) ) {
				return;
			}
			self.$body.off( 'mousemove', self._events.maybeDragMove );
			self._detach();
			self.$body.on( 'mousemove', self._events.dragMove );
		},

		dragMove: function( e ) {
			const self = this;
			e.stopPropagation();
			self.$draggedElm.css( {
				left: e.pageX - self.elmPointerOffset[0],
				top: e.pageY - self.elmPointerOffset[1]
			} );
			if ( self._isInBlindSpot( e ) ) {
				return;
			}
			var elm = e.target;
			// Checking two levels up
			for ( var level = 0; level <= 2; level ++, elm = elm.parentNode ) {
				if ( self._isShadow( elm ) ) {
					return;
				}

				var parentType;
				if ( self._isSortable( elm ) ) {
					parentType = self._isWrapperContent( elm.parentNode ) ? ( $( elm ).parent().parent().usMod( 'type' )[0] + 'wrapper' ) : 'cell';

					// Dropping element before or after sortables based on their relative position in DOM
					var nextElm = elm.previousSibling,
						shadowAtLeft = false;
					while ( nextElm ) {
						if ( nextElm == self.$dragshadow[0] ) {
							shadowAtLeft = true;
							break;
						}
						nextElm = nextElm.previousSibling;
					}
					self.$dragshadow[ shadowAtLeft ? 'insertAfter' : 'insertBefore' ]( elm );
					self._dragDrop( e );
					break;
				} else if ( self._isWrapperContent( elm ) ) {
					if ( $.contains( elm, self.$dragshadow[0] ) ) {
						break;
					}
					parentType = $( elm ).parent().usMod( 'type' )[0] + 'wrapper';

					// Cannot drop a wrapper to the wrapper of the same type
					self.$dragshadow.appendTo( elm );
					self._dragDrop( e );
					break;
				} else if ( self._isControls( elm ) ) {

					// Always dropping element before controls
					self.$dragshadow.insertBefore( elm );
					self._dragDrop( e );
					break;
				} else if ( self._hasClass( elm, 'us-bld-editor-cell' ) ) {

					// If not already in this cell, moving to it
					var $shadowCell = self.$dragshadow.closest( '.us-bld-editor-cell' );
					if ( $shadowCell.length == 0 || $shadowCell[0] != elm ) {
						self.$dragshadow.insertBefore( $( '.us-bld-editor-add', elm ) );
						self._dragDrop( e );
					}
					break;
				}
			}
		},

		_detach: function( e ) {
			const self = this;
			var offset = self.$draggedElm.offset();
			self.elmPointerOffset[0] -= offset.left;
			self.elmPointerOffset[1] -= offset.top;
			self.$dragshadow.css( {
				width: self.$draggedElm.outerWidth(),
				height: self.$draggedElm.outerHeight()
			} ).insertBefore( self.$draggedElm );
			self.$draggedElm.css( {
				position: 'absolute',
				'pointer-events': 'none',
				zIndex: 10000,
				width: self.$draggedElm.width(),
				height: self.$draggedElm.height()
			} ).css( offset ).appendTo( self.$body );
			self.$editor.addClass( 'dragstarted' );
			self.detached = true;
		},

		_dragDrop: function( e ) {
			const self = this;
			$( '.us-bld-editor-wrapper', self.$container )
				.removeClass( 'empty' )
				.find( '.us-bld-editor-wrapper-content:empty' )
				.parent()
				.addClass( 'empty' );
			self._updateBlindSpot( e );
		},

		dragEnd: function( e ) {
			const self = this;
			self.$body.off( 'mousemove', self._events.maybeDragMove ).off( 'mousemove', self._events.dragMove );
			self.$window.off( 'mouseup', self._events.dragEnd );
			if ( self.detached ) {
				self.$draggedElm.removeAttr( 'style' ).insertBefore( self.$dragshadow );
				self.$dragshadow.detach();
				self.$editor.removeClass( 'dragstarted' );
				// Getting the new element position and performing the actual drag
				var elmId = self.$draggedElm.data( 'id' ),
					$prev = self.$draggedElm.prev();
				if ( $prev.length == 0 ) {
					var $parent = self.$draggedElm.parent().closest( '.us-bld-editor-cell, .us-bld-editor-wrapper' ),
						place = '';
					if ( $parent.hasClass( 'us-bld-editor-cell' ) ) {
						place = $parent.parent().parent().usMod( 'at' ) + '_' + $parent.usMod( 'at' );
					} else if ( $parent.hasClass( 'us-bld-editor-wrapper' ) ) {
						place = $parent.data( 'id' );
					}
					self.moveElement( elmId, place, 'first_child' )
				} else {
					self.moveElement( elmId, $prev.data( 'id' ), 'after' );
				}
			}
		},
	} );
} );

jQuery( function( $ ) {
	function USGB( container ) {
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

		$( window ).on( 'keydown', function( e ) {
			if ( e.ctrlKey || e.metaKey ) {
				if ( String.fromCharCode( e.which ).toLowerCase() == 's' ) {
					e.preventDefault();
					this.save();
				}
			}
		}.bind( this ) );
	};
	$.extend( USGB.prototype, $usof.mixins.Fieldset, {
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
				action: 'usgb_save',
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

	new USGB( '.usof-container.type_builder' );

	// Pencil icon hear the header edit
	var $headerTitle = $( 'input[name="post_title"] + input' ),
		$headerEditIcon = $( '<span class="usof-form-row-control-icon"></span>' ).text( $headerTitle.val() ).insertAfter( $headerTitle );
	$headerTitle.on( 'change keyup', () => {
		$headerEditIcon.text( $headerTitle.val() || $headerTitle.attr( 'placeholder' ) );
	} );

	// Preventing default form post
	$( 'form#post' ).on( 'submit', ( e ) => {
		e.stopPropagation();
		e.preventDefault();
		$( '.usof-button.type_save' ).click();
	} );
} );
