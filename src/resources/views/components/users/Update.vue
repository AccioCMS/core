<template>
    <div class="componentsWs" dusk="userUpdateComponent">

        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}} <small>{{trans.__listTitle}}</small></h3>
            </div>
        </div>
        <!-- TITLE END -->

        <div class="clearfix"></div>

        <div class="row">
            <div class="col-md-6 col-xs-6">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>{{trans.__updateFormTitle}}</h2>
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

                            <div class="form-group" id="form-group-active">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__active}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">

                                    <div id="hasTags" class="btn-group" data-toggle="buttons">
                                        <label :class="{'btn btn-default':true, 'active': user.isActive}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="user.isActive == true">
                                            <input type="radio" name="visible" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                        </label>
                                        <label :class="{'btn btn-primary':true, 'active': !user.isActive}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="user.isActive == false">
                                            <input type="radio" name="visible" value="false"> {{trans.__false}}
                                        </label>
                                    </div>

                                    <div class="alert" v-if="StoreResponse.errors.isActive" v-for="error in StoreResponse.errors.isActive">{{ error }}</div>
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
                                        <a class="btn btn-info" v-if="mediaSelectedFiles['featuredImage']" @click="openMedia" id="openMediaChangeFeatureImage">{{trans.__change}}</a>
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
                        <div class="" role="tabpanel" data-example-id="togglable-tabs"><!-- TAB WRAPPER -->
                            <!-- TABS -->
                            <ul class="nav nav-tabs bar_tabs" v-if="Object.keys(languages).length > 1">
                                <li role="presentation" class="langTabs" :class="{active: activeLang == lang.slug}"
                                    :id="'tabBtn-'+lang.slug"
                                    v-for="(lang, count) in languages"
                                    :key="count"
                                    :data-lang="lang.slug"
                                    @click="activeLang = lang.slug">

                                    <a :href="'#tab_content'+ ++count" :id="'lang-tab'+count" role="tab" data-toggle="tab" aria-expanded="true">{{ lang.name }}</a>

                                </li>
                            </ul>

                            <!-- TAB CONTENT -->
                            <div class="tabBody">
                                <div class="tab-pane fade in"
                                     v-for="(lang, count) in languages"
                                     :key="count"
                                     v-if="activeLang == lang.slug">

                                    <div class="form-group" :id="'form-group-about_'+lang.slug">
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

            <!-- POPUP media -->
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
                __title: this.__('user.updateTitle'),
                __updateFormTitle: this.__('user.updateFormTitle'),
                __name: this.__('base.name'),
                __lastname: this.__('user.form.lastname'),
                __email: this.__('base.email'),
                __password: this.__('user.form.password'),
                __confirmPassword: this.__('user.form.confirmPassword'),
                __phone: this.__('user.form.phone'),
                __street: this.__('user.form.street'),
                __country: this.__('user.form.country'),
                __groups: this.__('user.form.groups'),
                __active: this.__('user.form.active'),
                __submitBtn: this.__('base.submitBtn'),
                __cancelBtn: this.__('base.cancelBtn'),
                __select: this.__('user.form.openMedia.select'),
                __change: this.__('user.form.openMedia.change'),
                __removeImage: this.__('user.form.openMedia.removeImage'),
                __true: this.__('base.booleans.true'),
                __false: this.__('base.booleans.false'),
                __globalUpdateBtn: this.__('base.updateBtn'),
                __globalUpdateAndCloseBtn: this.__('base.updateAndCloseBtn'),
                __globalUpdateAndNewBtn: this.__('base.updateAndNewBtn'),
                __globalCancelBtn: this.__('base.cancelBtn'),
                __featuredImage: this.__('base.featuredImage'),
            };

            this.$store.commit('setSpinner', true);

            this.languages = this.getLanguages;
            // make a key for each language in the about object
            for(let k in this.languages){
                if(this.languages[k].isDefault){
                    this.activeLang = this.languages[k].slug;
                }
            }

            // get user information
            var userDataPromise = this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/user/details/'+this.$route.params.id)
                .then((resp) => {
                    this.user.id = resp.body.details.userID;
                    this.user.firstName = resp.body.details.firstName;
                    this.user.lastName = resp.body.details.lastName;
                    this.user.email = resp.body.details.email;
                    this.user.phone = resp.body.details.phone;
                    this.user.street = resp.body.details.street;
                    this.user.country = resp.body.details.country;
                    this.user.isActive = resp.body.details.isActive;
                    this.user.about = resp.body.details.about;
                    this.user.groups = resp.body.details.roles;
                    this.groupsList = resp.body.allGroups;
                    if(resp.body.details.profile_image !== null){
                        this.$store.commit('setMediaSelectedFiles', {'featuredImage':[resp.body.details.profile_image]});
                        this.user.profileImageID = resp.body.details.profile_image.mediaID;
                    }
                }).then((resp) => {
                    this.getPluginsPanel(['users'], 'update');
                    this.$store.commit('setSpinner', false);
                });
        },
        data(){
            return{
                groupsList: [],
                pluginsPanels: [],
                languages: '',
                pluginsData: {},
                user:{
                    id: '',
                    firstName: '',
                    lastName: '',
                    email: '',
                    password: '',
                    confpassword: '',
                    phone: '',
                    street: '',
                    country: '',
                    groups: [],
                    profileImageID: null,
                    isActive: '',
                    about: '',
                },
                savedDropdownMenuVisible: false,
            }
        },
        methods: {
            store(redirectChoice){
                this.$store.dispatch('openLoading');
                if(this.mediaSelectedFiles['featuredImage'] !== undefined){
                    this.user.profileImageID = this.mediaSelectedFiles['featuredImage'][0].mediaID;
                }

                this.$store.dispatch('store',{
                    data: {
                        user: this.user,
                        redirect: redirectChoice,
                    },
                    url: this.basePath+'/'+this.getAdminPrefix+"/json/user/storeUpdate",
                    error: "User could not be updated. Please try again later"
                }).then((resp) => {
                    if(resp.code == 200){
                        if(this.Auth.userID == resp.data.userID){
                            this.Auth.firstName = resp.data.firstName;
                            this.Auth.lastName = resp.data.lastName;
                            if(this.mediaSelectedFiles['featuredImage'] !== undefined) {
                                this.Auth.avatar = this.basePath + '/' + this.mediaSelectedFiles['featuredImage'][0].fileDirectory + '/200x200/' + this.mediaSelectedFiles['featuredImage'][0].filename;
                            }
                        }
                        this.onStoreBtnClicked('user-',redirectChoice);
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
            },
        }
    }
</script>