<template>
    <div class="componentsWs" dusk="categoryListComponent">
        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}} <small>{{trans.__listTitle}}</small></h3>
                <a class="btn btn-primary pull-left addBtnMain" @click="redirect('category-create')" v-if="categoriesAddPermission">{{trans.__addBtn}}</a>
            </div>

            <div class="title_right">
                <!-- Simple search component -->
                <simple-search :url="dataSearchUrl" :view="viewSearchUrl"></simple-search>
            </div>

        </div>
        <!-- TITLE END -->
        <div class="clearfix"></div>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>{{trans.__listTableTitle}}</h2>
                        <div class="clearfix"></div>
                    </div>

                    <div class="x_content">

                        <div class="bulk_action_btns">
                            <a class="btn btn-danger" :class="{'disabled':!Object.keys(bulkDeleteIDs).length}" @click="deleteList()" id="deleteList" v-if="categoriesDeletePermission">{{trans.__deleteBtn}}</a>
                        </div>

                        <pagination :listUrl="listUrl" :dataSearchUrl="dataSearchUrl"></pagination>

                        <table id="datatable-checkbox" class="table table-striped table-bordered bulk_action">
                            <thead>
                            <tr class="tableHeader">
                                <th>#</th>
                                <th id="categoryID" @click="orderBy('categoryID')">{{trans.__id}} <i :class="tableHeaderOrderIcons('categoryID')" aria-hidden="true"></i></th>
                                <th id="title" @click="orderBy('title')">{{trans.__columnTitle}} <i :class="tableHeaderOrderIcons('title')" aria-hidden="true"></i></th>
                                <th id="slug" @click="orderBy('slug')">{{trans.__slug}} <i :class="tableHeaderOrderIcons('slug')" aria-hidden="true"></i></th>
                                <th>{{ trans.__action }}</th>
                            </tr>
                            </thead>


                            <tr v-if="spinner">
                                <td colspan="7">
                                    <!-- Loading component -->
                                    <spinner :width="'30px'" :height="'30px'" :border="'5px'"></spinner>
                                </td>
                            </tr>

                            <tbody class="sortable" v-if="!spinner">
                                <tr v-for="(item, index) in getList.data">
                                    <th class="checkboxTh">
                                        <span class="checkBoxBuldDeleteContainer">
                                            <input type="checkbox" :value="item.categoryID" v-model="bulkDeleteIDs" :id="'ID'+item.categoryID">
                                        </span>
                                        <span class="handleSort">
                                            <i class="fa fa-sort" aria-hidden="true"></i>
                                        </span>
                                    </th>
                                    <th>{{ item.categoryID }}</th>
                                    <td>{{ item.title }}</td>
                                    <td>{{ item.slug }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-primary" @click="redirect('category-update', item.categoryID)" v-if="categoriesUpdatePermission">{{trans.__updateBtn}}</button>
                                            <button type="button" class="btn disabled" v-if="!categoriesUpdatePermission">{{trans.__updateBtn}}</button>

                                            <button type="button" class="btn btn-primary" @click="toggleListActionBar(index)" :id="'toggleListBtn'+item.categoryID">
                                                <span class="caret"></span>
                                            </button>
                                            <ul class="lists-action-bar-dropdown" v-if="index === openedItemActionBar">
                                                <li>
                                                    <router-link
                                                            tag="a"
                                                            style="cursor:pointer"
                                                            :to="postsUrl+'?category='+item.categoryID">{{trans.__posts}}
                                                    </router-link>
                                                </li>
                                                <li class="divider"></li>
                                                <li v-if="categoriesDeletePermission"><a style="cursor:pointer" :id="'deleteItemBtn'+item.categoryID" @click="deleteItem(item.categoryID, index)">{{trans.__deleteBtn}}</a></li>
                                                <li v-if="!categoriesDeletePermission" class="disabled"><a>{{trans.__deleteBtn}}</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <pagination :listUrl="listUrl" :dataSearchUrl="dataSearchUrl"></pagination>

                    </div>
                </div>
            </div>

            <div class="clearfix"></div>

        </div>
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

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated, lists],
        components: {
            'simpleSearch': SimpleSearch,
            'pagination': Pagination,
        },
        created(){
            this.listUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/category/get-all/'+this.$route.params.id;
            this.viewSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/post-type/category/'+this.$route.params.id+'/search/';
            this.dataSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/category/'+this.$route.params.id+'/search/';
            this.deleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/category/delete/';
            this.bulkDeleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/category/bulk-delete';
        },
        mounted() {
            // permissions
            this.categoriesAddPermission = this.hasPermission('Categories','create');
            this.categoriesUpdatePermission = this.hasPermission('Categories','update');
            this.categoriesDeletePermission = this.hasPermission('Categories','delete');
            // translations
            this.trans = {
                __title: this.__('categories.title'),
                __listTitle: this.__('categories.listTitle'),
                __listTableTitle: this.__('categories.listTableTitle'),
                __id: this.__('categories.listTableColumns.id'),
                __columnTitle: this.__('categories.listTableColumns.title'),
                __slug: this.__('categories.listTableColumns.slug'),
                __order: this.__('categories.listTableColumns.order'),
                __posts: this.__('categories.posts'),
                __action: this.__('base.action'),
                __deleteBtn: this.__('base.deleteBtn'),
                __updateBtn: this.__('base.updateBtn'),
                __addBtn: this.__('base.addBtn'),
                __description: this.__('base.description'),
            };

            // get the columns in from the post type table
            this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/post-type/get-by-id/'+ this.$route.params.id)
                .then((resp) => {
                    this.postType = resp.body;
                    this.postsUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/posts/'+this.postType.slug+'/list';
                });

            // get data for the table
            this.getListData().then(resp => {
                var global = this;
                setTimeout(function(){
                    $(document).ready(function(){
                        $('tbody.sortable').sortable({
                            handle: '.handleSort',
                            update: global.orderRows
                        });
                    });
                },3000)
            });
        },
        data(){
            return{
                listUrl: '', // default list
                viewSearchUrl: '', // url to search view
                dataSearchUrl: '', // url to get the search data
                deleteUrl: '', // url to delete a item
                bulkDeleteUrl: '', // url to bulk delete items
                postsUrl: '',
                postType: '',
                bulkDeleteIDs: [],  // all selected ids to be deleted from the list
                categoriesAddPermission: false, // has create permission
                categoriesUpdatePermission: false, // has update permission
                categoriesDeletePermission: false, // has delete permission
                form:{  // data of advanced search
                   fields: [],  // search fields and parameters
                   pagination: '', // pagination number
                   orderBy: '', // if is ordered by a column
                   orderType: '' // order type which is ASC or DESC
                },
            }
        },
        methods: {
            // method responsible to sort (re-order) the list
            orderRows( event, ui ) {
                console.log("order updated");
                this.$store.dispatch('sort', {url: this.basePath+'/'+this.getAdminPrefix+'/json/category/sort', error: "List could not be sort"});
            },
        },
        watch:{
            // watch for url changes and component doesn't change
            '$route': function(){
                this.listUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/category/get-all/'+this.$route.params.id;
                this.viewSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/post-type/category/'+this.$route.params.id+'/search/';
                this.dataSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/category/'+this.$route.params.id+'/search/';
                this.deleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/category/delete/';
                this.bulkDeleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/category/bulk-delete';
                this.getListData();
            }
        },
    }
</script>
