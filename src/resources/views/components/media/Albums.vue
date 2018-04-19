<template>
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel albumPanel">

                <div class="x_title albumTitle">
                    <h2>{{trans.__albums}}</h2>
                    <button type="button" class="btn btn-primary addAlbumBtn" @click="editComponent(0, 'create')">{{trans.__addBtn}}</button>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content albumPanelContent">

                    <div id="albumsContainer" class="col-lg-10 col-md-10 col-sm-10 col-xs-11">
                        <div class="row">

                            <article class="col-lg-3 col-md-4 col-sm-6 col-xs-12 albumWrapper" v-for="(album, index) in getList">
                                <div class="albumContainer">
                                    <div class="imagesContainer" @click="openAlbum(album.albumID)">
                                        <!-- PASTE THE 4 IMAGES FOR EACH ALBUM -->
                                        <div class="image col-lg-6 col-md-6 col-sm-6" v-for="(image, imageIndex) in album.mediaList" v-if="imageIndex < 4">
                                            <img :src="generateUrl('/'+image.fileDirectory+'/200x200/'+image.filename)" />
                                        </div>
                                         <!--IF ALBUM DOES NOT HAVE 4 IMAGES PASTE IMAGE PLACEHOLDERS-->
                                        <div v-if="album.mediaList !== undefined && (4-album.mediaList.length) > 0">
                                            <div class="image col-lg-6 col-md-6 col-sm-6" v-for="imageIndex in (4-album.mediaList.length)" v-if="album.mediaList.length < 4">
                                                <img :src="resourcesUrl('/images/photo-placeholder.png')" />
                                            </div>
                                        </div>

                                    </div>

                                    <div class="titleContainer" @click="openAlbum(album.albumID)"><h4>{{ album.title }}</h4></div>
                                    <div class="btnContainer"><button class="btn btn-primary" type="button" @click="editComponent(album.albumID, 'edit')">{{trans.__editBtn}}</button></div>
                                </div>
                            </article>

                        </div>

                    </div>

                    <div id="editPanel" class="col-lg-2 col-md-2 col-sm-2 col-xs-3">
                        <edit @closeEditPanel="is_active = false" :is_active="is_active" :menu_link_id="$route.query.menu_link_id" ref="edit" :albumListPagination="page"></edit>
                    </div>

                </div>

            </div>
        </div>
    </div>
</template>
<style src="./style.css" scoped></style>
<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';
    import EditAlbum from './EditAlbum.vue'

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        mounted(){
            let url = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/album/get-all/'+1;
            if(this.$route.query.menu_link_id !== undefined){
                url += '?menu_link_id='+this.$route.query.menu_link_id;
            }

            let global = this;
            // get all albums with the related images
            this.$http.get(url).then((resp) => {
                this.$store.commit('setList', resp.body.data);
            });

            $('#albumsContainer').bind('scroll', function(){
                if($(this).scrollTop() + $(this).innerHeight()>=$(this)[0].scrollHeight){
                    global.page = global.page + 1;
                    global.loadMore();
                }
            });

            // translations
            this.trans = {
                __addBtn: this.__('base.addBtn'),
                __editBtn: this.__('base.editBtn'),
                __albums: this.__('media.albums'),
            };
        },
        data(){
            return{
                is_active: false,
                page: 1,
            }
        },
        components:{
            'edit':EditAlbum,
        },
        methods:{
            // use translation method of vuex
            __(key){
                this.$store.dispatch('__', key);
                return this.getTranslation;
            },
            // open and sent album_id to edit component
            editComponent: function(id, type){
                this.is_active = true;
                this.$refs.edit.watchAlbumID(id);
            },
            openAlbum(id){
                this.$store.commit('setSelectedAlbumID', id);
            },
            loadMore(){
                let url = this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/album/get-all/'+this.page;
                if(this.$route.query.menu_link_id !== undefined){
                    url += '?menu_link_id='+this.$route.query.menu_link_id;
                }
                // load more albums
                this.$http.get(url).then((resp) => {
                    let count = this.getList.length;
                    let newList = resp.body.data;
                    for(let k in newList){
                        this.$store.commit('pushToList', newList[k]);
                    }
                });
            }
        },
    }
</script>
