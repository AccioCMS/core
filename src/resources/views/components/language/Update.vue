<template>
    <div class="componentsWs" dusk="languageUpdateComponent">

        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}}</h3>
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

                        <form class="form-horizontal form-label-left" id="store" v-if="!spinner">

                            <div class="form-group" id="form-group-name">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__name}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" id="name" :value="form.name" disabled>
                                    <div class="alert" v-if="StoreResponse.errors.name" v-for="error in StoreResponse.errors.name">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-nativeName">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__nativeName}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" id="nativeName" name="nativeName" :value="form.nativeName" disabled>
                                    <div class="alert" v-if="StoreResponse.errors.nativeName" v-for="error in StoreResponse.errors.nativeName">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-slug">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__slug}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" id="slug" :value="form.slug" disabled>
                                    <div class="alert" v-if="StoreResponse.errors.slug" v-for="error in StoreResponse.errors.slug">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="formG-group-isDefault">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__default}}</label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div id="isDefault" class="btn-group" data-toggle="buttons">
                                        <label class="btn btn-default yes" :class="{active: form.isDefault}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.isDefault = true">
                                            <input type="radio" name="default" :value="true" v-model="form.isDefault"> &nbsp; {{trans.__true}} &nbsp;
                                        </label>
                                        <label class="btn btn-primary no" :class="{active: !form.isDefault}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.isDefault = false">
                                            <input type="radio" name="default" :value="false" v-model="form.isDefault"> {{trans.__false}}
                                        </label>
                                        <div class="alert" v-if="StoreResponse.errors.isDefault" v-for="error in StoreResponse.errors.isDefault">{{ error }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-visible">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__visible}}</label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div id="isVisible" class="btn-group" data-toggle="buttons">
                                        <label class="btn btn-default yes" :class="{active: form.isVisible}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.isVisible = true">
                                            <input type="radio" name="isVisible" :value="true"> &nbsp; {{trans.__true}} &nbsp;
                                        </label>
                                        <label class="btn btn-primary no" :class="{active: !form.isVisible}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.isVisible = false">
                                            <input type="radio" name="isVisible" :value="false"> {{trans.__false}}
                                        </label>
                                        <div class="alert" v-if="StoreResponse.errors.isVisible" v-for="error in StoreResponse.errors.isVisible">{{ error }}</div>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

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

                    <button type="button" class="btn btn-info" id="globalCancel" @click="redirect('language-list')">{{trans.__globalCancelBtn}}</button>
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
            this.$store.commit('setSpinner', true);
            // get language information
            this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/language/details/'+this.$route.params.id)
                .then((resp) => {
                    this.form.id = resp.body.details.languageID;
                    this.form.name = resp.body.details.name;
                    this.form.nativeName = resp.body.details.nativeName;
                    this.form.isDefault = resp.body.details.isDefault;
                    this.form.isVisible = resp.body.details.isVisible;
                    this.form.slug = resp.body.details.slug;
                    this.$store.commit('setSpinner', false);
                });
            // translations
            this.trans = {
                __title: this.__('language.updateTitle'),
                __updateFormTitle: this.__('language.updateFormTitle'),
                __name: this.__('language.form.name'),
                __nativeName: this.__('language.nativeName'),
                __default: this.__('language.form.default'),
                __slug: this.__('base.slug'),
                __visible: this.__('base.visible'),
                __true: this.__('base.booleans.true'),
                __false: this.__('base.booleans.false'),
                __globalUpdateBtn: this.__('base.updateBtn'),
                __globalUpdateAndCloseBtn: this.__('base.updateAndCloseBtn'),
                __globalUpdateAndNewBtn: this.__('base.updateAndNewBtn'),
                __globalCancelBtn: this.__('base.cancelBtn'),
            };
        },
        data(){
            return{
                savedDropdownMenuVisible: false,
                form:{
                    name: '',
                    nativeName: '',
                    isDefault: false,
                    isVisible: true,
                    slug: '',
                    redirect: '',
                },
            }
        },
        methods: {
            // method responsible for sending the update request
            store(redirectChoice){
                this.$store.dispatch('openLoading');
                this.form.redirect = redirectChoice;
                this.$store.dispatch('store',{
                    data: this.form,
                    url: this.basePath+'/'+this.getAdminPrefix+"/json/language/store",
                    error: "Language could not be updated. Please try again later."
                }).then((resp) => {
                    if(resp.code == 200){
                        this.onStoreBtnClicked('language-',redirectChoice, resp.id);
                    }
                });
            },
        },
    }
</script>
