<template>
    <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
        <div class="input-group">
            <input type="text" class="form-control" id="searchInput" v-model="term" @keyup.enter="search(true)" :placeholder="trans.__search + ' ...'">
            <span class="input-group-btn">
                <button class="btn btn-default" type="button" @click="search(true)">{{trans.__searchBtn}}</button>
            </span>
        </div>
    </div>
</template>
<script>
    import { globalComputed } from '../../mixins/globalComputed'
    import { globalMethods } from '../../mixins/globalMethods'
    import { globalData } from '../../mixins/globalData'

    export default{
        mixins: [globalComputed, globalMethods, globalData],
        props: ['url','view'],
        mounted(){
            this.trans = {
                __search: this.__('base.search'),
                __searchBtn: this.__('base.searchBtn'),
            };

            // make search when page is refreshed
            if(this.$route.params.term !== undefined){
                this.term = this.$route.params.term;
                this.search(false);
            }
        },
        data(){
            return{
                term: '',
            }
        },
        methods:{
            search(shouldRedirect){
                if(this.term){
                    if(shouldRedirect){
                        this.$router.push(this.view+this.term);
                    }
                    var finalUrl = this.url+this.term;
                    // check if pagination is set
                    if(this.$route.query.page !== undefined){
                        var page = this.$route.query.page;
                        finalUrl += '?page='+page;
                    }

                    // make ajax request
                    this.$store.commit('setSpinner', true);
                    this.$http.get(finalUrl)
                        .then((resp) => {
                            this.$store.commit('setList', resp.body);
                            this.$store.commit('setSpinner', false);
                        }, response => {
                            // if a error happens
                            this.$store.commit('setSpinner', false);
                            new Noty({
                                type: "error",
                                layout: 'bottomLeft',
                                text: response.statusText
                            }).show();
                        });
                }else{
                    alert("Please enter a search term")
                }
            }
        },

        watch:{
            '$route': function(){
                // make search when page is refreshed
                if(this.$route.params.term !== undefined){
                    this.term = this.$route.params.term;
                    this.search(false);
                }
            }
        },
    }
</script>
