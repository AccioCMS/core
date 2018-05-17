<template>
    <div class="col-md-12" style="margin: 0; padding: 0;">
        <div class="col-xs-1 th">
            <span class="checkBoxBuldDeleteContainer">
                <input type="checkbox" :value="item.categoryID" @click="bulkDeleteID(item.categoryID)" :id="'ID'+item.categoryID">
            </span>
        </div>
        <div class="col-xs-2 td">{{ item.categoryID }}</div>
        <div class="col-xs-3 td" :style="'padding-left:'+ margin + 'px;'"><i class="fa fa-level-up" v-if="parseInt(countLayer)"></i> {{ item.title }}</div>
        <div class="col-xs-3 td">{{ item.slug }}</div>
        <div class="col-xs-2 td" style="margin-bottom:10px;">
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
        </div>

        <hr>

        <category-item
                v-for="(item, childIndex) in item.children"
                :item="item"
                :key="childIndex"
                :index="childIndex"
                :countLayer="childLevel"
                :bulkDeleteIDs="bulkDeleteIDs"
                :categoriesUpdatePermission="categoriesUpdatePermission"
                :categoriesDeletePermission="categoriesDeletePermission"
                :trans="trans"
                :openedItemActionBar="openedItemActionBar"
                :postsUrl="postsUrl"
                v-on:toggleActionBar="toggleListActionBar"
                v-on:redirect="redirect"
                v-on:deleteItem="deleteItem">
        </category-item>

    </div>
</template>
<style scoped>
    i.fa-level-up{
        transform: rotate(90deg);
        margin-right: 5px;
        font-size: 15px;
    }
    hr{
        clear: both;
        margin-top: 5px;
    }
    .checkBoxBuldDeleteContainer input{
        margin: 0;
    }

</style>
<script>
    export default {
        props:['item', 'index', 'bulkDeleteIDs', 'categoriesUpdatePermission', 'categoriesDeletePermission', 'trans', 'openedItemActionBar', 'postsUrl', 'countLayer'],
        created(){
            this.childLevel = parseInt(this.countLayer) + 1;
            this.margin = 10 * this.childLevel;
        },
        data(){
            return {
                childLevel: 0,
                margin: 0,
            }
        },
        methods:{
            // toggle the action bar in tables (when listing items)
            toggleListActionBar(index){
                this.$emit("toggleActionBar", index);
            },
            // toggle the action bar in tables (when listing items)
            deleteItem(id, index){
                this.$emit("deleteItem", id, index);
            },
            redirect(type, categoryID){
                this.$emit("redirect", type, categoryID);
            },
            bulkDeleteID(id){
                if(this.bulkDeleteIDs.indexOf(id) == -1){
                    this.bulkDeleteIDs.push(id);
                }else{
                    let index = this.bulkDeleteIDs.indexOf(id);
                    this.bulkDeleteIDs.splice(index, 1);
                }
            }
        }
    }
</script>