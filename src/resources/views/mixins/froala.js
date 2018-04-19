export const froalaMixin = {
    methods: {

        froalaConstruct(){
            this.froalaAddImage();
            this.froalaAddVideo();
            this.froalaEmbedPlugin();
            this.froalaRemoveEmbed();
            this.froalaPrepare();
        },

        // Add image commands for the froala editor (Custom buttons of editors button panel)
        froalaAddImage(){
            var global = this;

            // make add image command for each editor on the app
            $.FroalaEditor.DefineIconTemplate('file-image-o', '<i class="fa fa-[NAME]"></i>'); // define the media icon
            $.FroalaEditor.DefineIcon('addImage', { NAME: 'file-image-o'}); // add media icon
            $.FroalaEditor.RegisterCommand('addImage', { // create add media button
                title: 'Add image',
                focus: true,
                undo: true,
                icon: 'addImage',
                refreshAfterCallback: false,
                // open media when this button is clicked
                callback: function (e) {

                    $('button[data-cmd="addImage"]').click(function () { // get button click event to get the ID of the editor
                        let isDisabled = $(this).attr('aria-disabled'); // get if btn is disabled
                        if(isDisabled === undefined || isDisabled === 'false'){ // if btn is disabled should not be clickable
                            let id = $(this).parents('.froala-container').children('.froala').attr('id');
                            global.$store.commit('setOpenMediaOptions', { format : 'image', inputName: id, langSlug: '' });
                            global.$store.commit('setIsMediaOpen', true);
                        }

                        //this.selection.save();
                    });
                },
            });

            // Define a button.
            $.FroalaEditor.RegisterQuickInsertButton('addImage', {
                icon: 'addImage',
                title: 'Add image',
                undo: true,
                // Callback for the button.
                callback: function () {
                    $('a[data-cmd="addImage"]').click(function () { // get button click event to get the ID of the editor
                        let isDisabled = $(this).attr('aria-disabled'); // get if btn is disabled
                        if(isDisabled === undefined || isDisabled === 'false'){ // if btn is disabled should not be clickable
                            let id = $(this).parents('.froala-container').children('.froala').attr('id');
                            global.$store.commit('setOpenMediaOptions', { format : 'image', inputName: id, langSlug: '' });
                            global.$store.commit('setIsMediaOpen', true);
                        }
                    });
                }
            });
        },

        // Add video commands for the froala editor (Custom buttons of editors button panel)
        froalaAddVideo(){
            var global = this;

            // make add image command for each editor on the app
            $.FroalaEditor.DefineIconTemplate('file-video-o', '<i class="fa fa-[NAME]"></i>'); // define the media icon
            $.FroalaEditor.DefineIcon('addVideo', { NAME: 'file-video-o'}); // add media icon
            $.FroalaEditor.RegisterCommand('addVideo', { // create add media button
                title: 'Add video',
                focus: false,
                undo: true,
                icon: 'addVideo',
                refreshAfterCallback: false,
                // open media when this button is clicked
                callback: function (e) {
                    var editor = this;
                    $('button[data-cmd="addVideo"]').click(function () { // get button click event to get the ID of the editor
                        let isDisabled = $(this).attr('aria-disabled'); // get if btn is disabled
                        if(isDisabled === undefined || isDisabled === 'false'){ // if btn is disabled should not be clickable
                            let id = $(this).parents('.froala-container').children('.froala').attr('id');
                            global.$store.commit('setOpenMediaOptions', { format : 'video', inputName: id, langSlug: '' });
                            global.$store.commit('setIsMediaOpen', true);
                        }
                    });
                }
            });

            // Define a button.
            $.FroalaEditor.RegisterQuickInsertButton('addVideo', {
                icon: 'addVideo',
                title: 'Add video',
                // Save changes to undo stack.
                undo: true,
                // Callback for the button.
                callback: function () {
                    $('button[data-cmd="addVideo"]').click(function () { // get button click event to get the ID of the editor
                        let isDisabled = $(this).attr('aria-disabled'); // get if btn is disabled
                        if(isDisabled === undefined || isDisabled === 'false'){ // if btn is disabled should not be clickable
                            let id = $(this).parents('.froala-container').children('.froala').attr('id');
                            global.$store.commit('setOpenMediaOptions', { format : 'video', inputName: id, langSlug: '' });
                            global.$store.commit('setIsMediaOpen', true);
                        }
                    });
                }
            });
        },

        // insert embed plugin
        froalaEmbedPlugin(){
            // Define popup template.
            $.extend($.FroalaEditor.POPUP_TEMPLATES, {
                "embedPlugin.popup": '[_CUSTOM_LAYER_][_BUTTONS_]'
            });

            // The custom popup is defined inside a plugin (new or existing).
            $.FroalaEditor.PLUGINS.embedPlugin = function (editor) {
                // Create custom popup.
                function initPopup () {
                    // Popup buttons.
                    var popup_buttons = '';

                    // Create the list of buttons.
                    popup_buttons += '<div class="fr-buttons">';
                    popup_buttons += editor.button.buildList(['insertEmbedCode', '|']);
                    popup_buttons += '</div>';

                    // Load popup template.
                    var template = {
                        custom_layer: '<div class="embed-code-input-container"><input class="embed-code-input" type="text" placeholder="Paste embed code"></div>',
                        buttons: popup_buttons
                    };

                    // Create popup.
                    var $popup = editor.popups.create('embedPlugin.popup', template);

                    return $popup;
                }

                // Show the popup
                function showPopup () {
                    // Get the popup object defined above.
                    var $popup = editor.popups.get('embedPlugin.popup');

                    // If popup doesn't exist then create it.
                    // To improve performance it is best to create the popup when it is first needed
                    // and not when the editor is initialized.
                    if (!$popup) $popup = initPopup();
                    // Set the editor toolbar as the popup's container.
                    editor.popups.setContainer('embedPlugin.popup', editor.$tb);
                    // This custom popup is opened by pressing a button from the editor's toolbar.
                    // Get the button's object in order to place the popup relative to it.
                    var $btn = editor.$tb.find('.fr-command[data-cmd="embedBtn"]');

                    // Set the popup's position.
                    var left = $btn.offset().left + $btn.outerWidth() / 2;
                    var top = $btn.offset().top + (editor.opts.toolbarBottom ? 10 : $btn.outerHeight() - 10);

                    // Show the custom popup.
                    // The button's outerHeight is required in case the popup needs to be displayed above it.
                    editor.popups.show('embedPlugin.popup', left, top, $btn.outerHeight());
                }

                // Hide the custom popup.
                function hidePopup () {
                    editor.popups.hide('embedPlugin.popup');
                }

                // Methods visible outside the plugin.
                return {
                    showPopup: showPopup,
                    hidePopup: hidePopup
                }
            };

            // Define an icon and command for the button that opens the custom popup.
            $.FroalaEditor.DefineIcon('buttonIcon', { NAME: 'star'});
            $.FroalaEditor.RegisterCommand('embedBtn', {
                title: 'Embed code',
                icon: 'buttonIcon',
                undo: false,
                focus: false,
                plugin: 'embedPlugin',
                callback: function () {
                    //this.selection.save();
                    this.embedPlugin.showPopup();
                }
            });

            // Define custom popup 1.
            $.FroalaEditor.DefineIcon('insertEmbedCode', { NAME: 'Insert', template: 'text' });
            $.FroalaEditor.RegisterCommand('insertEmbedCode', {
                title: 'Insert Embed Code',
                undo: true,
                focus: true,
                refreshAfterCallback: true,
                callback: function () {

                    let embedCode = this.popups.get('embedPlugin.popup').find('.embed-code-input').val();
                    this.popups.get('embedPlugin.popup').find('.embed-code-input').val("");
                    let id = $(".embed-container").length + (Math.floor(Math.random() * (999 - 1)) + 1);

                    this.html.insert(
                        "<div class='embed-container' contenteditable='false'>" +
                        "<div class='embed-wrapper' id='embed-wrapper-"+id+"'></div>" +
                        embedCode +
                        "</div><p></p>\n"
                    );

                    // set html
                    //this.html.set(this.html.get());
                    this.undo.saveStep();

                    this.selection.setAtEnd(0);
                    this.selection.restore();

                    this.embedPlugin.hidePopup();
                }
            });
        },

        // insert plugin for removing embed code container
        froalaRemoveEmbed(){
            // Define custom popup 1.
            $.FroalaEditor.DefineIcon('deleteEmbedCode', { NAME: 'Delete', template: 'text' });
            $.FroalaEditor.RegisterCommand('deleteEmbedCode', {
                title: 'Delete Embed Code',
                undo: false,
                focus: false,
                refreshAfterCallback: true,
                callback: function () {
                    $(".embed-wrapper.selected").parents(".embed-container").remove();
                    this.removeEmbedPlugin.hidePopup();
                }
            });

            // Define popup template.
            $.extend($.FroalaEditor.POPUP_TEMPLATES, {
                "removeEmbedPlugin.popup": '[_BUTTONS_]'
            });


            // The custom popup is defined inside a plugin (new or existing).
            $.FroalaEditor.PLUGINS.removeEmbedPlugin = function (editor) {
                // Create custom popup.
                function initPopup () {
                    // Popup buttons.
                    var popup_buttons = '';

                    // Create the list of buttons.
                    popup_buttons += '<div class="fr-buttons">';
                    popup_buttons += editor.button.buildList(['deleteEmbedCode', '|']);
                    popup_buttons += '</div>';

                    // Load popup template.
                    var template = {
                        buttons: popup_buttons
                    };

                    // Create popup.
                    var $popup = editor.popups.create('removeEmbedPlugin.popup', template);

                    return $popup;
                }

                // Show the popup
                function showPopup (left, top) {
                    // Get the popup object defined above.
                    var $popup = editor.popups.get('removeEmbedPlugin.popup');

                    // If popup doesn't exist then create it.
                    // To improve performance it is best to create the popup when it is first needed
                    // and not when the editor is initialized.
                    if (!$popup) $popup = initPopup();

                    // Set the editor toolbar as the popup's container.
                    editor.popups.setContainer('removeEmbedPlugin.popup', editor.$tb);

                    // This will trigger the refresh event assigned to the popup.
                    // editor.popups.refresh('removeEmbedPlugin.popup');

                    // This custom popup is opened by pressing a button from the editor's toolbar.
                    // Get the button's object in order to place the popup relative to it.
                    var $btn = editor.$tb.find('.fr-command[data-cmd="embedBtn"]');

                    // Show the custom popup.
                    // The button's outerHeight is required in case the popup needs to be displayed above it.
                    // editor.popups.show('removeEmbedPlugin.popup', left, top, $btn.outerHeight());
                    editor.popups.show('removeEmbedPlugin.popup', left, top, $btn.outerHeight());
                }

                // Hide the custom popup.
                function hidePopup () {
                    editor.popups.hide('removeEmbedPlugin.popup');
                }

                // Methods visible outside the plugin.
                return {
                    showPopup: showPopup,
                    hidePopup: hidePopup
                }
            };
        },

        /**
         *  Handel's base functionality like selected class for embed,
         *  create new line paragraph when enter is clicked in figcaption etc
         */
        froalaPrepare(){
            // remove selected class on the embed wrapper when elsewhere is clicked
            $(document).click(function(e){
                if(e.target.className !== "embed-wrapper" && e.target.className !== "embed-wrapper selected"){
                    $(".embed-wrapper").removeClass("selected");
                }
            });

            var initializedEditorsIDs = [];
            var interval = setInterval(function(){
                var editorsCount = $(".froala").length;
                if( editorsCount > 0){
                    editorsCount++;

                    $('.froala').each(function(e){
                        var id = $(this).attr("id");
                        var editor = $('#'+id);

                        if(initializedEditorsIDs.indexOf(id) == -1){

                            // click even to select embed wraper and show popup delete button for embed code
                            editor.on('froalaEditor.click', function (e, editor, clickEvent) {
                                var clickedClass = clickEvent.target.className;
                                let id = clickEvent.target.id;
                                if(clickedClass == 'embed-wrapper'){
                                    $(".embed-wrapper").removeClass("selected");
                                    $("#"+id).addClass("selected");
                                    editor.removeEmbedPlugin.showPopup(clickEvent.pageX, clickEvent.pageY);
                                }else{
                                    $(".embed-wrapper").removeClass("selected");
                                }
                            });

                            // create new line paragraph when enter is clicked in figcaption etc
                            editor.froalaEditor('events.on', 'keydown', (e) => {
                                if(e.keyCode == 13) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    e.stopImmediatePropagation();

                                    var newLine = false;
                                    var blocks = $(this).froalaEditor('selection.blocks');

                                    if(blocks[0] !== undefined && blocks[0].nodeName == "FIGURE"){
                                        var afterElement = $(this).froalaEditor('selection.get').anchorNode;
                                        newLine = true;
                                    }else if(blocks[0] !== undefined && blocks[0].nodeName == "FIGCAPTION"){
                                        if(blocks[0].innerText == "\n" || blocks[0].innerText == "<br>"){
                                            var afterElement = $(this).froalaEditor('selection.get').anchorNode.parentNode;
                                        }else{
                                            var afterElement = $(this).froalaEditor('selection.get').anchorNode.parentElement.parentNode;
                                        }
                                        newLine = true;
                                    }
                                    //should we create an empty paragraph at dhe end of the element
                                    if(newLine) {
                                        $(this).froalaEditor('selection.setAfter', afterElement);
                                        $(this).froalaEditor('selection.restore');
                                        $(this).froalaEditor('html.insert', '<p></p>');
                                        return false;
                                    }
                                }
                            }, true);

                            // remove figure when the images is being deleted
                            $('.froala').each(function(e){
                                var id = $(this).attr("id");
                                $('#'+id).on('froalaEditor.image.beforeRemove', function (e, editor, $img) {
                                    $img.closest("figure").remove();
                                });
                            });
                        }

                        initializedEditorsIDs.push(id);

                    });

                    if( editorsCount === initializedEditorsIDs.length){
                        clearInterval(interval);
                    }
                }
            }, 1000);
        }
    }
};