<template>
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>{{trans.__detailsFormTitle}}</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">

                    <table id="datatable-checkbox" class="table table-striped table-bordered bulk_action">
                        <thead>
                        <tr>
                            <th>{{trans.__key}}</th>
                            <td>{{trans.__value}}</td>
                        </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(item, index) in list">
                                <td>{{ index }}</td>
                                <td>{{ item }}</td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

        <div class="clearfix"></div>

    </div>
</template>

<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        mounted() {
            var userid = this.$route.params.id;
            this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/user/details/'+userid)
                .then((resp) => {
                    this.list = resp.body.details;
                });

            // translations
            this.trans = {
                __detailsFormTitle: this.__('user.detailsFormTitle'),
                __key: this.__('user.form.key'),
                __value: this.__('user.form.value'),
            };
        },
        data(){
            return{
                list: '',
            }
        },
    }
</script>
