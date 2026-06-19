const eventHub = new Vue(),
	buildQuery = function ( params ) {
		return Object.keys( params ).map(function ( key ) {
			return key + '=' + params[key];
		} ).join( '&' );
	};

Vue.directive( 'click-outside', {
	bind: function ( el, binding, vnode ) {
		el.clickOutsideEvent = function ( event ) {

			if ( ! ( el == event.target || el.contains( event.target ) ) ) {
				vnode.context[ binding.expression ]( event );
			}
		};
		document.body.addEventListener( 'click', el.clickOutsideEvent )
	},
	unbind: function ( el ) {
		document.body.removeEventListener( 'click', el.clickOutsideEvent )
	}
} );

Vue.component( 'jet-search-ajax-search-settings', {

	template: '#jet-dashboard-jet-search-ajax-search-settings',

	data: function() {
		return {
			settings: {},
			searchSourceList: [],
			taxonomiesList: [],
			currentquerySettingsType: '',
			querySettings: {
				show_search_category_list: 'false',
				search_taxonomy: 'category',
				current_query: 'false',
				search_query_param: 'jet_search',
				search_results_url: '',
				search_results_target_widget_id: '',
				search_source: '',
				include_terms_ids: [],
				exclude_terms_ids: [],
				exclude_posts_ids: [],
				custom_fields_source: '',
				sentence: 'false',
				search_in_taxonomy: 'false',
				search_in_taxonomy_source: '',
				results_order_by: 'relevance',
				results_order: 'asc',
				catalog_visibility: 'false',
			},
			requestTypeList: [],
			requestSettings: {
				request_type: 'rest_api'
			},
			isDataLoaded: false,
			isValidated: true,
			searchQueryParamNameError: false,
			placeholders: {
				searchQueryParam: '',
				parent: ''
			},
		};
	},
	methods: {
		getTerms: function( query, ids ) {
			return this.getQueryControlOptions( query, ids, 'terms' )
		},
		getPosts: function( query, ids ) {
			return this.getQueryControlOptions( query, ids, 'posts' )
		},
		searchQueryParamValidation: function ( value, key ) {
			const notAllowedQueryParams = [ '', 's', '_s', 'search', 'jsearch', 'jet_ajax_search_settings', 'jet_search_suggestions_settings' ];

			if ( notAllowedQueryParams.includes( value ) ) {
				if ( '' != value ) {
					this.placeholders.searchQueryParam = `Value "${value}" is not allowed`;
				} else {
					this.placeholders.searchQueryParam = `Empty Value is not allowed`;
				}

				this.searchQueryParamNameError = true;
				this.isValidated               = false;
			} else {
				this.searchQueryParamNameError = false;
				this.isValidated               = true;
			}
		},
		getQueryControlOptions: function( query, ids, type ) {
			return new Promise( ( resolve, reject ) => {
				let postType  = this.querySettings['search_source'] ? this.querySettings['search_source'] : 'any',
					queryData = {
						q: query,
						query_type: type,
						post_type: postType,
						ids:ids,
						is_global_settings: true
					};

				queryData = buildQuery( queryData, true );

				let xhr    = new XMLHttpRequest(),
					url    = this.settings['ajaxUrl'],
					action = 'jet_search_get_query_control_options';

				xhr.open( 'POST', url, true );
				xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );

				xhr.onload = function () {
					if ( xhr.status >= 200 && xhr.status < 300 ) {
						let responseText = JSON.parse( xhr.responseText ),
							response     = responseText['data']['results'],
							currentTerms = [];

						response.forEach( el => {
							currentTerms.push( {
								value: el.id,
								label: el.text
							} );
						} );

						resolve( currentTerms );
					} else {
						eventHub.$CXNotice.add( {
							message: xhr.statusText,
							type: 'error',
							duration: 7000,
						} );

						reject( xhr.statusText );
					}
				};

				xhr.onerror = function () {
					console.error('Network error');
					reject( 'Network error' );
				};

				var data = 'action=' + encodeURIComponent(action) + '&' + queryData;

				xhr.send( data );
			} );
		},
		saveQuerySettings: function() {

			queryData = buildQuery( {
				query_settings: JSON.stringify( this.querySettings ),
				request_settings: JSON.stringify( this.requestSettings ),
			} );

			var xhr    = new XMLHttpRequest();
			var url    = this.settings['ajaxUrl'];
			var action = 'jet_search_save_ajax_search_settings';

			xhr.open( 'POST', url, true );
			xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );

			xhr.onload = function () {
				if ( xhr.status >= 200 && xhr.status < 300 ) {
					let responseText = JSON.parse( xhr.responseText ),
						response     = responseText;

						eventHub.$CXNotice.add( {
							message: 'Settings successfully saved.',
							type: 'success',
							duration: 7000,
						} );
				} else {
					eventHub.$CXNotice.add( {
						message: xhr.statusText,
						type: 'error',
						duration: 7000,
					} );
				}
			};

			var data = 'action=' + encodeURIComponent(action) + '&' + queryData;

			xhr.send( data );
		},
		loadQuerySettings: function() {
			return new Promise( ( resolve, reject ) => {
				let xhr    = new XMLHttpRequest(),
					url    = this.settings['ajaxUrl'],
					action = 'jet_search_load_ajax_search_settings',
					_this  = this;

				xhr.open('POST', url, true);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

				xhr.onload = function() {
					if ( xhr.status >= 200 && xhr.status < 300 ) {
						let response = JSON.parse( xhr.responseText );

						if ( true === response['success']) {
							_this.querySettings   = { ..._this.querySettings, ...response['data']['query_settings'] };
							_this.requestSettings = { ..._this.requestSettings, ...response['data']['request_settings'] };

							resolve();
						}
					} else {
						eventHub.$CXNotice.add( {
							message: xhr.statusText,
							type: 'error',
							duration: 7000,
						} );

						reject( xhr.statusText );
					}
				};

				let data = 'action=' + encodeURIComponent( action );

				xhr.send(data);
			} );
		},
	},
	mounted: function() {
		this.settings         = window.JetSearchSettingsConfig;
		this.searchSourceList = this.settings['settingsData']['postTypes'];
		this.taxonomiesList   = this.settings['settingsData']['taxonomies'];
		this.taxonomiesListExtended = this.settings['settingsData']['taxonomiesListExtended'];
		this.requestTypeList  = this.settings['settingsData']['request_type_list'];

		this.loadQuerySettings().then( () => {
			this.isDataLoaded = true;
		} ).catch( error => {
			eventHub.$CXNotice.add( {
				message: 'An error occurred while loading data',
				type: 'error',
				duration: 7000,
			} );
		} );
	}
} );