<template>
    <div id="popupContent">
        <div class="text-container" id="dropzone">

            <div class="progressedImage" v-for="(image, index) in images">
                <h5>{{ image.name }}</h5>
                <div class="progress">
                    <div class="progress-bar" :id="index" role="progressbar" aria-valuenow="30" :class="{complete: image.progress == 100, failed: image.failed}" aria-valuemin="0" aria-valuemax="100" v-bind:style="{width: image.progress + '%'}">
                        <p class="completedMsg">{{trans.__completed}}</p>
                        <p class="failedMsg">{{trans.__failed}}</p>
                    </div>
                </div>
                <p class="errorMsg" v-if="image.msg != ''">{{ image.msg }}</p>
            </div>

            <p v-if="!imagesOnProgress">{{trans.__drop}}</p>
            <p v-if="!imagesOnProgress">{{trans.__or}}</p>
            <button v-if="!imagesOnProgress" @click="triggerClickChooseFiles">{{trans.__select}}</button>
            <input type="file" name="files[]" @change="chooseFiles" id="selectedFiles" multiple>
            <button class="btn btn-default" @click="goToLibrary">{{trans.__goToLibrary}}</button>
        </div>
    </div>
</template>
<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        mounted(){
            var dropzone = document.getElementById('dropzone');
            function upload(files){
                window.upload(files);
            }

            var global = this;
            dropzone.ondrop = function(e){
                e.preventDefault();
                //this.className = 'dropzone';
                global.upload(e.dataTransfer.files);
                global.imagesOnProgress = true;
            };

            dropzone.ondragover = function(){
                this.className = 'dropzone dragover';
                return false;
            };
            dropzone.ondragleave = function(){
                this.className = 'dropzone';
                return true;
            };

            // translations
            this.trans = {
                __select: this.__('media.select'),
                __goToLibrary: this.__('media.goToLibrary'),
                __drop: this.__('media.drop'),
                __completed: this.__('media.completed'),
                __failed: this.__('media.failed'),
                __or: this.__('base.or'),
            };
        },
        data(){
            return{
                images: [],
                imagesOnProgress: false,
                countUploadedImages: 0,
                hasUploadErrors: false,
                selectedImages: ''
            }
        },
        methods:{
            // Upload files to server, store to database and handle file loading while
            // files are beeing uploaded
            upload(files){
                var global = this;
                // get all images and include them in a form data array
                var form = new FormData();
                for(var x =0; x < files.length; x++){
                    form.append(files[x].name, files[x]);
                    this.images.push({name: files[x].name, progress: 0, failed: false, url: '', msg: ''});
                }
                // the uploading process loading bar
                var interval = setInterval(function(){
                    var min = Math.ceil(1);
                    var max = Math.floor(10);
                    var random = parseInt((Math.random() * (max - min + 1)) + min);
                    var randomItem = parseInt((Math.random() * (global.images.length-1 - 0 + 1)) + 0);

                    if(global.images[randomItem].progress <= 90){
                        global.images[randomItem].progress += random;
                    }
                },100);

                // append in form data the album id and the formAlbum boolean variable that tells if we are making the upload from the album
                form.append('albumID', this.getAlbumID);
                form.append('fromAlbum', this.getLibrarySavedState.upload.fromAlbum);

                var result = [];
                this.$http.post(this.basePath+'/'+this.getAdminPrefix+'/media/json/store', form)
                    .then((resp) => {
                        result = resp.body;
                        clearInterval(interval);
                        // complete the loading bar after the result from the backend
                        for(var i=0; i < result.length; i++){
                            if(result[i].result_code == 'ok'){
                                global.images[this.countUploadedImages].progress = 100;
                                $("#"+this.countUploadedImages+" .completedMsg").css('display', 'block');
                            }else{
                                global.images[this.countUploadedImages].progress = 100;
                                global.images[this.countUploadedImages].failed = true;
                                global.images[this.countUploadedImages].msg = result[i].msg;
                                $("#"+this.countUploadedImages+" .failedMsg").css('display', 'block');
                                $("#"+this.countUploadedImages+" .completedMsg").css('display', 'none');
                                this.hasUploadErrors = true;
                            }
                            this.countUploadedImages++;
                        }

                        // change view to library or album
                        setTimeout(function(e){
                            if(!global.hasUploadErrors){
                                if(global.getLibrarySavedState.upload.fromAlbum){
                                    global.$store.commit('setPopUpActiveMediaView', 'albums');
                                    global.$store.commit('setLibrarySavedStateForUpload', { fromAlbum: false });
                                }else{
                                    global.$store.commit('setPopUpActiveMediaView', 'library');
                                }
                            }
                        }, 1000);
                    }, response => {
                        console.log("responseresponse",response);
                        let msg = '';
                        if(response.body !== undefined && response.body.message !== undefined){
                            msg = '<br>'+response.body.message;
                        }
                        new Noty({
                            type: "error",
                            layout: 'bottomLeft',
                            text: response.statusText+msg
                        }).show();
                    });
            },
            // trigger a click in the hidden file input wich is responsible to open the window to select files
            triggerClickChooseFiles(){
                $( "#selectedFiles" ).trigger( "click" );
            },
            // get the selected files and call upload method
            chooseFiles(event){
                var files = $("#selectedFiles")[0].files;
                this.upload(files);
                this.imagesOnProgress = true;
            },
            goToLibrary(){
                this.$store.commit('setPopUpActiveMediaView', 'library');
            }
        },
        computed: {
            getAlbumID(){
                return this.$store.getters.get_selected_album_ID;
            },
            getLibrarySavedState(){
                return this.$store.getters.get_library_saved_state;
            },
        }
    }
</script>
