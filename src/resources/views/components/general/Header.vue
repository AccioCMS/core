<template>
    <div class="top_nav" @mouseleave="dropdownActive = false">
        <div class="nav_menu">
            <nav>
                <div class="nav toggle">
                    <a id="menu_toggle" @click="changeMenuType"><i class="fa fa-bars"></i></a>
                </div>

                <ul class="nav navbar-nav navbar-right headerBarNavigation">
                    <li>
                        <a class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false" style="cursor:pointer;" @click.prevent="dropdownActive = !dropdownActive">
                            <img :src="Auth.avatar" alt="">{{ Auth.firstName }}  {{ Auth.lastName }}
                            <span class=" fa fa-angle-down"></span>
                        </a>
                        <ul class="dropdown-menu dropdown-usermenu pull-right" style="display:block;" v-if="dropdownActive">
                            <li><router-link :to="basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/user/update/'+Auth.userID">Profile</router-link></li>
                            <li><a :href="logout_link"><i class="fa fa-sign-out pull-right"></i> Log Out</a></li>
                        </ul>
                    </li>

                    <li
                            :class="{active: $route.params.lang == language.slug}"
                            v-for="language in getLanguages" style="cursor:pointer"
                            @click="$router.push({ params: { lang: language.slug }}); $router.go(0)">
                        <a>{{ language.name }}</a>
                    </li>

                </ul>
            </nav>
        </div>
    </div>
</template>
<script>
    export default{
        props:['logout_link'],
        data(){
            return {
                dropdownActive: false,
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
            },
            // get languages
            getLanguages(){
                return this.$store.getters.get_languages;
            },
            // get base path
            basePath(){
                return this.$store.getters.get_base_path;
            }
        }
    }
</script>
