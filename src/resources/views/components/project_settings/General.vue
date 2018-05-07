<template>
    <div>
        <form>
            <!-- Loading component -->
            <spinner v-if="spinner" :width="'40px'" :height="'40px'" :border="'10px'"></spinner>

            <div class="inputsContainer" v-if="!spinner">
                <div class="form-group clearfix" id="form-group-siteTitle">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__siteTitle}}</label>
                    <div class="col-md-9 col-sm-9 col-xs-12">
                        <input type="text" class="form-control" id="siteTitle" name="siteTitle" v-model="form.siteTitle">
                        <div class="alert" v-if="StoreResponse.errors.siteTitle" v-for="error in StoreResponse.errors.siteTitle">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group clearfix" id="form-group-adminEmail">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__adminEmail}}</label>
                    <div class="col-md-9 col-sm-9 col-xs-12">
                        <input type="text" class="form-control" id="adminEmail" name="adminEmail" v-model="form.adminEmail">
                        <div class="alert" v-if="StoreResponse.errors.adminEmail" v-for="error in StoreResponse.errors.adminEmail">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group clearfix" id="form-group-defaultUserRole">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__defaultUserRole}}</label>
                    <div class="col-md-9 col-sm-9 col-xs-12">
                        <select v-model="form.defaultUserRole" id="defaultUserRole" name="defaultUserRole" class="form-control">
                            <option value="NULL">NONE</option>
                            <option :value="role.slug" v-for="role in userRoles">{{ role.name }}</option>
                        </select>
                        <div class="alert" v-if="StoreResponse.errors.defaultUserRole" v-for="error in StoreResponse.errors.defaultUserRole">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group clearfix" id="form-group-timezone">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__timezone}}</label>
                    <div class="col-md-9 col-sm-9 col-xs-12">
                        <select v-model="form.timezone" id="timezone" name="timezone" class="form-control">
                            <option :value="option" v-for="option in timezoneOptions">{{ option }}</option>
                        </select>
                        <div class="alert" v-if="StoreResponse.errors.timezone" v-for="error in StoreResponse.errors.timezone">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group" id="form-group-logo">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__logo}}</label>
                    <div class="imagePrevContainer col-md-9 col-sm-9 col-xs-12">

                        <div class="imageSingleThumb" v-if="mediaSelectedFiles['logo']">
                            <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile('logo', 0)"></i>
                            <img :src="generateUrl(constructUrl(mediaSelectedFiles['logo'][0]))">
                        </div>

                        <div class="clearfix"></div>

                        <a class="btn btn-info" @click="openMedia('image', 'logo')" id="openMediaLogo">
                            <span v-if="!mediaSelectedFiles['logo']">{{trans.__addImage}}</span>
                            <span v-if="mediaSelectedFiles['logo']">{{trans.__change}}</span>
                        </a>

                        <div class="alert" v-if="StoreResponse.errors.logo" v-for="error in StoreResponse.errors.logo">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group" id="form-group-watermark">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__watermark}}</label>
                    <div class="imagePrevContainer col-md-9 col-sm-9 col-xs-12">

                        <div class="imageSingleThumb" v-if="mediaSelectedFiles['watermark']">
                            <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile('watermark', 0)"></i>
                            <img :src="generateUrl(constructUrl(mediaSelectedFiles['watermark'][0]))">
                        </div>

                        <div class="clearfix"></div>

                        <a class="btn btn-info" @click="openMedia('image', 'watermark')" id="openMediaWatermark">
                            <span v-if="!mediaSelectedFiles['watermark']">{{trans.__addImage}}</span>
                            <span v-if="mediaSelectedFiles['watermark']">{{trans.__change}}</span>
                        </a>

                        <div class="alert" v-if="StoreResponse.errors.logo" v-for="error in StoreResponse.errors.logo">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group clearfix" id="form-group-defaultLanguage">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__defaultLanguage}}</label>
                    <div class="col-md-9 col-sm-9 col-xs-12">
                        <select v-model="form.defaultLanguage" id="defaultLanguage" name="defaultLanguage" class="form-control">
                            <option :value="language.languageID" v-for="language in languageList">{{ language.name }}</option>
                        </select>
                        <div class="alert" v-if="StoreResponse.errors.defaultLanguage" v-for="error in StoreResponse.errors.defaultLanguage">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group clearfix" id="form-group-homepageID">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__frontPage}}</label>
                    <div class="col-md-9 col-sm-9 col-xs-12">
                        <select v-model="form.homepageID" id="homepageID" name="homepageID" class="form-control">
                            <option :value="page.postID" v-for="page in pagesList">{{ page.title }}</option>
                        </select>
                        <div class="alert" v-if="StoreResponse.errors.homepageID" v-for="error in StoreResponse.errors.homepageID">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group clearfix" id="form-group-activeTheme">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__activeTheme}}</label>
                    <div class="col-md-9 col-sm-9 col-xs-12">
                        <select v-model="form.activeTheme" id="activeTheme" name="activeTheme" class="form-control">
                            <option :value="theme.namespace" v-for="theme in themesList">{{ theme.Title }}</option>
                        </select>
                        <div class="alert" v-if="StoreResponse.errors.activeTheme" v-for="error in StoreResponse.errors.activeTheme">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group clearfix" id="form-group-isVisible">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__activateMobileTheme}}</label>
                    <div class="col-md-9 col-sm-9 col-xs-12" >
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default" :class="{active: form.activateMobileTheme}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.activateMobileTheme = true">
                                <input type="radio" value="true"> &nbsp; {{trans.__true}} &nbsp;
                            </label>
                            <label class="btn btn-primary" :class="{active: !form.activateMobileTheme}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.activateMobileTheme = false">
                                <input type="radio" value="false"> {{trans.__false}}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group clearfix" id="form-group-activeTheme">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__mobileActiveTheme}}</label>
                    <div class="col-md-9 col-sm-9 col-xs-12">
                        <select v-model="form.mobileActiveTheme" :disabled="!form.activateMobileTheme"  id="activeTheme" name="activeTheme" class="form-control">
                            <option :value="theme.namespace" v-for="theme in themesList">{{ theme.Title }}</option>
                        </select>
                    </div>
                </div>

                <div class="form-group clearfix">
                    <div class="col-md-3 col-sm-3 col-xs-12"></div>
                    <div class="col-md-9 col-sm-9 col-xs-12">
                        <button type="button" class="btn btn-primary" @click="store">{{trans.__globalSaveBtn}}</button>
                    </div>
                </div>

            </div>

        </form>

        <!-- OPEN popup media window to choose files -->
        <transition name="slide-fade">
            <popup-media v-if="isMediaOpen"></popup-media>
        </transition>

    </div>

</template>
<style scoped>
    .form-group{
        margin-bottom: 15px;
    }
    .form-group label{
        margin-top: 10px;
    }
    .mainButtonsContainer button{
        float: right;
        margin-left: 10px;
        margin-top: 10px;
    }
</style>
<script>
    import PopupMedia from '../media/Popup.vue'
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';
    import { globalMethods } from '../../mixins/globalMethods';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        components:{'popup-media':PopupMedia},
        data(){
            return {
                userRoles: [],
                languageList: [],
                pagesList: [],
                themesList: [],
                timezoneOptions: ['UTC-12', 'UTC-11:30', 'UTC-11', 'UTC-10:30', 'UTC-10', 'UTC-9:30', 'UTC-9', 'UTC-8:30', 'UTC-8', 'UTC-7:30', 'UTC-7', 'UTC-6:30', 'UTC-6', 'UTC-5:30', 'UTC-5', 'UTC-4:30', 'UTC-4', 'UTC-3:30', 'UTC-3', 'UTC-2:30', 'UTC-2', 'UTC-1:30', 'UTC-1', 'UTC-0:30',
                'UTC+0', 'UTC+0:30', 'UTC+1', 'UTC+1:30', 'UTC+2', 'UTC+2:30', 'UTC+3', 'UTC+3:30', 'UTC+4', 'UTC+4:30', 'UTC+5', 'UTC+5:30', 'UTC+6', 'UTC+6:30', 'UTC+7', 'UTC+7:30', 'UTC+8', 'UTC+8:30', 'UTC+9', 'UTC+9:30', 'UTC+10',
                'UTC+10:30', 'UTC+11', 'UTC+11:30', 'UTC+12', 'UTC+12.45', 'UTC+13', 'UTC+13.45', 'UTC+14'],
                form: {
                    siteTitle: '',
                    adminEmail: '',
                    defaultUserRole: '',
                    timezone: '',
                    logo: '',
                    watermark: '',
                    defaultLanguage: '',
                    homepageID: '',
                    activeTheme: '',
                    activateMobileTheme: false,
                    mobileActiveTheme: '',
                }
            }
        },
        created(){
            // translations
            this.trans = {
                __siteTitle: this.__('settings.siteTitle'),
                __adminEmail: this.__('settings.adminEmail'),
                __defaultUserRole: this.__('settings.defaultUserRole'),
                __activeTheme: this.__('settings.activeTheme'),
                __activateMobileTheme: this.__('settings.activateMobileTheme'),
                __mobileActiveTheme: this.__('settings.mobileActiveTheme'),
                __timezone: this.__('settings.timezone'),
                __logo: this.__('settings.logo'),
                __defaultLanguage: this.__('settings.defaultLanguage'),
                __frontPage: this.__('settings.homepage'),
                __addImage: this.__('media.addImage'),
                __watermark: this.__('media.watermark'),
                __change: this.__('media.change'),
                __globalSaveBtn: this.__('base.saveBtn'),
                __true: this.__('base.booleans.true'),
                __false: this.__('base.booleans.false')
            };

            this.$store.commit('setSpinner', true);

            // get all languages
            this.languageList = this.getLanguages;

            // get all settings
            var settingsPromise = this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/settings/get-settings')
                .then((resp) => {
                    this.userRoles = resp.body.userGroups;
                    this.pagesList = resp.body.pages;
                    this.themesList = resp.body.themeConfigs;

                    this.form.siteTitle = resp.body.settings.siteTitle.value;
                    this.form.adminEmail = resp.body.settings.adminEmail.value;
                    this.form.defaultUserRole = resp.body.settings.defaultUserRole.value;
                    this.form.timezone = resp.body.settings.timezone.value;
                    this.form.defaultLanguage = resp.body.settings.defaultLanguage.value;
                    this.form.homepageID = resp.body.settings.homepageID.value;
                    this.form.activeTheme = resp.body.settings.activeTheme.value;
                    this.form.activateMobileTheme = resp.body.settings.activateMobileTheme.value;
                    this.form.mobileActiveTheme = resp.body.settings.mobileActiveTheme.value;
                    this.form.logo = resp.body.settings.logo.value;

                    let media = {};
                    if(resp.body.settings.logo && resp.body.settings.logo !== undefined && resp.body.settings.logo.media !== undefined && Object.keys(resp.body.settings.logo.media).length != 0){
                        media['logo'] = [resp.body.settings.logo.media];
                    }
                    this.form.watermark = resp.body.settings.watermark.value;
                    if(resp.body.settings.watermark && resp.body.settings.watermark !== undefined && resp.body.settings.watermark.media !== undefined && Object.keys(resp.body.settings.watermark.media).length != 0){
                        media['watermark'] = [resp.body.settings.watermark.media];
                    }
                    this.$store.commit('setMediaSelectedFiles', media);
                }).then((resp) => {
                    this.$store.commit('setSpinner', false);
                });

        },
        methods: {
            openMedia(format, inputName){
                this.$store.commit('setOpenMediaOptions', { multiple: false, has_multile_files: false, multipleInputs: false, format : format, inputName: inputName, langSlug: '', clear: false });
                this.$store.commit('setIsMediaOpen', true);
            },
            store(){
                if(this.mediaSelectedFiles['logo'] !== undefined && this.mediaSelectedFiles['logo'][0] !== undefined){
                    this.form.logo = this.mediaSelectedFiles['logo'][0].mediaID;
                }

                if(this.mediaSelectedFiles['watermark'] !== undefined && this.mediaSelectedFiles['watermark'][0] !== undefined){
                    this.form.watermark = this.mediaSelectedFiles['watermark'][0].mediaID;
                }

                const request = {
                    settingsType: 'general',
                    form: this.form,
                };

                this.$store.dispatch('store',{
                    data: request,
                    url: this.basePath+'/'+this.$route.params.adminPrefix+"/json/settings/store",
                    error: "Settings could not be saved. Please try again later."
                }).then((resp) => {
                    if(resp.code == 200){
                        this.getGlobalData.settings.logo = this.basePath+'/'+this.mediaSelectedFiles['logo'][0].fileDirectory+'/200x200/'+this.mediaSelectedFiles['logo'][0].filename;
                        this.getGlobalData.settings.siteTitle = this.form.siteTitle;
                    }
                });
            },
            // this function is used to remove the selected images in custom fields
            deleteSelectedMediaFile(key, mediaID){
                var mediaArr = this.mediaSelectedFiles;
                for(var k in mediaArr[key]){
                    if(key == "logo"){
                        delete mediaArr[key];
                        continue;
                    }
                    if(key == "watermark"){
                        delete mediaArr[key];
                        continue;
                    }
                    if(mediaArr[key][k].mediaID == mediaID){
                        mediaArr[key].splice(mediaArr[key][k], 1);
                    }
                }
                this.$store.commit('setMediaSelectedFiles', "");
                this.$store.commit('setMediaSelectedFiles', mediaArr);
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
            }
        }
    }
</script>
