<template>
    <ul class="nav navbar-right panel_toolbox relatedUL">
        <li class="dropdown" v-if="hasPermission('PostType','read') || hasPermission('Categories','read')">
            <a href="#" class="dropdown-toggle" @click="active = !active">Related&nbsp;<i class="fa fa-bars"></i></a>
            <ul class="lists-action-bar-dropdown" v-show="active">
                <li v-if="hasPermission('PostType','read') && count(relatedPostTypesList)"><b>Post types</b></li>
                <li v-if="hasPermission('PostType','read')" v-for="(item, index) in relatedPostTypesList">
                    <router-link :to="item.url+'?'+linkParams">{{item.title}}</router-link>
                </li>
                <li v-if="hasPermission('PostType','read') && count(relatedPostTypesList)" class="divider"></li>
                <li v-if="hasPermission('Categories','read') && count(relatedCategories)"><b>Categories</b></li>
                <li v-if="hasPermission('Categories','read')" v-for="(item, index) in relatedCategories">
                    <router-link :to="item.url+'&'+linkParams">{{item.title}}</router-link>
                </li>
                <li v-if="hasPermission('Categories','read') && count(relatedCategories)" class="divider"></li>
                <li v-if="hasPermission('PostType','read') && count(relatedPosts)"><b>Posts</b></li>
                <li v-if="hasPermission('PostType','read')" v-for="(item, index) in relatedPosts">
                    <router-link :to="item.url+'?'+linkParams">{{item.title}}</router-link>
                </li>
                <li v-if="hasPermission('Albums','read') && count(relatedPosts)" class="divider"></li>
                <li v-if="hasPermission('Albums','read')">
                    <router-link :to="albumURL+'?'+linkParams"><b>Albums</b></router-link>
                </li>
            </ul>
        </li>
        <li v-if="hasPermission('global','admin')"><router-link :to="configRelatedLink" role="button" aria-expanded="false">Config<i class="fa fa-wrench"></i></router-link></li>
    </ul>
</template>
<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        mounted() {
            this.loadRelatedBtnsData();
        },
        methods: {
            count(array){
                return Object.keys(array).length;
            },
            loadRelatedBtnsData(){
                this.linkParams = "menu_link_id="+this.$route.query.menu_link_id+"&related=1"; // get url parameters
                this.configRelatedLink = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/menu/related?'+this.linkParams;
                // get related
                this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/menu/get-related-apps/'+this.$route.query.menu_link_id)
                    .then((resp) => {
                        this.relatedPostTypesList = resp.body.post_types;
                        this.relatedCategories = resp.body.categories.list;
                        this.relatedPosts = resp.body.posts;
                        this.albumURL = resp.body.albums.url;
                    });
            }
        },
        data(){
            return{
                configRelatedLink: '',
                relatedPostTypesList: [],
                relatedCategories: '',
                relatedPosts: '',
                linkParams: '',
                albumURL: '',
                active: false,
            }
        },
        watch:{
            // watch for url changes and component doesn't change
            '$route': function(){
                this.loadRelatedBtnsData();
            }
        }
    }
</script>
