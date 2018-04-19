<template>
    <div class="right_col" role="main">

        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2><i class="fa fa-bars"></i> Settings </h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">

                    <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                        <ul class="nav nav-tabs tabs-left">
                            <li><router-link :to="{name: 'project-settings-general'}" tag="a">General</router-link></li>
                            <li><router-link :to="{name: 'project-settings-permalinks'}" tag="a">Permalinks</router-link></li>
                            <!--<li><router-link :to="{name: 'project-settings-notifications'}" tag="a">Notifications</router-link></li>-->
                            <li><router-link :to="{name: 'project-settings-analytics'}" tag="a">Analytics</router-link></li>
                        </ul>
                    </div>

                    <div class="col-lg-9 col-md-12 col-sm-12 col-xs-12">
                        <router-view></router-view>
                    </div>

                    <div class="clearfix"></div>

                </div>
            </div>
        </div>


    </div>
</template>
<style scoped>
    .router-link-active{
        background-color: #FFF;
    }
</style>
<script>
    export default{
        computed:{
            // get base path
            basePath(){
                return this.$store.getters.get_base_path;
            }
        },
    }
</script>