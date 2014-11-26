(function(){

	var customizeToolbar = function() {

		/* Remove Image Gallery */
		// $( '#wpTextbox1' ).wikiEditor( 'removeFromToolbar', {
		$( '.wikiEditor-ui-text textarea' ).wikiEditor( 'removeFromToolbar', {
			'section': 'advanced',
			'group': 'insert',
			'tool': 'gallery'
		});

		// replaces existing "add image" button with "image list" button
		// $('#wpTextbox1').wikiEditor('addToToolbar', {
		$('.wikiEditor-ui-text textarea').wikiEditor('addToToolbar', {
			section: 'advanced',
			group: 'insert',
			tools: {
				'imagelist': {
					'label': 'Image list',
					'type': 'button',
					'icon': 'insert-gallery.png',
					'offset': [2, -1510],
					'action': {
						'type': 'encapsulate',
						'options': {
							'pre': "{{Image list | \n",
							'peri': "File:Example.jpg## Caption for local wiki image\njsc2000e31343##    Caption for IO image",
							'post': "\n}}",
							'ownline': true
						}
					}
				}
			}
		});
		
		/*$( '#wpTextbox1' ).wikiEditor( 'addToToolbar', {
			'sections': {
				'Custom': {
					'type': 'toolbar', 
					'label': 'Custom'
				}
			}
		});*/
		
	};
	 
	/* Check if view is in edit mode and that the required modules are available. Then, customize the toolbar . . . */
	if ( $.inArray( mw.config.get( 'wgAction' ), ['edit', 'submit', 'formedit'] ) !== -1 ) {
		mw.loader.using( 'user.options', function () {
			if ( mw.user.options.get('usebetatoolbar') ) {
				mw.loader.using( 'ext.wikiEditor.toolbar', function () {
					$(document).ready(function(){
						customizeToolbar();
					});
				} );
			}
		} );
	}

})();