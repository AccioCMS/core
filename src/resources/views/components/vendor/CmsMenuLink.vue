<template>

    <li class="clearfix" v-if="data.access">

        <router-link tag="a" :to="link" class="col-lg-10 col-md-10 col-sm-10">
            {{ data.label }}
        </router-link>


        <span class="fa arrowIcon"
              :class="{'fa-chevron-down': !isChildUlActive, 'fa-chevron-up': isChildUlActive}"
              v-if="data.children"
              @click="isChildUlActive = !isChildUlActive"></span>

        <div class="clearfix"></div>

        <transition name="slide">
            <ul class="nav child_menu" style="display:block !important;" v-if="data.children && isChildUlActive">
                <cms-menu-link v-for="(link, index) in data.children" :key="index" :data="link"></cms-menu-link>
            </ul>
        </transition>

    </li>

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
    export default{
        props:['data'],
        data(){
            return{
                isChildUlActive: false,
                link: '',
            }
        },
        mounted(){
            let url = this.data.link;
            let devider = '?';
            if(url.indexOf("?") != -1){
                devider = '&';
            }
            this.link = url+devider+'menu_link_id='+this.data.menuLinkID;

        }
    }
</script>
