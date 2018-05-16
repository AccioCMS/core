<template>
    <div v-if="is_active" class="albumsEditFormWrapper">
        <div class="albumTitle">
            <h2>{{trans.__editAlbumTitle}}</h2>
        </div>

        <spinner :width="'30px'" :height="'30px'" :border="'5px'" v-if="isLoading"></spinner>

        <template v-if="!isLoading">
            <div class="albumLangTabs">
                <button type="button" v-for="(language, key ,index) in album" :class="'tabBtn '+isActive(index)" :id="'tab-'+index" :data-index="index">{{language.langName}}</button>
            </div>

            <div class="formContainer">
                <form>

                    <div :id="'tabContent-'+index" v-for="(album, key, index) in album" :class="'tabContent '+isActive(index)">

                        <div class="form-group clearfix" :id="'form-group-title_'+key">
                            <label class="control-label col-md-12 col-sm-12 col-xs-12">{{trans.__title}}</label>
                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <input type="text" class="form-control" v-model="album.title">
                                <div class="alert" v-if="StoreResponse.errors['title_'+key]" v-for="error in StoreResponse.errors['title_'+key]">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group clearfix" :id="'form-group-description_'+key">
                            <label class="control-label col-md-12 col-sm-12 col-xs-12">{{trans.__description}}</label>
                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <input type="text" class="form-control" v-model="album.description">
                                <div class="alert" v-if="StoreResponse.errors['description_'+key]" v-for="error in StoreResponse.errors['description_'+key]">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group clearfix" id="form-group-isVisible">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__visible}}</label>
                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <div id="isVisible" class="btn-group" data-toggle="buttons">
                                    <label :class="{ 'active': album.isVisible, 'btn btn-default': true}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="changeVisibility(true, key)">
                                        <input type="radio" name="isVisible" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                    </label>
                                    <label :class="{ 'active': !album.isVisible, 'btn btn-primary': true}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="changeVisibility(false, key)">
                                        <input type="radio" name="isVisible" value="false"> {{trans.__false}}
                                    </label>
                                    <div class="alert" v-if="StoreResponse.errors.isVisible" v-for="error in StoreResponse.errors.isVisible">{{ error }}</div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="form-group clearfix">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <button class="btn btn-primary" type="button" @click="store">{{trans.__saveBtn}}</button>
                            <button class="btn btn-info" type="button" @click="$emit('closeEditPanel')">{{trans.__cancelBtn}}</button>
                            <button class="btn btn-danger" type="button" @click="openModal" v-if="albumID">{{trans.__deleteBtn}}</button>
                        </div>
                    </div>

                </form>
            </div>
        </template>

        <!-- MODAL -->
        <div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">

                    <div class="modal-header">
                        <button type="button" class="close" @click="closeModal"><span aria-hidden="true">X</span></button>
                        <h4 class="modal-title" id="myModalLabel2">{{trans.__confirmBtn}}</h4>
                    </div>
                    <div class="modal-body">
                        <h4>{{trans.__deleteAlbumWarning}}</h4>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal" @click="closeModal">{{trans.__closeBtn}}</button>
                        <button type="button" class="btn btn-primary" @click="confirmSelected">{{trans.__confirmBtn}}</button>
                    </div>

                </div>
            </div>
        </div>
        <!-- MODAL -->

    </div>
</template>
<style>
    .albumLangTabs button{
        margin: 10px 2px;
        height: 30px;
        width: 70px;
        border: 1px solid #dad6d6;
        background-color: #f3f0f0;
    }
    .albumLangTabs button.active{
        background-color: #fdfafa !important;
    }
    .formContainer{
        margin-top: 20px;
    }
    .tabContent{
        display: none;
    }
    .tabContent.active{
        display: block;
    }
</style>
<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';
    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        mounted() {
            // translations
            this.trans = {
                __editAlbumTitle: this.__('media.editAlbumTitle'),
                __title: this.__('base.title'),
                __description: this.__('base.description'),
                __true: this.__('base.booleans.true'),
                __false: this.__('base.booleans.false'),
                __saveBtn: this.__('base.saveBtn'),
                __cancelBtn: this.__('base.cancelBtn'),
                __deleteBtn: this.__('base.deleteBtn'),
                __confirmBtn: this.__('base.confirmBtn'),
                __closeBtn: this.__('base.closeBtn'),
                __deleteAlbumWarning: this.__('media.deleteAlbumWarning'),
            };
        },
        data(){
            return{
                trans: {},
                album: "",
                albumID: "",
                isLoading: true,
            }
        },
        updated: function(){
            $(".tabBtn").click(function(e){
                let index = $(this).attr('data-index');
                $(".tabBtn").removeClass('active');
                $(this).addClass('active');
                $('.tabContent').hide();
                $('#tabContent-'+index).show();
            });
        },
        props: ['is_active','menu_link_id','albumListPagination'],
        methods:{
            // use translation method of vuex
            __(key){
                this.$store.dispatch('__', key);
                return this.getTranslation;
            },
            watchAlbumID: function(id){
                this.isLoading = true;
                // get the album info if we are updating
                this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/album/details/'+id)
                    .then((resp) => {
                        this.album = resp.body.album;
                        this.albumID = id;
                    }).then((resp) => {
                    this.isLoading = false;
                });
            },
            // this function checks if user has permissions to a specific language
            hasPermissionForLang(langID){
                // if is admin return true
                if(this.getGlobalPermissions.global !== undefined && this.getGlobalPermissions.global.admin !== undefined){
                    return true;
                }
                // check language permission if user is not admin
                if(this.getGlobalPermissions.Language !== undefined && this.getGlobalPermissions.Language.id){
                    let allowedLanguageIDs = this.getGlobalPermissions.Language.id;
                    if(allowedLanguageIDs.indexOf(langID) === -1){
                        return false;
                    }
                }
                return true;
            },
            // makes the first language tab active
            isActive(index){
                if(index == 0){
                    return 'active';
                }
                return '';
            },
            changeVisibility(option, lang){
                this.album[lang].isVisible = option;
            },
            store(e){
                let menu_link_id = '';
                if(this.menu_link_id !== undefined){
                    menu_link_id = this.menu_link_id;
                }
                let request = {
                    album: this.album,
                    albumID: this.albumID,
                    menu_link_id: menu_link_id,
                };

                this.$store.dispatch('store',
                    {data: request, url: this.basePath+'/'+this.getAdminPrefix+"/json/album/store", error: "Album could not be stored. Please try again later."}
                ).then(resp => {
                    this.refreshList(); // refresh the list of albums
                    this.$emit('closeEditPanel');
                    this.resetForm();
                });
            },
            // this function is used to refresh the list of albums
            refreshList(){
                let url = this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/album/get-all/1';
                if(this.menu_link_id !== undefined){
                    url += '?menu_link_id='+this.menu_link_id;
                }

                // get all albums with the related images
                this.$http.get(url).then((resp) => {
                    this.$store.commit('setList', resp.body.data);
                });
            },
            // reset form
            resetForm(){
                for(let k in this.album){
                    this.album[k].title = '';
                    this.album[k].isVisible = true;
                    this.album[k].description = '';
                }
            },
            // open confirmation modal
            openModal(e){
                $(".modal").css("opacity", 1);
                $(".modal").show();
            },
            closeModal(e){
                $(".modal").hide();
            },
            confirmSelected(){
                // get the album info if we are updating
                this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/album/delete/'+this.albumID)
                    .then((resp) => {
                        $(".modal").hide();
                        let response = resp.body;
                        this.$store.dispatch('handleErrors', {response});
                        this.$emit('closeEditPanel');
                        this.resetForm();
                        this.refreshList(); // refresh the list of albums
                    });
            }
        },
        computed:{
            getGlobalPermissions(){
                // return user permissions
                return this.$store.getters.get_global_data.permissions;
            }
        }
    }
</script>
