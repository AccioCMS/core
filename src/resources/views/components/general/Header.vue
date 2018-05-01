<template>
    <div class="top_nav" @mouseleave="dropdownActive = false">
        <div class="nav_menu">
            <nav>
                <div class="nav toggle">
                    <a id="menu_toggle" @click="changeMenuType"><i class="fa fa-bars"></i></a>
                </div>

                <ul class="nav navbar-nav navbar-right headerBarNavigation">
                    <li class="">
                        <a class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false" style="cursor:pointer;" @click.prevent="dropdownActive = !dropdownActive">
                            <img :src="Auth.user.avatar" alt="">{{ Auth.user.firstName }}  {{ Auth.user.lastName }}
                            <span class=" fa fa-angle-down"></span>
                        </a>
                        <ul class="dropdown-menu dropdown-usermenu pull-right" style="display:block;" v-if="dropdownActive">
                            <li><router-link :to="this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/user/update/'+Auth.user.userID">Profile</router-link></li>
                            <li><a :href="this.logoutLink+''"><i class="fa fa-sign-out pull-right"></i> Log Out</a></li>
                        </ul>
                    </li>

                    <li
                            :class="{active: $route.params.lang == language.slug}"
                            v-for="language in languages" style="cursor:pointer"
                            @click="$router.push({ params: { lang: language.slug }}); $router.go(0)">
                        <a>{{ language.name }}</a>
                    </li>

                </ul>
            </nav>
        </div>
    </div>
</template>
<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';
    import { globalMethods } from '../../mixins/globalMethods';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        created(){
            this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/language/get-all?order=isDefault&type=desc&a1')
                .then((resp) => {
                    this.languages = resp.body.data;
                });
        },
        data(){
            return {
                dropdownActive: false,
                languages: [],
            }
        },
        methods: {
            changeMenuType(){
                this.$store.commit("setNavigationMenuStateIsMobile", !this.IsMobile)

                if(this.IsMobile){
                    $("body").attr("class", "nav-sm");
                }else{
                    $("body").attr("class", "nav-md");
                }
            }
        },
        computed: {
            Auth(){
                return this.$store.getters.get_auth;
            },
            IsMobile(){
                return this.$store.getters.get_navigation_menu_state_is_mobile;
            }
        }
    }
</script>
