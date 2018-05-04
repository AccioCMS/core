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
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">

                        <div class="col-md-4 col-sm-4 col-xs-12">
                            <div class="form-group" id="form-group-name">
                                <label class="control-label col-md-2 col-sm-3 col-xs-12" style="padding-top:7px;">{{trans.__name}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" id="name" v-model="name">
                                    <div class="alert" v-if="StoreResponse.errors.name" v-for="error in StoreResponse.errors.name">{{ error }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="clearfix"></div>
                    </div>

                    <div class="x_content">
                        <!-- Loading component -->
                        <spinner v-if="spinner" :width="'40px'" :height="'40px'" :border="'10px'"></spinner>

                        <template v-if="!spinner">
                            <div class="col-lg-2 col-md-3 col-sm-3 col-xs-12 linkListContainer">
                                <ul class="linkList">
                                    <li><a @click="selectPermission(globalPermissions, 'globalPermissions')" :class="{active: selectedPermissionKey == 'globalPermissions'}">{{trans.__globalPermissions}}</a></li>
                                    <li v-for="(item, key, index) in permissions">
                                        <a @click="selectPermission(item, key)" :class="{active: selectedPermissionKey == key}">
                                            {{ item.label }}
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- GLOBAL PERMISSION -->
                            <div class="col-lg-10 col-md-9 col-sm-9 col-xs-12 permissionInputsContainer" v-if="selectedPermissionKey == 'globalPermissions'">
                                <h3 class="selectedObjTitle">{{trans.__globalPermissions}}</h3>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12" v-for="(item, key ,index) in globalPermissions">
                                        <div class="form-group">
                                            <div class="col-md-2 col-sm-2 col-xs-12">
                                                <input type="checkbox" :value="item" v-model="globalPermissions[key]" :id="key">
                                            </div>
                                            <label class="control-label col-md-9 col-sm-9 col-xs-12" :for="key">
                                                <span v-if="key == 'admin_panel'">{{trans.__hasAdminAccess}}</span>
                                                <span v-if="key == 'author'">{{trans.__author}}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- MODEL PERMISSION -->
                            <div class="col-lg-10 col-md-10 col-sm-10 col-xs-12 permissionInputsContainer" v-if="selectedPermissionKey != 'globalPermissions'">
                                <h3 class="selectedObjTitle">{{ selectedPermissionLabel }}</h3>

                                <!-- defaultPermissions -->
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12" v-for="(item, index) in selectedPermissionOBJ.defaultPermissions">
                                        <div class="form-group">
                                            <div class="col-md-2 col-sm-2 col-xs-12">
                                                <input type="checkbox" :value="item" v-model="permissions[selectedPermissionKey].defaultPermissionsValues" :id="selectedPermissionKey+'_'+item">
                                            </div>
                                            <label class="control-label col-md-9 col-sm-9 col-xs-12" :for="selectedPermissionKey+'_'+item">{{ item }}</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Categories permission in post types -->
                                <div class="row" v-if="selectedPermissionOBJ.categories !== undefined">
                                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">

                                        <div class="form-group clearfix">
                                            <label class="control-label col-md-12 col-sm-12 col-xs-12">{{trans.__categories}}</label>
                                            <div class="col-md-12 col-sm-12 col-xs-12">
                                                <multiselect
                                                        v-model="permissions[selectedPermissionKey].categoriesValues"
                                                        :options="selectedPermissionOBJ.categories"
                                                        :multiple="true"
                                                        :close-on-select="false"
                                                        :clear-on-select="false"
                                                        :hide-selected="true"
                                                        placeholder="Categories"
                                                        label="title"
                                                        :disabled="permissions[selectedPermissionKey].hasAll"
                                                        track-by="title"></multiselect>
                                            </div>
                                        </div>

                                        <div class="form-group clearfix">
                                            <div class="col-md-1 col-sm-1">
                                                <input type="checkbox" id="hasAll" v-model="permissions[selectedPermissionKey].hasAll">
                                            </div>
                                            <label class="control-label col-md-9 col-sm-9" for="hasAll">{{trans.__hasAll}}</label>
                                        </div>

                                    </div>
                                </div>

                                <!-- customPermissions -->
                                <div class="row">
                                    <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12" v-for="(item, key, index) in selectedPermissionOBJ.customPermissions">
                                        <div class="form-group" v-if="item.type == 'checkbox'">
                                            <div class="col-md-2 col-sm-2 col-xs-12">
                                                <input type="checkbox" :value="item.label" v-model="permissions[selectedPermissionKey].customPermissionsValues[key]">
                                            </div>
                                            <label class="control-label col-md-9 col-sm-9 col-xs-12">{{ item.label }}</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12" v-for="(item, key ,index) in selectedPermissionOBJ.customPermissions" v-if="item.type == 'select'">
                                        <div class="form-group">
                                            <label class="control-label col-md-12 col-sm-12 col-xs-12">{{trans.__urlAccessToLanguages}}</label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <multiselect
                                                        v-model="permissions[selectedPermissionKey].customPermissionsValues[key]"
                                                        :options="item.response"
                                                        :multiple="true"
                                                        :close-on-select="false"
                                                        :clear-on-select="false"
                                                        :hide-selected="true"
                                                        placeholder="Pick some"
                                                        label="title"
                                                        track-by="title"></multiselect>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </template>

                    </div>

                </div>
            </div>

            <div class="mainButtonsContainer" v-if="!spinner">
                <div class="row">
                    <button type="button" class="btn btn-primary" id="globalSaveBtn" @click="store()">{{trans.__saveBtn}}</button>
                    <button class="btn btn-info" id="globalCancel" @click="redirect('permission-list')">{{trans.__cancelBtn}}</button>
                </div>
            </div>

        </div>
    </div>
</template>
<style src="./style.css" scoped></style>
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
                __listTableTitle: this.__('Permissions.listTableTitle'),
                __name: this.__('Permissions.listTableColumns.name'),
                __globalPermissions: this.__('Permissions.globalPermissions'),
                __hasAdminAccess: this.__('Permissions.hasAdminAccess'),
                __author: this.__('Permissions.author'),
                __categories: this.__('Permissions.categories'),
                __hasAll: this.__('Permissions.hasAll'),
                __urlAccessToLanguages: this.__('Permissions.urlAccessToLanguages'),
                __saveBtn: this.__('base.saveBtn'),
                __cancelBtn: this.__('base.cancelBtn'),
            };

            this.$store.commit('setSpinner', true);
            this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/permissions/get-all-permissions-options')
                .then((resp) => {
                    // make a key for each language in the about object
                    for(let k in this.getLanguages){
                        if(this.getLanguages[k].isDefault){
                            this.defaultLanguage = this.getLanguages[k];
                        }
                    }

                    var permissions = resp.body;
                    // create value containers
                    for(let k in permissions){
                        if(permissions[k].categories !== undefined){
                            permissions[k].categoriesValues = [];
                        }
                        permissions[k].hasAll = false;
                        permissions[k].defaultPermissionsValues = []; // default values as a array
                        if(permissions[k].customPermissions !== undefined){ // if there has custom permissions
                            permissions[k].customPermissionsValues = {}; // create object for the values of custom permissions inputs

                            for(let cKey in permissions[k].customPermissions){

                                // if custom permission is of type select make a array for his values
                                if(permissions[k].customPermissions[cKey].type == 'select'){
                                    permissions[k].customPermissionsValues[cKey] = [];
                                    // set default language selected by default (in the language multiselect, customPermissionValue)
                                    if(k == "Language" && cKey == "id" && this.$route.params.id == 0){
                                        permissions[k].customPermissionsValues[cKey].push({languageID: this.defaultLanguage.languageID, title: this.defaultLanguage.name});
                                    }
                                }else{
                                    // if custom permission is simple checkbox
                                    permissions[k].customPermissionsValues[cKey] = false;
                                }
                            }
                        }
                    }

                    if(this.$route.params.id == 0){
                        // if we are creating a new group
                        this.permissions = permissions;
                        this.$store.commit('setSpinner', false);
                    }else{
                        // if we are updating a group
                        this.$http.post(this.basePath+'/'+this.$route.params.adminPrefix+'/json/permissions/get-list-values', {ID: this.$route.params.id, permissions: permissions})
                            .then((resp) => {
                                this.permissions = resp.body.permissions;
                                this.name = resp.body.name;
                                // populate global permissions
                                for(let k in resp.body.globalPermissions){
                                    this.globalPermissions[k] = resp.body.globalPermissions[k];
                                }
                                this.$store.commit('setSpinner', false);
                            }, response => {
                                // if a error happens
                                this.$store.commit('setSpinner', false);
                                new Noty({
                                    type: "error",
                                    layout: 'bottomLeft',
                                    text: response.statusText
                                }).show();
                            });
                    }
                }, response => {
                    // if a error happens
                    this.$store.commit('setSpinner', false);
                    new Noty({
                        type: "error",
                        layout: 'bottomLeft',
                        text: response.statusText
                    }).show();
                });
        },
        data(){
            return{
                defaultLanguage: {},
                permissions:'',
                name: '',
                selectedPermissionOBJ: '',
                selectedPermissionKey: 'globalPermissions',
                selectedPermissionLabel: 'Global permission',
                wereSelected: [],
                globalPermissions:{
                    admin_panel: false,
                    author: false,
                }
            }
        },
        methods:{
            // select permission to edit
            selectPermission(obj, key){
                this.selectedPermissionKey = key;
                this.selectedPermissionLabel = obj['label'];
                var selectedPermissionOBJ = obj;
                this.selectedPermissionOBJ = {};

                // if model has custom permissions excecute the query and get the results
                if(selectedPermissionOBJ.customPermissions !== undefined){
                    if(this.wereSelected.indexOf(key) == -1){ // check if this link is clicked earlier if it has don't make the ajax request
                        for(let k in selectedPermissionOBJ.customPermissions){
                            if(selectedPermissionOBJ.customPermissions[k].type == 'select'){
                                // make AJAX request
                                this.$store.commit('setSpinner', true);
                                this.$http.post(this.basePath+'/'+this.$route.params.adminPrefix+'/json/permissions/get-list', {customPermissions: selectedPermissionOBJ.customPermissions[k].value, ID: this.$route.params.id})
                                    .then((resp) => {
                                        selectedPermissionOBJ.customPermissions[k].response = resp.body;
                                        this.selectedPermissionOBJ = {};
                                        this.selectedPermissionOBJ = selectedPermissionOBJ;
                                        this.$store.commit('setSpinner', false);
                                    });
                            }
                        }
                    }else{
                        this.selectedPermissionOBJ = selectedPermissionOBJ;
                    }
                }else{
                    this.selectedPermissionOBJ = selectedPermissionOBJ;
                }

                // used to tell if we already have selected this model app
                if(this.wereSelected.indexOf(key) == -1){
                    this.wereSelected.push(key);
                }
            },
            store(){
                var request = {
                    id: this.$route.params.id,
                    name: this.name,
                    globalPermissions: this.globalPermissions,
                    permissions: this.permissions,
                };
                this.$store.dispatch('store',{
                    data: request,
                    url: this.basePath+'/'+this.getAdminPrefix+"/json/permissions/store",
                    error: "Permission could not be stored. Please try again later."
                }).then( (resp) => {
                    this.redirect('permission-list');
                });
            },
        },

        filters: {
            replaceUnderlines: function(value) {
                return value.replace(/_/g, " ");
            }
        }
    }
</script>
