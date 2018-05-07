<template>
    <div>
        <!-- Loading component -->
        <spinner v-if="spinner" :width="'40px'" :height="'40px'" :border="'10px'"></spinner>

        <form v-if="!spinner">
            <div class="message-info">
                Enter your Google Analytics tracking code below. You can also use Google Tag Manager instead by checking the relevant settings.
            </div>
            <br>

            <div class="form-group clearfix" id="form-group-trackingCode">
                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__trackingCode}} :</label>
                <div class="col-md-9 col-sm-9 col-xs-12">
                    <input type="text" class="form-control" id="trackingCode" name="trackingCode" v-model="form.trackingCode" placeholder="UA-">
                    <div class="alert" v-if="StoreResponse.errors.trackingCode" v-for="error in StoreResponse.errors.trackingCode">{{ error }}</div>
                </div>
            </div>

            <div class="form-group clearfix" id="form-group-useTagManager">
                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__useTagManager}} :</label>
                <div class="col-md-9 col-sm-9 col-xs-12">
                    <div id="sitemapIsActive" class="btn-group" data-toggle="buttons">
                        <label class="btn btn-default yes" :class="{active: form.useTagManager == true}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.useTagManager = true">
                            <input type="radio" value="yes"> &nbsp; Yes &nbsp;
                        </label>
                        <label class="btn btn-primary no" :class="{active: form.useTagManager == false}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.useTagManager =false">
                            <input type="radio" value="no"> No
                        </label>
                    </div>
                    <div class="alert" v-if="StoreResponse.errors.useTagManager" v-for="error in StoreResponse.errors.useTagManager">{{ error }}</div>
                </div>
            </div>

            <div class="form-group clearfix" id="form-group-tagManager">
                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__tagManager}} :</label>
                <div class="col-md-9 col-sm-9 col-xs-12">
                    <input type="text" class="form-control" id="tagManager" name="tagManager" v-model="form.tagManager" :disabled="!form.useTagManager">
                    <div class="alert" v-if="StoreResponse.errors.tagManager" v-for="error in StoreResponse.errors.tagManager">{{ error }}</div>
                </div>
            </div>

            <div class="form-group clearfix">
                <div class="col-md-3 col-sm-3 col-xs-12"></div>
                <div class="col-md-9 col-sm-9 col-xs-12">
                    <button type="button" class="btn btn-primary" @click="store">{{trans.__globalSaveBtn}}</button>
                </div>
            </div>


        </form>

    </div>
</template>
<style scoped>
    .message-info{
        color: #31708f;
        background-color: #d9edf7;
        border-color: #bce8f1;
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }
</style>
<script>
    export default{
        data(){
            return{
                spinner: true,
                StoreResponse: { errors:[] },
                form: {
                    trackingCode: '',
                    useTagManager: false,
                    tagManager: '',
                }
            }
        },
        created(){
            // translations
            this.trans = {
                __trackingCode: this.__('settings.trackingCode'),
                __useTagManager: this.__('settings.useTagManager'),
                __tagManager: this.__('settings.tagManager'),
                __globalSaveBtn: this.__('base.saveBtn'),
            };

            // get all settings
            this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/settings/get-settings')
                .then((resp) => {
                    this.form.trackingCode = resp.body.settings.trackingCode.value;
                    this.form.useTagManager = resp.body.settings.useTagManager.value;
                    this.form.tagManager = resp.body.settings.tagManager.value;
                    this.spinner = false;
                });
        },
        methods: {
            // use translation method of vuex
            __(key){
                this.$store.dispatch('__', key);
                return this.getTranslation;
            },
            // store request in database
            store(){
                var request = {
                    settingsType: 'analytics',
                    form: this.form,
                }

                this.$store.dispatch('store',{
                    data: request,
                    url: this.basePath+'/'+this.$route.params.adminPrefix+"/json/settings/store",
                    error: "Settings could not be saved. Please try again later."
                });
            }
        },
        computed: {
            getTranslation(){
                // returns translated value
                return this.$store.getters.get_translation;
            },
            // get base path
            basePath(){
                return this.$store.getters.get_base_path;
            }
        },
    }
</script>
