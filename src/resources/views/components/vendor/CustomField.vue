<template>
    <div class="form-group" :id="'form-group-'+vModel+'_'+ lang.slug">

        <label class="control-label col-md-2 col-sm-2 col-xs-12" v-if="!isSubField">{{field.label[lang.slug]}}</label>

        <!-- Text -->
        <template v-if="field.type == 'text'">
            <!-- If Custom Field (parent) -->
            <div class="col-md-10 col-sm-10 col-xs-12" v-if="!isSubField">
                <!-- If translatable -->
                <input type="text" class="form-control" :id="vModel" v-model="customFieldValues[vModel]" :placeholder="field.placeholder[lang.slug]" v-if="!field.isTranslatable">
                <!-- If not translatable -->
                <input type="text" class="form-control" :id="vModel" v-model="customFieldValues[vModel][lang.slug]" :placeholder="field.placeholder[lang.slug]" v-else>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>

            <!-- If Custom Sub Field (child) -->
            <div v-if="isSubField">
                <!-- If translatable -->
                <input type="text" class="form-control" :id="childKey" v-model="customFieldValues[vModel][lang.slug][subFieldGroupIndex][childKey]" :placeholder="field.placeholder[lang.slug]" v-if="isParentTranslatable">
                <!-- If not translatable -->
                <input type="text" class="form-control" :id="childKey" v-model="customFieldValues[vModel][subFieldGroupIndex][childKey]" :placeholder="field.placeholder[lang.slug]" v-else>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>

        </template>

        <!-- Textarea -->
        <template v-if="field.type == 'textarea'">
            <!-- If Custom Field (parent) -->
            <div class="col-md-10 col-sm-10 col-xs-12" v-if="!isSubField">
                <!-- If translatable -->
                <textarea class="form-control" :id="vModel" v-model="customFieldValues[vModel]" :placeholder="field.placeholder[lang.slug]" v-if="!field.isTranslatable"></textarea>
                <!-- If not translatable -->
                <textarea class="form-control" v-model="customFieldValues[vModel][lang.slug]" :id="vModel" :placeholder="field.placeholder[lang.slug]" v-if="field.isTranslatable"></textarea>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>
            <!-- If Custom Sub Field (child) -->
            <div v-if="isSubField">
                <!-- If translatable -->
                <textarea class="form-control" v-model="customFieldValues[vModel][lang.slug][subFieldGroupIndex][childKey]" :id="childKey" :placeholder="field.placeholder[lang.slug]" v-if="isParentTranslatable"></textarea>
                <!-- If not translatable -->
                <textarea class="form-control" v-model="customFieldValues[vModel][subFieldGroupIndex][childKey]" :id="childKey" :placeholder="field.placeholder[lang.slug]" v-else></textarea>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>
        </template>

        <!-- Editor -->
        <div class="col-md-10 col-sm-10 col-xs-12" v-if="field.type == 'editor' && !isSubField">
            <div class="editor froala-container">
                <!-- If translatable -->
                <froala :tag="'textarea'" :config="froalaConfig" v-model="customFieldValues[vModel][lang.slug]" class="froala" :id="'froala-custom-field-'+index+'-'+lang.slug" v-if="field.isTranslatable"></froala>
                <!-- If not translatable -->
                <froala :tag="'textarea'" :config="froalaConfig" v-model="customFieldValues[vModel]" class="froala" :id="'froala-custom-field-'+index" v-else></froala>
            </div>
            <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
        </div>

        <!-- Email -->
        <template v-if="field.type == 'email'">
            <!-- If Custom Field (parent) -->
            <div class="col-md-10 col-sm-10 col-xs-12" v-if="!isSubField">
                <!-- If not translatable -->
                <input type="email" class="form-control" :id="vModel" v-model="customFieldValues[vModel]" :placeholder="field.placeholder[lang.slug]" v-if="!field.isTranslatable">
                <!-- If translatable -->
                <input type="email" class="form-control" :id="vModel" v-model="customFieldValues[vModel][lang.slug]" :placeholder="field.placeholder[lang.slug]" v-else>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>
            <!-- If Custom Sub Field (child) -->
            <div v-if="isSubField">
                <!-- If translatable -->
                <input type="email" class="form-control" :id="childKey" v-model="customFieldValues[vModel][lang.slug][subFieldGroupIndex][childKey]" :placeholder="field.placeholder[lang.slug]" v-if="isParentTranslatable">
                <!-- If not translatable -->
                <input type="email" class="form-control" :id="childKey" v-model="customFieldValues[vModel][subFieldGroupIndex][childKey]" :placeholder="field.placeholder[lang.slug]" v-else>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>
        </template>

        <!-- Number -->
        <template v-if="field.type == 'number'">
            <!-- If Custom Field (parent) -->
            <div class="col-md-10 col-sm-10 col-xs-12" v-if="!isSubField">
                <!-- If translatable -->
                <input type="number" class="form-control" :id="vModel" v-model="customFieldValues[vModel]" :placeholder="field.placeholder[lang.slug]" v-if="!field.isTranslatable">
                <!-- If not translatable -->
                <input type="number" class="form-control" :id="vModel" v-model="customFieldValues[vModel][lang.slug]" :placeholder="field.placeholder[lang.slug]" v-else>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>
            <!-- If Custom Sub Field (child) -->
            <div v-if="isSubField">
                <!-- If translatable -->
                <input type="number" class="form-control" :id="childKey" v-model="customFieldValues[vModel][lang.slug][subFieldGroupIndex][childKey]" :placeholder="field.placeholder[lang.slug]" v-if="isParentTranslatable">
                <!-- If not translatable -->
                <input type="number" class="form-control" :id="childKey" v-model="customFieldValues[vModel][subFieldGroupIndex][childKey]" :placeholder="field.placeholder[lang.slug]" v-else>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>
        </template>

        <!-- Date -->
        <template v-if="field.type == 'date'">
            <!-- If Custom Field (parent) -->
            <div class="col-md-10 col-sm-10 col-xs-12" v-if="!isSubField">
                <!-- If translatable -->
                <input type="date" class="form-control" :id="vModel" v-model="customFieldValues[vModel]" :placeholder="field.placeholder[lang.slug]" format="dd/MM/yyyy" v-if="!field.isTranslatable">
                <!-- If not translatable -->
                <input type="date" class="form-control" :id="vModel" v-model="customFieldValues[vModel][lang.slug]" :placeholder="field.placeholder[lang.slug]" format="dd/MM/yyyy" v-else>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>
            <!-- If Custom Sub Field (child) -->
            <div v-if="isSubField">
                <!-- If translatable -->
                <input type="date" class="form-control" :id="childKey" v-model="customFieldValues[vModel][lang.slug][subFieldGroupIndex][childKey]" :placeholder="field.placeholder[lang.slug]" format="dd/MM/yyyy" v-if="isParentTranslatable">
                <!-- If not translatable -->
                <input type="date" class="form-control" :id="childKey" v-model="customFieldValues[vModel][subFieldGroupIndex][childKey]" :placeholder="field.placeholder[lang.slug]" format="dd/MM/yyyy" v-else>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>
        </template>

        <!-- Boolean -->
        <template v-if="field.type == 'boolean'">
            <!-- If Custom Field (parent) -->
            <div class="col-md-6 col-sm-6 col-xs-12" v-if="!isSubField">
                <div class="btn-group" data-toggle="buttons">
                    <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="changeBoolean(true, vModel, field.isTranslatable)">
                        <input type="radio" name="visible" value="true"> &nbsp; {{trans.__true}} &nbsp;
                    </label>
                    <label class="btn btn-primary active" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="changeBoolean(false, vModel, field.isTranslatable)">
                        <input type="radio" name="visible" value="false"> {{trans.__false}}
                    </label>
                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </div>
            </div>

            <!-- If Custom Sub Field (child) -->
            <div v-if="isSubField">
                <div class="btn-group" data-toggle="buttons">
                    <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="changeBoolean(true, vModel, isParentTranslatable, childKey, subFieldGroupIndex)">
                        <input type="radio" name="visible" value="true"> &nbsp; {{trans.__true}} &nbsp;
                    </label>
                    <label class="btn btn-primary active" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="changeBoolean(false, vModel, isParentTranslatable, childKey, subFieldGroupIndex)">
                        <input type="radio" name="visible" value="false"> {{trans.__false}}
                    </label>
                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </div>
            </div>

        </template>

        <!-- Checkbox -->
        <template v-if="field.type == 'checkbox'">
            <!-- If Custom Field (parent) -->
            <div class="col-md-10 col-sm-10 col-xs-12" v-if="!isSubField">
                <!-- If translatable -->
                <div v-for="(option, key) in field.optionsValues.object" v-if="!field.isTranslatable">
                    <input type="checkbox" :id="vModel+index+key" v-model="customFieldValues[vModel]" :value="key">
                    <label :for="vModel+index+key">{{ option }}</label>
                </div>
                <!-- If not translatable -->
                <div v-for="(option, key) in field.optionsValues.object" v-else>
                    <input type="checkbox" :id="vModel+index+key" v-model="customFieldValues[vModel][lang.slug]" :value="key">
                    <label :for="vModel+index+key">{{ option }}</label>
                </div>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>
            <!-- If Custom Sub Field (child) -->
            <div v-if="isSubField">
                <!-- If translatable -->
                <template v-if="isParentTranslatable">
                    <div v-for="(option, key) in field.optionsValues.object">
                        <input type="checkbox" :id="childKey+subFieldGroupIndex+key" v-model="customFieldValues[vModel][lang.slug][subFieldGroupIndex][childKey]" :value="key">
                        <label :for="childKey+subFieldGroupIndex+key">{{ option }}</label>
                    </div>
                </template>
                <!-- If not translatable -->
                <template v-else>
                    <div v-for="(option, key) in field.optionsValues.object">
                        <input type="checkbox" :id="childKey+subFieldGroupIndex+key" v-model="customFieldValues[vModel][subFieldGroupIndex][childKey]" :value="key">
                        <label :for="childKey+subFieldGroupIndex+key">{{ option }}</label>
                    </div>
                </template>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>
        </template>

        <!-- Radio buttons -->
        <template v-if="field.type == 'radio'">
            <!-- If Custom Field (parent) -->
            <div class="col-md-10 col-sm-10 col-xs-12" v-if="!isSubField">
                <!-- If translatable -->
                <div v-for="(option, key) in field.optionsValues.object" v-if="!field.isTranslatable">
                    <input type="radio" :id="vModel+index+key" v-model="customFieldValues[vModel]" :value="key" :name="vModel">
                    <label :for="vModel+index+key">{{ option }}</label>
                </div>
                <!-- If not translatable -->
                <div v-for="(option, key) in field.optionsValues.object" v-else>
                    <input type="radio" :id="vModel+index+key" v-model="customFieldValues[vModel][lang.slug]" :value="key" :name="vModel">
                    <label :for="vModel+index+key">{{ option }}</label>
                </div>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>
            <!-- If Custom Sub Field (child) -->
            <div v-if="isSubField">
                <!-- If translatable -->
                <template v-if="isParentTranslatable">
                    <div v-for="(option, key) in field.optionsValues.object">
                        <input type="radio" :id="childKey+subFieldGroupIndex+key" v-model="customFieldValues[vModel][lang.slug][subFieldGroupIndex][childKey]" :value="key" :name="childKey+'-'+subFieldGroupIndex">
                        <label :for="childKey+subFieldGroupIndex+key">{{ option }}</label>
                    </div>
                </template>
                <!-- If not translatable -->
                <template v-else>
                    <div v-for="(option, key) in field.optionsValues.object">
                        <input type="radio" :id="childKey+subFieldGroupIndex+key" v-model="customFieldValues[vModel][subFieldGroupIndex][childKey]" :value="key" :name="childKey+'-'+subFieldGroupIndex">
                        <label :for="childKey+subFieldGroupIndex+key">{{ option }}</label>
                    </div>
                </template>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>
        </template>


        <!-- Dropdown -->
        <template v-if="field.type == 'dropdown'">
            <!-- If Custom Field (parent) -->
            <template v-if="!isSubField">
                <!-- if dropdown is not multiple -->
                <div class="col-md-10 col-sm-10 col-xs-12" v-if="!field.isMultiple">
                    <!-- If translatable -->
                    <select class="form-control" :id="vModel" v-model="customFieldValues[vModel]" :placeholder="field.placeholder[lang.slug]" v-if="!field.isTranslatable">
                        <option v-for="(option, key) in field.optionsValues.object" :value="key">{{option}}</option>
                    </select>
                    <!-- If not translatable -->
                    <select class="form-control" :id="vModel" v-model="customFieldValues[vModel][lang.slug]" :placeholder="field.placeholder[lang.slug]" v-else>
                        <option v-for="(option, key) in field.optionsValues.object" :value="key">{{option}}</option>
                    </select>
                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </div>
                <!-- if dropdown is multiple -->
                <div class="col-md-10 col-sm-10 col-xs-12" v-else>
                    <multiselect
                            v-model="dropdownValue"
                            :options="dropdownOptions"
                            :multiple="field.isMultiple"
                            :close-on-select="true"
                            :hide-selected="true"
                            label="name"
                            track-by="value"
                            @open="getDropdownOptions(field)"
                            @input="setDropdownVModel"></multiselect>
                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </div>
            </template>

            <!-- If Custom Sub Field (child) -->
            <template v-if="isSubField">
                <!-- if dropdown is not multiple -->
                <div v-if="!field.isMultiple">
                    <!-- If translatable -->
                    <select class="form-control" :id="childKey" v-model="customFieldValues[vModel][lang.slug][subFieldGroupIndex][childKey]" :placeholder="field.placeholder[lang.slug]" v-if="isParentTranslatable">
                        <option v-for="(option, key) in field.optionsValues.object" :value="key">{{option}}</option>
                    </select>
                    <!-- If not translatable -->
                    <select class="form-control" :id="childKey" v-model="customFieldValues[vModel][subFieldGroupIndex][childKey]" :placeholder="field.placeholder[lang.slug]" v-else>
                        <option v-for="(option, key) in field.optionsValues.object" :value="key">{{option}}</option>
                    </select>
                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </div>
                <!-- if dropdown is multiple -->
                <div v-else>
                    <multiselect
                            v-model="dropdownValue"
                            :options="dropdownOptions"
                            :multiple="field.isMultiple"
                            :close-on-select="true"
                            :hide-selected="true"
                            label="name"
                            track-by="value"
                            @open="getDropdownOptions(field)"
                            @input="setDropdownVModel"></multiselect>
                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </div>

            </template>

        </template>

        <!--************************************************
            Dropdown from DB
        *************************************************-->
        <template v-if="field.type == 'db'">
            <!-- If Custom Field (parent) -->
            <div class="col-md-10 col-sm-10 col-xs-12" v-if="!isSubField">
                <!-- If translatable -->
                <div v-if="field.isTranslatable">
                    <!-- if belongs to User -->
                    <div v-if="field.properties.dbTable.belongsTo == 'User'">
                        <multiselect
                                v-model="tmpVModel"
                                :options="fromDBOptions"
                                :multiple="field.isMultiple"
                                :close-on-select="true"
                                :hide-selected="true"
                                label="fullName"
                                track-by="userID"
                                :show-labels="false"
                                @open="getFromDBOptions(field)"
                                @select="populateDBValue($event, 'user')"></multiselect>
                    </div>

                    <!-- if belongs to Post Type -->
                    <div v-if="field.isMultiple && field.properties.dbTable.belongsTo == 'PostType'">
                        <multiselect
                                v-model="tmpVModel"
                                :options="fromDBOptions"
                                :multiple="field.isMultiple"
                                :close-on-select="true"
                                :hide-selected="true"
                                label="title"
                                track-by="ID"
                                :show-labels="false"
                                @open="getFromDBOptions(field)"
                                @select="populateDBValue($event, 'PostType')"></multiselect>
                    </div>
                </div>

                <!-- If not translatable -->
                <div v-if="!field.isTranslatable">
                    <!-- if belongs to User -->
                    <div v-if="field.properties.dbTable.belongsTo == 'User'">
                        <multiselect
                                v-model="tmpVModel"
                                :options="fromDBOptions"
                                :multiple="field.isMultiple"
                                :close-on-select="true"
                                :hide-selected="true"
                                label="fullName"
                                track-by="userID"
                                :show-labels="false"
                                @open="getFromDBOptions(field)"
                                @select="populateDBValue($event, 'user')"></multiselect>
                    </div>

                    <!-- if belongs to Post Type -->
                    <div v-if="field.properties.dbTable.belongsTo == 'PostType'">
                        <multiselect
                                v-model="tmpVModel"
                                :options="fromDBOptions"
                                :multiple="field.isMultiple"
                                :close-on-select="true"
                                :hide-selected="true"
                                :loading="isLoading"
                                label="title"
                                track-by="ID"
                                :show-labels="false"
                                @open="getFromDBOptions(field)"
                                @select="populateDBValue($event, 'PostType')"></multiselect>
                    </div>
                </div>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>

            <!-- If Custom Sub Field (child) -->
            <div v-if="isSubField">
                <!-- If translatable -->
                <template v-if="isParentTranslatable">
                    <!-- if belongs to User -->
                    <div v-if="field.properties.dbTable.belongsTo == 'User'">
                        <multiselect
                                v-model="tmpVModel"
                                :options="fromDBOptions"
                                :multiple="field.isMultiple"
                                :close-on-select="true"
                                :hide-selected="true"
                                label="fullName"
                                track-by="userID"
                                :show-labels="false"
                                @open="getFromDBOptions(field)"
                                @select="populateDBValue($event, 'user')"></multiselect>
                    </div>

                    <!-- if belongs to Post Type -->
                    <div v-if="field.properties.dbTable.belongsTo == 'PostType'">
                        <multiselect
                                v-model="tmpVModel"
                                :options="fromDBOptions"
                                :multiple="field.isMultiple"
                                :close-on-select="true"
                                :hide-selected="true"
                                :loading="isLoading"
                                label="title"
                                track-by="ID"
                                :show-labels="false"
                                @open="getFromDBOptions(field)"
                                @select="populateDBValue($event, 'PostType')"></multiselect>
                    </div>
                </template>
                <!-- If not translatable -->
                <template v-else>
                    <!-- if belongs to User -->
                    <div v-if="field.properties.dbTable.belongsTo == 'User'">
                        <multiselect
                                v-model="tmpVModel"
                                :options="fromDBOptions"
                                :multiple="field.isMultiple"
                                :close-on-select="true"
                                :hide-selected="true"
                                label="fullName"
                                track-by="userID"
                                :show-labels="false"
                                @open="getFromDBOptions(field)"
                                @select="populateDBValue($event, 'user')"></multiselect>
                    </div>

                    <!-- if belongs to Post Type -->
                    <div v-if="field.properties.dbTable.belongsTo == 'PostType'">
                        <multiselect
                                v-model="tmpVModel"
                                :options="fromDBOptions"
                                :multiple="field.isMultiple"
                                :close-on-select="true"
                                :hide-selected="true"
                                :loading="isLoading"
                                label="title"
                                track-by="ID"
                                :show-labels="false"
                                @open="getFromDBOptions(field)"
                                @select="populateDBValue($event, 'PostType')"></multiselect>
                    </div>
                </template>
                <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
            </div>
        </template>
        <!--************************************************
            END Dropdown from DB
        *************************************************-->

        <!-- Images -->
        <span style="display:none">{{ isMediaOpen }}</span>
        <template v-if="field.type == 'image'">
            <!-- If Custom Field (parent) -->
            <div v-if="!isSubField">
                <!-- If it is translatable -->
                <div class="imagePrevContainer col-md-10 col-sm-10 col-xs-12" v-if="field.isTranslatable">

                    <div class="imageSingleThumb" v-for="(file, count) in mediaSelectedFiles[vModel+'__lang__'+lang.slug]" v-if="mediaSelectedFiles[vModel+'__lang__'+lang.slug] !== undefined">
                        <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile(vModel+'__lang__'+lang.slug, file.mediaID)"></i>
                        <img :src="generateUrl(constructUrl(file))">
                    </div>

                    <div class="clearfix"></div>

                    <a class="btn btn-info" @click="openMedia('image', vModel, lang.slug, field.isMultiple, true)" v-if="mediaSelectedFiles[vModel+'__lang__'+lang.slug]">
                        {{trans.__change}}
                    </a>

                    <a class="btn btn-info" @click="openMedia('image', vModel, lang.slug, field.isMultiple, false)">
                        {{trans.__addImage}}
                    </a>

                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </div>

                <!-- If it is not translatable -->
                <div class="imagePrevContainer col-md-10 col-sm-10 col-xs-12" v-if="!field.isTranslatable">
                    <div class="imageSingleThumb" v-for="(file, count) in mediaSelectedFiles[vModel]" v-if="mediaSelectedFiles[vModel] !== undefined">
                        <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile(vModel, file.mediaID)"></i>
                        <img :src="generateUrl(constructUrl(file))">
                    </div>

                    <div class="clearfix"></div>

                    <a class="btn btn-info" @click="openMedia('image', vModel, '', field.isMultiple, true)" v-if="mediaSelectedFiles[vModel]">
                        {{ trans.__change }}
                    </a>

                    <a class="btn btn-info" @click="openMedia('image', vModel, '', field.isMultiple, false)">{{trans.__addImage}}</a>

                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </div>
            </div>

            <!-- If Custom Sub Field (child) -->
            <div v-if="isSubField">
                <!-- If it is translatable -->
                <template v-if="isParentTranslatable">
                    <div class="imageSingleThumb" v-for="(file, count) in mediaSelectedFiles[vModel+'___'+subFieldGroupIndex+'___'+childKey+'___lang___'+lang.slug]" v-if="mediaSelectedFiles[vModel+'___'+subFieldGroupIndex+'___'+childKey+'___lang___'+lang.slug] !== undefined">
                        <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile(vModel+'___'+subFieldGroupIndex+'___'+childKey+'___lang___'+lang.slug, file.mediaID)"></i>
                        <img :src="generateUrl(constructUrl(file))">
                    </div>

                    <div class="clearfix"></div>

                    <a class="btn btn-info" @click="openMedia('image', vModel+'___'+subFieldGroupIndex+'___'+childKey+'___lang___'+lang.slug, '', field.isMultiple, true)" v-if="mediaSelectedFiles[vModel+'___'+subFieldGroupIndex+'___'+childKey+'___lang___'+lang.slug]">
                        {{trans.__change}}
                    </a>

                    <a class="btn btn-info" @click="openMedia('image', vModel+'___'+subFieldGroupIndex+'___'+childKey+'___lang___'+lang.slug, '', field.isMultiple, false)">{{trans.__addImage}}</a>

                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </template>

                <!-- If it is not translatable -->
                <template v-else>
                    <div class="imageSingleThumb" v-for="(file, count) in mediaSelectedFiles[vModel+'___'+subFieldGroupIndex+'___'+childKey]" v-if="mediaSelectedFiles[vModel+'___'+subFieldGroupIndex+'___'+childKey] !== undefined">
                        <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile(vModel+'___'+subFieldGroupIndex+'___'+childKey, file.mediaID)"></i>
                        <img :src="generateUrl(constructUrl(file))">
                    </div>

                    <div class="clearfix"></div>

                    <a class="btn btn-info" @click="openMedia('image', vModel+'___'+subFieldGroupIndex+'___'+childKey, '', field.isMultiple, true)" v-if="mediaSelectedFiles[vModel+'___'+subFieldGroupIndex+'___'+childKey]">
                        {{trans.__change}}
                    </a>

                    <a class="btn btn-info" @click="openMedia('image', vModel+'___'+subFieldGroupIndex+'___'+childKey, '', field.isMultiple, false)">{{trans.__addImage}}</a>

                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </template>

            </div>
        </template>

        <!-- Files -->
        <template v-if="field.type == 'file'">
            <!-- If Custom Field (parent) -->
            <div v-if="!isSubField">
                <!-- If it is translatable -->
                <div class="imagePrevContainer col-md-10 col-sm-10 col-xs-12" v-if="field.isTranslatable">
                    <div class="imageSingleThumb" v-for="(file, count) in mediaSelectedFiles[vModel+'__lang__'+lang.slug]">
                        <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile(vModel+'__lang__'+lang.slug, file.mediaID)"></i>
                        <img :src="resourcesUrl('/images/document.png')">
                    </div>

                    <div class="clearfix"></div>

                    <a class="btn btn-info" @click="openMedia('document', vModel, lang.slug, field.isMultiple, true)" v-if="mediaSelectedFiles[vModel]">
                        {{trans.__change}}
                    </a>
                    <a class="btn btn-info" @click="openMedia('document', vModel, lang.slug, field.isMultiple, false)">{{trans.__addFile}}</a>

                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </div>

                <!-- If it is not translatable -->
                <div class="imagePrevContainer col-md-10 col-sm-10 col-xs-12" v-if="!field.translatable">
                    <div class="imageSingleThumb" v-for="(file, count) in mediaSelectedFiles[vModel]">
                        <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile(vModel, file.mediaID)"></i>
                        <img :src="resourcesUrl('/images/document.png')">
                    </div>

                    <div class="clearfix"></div>

                    <a class="btn btn-info" @click="openMedia('document', vModel, '', field.isMultiple, true)" v-if="mediaSelectedFiles[vModel]">
                        {{trans.__change}}
                    </a>
                    <a class="btn btn-info" @click="openMedia('document', vModel, '', field.isMultiple, false)">{{trans.__addFile}}</a>

                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </div>
            </div>

            <!-- If Custom Sub Field (child) -->
            <div v-if="isSubField">
                <!-- If it is translatable -->
                <template v-if="isParentTranslatable">
                    <div class="imageSingleThumb" v-for="(file, count) in mediaSelectedFiles[vModel+'___'+subFieldGroupIndex+'___'+childKey]">
                        <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile(vModel+'___'+subFieldGroupIndex+'___'+childKey, file.mediaID)"></i>
                        <img :src="resourcesUrl('/images/document.png')">
                    </div>

                    <div class="clearfix"></div>

                    <a class="btn btn-info" @click="openMedia('document', vModel+'___'+subFieldGroupIndex+'___'+childKey, '', field.isMultiple, true)" v-if="mediaSelectedFiles[vModel+'___'+subFieldGroupIndex+'___'+childKey]">
                        {{trans.__change}}
                    </a>
                    <a class="btn btn-info" @click="openMedia('document', vModel+'___'+subFieldGroupIndex+'___'+childKey, '', field.isMultiple, false)">{{trans.__addFile}}</a>

                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </template>
                <!-- If it is not translatable -->
                <template v-else>
                    <div class="imageSingleThumb" v-for="(file, count) in mediaSelectedFiles[vModel+'___'+subFieldGroupIndex+'___'+childKey]">
                        <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile(vModel+'___'+subFieldGroupIndex+'___'+childKey, file.mediaID)"></i>
                        <img :src="resourcesUrl('/images/document.png')">
                    </div>

                    <div class="clearfix"></div>

                    <a class="btn btn-info" @click="openMedia('document', vModel+'___'+subFieldGroupIndex+'___'+childKey, '', field.isMultiple, true)" v-if="mediaSelectedFiles[vModel+'___'+subFieldGroupIndex+'___'+childKey]">
                        {{trans.__change}}
                    </a>
                    <a class="btn btn-info" @click="openMedia('document', vModel+'___'+subFieldGroupIndex+'___'+childKey, '', field.isMultiple, false)">{{trans.__addFile}}</a>

                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </template>

            </div>

        </template>

        <!-- Video -->
        <template v-if="field.type == 'video'">
            <!-- If Custom Field (parent) -->
            <div v-if="!isSubField">
                <!-- If it is translatable -->
                <div class="videoPrevContainer col-md-10 col-sm-10 col-xs-12" v-if="field.isTranslatable">
                    <div class="videoSingleThumb" v-for="(file, count) in mediaSelectedFiles[vModel+'__lang__'+lang.slug]">
                        <i class="fa fa-close closeBtnForPrevImages" style="margin-left: 100px;" @click="deleteSelectedMediaFile(vModel+'__lang__'+lang.slug, file.mediaID)"></i>
                        <img :src="resourcesUrl('/images/video.png')">
                        <p style="word-wrap: break-word; padding:5px;">{{ file.title }}</p>
                    </div>

                    <div class="clearfix"></div>

                    <a class="btn btn-info" @click="openMedia('video', vModel, lang.slug, field.isMultiple, true)" v-if="mediaSelectedFiles[vModel]">
                        {{trans.__change}}
                    </a>
                    <a class="btn btn-info" @click="openMedia('video', vModel, lang.slug, field.isMultiple, false)">{{trans.__addVideo}}</a>

                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </div>

                <!-- If it is not translatable -->
                <div class="videoPrevContainer col-md-10 col-sm-10 col-xs-12" v-if="!field.translatable">
                    <div class="videoSingleThumb" v-for="(file, count) in mediaSelectedFiles[vModel]">
                        <i class="fa fa-close closeBtnForPrevImages" style="margin-left: 100px;" @click="deleteSelectedMediaFile(vModel, file.mediaID)"></i>
                        <img :src="resourcesUrl('/images/video.png')">
                        <p style="word-wrap: break-word; padding:5px;">{{ file.title }}</p>
                    </div>

                    <div class="clearfix"></div>

                    <a class="btn btn-info" @click="openMedia('video', vModel, '', field.isMultiple, true)" v-if="mediaSelectedFiles[vModel]">
                        {{trans.__change}}
                    </a>
                    <a class="btn btn-info" @click="openMedia('video', vModel, '', field.isMultiple, false)">{{trans.__addVideo}}</a>

                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </div>
            </div>

            <!-- If Custom Sub Field (child) -->
            <div v-if="isSubField">
                <!-- If it is translatable -->
                <template v-if="isParentTranslatable">
                    <div class="videoSingleThumb" v-for="(file, count) in mediaSelectedFiles[vModel+'___'+subFieldGroupIndex+'___'+childKey]">
                        <i class="fa fa-close closeBtnForPrevImages" style="margin-left: 100px;" @click="deleteSelectedMediaFile(vModel+'___'+subFieldGroupIndex+'___'+childKey, file.mediaID)"></i>
                        <img :src="resourcesUrl('/images/video.png')">
                        <p style="word-wrap: break-word; padding:5px;">{{ file.title }}</p>
                    </div>

                    <div class="clearfix"></div>

                    <a class="btn btn-info" @click="openMedia('video', vModel+'___'+subFieldGroupIndex+'___'+childKey, '', field.isMultiple, true)" v-if="mediaSelectedFiles[vModel+'___'+subFieldGroupIndex+'___'+childKey]">
                        {{trans.__change}}
                    </a>
                    <a class="btn btn-info" @click="openMedia('video', vModel+'___'+subFieldGroupIndex+'___'+childKey, '', field.isMultiple, false)">{{trans.__addVideo}}</a>

                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </template>
                <!-- If it is not translatable -->
                <template v-else>
                    <div class="videoSingleThumb" v-for="(file, count) in mediaSelectedFiles[vModel+'___'+subFieldGroupIndex+'___'+childKey]">
                        <i class="fa fa-close closeBtnForPrevImages" style="margin-left: 100px;" @click="deleteSelectedMediaFile(vModel+'___'+subFieldGroupIndex+'___'+childKey, file.mediaID)"></i>
                        <img :src="resourcesUrl('/images/video.png')">
                        <p style="word-wrap: break-word; padding:5px;">{{ file.title }}</p>
                    </div>

                    <div class="clearfix"></div>

                    <a class="btn btn-info" @click="openMedia('video', vModel+'___'+subFieldGroupIndex+'___'+childKey, '', field.isMultiple, true)" v-if="mediaSelectedFiles[vModel+'___'+subFieldGroupIndex+'___'+childKey]">
                        {{trans.__change}}
                    </a>
                    <a class="btn btn-info" @click="openMedia('video', vModel+'___'+subFieldGroupIndex+'___'+childKey, '', field.isMultiple, false)">{{trans.__addVideo}}</a>

                    <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
                </template>

            </div>

        </template>

        <!-- Repeater -->
        <div v-if="field.type == 'repeater' && subFieldsActive" class="col-md-10 col-sm-10 col-xs-12">
            <div class="col-md-12 col-sm-12">
                <table class="subFieldTable">
                    <tr>
                        <th v-for="subField in childrenFieldsGroups[field.customFieldID]">{{ subField.label[lang.slug] }}</th>
                    </tr>

                    <template v-if="subFields[vModel] !== undefined">
                        <tr v-for="(obj, subFieldGroupIndex) in subFields[vModel][lang.slug]" v-if="field.isTranslatable">
                            <td v-for="(subField, subFieldIndex) in obj" style="padding:3px;">
                                <customField
                                        :vModel="vModel"
                                        :childKey="groupSlug+'__'+subField.slug"
                                        :groupSlug="groupSlug"
                                        :field="subField"
                                        :index="subFieldIndex"
                                        :subFieldGroupIndex="subFieldGroupIndex"
                                        :lang="lang"
                                        :isParentTranslatable="field.isTranslatable"
                                        :isSubField="true"
                                        :trans="trans"
                                        :childrenFieldsGroups="[]"
                                        :customFieldValues="customFieldValues"></customField>
                            </td>
                            <td><i class="fa fa-minus-circle fa-2x" style="cursor: pointer;" @click="removeSubFieldValue(subFieldGroupIndex)"></i></td>
                        </tr>

                        <tr v-for="(obj, subFieldGroupIndex) in subFields[vModel]" v-if="!field.isTranslatable">
                            <td v-for="(subField, subFieldIndex) in obj" style="padding:3px;">
                                <customField
                                        :vModel="vModel"
                                        :childKey="groupSlug+'__'+subField.slug"
                                        :groupSlug="groupSlug"
                                        :field="subField"
                                        :index="subFieldGroupIndex"
                                        :subFieldGroupIndex="subFieldGroupIndex"
                                        :lang="lang"
                                        :isParentTranslatable="field.isTranslatable"
                                        :isSubField="true"
                                        :trans="trans"
                                        :childrenFieldsGroups="[]"
                                        :customFieldValues="customFieldValues"></customField>

                            </td>
                            <td><i class="fa fa-minus-circle fa-2x" style="cursor: pointer;" @click="removeSubFieldValue(subFieldGroupIndex)"></i></td>
                        </tr>
                    </template>

                </table>
            </div>
            <div class="col-md-12 col-sm-12" style="text-align:center;"><i class="fa fa-plus-circle fa-2x" style="cursor: pointer;" @click="addSubFieldValue"></i></div>
            <div class="alert" v-if="StoreResponse.errors[vModel+'_'+lang.slug]" v-for="error in StoreResponse.errors[vModel+'_'+lang.slug]">{{ error }}</div>
        </div>

    </div>
</template>
<style scoped>
    .subFieldTable{
        width: 100%;
        border: 1px solid #EAEAEA;
    }
    .subFieldTable tr td, .subFieldTable tr th{
        border: 1px solid #EAEAEA;
    }
    .subFieldTable tr th{
        height: 30px;
        text-align: center;
    }
</style>
<script>
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalComputed } from '../../mixins/globalComputed';

    export default{
        mixins: [globalMethods, globalComputed],
        props:['field','index','customFieldValues','vModel','lang','trans','groupSlug','childrenFieldsGroups','isSubField','childKey','subFieldGroupIndex',"isParentTranslatable"],
        data(){
            return{
                fromDBOptions: [],
                dropdownOptions: [],
                isLoading: false,
                dropdownValue: [],
                customFieldValuesTmp: [],
                subFields: [],
                subFieldsActive: true,
                tmpVModel:[],
            }
        },
        created(){
            this.subFields = this.getSubFields;

            if(!this.isSubField){
                if(this.field.isTranslatable){
                    this.customFieldValuesTmp = this.customFieldValues[this.vModel][this.lang.slug];
                }else{
                    this.customFieldValuesTmp = this.customFieldValues[this.vModel];
                }
            }else{
                if(this.isParentTranslatable){
                    this.customFieldValuesTmp = this.customFieldValues[this.vModel][this.lang.slug][this.subFieldGroupIndex][this.childKey];
                }else{
                    this.customFieldValuesTmp = this.customFieldValues[this.vModel][this.subFieldGroupIndex][this.childKey];
                }
            }

            if(this.field.type == "dropdown"){
                if(
                    (typeof this.customFieldValuesTmp == 'object' && Object.keys(this.customFieldValuesTmp).length)
                    || (typeof this.customFieldValuesTmp == 'number' && this.customFieldValuesTmp != '')
                   ){
                        this.getDropdownOptions(this.field);
                        for(let k in this.dropdownOptions){
                            let value = this.dropdownOptions[k].value;
                            if(this.customFieldValuesTmp.indexOf(value) != -1){
                                this.dropdownValue.push(this.dropdownOptions[k]);
                            }
                        }
                   }
            }else if(this.field.type == "db"){
                this.getFromDBOptions(this.field);
            }else if(this.field.type == "editor"){
                if(this.field.isTranslatable){
                    if(typeof this.customFieldValues[this.vModel][this.lang.slug] == "object" && Object.keys(this.customFieldValues[this.vModel][this.lang.slug]).length == 0){
                        this.customFieldValues[this.vModel][this.lang.slug] = "";
                    }
                }else{
                    if(typeof this.customFieldValues[this.vModel] == "object" && Object.keys(this.customFieldValues[this.vModel]).length == 0){
                        this.customFieldValues[this.vModel] = "";
                    }
                }
            }
        },
        methods: {

            // change boolean value
            changeBoolean(option, key, isTranslatable, childKey = '', childGroupIndex = ''){
                if(childKey == ''){
                    if(isTranslatable){
                        this.customFieldValues[key][this.lang.slug] = option;
                    }else{
                        this.customFieldValues[key] = option;
                    }
                }else{
                    if(isTranslatable){
                        this.customFieldValues[key][this.lang.slug][childGroupIndex][childKey] = option;
                    }else{
                        this.customFieldValues[key][childGroupIndex][childKey] = option;
                    }
                }
            },

            // get options from db
            getFromDBOptions(field){
                if(!this.fromDBOptions.length){
                    this.isLoading = true;
                    this.$http.post(this.basePath+'/'+this.$route.params.adminPrefix+'/json/custom-fields/get-table-data',field.properties.dbTable)
                        .then((resp) => {
                            if(field.properties.dbTable.name != 'users'){
                                this.fromDBOptions = resp.body;
                            }else{
                                var tmp = [];
                                var tmpData = resp.body;
                                for(let k in tmpData){
                                    tmpData[k].fullName = tmpData[k].firstName+" "+tmpData[k].lastName;
                                    tmp.push(tmpData[k]);
                                }
                                this.fromDBOptions = tmp;
                            }
                            this.isLoading = false;
                        }, error =>{
                            this.isLoading = false;
                        }).then(resp => {
                            this.setTmpVModel();
                        });
                }
            },

            setTmpVModel(){
                if(
                    (typeof this.customFieldValuesTmp == 'object' && Object.keys(this.customFieldValuesTmp).length)
                    || (typeof this.customFieldValuesTmp == 'number' && this.customFieldValuesTmp != '')
                   ){
                    for(let k in this.fromDBOptions){
                        if(this.field.properties.dbTable.belongsTo == 'User'){
                            var id = this.fromDBOptions[k].userID;
                        }else if(this.field.properties.dbTable.belongsTo == 'PostType'){
                            var id = this.fromDBOptions[k].ID;
                        }

                        if(this.field.isMultiple){
                            if(this.customFieldValuesTmp.indexOf(id) != -1){
                                this.tmpVModel.push(this.fromDBOptions[k]);
                            }
                        }else{
                            if(id != this.customFieldValuesTmp){
                                this.tmpVModel = this.fromDBOptions[k];
                            }
                        }

                    }
                }
            },

            // make the options for the dropdown multiselect
            getDropdownOptions(field){
                var optionsInOBJ = field.optionsValues.object;
                var options = [];
                for(let k in optionsInOBJ){
                    options.push({name: optionsInOBJ[k], value: k})
                }
                this.dropdownOptions = options;
            },

            // sets the selected value/s for multiselect dropdown input type
            setDropdownVModel(ev){
                var result = [];
                for(let k in this.dropdownValue){
                    result.push(this.dropdownValue[k].value);
                }
                if(!this.isSubField){
                    if(this.field.isTranslatable){
                        this.customFieldValues[this.vModel][this.lang.slug] = result;
                    }else{
                        this.customFieldValues[this.vModel] = result;
                    }
                }else{
                    if(this.field.isTranslatable){
                        this.customFieldValues[this.vModel][this.lang.slug][this.subFieldGroupIndex][this.childKey] = result;
                    }else{
                        this.customFieldValues[this.vModel][this.subFieldGroupIndex][this.childKey] = result;
                    }
                }
            },

            // open the media popup and with his options
            openMedia(format, inputName, langSlug, multiple, clear){
                this.$store.commit('setOpenMediaOptions', {
                    multiple: multiple,
                    has_multile_files: true,
                    multipleInputs: true,
                    format : format,
                    inputName: inputName,
                    langSlug: langSlug,
                    clear: clear
                });
                this.$store.commit('setIsMediaOpen', true);
            },

            /**
             * this function is used to remove the selected images in custom fields
             * */
            deleteSelectedMediaFile(key, mediaID){
                // delete media from vuex
                let mediaArr = this.mediaSelectedFiles;
                for(let k in mediaArr[key]){
                    if(key == "featuredImage"){
                        delete mediaArr[key];
                        continue;
                    }
                    if(mediaArr[key][k].mediaID == mediaID){
                        mediaArr[key].splice(k, 1);
                    }
                    if(mediaArr[key].length == 0){
                        delete mediaArr[key];
                    }
                }
                // delete media from the custom field variables
                this.deleteImagesFromCustomFields(key, mediaID);

                this.$store.commit('setMediaSelectedFiles', "");
                this.$store.commit('setMediaSelectedFiles', mediaArr);
            },

            /**
             *  Delete media from the custom custom field variables
             * */
            deleteImagesFromCustomFields(key, mediaID){
                // delete media ids in custom field values
                if(this.customFieldValues[key] !== undefined){
                    if(typeof this.customFieldValues[key] == 'object'){
                        for(let cKey in this.customFieldValues[key]){
                            if(this.customFieldValues[key][cKey] == mediaID){
                                this.customFieldValues[key].splice(cKey, 1);
                            }
                        }
                    }else{
                        this.customFieldValues[key] = [];
                    }
                }else{
                    let keysArray = key.split("___");
                    /* if non translatable sub fields */
                    if(keysArray.length == 3){
                        if(this.customFieldValues[keysArray[0]] !== undefined
                            && this.customFieldValues[keysArray[0]][keysArray[1]] !== undefined
                            && this.customFieldValues[keysArray[0]][keysArray[1]][keysArray[2]] !== undefined){

                            if(typeof this.customFieldValues[keysArray[0]][keysArray[1]][keysArray[2]] == 'object'){
                                this.customFieldValues[keysArray[0]][keysArray[1]][keysArray[2]] = this.deleteValue(this.customFieldValues[keysArray[0]][keysArray[1]][keysArray[2]], mediaID);
                            }
                        }

                    }else if(keysArray.length == 5){
                        // For translatable sub custom fields (within repeater custom field)
                        if(this.customFieldValues[keysArray[0]] !== undefined
                            && this.customFieldValues[keysArray[0]][keysArray[4]] !== undefined
                            && this.customFieldValues[keysArray[0]][keysArray[4]][keysArray[1]] !== undefined
                            && this.customFieldValues[keysArray[0]][keysArray[4]][keysArray[1]][keysArray[2]] !== undefined){

                            if(typeof this.customFieldValues[keysArray[0]][keysArray[4]][keysArray[1]][keysArray[2]] == 'object'){
                                this.customFieldValues[keysArray[0]][keysArray[4]][keysArray[1]][keysArray[2]] = this.deleteValue(this.customFieldValues[keysArray[0]][keysArray[4]][keysArray[1]][keysArray[2]], mediaID);
                            }
                        }
                    }else{
                        /* FOR TRANSLATABLE MEDIA CUSTOM FIELDS */
                        let keysArray = key.split("__lang__");

                        if(keysArray.length == 2){
                            if(this.customFieldValues[keysArray[0]] !== undefined && typeof this.customFieldValues[keysArray[0]] == 'object'){
                                for(let cKey in this.customFieldValues[keysArray[0]]){
                                    // if field is multiple (custom field that allows multiple images)
                                    if(typeof this.customFieldValues[keysArray[0]][cKey] == 'object'){
                                        this.customFieldValues[keysArray[0]][cKey] = this.deleteValue(this.customFieldValues[keysArray[0]][cKey], mediaID);
                                    }else{
                                        // if custom field allows only one image to be selected
                                        this.customFieldValues[keysArray[0]][cKey] = [];
                                    }

                                }
                            }
                        }
                    }
                }
            },

            /**
             * Deletes a value from the array
             * @param array main array
             * @param value to be deleted
             * @returns {*}
             */
            deleteValue(array, value){
                let index = array.indexOf(value);
                delete array[index];
                return this.remakeArray(array);
            },
            /**
             * remakes array with the count keys (used after delete a key)
             * @param array
             * @returns {Array}
             */
            remakeArray(array){
                let count = 0;
                let result = [];
                for(let k in array){
                    result[count] = array[k];
                    count++;
                }
                return result;
            },


            // repair url to get the thumb
            constructUrl(image){
                let url = "";
                if(image.type == "image"){
                    url = "/"+image.fileDirectory + "/200x200/" + image.filename;
                }else if(image.type == "document"){
                    url = this.documentIconUrl;
                }else if(image.type == "video"){
                    url = this.videoIconUrl;
                }else if(image.type == "audio"){
                    url = this.audioIconUrl;
                }
                return url;
            },

            // add a sub field value for the repeater input type
            addSubFieldValue(){
                let tmp = {};
                this.subFields = [];
                if(this.childrenFieldsGroups[this.field.customFieldID] !== undefined){
                    let fields = this.childrenFieldsGroups[this.field.customFieldID];
                    if(this.field.isTranslatable){
                        this.$store.commit("addSubCustomField", {key: this.groupSlug+'__'+this.field.slug, value: fields, lang: this.lang.slug});
                    }else{
                        this.$store.commit("addSubCustomField", {key: this.groupSlug+'__'+this.field.slug, value: fields});
                    }
                    for(let k in fields){
                        let field = fields[k];
                        tmp[this.groupSlug+'__'+field.slug] = [];
                    }
                }
                this.subFields = this.getSubFields;

                if(this.field.isTranslatable){
                    this.customFieldValues[this.vModel][this.lang.slug].push(tmp);
                }else{
                    this.customFieldValues[this.vModel].push(tmp);
                }
            },

            // when remove sub field button is clicked / to remove a sub field
            removeSubFieldValue(subFieldGroupIndex){
                this.subFieldsActive = false;

                // remove fields
                var subFields = this.subFields;
                this.subFields = [];

                var result = [];
                for(let groupKey in subFields){
                    let globalGroupKey = this.groupSlug+'__'+this.field.slug;
                    if(groupKey == globalGroupKey){
                        var tmp = [];

                        if(this.field.isTranslatable){
                            for(let langKey in subFields[groupKey]){
                                var langTmp = [];
                                for(let k in subFields[groupKey][langKey]){
                                    if(k != subFieldGroupIndex){
                                        langTmp.push(subFields[groupKey][langKey][k]);
                                    }
                                }
                                tmp[langKey] = langTmp;
                            }
                        }else{
                            for(let k in subFields[groupKey]){
                                if(k != subFieldGroupIndex){
                                    tmp.push(subFields[groupKey][k]);
                                }
                            }
                        }
                        result[groupKey] = tmp;
                    }else{
                        result[groupKey] = subFields[groupKey];
                    }

                }

                this.subFields = result;
                this.$store.commit("setSubCustomFields", this.subFields);

                // remove values
                var customFieldValues = this.customFieldValues[this.vModel];
                this.customFieldValues[this.vModel] = [];
                var result = [];

                for(let k in customFieldValues){
                    if(k != subFieldGroupIndex){
                        result.push(customFieldValues[k]);
                    }
                }
                this.customFieldValues[this.vModel] = result;
                this.customFieldValuesTmp = result;

                var global = this;
                setTimeout(function () {
                    global.subFieldsActive = true;
                },100);
            },

            populateDBValue(ev, type){
                if(type == "user"){
                    var val = ev.userID;
                }else if(type == "PostType"){
                    var val = ev.ID;
                }

                if(!this.isSubField){
                    if(this.field.isTranslatable){
                        if(this.field.isMultiple){
                            this.customFieldValues[this.vModel][this.lang.slug].push(val);
                        }else{
                            this.customFieldValues[this.vModel][this.lang.slug] = val;
                        }
                    }else{
                        if(this.field.isMultiple){
                            this.customFieldValues[this.vModel].push(val);
                        }else{
                            this.customFieldValues[this.vModel] = val;
                        }
                    }
                }else{
                    if(this.isParentTranslatable){
                        if(this.field.isMultiple){
                            this.customFieldValues[this.vModel][this.subFieldGroupIndex][this.childKey].push(val);
                        }else{
                            this.customFieldValues[this.vModel][this.subFieldGroupIndex][this.childKey] = val;
                        }
                    }else{
                        if(this.field.isMultiple){
                            this.customFieldValues[this.vModel][this.subFieldGroupIndex][this.childKey].push(val);
                        }else{
                            this.customFieldValues[this.vModel][this.subFieldGroupIndex][this.childKey] = val;
                        }
                    }
                }
            },

        },
        computed: {
            basePath(){
                // return if media popup is open (true or false)
                return this.$store.getters.get_base_path;
            },
            mediaSelectedFiles(){
                // return when user chose files form media
                return this.$store.getters.get_media_selected_files;
            },
            isMediaOpen(){
                // return if media popup is open (true or false)
                return this.$store.getters.get_is_media_open;
            },
            mediaOptions(){
                // return the options for opening the media popup
                return this.$store.getters.get_open_media_options;
            },
            getSubFields(){
                return this.$store.getters.get_sub_custom_fields;
            },
            froalaConfig(){
                return this.$store.getters.get_froala_compact_config;
            }
        },
    }
</script>
