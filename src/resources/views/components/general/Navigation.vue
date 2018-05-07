<template>
    <div class="col-md-3 left_col">
        <div class="left_col scroll-view">
            <div class="navbar nav_title" style="border: 0;">

                <router-link v-if="!isPluginApp" :to="'/'+$route.params.adminPrefix" tag="a" class="site_title">
                    <div class="profile"><!-- menu profile quick info -->
                        <div class="profile_pic">
                            <img :src="Settings.logo" alt="" class="img-circle profile_img">
                        </div>
                    </div>
                    <span>{{ Settings.siteTitle }}</span>
                </router-link>

                <a :href="this.basePath+'/'+$route.params.adminPrefix" class="site_title" v-else>
                    <div class="profile"><!-- menu profile quick info -->
                        <div class="profile_pic">
                            <img :src="Settings.logo" alt="" class="img-circle profile_img">
                        </div>
                    </div>
                    <span>{{ Settings.siteTitle }}</span>
                </a>
            </div>


            <!-- sidebar menu -->
            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
                <div class="menu_section">

                    <div class="navBarTabs">
                        <button id="cmsTabBtn" :class="{active: getMenuMode == 'cms'}" @click="changeMenuMode('cms')">CMS</button>
                        <button id="applicationTabBtn" :class="{active: getMenuMode == 'application'}" @click="changeMenuMode('application')">Application</button>
                    </div>

                    <div class="cmsMenuNav" v-if="getMenuMode == 'cms'">
                        <div class="navMenuSection" v-for="(menu, key, index) in cmsMenus" :key="key">
                            <div :data-id="'menuSection'+menu.menuID" class="navMenuSection-header" @click="activeMenu = index">
                                <h3>{{ menu.title }}</h3>
                            </div>

                            <transition name="slide">
                                <div :id="'menuSection'+menu.menuID" v-if="index == activeMenu" class="navMenuSection-list" style="display: block;">
                                    <ul class="nav side-menu dimension1UL">
                                        <cms-menu-link v-for="(link, index) in menu.menuLinks" :key="index" :data="link"></cms-menu-link>
                                    </ul>
                                </div>
                            </transition>

                        </div>
                    </div>
                    <div class="applicationMenuNav" v-if="getMenuMode == 'application'">
                        <ul class="nav side-menu">
                            <application-link v-for="(link,  index) in applicationMenuLinks" :key="index" :data="link" :isPluginApp="isPluginApp"></application-link>
                        </ul>
                    </div>

                </div>
            </div>
            <!-- sidebar menu -->
        </div>
    </div>
</template>
<style scoped>
    .slide {
        transition: all .5s ease;
        height: auto;
    }
    .slide.v-enter, .slide.v-leave {
        height: 0;
        opacity: 0;
    }
</style>
<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';
    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        props:['applicationMenuLinks','cmsMenus','isPluginApp'],
        methods: {
            // change the menu mode (switch between cms and application menu)
            changeMenuMode(mode){
                this.$store.commit('setMenuMode', mode);
            }
        },
        data(){
            return {
                activeMenu: 0
            }
        },
        computed: {
            getMenuMode(){
                return this.$store.getters.get_menu_mode;
            },
            Auth(){
                return this.$store.getters.get_auth;
            },
            Settings() {
                return this.getGlobalData.settings;
            },
            IsMobile(){
                return this.$store.getters.get_navigation_menu_state_is_mobile;
            }
        }
    }
</script>
