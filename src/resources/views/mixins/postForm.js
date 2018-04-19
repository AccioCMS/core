export const postForm = {
    methods:{
        // load inputs when inserting a post
        loadCreateInputs(){
            this.categoriesOptions = false;
            this.selectedCategories = [];
            this.tagsOptions = [];
            this.selectedTags = {};
            this.columns = '';
            this.columnSlugs = '';
            this.selected = [];
            this.form = [];
            this.title = {};
            this.content = {};
            this.slug = {};
            this.status = {};
            this.languages = '';
            this.defaultLangSlug = '';
            this.activeLangsSlug = "";
            this.dateFormat = 'd MMMM yyyy';
            this.published_at = {date: '', time: {HH: "",mm: ""}, dateFormatted: ''};
            this.savedDropdownMenuVisible = false;
            this.customFieldsGroups = [];
            // display spinner
            this.$store.commit('setSpinner', true);

            // List of all languages
            var languagesPromise = this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/language/get-all?order=isDefault&type=desc')
                .then((resp) => {
                    this.languages = resp.body.data;

                    for(var k in this.languages){
                        if(this.languages[k].isDefault){
                            this.defaultLangSlug = this.languages[k].slug;
                            this.activeLang = this.languages[k].slug;
                            this.status[this.languages[k].slug] = 'published';
                        }else{
                            this.status[this.languages[k].slug] = 'draft';
                        }
                        this.slug[this.languages[k].slug] = "";
                        this.title[this.languages[k].slug] = "";
                        this.content[this.languages[k].slug] = "";
                        this.selectedTags[this.languages[k].slug] = "";
                    }
                    this.$store.commit('setLanguages', this.languages);
                }).then( (resp) => {
                    // ajax request to get custom fields
                    this.$http.get(
                        this.basePath+'/'+
                        this.$route.params.adminPrefix+'/'+
                        this.$route.params.lang+
                        '/json/custom-fields/get-by-app/post-type/create/0/'+
                        this.$route.params.post_type)
                            .then((resp) => {
                                this.loadCustomFields(resp.body, 'create');
                            });
                }).then((resp) => {
                    this.getFieldsAndPrepareValues();
                });

            // get post type
            var postTypePromise = this.$http.get(
                this.basePath+'/'+
                this.$route.params.adminPrefix+'/'+
                this.$route.params.lang+
                '/json/post-type/get-by-slug/'+
                this.$route.params.post_type)
                .then((resp) => {
                    this.hasFeaturedVideo = resp.body.hasFeaturedVideo;
                    this.hasCategories = resp.body.hasCategories;
                    this.isCategoryRequired = resp.body.isCategoryRequired;
                    this.hasTags = resp.body.hasTags;
                    this.isTagRequired = resp.body.isTagRequired;
                });

            // get all categories of post type
            const categoriesOptions = [];
            var categoryPromise = this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/category/get-all-without-pagination-by-post-type/'+this.$route.params.post_type)
                .then((resp) => {
                    this.categoriesOptions = resp.body;
                });

            // get all tags of post type
            const tagsOptions = [];
            var tagPromise = this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/tags/get-all-without-pagination-by-post-type/'+this.$route.params.post_type)
                .then((resp) => {
                    this.tagsOptions = resp.body;
                });

            // when all ajax request are done
            Promise.all([languagesPromise,categoryPromise,tagPromise,postTypePromise]).then(([v1,v2,v3,v4]) => {
                // get plugin panels
                this.getPluginsPanel(['post', this.$route.params.post_type], 'create');

                const global = this;
                setTimeout(function (e) {
                    global.$store.commit('setSpinner', false);
                }, 500);
            });
        },


        // get and prepare fields of a post type
        getFieldsAndPrepareValues(){
            // get the columns in from the post type table and set the form data to the column data
            this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/'+ this.$route.params.post_type+'/columns')
                .then((resp) => {
                    this.columns = resp.body.allColumn;
                    // this loop handles to populate the form with the arrays
                    for(var k in this.columns){
                        var tempArray = {};
                        if(this.columns[k].multioptionValues != ""){
                            if(this.columns[k].translatable){
                                this.columns[k].value = this.makeMultiLanguageValue('array');
                            }else{
                                // add value to the object / this value will be used to store the input value
                                this.columns[k].value = [];
                            }

                            // generate multioption values (options) array from the string
                            this.columns[k].multioptionValues = this.generateMultioptionsValue(this.columns[k].multioptionValues, this.columns[k].type.inputType);

                        }else{ // if it is not a multioption input type
                            if(this.columns[k].translatable == true){
                                // add value to the object / this value will be used to store the input value
                                this.columns[k].value = this.columns[k].value = this.makeMultiLanguageValue();
                            }else{
                                this.columns[k].value = ""; // add value to the object / this value will be used to store the input value
                            }
                        }
                        for(var key in this.columns[k]){
                            tempArray[key] = this.columns[k][key];
                        }
                        this.form.push(
                            tempArray
                        );
                    }
                    this.columnSlugs = resp.body.inTableColumnsSlugs;
                });
        },

        // generate multioptions value (the options for dropdown, checkboxes and radio buttons)
        generateMultioptionsValue(multioptionValues, inputType){
            let splited = multioptionValues.split(','); // split multioptionValues / each option with key and value as array parameter
            let tmp = [];
            if(typeof splited == "object"){
                // loop throw the options
                for(let i in splited){
                    // split the value form the key
                    let optionArray = splited[i].split(':');
                    if(typeof optionArray == "object" && optionArray[0] !== undefined && optionArray[1] !== undefined){
                        tmp.push([optionArray[0].trim(),optionArray[1].trim()]);
                    }
                }
            }
            return tmp;
        },

        // prepare values (v-model objects) of translatable fields
        makeMultiLanguageValue(type = "string"){
            let tmp = {};
            for(let langKey in this.languages){
                let slug = this.languages[langKey].slug;
                if(type == "string"){
                    tmp[slug] = "";
                }else if(type == "array"){
                    tmp[slug] = [];
                }else if(type == "object"){
                    tmp[slug] = {};
                }
            }
            return tmp;
        },

        // load inputs and their data when updating a post
        loadUpdateInputs(){
            this.columns = '';
            this.categoriesOptions = false;
            this.selectedCategories = [];
            this.tagsOptions = [];
            this.selectedTags = [];
            this.columnSlugs = '';
            this.selected  = [];
            this.form =[];
            this.title = {};
            this.content = {};
            this.slug = {};
            this.status = {};
            this.languages = '';
            this.defaultLangSlug = '';
            this.activeLangsSlug = "";
            this.dateFormat = 'd MMMM yyyy';
            this.published_at = {date: '', time: {HH: "",mm: ""}, dateFormatted: ''};
            this.createdByUserID = 0;
            this.savedDropdownMenuVisible = false;

            this.$store.commit('setSpinner', true);

            let customFieldsValuesTmp = {};
            // get the columns in from the post type table and set the form data to the column data
            let columnPromise = this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/details/'+this.$route.params.post_type+'/'+this.$route.params.id)
                .then((resp) => {
                    // get all languages
                    this.languages = resp.body.languages;
                    for(let k in this.languages){
                        if(this.languages[k].isDefault){
                            this.defaultLangSlug = this.languages[k].slug;
                            this.activeLang = this.languages[k].slug;
                        }
                    }
                    this.$store.commit('setLanguages', this.languages);

                    this.selectedCategories = resp.body.selectedCategories;
                    this.columns = this.populateValuesFromData(resp.body.details);
                    let published_at = resp.body.published_at;
                    this.createdByUserID = resp.body.createdByUserID;

                    this.selectedTags = resp.body.selectedTags;

                    if(!Object.keys(this.selectedTags).length){
                        let selectedTags = {};
                        for(let k in this.languages){
                            selectedTags[this.languages[k].slug] = []
                        }
                        this.selectedTags = selectedTags;
                    }

                    // populate the published_at (date and time) in the languages array
                    let date = published_at.split(" ")[0]; // get day form the published_at date time
                    let day = date.split("-")[0]; // get the day from the dateTime string
                    let month = date.split("-")[1]; // get the month from the dateTime string
                    month = parseInt(month) - 1;  // month - 1 -- how is needed in the dateTime object
                    let year = date.split("-")[2]; // get the year from the dateTime string

                    let time = published_at.split(" ")[1]; // get the time from the dateTime STRING
                    let H = time.split(":")[0]; // get only the hour form the time string
                    let M = time.split(":")[1]; // get only the minutes form the time string

                    this.published_at['date'] = new Date(date);
                    this.published_at['time'] = {HH: H,mm: M};

                    let media = resp.body.media;
                    if(!Object.keys(media).length){
                        media = {};
                    }
                    this.$store.commit('setMediaSelectedFiles', media);
                    // this loop handles to populate the form with the arrays
                    for(let k in this.columns){
                        let tempArray = {};
                        if(this.columns[k].multioptionValues != ""){
                            // generate multioption values (options) array from the string
                            this.columns[k].multioptionValues = this.generateMultioptionsValue(this.columns[k].multioptionValues, this.columns[k].type.inputType);
                        }

                        // get all columns and set as keys in the tempArray
                        for(let key in this.columns[k]){
                            tempArray[key] = this.columns[k][key];
                        }

                        this.form.push(
                            tempArray
                        );
                    }

                    this.columnSlugs = resp.body.inTableColumnsSlugs;
                    this.slug = resp.body.slug;
                    this.href = resp.body.href;
                    this.title = resp.body.title;
                    this.content = resp.body.content;
                    this.status = resp.body.status;
                    this.hasCategories = resp.body.hasCategories;
                    this.isCategoryRequired = resp.body.isCategoryRequired;
                    this.isTagRequired = resp.body.isTagRequired;
                    this.hasTags = resp.body.hasTags;

                    // TODO me i ndreq edhe custom fields edhe me ndrru ne nje funksion tjeter qita
                    for(let lK in this.languages){
                        if(this.title[this.languages[lK].slug] === undefined){
                            this.title[this.languages[lK].slug] = "";
                        }
                        if(this.content[this.languages[lK].slug] === undefined){
                            this.content[this.languages[lK].slug] = "";
                        }
                        if(this.status[this.languages[lK].slug] === undefined){
                            this.status[this.languages[lK].slug] = "";
                        }
                    }

                    customFieldsValuesTmp = resp.body.customFieldsValues;
                    this.loadCustomFields(resp.body.customFieldsGroups, 'update');
                }).then((resp) => {
                    // load the values of the custom fields
                    this.pupulateCustomFieldsValues(customFieldsValuesTmp);
                });

            // get all categories of post type
            const categoriesOptions = [];
            let categoryPromise = this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/category/get-all-without-pagination-by-post-type/'+this.$route.params.post_type)
                .then((resp) => {
                    this.categoriesOptions = resp.body;
                });
            // get all tags of post type
            const tagsOptions = [];
            let tagPromise = this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/tags/get-all-without-pagination-by-post-type/'+this.$route.params.post_type)
                .then((resp) => {
                    this.tagsOptions = resp.body;
                });

            // when all ajax request are done
            Promise.all([columnPromise,categoryPromise,tagPromise]).then(([v1,v2,v3]) => {
                const global = this;
                setTimeout(function (e) {
                    global.$store.commit('setSpinner', false);
                }, 500);
            });
        }

    }
};