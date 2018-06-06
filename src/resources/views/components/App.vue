<template>
    <div class="main_container" v-if="!isLoading">
        <app-navigation :applicationMenuLinks = "applicationMenuLinks" :cmsMenus="cmsMenus" :isPluginApp="isPlugin"></app-navigation>

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
        props:['base_url','base_path'],
        components:{
            'appHeader':Header,
            'appFooter':Footer,
            'appNavigation':Navigation,
        },
        mixins: [froalaMixin],
        data(){
            return {
                isLoading: true,
                applicationMenuLinks: [],
                cmsMenus: [],
                logout_link: '',
            }
        },
        created(){
            this.$store.commit('setBaseURL', this.base_url);
            this.$store.commit('setBasePath', this.base_path);

            // basic data for cms start
            this.$http.get(this.base_url+'/'+this.$route.params.adminPrefix+'/get-base-data')
                .then((resp) => {
                    this.$store.commit('setLogoutLink', resp.body.logoutLink);
                    this.logout_link = resp.body.logoutLink;
                    this.$store.commit('setAuth', resp.body.auth);
                    this.$store.commit('setGlobalData', resp.body.global_data);
                    this.$store.commit('setLabels', resp.body.labels);
                    this.$store.commit('setPluginsConfigs', resp.body.pluginsConfigs);
                    this.$store.commit('setLanguages', resp.body.languages);

                    this.applicationMenuLinks = resp.body.applicationMenuLinks;
                    this.cmsMenus = resp.body.cmsMenus;

                    // set menu mode on refresh
                    if(this.$route.query.mode !== undefined || this.$route.query.menu_link_id !== undefined){
                        this.$store.commit('setMenuMode', 'menu');
                    }else{
                        this.$store.commit('setMenuMode', 'application');
                    }

                    if(this.$route.meta.module == 'posts'){
                        this.$store.commit('setOpenModule', this.$route.params.post_type);
                    }else{
                        this.$store.commit('setOpenModule', this.$route.meta.module);
                    }

                    // if is not plugin
                    if(!this.isPlugin){
                        // create all froala components, custom plugins, buttons and extra functionality
                        this.froalaConstruct();
                    }
                }).then((resp) => {
                    this.isLoading = false;
            });
        },
        computed:{
            isPlugin(){
                let path = this.$route.path;
                return path.includes("plugins");
            }
        },
        watch:{
            // watch for url changes and component doesn't change
            '$route': function(){
                // reset vuex params
                if(!this.isPlugin){
                    this.$store.commit('setMediaSelectedFiles', {});
                    this.$store.commit('setStoreResponse', {errors: []});
                }
            }
        }
    }
</script>
