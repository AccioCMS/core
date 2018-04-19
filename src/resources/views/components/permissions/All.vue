<template>
    <div class="componentsWs">
        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}} <small>{{trans.__listTitle}}</small></h3>
                <a class="btn btn-primary pull-left addBtnMain" @click="redirect('permission-edit', 0)">{{trans.__addBtn}}</a>
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
                            <a class="btn btn-danger" @click="deleteList()">{{trans.__deleteBtn}}</a>
                        </div>

                        <table id="datatable-checkbox" class="table table-striped table-bordered bulk_action">
                            <thead>
                            <tr class="tableHeader">
                                <th>#</th>
                                <th id="name" @click="orderBy('name')">{{trans.__name}} <i class="fa fa-long-arrow-down" aria-hidden="true"></i></th>
                                <th id="slug" @click="orderBy('slug')">{{trans.__slug}} <i class="fa fa-long-arrow-down" aria-hidden="true"></i></th>
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

                            <tr v-for="(item, index) in getList.data" v-if="!spinner">
                                <th><input type="checkbox" :value="item.groupID" v-model="bulkDeleteIDs"></th>
                                <td>{{ item.name }}</td>
                                <td>{{ item.slug }}</td>
                                <td>
                                    <div class="btn-group">
                                        <button v-if="item.isDefault == 0" type="button" class="btn btn-primary" @click="redirect('permission-edit', item.groupID)">{{trans.__updateBtn}}</button>
                                        <button v-if="item.isDefault == 1" type="button" class="btn disabled">{{trans.__updateBtn}}</button>

                                        <button type="button" class="btn btn-primary" @click="toggleListActionBar(index)">
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="lists-action-bar-dropdown" v-if="index === openedItemActionBar">
                                            <li  v-if="item.isDefault == 0">
                                                <a @click="deleteItem(item.groupID, index)">{{trans.__deleteBtn}}</a>
                                            </li>
                                            <li class="disabled" v-if="item.isDefault == 1">
                                                <a>{{trans.__deleteBtn}}</a>
                                            </li>
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
            this.listUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/permissions/users-groups';
            this.deleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/permissions/delete';
            this.bulkDeleteUrl = this.basePath+'/'+this.$route.params.adminPrefix+'/json/permissions/bulk-delete';
        },
        mounted() {
            this.getListData();
            // translations
            this.trans = {
                __title: this.__('Permissions.title'),
                __listTitle: this.__('Permissions.listTitle'),
                __listTableTitle: this.__('Permissions.listTableTitle'),
                __name: this.__('Permissions.listTableColumns.name'),
                __id: this.__('Permissions.listTableColumns.id'),
                __slug: this.__('Permissions.listTableColumns.slug'),
                __action: this.__('base.action'),
                __deleteBtn: this.__('base.deleteBtn'),
                __updateBtn: this.__('base.updateBtn'),
                __addBtn: this.__('base.addBtn'),
            };
        },
        data(){
            return{
                listUrl: '', // default list
                deleteUrl: '', // url to delete a item
                bulkDeleteUrl: '', // url to bulk delete items
                bulkDeleteIDs: [],  // all selected ids to be deleted from the list
            }
        },
    }
</script>
