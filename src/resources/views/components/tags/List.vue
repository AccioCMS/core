<template>
    <div class="componentsWs" dusk="tagListComponent">
        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}} <small>{{trans.__listTitle}}</small></h3>
                <a class="btn btn-primary pull-left addBtnMain" @click="redirect('tag-create')" v-if="tagsCreatePermission">{{trans.__addBtn}}</a>
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
                            <a class="btn btn-danger" :class="{'disabled':!Object.keys(bulkDeleteIDs).length}" @click="deleteList()" id="deleteList">{{trans.__deleteBtn}}</a>
                        </div>

                        <pagination :listUrl="listUrl" :dataSearchUrl="dataSearchUrl"></pagination>

                        <table id="datatable-checkbox" class="table table-striped table-bordered bulk_action">
                            <thead>
                            <tr class="tableHeader">
                                <th>#</th>
                                <th id="tagID" @click="orderBy('tagID')">{{trans.__id}} <i :class="tableHeaderOrderIcons('tagID')" aria-hidden="true"></i></th>
                                <th id="title" @click="orderBy('title')">{{trans.__columnTitle}} <i :class="tableHeaderOrderIcons('title')" aria-hidden="true"></i></th>
                                <th id="slug" @click="orderBy('slug')">{{trans.__slug}} <i :class="tableHeaderOrderIcons('slug')" aria-hidden="true"></i></th>
                                <th>{{trans.__action}}</th>
                            </tr>
                            </thead>


                            <tr v-if="spinner">
                                <td colspan="7">
                                    <!-- Loading component -->
                                    <spinner :width="'30px'" :height="'30px'" :border="'5px'"></spinner>
                                </td>
                            </tr>

                            <tbody v-if="!spinner">
                                <tr v-for="(item, index) in getList.data">
                                    <th class="checkboxTh">
                                        <span class="checkBoxBuldDeleteContainer">
                                            <input type="checkbox" :value="item.tagID" v-model="bulkDeleteIDs" class="bulkDeleteCheckbox" :id="'ID'+item.tagID">
                                        </span>
                                    </th>
                                    <th>{{ item.tagID }}</th>
                                    <td>{{ item.title }}</td>
                                    <td>{{ item.slug }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-primary" @click="redirect('tag-update', item.tagID)" v-if="tagsUpdatePermission">{{trans.__updateBtn}}</button>
                                            <button type="button" class="btn disabled" v-if="!tagsUpdatePermission">{{trans.__updateBtn}}</button>
                                            <button type="button" class="btn btn-primary" @click="toggleListActionBar(index)" :id="'toggleListBtn'+item.tagID">
                                                <span class="caret"></span>
                                            </button>
                                            <ul class="lists-action-bar-dropdown" v-if="index === openedItemActionBar">
                                                <li v-if="tagsDeletePermission"><a style="cursor:pointer" :id="'deleteItemBtn'+item.tagID" @click="deleteItem(item.tagID, index)">{{trans.__deleteBtn}}</a></li>
                                                <li v-if="!tagsDeletePermission" class="disabled"><a>{{trans.__deleteBtn}}</a></li>
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
            this.listUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/tags/get-all/'+this.$route.params.id;
            this.viewSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/post-type/tags/'+this.$route.params.id+'/search/';
            this.dataSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/tags/'+this.$route.params.id+'/search/';
            this.deleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/tags/delete/';
            this.bulkDeleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/tags/bulk-delete';
        },
        mounted() {
            // permissions
            this.tagsCreatePermission = this.hasPermission('Tags','create');
            this.tagsUpdatePermission = this.hasPermission('Tags','update');
            this.tagsDeletePermission = this.hasPermission('Tags','delete');
            // translations
            this.trans = {
                __title: this.__('tags.title'),
                __listTitle: this.__('tags.listTitle'),
                __listTableTitle: this.__('tags.listTableTitle'),
                __id: this.__('tags.listTableColumns.id'),
                __columnTitle: this.__('tags.listTableColumns.title'),
                __slug: this.__('tags.listTableColumns.slug'),
                __order: this.__('tags.listTableColumns.order'),
                __posts: this.__('tags.posts'),
                __action: this.__('base.action'),
                __description: this.__('base.description'),
                __deleteBtn: this.__('base.deleteBtn'),
                __updateBtn: this.__('base.updateBtn'),
                __addBtn: this.__('base.addBtn'),
                __previous: this.__('pagination.previous'),
                __next: this.__('pagination.next'),
            };
            // get data for the table
            this.getListData();
        },
        props:[],
        data(){
            return{
                listUrl: '', // default list
                viewSearchUrl: '', // url to search view
                dataSearchUrl: '', // url to get the search data
                deleteUrl: '', // url to delete a item
                bulkDeleteUrl: '', // url to bulk delete items
                bulkDeleteIDs: [],  // all selected ids to be deleted from the list
                tagsCreatePermission: false, // create permission
                tagsUpdatePermission: false, // update permission
                tagsDeletePermission: false, // delete permission
                form:{  // data of advanced search
                   fields: [],  // search fields and parameters
                   pagination: '', // pagination number
                   orderBy: '', // if is ordered by a column
                   orderType: '' // order type which is ASC or DESC
                },
            }
        },
        watch:{
            // watch for url changes and component doesn't change
            '$route': function(){
                this.listUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/tags/get-all/'+this.$route.params.id;
                this.viewSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/post-type/tags/'+this.$route.params.id+'/search/';
                this.dataSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/tags/'+this.$route.params.id+'/search/';
                this.deleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/tags/delete/';
                this.bulkDeleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/tags/bulk-delete';
                this.getListData();
            }
        },

    }
</script>
