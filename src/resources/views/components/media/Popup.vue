<template>
    <div class="mediaPopupWrapper">
        <i class="fa fa-times fa-2x" id="closePopupBtn" aria-hidden="true" @click="cancel"></i>
        <div class="mediaPopupContainer">
            <div id="tabs">
                <div class="tabs-container">
                    <button class="btn btn-default" type="button" :class="{ active: activeView == 'upload' }" @click="redirect('upload')">{{trans.__upload}}</button>
                    <button class="btn btn-default" type="button" :class="{ active: activeView == 'library' }" @click="redirect('library')">{{trans.__library}}</button>
                    <button class="btn btn-default" type="button" :class="{ active: activeView == 'albums' }" @click="redirect('albums')">{{trans.__albums}}</button>
                </div>
                <hr>
            </div>

            <popup-upload v-if="activeView == 'upload'"></popup-upload>

            <popup-library v-if="activeView == 'library'"
                           ref="library"
                           :isAlbum="false"></popup-library>

            <div v-if="activeView == 'albums'">
                <popup-library v-if="getAlbumID != 0" ref="album" :isAlbum="true"></popup-library>
                <albums v-if="getAlbumID == 0" :menu_link_id="$route.query.menu_link_id"></albums>
            </div>

        </div>
    </div>
</template>
<style src="./style.css"></style>
<script>
    import PopupUpload from './PopupUpload.vue'
    import PopupLibrary from './PopupLibrary.vue'
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
        },
        props: [],
        components:{
            'popup-upload':PopupUpload,
            'popup-library':PopupLibrary,
            'albums':Albums,
        },
        data(){
            return{
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
            },
        }
    }
</script>
