<template>
    <div class="col-md-12 col-sm-12">
        <form id="advancedSearchForm">
            <div class="col-md-6 col-sm-6">
                <div class="startingInputs col-md-12 col-sm-12 startingInputsContainer" v-for="(field, index) in form.fields">

                    <div class="form-group">
                        <div class="col-md-4 col-sm-4 col-xs-4" style="margin-top:10px;">
                            <label class="control-label col-md-12 col-sm-12 col-xs-12">{{trans.__field}} :</label>
                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <multiselect
                                        v-model="field.type"
                                        deselect-label="Can't remove this value"
                                        track-by="name"
                                        label="name"
                                        placeholder="Select one"
                                        :options="options"
                                        :searchable="false"
                                        :allow-empty="false"
                                        :id="options"
                                        @select="dispatchAction($event, index)"></multiselect>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-4 col-sm-4 col-xs-4">
                            <label class="control-label col-md-12 col-sm-12 col-xs-12">{{trans.__operator}}:</label>
                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <select class="form-control operator" v-model="field.operator">
                                    <option class="select-one" :disabled="true">{{trans.__default}}</option>
                                    <option value="greater-than" class="greater-than" v-show="field.activeValueType != 'boolean'">{{trans.__greater}}</option>
                                    <option value="less-than" class="less-than" v-show="field.activeValueType != 'boolean'">{{trans.__less}}</option>
                                    <option value="equal" class="equal">{{trans.__equal}}</option>
                                    <option value="not-equal" class="not-equal">{{trans.__notEqual}}</option>
                                    <option value="contains" class="contains" v-show="field.activeValueType != 'boolean'">{{trans.__contains}}</option>
                                    <option value="starts-with" class="starts-with" v-show="field.activeValueType != 'boolean'">{{trans.__starts}}</option>
                                    <option value="ends-with" class="ends-with" v-show="field.activeValueType != 'boolean'">{{trans.__ends}}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-4 col-sm-4 col-xs-4">
                            <label class="control-label col-md-12 col-sm-12 col-xs-12">{{trans.__value}}:</label>
                            <div class="col-md-12 col-sm-12 col-xs-12 valueContainer">

                                <input type="text" class="form-control value text" v-model="field.value" v-if="field.activeValueType == 'text'">

                                <input type="email" class="form-control value email" v-model="field.value" v-if="field.activeValueType == 'email'">

                                <select class="form-control value boolean" v-model="field.boolean" v-if="field.activeValueType == 'boolean'">
                                    <option :value="1">True</option>
                                    <option :value="0">False</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="form-group">
                <div class="col-md-2 col-sm-2 col-xs-2">
                    <button @click.prevent="addField" class="btn btn-info addFieldBtn">{{trans.__newFieldBtn}}</button>
                    <button @click.prevent="advancedSearchSubmit" class="btn btn-info advancedSearchSubmit">{{trans.__searchBtn}}</button>
                </div>
            </div>

        </form>

    </div>
</template>
<script>
    import { globalComputed } from '../../mixins/globalComputed'
    import { globalMethods } from '../../mixins/globalMethods'
    import { globalData } from '../../mixins/globalData'

    export default{
        mixins: [globalComputed, globalMethods, globalData],
        props:['optionsURL', 'searchURL', 'advancedSearchFields'],
        mounted() {
            this.$http.get(this.optionsURL)
                .then((resp) => {
                    this.options = resp.body;
                });

            // If advanced search is made
            if(this.$route.query.advancedSearch == 1){
                this.isAdvancedSearchOpen = true;
                this.form.fields = this.getAdvancedSearchFieldsFromURL;

                // if order (order column) query param is set
                if(this.$route.query.order !== undefined){
                    this.form.orderBy = this.$route.query.order;
                }

                // if type (order type) query param is set
                if(this.$route.query.type !== undefined){
                    this.form.orderType = this.$route.query.type;
                }

                if(this.$route.query.page !== undefined){
                    this.form.page= this.$route.query.page;
                }

                this.advancedSearchSubmit();
            }

            // translations
            this.trans = {
                __newFieldBtn: this.__('user.advancedFields.newFieldBtn'),
                __searchBtn: this.__('user.advancedFields.searchBtn'),
                __field: this.__('user.advancedFields.inputLabels.field'),
                __operator: this.__('user.advancedFields.inputLabels.operator'),
                __value: this.__('user.advancedFields.inputLabels.value'),
                __default: this.__('user.advancedFields.operatorTypes.default'),
                __greater: this.__('user.advancedFields.operatorTypes.greater'),
                __less: this.__('user.advancedFields.operatorTypes.less'),
                __equal: this.__('user.advancedFields.operatorTypes.equal'),
                __notEqual: this.__('user.advancedFields.operatorTypes.notEqual'),
                __contains: this.__('user.advancedFields.operatorTypes.contains'),
                __starts: this.__('user.advancedFields.operatorTypes.starts'),
                __ends: this.__('user.advancedFields.operatorTypes.ends'),
            };
        },
        data(){
            return{
                form:{
                    fields: [
                        { type: '', operator: 'Select one', value: '', boolean: '', activeValueType: 'text' }
                    ],
                    page: 1
                },
                options: [],
                page: '',
            }
        },
        methods: {
            // add a new search row filter
            addField(e){
                this.form.fields.push({ type: '', operator: '', value: '', boolean: '', activeValueType: 'text' });
            },
            // called when multiselect field type is changed
            // used to show the desired value field type (text, email, boolean etc) depending what user has selected in the multiselect field
            dispatchAction ( actionName, index ){
                if(actionName.type == 'email'){
                    this.form.fields[index].activeValueType = "email";
                }else if(actionName.type == 'string'){
                    this.form.fields[index].activeValueType = "text";
                }else if(actionName.type == 'boolean'){
                    this.form.fields[index].activeValueType = "boolean";
                }
            },
            // makes the search ajax request
            advancedSearchSubmit(){
                this.$store.commit('setSpinner', true);
                this.$http.post(this.searchURL, this.form)
                    .then((resp) => {
                        this.$store.commit('setList', resp.body);

                        var urlParams = '';
                        var count = 0;
                        let queryParams = {};

                        if(this.$route.query.page !== undefined){
                            queryParams.page = this.$route.query.page;
                        }else{
                            queryParams.page = 1;
                        }
                        queryParams.advancedSearch = 1;

                        // loop throw advanced search data and
                        // create url as string from them
                        for(let key in this.form.fields){
                            urlParams = '';
                            if(this.form.fields[key]['type'] == ''){
                                urlParams += 'null,';
                            }else{
                                urlParams += this.form.fields[key]['type']['name']+",";
                                urlParams += this.form.fields[key]['type']['type']+",";
                                urlParams += this.form.fields[key]['type']['db-column']+",";
                            }

                            if(this.form.fields[key]['operator'] == ''){
                                urlParams += 'null,'
                            }else{
                                urlParams += this.form.fields[key]['operator']+",";
                            }

                            if(this.form.fields[key]['value'] == ''){
                                urlParams += 'null,'
                            }else{
                                urlParams += this.form.fields[key]['value']+",";
                            }

                            if(this.form.fields[key]['boolean'] == ''){
                                urlParams += 'null,'
                            }else{
                                urlParams += this.form.fields[key]['boolean']+",";
                            }

                            urlParams += this.form.fields[key]['activeValueType'];

                            queryParams['field'+count] = urlParams;

                            count++;
                            if(count < this.form.fields.length){
                                urlParams += '&';
                            }
                        }
                        this.$router.push({ query: Object.assign({}, this.$route.query, queryParams) });
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
    }
</script>
