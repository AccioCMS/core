<template>
    <div class="ruleGroup" :id="'ruleGroup-'+groupIndex+'-'+index"><!-- ruleGroup -->

        <div class="row">
            <div style="margin-top:8px; text-align:center;" v-if="index">
                <h5 style="text-transform: uppercase; font-weight: bold;">{{trans.__and}}</h5>
            </div>
        </div>

        <div class="clearfix"></div>
        <div class="row">
            <div class="col-md-11 col-sm-11 col-xs-11">
                <div class="form-group" id="form-group-field">
                    <div class="col-md-4 col-sm-4 col-xs-12">

                        <select class="form-control inputFields" v-model="rule.field" v-if="fields.length">
                            <option
                                    v-for="(option, index) in fields"
                                    v-if="shouldFieldBeShown(option)"
                                    :value="{index: index, slug: option.slug}"
                                    :data-type="index">
                                {{ option.label[defaultLanguageSlug] }}
                            </option>
                        </select>
                        <div class="alert"></div>
                    </div>

                    <div class="col-md-4 col-sm-4 col-xs-12" id="form-group-operator">
                        <multiselect
                                v-model="rule.operator"
                                track-by="value"
                                :options="[{label:'Equals' , value: 'equals'}, {label:'Not equals' , value: 'not-equals'}]"
                                label="label"
                                select-label=""
                                deselect-label=""
                                :allow-empty="false"></multiselect>
                    </div>

                    <template v-if="rule.field != ''">
                        <div class="col-md-4 col-sm-4 col-xs-12" v-if="fields[rule.field.index].type.inputType == 'text' || fields[rule.field.index].type.inputType == 'number' || fields[rule.field.index].type.inputType == 'radio'">
                            <input type="text" v-model="rule.value" class="form-control inputFields">
                        </div>

                        <div class="col-md-4 col-sm-4 col-xs-12" v-if="fields[rule.field.index].type.inputType == 'dropdown' || fields[rule.field.index].type.inputType == 'checkbox'">
                            <select class="form-control inputFields" v-model="rule.value">
                                <option v-for="(opt, i) in multioptionsStringToArray" :value="opt.value">
                                    {{opt.label}}
                                </option>
                            </select>
                        </div>


                        <div class="col-md-4 col-sm-4 col-xs-12" v-if="fields[rule.field.index].type.inputType == 'db'">
                            <!-- if values with post search -->
                            <multiselect
                                    v-model="rule.value"
                                    id="ajax"
                                    label="name"
                                    track-by="value"
                                    :value="value"
                                    placeholder="Type to search"
                                    open-direction="bottom"
                                    :options="valueOptions"
                                    :multiple="false"
                                    :searchable="true"
                                    :loading="isLoading"
                                    :internal-search="false"
                                    :close-on-select="true"
                                    :options-limit="300"
                                    :limit="3"
                                    :limit-text="limitText"
                                    :max-height="600"
                                    :show-no-results="false"
                                    select-label=""
                                    deselect-label=""
                                    @input="updateValueAfterSelect"
                                    @search-change="searchPosts($event, index)">
                                <template slot="clear" slot-scope="props">
                                    <div class="multiselect__clear"
                                         v-if="rule.value != null && rule.value.length"></div>
                                </template>
                                <span slot="noResult">Oops! No elements found. Consider changing the search query.</span>
                            </multiselect>

                        </div>

                    </template>

                </div>

            </div>

            <div class="col-md-1 col-sm-1 col-xs-1">
                <i class="fa fa-minus-circle fa-2x" @click="removeRule(index)"></i>
            </div>

        </div>
    </div><!-- ruleGroup -->
</template>
<style scoped>
    .inputFields{
        height: 40px;
        border: 1px solid #eaeaea;
        outline: none;
        box-shadow: none;
        border-radius: 7px;
    }
    i{
        margin-top: 5px;
        cursor: pointer;
    }
</style>
<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    export default{
        mixins: [globalComputed, globalMethods],
        props:['rule','index','groupIndex','fields','fieldID','defaultLanguageSlug'],
        data(){
            return{
                fieldsOptions:[],
                trans:{},
                fieldIndex: 0,
                isLoading: true,
                valueOptions: [],
            }
        },
        methods: {
            removeRule(index){
                this.$emit('removeRule', {groupIndex: this.groupIndex, index: index});
            },
            updateValueAfterSelect(value){
                this.rule.value = value.value;
            },
            shouldFieldBeShown(field){
                if(field.id == this.fieldID){
                    return false;
                }

                if(field.type.inputType != 'text'
                    && field.type.inputType != 'number'
                    && field.type.inputType != 'checkbox'
                    && field.type.inputType != 'radio'
                    && field.type.inputType != 'dropdown'
                    && field.type.inputType != 'db'){
                    console.log(field.type);
                    return false;
                }
                return true;
            },

            // search for post by post type
            searchPosts(query, index){
                this.valueOptions = [];
                if(query){
                    var postTypeSlug = this.fields[this.rule.field.index].dbTable.name;
                    if(postTypeSlug !== undefined && postTypeSlug != ""){
                        this.isLoading = true;
                        this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/search/'+postTypeSlug+'/'+query)
                            .then((resp) => {
                                var result = [];
                                var list = resp.body.list;
                                for(let k in list){
                                    result.push({
                                        name: list[k].title,
                                        value: list[k].ID
                                    });
                                }
                                this.valueOptions = result;
                                this.isLoading = false;
                        }, error => {
                            console.log(error);
                        });
                    }
                }
            },

        },
        computed:{
            // makes multioptions string to array
            multioptionsStringToArray(){
                var string = this.fields[this.rule.field.index].multioptionValues;
                var options = string.split(',');
                var result = [];
                if(Array.isArray(options)){
                    for(let k in options){
                        var splitedOptions = options[k].split(':');
                        if(Array.isArray(splitedOptions) && splitedOptions !== undefined && splitedOptions[0] !== undefined && splitedOptions[1] !== undefined){
                            result.push({value: splitedOptions[0].trim(), label: splitedOptions[1].trim()});
                        }
                    }
                }
                return result;
            },
        }
    }
</script>
