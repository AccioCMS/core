<template>
    <div class="componentsWs">
        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}} <small>{{trans.__listTitle}}</small></h3>
                <a class="btn btn-primary pull-left addBtnMain" @click="redirect('custom-fields-create')" v-if="createPermission">{{trans.__addBtn}}</a>
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
                            <a class="btn btn-danger" :class="{'disabled':!Object.keys(bulkDeleteIDs).length}" @click="deleteList()" id="deleteList" v-if="deletePermission">{{trans.__deleteBtn}}</a>
                        </div>

                        <pagination :listUrl="listUrl"></pagination>

                        <table id="datatable-checkbox" class="table table-striped table-bordered bulk_action">
                            <thead>
                            <tr class="tableHeader">
                                <th>#</th>
                                <th id="customFieldID" @click="orderBy('customFieldGroupID')">{{trans.__id}} <i class="fa fa-long-arrow-down" aria-hidden="true"></i></th>
                                <th id="title" @click="orderBy('title')">{{trans.__tableTitle}}</th>
                                <th id="slug" @click="orderBy('slug')">{{trans.__slug}}</th>
                                <th>{{trans.__action}}</th>
                            </tr>
                            </thead>

                            <tr v-if="spinner">
                                <td colspan="7">
                                    <!-- Loading component -->
                                    <spinner :width="'30px'" :height="'30px'" :border="'5px'"></spinner>
                                </td>
                            </tr>

                            <tbody v-if="!spinner" dusk="customFieldsListComponent">
                                <tr v-for="(item, index) in getList.data">
                                    <th class="checkboxTh">
                                    <span class="checkBoxBuldDeleteContainer">
                                        <input type="checkbox" :value="item.customFieldGroupID" v-model="bulkDeleteIDs" :id="'ID'+item.customFieldGroupID">
                                    </span>
                                    </th>
                                    <th>{{ item.customFieldGroupID }}</th>
                                    <td>{{ item.title }}</td>
                                    <td>{{ item.slug }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-primary" @click="redirect('custom-fields-update', item.customFieldGroupID)" v-if="updatePermission">{{trans.__updateBtn}}</button>
                                            <button type="button" class="btn disabled" v-if="!updatePermission">{{trans.__updateBtn}}</button>
                                            <button type="button" class="btn btn-primary" @click="toggleListActionBar(index)" :id="'toggleListBtn'+item.customFieldGroupID">
                                                <span class="caret"></span>
                                            </button>
                                            <ul class="lists-action-bar-dropdown" v-if="index === openedItemActionBar">
                                                <li v-if="deletePermission"><a @click="deleteItem(item.customFieldGroupID, index)" :id="'deleteItemBtn'+item.customFieldGroupID">{{trans.__deleteBtn}}</a></li>
                                                <li v-if="!deletePermission" class="disabled"><a>{{trans.__deleteBtn}}</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <pagination :listUrl="listUrl"></pagination>

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
    import Pagination from '../vendor/Pagination.vue';
    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated, lists],
        components: {
            'pagination': Pagination,
        },
        created(){
            this.listUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/custom-fields/get-all';
            this.deleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/custom-fields/delete/';
            this.bulkDeleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/custom-fields/bulk-delete';

            // permissions
            this.createPermission = this.hasPermission('CustomFields','create');
            this.updatePermission = this.hasPermission('CustomFields','update');
            this.deletePermission = this.hasPermission('CustomFields','delete');

            // translations
            this.trans = {
                __slug: this.__('base.slug'),
                __title: this.__('customFields.title'),
                __listTitle: this.__('customFields.listTitle'),
                __listTableTitle: this.__('customFields.listTableTitle'),
                __id: this.__('customFields.listTableColumns.id'),
                __tableTitle: this.__('customFields.listTableColumns.title'),
                __action: this.__('base.action'),
                __addBtn: this.__('base.addBtn'),
                __deleteBtn: this.__('base.deleteBtn'),
                __updateBtn: this.__('base.updateBtn'),
                __previous: this.__('pagination.previous'),
                __next: this.__('pagination.next'),
            };
        },
        mounted() {
           this.getListData();
        },
        data(){
            return{
                listUrl: '',
                deleteUrl: '',
                bulkDeleteUrl: '',
                bulkDeleteIDs: [],  // all selected ids to be deleted from the list
                updatePermission: false,
                deletePermission: false,
                form:{  // data of advanced search
                   fields: [],  // search fields and parameters
                   page: '', // pagination number
                   orderBy: '', // if is ordered by a column
                   orderType: '' // order type which is ASC or DESC
                },
            }
        }
    }
</script>
