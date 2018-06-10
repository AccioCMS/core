<template>
    <div class="componentsWs" dusk="postTypeUpdateComponent">
        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}}</h3>
            </div>
        </div>
        <!-- TITLE END -->

        <div class="clearfix"></div>

        <div class="row">
            <form class="form-horizontal form-label-left" id="store">

                <div class="col-md-6 col-xs-6">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>{{trans.__updateFormTitle}}</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <br />

                            <!-- Loading component -->
                            <spinner v-if="spinner" :width="'40px'" :height="'40px'" :border="'10px'"></spinner>

                            <form v-if="!spinner">
                                <div class="form-group" id="form-group-name">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{ trans.__name }}</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input type="text" class="form-control" id="name" v-model="form.name">
                                        <div class="alert" v-if="StoreResponse.errors.name" v-for="error in StoreResponse.errors.name">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group" id="form-group-slug">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__slug}}</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input type="text" class="form-control" id="slug" v-model="form.slug" disabled>
                                        <div class="alert" v-if="StoreResponse.errors.slug" v-for="error in StoreResponse.errors.slug">{{ error }}</div>
                                    </div>
                                </div>

                                <div class="form-group" id="form-group-visible">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__visible}}</label>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div id="isVisible" class="btn-group" data-toggle="buttons">
                                            <label class="btn btn-default yes" :class="{ active: form.isVisible }" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.isVisible = true">
                                                <input type="radio" name="isVisible" :value="true"> &nbsp; {{trans.__true}} &nbsp;
                                            </label>
                                            <label class="btn btn-primary no" :class="{ active: !form.isVisible }" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.isVisible = false">
                                                <input type="radio" name="isVisible" :value="false"> {{trans.__false}}
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
                                            <label class="btn btn-default" :class="{ active: form.isCategoryRequired }" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.isCategoryRequired = true; form.hasCategories = true">
                                                <input type="radio" name="hasCategories" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                            </label>
                                            <label class="btn btn-primary" :class="{ active: !form.isCategoryRequired }" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="form.isCategoryRequired = false">
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

                                <div class="form-group" id="form-group-hasFeaturedImage">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__hasFeaturedImage}}</label>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div id="hasFeaturedImage" class="btn-group" data-toggle="buttons">
                                            <label class="btn btn-default yes" :class="{active: form.hasFeaturedImage}" @click="form.hasFeaturedImage = true">
                                                <input type="radio" name="hasFeaturedImage" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                            </label>
                                            <label class="btn btn-primary active no" :class="{active: !form.hasFeaturedImage}" @click="form.hasFeaturedImage = false">
                                                <input type="radio" name="hasFeaturedImage" value="false"> {{trans.__false}}
                                            </label>
                                            <div class="alert" v-if="StoreResponse.errors.hasFeaturedImage" v-for="error in StoreResponse.errors.hasFeaturedImage">{{ error }}</div>
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

                                <div class="form-group" id="form-group-isFeaturedVideoRequired">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__isFeaturedVideoRequired}}</label>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div id="isFeaturedVideoRequired" class="btn-group" data-toggle="buttons">
                                            <label class="btn btn-default yes" :class="{active: form.isFeaturedVideoRequired}" @click="form.isFeaturedVideoRequired = true">
                                                <input type="radio" name="isFeaturedVideoRequired" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                            </label>
                                            <label class="btn btn-primary active no" :class="{active: !form.isFeaturedVideoRequired}" @click="form.isFeaturedVideoRequired = false">
                                                <input type="radio" name="isFeaturedVideoRequired" value="false"> {{trans.__false}}
                                            </label>
                                            <div class="alert" v-if="StoreResponse.errors.isFeaturedVideoRequired" v-for="error in StoreResponse.errors.isFeaturedVideoRequired">{{ error }}</div>
                                        </div>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>

                <div class="col-md-12 col-xs-12">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>{{trans.__fieldsTitle}}</h2>
                            <button @click.prevent="addField" class="btn btn-info newField" v-if="!spinner">{{trans.__newFieldBtn}}</button>
                            <div class="clearfix"></div>
                        </div>

                        <div class="x_content">
                            <br />
                            <!-- Loading component -->
                            <spinner v-if="spinner" :width="'40px'" :height="'40px'" :border="'10px'"></spinner>

                            <div class="startingInputs col-md-12 col-sm-12 startingInputsContainer" v-for="(field, index) in form.fields" :id="++index" v-if="!spinner">

                                <div class="x_title customFieldTitleWrapper" @click="toggle(index)">
                                    <div class="col-md-12 col-sm-12 col-xs-12">
                                        <div class="col-md-10 col-sm-10"><h4>{{ field.name }}</h4></div>
                                        <div class="col-md-2 col-sm-2"><i @click="removeField(index, false, field.canBeRemoved)" class="removeField fa fa-2x fa-close"></i></div>
                                    </div>
                                </div>

                                <div :class="{'form-group col-md-12 col-sm-12 col-xs-12 body': true, 'hiddenField': !field.canBeRemoved}">

                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-10 text-left">{{trans.__fieldName}}:</label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <input type="text" name="name" class="form-control fieldName" v-model="field.name">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-10 text-left">{{trans.__slug}}:</label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <input type="text" name="name" class="form-control fieldName" :value="field.slug" disabled>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12 text-left">{{trans.__placeholder}}:</label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <input type="text" name="placeholder" class="form-control placeholder" v-model="field.placeholder">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12 text-left">{{trans.__inputType}}:</label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <input type="text" name="name" class="form-control" :value="field.type.typeName" disabled v-if="!field.canBeRemoved">
                                                <multiselect
                                                        v-if="field.canBeRemoved"
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
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <label class="control-label text-left-checkbox-lable col-md-3 col-sm-3 col-xs-12">{{trans.__translatable}}:</label>
                                            <input type="checkbox" class="checkboxStyled col-md-6 col-sm-6 col-xs-12" v-model="field.translatable" :disabled="!field.canBeRemoved">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <label class="control-label text-left-checkbox-lable col-md-3 col-sm-3 col-xs-12">{{trans.__inTable}}:</label>
                                            <input type="checkbox" class="checkboxStyled col-md-6 col-sm-6 col-xs-12" v-model="field.inTable">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <label class="control-label text-left-checkbox-lable col-md-3 col-sm-3 col-xs-12">{{trans.__required}}:</label>
                                            <input type="checkbox" class="checkboxStyled col-md-6 col-sm-6 col-xs-12" v-model="field.required">
                                        </div>
                                    </div>

                                    <div class="row" v-if="field.type.inputType == 'radio' || field.type.inputType == 'checkbox' || field.type.inputType == 'dropdown'">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <div  :id="'option'+index" :class="{ multioptionValuesWrapper : true ,
                                                                        checkbox : field.type.inputType == 'checkbox',
                                                                        dropdown : field.type.inputType == 'dropdown',
                                                                        radio : field.type.inputType    == 'radio'}">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12 text-left">{{trans.__options}}:</label>
                                                <div class="col-md-6 col-sm-6 col-xs-12">
                                                    <textarea rows="3" name="multioptionValues" class="form-control multioptionValues" v-model="field.multioptionValues" placeholder="Example : value:Title, name:Name, lastname:Lastname"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row" v-if="field.type.inputType == 'db'">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12 text-left">{{trans.__table}}:</label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
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

                                    <div class="row" v-if="field.type.inputType == 'db'">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12 text-left">{{trans.__categories}}:</label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <multiselect
                                                        v-model="field.categories"
                                                        deselect-label="Can't remove this value"
                                                        track-by="title"
                                                        label="title"
                                                        placeholder="Select one"
                                                        :options="categories"
                                                        :searchable="false"
                                                        :multiple="true"
                                                        :allow-empty="true"></multiselect>
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

            <div class="mainButtonsContainer" v-if="!spinner">
                <div class="row">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" id="globalSaveBtn" @click="store('save')">{{trans.__globalUpdateBtn}}</button>
                        <button type="button" class="btn btn-primary dropdown-toggle" @click="savedDropdownMenuVisible = !savedDropdownMenuVisible">
                            <i class="fa fa-caret-up"></i>
                        </button>
                        <ul class="savedDropdownMenu" v-if="savedDropdownMenuVisible">
                            <li><a style="cursor:pointer" @click="store('close')">{{trans.__globalUpdateAndCloseBtn}}</a></li>
                            <li><a style="cursor:pointer" @click="store('new')">{{trans.__globalUpdateAndNewBtn}}</a></li>
                        </ul>
                    </div>

                    <button type="button" class="btn btn-info" id="globalCancel" @click="redirect('post-type-list')">{{trans.__globalCancelBtn}}</button>
                </div>
            </div>

        </div>


        <!-- MODAL -->
        <div class="modal" tabindex="-1" role="dialog" aria-hidden="true" style="display: block" v-if="fieldToBeRemoved.index != -1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">

                    <div class="modal-header">
                        <button type="button" class="close" @click="fieldToBeRemoved.index = -1" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                        <h4 class="modal-title" id="myModalLabel2">{{trans.__confirmBtn}}</h4>
                    </div>
                    <div class="modal-body">
                        <h4>{{trans.__sureToDeleteField}}</h4>
                        <p>{{trans.__deleteFieldWarning}}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal" @click="fieldToBeRemoved.index = -1">{{trans.__closeBtn}}</button>
                        <button type="button" class="btn btn-primary" @click="removeField(fieldToBeRemoved.index, true, fieldToBeRemoved.isNew)">{{trans.__confirmBtn}}</button>
                    </div>

                </div>
            </div>
        </div>
        <!-- MODAL -->
    </div>
</template>
<style src="./style.css" scoped></style>
<script>
    import RelatedButtons from '../menu/RelatedButtons.vue'
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        components:{
            'related-buttons':RelatedButtons
        },
        mounted() {
            this.$store.commit('setSpinner', true);
            // get post type data
            this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/post-type/details/'+this.$route.params.id)
                .then((resp) => {
                    this.form.id = resp.body.details.postTypeID;
                    this.form.name = resp.body.details.name;
                    this.form.isVisible = resp.body.details.isVisible;
                    this.form.hasCategories = resp.body.details.hasCategories;
                    this.form.isCategoryRequired = resp.body.details.isCategoryRequired;
                    this.form.hasTags = resp.body.details.hasTags;
                    this.form.isTagRequired = resp.body.details.isTagRequired;
                    this.form.hasFeaturedImage = resp.body.details.hasFeaturedImage;
                    this.form.isFeaturedImageRequired = resp.body.details.isFeaturedImageRequired;
                    this.form.hasFeaturedVideo = resp.body.details.hasFeaturedVideo;
                    this.form.isFeaturedVideoRequired = resp.body.details.isFeaturedVideoRequired;
                    this.form.slug = resp.body.details.slug;
                    this.form.fields = resp.body.details.fields;
                    this.dbTables = resp.body.dbTables;

                    const categories = resp.body.categories;
                    this.categories.push({title: 'All', slug: 0});
                    for(let k in categories){
                        this.categories.push(categories[k]);
                    }
                    this.$store.commit('setSpinner', false);
                });

            // translations
            this.trans = {
                __title: this.__('postType.updateTitle'),
                __updateFormTitle: this.__('postType.updateFormTitle'),
                __sureToDeleteField: this.__('postType.sureToDeleteField'),
                __deleteFieldWarning: this.__('postType.deleteFieldWarning'),
                __name: this.__('base.name'),
                __slug: this.__('base.slug'),
                __visible: this.__('base.visible'),
                __closeBtn: this.__('base.closeBtn'),
                __hasCategories: this.__('postType.form.hasCategories'),
                __isCategoryRequired: this.__('postType.form.isCategoryRequired'),
                __hasTags: this.__('postType.form.hasTags'),
                __isTagRequired: this.__('postType.form.isTagRequired'),
                __hasFeaturedVideo: this.__('postType.form.hasFeaturedVideo'),
                __isFeaturedVideoRequired: this.__('postType.form.isFeaturedVideoRequired'),
                __hasFeaturedImage: this.__('postType.form.hasFeaturedImage'),
                __isFeaturedImageRequired: this.__('postType.form.isFeaturedImageRequired'),
                __true: this.__('base.booleans.true'),
                __false: this.__('base.booleans.false'),
                __none: this.__('postType.form.belongsValues.none'),
                __pages: this.__('postType.form.belongsValues.pages'),
                __confirmBtn: this.__('base.confirmBtn'),
                __fieldsTitle: this.__('postType.fieldsTitle'),
                __type: this.__('postType.form.type'),
                __newFieldBtn: this.__('customFields.form.newFieldBtn'),
                __fieldName: this.__('customFields.form.fieldName'),
                __placeholder: this.__('customFields.form.placeholder'),
                __inTable: this.__('customFields.form.inTable'),
                __inputType: this.__('customFields.form.inputType'),
                __translatable: this.__('customFields.form.translatable'),
                __required: this.__('customFields.form.required'),
                __options: this.__('customFields.form.options'),
                __table: this.__('customFields.form.table'),
                __isMultiple: this.__('customFields.form.isMultiple'),
                __categories: this.__('customFields.form.categories'),
                __globalUpdateBtn: this.__('base.updateBtn'),
                __globalUpdateAndCloseBtn: this.__('base.updateAndCloseBtn'),
                __globalUpdateAndNewBtn: this.__('base.updateAndNewBtn'),
                __globalCancelBtn: this.__('base.cancelBtn'),
            };
        },
        data(){
            return{
                dbTables: [],
                categories: [],
                savedDropdownMenuVisible: false,
                fieldToBeRemoved: {
                    index: -1,
                    isNew: false
                },
                form:{
                    name: '',
                    isVisible: true,
                    hasCategories: false,
                    isCategoryRequired: false,
                    hasTags: false,
                    isTagRequired: false,
                    hasFeaturedImage: true,
                    isFeaturedImageRequired: false,
                    hasFeaturedVideo: false,
                    isFeaturedVideoRequired: false,
                    slug: '',
                    dbTable: '',
                    isMultiple: false,
                    redirect: '',
                    deletedFieldsSlugs: [],
                    fields: [
                        {
                            name: '',
                            placeholder: '',
                            multioptionValues: '',
                            translatable: '',
                            inTable: '',
                            required: false,
                            categories: [],
                            type: {
                                inputType: "text",
                                typeName: "Text"
                            }
                        }
                    ],
                },
                options: [
                    { inputType: 'text', typeName: 'Text' },
                    { inputType: 'email', typeName: 'Email' },
                    { inputType: 'textarea', typeName: 'Long text' },
                    { inputType: 'editor', typeName: 'Editor' },
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
            // toggle custom field
            toggle(id){
                $(".startingInputsContainer#"+id+" .body").slideToggle(200);
            },

            /**
             * Use to make store request
             * @param redirectChoice
             */
            store(redirectChoice){ // method responsible for sending the update request
                this.$store.dispatch('openLoading');

                this.form.redirect = redirectChoice;
                this.$http.post(this.basePath+'/'+this.getAdminPrefix+"/json/post-type/storeUpdate", this.form)
                    .then((resp) => {
                        console.log("this.form",resp.body);

                        this.$store.dispatch('closeLoading');
                        this.$store.commit('setStoreResponse', resp.body);
                        if(resp.code == 200 && redirectChoice == 'save'){
                            var response = resp.body;
                            this.$store.dispatch('handleErrors', {response});
                            if(resp.body.code == 200){
                                // to remove the close button for the fields if they are added into database
                                for(var i=0; i<this.form.fields.length; i++){
                                    if(this.form.fields[i].canBeRemoved !== undefined){
                                        this.form.fields[i].canBeRemoved = false;
                                    }
                                 }

                                 // refresh page
                                this.$router.go({path: this.$route.path});
                            }
                        }else{
                            if(resp.body.code == 200){
                                this.onStoreBtnClicked('post-type-',redirectChoice);
                                // refresh page
                                this.$router.go({path: this.$route.path});
                            }
                        }
                    });
            },
            // use to add new custom field (when new field btn is clicked)
            addField: function () {
                this.form.fields.push({
                    name: '',
                    placeholder: '',
                    multioptionValues: '',
                    translatable: false,
                    inTable: false,
                    required: false,
                    type: { inputType: 'text', typeName: 'Text'},
                    dbTable: '',
                    isMultiple: false,
                    categories: [],
                    canBeRemoved: true
                });
            },

            /**
             * Remove field
             * @param index
             * @param confirmed
             * @param isNew
             */
            removeField(index, confirmed, isNew){
                if(this.fieldToBeRemoved.index == -1){
                    this.fieldToBeRemoved.index = index;
                    this.fieldToBeRemoved.isNew = isNew;
                }else{
                    this.$store.dispatch('openLoading');
                    let i = index-1;
                    if(!this.fieldToBeRemoved.isNew){
                        this.form.deletedFieldsSlugs.push(this.form.fields[i].slug);
                    }
                    this.form.fields.splice(i, 1);
                    this.fieldToBeRemoved.index = -1;
                    this.$store.dispatch('closeLoading');
                }
            }
        },
    }
</script>
