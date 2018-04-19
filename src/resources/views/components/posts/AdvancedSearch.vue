<template>
    <div class="col-lg-12 col-md-12 col-sm-12">

        <div class="col-md-2 col-sm-2 col-xs-2" style="margin-top:10px;">
            <div class="form-group">
                <label class="control-label col-md-12 col-sm-12 col-xs-12">Title :</label>
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <input type="text" v-model="form.title" name="title" class="form-control">
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-2 col-xs-2" style="margin-top:10px;">
            <div class="form-group">
                <label class="control-label col-md-12 col-sm-12 col-xs-12">Category :</label>
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <select class="form-control" v-model="form.categoryID">
                        <option value="0">All</option>
                        <option v-for="category in categoryList" :value="category.categoryID">{{ category.title }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-2 col-xs-2" style="margin-top:10px;">
            <div class="form-group">
                <label class="control-label col-md-12 col-sm-12 col-xs-12">Author :</label>
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <select class="form-control" v-model="form.userID">
                        <option value="0">All</option>
                        <option v-for="author in authorList" :value="author.userID">{{ author.firstName }} {{ author.lastName }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-2 col-xs-2" style="margin-top:10px;">
            <div class="form-group">
                <label class="control-label col-md-12 col-sm-12 col-xs-12">From :</label>
                <div class="col-md-12 col-sm-12 col-xs-12 datepickerContainer">
                    <datepicker name="date" v-model="form.from" class="col-md-8 col-sm-8 removePaddingAndMargin" :format="dateFormat"></datepicker>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-2 col-xs-2" style="margin-top:10px;">
            <div class="form-group">
                <label class="control-label col-md-12 col-sm-12 col-xs-12">To :</label>
                <div class="col-md-12 col-sm-12 col-xs-12 datepickerContainer">
                    <datepicker name="date" v-model="form.to" class="col-md-8 col-sm-8" :format="dateFormat"></datepicker>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-2 col-xs-2" style="margin-top:33px;">
            <div class="form-group">
                <button @click="makeSearch" class="btn btn-info">Search</button>
            </div>
        </div>

    </div>
</template>
<style>
    .datepickerContainer div{
        width: 100%;
        margin: 0;
        padding: 0;
    }
</style>
<script>
    import Datepicker from 'vuejs-datepicker'
    export default{
        components: { Datepicker },
        props: ['advancedSearchPostUrl'],
        mounted(){
            // get all categories of post type
            var categoryPromise = this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/category/get-all-without-pagination-by-post-type/'+this.$route.params.post_type)
                .then((resp) => {
                    this.categoryList = resp.body;
                });

            // get all users
            var authorPromise = this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/user/get-all-without-pagination')
                .then((resp) => {
                    this.authorList = resp.body;
                });

            this.form.pagination = (this.$route.query.post_type !== undefined) ? this.$route.query.post_type : 1;
            this.form.title = (this.$route.query.title !== undefined) ? this.$route.query.title : '';
            this.form.categoryID = (this.$route.query.categoryID !== undefined) ? this.$route.query.categoryID : 0;
            this.form.userID = (this.$route.query.userID !== undefined) ? this.$route.query.userID : 0;
            this.form.from = (this.$route.query.from !== undefined && this.$route.query.from != '') ? new Date(this.$route.query.from) : '';
            this.form.to = (this.$route.query.to !== undefined && this.$route.query.to != '') ? new Date(this.$route.query.to) : '';
            this.form.orderBy = (this.$route.query.orderBy !== undefined) ? this.$route.query.orderBy : 'postID';
            this.form.orderType = (this.$route.query.orderType !== undefined) ? this.$route.query.orderType : 'DESC';

            // when all ajax request are done
            Promise.all([categoryPromise,authorPromise]).then(([v1,v2]) => {
                if(this.$route.query.advancedSearch !== undefined){
                    this.makeSearch();
                }
            });

        },
        data(){
            return{
                categoryList: [],
                authorList: [],
                dateFormat: 'd MMMM yyyy',
                form: {
                    title: '',
                    categoryID: 0,
                    userID: 0,
                    from: '',
                    to: '',
                    post_type: this.$route.params.post_type,
                    orderBy: 'postID',
                    orderType: 'DESC',
                    page: 1,
                }
            }
        },
        methods:{
            makeSearch(){
                this.$store.commit('setSpinner', true);
                this.$http.post(this.advancedSearchPostUrl, this.form)
                    .then((resp) => {
                        this.$store.commit('setList', resp.body);

                        this.$router.push({ query: {
                                pagination: this.form.page,
                                advancedSearch: 1,
                                title: this.form.title,
                                categoryID: this.form.categoryID,
                                userID: this.form.userID,
                                from: this.form.from,
                                to: this.form.to,
                                orderBy: this.form.orderBy,
                                orderType: this.form.orderType,
                                post_type: this.$route.params.post_type
                            }
                        });
                        this.$store.commit('setSpinner', false);
                    });
            }
        },
        computed: {
            // get base path
            basePath(){
                return this.$store.getters.get_base_path;
            }
        },
    }
</script>
