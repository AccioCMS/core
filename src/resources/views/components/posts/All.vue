<template>
    <div class="componentsWs">
        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}} <small>{{trans.__listTitle}}</small></h3>
                <a class="btn btn-primary pull-left addBtnMain" @click="redirect('post-create')" v-if="hasAddPermission">{{trans.__addBtn}}</a>
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
                    :advancedSearchPostUrl="advancedSearchPostUrl"
            ></advanced-search>

        </div>
        <!-- TITLE END -->
        <div class="clearfix"></div>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>{{trans.__listTableTitle}} {{ postType }}</h2>
                        <!-- RELATED BUTTONS COMPONENT USED IN CMS NAVIGATION -->
                        <related-buttons v-if="$route.query.menu_link_id"></related-buttons>
                        <div class="clearfix"></div>
                    </div>

                    <div class="x_content">

                        <div class="bulk_action_btns">
                            <a class="btn btn-danger" :class="{'disabled':!Object.keys(bulkDeleteIDs).length}" @click="deleteList([$route.params.post_type,bulkDeleteIDs])" id="deleteList" v-if="hasDeletePermission">{{trans.__deleteBtn}}</a>
                        </div>

                        <pagination :listUrl="listUrl" :dataSearchUrl="dataSearchUrl" :advancedSearchPostUrl="advancedSearchPostUrl"></pagination>

                        <table id="datatable-checkbox" class="table table-striped table-bordered bulk_action">
                            <thead>
                                <tr class="tableHeader">
                                    <th>#</th>
                                    <th id="postID" @click="orderBy('postID')">{{trans.__id}} <i :class="tableHeaderOrderIcons('postID')" aria-hidden="true"></i></th>
                                    <th id="title" @click="orderBy('title')">{{trans.__globalTitle}} <i :class="tableHeaderOrderIcons('title')" aria-hidden="true"></i></th>

                                    <th :id="item.slug" @click="orderBy(item.slug)" v-for="(item, index) in columnNames">{{item.name}} <i :class="tableHeaderOrderIcons(item.slug)" aria-hidden="true"></i></th>

                                    <th>{{trans.__action}}</th>
                                </tr>
                            </thead>

                            <tr v-if="spinner">
                                <td colspan="7">
                                    <!-- Loading component -->
                                    <spinner :width="'30px'" :height="'30px'" :border="'5px'"></spinner>
                                </td>
                            </tr>

                            <tbody v-if="!spinner" dusk="postListComponent">
                                <tr v-for="(item, index) in getList.data">
                                    <th><input type="checkbox" :value="item.postID" v-model="bulkDeleteIDs" :id="'ID'+item.postID"></th>
                                    <th>{{ item.postID }}</th>
                                    <th>{{ item.title }}</th>

                                    <td v-for="(value, columnKey, columnIndex) in item" v-if="isInTable(columnKey)">{{ value }}</td>

                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-primary" @click="redirect('post-update', item.postID)" v-if="isOwner(item.createdByUserID, hasUpdatePermission)">{{trans.__updateBtn}}</button>
                                            <button type="button" class="btn disabled" v-if="!isOwner(item.createdByUserID, hasUpdatePermission)">{{trans.__updateBtn}}</button>
                                            <button type="button" class="btn btn-primary" @click="toggleListActionBar(index)">
                                                <span class="caret"></span>
                                            </button>
                                            <ul class="lists-action-bar-dropdown" v-if="index === openedItemActionBar">
                                                <li v-if="isOwner(item.createdByUserID, hasDeletePermission)">
                                                    <a style="cursor:pointer" @click="deleteItem(item.postID, index)">{{trans.__deleteBtn}}</a>
                                                </li>
                                                <li class="disabled" v-if="!isOwner(item.createdByUserID, hasDeletePermission)">
                                                    <a>{{ trans.__deleteBtn }}</a>
                                                </li>
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
    </div>
</template>
<script>
    import RelatedButtons from '../menu/RelatedButtons.vue'
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';
    import { lists } from '../../mixins/lists';
    import SimpleSearch from '../vendor/SimpleSearch.vue';
    import Pagination from '../vendor/Pagination.vue';
    import AdvancedSearch from './AdvancedSearch.vue';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated, lists],
        components:{
            'related-buttons':RelatedButtons,
            'simpleSearch': SimpleSearch,
            'pagination': Pagination,
            'advancedSearch':AdvancedSearch,
        },
        created(){
            this.viewSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/posts/search/'+this.$route.params.post_type+'/';
            this.dataSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/search/'+this.$route.params.post_type+'/';
            //this.advancedSearchOptionsUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/advancedSearch/'+this.$route.params.post_type+'/';
            this.advancedSearchPostUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/posts/advanced-search-results';
            this.deleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/delete/'+this.$route.params.post_type+'/';
            this.bulkDeleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/posts/bulk-delete';
            if(this.$route.query.category !== undefined){
                this.listUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/get-all-of-category/'+this.$route.params.post_type+'/'+this.$route.query.category;
            }else{
                this.listUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/get-all/'+this.$route.params.post_type;
            }
        },
        mounted() {
            // permissions
            this.hasAddPermission = this.hasPermission(this.$route.params.post_type, 'create');
            this.hasDeletePermission = this.hasPermission(this.$route.params.post_type, 'delete');
            this.hasUpdatePermission = this.hasPermission(this.$route.params.post_type, 'update');
            // translations
            this.trans = {
                __title: this.__('post.title'),
                __listTitle: this.__('post.listTitle'),
                __listTableTitle: this.__('post.listTableTitle'),
                __id: this.__('post.listTableColumns.id'),
                __globalTitle: this.__('base.title'),
                __advancedSearchBtn: this.__('base.advancedSearchBtn'),
                __action: this.__('base.action'),
                __addBtn: this.__('base.addBtn'),
                __deleteBtn: this.__('base.deleteBtn'),
                __updateBtn: this.__('base.updateBtn'),
                __previous: this.__('pagination.previous'),
                __next: this.__('pagination.next'),
            };

            // get the columns in from the post type table
            this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/'+ this.$route.params.post_type+'/columns')
                .then((resp) => {
                    this.columnNames = resp.body.column;
                    this.columnSlugs = resp.body.inTableColumnsSlugs;
                });

            if(this.$route.query.advancedSearch !== undefined){
                this.$router.push({ name: 'post-list', query: this.$route.query});
                this.isAdvancedSearchOpen = true;
            }else{
                this.getListData();
            }
        },
        data(){
            return{
                listUrl: '', // default list
                viewSearchUrl: '', // url to search view
                dataSearchUrl: '', // url to get the search data
                //advancedSearchOptionsUrl: '', // url to get the options for advanced search
                advancedSearchPostUrl: '', // url to get the advanced search result
                deleteUrl: '', // url to delete a item
                bulkDeleteUrl: '', // url to bul delete items
                isAdvancedSearchOpen: false,
                columnNames: '',
                columnSlugs: '',
                hasAddPermission: true,
                hasDeletePermission: true,
                hasUpdatePermission: true,
                advancedSearchData: '', // data of advanced search if it is made
                bulkDeleteIDs: [],  // all selected ids to be deleted from the list
                form:{  // data of advanced search
                   fields: [],  // search fields and parameters
                   pagination: '', // pagination number
                   orderBy: '', // if is ordered by a column
                   orderType: '' // order type which is ASC or DESC
                },
            }
        },
        methods: {
            // check if user is owner of the post
            isOwner(createdByUserID, hasPermission){
                if(this.getGlobalData.permissions.global !== undefined && this.getGlobalData.permissions.global.author !== undefined){
                    if(hasPermission){
                        if(this.getGlobalData.user.userID == createdByUserID){
                            return true;
                        }
                    }
                    return false;
                }
                return true;
            },
            // check if parameter should appear in table
            isInTable(slug){
                if(this.columnSlugs.indexOf(slug) == -1){
                    return false;
                }
                return true;
            },
        },
        computed:{
            postType(){
                let postType = this.$route.params.post_type;
                postType = postType.replace("post_", "");
                return postType;
            }
        },
        watch:{
            // watch for url changes and component doesn't change
            '$route': function(){
                if(this.$route.query.category !== undefined){
                    this.listUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/get-all-of-category/'+this.$route.params.post_type+'/'+this.$route.query.category;
                }else{
                    this.listUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/get-all/'+this.$route.params.post_type;
                }
                this.viewSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/posts/search/'+this.$route.params.post_type+'/';
                this.dataSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/search/'+this.$route.params.post_type+'/';
                //this.advancedSearchOptionsUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/advancedSearch/'+this.$route.params.post_type+'/';
                this.advancedSearchPostUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/posts/advanced-search-results';
                this.deleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/delete/'+this.$route.params.post_type+'/';
                this.bulkDeleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/posts/bulk-delete';

                // TODO me kshyr edhe niher qet kusht
                if(this.$route.query.advancedSearch === undefined){
                    this.getListData();
                }
            }
        },
    }
</script>
