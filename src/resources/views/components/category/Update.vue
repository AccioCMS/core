<template>
    <div class="componentsWs" dusk="categoryUpdateComponent">
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
                            <h2>{{trans.__updateFormTitle}}</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <!-- Loading component -->
                            <spinner v-if="spinner" :width="'40px'" :height="'40px'" :border="'10px'"></spinner>

                            <div role="tabpanel" data-example-id="togglable-tabs" v-if="!spinner"><!-- TAB WRAPPER -->
                                <!-- TABS -->
                                <ul id="myTab" class="nav nav-tabs bar_tabs" role="tablist" v-if="Object.keys(languages).length > 1">
                                    <li role="presentation" class="langTabs" :class="{active: activeLang == lang.slug}"
                                        v-if="hasPermissionForLang(lang.languageID)"
                                        :id="'tabBtn-'+lang.slug"
                                        v-for="(lang, count) in form"
                                        :key="count"
                                        :data-lang="lang.slug"
                                        @click="activeLang = lang.slug">

                                        <a :href="'#tab_content'+ ++count" :id="'lang-tab'+count" role="tab" data-toggle="tab" aria-expanded="true">{{ lang.name }}</a>

                                    </li>
                                </ul>
                                <!-- TAB CONTENT -->
                                <div class="tabBody">
                                    <div class="tab-pane fade in"
                                         :id="'tab_content'+ count"
                                         v-for="(lang, count) in form"
                                         :key="count"
                                         v-if="hasPermissionForLang(lang.languageID) && activeLang == lang.slug">

                                        <div class="form-group" :id="'form-group-title_'+lang.slug">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__title}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <input type="text" class="form-control" id="title" v-model="lang.formdata.title">
                                                <div class="alert" v-if="StoreResponse.errors['title_'+ lang.slug]" v-for="error in StoreResponse.errors['title_'+ lang.slug]">{{ error }}</div>
                                            </div>
                                        </div>

                                        <div class="form-group" :id="'form-group-slug_'+lang.slug">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__slug}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <input type="text" class="form-control" :id="'slug_' + count" v-model="lang.formdata.slug" @dblclick="removeReadonly('slug_' + count)" readonly>
                                                <img :src="resourcesUrl('/images/loading.svg')" class="slugLoading">
                                                <div class="alert" v-if="StoreResponse.errors['slug_'+ lang.slug]" v-for="error in StoreResponse.errors['slug_'+ lang.slug]">{{ error }}</div>
                                            </div>
                                        </div>

                                        <div class="form-group" :id="'form-group-description_'+lang.slug">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__description}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12 froala-container">
                                                <froala :tag="'textarea'" id="description" :config="froalaCompactConfig" v-model="lang.formdata.description" class="froala" :id="'froala-description-'+lang.slug"></froala>
                                                <div class="alert" v-if="StoreResponse.errors['description_'+ lang.slug]" v-for="error in StoreResponse.errors['description_'+ lang.slug]">{{ error }}</div>
                                            </div>
                                        </div>

                                        <!-- Is Visible -->
                                        <div class="form-group" id="form-group-isVisible">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__visible}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12" >
                                                <div class="btn-group" data-toggle="buttons">
                                                    <label class="btn btn-default" :class="{active: lang.formdata.isVisible}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="lang.formdata.isVisible = true">
                                                        <input type="radio" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                                    </label>
                                                    <label class="btn btn-primary" :class="{active: !lang.formdata.isVisible}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="lang.formdata.isVisible = false">
                                                        <input type="radio" value="false"> {{trans.__false}}
                                                    </label>
                                                    <div class="alert" v-if="StoreResponse.errors['isVisible_'+ lang.slug]" v-for="error in StoreResponse.errors['isVisible_'+ lang.slug]">{{ error }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group" id="form-group-featuredImage">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__featuredImage}}</label>
                                            <div class="imagePrevContainer col-md-10 col-sm-10 col-xs-12">

                                                <div v-if="mediaSelectedFiles['feature_image']">
                                                    <div class="imageSingleThumb" v-if="mediaSelectedFiles['feature_image']">
                                                        <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile('feature_image', mediaSelectedFiles['feature_image'])"></i>
                                                        <img :src="generateUrl(constructUrl(mediaSelectedFiles['feature_image'][0]))">
                                                    </div>
                                                </div>

                                                <div class="clearfix"></div>

                                                <a class="btn btn-info" @click="openMediaForfeaturedImage('image', 'feature_image')" id="openMediaFeatureImage">
                                                    <span v-if="!mediaSelectedFiles['feature_image'] || mediaSelectedFiles['feature_image'] === null">{{trans.__addImage}}</span>
                                                    <span v-if="mediaSelectedFiles['feature_image'] && mediaSelectedFiles['feature_image'] !== null">{{trans.__change}}</span>
                                                </a>
                                            </div>
                                        </div>

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

                        <button type="button" class="btn btn-info" id="globalCancel" @click="redirect('category-list',postTypeID)">{{trans.__globalCancelBtn}}</button>
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
        mounted() {
            // translations
            this.trans = {
                __headTitle: this.__('categories.updateTitle'),
                __updateFormTitle: this.__('categories.updateFormTitle'),
                __title: this.__('categories.form.title'),
                __slug: this.__('base.slug'),
                __addImage: this.__('media.addImage'),
                __change: this.__('media.change'),
                __description: this.__('base.description'),
                __globalUpdateBtn: this.__('base.updateBtn'),
                __globalUpdateAndCloseBtn: this.__('base.updateAndCloseBtn'),
                __globalUpdateAndNewBtn: this.__('base.updateAndNewBtn'),
                __globalCancelBtn: this.__('base.cancelBtn'),
                __visible: this.__('base.visible'),
                __true: this.__('base.booleans.true'),
                __false: this.__('base.booleans.false'),
            };

            this.$store.commit('setSpinner', true);
            this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/category/details/'+this.$route.params.id)
                .then((resp) => {
                    let languages = resp.body.languages; // language list
                    this.languages = languages;
                    let data = resp.body.details; // category data
                    this.postTypeID = data.postTypeID;
                    // create the form data for the default page fields for each language
                    for(let k in languages){
                        let imageFile = resp.body.media["feature_image__lang__"+languages[k].slug];
                        if(imageFile !== undefined && imageFile[0] !== null){ // if feature image is set
                            imageFile = imageFile[0].mediaID;
                        }else{ // if feature is not set make it 0
                            imageFile = 0;
                        }

                        languages[k].formdata = {
                            title: '',
                            slug: '',
                            description: '',
                            featuredImage: imageFile,
                            isVisible: true,
                        };

                        if(languages[k].isDefault){
                            this.defaultLangSlug = languages[k].slug;
                            this.activeLang = languages[k].slug;
                        }
                    }

                    this.form = languages; // populate form variable
                    for(let k in this.form){ // loop throw form data to populate the values
                        let currentLangSlug = this.form[k].slug;
                        this.form[k].formdata.title = data.title[currentLangSlug];
                        this.form[k].formdata.slug = data.slug[currentLangSlug];
                        this.form[k].formdata.description = data.description[currentLangSlug];
                        this.form[k].formdata.isVisible = data.isVisible[currentLangSlug];
                    }
                    this.$store.commit('setMediaSelectedFiles', resp.body.media); // set the media files
                    // get plugin panels
                    this.getPluginsPanel(['category'], 'update');
                    // load the custom fields
                    this.loadCustomFields(resp.body.customFieldsGroups, 'update');
                    // load the values of the custom fields
                    this.pupulateCustomFieldsValues(resp.body.customFieldsValues);

                    this.$store.commit('setSpinner', false);
                });
        },
        data(){
            return{
                customFieldsGroups: [],
                childrenFieldsGroups: [],
                customFieldOriginalStructure: {},
                customFieldValues: {},
                pluginsPanels: [],
                pluginsData: {},
                languages: [],
                selected : [],
                form: '',
                fields: '',
                defaultLangSlug: '',
                savedDropdownMenuVisible: false,
                postTypeID: '',
            }
        },
        methods: {
            // use to open the media popup
            openMediaForfeaturedImage(format, inputName){
                // media popup options
                this.$store.commit('setOpenMediaOptions', { multiple: false, has_multile_files: false, multipleInputs: false, format : 'image', inputName: inputName, langSlug: '', clear: false });
                this.$store.commit('setIsMediaOpen', true);
            },
            // remove feature image
            removeMediaFiles(){
                this.$store.commit('setMediaSelectedFiles', "");
            },
            constructUrl(image){
                let url = "";
                if(image.type == "image"){
                    url = "/"+image.fileDirectory + "/200x200/" + image.filename;
                }else if(image.type == "document"){
                    url = this.documentIconUrl;
                }else if(image.type == "video"){
                    url = this.videoIconUrl;
                }else if(image.type == "audio"){
                    url = this.audioIconUrl;
                }
                return url;
            },

            // store request in the database
            store(redirectChoice){
                this.$store.dispatch('openLoading');
                let featuredImage = this.mediaSelectedFiles['feature_image'];
                if(featuredImage !== undefined){
                    featuredImage = this.mediaSelectedFiles['feature_image'][0].mediaID;
                }else{
                    featuredImage = null;
                }

                // gets media files of custom fields and writes them to their v-models
                this.constructMediaForCustomFields();

                let request = {
                    id: this.$route.params.id,
                    form: this.form,
                    featuredImage: featuredImage,
                    files: this.mediaSelectedFiles,
                    redirect: redirectChoice,
                    pluginsData: this.pluginsData,
                    customFieldValues: this.customFieldValues,
                };

                this.$store.dispatch('store',{
                    data: request,
                    url: this.basePath+'/'+this.getAdminPrefix+"/json/category/storeUpdate",
                    error: "Category could not be updated. Please try again later."
                }).then((resp) => {
                    if(resp.code == 200){
                        if(redirectChoice == 'close'){
                            this.redirect('category-list',this.postTypeID);
                        }else if(redirectChoice == 'new'){
                            this.redirect('category-create',this.postTypeID);
                        }
                    }
                });
            },
            changeVisibility(option, index){
                this.form[index].formdata.visible = option;
            },
            isActive(count){
                if(count == 0){
                    return "active";
                }
                return "inactive";
            },
            // this function activated when languages dropdown is changed
            languagesDropdownChanged(event){
                this.activeLangsSlug = event.target.value;
            },
            // this function is used to remove the selected images in custom fields
            deleteSelectedMediaFile(key, mediaID){
                var mediaArr = this.mediaSelectedFiles;
                for(var k in mediaArr[key]){
                    if(mediaArr[key][k].mediaID == mediaID){
                        console.log(mediaArr[key][k].mediaID);
                        mediaArr[key].splice(mediaArr[key][k], 1);
                    }
                }
                this.$store.commit('setMediaSelectedFiles', "");
                this.$store.commit('setMediaSelectedFiles', mediaArr);
            },
            change(option, index){
                this.fields[index].value = option;
            },
            removeReadonly(id){
                $("#"+id).attr("readonly",false);
            },

            // load the data of custom fields
            loadCustomFields(customFieldsGroups, type){
                var customFieldGroupFinal = [];
                // loop throw custom field group
                for(let groupKey in customFieldsGroups){
                    var groupSlug = customFieldsGroups[groupKey].slug;

                    var tmpFields = [];
                    for(var fieldKey in customFieldsGroups[groupKey].fields){
                        let field = customFieldsGroups[groupKey].fields[fieldKey];

                        if(!field.parentID){
                            tmpFields.push(field);
                            // make original fields structure with children
                            this.customFieldOriginalStructure[groupSlug+'__'+field.slug] = field;
                            this.customFieldOriginalStructure[groupSlug+'__'+field.slug].subFields = [];

                            if(field.isTranslatable){
                                this.customFieldValues[groupSlug+'__'+field.slug] = {};
                                var tmpLangKeys = {};
                                for(let langKey in this.languages){
                                    this.customFieldValues[groupSlug+'__'+field.slug][this.languages[langKey].slug] = [];
                                    tmpLangKeys[this.languages[langKey].slug] = [];
                                }

                                // populate sub field keys for each repeater
                                if(field.type == 'repeater'){
                                    this.$store.commit('addSubCustomFieldGroup', {key: groupSlug+'__'+field.slug, value: tmpLangKeys});
                                }

                            }else{
                                this.customFieldValues[groupSlug+'__'+field.slug] = [];

                                // populate sub field keys for each repeater
                                if(field.type == 'repeater'){
                                    this.$store.commit('addSubCustomFieldGroup', {key: groupSlug+'__'+field.slug, value: []});
                                }
                            }
                        }else{
                            if(this.childrenFieldsGroups[field.parentID] === undefined){
                                this.childrenFieldsGroups[field.parentID] = [];
                            }
                            this.childrenFieldsGroups[field.parentID].push(field);

                            // make original fields structure with children
                            for(let fKey in this.customFieldOriginalStructure){
                                if(this.customFieldOriginalStructure[fKey].customFieldID == field.parentID){
                                    this.customFieldOriginalStructure[groupSlug+'__'+this.customFieldOriginalStructure[fKey].slug].subFields[groupSlug+'__'+field.slug] = field;
                                }
                            }
                        }
                    }
                    customFieldsGroups[groupKey].fields = tmpFields;
                    customFieldGroupFinal.push(customFieldsGroups[groupKey]);
                }
                this.customFieldsGroups = customFieldGroupFinal;
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
