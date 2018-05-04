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
                <div class="col-lg-8 col-md-8 col-xs-12">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>{{trans.__createFormTitle}}</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <!-- Loading component -->
                            <spinner v-if="spinner" :width="'40px'" :height="'40px'" :border="'10px'"></spinner>

                            <div class="" role="tabpanel" data-example-id="togglable-tabs" v-if="!spinner"><!-- TAB WRAPPER -->
                                <!-- TABS -->
                                <ul id="myTab" class="nav nav-tabs bar_tabs" v-if="Object.keys(languages).length > 1">
                                    <li :id="'tabBtn-'+lang.slug" class="langTabs" :class="{active: activeLang == lang.slug}"
                                        v-if="hasPermissionForLang(lang.languageID)"
                                        v-for="(lang, count) in languages"
                                        @click="activeLang = lang.slug">
                                        <a :href="'#tab_content'+ ++count" :id="'lang-tab'+count" role="tab" data-toggle="tab" aria-expanded="true">{{ lang.name }}</a>

                                    </li>
                                </ul>

                                <!-- TAB CONTENT -->
                                <div class="tabBody">
                                    <div class="tab-pane fade in"
                                         v-for="(lang, count) in languages"
                                         v-if="hasPermissionForLang(lang.languageID) && activeLang == lang.slug">

                                        <div class="form-group" :id="'form-group-title_'+lang.slug">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__title}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <input type="text" class="form-control" id="title" v-model="form.title[lang.slug]" @change="createSlug(form.title[lang.slug], lang.slug)">
                                                <div class="alert" v-if="StoreResponse.errors['title_'+ lang.slug]" v-for="error in StoreResponse.errors['title_'+ lang.slug]">{{ error }}</div>
                                            </div>
                                        </div>

                                        <div class="form-group" :id="'form-group-slug_'+lang.slug">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__slug}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <input type="text" class="form-control" id="slug" v-model="form.slug[lang.slug]" @dblclick="removeReadonly('slug')" readonly>
                                                <img :src="resourcesUrl('/images/loading.svg')" class="slugLoading">
                                                <div class="alert" v-if="StoreResponse.errors['slug_'+ lang.slug]" v-for="error in StoreResponse.errors['slug_'+ lang.slug]">{{ error }}</div>
                                            </div>
                                        </div>

                                        <div class="form-group" :id="'form-group-description_'+lang.slug">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__description}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12 froala-container">
                                                <froala :tag="'textarea'" id="description" :config="froalaCompactConfig" v-model="form.description[lang.slug]" class="froala" :id="'froala-description-'+lang.slug"></froala>
                                                <div class="alert" v-if="StoreResponse.errors['description_'+ lang.slug]" v-for="error in StoreResponse.errors['description_'+ lang.slug]">{{ error }}</div>
                                            </div>
                                        </div>

                                        <!-- Is Visible -->
                                        <div class="form-group" id="form-group-isVisible">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__visible}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12" >
                                                <div class="btn-group" data-toggle="buttons">
                                                    <label class="btn btn-default" :class="{active: form.isVisible[lang.slug]}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.isVisible[lang.slug] = true">
                                                        <input type="radio" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                                    </label>
                                                    <label class="btn btn-primary" :class="{active: !form.isVisible[lang.slug]}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.isVisible[lang.slug] = false">
                                                        <input type="radio" value="false"> {{trans.__false}}
                                                    </label>
                                                    <div class="alert" v-if="StoreResponse.errors['isVisible_'+ lang.slug]" v-for="error in StoreResponse.errors['isVisible_'+ lang.slug]">{{ error }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group" id="form-group-featuredImage">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__featuredImage}}</label>
                                            <div class="imagePrevContainer col-md-10 col-sm-10 col-xs-12">

                                                <div class="imageSingleThumb" v-if="mediaSelectedFiles['feature_image']">
                                                    <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile('feature_image', 0)"></i>
                                                    <img :src="generateUrl(constructUrl(mediaSelectedFiles['feature_image'][0]))">
                                                </div>

                                                <div class="clearfix"></div>

                                                <a class="btn btn-info" @click="openMediaForfeaturedImage('image', 'feature_image')" id="openMediaFeatureImage">
                                                    <span v-if="!mediaSelectedFiles['feature_image']">{{trans.__addImage}}</span>
                                                    <span v-if="mediaSelectedFiles['feature_image']">{{trans.__change}}</span>
                                                </a>

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
                                                    <component :is="panel" :dataContainer="pluginsData[panel]" :plugin="plugin" :panel="panel" :activeLang="activeLang" :languages="languages"></component>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- pluginsPanelsWrapper -->

                                        <!-- customFieldsWrapper -->
                                        <div class="customFieldsWrapper col-lg-12 col-md-12 col-xs-12" v-if="customFieldsGroups.length">

                                            <h5 style="margin-top:30px;">{{ trans.__customFieldsTitle }}</h5>

                                            <customFieldGroup
                                                    v-for="(group, index) in customFieldsGroups"
                                                    :group="group"
                                                    :key="index"
                                                    :lang="lang"
                                                    :trans="trans"
                                                    :childrenFieldsGroups="childrenFieldsGroups"
                                                    :customFieldValues="customFieldValues"></customFieldGroup>
                                        </div>
                                        <!-- customFieldsWrapper -->

                                    </div>

                                </div>

                            </div>

                        </div>
                    </div>
                </div>

                <!-- OPEN popup media window to choose files -->
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

                        <button type="button" class="btn btn-info" id="globalCancel" @click="redirect('category-list')">{{trans.__globalCancelBtn}}</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</template>
<script>
    import PopupMedia from '../media/Popup.vue'
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';
    import { customFields } from '../../mixins/customFields';
    import CustomFieldGroup from '../vendor/CustomFieldGroup.vue';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated, customFields],
        components:{'popup-media':PopupMedia, CustomFieldGroup},
        created() {
            // translations
            this.trans = {
                __headTitle: this.__('categories.add'),
                __createFormTitle: this.__('categories.createFormTitle'),
                __title: this.__('categories.form.title'),
                __slug: this.__('base.slug'),
                __addImage: this.__('media.addImage'),
                __change: this.__('media.change'),
                __description: this.__('base.description'),
                __globalSaveBtn: this.__('base.saveBtn'),
                __globalSaveAndCloseBtn: this.__('base.saveAndCloseBtn'),
                __globalSaveAndNewBtn: this.__('base.saveAndNewBtn'),
                __globalCancelBtn: this.__('base.cancelBtn'),
                __featuredImage: this.__('base.featuredImage'),
                __visible: this.__('base.visible'),
                __true: this.__('base.booleans.true'),
                __false: this.__('base.booleans.false'),
            };

            this.$store.commit('setSpinner', true);
            // List of all languages
            this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/language/get-all?order=isDefault&type=desc')
                .then((resp) => {
                    this.languages = resp.body.data;
                    this.resetForm();
                    // get plugin panels
                    this.getPluginsPanel(['category'], 'create');
                }).then( (resp) => {
                    // ajax request to get custom fields
                    this.$http.get(
                        this.basePath+'/'+
                        this.$route.params.adminPrefix+'/'+
                        this.$route.params.lang+
                        '/json/custom-fields/get-by-app/category/create/'+this.$route.params.id+'/none')
                        .then((resp) => {
                            this.loadCustomFields(resp.body, 'create');
                            this.$store.commit('setSpinner', false);
                        });
                });
        },
        data(){
            return{
                languages: [],
                pluginsPanels: [],
                pluginsData: {},
                selected: [],
                form: {
                    title: {},
                    slug: {},
                    description: {},
                    featuredImage: {},
                    isVisible: {},
                },
                defaultLangSlug: '',
                savedDropdownMenuVisible: false,
                customFieldsGroups: [],
                childrenFieldsGroups: [],
                customFieldOriginalStructure: {},
                customFieldValues: {},
            }
        },
        methods: {
            openMediaForfeaturedImage(format, inputName){
                this.$store.commit('setOpenMediaOptions', { multiple: false, has_multile_files: false, multipleInputs: false, format : format, inputName: inputName, langSlug: '', clear: false });
                this.$store.commit('setIsMediaOpen', true);
            },
            // store request in the database
            store(redirectChoice){
                this.$store.dispatch('openLoading');
                let featuredImage = this.mediaSelectedFiles['feature_image'];
                if(featuredImage !== undefined){
                    this.form.featuredImage = this.mediaSelectedFiles['feature_image'][0].mediaID;
                }else{
                    this.form.featuredImage = null;
                }

                // gets media files of custom fields and writes them to their v-models
                this.constructMediaForCustomFields();

                let request = {
                    form: this.form,
                    languages: this.languages,
                    postTypeID: this.$route.params.id,
                    redirect: redirectChoice,
                    pluginsData: this.pluginsData,
                    customFieldValues: this.customFieldValues,
                };
                this.$store.dispatch('store',{
                    data: request,
                    url: this.basePath+'/'+this.getAdminPrefix+"/json/category/store",
                    error: "Category could not be created. Please try again later."
                }).then((resp) => {
                    if(resp.code == 200){
                        if(redirectChoice == 'save'){
                            this.redirect('category-update',resp.id);
                        }else if(redirectChoice == 'close'){
                            this.redirect('category-list',this.$route.params.id);
                        }else if(redirectChoice == 'new'){
                            this.resetForm();
                        }else{
                            alert("Some error occurred");
                        }
                    }
                });
            },


            // returns form to basic with all empty fields
            resetForm(){
                let form = this.form;
                this.form = {};
                // create the object for the form data
                for(let k in this.languages){
                    form.title[this.languages[k].slug] = "";
                    form.slug[this.languages[k].slug] = "";
                    form.description[this.languages[k].slug] = "";
                    form.isVisible[this.languages[k].slug] = false;

                    if(this.languages[k].isDefault){
                        form.isVisible[this.languages[k].slug] = true;
                        this.activeLang = this.languages[k].slug;
                    }
                }
                form.featuredImage = null;
                this.$store.commit('setMediaSelectedFiles', {});

                this.form = form;
            },
            // this function activated when languages dropdown is changed
            languagesDropdownChanged(event){
                this.activeLangsSlug = event.target.value;
            },
            // this function is used to remove the selected images in custom fields
            deleteSelectedMediaFile(key, mediaID){
                var mediaArr = this.mediaSelectedFiles;
                delete mediaArr[key];
                this.$store.commit('setMediaSelectedFiles', "");
                this.$store.commit('setMediaSelectedFiles', mediaArr);
            },
            // call the createSlug function in vuejs
            createSlug(title, langSlug){
                this.form.slug[langSlug] = "";
                $("#slug").attr("readonly",true);
                this.$store.dispatch('createSlug', {title: title, url: this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/category/check-slug/'+this.$route.params.id+"/"})
                    .then((response) => {
                        this.form.slug[langSlug] = response;
                        $("#form-group-slug_"+this.activeLang+" input").val(response);
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
            }
        }
    }
</script>
