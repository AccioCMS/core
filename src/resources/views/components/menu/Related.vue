<template>
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="col-md-4 col-sm-4 col-xs-4">
                <div class="x_panel">
                    <div class="x_content">

                        <!-- start accordion -->
                        <div class="accordion" id="accordion1" role="tablist" aria-multiselectable="true">

                            <!-- POST TYPES -->
                            <div class="panel">
                                <a class="panel-heading collapsed" @click="activatePanel('postType')">
                                    <h4 class="panel-title">{{trans.__postTypesTitle}}</h4>
                                </a>
                                <div v-show="activePanel == 'postType'">
                                    <div class="panel-body">
                                        <div class="" role="tabpanel" data-example-id="togglable-tabs">
                                            <ul id="postTypeTab" class="nav nav-tabs bar_tabs" role="tablist">
                                                <li role="presentation" class="active">
                                                    <a href="#postTypeRecent-tab1" id="postTypeRecent-tab" role="tab" data-toggle="tab" aria-expanded="true">{{trans.__mostRecent}}</a>
                                                </li>
                                                <li role="presentation" class="">
                                                    <a href="#postTypeRecent-tab2" role="tab" id="postTypeSearch-tab" data-toggle="tab" aria-expanded="false">{{trans.__search}}</a>
                                                </li>
                                            </ul>
                                            <div id="postTypeTabContent" class="tab-content">
                                                <div role="tabpanel" class="tab-pane fade active in" id="postTypeRecent-tab1" aria-labelledby="postTypeRecent-tab">

                                                    <div class="tableContainer">
                                                        <table class="table table-striped">
                                                            <thead>
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>{{trans.__globalName}}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr v-for="(postTypes, index) in recentPostTypes">
                                                                    <td><input type="checkbox" :value="postTypes" v-model="selectedPostTypes"></td>
                                                                    <td>{{ postTypes.name }}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <button class="btn btn-default selectLinkBtn" @click="addLinkInMenu('postTypes')">{{trans.__globalSelect}}</button>
                                                    <div class="clearfix"></div>
                                                </div>
                                                <div role="tabpanel" class="tab-pane fade" id="postTypeRecent-tab2" aria-labelledby="profile-tab">
                                                    <input type="search" class="form-control" placeholder="Post type title" @keyup="searchLink($event, 'postTypes')" v-model="postTypeSearchTerm">
                                                    <div class="tableContainer" v-if="searchedPostTypesResult != ''">
                                                        <table class="table table-striped">
                                                            <thead>
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>{{ trans.__globalName }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr v-for="(postTypes, index) in searchedPostTypesResult">
                                                                    <td><input type="checkbox" :value="postTypes" v-model="selectedPostTypes"></td>
                                                                    <td>{{ postTypes.name }}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <button class="btn btn-default selectLinkBtn" @click="addLinkInMenu('postTypes')">Select</button>

                                                    <div class="paginationBtns">
                                                        <button class="btn btn-default selectLinkBtn" @click="updatePagination('postTypes', 'next')">{{trans.__next}} <i class="fa fa-arrow-right"></i></button>
                                                        <button class="btn btn-default selectLinkBtn" @click="updatePagination('postTypes', 'prev')"><i class="fa fa-arrow-left"></i> {{trans.__previous}} </button>
                                                    </div>
                                                    <div class="clearfix"></div>

                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- CATEGORIES -->
                            <div class="panel">
                                <a class="panel-heading collapsed" @click="activatePanel('category')">
                                    <h4 class="panel-title">{{trans.__categoriesTitle}}</h4>
                                </a>
                                <div v-show="activePanel == 'category'">
                                    <div class="panel-body">
                                        <div class="" role="tabpanel" data-example-id="togglable-tabs">
                                            <ul id="categoriesTab" class="nav nav-tabs bar_tabs" role="tablist">
                                                <li role="presentation" class="active">
                                                    <a href="#categoriesRecent-tab1" id="categoriesRecent-tab" role="tab" data-toggle="tab" aria-expanded="true">{{trans.__mostRecent}}</a>
                                                </li>
                                                <li role="presentation" class="">
                                                    <a href="#categoriesRecent-tab2" role="tab" id="categoriesSearch-tab" data-toggle="tab" aria-expanded="false">{{trans.__search}}</a>
                                                </li>
                                            </ul>
                                            <div id="categoriesTabContent" class="tab-content">
                                                <div role="tabpanel" class="tab-pane fade active in" id="categoriesRecent-tab1" aria-labelledby="postTypeRecent-tab">

                                                    <div class="tableContainer">
                                                        <table class="table table-striped">
                                                            <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>{{trans.__globalName}}</th>
                                                                <th>{{trans.__postTypeTitle}}</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr v-for="(categories, index) in recentCategories">
                                                                    <td><input type="checkbox" :value="categories" v-model="selectedCategories"></td>
                                                                    <td v-if="categories.title[$route.params.lang] !== undefined">{{ categories.title[$route.params.lang] }}</td>
                                                                    <td v-else>{{ categories.title }}</td>
                                                                    <td>{{ categories.name }}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <button class="btn btn-default selectLinkBtn" @click="addLinkInMenu('categories')">{{trans.__globalSelect}}</button>
                                                    <div class="clearfix"></div>
                                                </div>
                                                <div role="tabpanel" class="tab-pane fade" id="categoriesRecent-tab2" aria-labelledby="profile-tab">
                                                    <input type="search" class="form-control" placeholder="Categories name" @keyup="searchLink($event, 'categories')" v-model="categoriesSearchTerm">
                                                    <div class="tableContainer" v-if="searchedCategoriesResult != ''">
                                                        <table class="table table-striped">
                                                            <thead>
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>{{trans.__globalName}}</th>
                                                                    <th>{{trans.__postTypeTitle}}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                            <tr v-for="(categories, index) in searchedCategoriesResult">
                                                                <td><input type="checkbox" :value="categories" v-model="selectedCategories"></td>
                                                                <td>{{ categories.title }}</td>
                                                                <td>{{ categories.name }}</td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <button class="btn btn-default selectLinkBtn" @click="addLinkInMenu('categories')">{{trans.__globalSelect}}</button>

                                                    <div class="paginationBtns">
                                                        <button class="btn btn-default selectLinkBtn" @click="updatePagination('categories', 'next')">{{trans.__next}} <i class="fa fa-arrow-right"></i></button>
                                                        <button class="btn btn-default selectLinkBtn" @click="updatePagination('categories', 'prev')"><i class="fa fa-arrow-left"></i> {{trans.__previous}} </button>
                                                    </div>
                                                    <div class="clearfix"></div>

                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- POSTS -->
                            <div class="panel" v-for="(postType, index) in recentPostTypes">
                                <a class="panel-heading collapsed" @click="activatePanel(postType.slug, 'posts')">
                                    <h4 class="panel-title">{{ postType.name }}</h4>
                                </a>
                                <div v-show="activePanel == postType.slug">
                                    <div class="panel-body">
                                        <div>
                                            <ul :id="postType.name+'Tab'" class="nav nav-tabs bar_tabs" role="tablist">
                                                <li role="presentation" class="active">
                                                    <a :href="'#'+postType.slug+'Recent-tab1'" :id="postType.slug+'Recent-tab'" role="tab" data-toggle="tab" aria-expanded="true">{{trans.__mostRecent}}</a>
                                                </li>
                                                <li role="presentation" class="">
                                                    <a :href="'#'+postType.slug+'Recent-tab2'" role="tab" :id="postType.slug+'Search-tab'" data-toggle="tab" aria-expanded="false">{{trans.__search}}</a>
                                                </li>
                                            </ul>
                                            <div :id="postType.slug+'TabContent'" class="tab-content">
                                                <div role="tabpanel" class="tab-pane fade active in" :id="postType.slug+'Recent-tab1'" :aria-labelledby="postType.slug+'Recent-tab'">

                                                    <div class="tableContainer">
                                                        <table class="table table-striped">
                                                            <thead>
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>{{trans.__globalName}}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr v-for="(posts, i) in postType.posts">
                                                                    <td><input type="checkbox" :value="posts" v-model="selectedPosts" :data-type="postType.name" :data-id="postType.postTypeID" class="postsCheckboxes"></td>
                                                                    <td v-if="posts.title[$route.params.lang] !== undefined">{{ posts.title[$route.params.lang] }}</td>
                                                                    <td v-else>{{ posts.title }}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <button class="btn btn-default selectLinkBtn" @click="addLinkInMenu(postType.slug)">{{trans.__globalSelect}}</button>
                                                    <div class="clearfix"></div>
                                                </div>
                                                <div role="tabpanel" class="tab-pane fade" :id="postType.slug+'Recent-tab2'" aria-labelledby="profile-tab">
                                                    <input type="search" class="form-control" :placeholder="postType.name+' name'" @keyup="searchLink($event, postType.slug)" v-model="postsSearchTerm[postType.slug]">
                                                    <div class="tableContainer" v-if="getSearchedPostsResult[postType.slug] != ''">
                                                        <table class="table table-striped">
                                                            <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>{{trans.__globalName}}</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            <tr v-for="(posts, index) in getSearchedPostsResult[postType.slug]">
                                                                <td><input type="checkbox" :value="posts" v-model="selectedPosts" :data-type="postType.name" :data-id="postType.postTypeID" class="postsCheckboxes"></td>
                                                                <td>{{ posts.title }}</td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <button class="btn btn-default selectLinkBtn" @click="addLinkInMenu('posts')">{{trans.__globalSelect}}</button>

                                                    <div class="paginationBtns">
                                                        <button class="btn btn-default selectLinkBtn" @click="updatePagination(postType.slug, 'next')">{{trans.__next}} <i class="fa fa-arrow-right"></i></button>
                                                        <button class="btn btn-default selectLinkBtn" @click="updatePagination(postType.slug, 'prev')"><i class="fa fa-arrow-left"></i> {{trans.__previous}} </button>
                                                    </div>
                                                    <div class="clearfix"></div>

                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ALBUMS -->
                            <div class="panel">
                                <a class="panel-heading collapsed" @click="activatePanel('album')">
                                    <h4 class="panel-title">{{trans.__albumsTitle}}</h4>
                                </a>
                                <div v-show="activePanel == 'album'">
                                    <div class="panel-body">
                                        <div class="" role="tabpanel" data-example-id="togglable-tabs">
                                            <ul :id="'album_Tab'" class="nav nav-tabs bar_tabs" role="tablist">
                                                <li role="presentation" class="active">
                                                    <a :href="'#album_Recent-tab1'" :id="'album_Recent-tab'" role="tab" data-toggle="tab" aria-expanded="true">{{trans.__mostRecent}}</a>
                                                </li>
                                                <li role="presentation" class="">
                                                    <a :href="'#album_Recent-tab2'" role="tab" :id="'album_Search-tab'" data-toggle="tab" aria-expanded="false">{{trans.__search}}</a>
                                                </li>
                                            </ul>
                                            <div :id="'album_TabContent'" class="tab-content">
                                                <div role="tabpanel" class="tab-pane fade active in" :id="'album_Recent-tab1'" :aria-labelledby="'album_Recent-tab'">

                                                    <div class="tableContainer">
                                                        <table class="table table-striped">
                                                            <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>{{trans.__globalName}}</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            <tr v-for="(album, i) in recentAlbums">
                                                                <td><input type="checkbox" :value="album" v-model="selectedAlbums" :data-type="album.title" :data-id="album.albumID" class="albumsCheckboxes"></td>
                                                                <td>{{ album.title }}</td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <button class="btn btn-default selectLinkBtn" @click="addLinkInMenu('albums')">{{trans.__globalSelect}}</button>
                                                    <div class="clearfix"></div>
                                                </div>
                                                <div role="tabpanel" class="tab-pane fade" :id="'album_Recent-tab2'" aria-labelledby="profile-tab">
                                                    <input type="search" class="form-control" :placeholder="'Album name'" @keyup="searchLink($event, 'album')" v-model="albumsSearchTerm">
                                                    <div class="tableContainer" v-if="searchedAlbumsResult != ''">
                                                        <table class="table table-striped">
                                                            <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>{{trans.__globalName}}</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            <!--<tr v-for="(posts, index) in getSearchedPostsResult[postType.slug]">-->
                                                                <!--<td><input type="checkbox" :value="posts" v-model="selectedPosts" :data-type="postType.name" :data-id="postType.postTypeID" class="postsCheckboxes"></td>-->
                                                                <!--<td>{{ posts.title }}</td>-->
                                                            <!--</tr>-->
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <button class="btn btn-default selectLinkBtn" @click="addLinkInMenu('albums')">{{trans.__globalSelect}}</button>

                                                    <div class="paginationBtns">
                                                        <!--<button class="btn btn-default selectLinkBtn" @click="updatePagination(postType.slug, 'next')">Next <i class="fa fa-arrow-right"></i></button>-->
                                                        <!--<button class="btn btn-default selectLinkBtn" @click="updatePagination(postType.slug, 'prev')"><i class="fa fa-arrow-left"></i> Prev </button>-->
                                                    </div>
                                                    <div class="clearfix"></div>

                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <!-- end of accordion -->
                    </div>
                </div>
            </div>

            <div class="col-md-8 col-sm-8 col-xs-8">
                <!-- POST TYPES -->
                <div class="x_panel">
                    <div class="x_title">
                        <h2>{{trans.__postTypesTitle}}</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{trans.__globalTitle}}</th>
                                    <th class="smallColumn">{{trans.__globalAction}}</th>
                                </tr>
                                </thead>
                            <tbody>
                                <tr v-for="(item, index) in relatedPostTypesList">
                                    <td>{{ item.title }}</td>
                                    <td slot-scope="row"><i class="fa fa-close fa-2x" @click="openConfirmModal('post_type',index)"></i></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- CATEGORIES -->
                <div class="x_panel">
                    <div class="x_title">
                        <h2>{{ relatedCategories.title }}</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{trans.__globalTitle}}</th>
                                    <th class="smallColumn">{{trans.__globalAction}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in relatedCategories.list">
                                    <td>{{ item.title }}</td>
                                    <td slot-scope="row"><i class="fa fa-close fa-2x" @click="openConfirmModal('categories',index)"></i></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- POSTS -->
                <div class="x_panel" v-for="(item, key ,index) in getPostsList">
                    <div class="x_title">
                        <h2>{{ item.title }}</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{trans.__globalTitle}}</th>
                                    <th class="smallColumn">{{trans.__globalAction}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(post, i) in item.list">
                                    <td v-if="post.title[$route.params.lang] !== undefined">{{ post.title[$route.params.lang] }}</td>
                                    <td v-else>{{ post.title }}</td>
                                    <td slot-cope="row"><i class="fa fa-close fa-2x" @click="openConfirmModal(key,i)"></i></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ALBUMS -->
                <div class="x_panel">
                    <div class="x_title">
                        <h2>{{trans.__albumsTitle}}</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>{{trans.__globalTitle}}</th>
                                <th class="smallColumn">{{trans.__globalAction}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(album, i) in relatedAlbums.list">
                                <td>{{ album.title }}</td>
                                <td slot-scope="row"><i class="fa fa-close fa-2x" @click="openConfirmModal('albums',i)"></i></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div tabindex="-1" role="dialog" aria-hidden="true" class="modal fade bs-example-modal-sm" style="opacity: 1; display: none;">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" @click="closeModal()" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true">X</span></button>
                        <h4 id="myModalLabel2" class="modal-title">{{trans.__globalConfirmBtn}}</h4>
                    </div>
                    <div class="modal-body">
                        <h4>{{trans.__globalSure}}</h4>
                        <p id="confirmDialogMsg">{{trans.__deleteFileWarning}}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-dismiss="modal" @click="closeModal()" class="btn btn-default">{{trans.__globalCloseBtn}}</button>
                        <button type="button" class="btn btn-primary" @click="deleteRelationItem">{{trans.__globalConfirmBtn}}</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
<style scoped>
    table th, table td{
        color: #000;
    }
</style>
<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        mounted() {
            // get related
            this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/menu/get-related-apps/'+this.$route.query.menu_link_id)
                .then((resp) => {
                    this.relatedPostTypesList = resp.body.post_types;
                    this.countRelatedPostTypes = this.relatedPostTypesList.length;

                    this.relatedCategories = resp.body.categories;
                    this.countRelatedCategories = this.relatedCategories.list.length;

                    this.relatedAlbums = resp.body.albums;
                    this.countRelatedAlbums = this.relatedAlbums.list.length;

                    if(!Object.keys(resp.body.posts).length){
                        // if no posts set empty object
                        this.relatedPosts = {};
                    }else{
                        // otherwise set POST TYPE with post list
                        this.relatedPosts = resp.body.posts;
                    }
                });

            // get posts types and posts
            this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/post-type/get-all')
                .then((resp) => {
                    let result = {};
                    let data = resp.body.data;

                    for(let k in data){
                        data[k].posts = [];
                        result[data[k].slug] = data[k];
                    }
                    this.recentPostTypes = result;

                    /*

                    for(var k in this.recentPostTypes){
                        this.postsSearchTerm[this.recentPostTypes[k].slug] = "";
                        this.searchedPostsResult[this.recentPostTypes[k].slug] = [];
                        this.$store.commit('setActionReturnedDataNested', [this.recentPostTypes[k].slug, []]);
                        this.totalPagesOfPosts[this.recentPostTypes[k].slug] = 1;
                        this.paginationForPosts[this.recentPostTypes[k].slug] = 1;

                        var labelName = "title";

                        for(var p in this.recentPostTypes[k].posts){
                            this.recentPostTypes[k].posts[p].title = this.recentPostTypes[k].posts[p][labelName];
                            if(this.recentPostTypes[k].posts[p]["original_object_"+labelName] !== undefined){
                                this.recentPostTypes[k].posts[p].original_object_title = this.recentPostTypes[k].posts[p]["original_object_"+labelName];
                            }
                        }
                    }

                    */


                });
            // get categories
            this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/category/json/menuPanelItems')
                .then((resp) => {
                    this.recentCategories = resp.body.data;
                });

            // get recent albums
            this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/album/get-all/0')
                .then((resp) => {
                    this.recentAlbums = resp.body.data;
                });

            var globalThis = this;
            $(function(e) {
                // remove selected posts of a post type if the other post type is opened
                $(".panel-heading").click(function(e){
                    globalThis.selectedPosts = [];
                });
            });

            // translations
            this.trans = {
                __selectMenuLabel: this.__('menu.form.selectMenuLabel'),
                __menuName: this.__('menu.form.menuName'),
                __label: this.__('menu.form.label'),
                __methodLabel: this.__('menu.form.methodLabel'),
                __cssClassLabel: this.__('menu.form.cssClassLabel'),
                __selectBtn: this.__('menu.form.selectBtn'),
                __createBtn: this.__('menu.form.createBtn'),
                __mostRecent: this.__('base.mostRecent'),
                __search: this.__('base.search'),
                __pagesTitle: this.__('base.pagesTitle'),
                __pageTitle: this.__('base.pageTitle'),
                __globalTitle: this.__('base.title'),
                __globalInfo: this.__('base.info'),
                __globalName: this.__('base.name'),
                __globalSelect: this.__('base.select'),
                __globalSlug: this.__('base.slug'),
                __globalSubmitBtn: this.__('base.submitBtn'),
                __globalCancelBtn: this.__('base.cancelBtn'),
                __globalSaveBtn: this.__('base.saveBtn'),
                __postTypeTitle: this.__('postType.title'),
                __postTypesTitle: this.__('postType.titlePlural'),
                __globalOr: this.__('base.or'),
                __categoriesTitle: this.__('categories.title'),
                __albumsTitle: this.__('media.albums'),
                __deleteFileWarning: this.__('media.deleteFileWarning'),
                __globalAction: this.__('base.action'),
                __globalConfirmBtn: this.__('base.confirmBtn'),
                __globalCloseBtn: this.__('base.closeBtn'),
                __globalSure: this.__('base.sure'),
            };
        },
        data(){
            return{
                activePanel: '',
                trans: {},
                relatedPostTypesList:{},
                relatedCategories:{},
                relatedPosts:{},
                relatedAlbums:{},
                countRelatedPostTypes: 0,
                countRelatedCategories: 0,
                toBeDeleted: { type: '', index: ''},
                // POST TYPES
                recentPostTypes: {},
                selectedPostTypes: [],
                postTypeSearchTerm: '',
                searchedPostTypesResult: '',
                paginationForPostTypes: 1,
                totalPagesOfPostTypes: 1,
                // CATEGORIES
                recentCategories: '',
                selectedCategories: [],
                categoriesSearchTerm: '',
                searchedCategoriesResult: '',
                paginationForCategories: 1,
                totalPagesOfCategories: 1,
                // POSTS
                //recentPosts: {},
                selectedPosts: [],
                postsSearchTerm: {},
                searchedPostsResult: {},
                paginationForPosts: {},
                totalPagesOfPosts: {},
                // ALBUMS
                recentAlbums: '',
                selectedAlbums: [],
                albumsSearchTerm: {},
                searchedAlbumsResult: {},
                paginationForAlbums: {},
                totalPagesOfAlbums: {},
            }
        },
        methods:{
            // use translation method of vuex
            __(key){
                this.$store.dispatch('__', key);
                return this.getTranslation;
            },
            activatePanel(panelKey, type = ''){
                this.activePanel = panelKey;

                if(type == "posts"){
                    if(!this.recentPostTypes[panelKey].posts.length){
                        // get posts types and posts
                        this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/post/json/menuPanelItems/'+panelKey)
                            .then((resp) => {
                                this.recentPostTypes[panelKey].posts = resp.body.data;
                            });
                    }
                }
            },
            searchLink(event, type){
                let key = event.key;
                let url = '';
                if(key == "Enter"){
                    if(type == "pages"){
                        url = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/search/pages/'+this.pageSearchTerm+'?pagination='+this.paginationForPages;
                    }else if(type == "postTypes"){
                        url = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/search/post-type/'+this.postTypeSearchTerm+'?pagination='+this.paginationForPostTypes;
                    }else if(type == "categories"){
                        url = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/category/0/search/'+this.categoriesSearchTerm+'?pagination='+this.paginationForCategories;
                    }else{
                        url = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/search/'+type+'/'+this.postsSearchTerm[type]+'?pagination='+this.paginationForPosts[type];
                    }

                    this.$http.get(url)
                        .then((resp) => {
                            if(type == "pages"){
                                this.searchedPagesResult = resp.body.list;
                                this.totalPagesOfPages = resp.body.totalPages;
                            }else if(type == "postTypes"){
                                this.searchedPostTypesResult = resp.body.list;
                                this.totalPagesOfPostTypes = resp.body.totalPages;
                            }else if(type == "categories"){
                                this.searchedCategoriesResult = resp.body.list;
                                this.totalPagesOfCategories = resp.body.totalPages;
                            }else{
                                this.searchedPostsResult[type] = resp.body.list;
                                this.$store.commit('setActionReturnedData', "");
                                this.$store.commit('setActionReturnedData', this.searchedPostsResult);
                                this.totalPagesOfPosts[type] = resp.body.totalPages;
                            }
                        });
                }
            },
            // The pagination buttons (Next and Prev) invoke this methods which is responsible for updating the pagination
            updatePagination(type, clickedBtn){
                // when page pagination is clicked
                if(type === "pages"){
                    if(clickedBtn == "next"){
                        if(this.totalPagesOfPages > this.paginationForPages){
                            this.paginationForPages++;
                        }
                    }else if(clickedBtn == "prev"){
                        if(this.paginationForPages > 1){
                            this.paginationForPages--;
                        }
                    }
                // when post type pagination is clicked
                }else if(type === "postTypes"){
                    if(clickedBtn == "next"){
                        if(this.totalPagesOfPostTypes > this.paginationForPostTypes){
                            this.paginationForPostTypes++;
                        }
                    }else if(clickedBtn == "prev"){
                        if(this.paginationForPostTypes > 1){
                            this.paginationForPostTypes--;
                        }
                    }
                }else if(type == "categories"){
                    if(clickedBtn == "next"){
                        if(this.totalPagesOfCategories > this.paginationForCategories){
                            this.paginationForCategories++;
                        }
                    }else if(clickedBtn == "prev"){
                        if(this.paginationForCategories > 1){
                            this.paginationForCategories--;
                        }
                    }
                }else{
                    if(clickedBtn == "next"){
                        if(this.totalPagesOfPosts[type] > this.paginationForPosts[type]){
                            this.paginationForPosts[type]++;
                        }
                    }else if(clickedBtn == "prev"){
                        if(this.paginationForPosts[type] > 1){
                            this.paginationForPosts[type]--;
                        }
                    }
                }
                this.searchLink({key:"Enter"}, type);
            },
            // add links to the related (config)
            addLinkInMenu(typeOfLink){
                var hasErrors = true;
                // clean the current list
                // include post types in the list
                if(typeOfLink == "postTypes"){
                    var relatedPostTypesList = this.relatedPostTypesList;
                    this.relatedPostTypesList = {};
                    var isBeingUsed = false;
                    for(var k in this.selectedPostTypes){
                        for(var key in relatedPostTypesList){
                            if(relatedPostTypesList[key].belongsToID == this.selectedPostTypes[k].postTypeID){
                                this.noty('error', 'bottomLeft', "Post type "+this.selectedPostTypes[k].name+" already in list", 10000);
                                isBeingUsed = true;
                            }
                        }
                        if(!isBeingUsed){
                            relatedPostTypesList[this.countRelatedPostTypes] = {};
                            relatedPostTypesList[this.countRelatedPostTypes].title = this.selectedPostTypes[k].name;
                            relatedPostTypesList[this.countRelatedPostTypes].belongsTo = "post_type";
                            relatedPostTypesList[this.countRelatedPostTypes].belongsToID = this.selectedPostTypes[k].postTypeID;
                            relatedPostTypesList[this.countRelatedPostTypes].menuLinkID = this.$route.query.menu_link_id;
                            relatedPostTypesList[this.countRelatedPostTypes].postIDs = [];
                            this.countRelatedPostTypes++;
                        }

                    }
                    this.selectedPostTypes = [];
                    this.relatedPostTypesList = relatedPostTypesList;
                    hasErrors = false;
                }else if(typeOfLink == "categories"){ // include categories in the list
                    var relatedCategories = this.relatedCategories.list;
                    this.relatedCategories.list = {};
                    var isBeingUsed = false;
                    for(var k in this.selectedCategories){
                        for(var key in relatedCategories){
                            if(relatedCategories[key].belongsToID == this.selectedCategories[k].categoryID){
                                this.noty('error', 'bottomLeft', "Category "+this.selectedCategories[k].title+" already in list", 10000);
                                isBeingUsed = true;
                            }
                        }
                        if(!isBeingUsed){
                            relatedCategories[this.countRelatedCategories] = {};

                            let title = this.selectedCategories[k].title[this.$route.params.lang];
                            relatedCategories[this.countRelatedCategories].title = (title !== undefined) ? title : '';

                            relatedCategories[this.countRelatedCategories].belongsTo = "categories";
                            relatedCategories[this.countRelatedCategories].belongsToID = this.selectedCategories[k].categoryID;
                            relatedCategories[this.countRelatedCategories].menuLinkID = this.$route.query.menu_link_id;
                            relatedCategories[this.countRelatedCategories].postIDs = [];
                            this.countRelatedCategories++;
                        }
                    }
                    this.selectedCategories = [];
                    this.relatedCategories.list = relatedCategories;
                    hasErrors = false;
                }else if(typeOfLink == "albums"){ // include albums in the list
                    var relatedAlbums = this.relatedAlbums.list;
                    this.relatedAlbums.list = {};
                    var isBeingUsed = false;
                    for(var k in this.selectedAlbums){
                        for(var key in relatedAlbums){
                            if(relatedAlbums[key].belongsToID == this.selectedAlbums[k].albumID){
                                this.noty('error', 'bottomLeft', "Album "+this.selectedAlbums[k].title+" already in list", 10000);
                                isBeingUsed = true;
                            }
                        }
                        if(!isBeingUsed){
                            relatedAlbums[this.countRelatedAlbums] = {};
                            relatedAlbums[this.countRelatedAlbums].title = this.selectedAlbums[k].title;
                            relatedAlbums[this.countRelatedAlbums].belongsTo = "albums";
                            relatedAlbums[this.countRelatedAlbums].belongsToID = this.selectedAlbums[k].albumID;
                            relatedAlbums[this.countRelatedAlbums].menuLinkID = this.$route.query.menu_link_id;
                            relatedAlbums[this.countRelatedAlbums].postIDs = [];
                            this.countRelatedAlbums++;
                        }
                    }
                    this.selectedAlbums = [];
                    this.relatedAlbums.list = relatedAlbums;
                    hasErrors = false;
                }else{ // include post types with post IDs in the list
                    var title = $(".postsCheckboxes:checked").attr("data-type");
                    var postTypeID = $(".postsCheckboxes:checked").attr("data-id");
                    var postIDs = {};
                    var relatedPosts = this.relatedPosts;
                    this.relatedPosts = {};

                    // check if post type exist in the post type array
                    for(var key in this.relatedPostTypesList){
                        if(postTypeID == this.relatedPostTypesList[key].belongsToID){
                            this.noty('error', 'bottomLeft', "Post type "+this.relatedPostTypesList[key].title+" already in list", 10000);
                            this.selectedPosts = [];
                            this.relatedPosts = relatedPosts;
                            return;
                        }
                    }

                    // insert post type in the list include
                    if(relatedPosts[typeOfLink] === undefined){
                        relatedPosts[typeOfLink] = {};
                        relatedPosts[typeOfLink].list = {};
                        relatedPosts[typeOfLink].title = title;
                        relatedPosts[typeOfLink].belongsTo = "post_type";
                        relatedPosts[typeOfLink].belongsToID = postTypeID;
                        relatedPosts[typeOfLink].postIDs = {};
                    }
                    relatedPosts[typeOfLink].menuLinkID = this.$route.query.menu_link_id;

                    // count current posts
                    var count = this.countObj(relatedPosts[typeOfLink].list);
                    // insert posts in the post type list
                    for(var k in this.selectedPosts){
                        var isBeingUsed = false;

                        // check if is being used
                        for(var key in relatedPosts[typeOfLink].list){
                            if(relatedPosts[typeOfLink].list[key].slug == this.selectedPosts[k].slug){
                                this.noty('error', 'bottomLeft', "Post "+this.selectedPosts[k].title+" already in list", 10000);
                                isBeingUsed = true;
                            }
                        }
                        if(!isBeingUsed){
                            relatedPosts[typeOfLink].list[count] = this.selectedPosts[k];
                            relatedPosts[typeOfLink].postIDs[count] = this.selectedPosts[k].postID;
                            count++;
                        }
                    }
                    this.selectedPosts = [];
                    this.relatedPosts = relatedPosts;
                    hasErrors = false;
                }

                if(!hasErrors){
                    this.store();
                }
            },
            // used to count items of a object
            countObj(obj){
                var count = 0;
                for(var k in obj){
                    count++;
                }
                return count;
            },
            // open modal if close btn in a item is clicked
            openConfirmModal(type, index){
                this.toBeDeleted.type = type;
                this.toBeDeleted.index = index;
                $('.modal').show();
            },
            // delete relation when confirm btn in modal is clicked
            deleteRelationItem(){
                var excludingDeleted = {};
                if(this.toBeDeleted.type == 'post_type'){
                    var relatedPostTypesList = this.relatedPostTypesList;
                    this.relatedPostTypesList = {};
                    for(var k in relatedPostTypesList){
                        if(k != this.toBeDeleted.index){
                            excludingDeleted[k] = relatedPostTypesList[k];
                            continue;
                        }
                    }
                    this.relatedPostTypesList = excludingDeleted;
                }else if(this.toBeDeleted.type == 'categories'){
                    var relatedCategories = this.relatedCategories.list;
                    this.relatedCategories = {};
                    for(var k in relatedCategories){
                        if(k != this.toBeDeleted.index){
                            excludingDeleted[k] = relatedCategories[k];
                            continue;
                        }
                    }
                    this.relatedCategories.list = excludingDeleted;
                }else if(this.toBeDeleted.type == 'albums'){
                    var relatedAlbums = this.relatedAlbums.list;
                    this.relatedAlbums = {};
                    for(var k in relatedAlbums){
                        if(k != this.toBeDeleted.index){
                            excludingDeleted[k] = relatedAlbums[k];
                            continue;
                        }
                    }
                    this.relatedAlbums.list = excludingDeleted;
                }else{
                    var relatedPosts = this.relatedPosts;
                    var postIDs = this.relatedPosts[this.toBeDeleted.type].postIDs;
                    this.relatedPosts = {};
                    for(var k in relatedPosts[this.toBeDeleted.type].list){
                        if(k != this.toBeDeleted.index){
                            excludingDeleted[k] = relatedPosts[this.toBeDeleted.type].list[k];
                            delete postIDs[this.toBeDeleted.index];
                            continue;
                        }
                    }
                    this.relatedPosts = relatedPosts;
                    this.relatedPosts[this.toBeDeleted.type].list = excludingDeleted;
                    var count = this.countObj(this.relatedPosts[this.toBeDeleted.type].list);
                    // if there are no pore post in the post type list
                    if(count == 0){
                        delete this.relatedPosts[this.toBeDeleted.type];
                    }
                }
                $('.modal').hide();
                this.store();
            },
            // store request in database - save relation in database
            store(){
                // make a single list for all relations (relatedPostTypesList, relatedCategories, relatedPosts)
                var relatedList = [];
                for(var k in this.relatedPostTypesList){
                    relatedList.push(this.relatedPostTypesList[k]);
                }
                for(var k in this.relatedCategories.list){
                    relatedList.push(this.relatedCategories.list[k]);
                }
                for(var k in this.relatedPosts){
                    relatedList.push(this.relatedPosts[k]);
                }
                for(var k in this.relatedAlbums.list){
                    relatedList.push(this.relatedAlbums.list[k]);
                }
                var request = {
                    relatedList: relatedList,
                    menu_link_id: this.$route.query.menu_link_id,
                };

                this.$store.dispatch('store',{data: request, url: this.basePath+'/'+this.$route.params.adminPrefix+"/json/menu/store-related", error: "Menu relations could not be stored. Please try again later."});
            },
            // this function displays a noty message
            noty(type, layout, message, timeout){
                new Noty({
                    type: type,
                    layout: layout,
                    text: message,
                    timeout: timeout,
                    closeWith: ['button']
                }).show();
            },
            closeModal(){
                this.toBeDeleted = { type: '', index: ''};
                $('.modal').hide();
            }
        },
        computed:{
            getCurrentLang(){
                //  get the current language slug
                return this.$store.getters.get_current_lang
            },
            getSearchedPostsResult(){
                return this.$store.getters.get_action_returned_data;
            },
            getAdminPrefix(){
                // return if we are in the advanced search
                return this.$store.getters.get_admin_prefix;
            },
            getPostsList(){
                return this.relatedPosts;
            },
            getTranslation(){
                // returns translated value
                return this.$store.getters.get_translation;
            }
        }
    }
</script>