<template>
    <div id="popupContent" class="popupContentLibrary" dusk="popupContentLibrary">
        <div class="imageWrapperPopupMedia" id="dropzone">
            <div class="imageContainerPopupMedia">

                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" id="filterSidebarMediaPopup">

                    <div class="form-group">
                        <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                            <input type="text" class="form-control" :placeholder="trans.__search + ' ...'" v-model="searchTerm">
                        </div>

                        <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                            <select class="form-control" v-model="type">
                                <option value="false" disabled>{{trans.__type}}</option>
                                <option value="all">{{trans.__all}}</option>
                                <option value="image">Image</option>
                                <option value="video">Video</option>
                                <option value="audio">Audio</option>
                                <option value="document">Document</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12 datepickerContainer">
                            <datepicker v-model="from" name="from" :format="format" placeholder="From: "></datepicker>
                        </div>

                        <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12 datepickerContainer">
                            <datepicker v-model="to" name="to" :format="format" placeholder="To: "></datepicker>
                        </div>

                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 searchBtnContainer">
                            <button class="btn btn-default" @click="makeSearch">{{trans.__searchBtn}}</button>
                            <button class="btn btn-warning" @click="reset(false)">{{trans.__resetBtn}}</button>
                        </div>

                    </div>

                </div>

                <div class="col-lg-9 col-md-7 col-sm-4 col-xs-12" id="filesMediaPopup">

                    <div class="x_title albumTitle" v-if="isAlbum">
                        <h2 style="margin:0;">{{ albumDetails.title }}</h2>
                        <div class="clearfix"></div>
                        <h4 style="cursor: pointer; text-align: start; font-size: 14px" @click="backToAlbumList()"><i class="fa fa-arrow-left backBtn" aria-hidden="true"></i> {{trans.__backToAlbums}}</h4>
                        <div class="clearfix"></div>
                    </div>

                    <div v-if="noResults || mediaCount == 0">
                        {{trans.__noResults}}
                    </div>

                    <spinner :width="'30px'" :height="'30px'" :border="'5px'" v-if="spinner"></spinner>

                    <div v-if="shouldHide(image.type,image.mediaID) && !spinner && !noResults" :class="{'imageWrapper':true, 'active': isFileSelected(image.mediaID)}" v-for="(image, index) in getMediaList" @click="selectFile" :id="image.mediaID" :data-index="index">
                        <div class="singleImgContainer">
                            <img :src="generateUrl(constructUrl(image))+'?'+image.updated_at" draggable="false" v-if="image.type == 'image'">
                            <img :src="resourcesUrl(constructUrl(image))" draggable="false" v-else>
                        </div>
                        <p>{{ image.title }}</p>
                    </div>

                </div>

                <div class="col-lg-3 col-md-5 col-sm-8 col-xs-12" id="editPanel" v-if="Object.keys(selectedFiles).length">

                    <template v-if="Object.keys(selectedFiles).length == 1">
                        <div class="row clearfix">
                            <h5>{{trans.__details}}</h5>
                            <div class="col-xs-12" :class="{'col-lg-12 col-md-12 col-sm-12': selectedFiles[0].type == 'video', 'col-lg-6 col-md-6 col-sm-6': selectedFiles[0].type != 'video'}">
                                <template v-if="selectedFiles[0] !== undefined && selectedFiles[0].type != 'video'">
                                    <img :src="generateUrl(constructUrl(selectedFiles[0]))+'?'+selectedFiles[0].updated_at" id="detailsUrl">
                                </template>
                                <template v-else>
                                    <figure width="100%" height="100%">
                                        <video width="100%" height="100%" controls>
                                            <source :src="generateUrl(constructUrl(selectedFiles[0], true))" :type="'video/'+selectedFiles[0].extension" width="100%" height="100%" />
                                        </video>
                                    </figure>
                                </template>
                            </div>

                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 mediaDescriptions">
                                <span id="filename">{{ selectedFiles[0].filename }}</span>
                                <span id="filesize">{{ selectedFiles[0].filesize }} Mb</span>
                                <span id="dimensions">{{ selectedFiles[0].dimensions }}</span>
                                <span id="created">{{ selectedFiles[0].created_at }}</span>
                                <span id="typeDetails">{{ selectedFiles[0].type }}</span>

                                <a id="editImage" v-if="selectedFiles[0].type == 'image'" @click="openCropWindow">{{trans.__editBtn}}</a>
                                <a id="assignWatermarkBtn" @click="openModal" class="watermarkBtn" v-if="selectedFiles[0].type != 'video' && getGlobalData.settings.watermark !== undefined && parseInt(getGlobalData.settings.watermark)">{{trans.__watermarkBtn}}</a>
                                <a id="deleteImage" @click="openModal" class="deleteImageBtn">{{trans.__deleteBtn}}</a>
                            </div>
                        </div>

                        <hr class="row">

                        <div class="detailsFromContainer row">
                            <div class="form-group clearfix">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__title}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" id="titleInput" v-model="selectedFileChangedTitle">
                                </div>
                            </div>

                            <div class="form-group clearfix">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__description}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <textarea class="form-control" id="description" rows="5" v-model="selectedFileChangedDescription"></textarea>
                                </div>
                            </div>

                            <div class="form-group clearfix">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__credit}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" id="credit" v-model="selectedFileChangedCredit">
                                </div>
                            </div>

                            <div class="form-group clearfix">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__album}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <multiselect
                                            v-model="selectedFileAlbums"
                                            :options="albums"
                                            :multiple="true"
                                            :close-on-select="false"
                                            :clear-on-select="false"
                                            :hide-selected="true"
                                            :placeholder="trans.__pickSome"
                                            label="title"
                                            track-by="title"></multiselect>
                                </div>
                            </div>

                            <div class="form-group clearfix">
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button type="button" class="btn btn-success pull-left" @click="editMedia">{{trans.__submitBtn}}</button>
                                </div>
                            </div>

                        </div>
                    </template>

                    <div class="row clearfix" v-if="Object.keys(selectedFiles).length > 1">
                        <a id="assignWatermarkBtnMulti" @click="openModal" class="watermarkBtn btn btn-info">{{trans.__watermarkBtn}}</a>
                        <a id="deleteImageMulti" @click="openModal" class="deleteImageBtn btn btn-danger">{{trans.__deleteBtn}}</a>
                    </div>
                </div>

                <div id="popupButtons" class="col-lg-12 col-md-12">
                    <div class="btn-container">
                        <button type="button" class="btn btn-danger" @click="cancel($event)">{{trans.__cancelBtn}}</button>
                        <button type="button" class="btn btn-primary" @click="chooseFiles($event)" dusk="chooseMedia">{{trans.__chooseBtn}}</button>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL -->
        <div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">

                    <div class="modal-header">
                        <button type="button" class="close" @click="closeModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                        <h4 class="modal-title" id="myModalLabel2">{{trans.__confirmBtn}}</h4>
                    </div>
                    <div class="modal-body">
                        <h4>{{trans.__sure}}</h4>
                        <p id="confirmDialogMsg"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal" @click="closeModal">{{trans.__closeBtn}}</button>
                        <button type="button" class="btn btn-primary" @click="confirmSelected">{{trans.__confirmBtn}}</button>
                    </div>

                </div>
            </div>
        </div>
        <!-- MODAL -->

        <!-- CROP IMAGE -->
        <crop-image v-if="isCropOpen" :selected_image="selectedFiles[0]" @reset="reset(true)"></crop-image>

    </div>
</template>

<script>
    import Datepicker from 'vuejs-datepicker';
    import CropImage from './CropImage.vue'
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        components: {
            Datepicker,
            'crop-image': CropImage
        },
        props: ['isAlbum'],
        mounted(){
            this.$store.dispatch('openLoading');
            this.$store.commit('setMediaList', []);
            // permissions
            this.hasUpdatePermission = this.hasPermission('Media','update');
            this.hasDeletePermission = this.hasPermission('Media','delete');

            // if the album ID is set
            if(this.getAlbumID !== 0 && this.isAlbum){
                // instances from the saved state
                this.searchTerm = this.getLibrarySavedState.album.searchTerm;
                this.type = this.getLibrarySavedState.album.type;
                this.from = this.getLibrarySavedState.album.from;
                this.to = this.getLibrarySavedState.album.to;
                this.selectedFiles = this.getLibrarySavedState.album.selectedFiles;

                // get media IDs that have relation with this album ID
                // get the album info if we are updating
                this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/album/get-media-ids-of-album/'+this.getAlbumID)
                    .then((resp) => {
                        this.albumRelatedMediaIDs = resp.body;
                    });

                /* Album Data */
                this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/album/details/'+this.getAlbumID)
                    .then((resp) => {
                        this.albumDetails = resp.body.album[this.$route.params.lang];
                        this.$store.commit('setMediaList', resp.body.images);

                        this.$store.state.pagination = 1;
                        this.$store.state.imagesExtensions = resp.body.imagesExtensions;
                        this.$store.state.videoExtensions = resp.body.videoExtensions;
                        this.$store.state.audioExtensions = resp.body.audioExtensions;
                        this.$store.state.documentExtensions = resp.body.documentExtensions;

                        this.videoIconUrl = resp.body.videoIconUrl;
                        this.audioIconUrl = resp.body.audioIconUrl;
                        this.documentIconUrl = resp.body.documentIconUrl;

                        this.$store.dispatch('closeLoading');
                    });
                // if search has been made
                if(this.getLibrarySavedState.album.filtered !== undefined && this.getLibrarySavedState.album.filtered){
                    this.makeSearch();
                }
            }else{ // normal library -- all files

                // Get the first 100 results
                this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/media/json/get-list/'+ 1)
                    .then((resp) => {
                        this.$store.commit('setMediaList', resp.body.list);
                        this.$store.state.pagination = parseInt(resp.body.pagination);
                        this.$store.state.imagesExtensions = resp.body.imagesExtensions;
                        this.$store.state.videoExtensions = resp.body.videoExtensions;
                        this.$store.state.audioExtensions = resp.body.audioExtensions;
                        this.$store.state.documentExtensions = resp.body.documentExtensions;
                        this.videoIconUrl = resp.body.videoIconUrl;
                        this.audioIconUrl = resp.body.audioIconUrl;
                        this.documentIconUrl = resp.body.documentIconUrl;
                        this.$store.dispatch('closeLoading');
                    });

                // instances from the saved state
                this.searchTerm = this.getLibrarySavedState.library.searchTerm;
                this.type = this.getLibrarySavedState.library.type;
                this.from = this.getLibrarySavedState.library.from;
                this.to = this.getLibrarySavedState.library.to;
                this.selectedFiles = this.getLibrarySavedState.library.selectedFiles;
                // if search has been made
                if(this.getLibrarySavedState.library.filtered !== undefined && this.getLibrarySavedState.library.filtered){
                    this.makeSearch();
                }
            }

            // if there are any selected files from saved state
            if(this.selectedFiles.length > 0){
                for(let k in this.selectedFiles){
                    this.selectedMediaFilesFromSavedState.push(this.selectedFiles[k].mediaID);
                }
                // open edit panel for only one selected file
                if(this.selectedFiles.length == 1){
                    this.populateEditPanel();
                }else{
                    // open edit panel for only one selected file
                    this.multiselect = true;
                }
            }

            // get all albums with the related images
            this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/album/get-all/0')
                .then((resp) => {
                    this.albums = resp.body.data;
                });

            var dropzone = document.getElementById('dropzone');
            var global = this;
            dropzone.ondragover = function(){
                this.className = 'dropzone dragover';
                if(global.getAlbumID !== 0 && global.isAlbum){
                    global.$store.commit('setLibrarySavedStateForUpload', { fromAlbum: true });
                }
                global.$store.commit('setPopUpActiveMediaView', 'upload');
                return false;
            }

            $('#filesMediaPopup').bind('scroll', function(){
                if($(this).scrollTop() + $(this).innerHeight()>=$(this)[0].scrollHeight){
                    global.loadMore();
                }
            });

            // translations
            this.trans = {
                __backToAlbums: this.__('media.backToAlbums'),
                __term: this.__('media.form.term'),
                __type: this.__('media.form.type'),
                __search: this.__('base.search'),
                __searchBtn: this.__('base.searchBtn'),
                __resetBtn: this.__('base.resetBtn'),
                __confirmBtn: this.__('base.confirmBtn'),
                __closeBtn: this.__('base.closeBtn'),
                __cancelBtn: this.__('base.cancelBtn'),
                __chooseBtn: this.__('base.chooseBtn'),
                __submitBtn: this.__('base.submitBtn'),
                __pickSome: this.__('base.pickSome'),
                __description: this.__('base.description'),
                __details: this.__('base.details'),
                __all: this.__('base.all'),
                __noResults: this.__('base.noResults'),
                __searchTermEmpty: this.__('base.searchTermEmpty'),
                __editedSuccessfully: this.__('media.editedSuccessfully'),
                __editedFailed: this.__('media.editedFailed'),
                __onlyOneFileError: this.__('media.onlyOneFileError'),
                __noFileSelectedError: this.__('media.noFileSelectedError'),
                __from: this.__('media.form.from'),
                __to: this.__('media.form.to'),
                __deleteBtn: this.__('media.form.deleteBtn'),
                __watermarkBtn: this.__('media.form.watermarkBtn'),
                __editBtn: this.__('media.form.editBtn'),
                __title: this.__('media.form.title'),
                __album: this.__('media.form.album'),
                __credit: this.__('media.form.credit'),
            };
            $('body').css('overflow', 'hidden');
        },
        beforeDestroy(){
            $('body').css('overflow', 'visible');
        },
        data(){
            return{
                videoIconUrl: '',
                audioIconUrl: '',
                documentIconUrl: '',
                searchTerm: '',
                type: 'false',
                from: '',
                to: '',
                albums: [],
                format: 'd MMMM yyyy',
                noResults: false,
                selectedFiles: [],
                selectedFileChangedTitle: '',
                selectedFileChangedCredit: '',
                selectedFileChangedDescription: '',
                selectedFileAlbums: [],
                selectedOptionForModal: "",
                hasUpdatePermission: true,
                hasDeletePermission: false,
                multiselect: false,
                albumRelatedMediaIDs: [],
                albumDetails: '',
                selectedMediaFilesFromSavedState: [],
                pageNumber: 1,
                mediaCount: 0,
            }
        },
        methods: {
            // repair url to get the thumb
            constructUrl(media, originalUrl = false){
                if(media.updated_at === undefined){
                    return;
                }
                var url = "";
                if(media.type == "image"){
                    url = "/"+media.fileDirectory + "/200x200/" + media.filename;
                }else if(media.type == "document"){
                    url = this.documentIconUrl;
                }else if(media.type == "video"){
                    if(originalUrl){
                        url = "/"+media.url;
                    }else{
                        url = this.videoIconUrl;
                    }
                }else if(media.type == "audio"){
                    url = this.audioIconUrl;
                }
                return url;
            },

            // load 100 more result when scroll goes to the end
            loadMore(){
                this.pageNumber = this.pageNumber + 1;
                this.$store.state.pagination = this.pageNumber;
                this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/media/json/get-list/'+ this.pageNumber)
                    .then((resp) => {
                        if(resp.body.count != 0){
                            let list = this.getMediaList;
                            for(let i=0; i< resp.body.list.length; i++){
                                list.push(resp.body.list[i]);
                            }
                            this.$store.commit('setMediaList', list);
                        }
                    });
            },
            // make search with the search term
            makeSearch(){
                // open loading
                this.$store.commit('setSpinner', true);

                var from = this.from;
                var to = this.to;
                if(this.from == ''){
                    from = 'null';
                }else{
                    var monthFrom = parseInt(from.getMonth())+1;
                    from = from.getDate() + "-" + monthFrom + "-" + from.getFullYear();
                }
                if(this.to == ''){
                    to = 'null';
                }else{
                    var monthTo = parseInt(to.getMonth())+1;
                    to = to.getDate() + "-" + monthTo + "-" + to.getFullYear();
                }

                // if user didn't choose a type make all default
                var type = this.type;
                if(this.type == "false"){
                    type = 'all';
                }

                // set saved instances
                let obj = {
                    searchTerm: this.searchTerm,
                    type: this.type,
                    from: this.from,
                    to: this.to,
                    selectedFiles: this.selectedFiles,
                    filtered: true,
                };
                if(this.isAlbum){
                    this.$store.commit('setLibrarySavedStateForAlbum', obj);
                }else{
                    this.$store.commit('setLibrarySavedStateForLibrary', obj);
                }

                let request = {
                    term: this.searchTerm,
                    page: this.pageNumber,
                    type: type,
                    from: from,
                    to: to
                };

                this.noResults = false;

                this.$http.post(this.basePath+'/'+this.$route.params.adminPrefix+'/media/json/search', request)
                    .then((resp) => {
                        if(!resp.body.length){
                            this.noResults = true;
                        }else{
                            this.$store.commit('setMediaList', resp.body);
                        }
                        // close loading
                        this.$store.commit('setSpinner', false);
                    });
            },

            /**
             * Rest media list when media is edited
             * @param updateImgInEditPanel
             */
            reset(updateImgInEditPanel = true){
                // Get the first 100 results
                this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/media/json/get-list/'+ 1)
                    .then((resp) => {
                        this.$store.commit('setMediaList', resp.body.list);
                        this.refreshImageUrls();

                        this.$store.state.pagination = parseInt(resp.body.pagination);
                        this.$store.state.imagesExtensions = resp.body.imagesExtensions;
                        this.$store.state.videoExtensions = resp.body.videoExtensions;
                        this.$store.state.audioExtensions = resp.body.audioExtensions;
                        this.$store.state.documentExtensions = resp.body.documentExtensions;

                        this.videoIconUrl = resp.body.videoIconUrl;
                        this.audioIconUrl = resp.body.audioIconUrl;
                        this.documentIconUrl = resp.body.documentIconUrl;

                        this.searchTerm = '';
                        this.type = 'false';
                        this.from = '';
                        this.to = '';
                    });
                this.noResults = false;
            },

            /**
             * Refresh image urls when media is edited
             */
            refreshImageUrls(){
                let mediaList = this.getMediaList;
                let newList = [];
                this.$store.commit('setMediaList', {});
                for(let k in mediaList) {
                    let media = mediaList[k];

                    let rand = Math.floor((Math.random() * 100) + 1);
                    if(media.type == "image"){
                        let filename = media.filename.split("?");
                        media.filename = filename[0] + "?" + rand;
                    }

                    newList[k] = media;
                }
                this.$store.commit('setMediaList', newList);

                // reset selected image urls
                for(var i=0; i<this.selectedFiles.length; i++){
                    var rand = Math.floor((Math.random() * 100) + 1);
                    this.selectedFiles[i].url = this.selectedFiles[i].url + "?"+rand;
                }
                let src = $("#detailsImage").attr('src');
                $("#detailsImage").attr('src', src+"?"+rand);
            },

            selectFile(event){
                var currentClicked = '';
                var selectedIndex = '';
                var removedSelectedIndex = '';
                var multiFilesSelectedIndexes = []; // when selecting multiple images with shift
                this.selectedFileAlbums = [];

                let path = event.path || (event.composedPath && event.composedPath());

                // find wich file is beeing selected
                for(let i = 0; i < path.length; i++){
                    // check if image is selected
                    if(path[i].className == "imageWrapper"){
                        // get current clicked media index
                        currentClicked = path[i].dataset.index;
                        // get current media file ID (mediaID)
                        selectedIndex = path[i].id;

                        // if shift key is not pressed
                        if(!event.shiftKey){
                            if(!event.ctrlKey){ // if CTRL key is not beeing pressed remove selected
                                this.selectedFiles = [];
                                $(".imageWrapper.active").removeClass("active");
                            }
                        }else{
                            if(this.selectedFiles.length > 0){ // if there are files selected
                                // first selected file
                                var firstChild = parseInt($("#filesMediaPopup .active:first").attr('data-index'));
                                // current clicked file
                                currentClicked = parseInt(currentClicked);
                                // if the first selected element has a greater index as the current selected
                                if(firstChild < currentClicked){
                                    $(".imageWrapper.active").removeClass("active");
                                    for(var sCount = firstChild; sCount < currentClicked; sCount++){
                                        var id = $(".imageWrapper[data-index='"+sCount+"']").attr('id');
                                        $("#"+id).addClass("active");
                                        multiFilesSelectedIndexes.push(sCount);
                                    }
                                    multiFilesSelectedIndexes.push(currentClicked);
                                    // if the first selected element has a lower index as the current selected
                                }else if(firstChild > currentClicked){
                                    $(".imageWrapper.active").removeClass("active");
                                    for(var sCount = firstChild; sCount > currentClicked; sCount--){
                                        var id = $(".imageWrapper[data-index='"+sCount+"']").attr('id');
                                        $("#"+id).addClass("active");
                                        multiFilesSelectedIndexes.push(sCount);
                                    }
                                    multiFilesSelectedIndexes.push(currentClicked);
                                }
                            }
                        }
                        // set clicked file to active active class
                        path[i].className = "imageWrapper active";
                    }else if(path[i].className == "imageWrapper active"){ // if clicked media file is active
                        if(!event.ctrlKey){ // check if ctrl key is presed and if yes deselect all files
                            this.selectedFiles = [];
                            $(".imageWrapper.active").removeClass("active");
                            path[i].className = "imageWrapper active"; // make clicked media file active
                        }
                        removedSelectedIndex = path[i].id;
                        path[i].className = "imageWrapper";
                    }
                }

                // if there are multiple files selected by using shift key
                // make selected list
                if(multiFilesSelectedIndexes.length > 0){
                    this.selectedFiles = [];
                    for(var k in multiFilesSelectedIndexes){
                        this.selectedFiles.push(this.getMediaList[multiFilesSelectedIndexes[k]]);
                    }

                    // if there is only a file selected
                    // make selected list
                }else{
                    for(var i = 0; i < this.getMediaList.length; i++){
                        // if it is being selected insert it into the selected array
                        if(selectedIndex != '' && this.getMediaList[i].mediaID == selectedIndex){
                            this.selectedFiles.push(this.getMediaList[i]);
                        }
                        // if it is being unselected remove from selected array
                        if(removedSelectedIndex != '' && this.getMediaList[i].mediaID == removedSelectedIndex){
                            this.selectedFiles.splice(currentClicked, 1);
                        }
                    }
                }

                // if only one file is selected open edit panel
                if(this.selectedFiles.length == 1){
                    this.populateEditPanel();

                    // get related albums
                    this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/album/get-album-relation/'+this.selectedFiles[0].mediaID)
                        .then((resp) => {
                            this.selectedFileAlbums = resp.body;
                        });

                }else if(this.selectedFiles.length > 1){
                    this.multiselect = true;
                }else{
                    this.multiselect = false;
                }
            },

            // set media edit panel params
            populateEditPanel(){
                this.selectedFileChangedTitle = this.selectedFiles[0].title;
                this.selectedFileChangedCredit = this.selectedFiles[0].credit;
                this.selectedFileChangedDescription = this.selectedFiles[0].description;
                this.multiselect = false;
            },

            // submit the edit -- edit a media file
            editMedia(){
                // open loading
                this.$store.dispatch('openLoading');

                this.selectedFiles[0].title = this.selectedFileChangedTitle;
                this.selectedFiles[0].credit = this.selectedFileChangedCredit;
                this.selectedFiles[0].description = this.selectedFileChangedDescription;
                this.selectedFiles[0].selectedFileAlbums = this.selectedFileAlbums;
                this.$http.post(this.basePath+'/'+this.$route.params.adminPrefix+'/media/json/edit', this.selectedFiles[0])
                    .then((resp) => {
                        var resultMsg = "";
                        var type = "success";
                        if(resp.body == "OK"){
                            resultMsg = this.trans.__editedSuccessfully;
                            this.reset(false);
                        }else{
                            type = "error"
                            resultMsg = this.trans.__editedFailed;
                        }
                        // close loading
                        this.$store.dispatch('closeLoading');

                        new Noty({
                            type: type,
                            layout: 'topRight',
                            text: resultMsg,
                            timeout: 3000,
                            closeWith: ['button']
                        }).show();
                    });
            },
            openModal(event){
                var btnClicked = event.target.className;
                if(btnClicked == "watermarkBtn" || btnClicked == "watermarkBtn btn btn-info"){
                    $("#confirmDialogMsg").text("By pressing confirm you add a watermark to the selected image. Can not be undone.");
                    this.selectedOptionForModal = "watermark";
                }else if(btnClicked == "deleteImageBtn" || btnClicked == "deleteImageBtn btn btn-danger"){
                    $("#confirmDialogMsg").text("By pressing confirm you will completely delete this media file");
                    this.selectedOptionForModal = "delete";
                }
                $(".modal").css("opacity", 1);
                $(".modal").show();
            },
            // confirm btn in modal popup
            // depends if delete btn or watermark is clicked
            confirmSelected(){
                if(this.selectedOptionForModal == "watermark"){
                    this.setWatermark();
                }else if(this.selectedOptionForModal == "delete"){
                    this.deleteSelected();
                }
            },
            // delete the selected media file
            deleteSelected(){
                // open loading
                this.$store.dispatch('openLoading');

                let selectedFiles = this.selectedFiles;
                this.selectedFiles = [];

                var global = this;
                this.$http.post(this.basePath+'/'+this.$route.params.adminPrefix+'/media/json/delete', selectedFiles)
                    .then((resp) => {
                        if(resp.body == "OK"){
                            this.reset(false);
                            $(".modal").hide();
                            $(".imageWrapper.active").removeClass("active");
                            $("#editPanel .row").hide(50);

                            new Noty({
                                type: "success",
                                layout: 'topRight',
                                text: "Media file is deleted",
                                timeout: 3000,
                                closeWith: ['button']
                            }).show();
                        }else{
                            $(".modal").hide();
                            new Noty({
                                type: "error",
                                layout: 'topRight',
                                text: "Media file could not be deleted",
                                timeout: 3000,
                                closeWith: ['button']
                            }).show();
                        }
                        // close loading
                        this.$store.dispatch('closeLoading');
                        $("#editPanel .row").hide(50);
                        this.multiselect = false;
                    });
            },
            setWatermark(){
                // open loading
                this.$store.dispatch('openLoading');

                var global = this;
                this.$http.post(this.basePath+'/'+this.$route.params.adminPrefix+'/media/json/assign-watermark', this.selectedFiles)
                    .then((resp) => {
                        // if response is ok - if watermarks are set
                        if(resp.body == "OK"){
                            // reset library and selected files
                            setTimeout(function(e){
                                global.reset(true)
                            },1000);
                            global.selectedFiles = [];
                            $(".modal").hide();
                            $(".imageWrapper.active").removeClass("active");
                            new Noty({
                                type: "success",
                                layout: 'topRight',
                                text: "Image is watermarked",
                                timeout: 3000,
                                closeWith: ['button']
                            }).show();
                        }else{
                            $(".modal").hide();
                            new Noty({
                                type: "error",
                                layout: 'topRight',
                                text: "Image could not be watermarked",
                                timeout: 3000,
                                closeWith: ['button']
                            }).show();
                        }
                        // close loading
                        this.$store.dispatch('closeLoading');
                        // remove edit panel
                        $("#editPanel .row").hide(50);
                        this.multiselect = false;
                    });
            },
            closeModal(){
                $(".modal").hide();
                this.selectedOptionForModal = "";
            },
            openCropWindow(){
                if(this.selectedFiles[0].type == "image"){
                    this.$store.commit('setIsCropOpen', true);
                }
            },
            // cancel pop
            cancel(event){
                event.preventDefault();
                this.deleteSavedState();
                this.$store.commit('setSelectedAlbumID', 0);
                this.$store.commit('setIsMediaOpen', false);
            },
            deleteSavedState(){
                this.$store.commit('setLibrarySavedState', {
                    library: {
                        searchTerm: '',
                        type: 'false',
                        from: '',
                        to: '',
                        filtered: false,
                        selectedFiles: [],
                    },
                    album: {
                        searchTerm: '',
                        type: 'false',
                        from: '',
                        to: '',
                        filtered: false,
                        selectedFiles: [],
                    },
                    upload: {
                        fromAlbum: false,
                    }
                });
            },
            // choose the media files to be returned from library
            chooseFiles(e){
                e.preventDefault();
                var type = "";
                var text = "";

                // if media is open from editor button
                if(this.mediaOptions.inputName !== undefined){
                    if(this.mediaOptions.froalaInstance !== undefined){
                        // restore selection
                        this.mediaOptions.froalaInstance.selection.restore();
                        let html = "";

                        for(let k in this.selectedFiles){
                            if(this.mediaOptions.format == "image"){
                                html += "<figure>";
                                html += "<img src='"+this.baseURL+'/'+this.selectedFiles[k].url+"' alt='"+this.selectedFiles[k].description+"' title='"+this.selectedFiles[k].title+"' />";

                                if(this.selectedFiles[k].description || this.selectedFiles[k].credit){
                                    html += "<figcaption>";
                                    if(this.selectedFiles[k].description){
                                        html += "<span>"+this.selectedFiles[k].description+"</span>";
                                    }
                                    if(this.selectedFiles[k].credit){
                                        html += "<cite>"+this.selectedFiles[k].credit+"</cite>";
                                    }
                                    html += "</figcaption>";
                                }

                                html += "</figure> \n";
                            }else if(this.mediaOptions.format == "video") {
                                html += "<figure>";
                                html += "<video controls>";
                                html += "<img src='" + this.baseURL+'/'+  this.selectedFiles[k].url + "' alt='" + this.selectedFiles[k].description + "' title='" + this.selectedFiles[k].title + "' />";
                                html += "<source src='" + this.baseURL+'/' + this.selectedFiles[k].url + "' type='video/" + this.selectedFiles[k].extension + "' />";
                                html += "</video>\n";
                                if(this.selectedFiles[k].description || this.selectedFiles[k].credit){
                                    html += "<figcaption>";
                                    if(this.selectedFiles[k].description){
                                        html += "<span>" + this.selectedFiles[k].description + "</span>";
                                    }
                                    if(this.selectedFiles[k].credit){
                                        html += "<cite>" + this.selectedFiles[k].credit + "</cite>";
                                    }
                                    html += "</figcaption>";
                                }
                                html += "</figure> \n";

                                // insert selected images to editor
                                // $('#'+this.mediaOptions.inputName).froalaEditor('codeView.toggle');
                                // $('#'+this.mediaOptions.inputName).froalaEditor('codeView.toggle');
                            }
                        }

                        // Insert images into editor
                        this.mediaOptions.froalaInstance.html.insert(html, false);

                        this.$store.commit('setSelectedAlbumID', 0);
                        this.deleteSavedState();
                        this.$store.commit('setIsMediaOpen', false);
                        return;
                    }
                }

                if(this.mediaOptions.multiple){ // if is allowed to be selected more than 1 file
                    if(this.mediaOptions.multipleInputs){ // if there are multiple file inputs in the same view
                        if(this.mediaOptions.langSlug != ''){ // if the file is translatable
                            var fieldSlug = this.mediaOptions.inputName+"__lang__"+this.mediaOptions.langSlug;
                        }else{ // if the file is not translatable
                            var fieldSlug = this.mediaOptions.inputName;
                        }
                        // repopulate selected files with the old selected files from store in case we are just adding new files not clearing
                        this.rePopulateSelectedFiles(fieldSlug);
                        var filesArr = [fieldSlug, this.selectedFiles];

                        this.$store.commit('setMediaSelectedFilesNested', filesArr);
                    }else{
                        this.$store.commit('setMediaSelectedFiles', this.selectedFiles);
                    }
                    this.$store.commit('setSelectedAlbumID', 0);
                    this.deleteSavedState();
                    this.$store.commit('setIsMediaOpen', false);
                }else{
                    if(this.selectedFiles.length == 1){
                        if(this.mediaOptions.inputName != ''){
                            if(this.mediaOptions.langSlug != ''){ // if the file is translatable
                                var filesArr = [this.mediaOptions.inputName+"__lang__"+this.mediaOptions.langSlug, this.selectedFiles];
                            }else{// if the file is not translatable
                                var filesArr = [this.mediaOptions.inputName, this.selectedFiles];
                            }
                            this.$store.commit('setMediaSelectedFilesNested', filesArr);
                            this.$store.commit('setSelectedAlbumID', 0);
                            this.deleteSavedState();
                            this.$store.commit('setIsMediaOpen', false);
                        }else{
                            this.$store.commit('setMediaSelectedFiles', this.selectedFiles);
                            this.$store.commit('setSelectedAlbumID', 0);
                            this.deleteSavedState();
                            this.$store.commit('setIsMediaOpen', false);
                        }
                        return;
                    }else if(this.selectedFiles.length > 1){
                        type = "error";
                        text = this.trans.__onlyOneFileError;
                    }else{
                        type = "error";
                        text = this.trans.__noFileSelectedError;
                    }
                }

                new Noty({
                    type: type,
                    layout: 'topRight',
                    text: text,
                    timeout: 3000,
                    closeWith: ['button']
                }).show();
            },
            // re-populate selected files in case we are adding new file to a field
            rePopulateSelectedFiles(fieldSlug){
                // if users clicked in new image not in the change button
                if(this.mediaOptions !== undefined && this.mediaOptions.clear !== undefined && !this.mediaOptions.clear){
                    // the length of the global selected files
                    let mediaFileLength = Object.keys(this.mediaSelectedFiles).length;
                    if(mediaFileLength && this.mediaSelectedFiles[fieldSlug] !== undefined){
                        let currentSelectedFiles = this.selectedFiles; // save the new selected files of this ssession
                        this.selectedFiles = []; // make the new selected files array empty
                        for(let k in this.mediaSelectedFiles[fieldSlug]){ // loop throw the old selected files and mark them as the new selected
                            this.selectedFiles[k] = this.mediaSelectedFiles[fieldSlug][k];
                        }
                        // add the new sessions selected files to the stack to
                        for(let k in currentSelectedFiles){
                            this.selectedFiles.push(currentSelectedFiles[k]);
                        }
                    }
                }
            },
            shouldHide(type, id){ // this function hides files if they format is not required
                if(this.getAlbumID !== 0 && this.isAlbum){
                    if(this.albumRelatedMediaIDs.indexOf(id) == -1){
                        return false;
                    }
                }

                if(this.mediaOptions.format == ''){
                    this.addMediaCount;
                    return true;
                }else if(this.mediaOptions.format != type){
                    return false;
                }

                this.addMediaCount;
                return true;
            },
            // return view to album
            backToAlbumList(){
                let obj = {
                    searchTerm: "",
                    type: "",
                    from: "",
                    to: "",
                    selectedFiles: [],
                    filtered: false,
                };
                if(this.isAlbum){
                    this.$store.commit('setLibrarySavedStateForAlbum', obj);
                }else{
                    this.$store.commit('setLibrarySavedStateForLibrary', obj);
                }
                this.$store.commit('setSelectedAlbumID', 0);
            },
            // register the saved state of this component
            registerSavedState(){
                let obj = {
                    searchTerm: this.searchTerm,
                    type: this.type,
                    from: this.from,
                    to: this.to,
                    selectedFiles: this.selectedFiles,
                    filtered: this.getLibrarySavedState.library.filtered,
                };
                if(this.isAlbum){
                    this.$store.commit('setLibrarySavedStateForAlbum', obj);
                }else{
                    this.$store.commit('setLibrarySavedStateForLibrary', obj);
                }
            },
            isFileSelected(mediaID){
                if(this.selectedFiles.length > 0 && this.selectedMediaFilesFromSavedState.indexOf(mediaID) !== -1){
                    return true;
                }
                return false;
            },

            randomNr(){

            }
        },
        computed:{
            getMediaSelectedFiles(){
                return this.$store.getters.get_media_selected_files;
            },
            getAlbumID(){
                return this.$store.getters.get_selected_album_ID;
            },
            getLibrarySavedState(){
                return this.$store.getters.get_library_saved_state;
            },
            mediaOptions(){
                // return the options for opening the media popup
                return this.$store.getters.get_open_media_options;
            },
            mediaSelectedFiles(){
                // return when user chose files form media
                return this.$store.getters.get_media_selected_files;
            },
            isCropOpen(){
                // returns boolean is crop window open
                return this.$store.getters.get_is_crop_open;
            },
            addMediaCount(){
                this.mediaCount++;
            },
            getMediaList(){
                return this.$store.getters.get_media_list;
            }
        }
    }
</script>
