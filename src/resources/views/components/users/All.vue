<template>
    <div class="componentsWs">

        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}} <small>{{trans.__listTitle}}</small></h3>
                <a class="btn btn-primary pull-left addBtnMain" @click="redirect('user-create')" v-if="addPermission">{{trans.__addBtn}}</a>
            </div>

            <div class="title_right">
                <span class="input-group-btn advancedSearchBtnContainer">
                    <button class="btn btn-primary openAdvancedSearchBtn" @click="isAdvancedSearchOpen = !isAdvancedSearchOpen">{{trans.__advancedSearchBtn}}</button>
                </span>

                <!-- Simple search component -->
                <simple-search :url="dataSearchUrl" :view="viewSearchUrl"></simple-search>
            </div>

            <advanced-search
                    v-if="isAdvancedSearchOpen"
                    :optionsURL="advancedSearchOptionsUrl"
                    :searchURL="advancedSearchPostUrl"></advanced-search>

        </div>
        <!-- TITLE END -->
        <div class="clearfix"></div>

        <!-- BODY -->
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>{{trans.__listTableTitle}}</h2>
                        <div class="clearfix"></div>
                    </div>

                    <div class="x_content">
                        <div class="bulk_action_btns">
                            <a class="btn btn-danger" @click="deleteList()" :class="{'disabled':!Object.keys(bulkDeleteIDs).length}" id="deleteList" v-if="deletePermission">{{trans.__deleteBtn}}</a>
                        </div>

                        <pagination :listUrl="listUrl" :dataSearchUrl="dataSearchUrl" :advancedSearchPostUrl="advancedSearchPostUrl"></pagination>

                        <table id="datatable-checkbox" class="table table-striped table-bordered bulk_action">
                            <thead>
                            <tr class="tableHeader">
                                <th>#</th>
                                <th id="userID" @click="orderBy('userID')">{{trans.__id}} <i :class="tableHeaderOrderIcons('userID')" aria-hidden="true"></i></th>
                                <td>{{trans.__profileImage}}</td>
                                <th id="firstName" @click="orderBy('firstName')">{{trans.__firstName}} <i :class="tableHeaderOrderIcons('firstName')" aria-hidden="true"></i></th>
                                <th id="lastName" @click="orderBy('lastName')">{{trans.__lastName}} <i :class="tableHeaderOrderIcons('lastName')" aria-hidden="true"></i></th>
                                <th id="email" @click="orderBy('email')">{{trans.__email}} <i :class="tableHeaderOrderIcons('email')" aria-hidden="true"></i></th>
                                <th>{{trans.__action}}</th>
                            </tr>
                            </thead>

                            <tbody>

                            <tr v-if="spinner">
                                <td colspan="7">
                                    <!-- Loading component -->
                                    <spinner :width="'30px'" :height="'30px'" :border="'5px'"></spinner>
                                </td>
                            </tr>

                            <tr v-for="(item, index) in getList.data" v-if="!spinner" dusk="userListComponent">
                                <th><input type="checkbox" :value="item.userID" v-model="bulkDeleteIDs" :id="'ID'+item.userID"></th>
                                <th>{{ item.userID }}</th>
                                <td><img :src="getAvatar(item)" class="listUserProfileImage"></td>
                                <td>{{ item.firstName }}</td>
                                <td>{{ item.lastName }}</td>
                                <td>{{ item.email }}</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary" @click="redirect('user-update', item.userID)" v-if="updatePermission">{{trans.__updateBtn}}</button>
                                        <button type="button" class="btn disabled" v-if="!updatePermission">{{trans.__updateBtn}}</button>

                                        <button type="button" class="btn btn-primary" @click="toggleListActionBar(index)" :id="'toggleListBtn'+item.userID">
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="lists-action-bar-dropdown" v-if="index === openedItemActionBar">
                                            <li><a style="cursor:pointer" @click="redirect('user-reset', item.userID)" v-if="resetPermission">{{trans.__reset}}</a></li>
                                            <li><a style="cursor:pointer" @click="redirect('user-details', item.userID)">{{trans.__details}}</a></li>
                                            <li class="divider"></li>
                                            <li v-if="deletePermission"><a :id="'deleteItemBtn'+item.userID" @click="deleteItem(item.userID, index)">{{trans.__deleteBtn}}</a></li>
                                            <li v-if="!deletePermission" class="disabled"><a>{{trans.__deleteBtn}}</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            </tbody>
                        </table>

                        <pagination :listUrl="listUrl" :dataSearchUrl="dataSearchUrl" :advancedSearchPostUrl="advancedSearchPostUrl"></pagination>

                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <!-- BODY END -->

    </div>
</template>
<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';
    import { lists } from '../../mixins/lists';
    import SimpleSearch from '../vendor/SimpleSearch.vue';
    import Pagination from '../vendor/Pagination.vue';
    import AdvancedSearch from '../vendor/AdvancedSearch.vue';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated, lists],
        components: {
            'simpleSearch': SimpleSearch,
            'pagination': Pagination,
            'advancedSearch':AdvancedSearch,
        },
        created(){
            this.listUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/user/get-all';
            this.viewSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/user/search/';
            this.dataSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/search/user/';
            this.advancedSearchOptionsUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/user/advancedSearch';
            this.advancedSearchPostUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/user/advanced-search-results';
            this.deleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/user/delete/';
            this.bulkDeleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/user/bulk-delete';

            this.trans = {
                __title: this.__('user.title'),
                __listTitle: this.__('user.listTitle'),
                __addBtn: this.__('user.addBtn'),
                __advancedSearchBtn: this.__('base.advancedSearchBtn'),
                __listTableTitle: this.__('user.listTableTitle'),
                __deleteBtn: this.__('base.deleteBtn'),
                __updateBtn: this.__('base.updateBtn'),
                __details: this.__('user.details'),
                __reset: this.__('user.reset'),
                __id: this.__('user.listTableColumns.id'),
                __profileImage: this.__('user.listTableColumns.profileImage'),
                __firstName: this.__('user.listTableColumns.firstName'),
                __lastName: this.__('user.listTableColumns.lastName'),
                __email: this.__('base.email'),
                __action: this.__('base.action'),
            };
        },
        mounted(){
            // If advanced search is made
            if(this.$route.query.advancedSearch == 1){
                this.$router.push({ name: 'user-list', query: this.$route.query});
                this.isAdvancedSearchOpen = true;
            }else{
                this.getListData();
            }

            // permissions
            this.addPermission = this.hasPermission('User','create');
            this.updatePermission = this.hasPermission('User','update');
            this.deletePermission = this.hasPermission('User','delete');
            this.resetPermission = this.hasPermission('global','admin');
        },

        data(){
            return{
                listUrl: '', // default list
                viewSearchUrl: '', // url to search view
                dataSearchUrl: '', // url to get the search data
                advancedSearchOptionsUrl: '', // url to get the options for advanced search
                advancedSearchPostUrl: '', // url to get the advanced search result
                deleteUrl: '', // url to delete a item
                bulkDeleteUrl: '', // url to bul delete items
                isAdvancedSearchOpen: false,
                bulkDeleteIDs: [],  // all selected ids to be deleted from the list
                addPermission: false,
                updatePermission: false,
                deletePermission: false,
                resetPermission: false,
                form:{  // data of advanced search
                   fields: [],  // search fields and parameters
                   page: '', // pagination number
                   orderBy: '', // if is ordered by a column
                   orderType: '' // order type which is ASC or DESC
                }
            }
        },
        methods: {
            // get users profile image
            // if they don't have any profile image set the default avatar
            getAvatar(object){
                if(object.url != null){
                    return this.generateUrl("/"+object.fileDirectory+'/200x200/'+object.filename);
                }else{
                    return object.gravatar;
                }
            },
        },
        watch:{
            // watch for url changes and component doesn't change
            '$route': function(){
                if(this.$route.query.advancedSearch === undefined){
                    this.getListData();
                }
            }
        },
    }
</script>
