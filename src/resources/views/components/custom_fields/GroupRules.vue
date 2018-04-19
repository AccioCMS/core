<template>
    <div class="ruleGroup" :id="'ruleGroup-'+groupIndex+'-'+index"><!-- ruleGroup -->

        <div class="row">
            <div style="margin-top:8px; text-align:center;" v-if="index">
                <h5 style="text-transform: uppercase; font-weight: bold;">{{trans.__and}}</h5>
            </div>
        </div>

        <div class="clearfix"></div>

        <div class="row">
            <div class="col-md-11 col-sm-11 col-xs-11">
                <div class="form-group" id="form-group-app">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <multiselect
                                v-model="group.app"
                                track-by="key"
                                placeholder="Choose app"
                                :options="rulesOptions"
                                group-label="title"
                                group-values="options"
                                label="name"
                                :allow-empty="false"
                                select-label=""
                                deselect-label=""
                                @select="populateValueOptions($event, index, true)"></multiselect>
                        <div class="alert" v-if="StoreResponse.errors.app" v-for="error in StoreResponse.errors.app">{{ error }}</div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-md-6 col-sm-6 col-xs-12" id="form-group-operator">
                        <multiselect
                                v-model="group.operator"
                                track-by="value"
                                :options="[{label:'Equals' , value: 'equals'}, {label:'Not equals' , value: 'not-equals'}]"
                                label="label"
                                select-label=""
                                deselect-label=""
                                :allow-empty="false"></multiselect>
                    </div>
                    <div class="col-md-6 col-sm-6 col-xs-12" id="form-group-value">

                        <!-- if values with groups -->
                        <multiselect
                                v-if="group.app.app == 'category' && group.app.type == 'title'"
                                v-model="group.value"
                                :options="valueOptions"
                                :disabled="valueOptions.length <= 0"
                                :allow-empty="false"
                                track-by="value"
                                group-label="groupName"
                                group-values="options"
                                select-label=""
                                deselect-label=""
                                label="name"></multiselect>

                        <!-- if values with post search -->
                        <multiselect
                                v-else-if="group.app.app == 'post' && group.app.type == 'title'"
                                v-model="group.value"
                                id="ajax"
                                label="name"
                                track-by="value"
                                placeholder="Type to search"
                                open-direction="bottom"
                                :options="valueOptions"
                                :multiple="false"
                                :searchable="true"
                                :loading="arePostsLoading[index]"
                                :internal-search="false"
                                :clear-on-select="false"
                                :close-on-select="true"
                                :options-limit="300"
                                :limit="3"
                                :limit-text="limitText"
                                :max-height="600"
                                :show-no-results="false"
                                select-label=""
                                deselect-label=""
                                @search-change="searchPosts($event, index)">
                            <template slot="clear" slot-scope="props">
                                <div class="multiselect__clear"
                                     v-if="group.value != null && group.value.length"></div>
                            </template>
                            <span slot="noResult">Oops! No elements found. Consider changing the search query.</span>
                        </multiselect>

                        <!-- if values without groups -->
                        <multiselect
                                v-else
                                v-model="group.value"
                                :options="valueOptions"
                                :disabled="valueOptions.length <= 0"
                                :allow-empty="false"
                                track-by="value"
                                select-label=""
                                deselect-label=""
                                label="name"></multiselect>

                    </div>
                </div>
            </div>

            <div class="col-md-1 col-sm-1 col-xs-1" style="padding:0">
                <i class="fa fa-minus-circle fa-2x deleteRule" @click="remove" style="display:none;"></i>
            </div>
        </div>

    </div> <!-- END ruleGroup -->

</template>
<style scoped>
    .deleteRule{
        margin-top: 32px;
        cursor: pointer;
    }
    .deleteRule:hover{
        color: #d02222;
    }
</style>
<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';

    export default{
        mixins: [globalComputed, globalMethods],
        mounted(){
            this.trans = {
                __and: this.__('base.and'),
            };

            for(let k in this.postsOptions){
                this.rulesOptions.push(this.postsOptions[k]);
            }
            var global = this;
            $(document).ready(function() {
                $('#ruleGroup-'+global.groupIndex+'-'+global.index).hover(function(){
                    var id = $(this).attr('id');
                    $("#"+id+" .deleteRule").show();
                }, function(){
                    var id = $(this).attr('id');
                    $("#"+id+" .deleteRule").hide();
                });
            });

            this.populateValueOptions(this.group.app, this.index, false);
        },
        props:['group','groupIndex','index','postTypeList','postsOptions'],
        data(){
            return{
                isRemoveIconVisible: false,
                trans: {},
                arePostsLoading: [ false ],
                rulesOptions: [
                    {
                        title: 'CATEGORY',
                        options: [
                            { name: 'Title', type: 'title', key: 'category-title', app: 'category' },
                            { name: 'Form', type: 'form',  key: 'category-form', app: 'category' }
                        ]
                    },
                    {
                        title: 'POST TYPE',
                        options: [
                            { name: 'Title', type: 'title', key: 'post-type-title', app: 'post-type' },
                            { name: 'Form', type: 'form',  key: 'post-type-form', app: 'post-type' }
                        ]
                    },
                    {
                        title: 'USER',
                        options: [
                            { name: 'Role', type: 'role', key: 'user-role', app: 'user' },
                            { name: 'Form', type: 'form', key: 'user-form', app: 'user' }
                        ]
                    }
                ],
                valueOptions: [],
            }
        },

        methods: {
            // when app input is changed
            // change the value list of the value input
            populateValueOptions(event, index, emptyValue){
                //reset value
                this.valueOptions = [];
                if(emptyValue){
                    this.group.value = "";
                }
                if(event.type == 'form'){
                    this.valueOptions = [
                        { name: 'All', value: 'all' },
                        { name: 'Create', value: 'create' },
                        { name: 'Update', value: 'update' },
                    ];
                    return;
                }

                if(event.app == 'post-type'){
                    this.valueOptions = this.postTypeList;
                }else if(event.app == 'category'){
                    this.getCategories().then((data) => {
                        this.valueOptions = data;
                    });
                }else if(event.app == 'post'){
                    if(event.type == 'status'){
                        this.valueOptions = [
                            { name: 'Published', value: 'published' },
                            { name: 'Draft', value: 'draft' },
                            { name: 'Trash', value: 'trash' },
                        ];
                    }else if(event.type == 'title'){
                        this.valueOptions = [];
                        return;
                    }
                }else if(event.app == 'user'){
                    this.getUsersGroups().then((data) => {
                        this.valueOptions = data;
                    });
                }
            },

            // get categories from db
            getCategories(){
                return new Promise((resolve, reject) => {
                    this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/category/get-all-without-pagination')
                        .then((resp) => {
                            var list = resp.body;
                            var result = [];
                            var tmp = [];
                            for(let k in list){
                                if(tmp[list[k].name] === undefined){
                                    tmp[list[k].name] = [];
                                }
                                tmp[list[k].name].push({
                                    name: list[k].title,
                                    value: list[k].categoryID
                                });
                            }
                            for(let k in tmp){
                                result.push({
                                    groupName: k,
                                    options: tmp[k]
                                });
                            }

                            resolve(result);
                        }, error => {
                            reject(error);
                        });
                });
            },
            // get user groups from db
            getUsersGroups(){
                return new Promise((resolve, reject) => {
                    this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/user/get-groups')
                        .then((resp) => {
                            let result = [];
                            let list = resp.body;
                            for(let k in list){
                                result.push({
                                    name: list[k].name,
                                    value: list[k].groupID
                                });
                            }
                            resolve(result);
                        }, error => {
                            reject(error);
                        });
                });
            },
            // search for post by post type
            searchPosts(query, index){
                this.valueOptions = [];
                if(query){
                    this.arePostsLoading[index] = true;
                    this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/search/'+this.group.app.slug+'/'+query)
                        .then((resp) => {
                            let result = [];
                            let list = resp.body.data;
                            for(let k in list){
                                result.push({
                                    name: list[k].title,
                                    value: list[k].postID
                                });
                            }
                            this.valueOptions = result;
                            this.arePostsLoading[index] = false;
                        }, error => {
                            console.log(error);
                        });
                }
            },
            // remove this rule
            remove(){
                this.$emit('remove', {groupIndex: this.groupIndex, index: this.index})
            }
        },
    }
</script>
