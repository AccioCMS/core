<template>
    <div class="startingInputs col-md-12 col-sm-12 startingInputsContainer" :id="index" :class="{'removePaddingAndMargin': isSubField}">

        <div class="x_title customFieldTitleWrapper" @click="toggle(index)" :class="{'removePaddingAndMargin': isSubField}">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="col-md-10 col-sm-10"><h4>{{ field.label[languages[0].slug] }}</h4></div>
                <div class="col-md-2 col-sm-2" v-if="!isSubField"><i @click="removeField($event, index)" class="removeField fa fa-2x fa-close"></i></div>
            </div>
        </div>

        <div class="clearfix" style="margin-top:10px;" v-if="isSubField"></div>

        <div class="form-group col-md-12 col-sm-12 col-xs-12 body" style="display:none;">

            <div class="languageTabs">
                <ul>
                    <li v-for="(lang, key, index) in languages" class="btn btn-default" :class="{active: languageState == lang.slug}" @click="languageState = lang.slug">{{ lang.name }}</li>
                </ul>
            </div>

            <div class="row" v-for="(lang, key, index) in languages" v-show="languageState == lang.slug">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__fieldName}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="text" name="label" class="form-control inputFields fieldName" v-model="field.label[lang.slug]" @change="createSlug(lang.slug)">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__slug}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="text" name="name" class="form-control inputFields fieldSlug" v-model="field.slug" @dblclick="readonly = false" :readonly="readonly">
                        <img :src="basePath+'/public/images/loading.svg'" class="slugLoading" v-if="displaySlugLoading">
                        <small>{{trans.__frontEndSlugExp}} {{ groupSlug }}__{{ field.slug }}</small>
                    </div>
                </div>
            </div>

            <div class="row" v-for="(lang, key, index) in languages" v-show="languageState == lang.slug">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__placeholder}}: </label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="text" name="placeholder" class="form-control inputFields placeholder" v-model="field.placeholder[lang.slug]">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12" v-for="(lang, key, index) in languages" v-show="languageState == lang.slug">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__fieldNote}}: </label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <textarea rows="5" class="form-control textareaFields" v-model="field.note[lang.slug]"></textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__defaultValue}}: </label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="text" name="defaultValue" class="form-control inputFields defaultValue" v-model="field.defaultValue">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__layout}}: </label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <multiselect
                                v-model="field.layout"
                                deselect-label=""
                                select-label=""
                                placeholder="Select one"
                                :options="layoutOptions"
                                :searchable="false"
                                :allow-empty="false"
                                :id="options"></multiselect>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label text-left-checkbox-lable col-md-2 col-sm-2 col-xs-12">{{trans.__isActive}} :</label>
                    <input type="checkbox" class="checkboxStyled col-md-8 col-sm-8 col-xs-12" v-model="field.isActive">
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label text-left-checkbox-lable col-md-2 col-sm-2 col-xs-12">{{trans.__translatable}} :</label>
                    <input type="checkbox" class="checkboxStyled col-md-8 col-sm-8 col-xs-12" v-model="field.isTranslatable">
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label text-left-checkbox-lable col-md-2 col-sm-2 col-xs-12">{{trans.__required}}:</label>
                    <input type="checkbox" class="checkboxStyled col-md-8 col-sm-8 col-xs-12" v-model="field.isRequired">
                </div>
            </div>

            <div class="row" v-if="field.parent == 0" style="margin-top: 40px;">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="col-md-12 col-sm-12 col-xs-12">{{ trans.__siblingRulesTitle }}:</label>

                    <div class="siblingRules col-sm-12 col-xs-12" v-for="(ruleGroup, groupIndex) in field.rules">
                        <div class="background">
                            <field-rule
                                    v-for="(rule, index) in ruleGroup"
                                    :rule="rule"
                                    :index="index"
                                    :key="index"
                                    :groupIndex="groupIndex"
                                    :fields="allFields"
                                    :fieldID="field.id"
                                    :defaultLanguageSlug="defaultLanguageSlug"
                                    v-on:removeRule="removeRule"></field-rule>

                            <div class="addRule" style="text-align:center;">
                                <i style="cursor:pointer;" @click="addRule(groupIndex)" class="fa fa-plus-circle fa-2x"></i>
                            </div>

                        </div>

                        <div style="text-align:center; margin-top: 20px;">
                            <h5 style="text-transform: uppercase; font-weight: bold;">{{trans.__or}}</h5>
                        </div>

                    </div>

                    <div style="text-align:center; margin-top: 20px;">
                        <button class="btn btn-default" @click.prevent="addGroup">
                            Add a new group rules
                        </button>
                    </div>

                </div>
            </div>

            <div class="clearfix"></div>
            <div class="x_title" style="margin-top:50px;">
                <h2>{{trans.__properties}}</h2>
                <div class="clearfix"></div>
            </div>

            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__inputType}}:</label>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <multiselect
                                v-model="field.type"
                                deselect-label=""
                                select-label=""
                                track-by="typeName"
                                label="typeName"
                                placeholder="Select one"
                                :options="typeOptions"
                                :searchable="false"
                                :allow-empty="false"
                                :id="options"
                                @select="fieldTypeChanged"></multiselect>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12" v-if="field.type.inputType == 'repeater'">
                        <button class="btn btn-default" @click.prevent="addSubField">Add new subfield</button>
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'radio' || field.type.inputType == 'checkbox' || field.type.inputType == 'dropdown'">
                <div class="col-md-12 col-sm-12 col-xs-12 multioptionValuesWrapper">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__options}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <textarea rows="3" name="multioptionValues" class="form-control textareaFields multioptionValues" v-model="field.multioptionValues" placeholder="Example : value:Title, name:Name, lastname:Lastname"></textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12 wrapperStyle">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left" style="padding-top:33px;">{{trans.__wrapperStyle}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12" style="margin:0;padding:0;">
                        <div class="col-md-4 col-sm-4">
                            <label class="col-md-12 col-sm-12 col-xs-12">{{trans.__width}}</label>
                            <input type="text" name="wrapperStyleWidth" class="form-control inputFields" v-model="field.wrapperStyle.width">
                        </div>

                        <div class="col-md-4 col-sm-4">
                            <label class="col-md-12 col-sm-12 col-xs-12">{{trans.__class}}</label>
                            <input type="text" name="wrapperStyleWidth" class="form-control inputFields" v-model="field.wrapperStyle.class">
                        </div>

                        <div class="col-md-4 col-sm-4">
                            <label class="col-md-12 col-sm-12 col-xs-12">{{trans.__id}}</label>
                            <input type="text" name="wrapperStyleWidth" class="form-control inputFields" v-model="field.wrapperStyle.id">
                        </div>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12 fieldStyle">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left" style="padding-top:33px;">{{trans.__fieldStyle}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12" style="margin:0;padding:0;">
                        <div class="col-md-4 col-sm-4">
                            <label class="col-md-12 col-sm-12 col-xs-12">{{trans.__width}}</label>
                            <input type="text" name="wrapperStyleWidth" class="form-control inputFields" v-model="field.fieldStyle.width">
                        </div>

                        <div class="col-md-4 col-sm-4">
                            <label class="col-md-12 col-sm-12 col-xs-12">{{trans.__class}}</label>
                            <input type="text" name="wrapperStyleWidth" class="form-control inputFields" v-model="field.fieldStyle.class">
                        </div>

                        <div class="col-md-4 col-sm-4">
                            <label class="col-md-12 col-sm-12 col-xs-12">{{trans.__id}}</label>
                            <input type="text" name="wrapperStyleWidth" class="form-control inputFields" v-model="field.fieldStyle.id">
                        </div>

                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'db'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__table}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <multiselect
                                v-model="field.properties.dbTable"
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

            <div class="row" v-if="field.type.inputType == 'db' || field.type.inputType == 'image' || field.type.inputType == 'dropdown'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__isMultiple}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="checkbox" class="checkboxStyled" v-model="field.isMultiple">
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'textarea' || field.type.inputType == 'text'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__characterLimit}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="number" class="form-control inputFields" v-model="field.properties.characterLimit">
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'textarea'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__rows}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="number" class="form-control inputFields" v-model="field.properties.rows">
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'textarea'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__allowHTML}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="checkbox" class="checkboxStyled" v-model="field.properties.allowHTML">
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'textarea'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__allowParagraphs}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="checkbox" class="checkboxStyled" v-model="field.properties.allowParagraphs">
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'editor'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__toolbar}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <select class="form-control inputFields" v-model="field.properties.toolbar">
                            <option value="basic">Basic</option>
                            <option value="compact">Compact</option>
                            <option value="full">Full</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'file' || field.type.inputType == 'image'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__maxUploadSize}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="number" class="form-control inputFields" v-model="field.properties.maxUploadSize">
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'image'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__minWidth}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="number" class="form-control inputFields" v-model="field.properties.minWidth">
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'image'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__minHeight}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="number" class="form-control inputFields" v-model="field.properties.minHeight">
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'image'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__maxWidth}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="number" class="form-control inputFields" v-model="field.properties.maxWidth">
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'image'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__maxHeight}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="number" class="form-control inputFields" v-model="field.properties.maxHeight">
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'number' || field.type.inputType == 'range'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__min}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="number" class="form-control inputFields" v-model="field.properties.min">
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'number' || field.type.inputType == 'range'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__max}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="number" class="form-control inputFields" v-model="field.properties.max">
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'range'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__rangeLabel}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="number" class="form-control inputFields" v-model="field.properties.rangeLabel">
                    </div>
                </div>
            </div>

            <div class="row" v-if="field.type.inputType == 'dropdown'">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="control-label col-md-2 col-sm-2 col-xs-12 text-left">{{trans.__allowOther}}:</label>
                    <div class="col-md-8 col-sm-8 col-xs-12">
                        <input type="checkbox" class="checkboxStyled" v-model="field.properties.allowOther">
                    </div>
                </div>
            </div>

            <div class="row subfields" v-if="field.type.inputType == 'repeater'">
                <div class="col-md-10 col-sm-10 col-xs-10 col-md-offset-2 col-sm-offset-2">
                    <div v-for="(childField, subIndex) in allFields">
                        <div class="col-md-11 col-sm-11 col-xs-11 removePaddingAndMargin">
                            <field
                                    v-if="childField.parent != 0 && childField.parent == field.id"
                                    :field="childField"
                                    :isSubField="true"
                                    :index="subIndex"
                                    :trans="trans"
                                    :options="options"
                                    :languages="languages"
                                    :groupSlug="groupSlug"
                                    :dbTables="dbTables">
                            </field>
                        </div>
                        <div class="col-md-1 col-sm-1 col-xs-1 removeSubFieldWrapper removePaddingAndMargin" v-if="childField.parent != 0 && childField.parent == field.id">
                            <i @click="removeField($event, subIndex)" class="removeField fa fa-2x fa-close"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</template>
<style src="./style.css" scoped></style>
<script>
    import FieldRule from './FieldRule.vue'
    export default{
        props: ['field','index','trans','languages','dbTables','allFields','options','isInUpdate','isSubField', 'groupSlug'],
        components:{
            'field-rule':FieldRule,
        },
        mounted() {
            if(Object.keys(this.languages).length){
                this.languageState = this.languages[0].slug;
                this.defaultLanguageSlug = this.languages[0].slug;
            }
            for(let k in this.options){
                this.typeOptions.push(this.options[k]);
            }
            if(this.field.parent == 0){
                this.typeOptions.push({ inputType: 'repeater', typeName: 'Repeater' });
            }
        },
        data(){
            return{
                typeOptions: [],
                languageState: '',
                isSlugCreated: false,
                displaySlugLoading: false,
                readonly: true,
                layoutOptions: [
                    'row',
                    'table',
                    'block',
                ],
            }
        },
        methods:{
            // toggle custom field
            toggle(id){
                $(".startingInputsContainer#"+id+" .body").slideToggle(200);
            },
            addSubField(){
                this.$emit('addSubField', this.field.id);
            },
            // make request to the parent component to remove the field
            removeField(event, index){
                this.$emit("removeField", index);
            },
            createSlug(inLang){
                let id = this.field.id;

                if(!this.isSlugCreated && id.indexOf('NEW') > -1){
                    var defaultLangSlug = this.languages[0].slug;
                    // only in the default language
                    if(inLang == defaultLangSlug){
                        this.displaySlugLoading = true;
                        var title = this.field.label[defaultLangSlug];

                        // ket all keys form fields array
                        var keys = [];
                        for(let k in this.allFields){
                            if(this.allFields[k].slug != '' && this.allFields[k].id != this.field.id){
                                keys.push({id: this.allFields[k].id, slug: this.allFields[k].slug});
                            }
                        }
                        // get generated key
                        this.$http.post(this.basePath+'/'+this.$route.params.adminPrefix+'/json/custom-fields/generate-field-slug-request', {id: this.field.id, title: title, keys: keys})
                            .then((resp) => {
                                if(resp.status == 200){
                                    this.field.slug = resp.body;
                                    this.isSlugCreated = true;
                                    this.displaySlugLoading = false;
                                }
                            }, error => {
                                console.log(error);
                                this.displaySlugLoading = false;
                            });
                    }
                }
            },
            // add new rule group
            addGroup(){
                this.field.rules.push([{ field: '', operator: {label:'Equals' , value: 'equals'}, value: '' }]);
            },
            // add new rule to the list
            addRule(index){
                this.field.rules[index].push({ field: '', operator: {label:'Equals' , value: 'equals'}, value: '' });
            },
            // request a field remove rule to parent
            removeRule(request){
                this.$emit('removeFieldRule', {fieldIndex: this.index, groupIndex: request.groupIndex, ruleIndex: request.index});
            },
            // remove field request in parent create component
            fieldTypeChanged(actionName){
                this.$emit('refreshFieldRulesValues', this.index);
            }
        },
        computed:{
            basePath(){
                return this.$store.getters.get_base_path;
            }
        },
    }
</script>
