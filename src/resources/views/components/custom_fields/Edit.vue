<template>
    <div class="componentsWs" dusk="customFieldEdit">
        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">
                    {{trans.__title}}
                    <small v-if="$route.params.id === undefined">{{trans.__create}}</small>
                    <small v-else>{{trans.__update}}</small>
                </h3>
            </div>
        </div>
        <!-- TITLE END -->

        <div class="clearfix"></div>

        <div class="row"><!-- row -->

            <!-- Loading component -->
            <spinner v-if="spinner" :width="'40px'" :height="'40px'" :border="'10px'"></spinner>

            <form class="form-horizontal form-label-left" id="store" enctype="multipart/form-data" v-if="!spinner">
                <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12" id="groupFromInputContainer"><!-- groupFromInputContainer -->
                    <div class="x_panel"><!-- x_panel -->
                        <div class="x_title">
                            <h2>{{trans.__createFormTitle}}</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content"><!-- x_content -->
                            <br />

                            <div class="form-group" id="form-group-title">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="title">{{trans.__formTitle}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" id="title" v-model="form.title" @change="generateSlug">
                                    <div class="alert" v-if="StoreResponse.errors.title" v-for="error in StoreResponse.errors.title">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-slug">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="slug">{{trans.__slug}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" id="slug" v-model="form.slug" :readonly="readonlySlug" @dblclick="readonlySlug = false">
                                    <img :src="basePath+'/public/images/loading.svg'" class="slugLoading" v-if="displaySlugLoading">
                                    <div class="alert" v-if="StoreResponse.errors.slug" v-for="error in StoreResponse.errors.slug">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-description">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__formDescription}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" id="description" v-model="form.description">
                                    <div class="alert" v-if="StoreResponse.errors.description" v-for="error in StoreResponse.errors.description">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-isActive">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__isActive}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="checkbox" class="checkboxStyled" style="margin-top:12px;" id="isActive" v-model="form.isActive">
                                    <div class="alert" v-if="StoreResponse.errors.isActive" v-for="error in StoreResponse.errors.isActive">{{ error }}</div>
                                </div>
                            </div>

                        </div><!-- x_content -->

                        <div class="clearfix"></div>

                        <div class="x_title" style="margin-top:15px;">
                            <h2>{{ trans.__createFormRulesTitle }}</h2>
                            <div class="clearfix"></div>
                        </div>

                        <div class="x_content"><!-- x_content -->
                            <br />
                            <div class="inputGroupTitle col-md-12 col-sm-12 col-xs-12">
                                <h4>{{trans.__rulesUnderTitle}}</h4>
                            </div>

                            <div class="clearfix"></div>

                            <div class="rulesGroups" v-for="(group, index) in getGroupRules" :key="index" v-if="!spinner">
                                <div class="background">
                                    <!-- Group rules inputs (Component) -->
                                    <group-rules
                                            v-for="(item, ruleIndex) in group"
                                            :group="item"
                                            :groupIndex="index"
                                            :postTypeList="postTypeList"
                                            :postsOptions="postsOptions"
                                            :index="ruleIndex"
                                            :key="ruleIndex"
                                            ref="groupRules"
                                            v-on:remove="removeRule"></group-rules>

                                    <div class="addRule">
                                        <i style="cursor:pointer;" @click="addRule(index)" class="fa fa-plus-circle fa-2x"></i>
                                    </div>
                                </div>

                                <div style="text-align:center; margin-top: 20px;">
                                    <h5 style="text-transform: uppercase; font-weight: bold;">{{trans.__or}}</h5>
                                </div>
                            </div>

                            <div style="text-align:center; margin-top: 20px;">
                                <button class="btn btn-default" @click.prevent="addGroup" id="addGroup">
                                    Add a new group rules
                                </button>
                            </div>

                        </div><!-- x_content -->
                    </div><!-- x_panel -->
                </div><!-- groupFromInputContainer -->

                <div class="col-lg-8 col-md-12 col-sm-12 col-xs-12">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2 class="customFieldsPanelTitle">{{trans.__customFieldsTitle}}</h2>
                            <button @click.prevent="addField(0)" class="btn btn-info newField">{{trans.__newFieldBtn}}</button>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content" v-if="!spinner">
                            <br />
                            <field
                                    v-for="(field, index) in form.fields"
                                    v-if="field.parent == 0"
                                    :field="field"
                                    :isSubField="false"
                                    :allFields="form.fields"
                                    :index="index"
                                    :key="index"
                                    :trans="trans"
                                    :languages="languages"
                                    :dbTables="dbTables"
                                    :options="options"
                                    :isInUpdate="isInUpdate"
                                    :groupSlug="form.slug"
                                    v-on:addSubField="addSubField"
                                    v-on:removeField="removeField"
                                    v-on:removeFieldRule="removeFieldRule"
                                    v-on:refreshFieldRulesValues="refreshFieldRulesValues">
                            </field>
                        </div>
                    </div>
                </div>
            </form>

            <div class="mainButtonsContainer" v-if="!spinner">
                <div class="row">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" id="globalSaveBtn" @click="store('save')">{{trans.__globalSaveBtn}}</button>
                        <button type="button" class="btn btn-primary dropdown-toggle" @click="savedDropdownMenuVisible = !savedDropdownMenuVisible">
                            <i class="fa fa-caret-up"></i>
                        </button>
                        <ul class="savedDropdownMenu" v-if="savedDropdownMenuVisible">
                            <li><a style="cursor:pointer" @click="store('close')">{{trans.__globalSaveAndCloseBtn}}</a></li>
                            <li><a style="cursor:pointer" @click="store('new')">{{trans.__globalSaveAndNewBtn}}</a></li>
                        </ul>
                    </div>

                    <button type="button" class="btn btn-info" id="globalCancel" @click="redirect('custom-fields-list')">{{trans.__globalCancelBtn}}</button>
                </div>
            </div>

        </div><!-- row -->
    </div>
</template>
<style src="./style.css" scoped></style>
<script>
    import GroupRules from './GroupRules.vue'
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';
    import { lists } from '../../mixins/lists';
    import { trans } from './trans';
    import { update } from './update';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated, lists, trans, update],
        components:{
            'group-rules':GroupRules,
        },
        created(){
            this.$store.commit('setSpinner', true);
        },
        mounted() {
            // get post-types
            var postTypePromise = this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/post-type/get-all')
                .then((resp) => {
                    var list = resp.body.data;
                    for(let k in list){
                        this.postTypeList[k] = {name: list[k].name, value: list[k].slug};
                        this.postsOptions.push({
                            title: list[k].name,
                            options: [
                                { name: 'Title', type: 'title', key: list[k].slug+'-title', app: 'post', slug: list[k].slug },
                                { name: 'Form', type: 'form',  key: list[k].slug+'-form', app: 'post', slug: list[k].slug },
                                { name: 'Status', type: 'status', key: list[k].slug+'-status', app: 'post', slug: list[k].slug }
                            ]
                        });
                    }
                });

            var languagePromise = this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/language/get-all?order=isDefault&type=desc')
                .then((resp) => {
                    this.languages = resp.body.data;
                });

            // get table names for custom fields ( table names that can be used to make the values of a dropdown field )
            var dbTablesPromise = this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/post-type/get-tables')
                .then((resp) => {
                    this.dbTables = resp.body;
                });

            // this function makes ajax request and fills updatePromise if the $route.params.id is set
            this.fillFormOnUpdate();
            // when all ajax request are done
            Promise.all([postTypePromise,languagePromise,dbTablesPromise,this.updatePromise]).then(([v1,v2,v3,v4]) => {
                this.$store.commit('setSpinner', false);
            });

        },
        data(){
            return{
                activeTargetedAppListResults: '',
                languages: [],
                savedDropdownMenuVisible: false,
                postTypeList: [],
                postsOptions: [],
                readonlySlug: true,
                displaySlugLoading: false,
                isSlugCreated: false,
                dbTables: "",
                updatePromise: null,
                isInUpdate: false,
                form:{
                    id: 0,
                    title: '',
                    slug: '',
                    description: '',
                    isActive: true,
                    groupRules: [
                        [
                            {
                                app: '',
                                operator: {label:'Equals' , value: 'equals'},
                                value: '',
                            },
                        ],
                    ],
                    fields: [],
                },
                rulesOptions: [],
                valueOptions: [],
                options: [
                    { inputType: 'text', typeName: 'Text' },
                    { inputType: 'textarea', typeName: 'Long text' },
                    { inputType: 'editor', typeName: 'Editor' },
                    { inputType: 'email', typeName: 'Email' },
                    { inputType: 'number', typeName: 'Number' },
                    { inputType: 'image', typeName: 'Image' },
                    { inputType: 'file', typeName: 'File' },
                    { inputType: 'video', typeName: 'Video' },
                    { inputType: 'date', typeName: 'Date' },
                    { inputType: 'boolean', typeName: 'Boolean (True of False)' },
                    { inputType: 'checkbox', typeName: 'Checkboxes' },
                    { inputType: 'radio', typeName: 'Radio (One choice options)' },
                    { inputType: 'dropdown', typeName: 'Dropdown' },
                    { inputType: 'db', typeName: 'Dropdown from DB' },
                ],
            }
        },
        methods: {
            // make ajax request for storing in database
            store(redirectChoice){
                this.$store.dispatch('openLoading');
                this.$store.dispatch('store',{
                    data: this.form,
                    url: this.basePath+'/'+this.getAdminPrefix+"/json/custom-fields/store",
                    error: "Problem occurred. Please try again later."
                }).then((resp) => {
                    if(resp.code == 200){
                        this.onStoreBtnClicked('custom-fields-',redirectChoice, resp.id);
                        this.savedDropdownMenuVisible = false;
                        // refresh page
                        this.$router.go({path: this.$route.path});
                    }
                });
            },
            // add new rule group
            addGroup(){
                this.form.groupRules.push([{ app: '', operator: {label:'Equals' , value: 'equals'}, value: '' }]);
            },
            // add new rule to the list
            addRule(index){
                this.form.groupRules[index].push({ app: '', operator: {label:'Equals' , value: 'equals'}, value: '' });
            },
            // remove a rule from the rule group
            // if group has no rules then removes the group to
            removeRule(ev){
                var group = this.form.groupRules;
                // reset group to empty temporarily
                this.form.groupRules = [];
                delete group[ev.groupIndex][ev.index];

                // reorder keys of group rules
                var result = [];
                for(let k in group[ev.groupIndex]){
                    result.push(group[ev.groupIndex][k]);
                }
                group[ev.groupIndex] = result;

                // reorder group keys
                var resultGroup = [];
                for(let k in group){
                    if(Object.keys(group[k]).length){
                        resultGroup.push(group[k]);
                    }
                }
                this.form.groupRules = resultGroup;
            },
            // use to add new custom field (when new field btn is clicked)
            addField: function (parent = 0){
                var label = {};
                var placeholder = {};
                var note = {};
                var list = this.languages;
                for(let langKey in list){
                    label[list[langKey].slug] = "";
                    placeholder[list[langKey].slug] = "";
                    note[list[langKey].slug] = "";
                }
                var id = parseInt(Object.keys(this.form.fields).length);
                var order = parseInt(Object.keys(this.form.fields).length);

                this.form.fields.push({
                    id: 'NEW'+ ++id,
                    parent: parent,
                    label: label,
                    order: order,
                    slug: '',
                    placeholder: placeholder,
                    defaultValue: '',
                    note: note,
                    multioptionValues: '',
                    isTranslatable: false,
                    isRequired: false,
                    isActive: true,
                    properties: {
                        dbTable: '',
                        characterLimit: '',
                        rows: '',
                        allowHTML: true,
                        allowParagraphs: true,
                        toolbar: 'basic',
                        minWidth: '',
                        minHeight: '',
                        maxWidth: '',
                        maxHeight: '',
                        maxUploadSize: '',
                        min: 0,
                        max: 0,
                        rangeLabel: '',
                        allowOther: false,
                    },
                    isMultiple: false,
                    layout: 'row',
                    type: {
                        inputType: "text",
                        typeName: "Text"
                    },
                    wrapperStyle: {
                        width: '',
                        class: '',
                        id: '',
                    },
                    fieldStyle: {
                        width: '',
                        class: '',
                        id: '',
                    },
                    rules: [],
                });

            },
            addSubField(parent){
                this.addField(parent);
            },
            // remove a field form field list
            removeField(index){
                var fields = this.form.fields;
                this.form.fields = [];
                for(let k in fields){
                    if(k != index){
                        var rulesGroup = fields[k].rules;
                        fields[k].rules = [];
                        for(let ruleGroupKey in rulesGroup){
                            var tmp = [];
                            for(let ruleKey in rulesGroup[ruleGroupKey]){
                                if(rulesGroup[ruleGroupKey][ruleKey].field.index != index){
                                    tmp.push(rulesGroup[ruleGroupKey][ruleKey]);
                                }
                            }

                            if(tmp.length){
                                fields[k].rules.push(tmp);
                            }
                        }

                        this.form.fields.push(fields[k]);
                    }
                }
                // refresh the rules indexes when a field is removed
                this.refreshRulesIndexes();
            },
            // refresh the rules indexes when a field is removed
            refreshRulesIndexes(){
                for(let k in this.form.fields){
                    for(let ruleGroupKey in this.form.fields[k].rules){
                        for(let ruleKey in this.form.fields[k].rules[ruleGroupKey]){
                            var slug = this.form.fields[k].rules[ruleGroupKey][ruleKey].field.slug;
                            this.form.fields[k].rules[ruleGroupKey][ruleKey].field.index = this.getFieldIndexBySlug(slug);
                        }
                    }
                }
            },
            // get a field by using his index
            getFieldIndexBySlug(slug){
                for(let k in this.form.fields){
                    if(this.form.fields[k].slug == slug){
                        return k;
                    }
                }
                return undefined;
            },

            // loop throw rules of a specific field and remove the requested one
            removeFieldRule(request){
                var rules = this.form.fields[request.fieldIndex].rules;
                this.form.fields[request.fieldIndex].rules = [];

                for(let ruleGroupKey in rules){
                    if(ruleGroupKey == request.groupIndex){
                        var tmp = [];
                        for(let ruleKey in rules[ruleGroupKey]){
                            if(ruleKey != request.ruleIndex){
                                tmp.push(rules[ruleGroupKey][ruleKey]);
                            }
                        }
                        if(tmp.length){
                            this.form.fields[request.fieldIndex].rules.push(tmp);
                        }
                    }
                }
            },
            // empty the rules values if a field type is changed
            refreshFieldRulesValues(fieldIndex){
                for(let fieldKey in this.form.fields){
                    for(let ruleGroupKey in this.form.fields[fieldKey].rules){
                        for(let ruleKey in this.form.fields[fieldKey].rules[ruleGroupKey]){
                            if(this.form.fields[fieldKey].rules[ruleGroupKey][ruleKey].field.index == fieldIndex){
                                this.form.fields[fieldKey].rules[ruleGroupKey][ruleKey].value = "";
                            }
                        }
                    }
                }
            },
            // generate the slug for the custom field group
            generateSlug(){
                if(!this.isInUpdate && !this.isSlugCreated){
                    this.displaySlugLoading = true;
                    // get generated key
                    this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/custom-fields/get-slug/'+this.form.title)
                        .then((resp) => {
                            if(resp.status == 200){
                                this.form.slug = resp.body;
                                this.isSlugCreated = true;
                                this.displaySlugLoading = false;
                            }
                        }, error => {
                            console.log(error);
                            this.displaySlugLoading = false;
                        });
                }
            },
        },

        computed: {
            getGroupRules(){
                return this.form.groupRules;
            },
        },
        watch:{
            // watch for url changes and component doesn't change
            '$route': function(){
                this.form = {
                    id: 0,
                    title: '',
                    slug: '',
                    description: '',
                    isActive: false,
                    groupRules: [
                        [
                            {
                                app: '',
                                operator: {label:'Equals' , value: 'equals'},
                                value: '',
                            },
                        ],
                    ],
                    fields: [],
                };
            }
        },
    }
</script>
