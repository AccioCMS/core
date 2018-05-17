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



                        <div class="clearfix"></div>

                        <div class="row table table-striped table-bordered bulk_action">
                            <div class="col-md-12 col-lg-12">
                                <div class="row tableHeader tr">
                                    <div class="col-xs-1 th">#</div>
                                    <div class="col-xs-2 th">{{trans.__id}} <i :class="tableHeaderOrderIcons('categoryID')" aria-hidden="true"></i></div>
                                    <div class="col-xs-3 th">{{trans.__columnTitle}} <i :class="tableHeaderOrderIcons('title')" aria-hidden="true"></i></div>
                                    <div class="col-xs-3 th">{{trans.__slug}} <i :class="tableHeaderOrderIcons('slug')" aria-hidden="true"></i></div>
                                    <div class="col-xs-2 th">{{ trans.__action }}</div>
                                </div>

                                <div class="row tableBody">
                                    <category-item
                                            v-for="(item, index) in getList.data"
                                            :item="item"
                                            :key="index"
                                            :index="index"
                                            countLayer="0"
                                            :bulkDeleteIDs="bulkDeleteIDs"
                                            :categoriesUpdatePermission="categoriesUpdatePermission"
                                            :categoriesDeletePermission="categoriesDeletePermission"
                                            :trans="trans"
                                            :openedItemActionBar="openedItemActionBar"
                                            :postsUrl="postsUrl"
                                            v-on:toggleActionBar="toggleListActionBar"
                                            v-on:redirect="redirect"
                                            v-on:deleteItem="deleteConfirmDialog">
                                    </category-item>
                                </div>
                            </div>
                        </div>

                        <pagination :listUrl="listUrl" :dataSearchUrl="dataSearchUrl"></pagination>

                    </div>
                </div>
            </div>

            <div class="clearfix"></div>

        </div>

        <!-- MODAL -->
        <div class="modal" style="display: block; z-index: 9999;" tabindex="-1" role="dialog" aria-hidden="true" v-if="modalOpen">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">

                    <div class="modal-header">
                        <button type="button" class="close" @click="modalOpen = false" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                        <h4 class="modal-title" id="myModalLabel2">{{trans.__confirmBtn}}</h4>
                    </div>
                    <div class="modal-body">
                        <h4>{{trans.__sure}}</h4>
                        <p id="confirmDialogMsg">{{ trans.__sureToDeleteMsg }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal" @click="modalOpen = false">{{trans.__closeBtn}}</button>
                        <button type="button" class="btn btn-danger" @click="confirmDelete()">{{trans.__deleteBtn}}</button>
                    </div>

                </div>
            </div>
        </div>
        <!-- MODAL -->

    </div>
</template>
<style scoped>
    .tableHeader{
        border-bottom: 1px solid #DDD;
        padding-top: 10px;
        padding-bottom: 10px;
        margin-bottom: 10px;
    }
</style>
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
            this.listUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/category/get-tree/'+this.$route.params.id;
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
                __closeBtn: this.__('base.closeBtn'),
                __sure: this.__('categories.sureToDelete'),
                __sureToDeleteMsg: this.__('categories.sureToDeleteMsg'),
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
                modalOpen: false,
                categoryToBeDeleted: {id: 0, index: 0},
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
            // TODO (DELETE) qiky funksion nuk perdoret prejsi u bo parent child relation ne category
            orderRows( event, ui ){
                this.$store.dispatch('sort', {url: this.basePath+'/'+this.getAdminPrefix+'/json/category/sort', error: "List could not be sort"});
            },
            deleteConfirmDialog(id, index){
                this.categoryToBeDeleted = {id: id, index: index};
                this.modalOpen = true;
            },
            confirmDelete(){
                this.modalOpen = false;
                this.deleteItem(this.categoryToBeDeleted.id, this.categoryToBeDeleted.index);
            }
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
