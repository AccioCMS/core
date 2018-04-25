<template>
    <div class="componentsWs">

        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}}</h3>
            </div>
        </div>
        <!-- TITLE END -->

        <div class="clearfix"></div>

        <div class="row">
            <form class="form-horizontal form-label-left" id="store" enctype="multipart/form-data">

                <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>{{trans.__createFormTitle}}</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <br />

                            <div class="form-group" id="form-group-name">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__name}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" id="name" v-model="form.name" @change="createSlug(form.name)">
                                    <div class="alert" v-if="StoreResponse.errors.name" v-for="error in StoreResponse.errors.name">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group"  id="form-group-slug">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__slug}}</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" id="slug" v-model="form.slug" @dblclick="removeReadonly('slug')" readonly>
                                    <img :src="resourcesUrl('/images/loading.svg')" class="slugLoading">
                                    <div class="alert" v-if="StoreResponse.errors.slug" v-for="error in StoreResponse.errors.slug">{{ error }}</div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-visible">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__visible}}</label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div id="isVisible" class="btn-group" data-toggle="buttons">
                                        <label class="btn btn-default active" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.isVisible = true">
                                            <input type="radio" name="visible" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                        </label>
                                        <label class="btn btn-primary false" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.isVisible = false">
                                            <input type="radio" name="visible" value="false"> {{trans.__false}}
                                        </label>
                                        <div class="alert" v-if="StoreResponse.errors.isVisible" v-for="error in StoreResponse.errors.isVisible">{{ error }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-hasCategories">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__hasCategories}}</label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div id="hasCategories" class="btn-group" data-toggle="buttons">
                                        <label class="btn btn-default yes" :class="{ active: form.hasCategories }" @click="form.hasCategories = true">
                                            <input type="radio" name="hasCategories" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                        </label>
                                        <label class="btn btn-primary no" :class="{ active: !form.hasCategories }" @click="form.hasCategories = false">
                                            <input type="radio" name="hasCategories" value="false"> {{trans.__false}}
                                        </label>
                                        <div class="alert" v-if="StoreResponse.errors.hasCategories" v-for="error in StoreResponse.errors.hasCategories">{{ error }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-isCategoryRequired">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__isCategoryRequired}}</label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div id="isCategoryRequired" class="btn-group" data-toggle="buttons">
                                        <label class="btn btn-default yes" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.isCategoryRequired = true; form.hasCategories = true">
                                            <input type="radio" name="hasCategories" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                        </label>
                                        <label class="btn btn-primary active no" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.isCategoryRequired = false">
                                            <input type="radio" name="hasCategories" value="false"> {{trans.__false}}
                                        </label>
                                        <div class="alert" v-if="StoreResponse.errors.isCategoryRequired" v-for="error in StoreResponse.errors.isCategoryRequired">{{ error }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-hasTags">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__hasTags}}</label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div id="hasTags" class="btn-group" data-toggle="buttons">
                                        <label class="btn btn-default yes" :class="{ active: form.hasTags }" @click="form.hasTags = true">
                                            <input type="radio" name="hasTags" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                        </label>
                                        <label class="btn btn-primary no" :class="{ active: !form.hasTags }" @click="form.hasTags = false">
                                            <input type="radio" name="hasTags" value="false"> {{trans.__false}}
                                        </label>
                                        <div class="alert" v-if="StoreResponse.errors.hasTags" v-for="error in StoreResponse.errors.hasTags">{{ error }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-isTagRequired">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__isTagRequired}}</label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div id="isTagRequired" class="btn-group" data-toggle="buttons">
                                        <label class="btn btn-default yes" :class="{active: form.isTagRequired}" @click="form.isTagRequired = true; form.hasTags = true">
                                            <input type="radio" name="hasCategories" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                        </label>
                                        <label class="btn btn-primary active no" :class="{active: !form.isTagRequired}" @click="form.isTagRequired = false">
                                            <input type="radio" name="hasCategories" value="false"> {{trans.__false}}
                                        </label>
                                        <div class="alert" v-if="StoreResponse.errors.isTagRequired" v-for="error in StoreResponse.errors.isTagRequired">{{ error }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-isFeaturedImageRequired">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__isFeaturedImageRequired}}</label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div id="isFeaturedImageRequired" class="btn-group" data-toggle="buttons">
                                        <label class="btn btn-default yes" :class="{active: form.isFeaturedImageRequired}" @click="form.isFeaturedImageRequired = true">
                                            <input type="radio" name="isFeaturedImageRequired" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                        </label>
                                        <label class="btn btn-primary active no" :class="{active: !form.isFeaturedImageRequired}" @click="form.isFeaturedImageRequired = false">
                                            <input type="radio" name="isFeaturedImageRequired" value="false"> {{trans.__false}}
                                        </label>
                                        <div class="alert" v-if="StoreResponse.errors.isFeaturedImageRequired" v-for="error in StoreResponse.errors.isFeaturedImageRequired">{{ error }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" id="form-group-hasFeaturedVideo">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__hasFeaturedVideo}}</label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div id="hasFeaturedVideo" class="btn-group" data-toggle="buttons">
                                        <label class="btn btn-default yes" :class="{active: form.hasFeaturedVideo}" @click="form.hasFeaturedVideo = true">
                                            <input type="radio" name="hasFeaturedVideo" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                        </label>
                                        <label class="btn btn-primary active no" :class="{active: !form.hasFeaturedVideo}" @click="form.hasFeaturedVideo = false">
                                            <input type="radio" name="hasFeaturedVideo" value="false"> {{trans.__false}}
                                        </label>
                                        <div class="alert" v-if="StoreResponse.errors.hasFeaturedVideo" v-for="error in StoreResponse.errors.hasFeaturedVideo">{{ error }}</div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="col-lg-8 col-md-12 col-sm-12 col-xs-12">
                    <div class="x_panel">

                        <div class="x_title">
                            <h2>{{trans.__customFieldsTitle}}</h2>
                            <button @click.prevent="addField" class="btn btn-info newField">{{trans.__newFieldBtn}}</button>
                            <div class="clearfix"></div>
                        </div>

                        <div class="x_content">
                            <br />

                            <div class="startingInputs col-md-12 col-sm-12 startingInputsContainer" v-for="(field, index) in form.fields" :id="++index">

                                <div class="x_title customFieldTitleWrapper" @click="toggle(index)">
                                    <div class="col-md-12 col-sm-12 col-xs-12">
                                        <div class="col-md-10 col-sm-10"><h4>{{ field.name }}</h4></div>
                                        <div class="col-md-2 col-sm-2"><i @click="removeField($event, index)" class="removeField fa fa-2x fa-close"></i></div>
                                    </div>
                                </div>

                                <div class="form-group col-md-12 col-sm-12 col-xs-12 body">

                                    <div class="row">
                                        <div class="col-lg-6 col-md-8 col-sm-8 col-xs-12">
                                            <label class="control-label col-lg-3 col-md-3 col-sm-3 col-xs-12 text-left">{{trans.__fieldName}}:</label>
                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                <input type="text" name="name" class="form-control fieldName" v-model="field.name">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-10 text-left">{{trans.__slug}}:</label>
                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                <input type="text" name="slug" class="form-control fieldSlug" v-model="field.slug">
                                                <small id="emailHelp" class="form-text text-muted">{{ trans.__slugTip}}</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-6 col-md-8 col-sm-8 col-xs-12">
                                            <label class="control-label col-lg-3 col-md-3 col-sm-3 col-xs-12 text-left">{{trans.__placeholder}}: </label>
                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                <input type="text" name="placeholder" class="form-control placeholder" v-model="field.placeholder">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-6 col-md-8 col-sm-8 col-xs-12">
                                            <label class="control-label col-lg-3 col-md-3 col-sm-3 col-xs-12 text-left">{{trans.__inputType}}:</label>
                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                <multiselect
                                                        v-model="field.type"
                                                        deselect-label="Can't remove this value"
                                                        track-by="typeName"
                                                        label="typeName"
                                                        placeholder="Select one"
                                                        :options="options"
                                                        :searchable="false"
                                                        :allow-empty="false"
                                                        :id="options"></multiselect>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-6 col-md-8 col-sm-8 col-xs-12">
                                            <label class="control-label text-left-checkbox-lable col-md-3 col-sm-3 col-xs-12">{{trans.__translatable}} :</label>
                                            <input type="checkbox" class="checkboxStyled col-md-6 col-sm-6 col-xs-12" v-model="field.translatable">
                                        </div>
                                    </div>


                                    <div class="row">
                                        <div class="col-lg-6 col-md-8 col-sm-8 col-xs-12">
                                            <label class="control-label text-left-checkbox-lable col-md-3 col-sm-3 col-xs-12">{{trans.__inTable}}:</label>
                                            <input type="checkbox" class="checkboxStyled col-md-6 col-sm-6 col-xs-12" v-model="field.inTable">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-6 col-md-8 col-sm-8 col-xs-12">
                                            <label class="control-label text-left-checkbox-lable col-md-3 col-sm-3 col-xs-12">{{trans.__required}}:</label>
                                            <input type="checkbox" class="checkboxStyled col-md-6 col-sm-6 col-xs-12" v-model="field.required">
                                        </div>
                                    </div>

                                    <div class="row" v-if="field.type.inputType == 'radio' || field.type.inputType == 'checkbox' || field.type.inputType == 'dropdown'">
                                        <div class="col-md-6 col-sm-6 col-xs-12 multioptionValuesWrapper" :id="'option'+index">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12 text-left">{{trans.__options}}:</label>
                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                <textarea rows="3" name="multioptionValues" class="form-control multioptionValues" v-model="field.multioptionValues" placeholder="Example : value:Title, firstName:Name, lastName:Lastname"></textarea>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="row" v-if="field.type.inputType == 'db'">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12 text-left">{{trans.__table}}:</label>
                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                <multiselect
                                                        v-model="field.dbTable"
                                                        deselect-label="Can't remove this value"
                                                        track-by="label"
                                                        label="label"
                                                        placeholder="Select one"
                                                        :options="dbTables"
                                                        :searchable="false"
                                                        group-values="options"
                                                        group-label="group"
                                                        :allow-empty="false"></multiselect>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row" v-if="field.type.inputType == 'db' || field.type.inputType == 'image' || field.type.inputType == 'file'">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <label class="control-label text-left-checkbox-lable col-md-3 col-sm-3 col-xs-12">{{trans.__isMultiple}}:</label>
                                            <input type="checkbox" class="checkboxStyled col-md-6 col-sm-6 col-xs-12" v-model="field.isMultiple">
                                        </div>
                                    </div>

                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </form>

            <div class="mainButtonsContainer">
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

                    <button type="button" class="btn btn-info" id="globalCancel" @click="redirect('post-type-list')">{{trans.__globalCancelBtn}}</button>
                </div>
            </div>

        </div>
    </div>
</template>
<style src="./style.css" scoped></style>
<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        mounted() {
            // translations
            this.trans = {
                __title: this.__('postType.add'),
                __createFormTitle: this.__('postType.createFormTitle'),
                __name: this.__('postType.form.name'),
                __slug: this.__('base.slug'),
                __visible: this.__('base.visible'),
                __hasCategories: this.__('postType.form.hasCategories'),
                __isCategoryRequired: this.__('postType.form.isCategoryRequired'),
                __hasTags: this.__('postType.form.hasTags'),
                __isTagRequired: this.__('postType.form.isTagRequired'),
                __hasFeaturedVideo: this.__('postType.form.hasFeaturedVideo'),
                __isFeaturedImageRequired: this.__('postType.form.isFeaturedImageRequired'),
                __true: this.__('base.booleans.true'),
                __false: this.__('base.booleans.false'),
                __none: this.__('postType.form.belongsValues.none'),
                __pages: this.__('postType.form.belongsValues.pages'),
                __categories: this.__('postType.form.belongsValues.categories'),
                __customFieldsTitle: this.__('customFields.form.customFieldsTitle'),
                __type: this.__('postType.form.type'),
                __newFieldBtn: this.__('customFields.form.newFieldBtn'),
                __fieldName: this.__('customFields.form.fieldName'),
                __slugTip: this.__('customFields.form.slugTip'),
                __placeholder: this.__('customFields.form.placeholder'),
                __inTable: this.__('customFields.form.inTable'),
                __inputType: this.__('customFields.form.inputType'),
                __translatable: this.__('customFields.form.translatable'),
                __required: this.__('customFields.form.required'),
                __options: this.__('customFields.form.options'),
                __table: this.__('customFields.form.table'),
                __isMultiple: this.__('customFields.form.isMultiple'),
                __globalSaveBtn: this.__('base.saveBtn'),
                __globalSaveAndCloseBtn: this.__('base.saveAndCloseBtn'),
                __globalSaveAndNewBtn: this.__('base.saveAndNewBtn'),
                __globalCancelBtn: this.__('base.cancelBtn'),
            };

            // get table names for custom fields ( table names that can be used to make the values of a dropdown field )
            this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/post-type/get-tables')
                .then((resp) => {
                    this.dbTables = resp.body;
                });
        },
        data(){
            return{
                selected : [],
                dbTables: [],
                requestForTable: {},
                savedDropdownMenuVisible: false,
                form:{
                    name: '',
                    isVisible: true,
                    slug: '',
                    hasCategories: false,
                    isCategoryRequired: false,
                    hasTags: false,
                    isTagRequired: false,
                    isFeaturedImageRequired: false,
                    hasFeaturedVideo: false,
                    redirect: '',
                    fields: []
                },
                options: [
                    { inputType: 'text', typeName: 'Text'},
                    { inputType: 'email', typeName: 'Email'},
                    { inputType: 'textarea', typeName: 'Long text'},
                    { inputType: 'editor', typeName: 'Editor'},
                    { inputType: 'number', typeName: 'Number'},
                    { inputType: 'image', typeName: 'Image'},
                    { inputType: 'file', typeName: 'File'},
                    { inputType: 'video', typeName: 'Video'},
                    { inputType: 'date', typeName: 'Date'},
                    { inputType: 'boolean', typeName: 'Boolean (True of False)'},
                    { inputType: 'checkbox', typeName: 'Checkboxes'},
                    { inputType: 'radio', typeName: 'Radio (One choice options)'},
                    { inputType: 'dropdown', typeName: 'Dropdown'},
                    { inputType: 'db', typeName: 'Dropdown from DB'},
                ]
            }
        },
        methods: {
            // toggle custom field
            toggle(id){
                $(".startingInputsContainer#"+id+" .body").slideToggle(200);
            },
            // store request
            store(redirectChoice){
                this.$store.dispatch('openLoading');
                this.form.redirect = redirectChoice;
                this.$store.dispatch('store',{
                    data: this.form,
                    url: this.basePath+'/'+this.getAdminPrefix+"/json/post-type/store",
                    error: "Post type could not be created. Please try again later."
                }).then((resp) => {
                    if(resp.code == 200){
                        this.onStoreBtnClicked('post-type-',redirectChoice, resp.id);
                        // refresh page
                        this.$router.go({path: this.$route.path});
                    }
                });
            },
            // use to add new custom field (when new field btn is clicked)
            addField: function (){
                this.form.fields.push({
                    name: '',
                    slug: '',
                    placeholder: '',
                    multioptionValues: '',
                    translatable: false,
                    inTable: false,
                    required: false,
                    type: { inputType: 'text', typeName: 'Text'},
                    dbTable: '',
                    isMultiple: false,
                    categories: [{title: 'All', slug: 0}],
                });
            },
            removeField(event, index){
                var i = index-1;
                this.form.fields.splice(i, 1);
            },
            // call the createSlug function in vuejs
            createSlug(title, index){
                this.form.slug = "";
                $("#slug").attr("readonly",true);
                this.$store.dispatch('createSlug', {title: title, url: this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/post-type/check-slug/'})
                    .then((response) => {
                        this.form.slug = response;
                    });
            },
            removeReadonly(id){
                $("#"+id).attr("readonly",false);
            }
        },
    }
</script>
