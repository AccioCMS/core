<template>
    <div id="popupContent" class="popupContentLibrary">
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

                <div class="col-lg-8 col-md-7 col-sm-6 col-xs-6" id="filesMediaPopup2">

                    <div v-if="noResults">
                        {{trans.__noResults}}
                    </div>

                    <spinner :width="'30px'" :height="'30px'" :border="'5px'" v-if="spinner"></spinner>

                    <div v-if="!spinner && !noResults" :class="{'imageWrapper':true, 'active': isFileSelected(image.mediaID)}" v-for="(image, index) in getMediaList" @click="selectFile" :id="image.mediaID" :data-index="index">
                        <div class="singleImgContainer">
                            <img :src="constructMediaUrl(image)" draggable="false" v-if="image.type == 'image'">
                            <img :src="resourcesUrl(constructUrl(image))" draggable="false" v-else>
                        </div>
                        <p>{{ image.title }}</p>
                    </div>

                </div>


                <div class="col-lg-4 col-md-5 col-sm-6 col-xs-6" id="editPanel" v-if="selectedFiles[0] !== undefined">

                    <template v-if="Object.keys(selectedFiles).length == 1">
                        <div class="row clearfix">
                            <h5>{{trans.__details}}</h5>
                            <div class="col-xs-12" :class="{'col-lg-12 col-md-12 col-sm-12': selectedFiles[0].type == 'video', 'col-lg-6 col-md-6 col-sm-6': selectedFiles[0].type != 'video'}">
                                <template v-if="selectedFiles[0] !== undefined && selectedFiles[0].type != 'video'">
                                    <img :src="constructMediaUrl(selectedFiles[0])" id="detailsUrl">
                                </template>
                                <template v-else>
                                    <figure width="100%" height="100%">
                                        <video  width="100%" height="100%" controls>
                                            <source :src="constructMediaUrl(selectedFiles[0], true)" :type="'video/'+selectedFiles[0].extension" width="100%" height="100%" />
                                        </video>
                                    </figure>

                                </template>
                            </div>
                            <div class="mediaDescriptions" :class="{'col-lg-12 col-md-12 col-sm-12': selectedFiles[0].type == 'video', 'col-lg-6 col-md-6 col-sm-6': selectedFiles[0].type != 'video'}">

                                <span id="filename">{{ selectedFiles[0].filename }}</span>
                                <span id="filesize">{{ selectedFiles[0].filesize }} Mb</span>
                                <span id="dimensions">{{ selectedFiles[0].dimensions }}</span>
                                <span id="created">{{ selectedFiles[0].created_at }}</span>
                                <span id="typeDetails">{{ selectedFiles[0].type }}</span>

                                <a id="editImage" v-if="selectedFiles[0].type == 'image' && hasUpdatePermission" @click="openCropWindow">{{trans.__editBtn}}</a>
                                <a id="assignWatermarkBtn" @click="openModal" class="watermarkBtn" v-if="hasUpdatePermission && selectedFiles[0].type != 'video'">{{trans.__watermarkBtn}}</a>
                                <a id="deleteImage" @click="openModal" class="deleteImageBtn" v-if="hasDeletePermission">{{trans.__deleteBtn}}</a>
                            </div>
                        </div>

                        <hr class="row">

                        <div class="detailsFromContainer row" v-if="hasUpdatePermission">
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
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button class="btn btn-success pull-left" @click="editMedia">{{trans.__submitBtn}}</button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="row clearfix multiselect" v-if="multiselect && Object.keys(this.selectedFiles).length > 1">
                        <a id="assignWatermarkBtnMulti" @click="openModal" class="watermarkBtn btn btn-info" v-if="hasUpdatePermission">{{trans.__watermarkBtn}}</a>
                        <a id="deleteImageMulti" @click="openModal" class="deleteImageBtn btn btn-danger" v-if="hasDeletePermission">{{trans.__deleteBtn}}</a>
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
    import Multiselect from 'vue-multiselect'
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';

    export default{
        components: {
            Multiselect,
            Datepicker,
            'crop-image': CropImage
        },
        props: ['multiple', 'multipleInputs'],
        mixins: [globalComputed, globalData, globalMethods, globalUpdated],
        created(){
            this.$store.commit('setMediaList', []);
        },
        mounted(){
            this.$store.dispatch('openLoading');

            // permissions
            this.hasUpdatePermission = this.hasPermission('Media','update');
            this.hasDeletePermission = this.hasPermission('Media','delete');

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

            var dropzone = document.getElementById('dropzone');
            var global = this;
            dropzone.ondragover = function(){
                this.className = 'dropzone dragover text-container';
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
                __term: this.__('media.form.term'),
                __type: this.__('media.form.type'),
                __search: this.__('base.search'),
                __searchBtn: this.__('base.searchBtn'),
                __resetBtn: this.__('base.resetBtn'),
                __confirmBtn: this.__('base.confirmBtn'),
                __closeBtn: this.__('base.closeBtn'),
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
                __credit: this.__('media.form.credit'),
            };

        },
        data(){
            return {
                videoIconUrl: '',
                audioIconUrl: '',
                documentIconUrl: '',
                searchTerm: '',
                type: 'false',
                from: '',
                to: '',
                format: 'd MMMM yyyy',
                noResults: false,
                selectedFiles: [],
                selectedFileChangedTitle: '',
                selectedFileChangedCredit: '',
                selectedFileChangedDescription: '',
                selectedOptionForModal: "",
                multiselect: false,
                hasUpdatePermission: true,
                hasDeletePermission: false,
                selectedMediaFilesFromSavedState: [],
                pageNumber: 1,
            }
        },
        methods: {
            // repair url to get the thumb
            constructUrl(media, originalUrl = false){
                if(media.updated_at === undefined){
                    return;
                }
                var uploaded = media.updated_at;
                var res = uploaded.replace(/-/g, '');
                res = res.replace(/ /g, '');
                res = res.replace(/:/g, '');
                var rand = Math.floor((Math.random() * 100) + 1);

                var url = "";
                if(media.type == "image"){
                    url = "/"+media.fileDirectory + "/200x200/" + media.filename + "?"+res+rand;
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
                        if(resp.body.count){
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
                let type = this.type;
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

                let request = {
                    term: this.searchTerm,
                    page: this.pageNumber,
                    type: type,
                    from: from,
                    to: to
                };

                // make ajax request
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
            reset(updateImgInEditPanel){
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

                        this.searchTerm = '';
                        this.type = 'false';
                        this.from = '';
                        this.to = '';

                    });

                this.noResults = false;

                // reset selected image urls
                for(var i=0; i<this.selectedFiles.length; i++){
                    var rand = Math.floor((Math.random() * 100) + 1);
                    this.selectedFiles[i].url = this.selectedFiles[i].url + "?"+rand;
                }

                if(updateImgInEditPanel){
                    var src = $("#detailsUrl").attr('src');
                    $("#detailsUrl").attr('src', src+"?"+rand);
                }
            },

            selectFile(event){
                var currentClicked = '';
                var selectedIndex = '';
                var removedSelectedIndex = '';
                var multiFilesSelectedIndexes = []; // when selecting multiple images with shift

                // find wich file is beeing selected
                for(let i = 0; i < event.path.length; i++){
                    // check if image is selected
                    if(event.path[i].className == "imageWrapper"){
                        // get current clicked media index
                        currentClicked = event.path[i].dataset.index;
                        // get current media file ID (mediaID)
                        selectedIndex = event.path[i].id;

                        // if shift key isn't being held
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
                        event.path[i].className = "imageWrapper active";
                    }else if(event.path[i].className == "imageWrapper active"){ // if clicked media file is active
                        if(!event.ctrlKey){ // check if ctrl key is presed and if yes deselect all files
                            this.selectedFiles = [];
                            $(".imageWrapper.active").removeClass("active");
                            event.path[i].className = "imageWrapper active"; // make clicked media file active
                        }
                        removedSelectedIndex = event.path[i].id;
                        event.path[i].className = "imageWrapper";
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
                this.$http.post(this.basePath+'/'+this.$route.params.adminPrefix+'/media/json/edit', this.selectedFiles[0])
                    .then((resp) => {
                        var resultMsg = "";
                        var type = "success";
                        if(resp.body == "OK"){
                            resultMsg = this.trans.__editedSuccessfully;
                            this.reset(false);
                        }else{
                            type = "error";
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

                this.$http.post(this.basePath+'/'+this.$route.params.adminPrefix+'/media/json/delete', selectedFiles)
                    .then((resp) => {
                        if(resp.body == "OK"){
                            this.reset(false);
                            $(".modal").hide();
                            $(".imageWrapper.active").removeClass("active");

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
                        //$("#editPanel .row").hide(50);
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
                        //$("#editPanel .row").hide(50);
                        this.multiselect = false;
                    }, error => {
                        // if a error happens
                        new Noty({
                            type: "error",
                            layout: 'bottomLeft',
                            text: error.statusText
                        }).show();
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
            cancel(){
                this.$store.commit('setIsMediaOpen', false);
            },
            // choose the media files to be returned from library
            chooseFiles(){
                var type = "";
                var text = "";
                if(this.multiple){
                    this.$store.commit('setMediaSelectedFiles', this.selectedFiles);
                    this.$store.commit('setIsMediaOpen', false);
                    return;
                }else{
                    if(this.selectedFiles.length == 1){
                        this.$store.commit('setMediaSelectedFiles', this.selectedFiles);
                        this.$store.commit('setIsMediaOpen', false);
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
            isFileSelected(mediaID){
                if(this.selectedFiles.length > 0 && this.selectedMediaFilesFromSavedState.indexOf(mediaID) !== -1){
                    return true;
                }
                return false;
            }
        },
        computed:{
            getLibrarySavedState(){
                return this.$store.getters.get_library_saved_state;
            },
            getTranslation(){
                // returns translated value
                return this.$store.getters.get_translation;
            },
            isCropOpen(){
                // returns boolean is crop window open
                return this.$store.getters.get_is_crop_open;
            },
            // get base url
            basePath(){
                return this.$store.getters.get_base_path;
            },
            getMediaList(){
                return this.$store.getters.get_media_list;
            }
        }
    }
</script>
