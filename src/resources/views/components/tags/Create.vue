<template>
    <div class="componentsWs">
        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__headTitle}}</h3>
            </div>
        </div>
        <!-- TITLE END -->

        <div class="clearfix"></div>

        <div class="row">
            <form class="form-horizontal form-label-left" id="store" enctype="multipart/form-data">
                <div class="col-md-8 col-sm-8 col-xs-12">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>{{trans.__createFormTitle}}</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <br />

                            <div class="form-group" id="form-group-title">
                                <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__title}}</label>
                                <div class="col-md-10 col-sm-10 col-xs-12">
                                    <input type="text" class="form-control" id="title" v-model="form.title" @change="createSlug(form.title)">
                                    <div class="alert" v-if="StoreResponse.errors.title" v-for="error in StoreResponse.errors.title">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-slug">
                                <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__slug}}</label>
                                <div class="col-md-10 col-sm-10 col-xs-12">
                                    <input type="text" class="form-control" id="slug" v-model="form.slug" @dblclick="removeReadonly('slug')" readonly>
                                    <div class="alert" v-if="StoreResponse.errors.slug" v-for="error in StoreResponse.errors.slug">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-description">
                                <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__description}}</label>
                                <div class="col-md-10 col-sm-10 col-xs-12 froala-container">
                                    <froala :tag="'textarea'" :config="froalaBasicConfig" v-model="form.description"  class="froala" id="froala-description"></froala>
                                    <div class="alert" v-if="StoreResponse.errors.description" v-for="error in StoreResponse.errors.description">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-featuredImage">
                                <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__featuredImage}}</label>
                                <div class="imageContainer col-md-10 col-sm-10 col-xs-12">
                                    <img v-if="mediaSelectedFiles['featuredImage']" :src="constructMediaUrl(mediaSelectedFiles['featuredImage'][0])" class="featuredImagePreview">
                                    <br>
                                    <a class="btn btn-info" v-if="!mediaSelectedFiles['featuredImage']" id="openMediaFeatureImage" @click="openMedia">{{trans.__addImage}}</a>
                                    <a class="btn btn-info" v-if="mediaSelectedFiles['featuredImage']" @click="openMedia">{{trans.__change}}</a>
                                    <a class="btn btn-danger" v-if="mediaSelectedFiles['featuredImage']" @click="removeFeatureImage">{{trans.__remove}}</a>
                                </div>
                            </div>

                            <!-- pluginsPanelsWrapper -->
                            <div class="pluginsPanelsWrapper col-lg-12 col-md-12 col-xs-12" v-if="pluginsPanels.length">
                                <h5 style="margin-top: 30px;">{{ trans.__pluginAppName }}</h5>
                                <div class="panelContainer" v-for="(plugin, panelIndex) in pluginsPanels" v-if="plugin.panels.length">
                                    <div class="pluginHeader">
                                        <h4>{{ plugin.name }}</h4>
                                    </div>
                                    <div v-for="(panel, panelIndex) in plugin.panels">
                                        <component :is="panel" :dataContainer="pluginsData[panel]" :plugin="plugin" :panel="panel"></component>
                                    </div>
                                </div>
                            </div>
                            <!-- pluginsPanelsWrapper -->

                        </div>
                    </div>
                </div>

            </form>

            <!-- POPUP media window -->
            <transition name="slide-fade">
                <popup-media v-if="isMediaOpen"></popup-media>
            </transition>

            <div class="mainButtonsContainer">
                <div class="row">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" id="globalSaveBtn" @click="store('save')">{{trans.__globalSaveBtn}}</button>
                        <button type="button" class="btn btn-primary dropdown-toggle" @click="savedDropdownMenuVisible = !savedDropdownMenuVisible">
                            <i class="fa fa-caret-up"></i>
                        </button>
                        <ul class="savedDropdownMenu" v-if="savedDropdownMenuVisible">
                            <li><a style="cursor:pointer" @click="store('close')">{{trans.__globalSaveAndCloseBtn}}</a></li>
                            <li><a style="cursor:pointer" @click="store('new')">{{trans.__globalSaveAndNewBtn}}</a></li>
                        </ul>
                    </div>

                    <button type="button" class="btn btn-info" id="globalCancel" @click="redirect('tag-list')">{{trans.__globalCancelBtn}}</button>
                </div>
            </div>

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
        mounted() {
            this.form.postTypeID = this.getID;

            // translations
            this.trans = {
                __createFormTitle: this.__('tags.createFormTitle'),
                __title: this.__('tags.form.title'),
                __slug: this.__('base.slug'),
                __addImage: this.__('media.addImage'),
                __change: this.__('media.change'),
                __description: this.__('base.description'),
                __remove: this.__('base.remove'),
                __featuredImage: this.__('base.featuredImage'),
                __globalSaveBtn: this.__('base.saveBtn'),
                __globalSaveAndCloseBtn: this.__('base.saveAndCloseBtn'),
                __globalSaveAndNewBtn: this.__('base.saveAndNewBtn'),
                __globalCancelBtn: this.__('base.cancelBtn'),
            };

            // get plugin panels
            this.getPluginsPanel(['tags'], 'create');
        },
        data(){
            return{
                selected: [],
                pluginsPanels: [],
                pluginsData: {},
                form:{
                    postTypeID: '',
                    title: '',
                    visible: true,
                    slug: '',
                    description: '',
                    featuredImage: 0,
                    redirect: '',
                },
                savedDropdownMenuVisible: false,
            }
        },
        methods: {
            openMedia(){
                this.$store.commit('setOpenMediaOptions', { multiple: false, has_multile_files: false, multipleInputs: false, format : 'image', inputName: 'featuredImage', langSlug: '', clear: true });
                this.$store.commit('setIsMediaOpen', true);
            },
            // remove feature image
            removeFeatureImage(){
                this.$store.commit('removeSpecificMediaKey', 'featuredImage');
                this.form.featuredImage = 0;
            },
            // store tag
            store(redirectChoice){
                // set featured image in form
                if(this.mediaSelectedFiles['featuredImage'] !== undefined && this.mediaSelectedFiles['featuredImage'][0] !== undefined){
                    this.form.featuredImage = this.mediaSelectedFiles['featuredImage'][0].mediaID;
                }

                this.$store.dispatch('openLoading');
                this.form.redirect = redirectChoice;
                this.$store.dispatch('store',{
                    data: {
                        formData: this.form,
                        pluginsData: this.pluginsData
                    },
                    url: this.basePath+'/'+this.getAdminPrefix+"/json/tags/store",
                    error: "Tag could not be created. Please try again later."
                }).then((resp) => {
                    if(resp.code == 200){
                        if(redirectChoice == 'save'){
                            this.redirect('tag-update',resp.id);
                        }else if(redirectChoice == 'close'){
                            this.redirect('tag-list',this.$route.params.id);
                        }else if(redirectChoice == 'new'){
                            this.redirect('tag-create',this.$route.params.id);
                        }else{
                            alert("Some error occurred");
                        }
                    }
                });
            },
            // this function enables and disables the multi options textarea depending in the selected value
            dispatchAction(event, index){
                var type = event.inputType;
                if(type == "checkbox" || type == "radio" || type == "dropdown"){
                    $("#option"+index).show();
                }else{
                    $("#option"+index).hide();
                }
            },
            // call the createSlug function in vuejs
            createSlug(title, index){
                this.form.slug = "";
                $("#slug").attr("readonly",true);
                this.$store.dispatch('createSlug', {title: title, url: this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/tags/check-slug/'+this.getID+'/'})
                    .then((response) => {
                        this.form.slug = response;
                    });
            },
            // remove readonly attr of a specific input
            removeReadonly(id){
                $("#"+id).attr("readonly",false);
            },
        },
        computed: {
            isMediaOpen(){
                // return if media popup is open (true or false)
                return this.$store.getters.get_is_media_open;
            },
            mediaSelectedFiles(){
                // return when user chose files form media
                return this.$store.getters.get_media_selected_files;
            },
        },

    }
</script>