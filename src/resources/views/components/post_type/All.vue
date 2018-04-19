<template>
    <div class="componentsWs">
        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}} <small>{{trans.__listTitle}}</small></h3>
                <a class="btn btn-primary pull-left addBtnMain" @click="redirect('post-type-create')" v-if="postTypeAddPermission">{{trans.__addBtn}}</a>
            </div>

            <div class="title_right">
                <!-- Simple search component -->
                <!--<simple-search :url="dataSearchUrl" :view="viewSearchUrl"></simple-search>-->
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
                            <a class="btn btn-danger" :class="{'disabled':!Object.keys(bulkDeleteIDs).length}" @click="deleteList()" id="deleteList" v-if="postTypeDeletePermission">{{trans.__deleteBtn}}</a>
                        </div>


                        <table id="datatable-checkbox" class="table table-striped table-bordered bulk_action">
                            <thead>
                            <tr class="tableHeader">
                                <th>#</th>
                                <th id="postTypeID" @click="orderBy('postTypeID')">{{trans.__id}} <i :class="tableHeaderOrderIcons('postTypeID')"  aria-hidden="true"></i></th>
                                <th id="name" @click="orderBy('name')">{{trans.__name}} <i :class="tableHeaderOrderIcons('name')"  aria-hidden="true"></i></th>
                                <th id="slug" @click="orderBy('slug')">{{trans.__slug}} <i :class="tableHeaderOrderIcons('slug')"  aria-hidden="true"></i></th>
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

                            <tr v-for="(item, index) in getList.data" v-if="!spinner" dusk="postTypeListComponent">
                                <th><input type="checkbox" :value="item.postTypeID" v-model="bulkDeleteIDs" :id="'ID'+item.postTypeID"></th>
                                <th>{{ item.postTypeID }}</th>
                                <td>{{ item.name }}</td>
                                <td>{{ item.slug }}</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary" @click="redirect('post-type-update', item.postTypeID)" v-if="postTypeUpdatePermission">{{trans.__updateBtn}}</button>
                                        <button type="button" class="btn disabled" v-if="!postTypeUpdatePermission">{{trans.__updateBtn}}</button>
                                        <button type="button" class="btn btn-primary" @click="toggleListActionBar(index)" :id="'toggleListBtn'+item.postTypeID">
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="lists-action-bar-dropdown" v-if="index === openedItemActionBar">
                                            <li v-if="categoriesReadPermission"><a style="cursor:pointer" @click="redirect('category-list', item.postTypeID)">{{trans.__categoryTitle}}</a></li>
                                            <li v-if="!categoriesReadPermission" class="disabled"><a>{{trans.__categoryTitle}}</a></li>

                                            <li v-if="tagsReadPermission"><a style="cursor:pointer" @click="redirect('tag-list', item.postTypeID)">{{trans.__tagsTitle}}</a></li>
                                            <li v-if="!tagsReadPermission" class="disabled"><a>{{trans.__tagsTitle}}</a></li>
                                            <li class="divider"></li>
                                            <li v-if="postTypeDeletePermission"><a style="cursor:pointer" :id="'deleteItemBtn'+item.postTypeID" @click="deleteItem(item.postTypeID, index)">{{trans.__deleteBtn}}</a></li>
                                            <li class="disabled" v-if="!postTypeDeletePermission"><a>{{trans.__deleteBtn}}</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>

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

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated, lists],
        components: {
            'simpleSearch': SimpleSearch,
        },
        created(){
            this.listUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/post-type/get-all';
            //this.viewSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/post-type/search/';
            //this.dataSearchUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/search/post-type/';
            this.deleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/post-type/delete/';
            this.bulkDeleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/post-type/bulk-delete';
        },
        mounted() {
            // permissions
            this.postTypeAddPermission = this.hasPermission('PostType','create');
            this.postTypeUpdatePermission = this.hasPermission('PostType','update');
            this.postTypeDeletePermission = this.hasPermission('PostType','delete');
            this.categoriesReadPermission = this.hasPermission('Categories','read');
            this.tagsReadPermission = this.hasPermission('Tags','read');

            // translations
            this.trans = {
                __title: this.__('postType.title'),
                __listTitle: this.__('postType.listTitle'),
                __categoryTitle: this.__('categories.title'),
                __tagsTitle: this.__('tags.title'),
                __listTableTitle: this.__('postType.listTableTitle'),
                __name: this.__('postType.listTableColumns.name'),
                __id: this.__('postType.listTableColumns.id'),
                __slug: this.__('base.slug'),
                __action: this.__('base.action'),
                __addBtn: this.__('base.addBtn'),
                __deleteBtn: this.__('base.deleteBtn'),
                __updateBtn: this.__('base.updateBtn'),
            };
            // get data for the table
            this.getListData();

        },
        data(){
            return{
                listUrl: '', // default list
                //viewSearchUrl: '', // url to search view
                //dataSearchUrl: '', // url to get the search data
                deleteUrl: '', // url to delete a item
                bulkDeleteUrl: '', // url to bulk delete items
                bulkDeleteIDs: [],  // all selected ids to be deleted from the list
                postTypeUpdatePermission: true,
                postTypeDeletePermission: true,
                categoriesReadPermission: true,
                postTypeAddPermission: true,
                tagsReadPermission: true,
                form:{  // data of advanced search
                   fields: [],  // search fields and parameters
                   page: '', // pagination number
                   orderBy: '', // if is ordered by a column
                   orderType: '' // order type which is ASC or DESC
                },
            }
        },
        watch:{
            // watch for url changes and component doesn't change
            '$route': function(){
                this.getListData();
            }
        },

    }
</script>
