<template>
    <div class="componentsWs">

        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}} <small>{{trans.__listTitle}}</small></h3>
                <a class="btn btn-primary pull-left addBtnMain" @click="redirect('language-create')" v-if="addPermission">{{trans.__addBtn}}</a>
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
                            <a id="deleteList" class="btn btn-danger" :class="{'disabled':!Object.keys(bulkDeleteIDs).length}" v-if="hasDeletePermission" @click="deleteList()">{{trans.__deleteBtn}}</a>
                        </div>


                        <table id="datatable-checkbox" class="table table-striped table-bordered bulk_action">
                            <thead>
                            <tr class="tableHeader">
                                <th>#</th>
                                <th id="languageID" @click="orderBy('languageID')">{{trans.__id}} <i  :class="tableHeaderOrderIcons('languageID')" aria-hidden="true"></i></th>
                                <th id="name" @click="orderBy('name')">{{trans.__name}} <i  :class="tableHeaderOrderIcons('name')" aria-hidden="true"></i></th>
                                <th id="isDefault">{{trans.__isDefault}}</th>
                                <th id="slug" @click="orderBy('slug')">{{trans.__slug}} <i  :class="tableHeaderOrderIcons('slug')" aria-hidden="true"></i></th>
                                <th>{{trans.__action}}</th>
                            </tr>
                            </thead>

                            <tr v-if="spinner">
                                <td colspan="7">
                                    <!-- Loading component -->
                                    <spinner :width="'30px'" :height="'30px'" :border="'5px'"></spinner>
                                </td>
                            </tr>

                            <tbody v-if="!spinner" dusk="languageListComponent">
                            <tr v-for="(item, index) in getList.data">
                                <th><input type="checkbox" :value="item.languageID" v-model="bulkDeleteIDs" :id="'ID'+item.languageID"></th>
                                <th>{{ item.languageID }}</th>
                                <td>{{ item.name }}</td>
                                <td>{{ item.isDefault }}</td>
                                <td>{{ item.slug }}</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary" @click="redirect('language-update', item.languageID)" v-if="hasUpdatePermission">{{trans.__updateBtn}}</button>
                                        <button type="button" class="btn disabled" v-if="!hasUpdatePermission">{{trans.__updateBtn}}</button>
                                        <button type="button" class="btn btn-primary" @click="toggleListActionBar(index)" :id="'toggleListBtn'+item.languageID">
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="lists-action-bar-dropdown" v-if="index === openedItemActionBar">
                                            <li><a v-if="hasDeletePermission" :id="'deleteItemBtn'+item.languageID" style="cursor:pointer" @click="deleteItem(item.languageID, index)">{{trans.__deleteBtn}}</a></li>
                                            <li class="disabled" v-if="!hasDeletePermission"><a>{{trans.__deleteBtn}}</a></li>
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

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated, lists],
        created(){
            this.listUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/language/get-all';
            this.deleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/language/delete/';
            this.bulkDeleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/language/bulk-deletes';
        },
        mounted() {
            // translations
            this.trans = {
                __title: this.__('language.title'),
                __listTableTitle: this.__('language.listTableTitle'),
                __name: this.__('language.listTableColumns.name'),
                __id: this.__('language.listTableColumns.id'),
                __slug: this.__('language.listTableColumns.slug'),
                __isDefault: this.__('language.listTableColumns.isDefault'),
                __action: this.__('base.action'),
                __addBtn: this.__('base.addBtn'),
                __deleteBtn: this.__('base.deleteBtn'),
                __updateBtn: this.__('base.updateBtn'),
            };

            // permissions
            this.addPermission = this.hasPermission('Language','create');
            this.hasDeletePermission = this.hasPermission('Language','delete');
            this.hasUpdatePermission = this.hasPermission('Language','update');

            // get the table data
            this.getListData();

        },
        props:[],
        data(){
            return{
                listUrl: '', // default list
                deleteUrl: '', // url to delete a item
                bulkDeleteUrl: '', // url to bul delete items
                bulkDeleteIDs: [],  // all selected ids to be deleted from the list
                addPermission: false,
                hasDeletePermission: false,
                hasUpdatePermission: false,
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
