<template>
    <div class="right_col" role="main">
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
                        <button class="btn btn-default" :class="{ active: activeView == 'albums' }" @click="redirect('albums')">{{trans.__albums}}</button>
                    </div>
                    <hr>
                </div>

                <popup-upload v-if="activeView == 'upload'"></popup-upload>

                <library v-if="activeView == 'library'" :multiple="true" :multipleInputs="true" :isAlbum="false" ref="library"></library>

                <div v-if="activeView == 'albums'">
                    <library v-if="getAlbumID != 0" :multiple="true" :multipleInputs="true" :isAlbum="true" ref="album"></library>
                    <albums v-if="getAlbumID == 0" :menu_link_id="$route.query.menu_link_id"></albums>
                </div>

            </div>

        </div>

    </div>
</template>
<style src="./style.css"></style>
<script>
    import PopupUpload from './PopupUpload.vue'
    import Library from './Library.vue'
    import Albums from './Albums.vue'
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
                __albums: this.__('media.albums'),
            };

            this.$store.commit('setPopUpActiveMediaView', this.$route.params.view);
        },
        components:{
            'popup-upload':PopupUpload,
            'library':Library,
            'albums':Albums,
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
                if(this.getAlbumID == 0 && this.activeView == "library"){
                    this.$refs.library.registerSavedState();
                }else if(this.getAlbumID !== 0 && this.activeView == "albums"){
                    this.$refs.album.registerSavedState();
                }
                this.$store.commit('setPopUpActiveMediaView', to);
            }
        },
        computed: {
            activeView(){
                return this.$store.getters.get_popup_active_media_view;
            },
            getAlbumID(){
                return this.$store.getters.get_selected_album_ID;
            },
            getLibrarySavedState(){
                return this.$store.getters.get_library_saved_state;
            }
        }
    }
</script>
