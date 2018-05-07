<template>
    <div class="row">

        <div class="col-lg-6 col-md-6 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>{{trans.__resetFormTitle}}</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <br />
                    <form class="form-horizontal form-label-left" id="storeUser" enctype="multipart/form-data">

                        <div class="form-group" id="form-group-password">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">{{ trans.__password }}</label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                <input type="password" class="form-control" v-model="user.password">
                                <div class="alert" v-if="StoreResponse.errors.password" v-for="error in StoreResponse.errors.password">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group" id="form-group-confpassword">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__confirmPassword}}</label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                <input type="password" class="form-control" v-model="user.confpassword">
                                <div class="alert" v-if="StoreResponse.errors.confpassword" v-for="error in StoreResponse.errors.confpassword">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                <button type="button" class="btn btn-success" @click.prevent="resetPassword">{{trans.__saveBtn}}</button>
                                <a class="btn btn-primary" @click="redirect('user-list')">{{trans.__cancelBtn}}</a>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

</template>
<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        mounted(){
            // get user information
            this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/user/details/'+this.$route.params.id)
                .then((resp) => {
                    this.user.id = resp.body.details.userID;
                });
            // translations
            this.trans = {
                __resetFormTitle: this.__('user.resetFormTitle'),
                __password: this.__('user.form.password'),
                __confirmPassword: this.__('user.form.confirmPassword'),
                __saveBtn: this.__('base.saveBtn'),
                __cancelBtn: this.__('base.cancelBtn'),
            };
        },
        data(){
            return{
                groupsList: '',
                user:{
                    id: '',
                    password: '',
                    confpassword: '',
                },
            }
        },
        methods: {
            resetForm(){
                this.user.password = "";
                this.user.confpassword = "";
            },
            resetPassword(e){
                this.$store.dispatch('openLoading');
                this.$store.dispatch('store',{
                    data: this.user,
                    url: this.basePath+'/'+this.getAdminPrefix+'/json/user/resetPassword',
                    error: "Password could not be reseted. Please try again later."
                });
            }
        },
    }
</script>
