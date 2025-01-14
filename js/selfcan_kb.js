jQuery(function($) {

	// QWERTY Text Input
	// The bottom of this file is where the autocomplete extension is added
	// ********************
	$('#text').keyboard({ layout: 'qwerty' });

	//$('.version').html( '(v' + $('#text').getkeyboard().version + ')' );




	// Num Pad Input
	// ********************
	$('#uscli').keyboard({
		layout: 'custom',
		customLayout: {
			'normal' : [
				'T {b}',
				'1 2 3 4',
				'5 6 7 8',
				'9 0 {a} {c}'
			]
		},
		restrictInput : true, // Prevent keys not in the displayed keyboard from being typed in
		restrictInclude : 'T t',
		//restrictInput : true, // Prevent keys not in the displayed keyboard from being typed in
		preventPaste : true  // prevent ctrl-v and right click
		//autoAccept : true
	});
	$('#pin').keyboard({
		layout: 'custom',
		customLayout: {
			'normal' : [
				'{b}',
				'1 2 3 4',
				'5 6 7 8',
				'9 0 {a} {c}'
			]
		},
		restrictInput : true, // Prevent keys not in the displayed keyboard from being typed in
		//restrictInput : true, // Prevent keys not in the displayed keyboard from being typed in
		preventPaste : true  // prevent ctrl-v and right click
		//autoAccept : true
	});

	/*$('#pin').keyboard({
		layout: 'num',
		restrictInput : true, // Prevent keys not in the displayed keyboard from being typed in
		preventPaste : true,  // prevent ctrl-v and right click
		autoAccept : true
	});*/

	


});
