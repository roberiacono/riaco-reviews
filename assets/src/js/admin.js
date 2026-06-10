( function () {
    var mediaFrame;
    var input     = document.getElementById( 'riaco_author_avatar' );
    var preview   = document.getElementById( 'riaco_avatar_preview' );
    var uploadBtn = document.getElementById( 'riaco_avatar_upload_btn' );
    var removeBtn = document.getElementById( 'riaco_avatar_remove_btn' );

    if ( input && uploadBtn ) {
        uploadBtn.addEventListener( 'click', function ( e ) {
            e.preventDefault();
            if ( mediaFrame ) { mediaFrame.open(); return; }
            mediaFrame = wp.media( {
                title   : 'Select Avatar',
                button  : { text: 'Use this image' },
                multiple: false,
                library : { type: 'image' },
            } );
            mediaFrame.on( 'select', function () {
                var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
                input.value = attachment.url;
                if ( preview ) { preview.src = attachment.url; preview.style.display = ''; }
                if ( removeBtn ) { removeBtn.style.display = ''; }
            } );
            mediaFrame.open();
        } );

        if ( removeBtn ) {
            removeBtn.addEventListener( 'click', function ( e ) {
                e.preventDefault();
                input.value = '';
                if ( preview ) { preview.src = ''; preview.style.display = 'none'; }
                removeBtn.style.display = 'none';
            } );
        }
    }
} )();

( function () {
    var mediaFrame;
    var input     = document.getElementById( 'riaco_source_image' );
    var preview   = document.getElementById( 'riaco_source_image_preview' );
    var uploadBtn = document.getElementById( 'riaco_source_image_upload_btn' );
    var removeBtn = document.getElementById( 'riaco_source_image_remove_btn' );

    if ( ! input || ! uploadBtn ) return;

    uploadBtn.addEventListener( 'click', function ( e ) {
        e.preventDefault();
        if ( mediaFrame ) { mediaFrame.open(); return; }
        mediaFrame = wp.media( {
            title   : 'Select Logo',
            button  : { text: 'Use this image' },
            multiple: false,
            library : { type: 'image' },
        } );
        mediaFrame.on( 'select', function () {
            var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
            input.value = attachment.url;
            if ( preview ) { preview.src = attachment.url; preview.style.display = ''; }
            if ( removeBtn ) { removeBtn.style.display = ''; }
        } );
        mediaFrame.open();
    } );

    if ( removeBtn ) {
        removeBtn.addEventListener( 'click', function ( e ) {
            e.preventDefault();
            input.value = '';
            if ( preview ) { preview.src = ''; preview.style.display = 'none'; }
            removeBtn.style.display = 'none';
        } );
    }
} )();
