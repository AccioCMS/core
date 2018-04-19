<template>
    <div class="col-md-12 col-xs-12 colons">

        <div class="x_panel">
            <div class="x_content">
                <div class="tabWrapper"><!-- TAB WRAPPER -->
                    <!-- TABS -->
                    <ul class="nav nav-tabs bar_tabs">
                        <li v-for="(tab, key) in tabs" :class="{active: activeTab == key}" @click="activeTab = key"><a style="text-transform: capitalize">{{ tab }}</a></li>
                    </ul>

                    <!-- TAB CONTENT -->
                    <div class="tabBody" v-if="!spinner">
                        <form class="form-horizontal form-label-left" id="store">

                            <div v-for="permalink in data[activeTab]">
                                <div class="form-group">
                                    <div class="col-md-2 col-sm-2 col-xs-12"></div>
                                    <div class="col-md-10 col-sm-10 col-xs-12">
                                        <h2 style="text-transform: capitalize">{{ permalink.name }}</h2>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-md-2 col-sm-2 col-xs-12">Custom URL: </label>
                                    <div class="col-md-10 col-sm-10 col-xs-12">
                                        <input type="text" class="form-control" v-model="permalink.custom_url" :placeholder="'Default URL: '+ permalink.default_url">
                                    </div>
                                </div>

                                <div class="message-info">
                                    URL parameters allowed : {{ permalink.acceptedParameters }}
                                </div>

                                <hr>
                            </div>

                            <div class="form-group clearfix">
                                <div class="col-md-2 col-sm-2 col-xs-12"></div>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <button type="button" class="btn btn-primary" @click="store">{{trans.__globalSaveBtn}}</button>
                                </div>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>

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
    a{
        cursor:pointer
    }
</style>
<script>
    export default{
        data(){
            return {
                spinner: true,
                activeTab: 'base',
                tabs: {},
                data:{}
            }
        },

        created(){
            // translations
            this.trans = {
                __trackingCode: this.__('settings.trackingCode'),
                __globalSaveBtn: this.__('base.saveBtn'),
            };

            // get all settings
            this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/settings/get-permalinks')
                .then((resp) => {
                    let permalinks = resp.body;
                    for(let k in permalinks){
                        let belongsTo = permalinks[k].belongsTo;

                        if(this.data[belongsTo] === undefined){
                            this.data[belongsTo] = [];
                        }
                        this.data[belongsTo].push(permalinks[k]);

                        if(this.tabs[permalinks[k].belongsTo] === undefined){
                            this.tabs[belongsTo] = belongsTo.replace("_", " ");
                        }
                    }
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
                this.$store.dispatch('store',{
                    data: this.data,
                    url: this.basePath+'/'+this.$route.params.adminPrefix+"/json/settings/store-permalinks",
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
