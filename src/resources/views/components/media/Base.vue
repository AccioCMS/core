<template>
    <div class="right_col mainBaseMedia" role="main">
        <div class="componentsWs">
            <div class="page-title col-lg-12 col-md-12 col-sm-12 col-xs-12 ">
                <div class="title_left">
                    <div>
                        <h3 class="pull-left">{{trans.__title}}</h3>
                    </div>
                </div>

                <div class="title_right">
                    <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                        <div class="input-group">

                        </div>
                    </div>
                </div>
            </div>

            <div class="mediaContainer">
                <div id="tabs">
                    <div class="tabs-container">
                        <button class="btn btn-default" :class="{ active: activeView == 'upload' }" @click="redirect('upload')">{{trans.__upload}}</button>
                        <button class="btn btn-default" :class="{ active: activeView == 'library' }" @click="redirect('library')">{{trans.__library}}</button>
                    </div>
                    <hr>
                </div>

                <popup-upload v-if="activeView == 'upload'"></popup-upload>

                <library v-if="activeView == 'library'" :multiple="true" :multipleInputs="true" ref="library"></library>

            </div>

        </div>

    </div>
</template>
<style src="./style.css"></style>
<script>
    import PopupUpload from './PopupUpload.vue'
    import Library from './Library.vue'
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        mounted() {
            this.$store.commit('setPopUpActiveMediaView', 'library');

            // translations
            this.trans = {
                __title: this.__('media.title'),
                __library: this.__('media.library'),
                __upload: this.__('media.upload'),
            };

            this.$store.commit('setPopUpActiveMediaView', this.$route.params.view);
        },
        components:{
            'popup-upload':PopupUpload,
            'library':Library,
        },
        data(){
            return{
                trans: {},
            }
        },
        methods:{
            // cancel pop
            cancel(){
                this.$store.commit('setIsMediaOpen', false);
            },
            redirect(to){
                this.$store.commit('setPopUpActiveMediaView', to);
            }
        },
        computed: {
            activeView(){
                return this.$store.getters.get_popup_active_media_view;
            }
        }
    }
</script>
