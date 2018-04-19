<template>
    <div class="paginationContainer" v-show="getList.total && Object.keys(getPagesToPaginate).length > 1">
        <ul class="pagination">
            <li class="paginate_button previous" id="firstPage">
                <a style="cursor:pointer" @click.prevent="paginate(1)">{{trans.__first}}</a>
            </li>

            <li class="paginate_button previous" id="datatable-checkbox_previous">
                <a style="cursor:pointer" @click.prevent="paginate('prev')">{{trans.__previous}}</a>
            </li>

            <li class="paginate_button numbers" v-for="n in getPagesToPaginate">
                <a style="cursor:pointer" @click.prevent="paginate(n)" :id="'b' + n" :class="{ active: getList.current_page == n }">{{ n }}</a>
            </li>

            <li class="paginate_button next" id="datatable-checkbox_next">
                <a style="cursor:pointer" @click.prevent="paginate('next')">{{trans.__next}}</a>
            </li>

            <li class="paginate_button previous" id="lastPage">
                <a style="cursor:pointer" @click.prevent="paginate(getList.last_page)">{{trans.__last}}</a>
            </li>
        </ul>
    </div>
</template>
<style>
    .paginationContainer{
        float: right;
    }
    .paginationContainer .pagination .active{
        background-color: #ddd;
    }
</style>
<script>
    import { globalComputed } from '../../mixins/globalComputed'
    import { globalData } from '../../mixins/globalData'

    export default{
        mixins: [globalComputed, globalData],
        props:['listUrl', 'dataSearchUrl', 'advancedSearchPostUrl'],
        mounted(){
            this.trans = {
                __next: this.__('pagination.next'),
                __previous: this.__('pagination.previous'),
                __first: this.__('pagination.first'),
                __last: this.__('pagination.last'),
            };
        },
        data(){
            return{
                formData: {}, // if a post request is needed (Exm. in advanced search)
            }
        },
        methods:{
            paginate(value){
                // change page number
                let page = parseInt(this.getList.current_page);
                if(value == "next"){
                    if(page < this.getList.last_page){
                        page++;
                    }
                }else if(value == "prev"){
                    if(page > 1){
                        page--;
                    }
                }else{
                    page = value;
                }

                // if page number isn't changed don't make the ajax request for the same data
                if(page == this.getList.current_page){
                    return;
                }

                // replace pagination number
                let queryParamsToChange = {};
                queryParamsToChange.page = page;
                this.$router.replace({ query: Object.assign({}, this.$route.query, queryParamsToChange) });

                // set url depending if the request is made from the normal list or search route
                var url = false;
                var method = "get";
                // make search when page is refreshed
                if(this.$route.params.term !== undefined){
                    url = this.dataSearchUrl+this.$route.params.term;
                }else if(this.$route.query.advancedSearch == 1){
                // if advanced search is made
                    method = "post";
                    let fields = this.getAdvancedSearchFieldsFromURL;
                    this.formData.fields = fields;
                    this.formData.pagination = page;
                    url = this.advancedSearchPostUrl;
                }else{
                // default list query ( all data without any filter )
                    url = this.listUrl;
                }
                // add query params if they exist
                url += this.getQueryParamsAsString;

                if(url){
                    this.$store.commit('setSpinner', true);
                    this.$http[method](url, this.formData)
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
                }
            },

            // use translation method of vuex
            __(key){
                this.$store.dispatch('__', key);
                return this.getTranslation;
            },
            // base url to resources folder
            resourcesUrl(url){
                return this.generateUrl('/public'+url);
            },
            // repair url with the base
            generateUrl(url){
                return this.baseURL+url;
            }
        },

        computed: {
            getPagesToPaginate(){
                let firstNr = this.getList.current_page - 1;

                if(this.getList.current_page > 2){
                    firstNr = this.getList.current_page - 2;
                }else{
                    firstNr = 1;
                }

                let lastNr = this.getList.current_page + 2;

                if(firstNr == 1){
                    lastNr = firstNr + 4;
                    if(lastNr > this.getList.last_page){
                        lastNr = this.getList.last_page;
                    }
                }

                if(lastNr > this.getList.last_page){
                    lastNr = this.getList.last_page;
                }

                if(this.getList.current_page == this.getList.last_page){
                    firstNr = this.getList.current_page - 4;
                    if(firstNr < 1){
                        firstNr = 1;
                    }
                }

                let result = [];
                for(let i = firstNr; i <= lastNr; i++){
                    result.push(i);
                }

                return result;
            },
        }
    }
</script>
