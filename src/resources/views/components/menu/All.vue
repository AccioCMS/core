<template>
    <div class="componentsWs" dusk="menuComponent">
        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}} <small>{{trans.__listTitle}}</small></h3>
            </div>
        </div>
        <!-- TITLE END -->
        <div class="clearfix"></div>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <label class="control-label selectMenuLabel col-md-2 col-sm-2 col-xs-2">{{trans.__selectMenuLabel}}:</label>
                    <div class="col-md-2 col-sm-2 col-xs-2">
                        <select class="form-control" v-model="selectedMenuID">
                            <option v-for="(option, i) in menuList" :value="option.menuID">{{ option.title }}</option>
                        </select>
                    </div>

                    <div class="col-md-8 col-sm-4 col-xs-4">
                        <div class="col-md-3 col-sm-3 col-xs-4">
                            <label class="control-label selectMenuLabel col-md-3 col-sm-3">{{trans.__slug}}:</label>
                            <div class="col-md-9 col-sm-9">
                                <input type="text" class="form-control" disabled :value="selectedMenu.slug">
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-3 col-xs-4">
                            <button class="btn btn-default selectMenuBtn" @click="changeSelectedMenu">{{trans.__selectBtn}}</button>
                            <span> {{ trans.__globalOr }} </span>
                            <button class="btn btn-primary createMenuBtn" @click="createNewMenu">{{trans.__createBtn}}</button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="col-md-4 col-sm-4 col-xs-4">
                    <div class="x_panel">
                        <div class="x_content">
                            <!-- LINK PANELS -->
                            <link-panel v-for="(panel, key, index) in linkPanels"
                                        :key="key"
                                        :index="key"
                                        :panel="panel"
                                        :trans="trans"
                                        :languages="languages"
                                        :defaultLanguagesSlug="defaultLanguagesSlug"></link-panel>
                        </div>
                    </div>
                </div>

                <!-- EDIT MENU PANEL -->
                <div class="col-md-8 col-sm-8 col-xs-12">
                    <div class="x_panel">
                        <div class="x_title">
                            <label>{{trans.__menuName}}: </label>
                            <input class="menuNameInput" v-model="selectedMenu.title">
                            <div class="clearfix"></div>
                        </div>

                        <!-- Loading component -->
                        <spinner v-if="spinner" :width="'60px'" :height="'60px'" :border="'14px'"></spinner>

                        <div class="dd col-md-6 col-sm-6 col-xs-12" id="nestable" v-show="!spinner">

                            <ol class="dd-list" id="menuLinks"><!-- FIRST DIMENSION -->
                                <li v-for="(itemD1, keyD1, indexD1) in menuLinkList" :id="'link'+keyD1" class="dd-item" :data-id="keyD1" :data-belongsTo="itemD1.belongsTo"
                                    :data-belongsToID="itemD1.belongsToID" :data-label="itemD1.label"
                                    :data-cssClass="itemD1.cssClass" :data-customLink="itemD1.customLink"
                                    :data-controller="itemD1.controller" :data-method="itemD1.method" :data-slug="itemD1.slug">
                                    <div class="icons">
                                        <i class="fa fa-pencil-square fa-2x" @click="openEditLinkPanel(itemD1.menuLinkID)"></i>
                                        <i class="fa fa-close fa-2x" @click="deleteLink(indexD1, itemD1.menuLinkID, keyD1)"></i>
                                    </div>
                                    <div class="dd-handle">{{ itemD1.label[getCurrentLang] }}</div>

                                    <ol class="dd-list" v-if="itemD1.children"> <!-- SECOND DIMENSION -->
                                        <li v-for="(itemD2, keyD2, indexD2) in itemD1.children" :id="'link'+keyD2" class="dd-item" :data-id="keyD2" :data-belongsTo="itemD2.belongsTo"
                                            :data-belongsToID="itemD2.belongsToID" :data-label="itemD2.label"
                                            :data-cssClass="itemD2.cssClass" :data-customLink="itemD2.customLink"
                                            :data-controller="itemD2.controller" :data-method="itemD2.method" :data-slug="itemD2.slug">
                                            <div class="icons">
                                                <i class="fa fa-pencil-square fa-2x" @click="openEditLinkPanel(itemD2.menuLinkID)"></i>
                                                <i class="fa fa-close fa-2x" @click="deleteLink(indexD2, itemD2.menuLinkID, keyD2)"></i>
                                            </div>
                                            <div class="dd-handle">{{ itemD2.label[getCurrentLang] }}</div>

                                            <ol class="dd-list" v-if="itemD2.children"> <!-- THIRD DIMENSION -->
                                                <li v-for="(itemD3, keyD3, indexD3) in itemD2.children" :id="'link'+keyD3" class="dd-item" :data-id="keyD3" :data-belongsTo="itemD3.belongsTo"
                                                    :data-belongsToID="itemD3.belongsToID" :data-label="itemD3.label"
                                                    :data-cssClass="itemD3.cssClass" :data-customLink="itemD3.customLink"
                                                    :data-controller="itemD3.controller" :data-method="itemD3.method" :data-slug="itemD3.slug">
                                                    <div class="icons">
                                                        <i class="fa fa-pencil-square fa-2x" @click="openEditLinkPanel(itemD3.menuLinkID)"></i>
                                                        <i class="fa fa-close fa-2x" @click="deleteLink(indexD3, itemD3.menuLinkID, keyD3)"></i>
                                                    </div>
                                                    <div class="dd-handle">{{ itemD3.label[getCurrentLang] }}</div>

                                                    <ol class="dd-list" v-if="itemD3.children"> <!-- FOURTH DIMENSION -->
                                                        <li v-for="(itemD4, keyD4, indexD4) in itemD3.children" :id="'link'+keyD4" class="dd-item" :data-id="keyD4" :data-belongsTo="itemD4.belongsTo"
                                                            :data-belongsToID="itemD4.belongsToID" :data-label="itemD4.label"
                                                            :data-cssClass="itemD4.cssClass" :data-customLink="itemD4.customLink"
                                                            :data-controller="itemD4.controller" :data-method="itemD4.method" :data-slug="itemD4.slug">
                                                            <div class="icons">
                                                                <i class="fa fa-pencil-square fa-2x" @click="openEditLinkPanel(itemD4.menuLinkID)"></i>
                                                                <i class="fa fa-close fa-2x" @click="deleteLink(indexD4, itemD4.menuLinkID, keyD4)"></i>
                                                            </div>
                                                            <div class="dd-handle">{{ itemD4.label[getCurrentLang] }}</div>

                                                            <ol class="dd-list" v-if="itemD4.children"> <!-- FIFTH DIMENSION -->
                                                                <li v-for="(itemD5, keyD5, indexD5) in itemD4.children" :id="'link'+keyD5" class="dd-item" :data-id="keyD5" :data-belongsTo="itemD5.belongsTo"
                                                                    :data-belongsToID="itemD5.belongsToID" :data-label="itemD5.label"
                                                                    :data-cssClass="itemD5.cssClass" :data-customLink="itemD5.customLink"
                                                                    :data-controller="itemD5.controller" :data-method="itemD5.method" :data-slug="itemD5.slug">
                                                                    <div class="icons">
                                                                        <i class="fa fa-pencil-square fa-2x" @click="openEditLinkPanel(itemD5.menuLinkID)"></i>
                                                                        <i class="fa fa-close fa-2x" @click="deleteLink(indexD5, keyD5)"></i>
                                                                    </div>
                                                                    <div class="dd-handle">{{ itemD5.label[getCurrentLang] }}</div>
                                                                </li>
                                                            </ol>

                                                        </li>
                                                    </ol>

                                                </li>
                                            </ol>

                                        </li>
                                    </ol>

                                </li>
                            </ol>

                        </div>

                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="openEditLinkPanelPanel">
                                <form>
                                    <div class="panelBody">

                                        <ul class="nav nav-tabs">
                                            <li v-for="(language, index) in languages" role="presentation" :class="{active: editPanelActiveLanguage == language.slug}" @click="editPanelActiveLanguage = language.slug">
                                                <a>{{ language.name }}</a>
                                            </li>
                                        </ul>

                                        <div v-for="(language, index) in languages" v-if="editPanelActiveLanguage == language.slug">

                                            <div class="form-group" :id="'form-group-label-'+language.slug">
                                                <label class="control-label col-md-12 col-sm-12 col-xs-12">{{trans.__label}} ({{ language.name }})</label>
                                                <div class="col-md-12 col-sm-12 col-xs-12">
                                                    <input type="text" class="form-control" :id="'editMenuLabel_'+language.slug" v-model="selectedMenuInfoToChange.label[language.slug]">
                                                    <div class="alert" v-if="StoreResponse.errors['label_'+language.slug]" v-for="error in StoreResponse.errors['label_'+language.slug]">{{ error }}</div>
                                                </div>
                                                <div class="clearfix"></div>
                                            </div>

                                            <div class="form-group" :id="'form-group-slug-'+language.slug">
                                                <label class="control-label col-md-12 col-sm-12 col-xs-12">{{trans.__globalSlug}} ({{ language.name }})</label>
                                                <div class="col-md-12 col-sm-12 col-xs-12">
                                                    <input type="text" class="form-control" :id="'editMenuSlug_'+language.slug" :value="selectedMenuInfoToChange.slug[language.slug]" disabled>
                                                    <div class="alert" v-if="StoreResponse.errors['slug_'+language.slug]" v-for="error in StoreResponse.errors['slug_'+language.slug]">{{ error }}</div>
                                                </div>
                                                <div class="clearfix"></div>
                                            </div>

                                            <hr>
                                        </div>

                                    </div>

                                    <div class="form-group" id="form-group-method">
                                        <label class="control-label col-md-12 col-sm-12 col-xs-12">{{trans.__methodLabel}}</label>
                                        <div class="col-md-12 col-sm-12 col-xs-12">
                                            <select class="form-control" id="method" v-model="selectedMenuInfoToChange.routeName">
                                                <option
                                                        v-for="(route, key, index) in selectedMenuInfoToChange.routeList"
                                                        :value="key">
                                                    {{ route }}
                                                </option>

                                            </select>
                                            <div class="alert" v-if="StoreResponse.errors.routeName" v-for="error in StoreResponse.errors.routeName">{{ error }}</div>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>

                                    <div class="form-group" id="form-group-cssClass">
                                        <label class="control-label col-md-12 col-sm-12 col-xs-12">{{trans.__cssClassLabel}}</label>
                                        <div class="col-md-12 col-sm-12 col-xs-12">
                                            <input type="text" class="form-control" id="cssClass" v-model="selectedMenuInfoToChange.cssClass">
                                            <div class="alert" v-if="StoreResponse.errors.cssClass" v-for="error in StoreResponse.errors.cssClass">{{ error }}</div>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>

                                    <div class="form-group">
                                        <div class="col-md-9 col-sm-9 col-xs-12">
                                            <button type="button" class="btn btn-success" @click="selectedMenuInfoToChangeEdited">{{trans.__globalSubmitBtn}}</button>
                                            <a  type="button" class="btn btn-primary" @click="cancelEditLinkPanel">{{trans.__globalCancelBtn}}</a>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mainButtonsContainer" v-if="!spinner">
                <div class="row">
                    <button type="button" class="btn btn-primary" @click="store" id="globalSaveBtn">{{trans.__globalSaveBtn}}</button>
                </div>
            </div>

        </div>
    </div>
</template>
<style src="./style.css" scoped></style>
<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';
    import { lists } from '../../mixins/lists';
    import { menuLoadData } from '../../mixins/menuLoadData';
    import LinkPanel from './LinkPanel.vue';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated, lists, menuLoadData],
        components: {'link-panel':LinkPanel},
        mounted() {
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
                __slug: this.__('base.slug'),
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
                __clickHereEdit: this.__('base.clickHereEdit'),
                __postTypeTitle: this.__('postType.title'),
                __postTypesTitle: this.__('postType.titlePlural'),
                __globalOr: this.__('base.or'),
                __categoriesTitle: this.__('categories.title'),
            };

            // load all data // function is in mixins/menuLoadData.js mixin
            // get all details of the selected menu (Menu links of the selected menu)
            this.loadMenuData();

            var globalThis = this;
            // load order plugin
            $(document).ready(function(){
                // activate Nestable for list 1
                $('#nestable').nestable({
                    group: 1
                }).on('change', globalThis.updateOutput);

                // output initial serialised data
                globalThis.updateOutput($('#nestable').data('output', $('#nestable-output')));
                $('#nestable-menu').on('click', function(e){
                    var target = $(e.target),
                    action = target.data('action');

                    if (action === 'expand-all') {
                        $('.dd').nestable('expandAll');
                    }

                    if (action === 'collapse-all') {
                        $('.dd').nestable('collapseAll');
                    }
                });
            });
        },
        data(){
            return{
                linkPanels: [],
                menuList: '',
                menuListLength: 0,
                menuLinkListAfterOrder:'',
                selectedMenu: '',
                selectedMenuID: '',
                editPanelActiveLanguage: '',
                selectedMenuInfoToChange: { label:{}, cssClass:'', slug:{}, linkType:'', routeName:'', routeList: {}, href: '' },
                newMenuLinksCount: 1,
                languages: '',
                defaultLanguagesSlug: '',
                selectedLinkKey: '',
                deletedMenuLinks: [],
            }
        },
        methods:{
            changeSelectedMenu(){
                this.redirect('menu-list',this.selectedMenuID);
                location.reload();
            },

            // hide menu link edit panel // cancel editing post
            cancelEditLinkPanel(e){
                e.preventDefault();
                $(".openEditLinkPanelPanel").slideUp(100);
            },

            // hide single method in posts and post type menu links
            shouldHide(method){
                var linkType = this.selectedMenuInfoToChange.linkType;
                if(linkType == "posts"){
                    if(method == "index"){
                        return false;
                    }
                }else if(linkType == "post_type"){
                    if(method == "single"){
                        return false;
                    }
                }
                return true;
            },

            // open edit panel for menu link
            openEditLinkPanel(key){
                this.selectedLinkKey = key;
                this.getSelectedMenuLinkData(this.menuLinkList);
            },

            // the data of a menu link
            getSelectedMenuLinkData(menuLinkList){
                for(var k in menuLinkList){
                    if(menuLinkList[k].menuLinkID == this.selectedLinkKey){
                        this.selectedMenuInfoToChange.label = menuLinkList[k].label;
                        this.selectedMenuInfoToChange.cssClass = menuLinkList[k].cssClass;
                        this.selectedMenuInfoToChange.slug = menuLinkList[k].slug;
                        this.selectedMenuInfoToChange.linkType = menuLinkList[k].belongsTo;
                        // this method is used to get the template methods for a specific controller
                        this.selectedMenuInfoToChange.routeName = menuLinkList[k].routeName;
                        this.selectedMenuInfoToChange.routeList = menuLinkList[k].routeList;
                        this.selectedMenuInfoToChange.href = menuLinkList[k].href;

                        $(".openEditLinkPanelPanel").slideDown(100);
                        break;
                    }else{
                        this.getSelectedMenuLinkData(menuLinkList[k].children);
                    }
                }
            },

            // remove link from menu
            deleteLink(index, id, key){
                this.updateOutput($('#nestable').data('output', $('#nestable-output')));
                this.deletedMenuLinks.push(id);
                // get the menu link by id
                let menuLink = {};
                for(let k in this.menuLinkListAfterOrder){
                    if(this.menuLinkListAfterOrder[k].id == key){
                        menuLink = this.menuLinkListAfterOrder[k];
                    }
                }
                // if menu link has children
                if(menuLink.children !== undefined){
                    this.getMenuLinkChildrenDeletedIDs(menuLink.children);
                }

                $("#link"+key).remove();
            },

            // get children IDs
            getMenuLinkChildrenDeletedIDs(menuLinks){
                for(let k in menuLinks){
                    this.deletedMenuLinks.push(menuLinks[k].id);
                    if(menuLinks[k].children !== undefined){
                        this.getMenuLinkChildrenDeletedIDs(menuLinks[k].children);
                    }
                }
            },

            // create menu button is clicked
            createNewMenu(){
                this.menuList.push({
                    menuID: 0,
                    title: "",
                    slug: "",
                    isPrimary: 0,
                });

                this.selectedMenuID = 0;
                this.selectedMenu = {
                    menuID: 0,
                    title: "",
                    slug: "",
                    isPrimary: 0,
                };
                this.$store.commit('setMenuLinkList', {});
                this.redirect('menu-list',0);
            },

            // when submit button of menu link edit panel is clicek
            selectedMenuInfoToChangeEdited(){
                var menuLinkList = this.menuLinkList;
                this.$store.commit('setMenuLinkList', {});

                const label = this.selectedMenuInfoToChange.label;
                const cssClass = this.selectedMenuInfoToChange.cssClass;
                const routeName = this.selectedMenuInfoToChange.routeName;
                const key = this.selectedLinkKey;

                var changedMenuLinks = this.changeMenuLinkValues(menuLinkList, key, label, cssClass, routeName);
                this.$store.commit('setMenuLinkList', changedMenuLinks);
            },

            // loop throw multidimensional array of menu links and change the specific menu link value
            changeMenuLinkValues(menuLinkList, key, label, cssClass, routeName){
                for(var k in menuLinkList){
                    if(menuLinkList[k].menuLinkID == key){
                        menuLinkList[k].label = label;
                        menuLinkList[k].cssClass = cssClass;
                        menuLinkList[k].routeName = routeName;
                        break;
                     }else{
                        this.changeMenuLinkValues(menuLinkList[k].children, key, label, cssClass, routeName);
                     }
                }
                return menuLinkList;
            },

            // update menu link order (nestable plugin)
            updateOutput(e){
                this.menuLinkListAfterOrder = '';
                var list = e.length ? e : $(e.target),
                output = list.data('output');
                var changedMenuLinkList = JSON.parse(window.JSON.stringify(list.nestable('serialize')));
                this.menuLinkListAfterOrder = changedMenuLinkList;
            },

            // store request
            store(){
                this.$store.dispatch('openLoading');

                this.updateOutput($('#nestable').data('output', $('#nestable-output')));
                var request = {
                    menuLinkList: this.menuLinkList,
                    selectedMenuID: this.selectedMenuID,
                    selectedMenu: this.selectedMenu,
                    menuLinkListAfterOrder: this.menuLinkListAfterOrder,
                    deletedMenuLinks: this.deletedMenuLinks,
                };
                this.$store.dispatch('store',{data: request, url: this.basePath+'/'+this.getAdminPrefix+"/json/menu/store", error: "Menu could not be stored. Please try again later."})
                    .then((resp) => {
                        if(resp.code == 200){
                            this.selectedMenuID = resp.id;
                            this.changeSelectedMenu();
                        }
                    });
            },

            // set the active method in menu link edit panel
            isMethodActive(method){
                if(method == this.selectedMenuInfoToChange.method){
                    return true;
                }
                return false;
            }
        },
        computed: {
            getSearchedPostsResult(){
                return this.$store.getters.get_action_returned_data;
            },
            menuLinkList(){
                return this.$store.getters.get_menu_link_list;
            }
        },
    }
</script>
