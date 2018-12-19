export const globalComputed = {
    computed: {
        getAdminPrefix(){
            // return if we are in the advanced search
            return this.$route.params.adminPrefix;
        },
        getCurrentLang(){
            //  get the current language slug
            return this.$route.params.lang;
        },
        getGlobalData(){
            //  get global data from vuex
            return this.$store.getters.get_global_data;
        },
        // info about the logged in user
        Auth(){
            return this.$store.getters.get_auth;
        },
        getID(){
            //  get the id from route params
            return this.$route.params.id;
        },
        getHasPermission(){
            // return if user has permission
            return this.$store.getters.get_has_permission;
        },
        getTranslation(){
            // returns translated value
            return this.$store.getters.get_translation;
        },
        // get the max pagination number from $store
        getList(){
            // returns the list used to populate tables of apps
            return this.$store.getters.get_list;
        },
        // get languages
        getLanguages(){
            return this.$store.getters.get_languages;
        },
        // get the max pagination number from $store
        // getMaxPag(){
        //     return this.$store.getters.get_maxPaginationNr;
        // },
        // get the order type
        // getPage(){
        //     if(this.$route.query.pagination !== undefined && this.$route.query.pagination != ''){
        //         return this.$route.query.pagination;
        //     }
        //     return 1;
        // },
        // get advanced_search_form_data
        advancedSearchFormData(){
            return this.$store.getters.get_advanced_search_form_data;
        },
        // get if spinner is active
        spinner(){
            return this.$store.getters.get_spinner;
        },
        // get base url
        baseURL(){
            return this.$store.getters.get_base_url;
        },
        // get base path
        basePath(){
            return this.$store.getters.get_base_path;
        },
        // get logouturl
        logoutLink(){
            return this.$store.getters.get_logout_link;
        },
        // get base path
        adminBaseURL(){
            return this.$store.getters.get_base_url+'/'+this.$route.params.adminPrefix;
        },
        // global permissions
        getGlobalPermissions(){
            // return user permissions
            return this.$store.getters.get_global_data.permissions;
        },
        // open module (used in navigation)
        getOpenModule(){
            return this.$store.getters.get_open_module;
        },
        // froala config options
        froalaFullConfig(){
            return this.$store.getters.get_froala_full_config;
        },
        // froala config options
        froalaCompactConfig(){
            return this.$store.getters.get_froala_compact_config;
        },
        // froala config options
        froalaBasicConfig(){
            return this.$store.getters.get_froala_basic_config;
        },
        // get query parameters automatic
        getQueryParamsAsString(){
            // get all url query params as a string
            let queryPath = "?";
            let count = 1;
            let length = Object.keys(this.$route.query).length;
            for(let k in this.$route.query){
                queryPath += k+"="+this.$route.query[k];
                if(count < length) {
                    queryPath += "&";
                }
                count++;
            }
            if(length) {
                return queryPath;
            }
            return "";
        },
        // get the fields object collection form urls query parameters
        getAdvancedSearchFieldsFromURL(){
            let params = this.$route.query;
            let fields = [];
            let count = 0;
            for(let k in params){
                if(k.includes('field')) {
                    var options = params[k].split(',');
                    fields[count] = {
                        type: {
                            'name': options[0],
                            'type': options[1],
                            'db-column': options[2]
                        },
                        operator: options[3],
                        value: options[4],
                        boolean: options[5],
                        activeValueType: options[6]
                    };
                    count++;
                }
            }
            return fields;
        },
        // all plugins config
        pluginsConfigs(){
            return this.$store.getters.get_plugins_configs;
        },

        // ajax response when store method of vuex ( in store.js ) is called
        StoreResponse(){
            return this.$store.getters.get_store_response;
        }
    }
};
