export const lists = {
    methods:{
        // get default list data
        getListData(){
            var promise = "";
            // clean the list first
            this.$store.dispatch('setList', []);

            // if not search route
            if(this.$route.params.term === undefined){
                this.$store.commit('setSpinner', true);
                // make ajax request
                promise = this.$http.get(this.listUrl+this.getQueryParamsAsString)
                    .then((resp) => {
                        this.$store.dispatch('setList', resp.body);
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
            return promise;
        },
        // change the route (url)
        orderBy(columnName){
            // replace pagination number
            let queryParamsToChange = {};
            queryParamsToChange.order = columnName;
            var type = 'asc';
            if(this.$route.query.type !== undefined){
                if(this.$route.query.type == 'asc'){
                    type = 'desc';
                }
            }
            queryParamsToChange.type = type;
            this.$router.push({ query: Object.assign({}, this.$route.query, queryParamsToChange) });
            // refresh the list of the table
            this.refreshList();
        },
        // change the icon of the table header up or down depending of the type parameter in url query
        tableHeaderOrderIcons(columnName){
            if(this.$route.query.type !== undefined){
                if(this.$route.query.type == 'desc' && this.$route.query.order == columnName){
                    return 'fa fa-long-arrow-up';
                }
            }
            return 'fa fa-long-arrow-down';
        },
        // refresh list ( make a ajax request to refresh data of table )
        refreshList(){
            var url = '';
            var method = 'get';
            var formData = {};
            if(this.$route.query.advancedSearch == 1){
                // when a advanced search list is being refreshed
                method = 'post';
                let fields = this.getAdvancedSearchFieldsFromURL;
                formData.fields = fields;
                formData.page = 1;
                url = this.advancedSearchPostUrl;

                // if order (order column) query param is set
                if(this.$route.query.order !== undefined){
                    formData.orderBy = this.$route.query.order;
                }

                // if type (order type) query param is set
                if(this.$route.query.type !== undefined){
                    formData.orderType = this.$route.query.type;
                }

            }else if(this.$route.params.term !== undefined){
                // when a search list is being refreshed
                url = this.dataSearchUrl+this.$route.params.term;
            }else{
                // when the default list is being refreshed
                url = this.listUrl;
            }
            url += this.getQueryParamsAsString;

            this.$store.commit('setSpinner', true);
            this.firstTime = true;
            // make ajax request
            this.$http[method](url, formData)
                .then((resp) => {
                    this.$store.dispatch('setList', resp.body);
                    this.$store.commit('setSpinner', false);
                    this.$store.dispatch('closeLoading');
                }, response => {
                    // if a error happens
                    this.$store.commit('setSpinner', false);
                    this.$store.dispatch('closeLoading');
                    new Noty({
                        type: "error",
                        layout: 'bottomLeft',
                        text: response.statusText
                    }).show();
                });
        },
        // delete row
        deleteItem(id, i){
            this.openedItemActionBar = 0;
            this.$store.dispatch('openLoading');
            this.$http.get(this.deleteUrl+id)
                .then((resp) => {
                    let response = resp.body;
                    if(response.code === 200){
                        this.$store.dispatch('handleErrors', {response});
                        this.refreshList();  // refresh the list of the table
                    }else{
                        // noty notification
                        this.$store.dispatch('closeLoading');
                        this.noty("error", 'bottomLeft', "Error occurred. Please try again later.", 3000);
                    }
                }, response => {
                    // if a error happens
                    this.$store.commit('setSpinner', false);
                    this.$store.dispatch('closeLoading');
                    new Noty({
                        type: "error",
                        layout: 'bottomLeft',
                        text: response.statusText
                    }).show();
                });
        },
        // this function is used by bulk delete - it deletes multiple list items
        deleteList(object = null){
            if(!Object.keys(this.bulkDeleteIDs).length){
                alert("Please select a item to delete");
                return;
            }
            if(object === null){
                var object = this.bulkDeleteIDs;
            }

            this.$store.dispatch('openLoading');
            this.$http.post(this.bulkDeleteUrl, object)
                .then((resp) => {
                    this.refreshList();  // refresh the list of the table
                    this.bulkDeleteIDs = []; // reset id list after they are deleted
                    var response = resp.body;
                    this.$store.dispatch('handleErrors', {response});
                }, response => {
                    // if a error happens
                    this.$store.commit('setSpinner', false);
                    this.$store.dispatch('closeLoading');
                    new Noty({
                        type: "error",
                        layout: 'bottomLeft',
                        text: response.statusText
                    }).show();
                });
        },

    },
};
