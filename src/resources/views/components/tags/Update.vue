<template>
    <div class="componentsWs" dusk="tagUpdateComponent">
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
                            <h2>{{trans.__updateFormTitle}}</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <br />

                            <!-- Loading component -->
                            <spinner v-if="spinner" :width="'40px'" :height="'40px'" :border="'10px'"></spinner>

                            <form v-if="!spinner">
                                <div class="form-group" id="form-group-title">
                                    <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__title}}</label>
                                    <div class="col-md-10 col-sm-10 col-xs-12">
                                        <input type="text" class="form-control" id="title" v-model="form.title">
                                        <div class="alert" v-if="StoreResponse.errors.title" v-for="error in StoreResponse.errors.title">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group"  id="form-group-slug">
                                    <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__slug}}</label>
                                    <div class="col-md-10 col-sm-10 col-xs-12">
                                        <input type="text" class="form-control" id="slug" v-model="form.slug" @dblclick="removeReadonly('slug')" readonly>
                                        <div class="alert" v-if="StoreResponse.errors.slug" v-for="error in StoreResponse.errors.slug">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group"  id="form-group-description">
                                    <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__description}}</label>
                                    <div class="col-md-10 col-sm-10 col-xs-12 froala-container">
                                        <froala :tag="'textarea'" :config="froalaBasicConfig" v-model="form.description" class="froala" id="froala-description"></froala>
                                        <div class="alert" v-if="StoreResponse.errors.description" v-for="error in StoreResponse.errors.description">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group" id="form-group-featuredImage">
                                    <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__featuredImage}}</label>
                                    <div class="imageContainer col-md-10 col-sm-10 col-xs-12">
                                        <img v-if="mediaSelectedFiles['featuredImage']" :src="constructMediaUrl(mediaSelectedFiles['featuredImage'][0])" class="featuredImagePreview">
                                        <br>
                                        <a class="btn btn-info" v-if="!mediaSelectedFiles['featuredImage']" @click="openMedia" id="openMediaFeatureImage">{{trans.__addImage}}</a>
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

                            </form>

                        </div>
                    </div>
                </div>

                <!-- Media popup component -->
                <transition name="slide-fade">
                    <popup-media v-if="isMediaOpen"></popup-media>
                </transition>

                <div class="mainButtonsContainer" v-if="!spinner">
                    <div class="row">
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary" id="globalSaveBtn" @click="store('save')">{{trans.__globalUpdateBtn}}</button>
                            <button type="button" class="btn btn-primary dropdown-toggle" @click="savedDropdownMenuVisible = !savedDropdownMenuVisible">
                                <i class="fa fa-caret-up"></i>
                            </button>
                            <ul class="savedDropdownMenu" v-if="savedDropdownMenuVisible">
                                <li><a style="cursor:pointer" @click="store('close')">{{trans.__globalUpdateAndCloseBtn}}</a></li>
                                <li><a style="cursor:pointer" @click="store('new')">{{trans.__globalUpdateAndNewBtn}}</a></li>
                            </ul>
                        </div>

                        <button type="button" class="btn btn-info" id="globalCancel" @click="redirect('tag-list',form.postTypeID)">{{trans.__globalCancelBtn}}</button>
                    </div>
                </div>

            </form>

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
        created(){
            // translations
            this.trans = {
                __updateFormTitle: this.__('tags.updateFormTitle'),
                __title: this.__('tags.form.title'),
                __slug: this.__('base.slug'),
                __addImage: this.__('media.addImage'),
                __change: this.__('media.change'),
                __description: this.__('base.description'),
                __remove: this.__('base.remove'),
                __featuredImage: this.__('base.featuredImage'),
                __globalUpdateBtn: this.__('base.updateBtn'),
                __globalUpdateAndCloseBtn: this.__('base.updateAndCloseBtn'),
                __globalUpdateAndNewBtn: this.__('base.updateAndNewBtn'),
                __globalCancelBtn: this.__('base.cancelBtn'),
            };
        },
        mounted() {
            this.$store.commit('setSpinner', true);
            // get language information
            var promise = this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/tags/details/'+this.$route.params.id)
                .then((resp) => {
                    this.form.title = resp.body.details.title;
                    this.form.id = resp.body.details.tagID;
                    this.form.description = resp.body.details.description;
                    this.form.slug = resp.body.details.slug;
                    this.form.postTypeID = resp.body.details.postTypeID;

                    if(resp.body.featuredImage != null){
                        this.$store.commit('setMediaSelectedFiles', {'featuredImage':[resp.body.featuredImage]});
                        this.form.featuredImage = resp.body.featuredImage.mediaID;
                    }
                });

            // after ajax requests are done
            Promise.all([promise]).then(([v1]) => {
                // get plugin panels
                this.getPluginsPanel(['tags'], 'update');
                this.$store.commit('setSpinner', false);
            });
        },
        data(){
            return{
                has_multile_files: false,
                selected : [],
                savedDropdownMenuVisible: false,
                pluginsPanels: [],
                pluginsData: {},
                form:{
                    id: '',
                    title: '',
                    visible: true,
                    slug: '',
                    description: '',
                    featuredImage: '',
                    postTypeID: '',
                    redirect: '',
                },
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
                }else{
                    this.form.featuredImage = 0;
                }

                this.$store.dispatch('openLoading');
                this.form.redirect = redirectChoice;

                this.$store.dispatch('store',{
                    data: {
                        formData: this.form,
                        pluginsData: this.pluginsData
                    },
                    url: this.basePath+'/'+this.getAdminPrefix+"/json/tags/storeUpdate",
                    error: "Tag could not be updated. Please try again later."
                }).then((resp) => {
                    if(resp.code == 200){
                        if(redirectChoice == 'close'){
                            this.redirect('tag-list',this.form.postTypeID);
                        }else if(redirectChoice == 'new'){
                            this.redirect('tag-create',this.form.postTypeID);
                        }
                    }
                });
            },
            removeReadonly(id){
                $("#"+id).attr("readonly",false);
            }
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
        }

    }
</script>