<template>
    <div class="main_container">

        <app-navigation :applicationMenuLinks = "applicationMenuLinks" :cmsMenus="cmsMenus" :isPluginApp="is_plugin_app"></app-navigation>

        <app-header :logout_link="logout_link"></app-header>

        <router-view></router-view>

        <app-footer></app-footer>
    </div>
</template>
<script>
    import { froalaMixin } from '../mixins/froala';
    import Header from './general/Header.vue';
    import Footer from './general/Footer.vue';
    import Navigation from './general/Navigation.vue';

    export default{
        props:['application_menu_links','cms_menus','auth','labels','global_data','base_url','base_path','plugins_configs','is_plugin_app','logout_link'],
        components:{
            'appHeader':Header,
            'appFooter':Footer,
            'appNavigation':Navigation,
        },
        mixins: [froalaMixin],
        data(){
            return {
                applicationMenuLinks: JSON.parse(this.application_menu_links),
                cmsMenus: JSON.parse(this.cms_menus),
            }
        },
        created() {
            // information about the authentication
            this.$store.commit('setBaseURL', this.base_url);
            this.$store.commit('setBasePath', this.base_path);
            this.$store.commit('setLogoutLink', this.logout_link);
            this.$store.commit('setAuth', JSON.parse(this.auth));
            this.$store.commit('setGlobalData', JSON.parse(this.global_data));
            this.$store.commit('setLabels', JSON.parse(this.labels));
            this.$store.commit('setPluginsConfigs', JSON.parse(this.plugins_configs));

            // set menu mode on refresh
            if(this.$route.query.mode !== undefined || this.$route.query.menu_link_id !== undefined){
                this.$store.commit('setMenuMode', 'cms');
            }else{
                this.$store.commit('setMenuMode', 'application');
            }

            if(this.$route.meta.module == 'posts'){
                this.$store.commit('setOpenModule', this.$route.params.post_type);
            }else{
                this.$store.commit('setOpenModule', this.$route.meta.module);
            }

            if(!Boolean(this.is_plugin_app)){
                // create all froala components, custom plugins, buttons and extra functionality
                this.froalaConstruct();
            }
        },
        watch:{
            // watch for url changes and component doesn't change
            '$route': function(){
                // reset vuex params
                if(!Boolean(this.is_plugin_app)){
                    this.$store.commit('setMediaSelectedFiles', {});
                    this.$store.commit('setStoreResponse', {errors: []});
                }
            }
        }
    }
</script>
