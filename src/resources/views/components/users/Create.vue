<!--suppress ALL -->
<template>
    <div class="componentsWs">

        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}} <small>{{trans.__listTitle}}</small></h3>
            </div>
        </div>
        <!-- TITLE END -->

        <div class="clearfix"></div>

        <div class="row">
            <div class="col-md-6 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>{{trans.__createFormTitle}}</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <br />

                        <!-- Loading component -->
                        <spinner v-if="spinner" :width="'40px'" :height="'40px'" :border="'10px'"></spinner>

                        <form class="form-horizontal form-label-left" id="storeUser" enctype="multipart/form-data" v-if="!spinner">
                            <div class="form-group" id="form-group-name">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__name}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" id="name" v-model="user.firstName">
                                    <div class="alert" v-if="StoreResponse.errors.firstName" v-for="error in StoreResponse.errors.firstName">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-lastname">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__lastname}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" v-model="user.lastName">
                                    <div class="alert" v-if="StoreResponse.errors.lastName" v-for="error in StoreResponse.errors.lastName">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-email">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__email}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="email" class="form-control" v-model="user.email">
                                    <div class="alert" v-if="StoreResponse.errors.email" v-for="error in StoreResponse.errors.email">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-password">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__password}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="password" class="form-control" v-model="user.password">
                                    <div class="alert" v-if="StoreResponse.errors.password" v-for="error in StoreResponse.errors.password">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-confpassword">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__confirmPassword}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="password" class="form-control" v-model="user.confpassword">
                                    <div class="alert" v-if="StoreResponse.errors.confpassword" v-for="error in StoreResponse.errors.confpassword">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-phone">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__phone}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" v-model="user.phone">
                                    <div class="alert" v-if="StoreResponse.errors.phone" v-for="error in StoreResponse.errors.phone">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-street">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__street}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" v-model="user.street">
                                    <div class="alert" v-if="StoreResponse.errors.street" v-for="error in StoreResponse.errors.street">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-country">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__country}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" v-model="user.country">
                                    <div class="alert" v-if="StoreResponse.errors.country" v-for="error in StoreResponse.errors.country">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-groups">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__groups}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <multiselect
                                            v-model="user.groups"
                                            :options="groupsList"
                                            :multiple="true"
                                            :close-on-select="true"
                                            :clear-on-select="false"
                                            :hide-selected="true"
                                            placeholder="Pick some"
                                            label="name"
                                            track-by="name"></multiselect>
                                    <div class="alert" v-if="StoreResponse.errors.groups" v-for="error in StoreResponse.errors.groups">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-featuredImage">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__featuredImage}}</label>

                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <div class="imageContainer">
                                        <img v-if="mediaSelectedFiles['featuredImage']" :src="constructMediaUrl(mediaSelectedFiles['featuredImage'][0])" class="featuredImagePreview">
                                        <br>
                                        <a class="btn btn-info" v-if="!mediaSelectedFiles['featuredImage']" @click="openMedia" id="openMediaFeatureImage">{{trans.__select}}</a>
                                        <a class="btn btn-info" v-if="mediaSelectedFiles['featuredImage']" @click="openMedia">{{trans.__change}}</a>
                                        <a class="btn btn-danger" v-if="mediaSelectedFiles['featuredImage']" @click="removeFeatureImage">{{trans.__removeImage}}</a>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xs-12 colons" v-if="!spinner">

                <!-- MULTILANGUAGE PANEL -->
                <div class="x_panel">
                    <div class="x_content">
                        <div class=""><!-- TAB WRAPPER -->
                            <!-- TABS -->
                            <ul class="nav nav-tabs bar_tabs" v-if="Object.keys(languages).length > 1">
                                <li role="presentation" class="langTabs" :class="{active: activeLang == lang.slug}"
                                    :id="'tabBtn-'+lang.slug"
                                    v-for="(lang, count) in languages"
                                    :key="count"
                                    :data-lang="lang.slug"
                                    @click="activeLang = lang.slug">

                                    <a :href="'#tab_content'+ ++count" :id="'lang-tab'+count" role="tab" data-toggle="tab">{{ lang.name }}</a>

                                </li>
                            </ul>

                            <!-- TAB CONTENT -->
                            <div class="tabBody">
                                <div class="tab-pane fade in"
                                     v-for="(lang, count) in languages"
                                     :key="count"
                                     v-if="activeLang == lang.slug">

                                    <div class="form-group" :id="'form-group-about_'+ lang.slug">
                                        <label class="control-label col-md-12 col-sm-12 col-xs-12">{{trans.__about}}</label>
                                        <div class="col-md-12 col-sm-12 col-xs-12 froala-container">
                                            <froala :tag="'textarea'" :config="froalaBasicConfig" v-model="user.about[lang.slug]" class="froala" :id="'froala-about-'+lang.slug"></froala>
                                            <div class="alert" v-if="StoreResponse.errors['about_'+lang.slug]" v-for="error in StoreResponse.errors.groups">{{ error }}</div>
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

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- END MULTILANGUAGE PANEL -->
            </div>

            <!-- POPUP media component -->
            <transition name="slide-fade">
                <popup-media v-if="isMediaOpen"></popup-media>
            </transition>

            <div class="mainButtonsContainer" v-if="!spinner">
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

                    <button class="btn btn-info" id="globalCancel" @click="redirect('user-list')">{{trans.__globalCancelBtn}}</button>
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
            // translations
            this.trans = {
                __title: this.__('user.add'),
                __createFormTitle: this.__('user.createFormTitle'),
                __name: this.__('base.name'),
                __about: this.__('base.about'),
                __lastname: this.__('user.form.lastname'),
                __email: this.__('base.email'),
                __password: this.__('user.form.password'),
                __confirmPassword: this.__('user.form.confirmPassword'),
                __phone: this.__('user.form.phone'),
                __street: this.__('user.form.street'),
                __country: this.__('user.form.country'),
                __groups: this.__('user.form.groups'),
                __submitBtn: this.__('base.submitBtn'),
                __cancelBtn: this.__('base.cancelBtn'),
                __featuredImage: this.__('base.featuredImage'),
                __select: this.__('user.form.openMedia.select'),
                __change: this.__('user.form.openMedia.change'),
                __removeImage: this.__('user.form.openMedia.removeImage'),
                __globalSaveBtn: this.__('base.saveBtn'),
                __globalSaveAndCloseBtn: this.__('base.saveAndCloseBtn'),
                __globalSaveAndNewBtn: this.__('base.saveAndNewBtn'),
                __globalCancelBtn: this.__('base.cancelBtn'),
            };

            this.languages = this.getLanguages;
            // make a key for each language in the about object
            for(let k in this.languages){
                if(this.languages[k].isDefault){
                    this.activeLang = this.languages[k].slug;
                }
                this.user.about[this.languages[k].slug] = '';
            }

            this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/user/get-groups')
                .then((resp) => {
                    this.groupsList = resp.body;
                }).then((resp) => {
                    this.getPluginsPanel(['users'], 'create');
                    this.$store.commit('setSpinner', false);
                });
        },
        data(){
            return{
                groupsList: [],
                selected : [],
                languages: '',
                pluginsPanels: [],
                pluginsData: {},
                user:{
                    firstName: '',
                    lastName: '',
                    email: '',
                    password: '',
                    confpassword: '',
                    phone: '',
                    street: '',
                    country: '',
                    groups: [],
                    profileImageID: '',
                    about: {},
                },
                savedDropdownMenuVisible: false,
            }
        },
        methods: {
            store(redirectChoice){
                if(this.mediaSelectedFiles['featuredImage'] !== undefined && this.mediaSelectedFiles['featuredImage'][0] !== undefined){
                    this.user.profileImageID = this.mediaSelectedFiles['featuredImage'][0].mediaID;
                }
                this.$store.dispatch('openLoading');
                this.$store.dispatch('store',{
                    data: {
                        user: this.user,
                        redirect: redirectChoice,
                    },
                    url: this.basePath+'/'+this.getAdminPrefix+"/json/user/store",
                    error: "User could not be created. Please try again later."
                }).then((resp) => {
                    if(resp.code == 200){
                        this.onStoreBtnClicked('user-',redirectChoice, resp.id);
                    }
                });
            },
            openMedia(){
                this.$store.commit('setOpenMediaOptions', { multiple: false, has_multile_files: false, multipleInputs: false, format : 'image', inputName: 'featuredImage', langSlug: '', clear: true });
                this.$store.commit('setIsMediaOpen', true);
            },
            // remove feature image
            removeFeatureImage(){
                this.$store.commit('removeSpecificMediaKey', 'featuredImage');
                this.user.profileImageID = "";
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
