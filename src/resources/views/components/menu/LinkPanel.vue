<template>

    <div class="panel" :id="'panel-'+index">
        <a class="panel-heading" @click="openPanel">
            <h4 class="panel-title">{{panel.label}}</h4>
        </a>
        <div class="panel-collapse" v-show="isPanelOpen">
            <div class="panel-body">
                <div class="panelBody">
                    <ul class="nav nav-tabs">
                        <li role="presentation" :class="{active: mode == 'recent'}" @click="mode = 'recent'">
                            <a>{{trans.__mostRecent}}</a>
                        </li>
                        <li role="presentation" :class="{active: mode == 'search'}" @click="mode = 'search'">
                            <a>{{trans.__search}}</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tabPane" v-if="mode == 'recent'">

                            <div class="tableContainer">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th v-for="column in columns">{{ column }}</th>
                                        </tr>
                                    </thead>

                                    <tr v-if="spinner" :id="'panel-'+index+'-spinner'">
                                        <td colspan="7">
                                            <!-- Loading component -->
                                            <spinner :width="'30px'" :height="'30px'" :border="'5px'"></spinner>
                                        </td>
                                    </tr>

                                    <tbody>
                                        <tr v-for="(item, index) in data">
                                            <td v-for="(column, key, index) in columns">
                                                <input type="checkbox" :value="item" v-model="selectedLinks" v-if="column == 'id'">
                                                <span v-else>
                                                    <template v-if="item[key][$route.params.lang] !== undefined">
                                                        {{ item[key][$route.params.lang] }}
                                                    </template>

                                                    <template v-else>
                                                        {{ item[key] }}
                                                    </template>
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>

                                </table>
                            </div>

                            <button class="btn btn-default selectLinkBtn" @click="addLinkInMenu">{{trans.__globalSelect}}</button>
                            <div class="clearfix"></div>
                        </div>
                        <div class="tabPane fade" v-if="mode == 'search'">
                            <!--<input type="search" class="form-control" placeholder="Categories name" @keyup="searchLink($event, 'categories')" v-model="categoriesSearchTerm">-->
                            <div class="tableContainer">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th v-for="column in columns">{{ column }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    <!--<tr v-for="(categories, index) in searchedCategoriesResult">-->
                                        <!--<td><input type="checkbox" :value="categories" v-model="selectedCategories"></td>-->
                                        <!--<td>{{ categories.title }}</td>-->
                                        <!--<td>{{ categories.name }}</td>-->
                                    <!--</tr>-->

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

</template>
<style src="./style.css" scoped></style>
<script>
    export default{
        props:['panel', 'trans', 'languages', 'defaultLanguagesSlug', 'index'],
        data(){
            return{
                isPanelOpen: false,
                data: {},
                columns: {},
                selectedLinks: [],
                mode: 'recent',
                spinner: false,
            }
        },
        methods:{
            openPanel(){
                if(!this.isPanelOpen){
                    this.isPanelOpen = true;
                    if(!Object.keys(this.data).length){
                        this.spinner = true;
                        this.$http.get(this.panel.items.url)
                            .then((resp) => {
                                this.columns = resp.body.columns;
                                this.data = resp.body.data;
                                this.spinner = false;
                            }, response => {
                                new Noty({
                                    type: "error",
                                    layout: 'bottomLeft',
                                    text: response.statusText
                                }).show();
                                this.spinner = false;
                            });
                    }
                }else{
                    this.isPanelOpen = false;
                }
            },
            // used to add a new link in the menu
            addLinkInMenu(){
                let index = Object.keys(this.menuLinkList).length;
                let tmpLinks = this.menuLinkList;
                this.$store.commit('setMenuLinkList', {});
                for(let k in this.selectedLinks){

                    let routeList = this.panel.routes.list;
                    let defaultRoute = this.panel.routes.defaultRoute;
                    // routes for post types
                    if(this.panel.belongsTo == "post_type"){
                        routeList = this.panel.routes[this.selectedLinks[k].slug].list;
                        defaultRoute = this.panel.routes[this.selectedLinks[k].slug].defaultRoute;
                    }

                    let tmpKey = 'NEW'+index;
                    let tmpLink = {
                        menuLinkID: tmpKey,
                        belongsTo: this.selectedLinks[k].belongsTo,
                        belongsToID: this.selectedLinks[k].belongsToID,
                        parent: 0,
                        cssClass: "",
                        customLink: "",
                        order: index,
                        routeName: defaultRoute,
                        routeList: routeList,
                        params: this.selectedLinks[k].menuLinkParameters,
                    };

                    tmpLink.label = this.multilanguageObj(this.selectedLinks[k].label);
                    tmpLink.slug = this.multilanguageObj(this.selectedLinks[k].slug);

                    tmpLinks[tmpKey] = tmpLink;
                    index++;
                }

                this.$store.commit('setMenuLinkList', tmpLinks);
                this.selectedLinks = [];
            },

            multilanguageObj(value){
                let tmpVal = value;

                if(typeof value == "object"){
                    for(var l in this.languages){
                        if(value[this.languages[l].slug] === undefined){
                            if(this.defaultLanguagesSlug !== undefined && this.defaultLanguagesSlug != '' && tmpVal[this.defaultLanguagesSlug] !== undefined){
                                value[this.languages[l].slug] = tmpVal[this.defaultLanguagesSlug];
                            }else{
                                value[this.languages[l].slug] = "";
                            }
                        }
                    }
                }else{
                    value = {};
                    for(var l in this.languages){
                        value[this.languages[l].slug] = tmpVal;
                    }
                }
                return value;
            }

        },

        computed: {
            menuLinkList(){
                return this.$store.getters.get_menu_link_list;
            }
        },
    }
</script>
