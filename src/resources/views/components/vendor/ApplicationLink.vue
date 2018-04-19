<template>
    <li class="menu-parent-link" v-if="data.access" :class="{active: data.module == getOpenModule}">

        <template v-if="data.link != ''">

            <template v-if="isPluginApp == 1">
                <a :href="data.link">
                    <i :class="data.icon" v-if="data.icon"></i>
                    {{ data.label }} <span v-if="data.children" class="fa fa-chevron-down"></span>
                </a>
            </template>

            <template v-else>
                <router-link v-if="data.module != 'plugins'" tag="a" :to="data.link" @click.native="makeActive">
                    <i :class="data.icon" v-if="data.icon"></i>
                    {{ data.label }} <span v-if="data.children" class="fa fa-chevron-down"></span>
                </router-link>

                <a :href="data.link" v-else>
                    <i :class="data.icon" v-if="data.icon"></i>
                    {{ data.label }} <span v-if="data.children" class="fa fa-chevron-down"></span>
                </a>
            </template>

        </template>


        <a v-else @click.prevent="makeActive">
            <i :class="data.icon" v-if="data.icon"></i>
            {{ data.label }}
            <span
                    v-if="data.children"
                    class="fa"
                    :class="{'fa-chevron-down': data.module != getOpenModule, 'fa-chevron-up': data.module == getOpenModule}"></span>
        </a>
        <transition name="slide">
            <ul class="nav child_menu" style="display:block !important;" v-show="data.children && data.module == getOpenModule">
                <application-link v-for="(link, index) in data.children" :key="index" :data="link" :isPluginApp="isPluginApp"></application-link>
            </ul>
        </transition>

    </li>
</template>
<style scoped>
    .slide {
        transition: all 1s ease;
        height: auto;
    }
    .slide.v-enter, .slide.v-leave {
        height: 0;
        opacity: 0;
    }
</style>
<script>
    export default{
        props:['data','isPluginApp'],
        methods:{
            makeActive(){
                if(this.data.module !== undefined){
                    this.$store.commit('setOpenModule', this.data.module);
                }
            }
        },
        computed:{
            // open module (used in navigation)
            getOpenModule(){
                return this.$store.getters.get_open_module;
            }
        }
    }
</script>

