<template>
    <div class="componentsWs" dusk="postUpdateComponent">
        <!-- TITLE -->
        <div class="page-title">
            <div class="title_left">
                <h3 class="pull-left">{{trans.__title}} <small>{{trans.__listTitle}}</small></h3>
            </div>
        </div>
        <!-- TITLE END -->
        <div class="clearfix"></div>

        <div class="row">
            <div class="col-lg-8 col-md-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>{{trans.__updateFormTitle}}</h2>
                        <!-- RELATED BUTTONS COMPONENT USED IN CMS NAVIGATION -->
                        <related-buttons v-if="$route.query.menu_link_id"></related-buttons>

                        <div class="clearfix"></div>
                    </div>

                    <div class="x_content">

                        <!-- Loading component -->
                        <spinner v-if="spinner" :width="'40px'" :height="'40px'" :border="'10px'"></spinner>

                        <form class="form-horizontal form-label-left" id="store" v-if="!spinner">
                            <div class="" role="tabpanel" data-example-id="togglable-tabs">

                                <ul id="myTab" class="nav nav-tabs bar_tabs" role="tablist" v-if="Object.keys(languages).length > 1">
                                    <li class="langTabs" :class="{active: activeLang == lang.slug}"
                                        v-if="hasPermissionForLang(lang.languageID)"
                                        :id="'tabBtn-'+lang.slug"
                                        v-for="(lang, count) in languagesComputed"
                                        :data-lang="lang.slug"
                                        :key="count"
                                        @click="activeLang = lang.slug">

                                        <a style="cursor:pointer" :id="'lang-tab'+count">{{ lang.name }}</a>

                                    </li>
                                </ul>

                                <!-- TAB CONTENT -->
                                <div class="tabBody">
                                    <div role="tabpanel" class="tab-pane fade in"
                                         v-if="hasPermissionForLang(lang.languageID) && activeLang == lang.slug"
                                         v-for="(lang, count) in languagesComputed" :key="count">

                                        <!-- Title default field -->
                                        <div class="form-group title" :id="'form-group-title_'+ lang.slug">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__formTitle}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <input type="text" class="form-control" :id="'title_'+lang.slug" v-model="title[lang.slug]">
                                                <div class="alert" v-if="StoreResponse.errors['title_'+ lang.slug]" v-for="error in StoreResponse.errors['title_'+ lang.slug]">{{ error }}</div>
                                            </div>

                                        </div>

                                        <div class="form-group visble">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__slug}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <input type="text" class="form-control" v-model="slug[lang.slug]" :id="'slug_'+lang.slug" @dblclick="removeReadonly('slug_'+lang.slug)" readonly>
                                                <img :src="resourcesUrl('/images/loading.svg')" class="slugLoading">
                                                <div class="alert" v-if="StoreResponse.errors['slug_'+ lang.slug]" v-for="error in StoreResponse.errors['slug_'+ lang.slug]">{{ error }}</div>
                                            </div>
                                        </div>


                                        <div class="form-group previewPost">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__prevLink}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <a :href="href" target="_blank" class="urlToFrontend">{{ href }}</a>
                                            </div>
                                        </div>

                                        <!-- Content default field -->
                                        <div class="form-group" :id="'form-group-content_'+ lang.slug">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__content}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12 froala-container">
                                                <froala :tag="'textarea'" :config="froalaCompactConfig" v-model="content[lang.slug]" class="froala" :id="'froala-content-'+lang.slug"></froala>
                                                <div class="alert" v-if="StoreResponse.errors['content_'+ lang.slug]" v-for="error in StoreResponse.errors['content_'+ lang.slug]">{{ error }}</div>
                                            </div>
                                        </div>

                                        <div v-for="(value, key, index) in form" :key="key" class="form-group" :id="'form-group-'+value.slug+'_'+ lang.slug" v-if="hasCategory(value.categories)">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{value.name}}</label>

                                            <!-- Text -->
                                            <div class="col-md-10 col-sm-10 col-xs-12" v-if="value.type.inputType == 'text'">
                                                <input type="text" class="form-control" :id="value.slug" v-model="value.value[lang.slug]" :placeholder="value.placeholder" v-if="value.translatable">
                                                <input type="text" class="form-control" :id="value.slug" v-model="value.value" :placeholder="value.placeholder" v-else>
                                                <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                            </div>

                                            <!-- Textarea -->
                                            <div class="col-md-10 col-sm-10 col-xs-12" v-if="value.type.inputType == 'textarea'">
                                                <textarea class="form-control" :id="value.slug" v-model="value.value[lang.slug]" :placeholder="value.placeholder" v-if="value.translatable"></textarea>
                                                <textarea class="form-control" :id="value.slug" v-model="value.value" :placeholder="value.placeholder" v-else></textarea>
                                                <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                            </div>

                                            <!-- Editor -->
                                            <div class="col-md-10 col-sm-10 col-xs-12" v-if="value.type.inputType == 'editor'">
                                                <div class="editor froala-container">
                                                    <froala :tag="'textarea'" :config="froalaCompactConfig" v-model="value.value[lang.slug]" class="froala" :id="'froala-field-'+key+'-'+lang.slug" v-if="value.translatable"></froala>
                                                    <froala :tag="'textarea'" :config="froalaCompactConfig" v-model="value.value" class="froala" :id="'froala-field-'+key" v-else></froala>
                                                </div>
                                                <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                            </div>

                                            <!-- Email -->
                                            <div class="col-md-10 col-sm-10 col-xs-12" v-if="value.type.inputType == 'email'">
                                                <input type="email" class="form-control" :id="value.slug" v-model="value.value[lang.slug]" :placeholder="value.placeholder" v-if="value.translatable">
                                                <input type="email" class="form-control" :id="value.slug" v-model="value.value" :placeholder="value.placeholder" v-else>
                                                <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                            </div>

                                            <!-- Number -->
                                            <div class="col-md-10 col-sm-10 col-xs-12" v-if="value.type.inputType == 'number'">
                                                <input type="number" class="form-control" :id="value.slug" v-model="value.value[lang.slug]" :placeholder="value.placeholder" v-if="value.translatable">
                                                <input type="number" class="form-control" :id="value.slug" v-model="value.value" :placeholder="value.placeholder" v-else>
                                                <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                            </div>

                                            <!-- Images -->
                                            <div v-if="value.type.inputType == 'image'">
                                                <!-- If it is translatable -->
                                                <div class="imagePrevContainer col-md-10 col-sm-10 col-xs-12" v-if="value.translatable">

                                                    <div class="imageSingleThumb" v-for="(file, count) in mediaSelectedFiles[value.slug+'__lang__'+lang.slug]" :key="count">
                                                        <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile(value.slug+'__lang__'+lang.slug, file.mediaID)"></i>
                                                        <img :src="generateUrl(constructUrl(file))">
                                                    </div>

                                                    <div class="clearfix"></div>

                                                    <a class="btn btn-info" @click="openMedia('image', value.slug, lang.slug, value.isMultiple, true)" v-if="mediaSelectedFiles[value.slug+'__lang__'+lang.slug]">
                                                        {{trans.__change}}
                                                    </a>

                                                    <a class="btn btn-info" @click="openMedia('image', value.slug, lang.slug, value.isMultiple, false)">{{trans.__addImage}}</a>


                                                    <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                                </div>

                                                <!-- If it is not translatable -->
                                                <div class="imagePrevContainer col-md-10 col-sm-10 col-xs-12" v-if="!value.translatable">
                                                    <div class="imageSingleThumb" v-for="(file, count) in mediaSelectedFiles[value.slug]" :key="count">
                                                        <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile(value.slug, file.mediaID)"></i>
                                                        <img :src="generateUrl(constructUrl(file))">
                                                    </div>

                                                    <div class="clearfix"></div>

                                                    <a class="btn btn-info" @click="openMedia('image', value.slug, '', value.isMultiple, true)" v-if="mediaSelectedFiles[value.slug]">
                                                        {{trans.__change}}
                                                    </a>
                                                    <a class="btn btn-info" @click="openMedia('image', value.slug, '', value.isMultiple, false)">{{trans.__addImage}}</a>

                                                    <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                                </div>

                                            </div>

                                            <!-- Files -->
                                            <div v-if="value.type.inputType == 'file'">
                                                <!-- If it is translatable -->
                                                <div class="imagePrevContainer col-md-10 col-sm-10 col-xs-12" v-if="value.translatable">
                                                    <div class="imageSingleThumb" v-for="(file, count) in mediaSelectedFiles[value.slug+'__lang__'+lang.slug]" :key="count">
                                                        <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile(value.slug+'__lang__'+lang.slug, file.mediaID)"></i>
                                                        <img :src="resourcesUrl('/images/document.png')">
                                                    </div>

                                                    <div class="clearfix"></div>

                                                    <a class="btn btn-info" @click="openMedia('document', value.slug, lang.slug, value.isMultiple, true)" v-if="mediaSelectedFiles[value.slug]">
                                                        {{trans.__change}}
                                                    </a>
                                                    <a class="btn btn-info" @click="openMedia('document', value.slug, lang.slug, value.isMultiple, false)">{{trans.__addFile}}</a>

                                                    <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                                </div>

                                                <!-- If it is not translatable -->
                                                <div class="imagePrevContainer col-md-10 col-sm-10 col-xs-12" v-if="!value.translatable">
                                                    <div class="imageSingleThumb" v-for="(file, count) in mediaSelectedFiles[value.slug]" :key="count">
                                                        <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile(value.slug, file.mediaID)"></i>
                                                        <img :src="resourcesUrl('/images/document.png')">
                                                    </div>

                                                    <div class="clearfix"></div>

                                                    <a class="btn btn-info" @click="openMedia('document', value.slug, '', value.isMultiple, true)" v-if="mediaSelectedFiles[value.slug]">
                                                        {{trans.__change}}
                                                    </a>
                                                    <a class="btn btn-info" @click="openMedia('document', value.slug, '', value.isMultiple, false)">{{trans.__addFile}}</a>

                                                    <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>

                                                </div>
                                            </div>

                                            <!-- Video -->
                                            <div v-if="value.type.inputType == 'video'">
                                                <!-- If it is translatable -->
                                                <div class="videoPrevContainer col-md-10 col-sm-10 col-xs-12" v-if="value.translatable">
                                                    <div class="videoSingleThumb" v-for="(file, count) in mediaSelectedFiles[value.slug+'__lang__'+lang.slug]" :key="count">
                                                        <img :src="videoIconUrl">
                                                        <p style="word-wrap: break-word; padding:5px;">{{ file.title }}</p>
                                                    </div>

                                                    <div class="clearfix"></div>

                                                    <a class="btn btn-info" @click="openMedia('video', value.slug, lang.slug, value.isMultiple, true)" v-if="mediaSelectedFiles[value.slug]">
                                                        {{trans.__change}}
                                                    </a>
                                                    <a class="btn btn-info" @click="openMedia('video', value.slug, lang.slug, value.isMultiple, false)">{{trans.__addVideo}}</a>

                                                    <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                                </div>

                                                <!-- If it is not translatable -->
                                                <div class="videoPrevContainer col-md-10 col-sm-10 col-xs-12" v-if="!value.translatable">
                                                    <div class="videoSingleThumb" v-for="(file, count) in mediaSelectedFiles[value.slug]" :key="count">
                                                        <img :src="videoIconUrl">
                                                        <p style="word-wrap: break-word; padding:5px;">{{ file.title }}</p>
                                                    </div>

                                                    <div class="clearfix"></div>

                                                    <a class="btn btn-info" @click="openMedia('video', value.slug, '', value.isMultiple, true)" v-if="mediaSelectedFiles[value.slug]">
                                                        {{trans.__change}}
                                                    </a>
                                                    <a class="btn btn-info" @click="openMedia('video', value.slug, '', value.isMultiple, false)">{{trans.__addVideo}}</a>

                                                    <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                                </div>
                                            </div>

                                            <!-- Date -->
                                            <div class="col-md-10 col-sm-10 col-xs-12" v-if="value.type.inputType == 'date'">
                                                <input type="date" class="form-control" :id="value.slug" v-model="value.value[lang.slug]" :placeholder="value.placeholder" format="dd/MM/yyyy" v-if="value.translatable">
                                                <input type="date" class="form-control" :id="value.slug" v-model="value.value" :placeholder="value.placeholder" format="dd/MM/yyyy" v-else>
                                                <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                            </div>

                                            <!-- Boolean -->
                                            <div class="col-md-6 col-sm-6 col-xs-12" v-if="value.type.inputType == 'boolean'">
                                                <div class="btn-group" data-toggle="buttons" v-if="value.translatable">
                                                    <label class="btn btn-default" :class="{active: value.value[lang.slug]}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="value.value[lang.slug] = true">
                                                        <input type="radio" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                                    </label>
                                                    <label class="btn btn-primary" :class="{active: !value.value[lang.slug]}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="value.value[lang.slug] = false">
                                                        <input type="radio" value="false"> {{trans.__false}}
                                                    </label>
                                                    <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                                </div>

                                                <div class="btn-group" data-toggle="buttons" v-else>
                                                    <label class="btn btn-default" :class="{active: value.value}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="value.value = true">
                                                        <input type="radio" value="true"> &nbsp; {{trans.__true}} &nbsp;
                                                    </label>
                                                    <label class="btn btn-primary" :class="{active: !value.value}" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default" @click="value.value = false">
                                                        <input type="radio" value="false"> {{trans.__false}}
                                                    </label>
                                                    <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                                </div>
                                            </div>

                                            <!-- Checkbox -->
                                            <div class="col-md-10 col-sm-10 col-xs-12" v-if="value.type.inputType == 'checkbox'">
                                                <div v-for="(option, i) in value.multioptionValues" :key="i">
                                                    <input type="checkbox" :id="value.slug" v-model="value.value[lang.slug]" :value="option[0]" v-if="value.translatable">
                                                    <input type="checkbox" :id="value.slug" v-model="value.value" :value="option[0]" v-else>
                                                    {{option[1]}}
                                                </div>
                                                <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                            </div>

                                            <!-- Radio buttons -->
                                            <div class="col-md-10 col-sm-10 col-xs-12" v-if="value.type.inputType == 'radio'">
                                                <div v-for="(option, i) in value.multioptionValues" :key="i">
                                                    <input type="radio" :id="value.slug" v-model="value.value[lang.slug]" :value="option[0]" v-if="value.translatable">
                                                    <input type="radio" :id="value.slug" v-model="value.value" :value="option[0]" v-else>
                                                    {{option[1]}}
                                                </div>
                                                <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                            </div>

                                            <!-- Dropdown -->
                                            <div class="col-md-10 col-sm-10 col-xs-12" v-if="value.type.inputType == 'dropdown'">
                                                <select class="form-control" :id="value.slug" v-model="value.value[lang.slug]" placeholder="Select one" v-if="value.translatable">
                                                    <option v-for="(option, i) in value.multioptionValues" :key="i" :value="option[0]">{{option[1]}}</option>
                                                </select>

                                                <select class="form-control" :id="value.slug" v-model="value.value" placeholder="Select one" v-else>
                                                    <option v-for="(option, i) in value.multioptionValues" :key="i" :value="option[0]">{{option[1]}}</option>
                                                </select>
                                                <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                            </div>


                                            <!--************************************************
                                                Dropdown from DB
                                            *************************************************-->
                                            <div class="col-md-10 col-sm-10 col-xs-12" v-if="value.type.inputType == 'db'">
                                                <!-- If translatable -->
                                                <div  v-if="value.translatable">
                                                    <!-- if belongs to User -->
                                                    <div v-if="value.dbTable.belongsTo == 'User'">
                                                        <multiselect
                                                                v-model="value.value[lang.slug]"
                                                                :options="value.data"
                                                                :multiple="value.isMultiple"
                                                                :close-on-select="true"
                                                                :hide-selected="true"
                                                                label="fullName"
                                                                track-by="userID"
                                                                @open="createFullName(value.data)"></multiselect>
                                                        <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                                    </div>
                                                    <!-- if belongs to Post Type -->
                                                    <div v-if="value.isMultiple && value.dbTable.belongsTo == 'PostType'">
                                                        <multiselect
                                                                v-model="value.value[lang.slug]"
                                                                :options="value.data"
                                                                :multiple="value.isMultiple"
                                                                :close-on-select="true"
                                                                :hide-selected="true"
                                                                label="title"
                                                                track-by="postID"></multiselect>
                                                        <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                                    </div>
                                                </div>

                                                <!-- If not translatable -->
                                                <div v-if="!value.translatable">
                                                    <!-- if belongs to User -->
                                                    <div v-if="value.dbTable.belongsTo == 'User'">
                                                        <multiselect
                                                                v-model="value.value"
                                                                :options="value.data"
                                                                :multiple="value.isMultiple"
                                                                :close-on-select="true"
                                                                :hide-selected="true"
                                                                label="fullName"
                                                                track-by="userID"
                                                                @open="createFullName(value.data)"></multiselect>
                                                        <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                                    </div>
                                                    <!-- if belongs to Post Type -->
                                                    <div v-if="value.dbTable.belongsTo == 'PostType'">
                                                        <multiselect
                                                                v-model="value.value"
                                                                :options="value.data"
                                                                :multiple="value.isMultiple"
                                                                :close-on-select="true"
                                                                :hide-selected="true"
                                                                label="title"
                                                                track-by="postID"></multiselect>
                                                        <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                                    </div>
                                                </div>

                                            </div>
                                            <!--************************************************
                                                END Dropdown from DB
                                            *************************************************-->

                                        </div>

                                        <!-- POST STATUS -->
                                        <div class="form-group status" :id="'form-group-status_'+ lang.slug">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12" for="status">{{trans.__status}} {{lang.name}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <select name="status" id="status" v-model="status[lang.slug]" class="form-control">
                                                    <option value="published">{{trans.__published}}</option>
                                                    <option value="draft">{{trans.__draft}}</option>
                                                    <option value="pending">{{trans.__pending}}</option>
                                                </select>
                                                <div class="alert" v-if="StoreResponse.errors['status_'+lang.slug]" v-for="error in StoreResponse.errors['status_'+lang.slug]">{{ error }}</div>
                                            </div>
                                        </div>

                                        <hr>

                                        <!-- CATEGORIES -->
                                        <div :id="'form-group-categories_'+ lang.slug" class="form-group" v-if="hasCategories">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__categoryTitle}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <multiselect
                                                        v-model="selectedCategories"
                                                        :options="categoriesOptions"
                                                        :multiple="true"
                                                        :close-on-select="false"
                                                        :clear-on-select="false"
                                                        :hide-selected="true"
                                                        :placeholder="trans.__multiselectCategoriesPlaceholder"
                                                        label="title"
                                                        track-by="title"></multiselect>
                                                <div class="alert" v-if="StoreResponse.errors['categories']" v-for="error in StoreResponse.errors['categories']">{{ error }}</div>
                                            </div>
                                        </div>

                                        <!-- TAGS -->
                                        <div class="form-group" :id="'form-group-tags_'+ lang.slug" v-if="hasTags">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__tagsTitle}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <pre>{{ selectedTags }}</pre>
                                                <multiselect
                                                        v-model="selectedTags[lang.slug]"
                                                        :options="tagsOptions"
                                                        :tag-placeholder="trans.__multiselectAddTagPlaceholder"
                                                        :placeholder="trans.__multiselectTagPlaceholder"
                                                        label="title"
                                                        track-by="title"
                                                        :multiple="true"
                                                        :taggable="true"
                                                        @tag="addTag($event, lang.slug)"></multiselect>
                                                <div class="alert" v-if="StoreResponse.errors['tags_'+lang.slug]" v-for="error in StoreResponse.errors['tags_'+lang.slug]">{{ error }}</div>
                                            </div>
                                        </div>

                                        <!-- customFieldsWrapper -->
                                        <div class="customFieldsWrapper col-lg-12 col-md-12 col-xs-12" v-if="customFieldsGroups.length">
                                            <h5 style="margin-top:30px;">{{ trans.__customFieldsTitle }}</h5>
                                            <customFieldGroup
                                                    v-for="(group, index) in customFieldsGroups"
                                                    :group="group"
                                                    :key="index"
                                                    :lang="lang"
                                                    :trans="trans"
                                                    :childrenFieldsGroups="childrenFieldsGroups"
                                                    :customFieldValues="customFieldValues"></customFieldGroup>
                                        </div>
                                        <!-- customFieldsWrapper -->

                                        <!-- pluginsPanelsWrapper -->
                                        <div class="pluginsPanelsWrapper col-lg-12 col-md-12 col-xs-12" v-if="pluginsPanels.length">
                                            <h5 style="margin-top: 30px;">{{ trans.__pluginAppName }}</h5>
                                            <div class="panelContainer" v-for="(plugin, panelIndex) in pluginsPanels" v-if="plugin.panels.length">
                                                <div class="pluginHeader">
                                                    <h4>{{ plugin.name }}</h4>
                                                </div>
                                                <div v-for="(panel, panelIndex) in plugin.panels">
                                                    <component :is="panel" :pluginData="pluginsData[panel]" :plugin="plugin" :panel="panel" :activeLang="activeLang" :languages="languages"></component>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- pluginsPanelsWrapper -->

                                    </div>

                                </div>
                            </div>
                            <br />

                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-12 col-xs-12" v-if="!spinner">
                <div class="x_panel">

                    <!-- Date and time when the post should be displayed -->
                    <div class="form-group dateAndTimepicker">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">{{trans.__schedulePost}}</label>
                        <div class="col-md-12 col-sm-12 col-xs-12 datepickerContainer postsDatepicker">
                            <datepicker v-model="published_at.date" name="date" class="col-md-8 col-sm-8 removePaddingAndMargin" :format="dateFormat"></datepicker>
                            <vue-timepicker v-model="published_at.time" class="col-md-4 col-sm-4"></vue-timepicker>
                        </div>
                    </div>

                    <div class="imageContainer" style="margin-top:15px; margin-left:10px;" id="form-group-featuredImage">
                        <img v-if="mediaSelectedFiles['featuredImage']" :src="generateUrl(constructUrl(mediaSelectedFiles['featuredImage'][0]))" class="featuredImagePreview">
                        <br>
                        <a class="btn btn-info" v-if="!mediaSelectedFiles['featuredImage']" @click="openMediaForFeatured('featuredImage','image')" id="openMediaChangeFeatureImage">{{trans.__featuredImage}}</a>
                        <a class="btn btn-info" v-if="mediaSelectedFiles['featuredImage']" @click="openMediaForFeatured('featuredImage','image')">{{trans.__change}}</a>
                        <a class="btn btn-danger" v-if="mediaSelectedFiles['featuredImage']" @click="deleteSelectedMediaFile('featuredImage', 0)">{{trans.__remove}}</a>
                    </div>

                    <div class="imageContainer" style="margin-top:15px; margin-left:10px;" id="form-group-featuredVideo">
                        <template v-if="mediaSelectedFiles['featuredVideo']">
                            <img :src="videoIconUrl" class="featuredImagePreview">
                            <p style="max-width:200px; word-wrap: break-word;">{{ mediaSelectedFiles['featuredVideo'][0].filename }}</p>
                        </template>
                        <br>
                        <a class="btn btn-info" v-if="!mediaSelectedFiles['featuredVideo']" @click="openMediaForFeatured('featuredVideo','video')" id="openMediaChangeFeatureVideo">{{trans.__featuredVideo}}</a>
                        <a class="btn btn-info" v-if="mediaSelectedFiles['featuredVideo']" @click="openMediaForFeatured('featuredVideo','video')">{{trans.__change}}</a>
                        <a class="btn btn-danger" v-if="mediaSelectedFiles['featuredVideo']" @click="deleteSelectedMediaFile('featuredVideo', 0)">{{trans.__remove}}</a>
                    </div>

                    <div class="form-group" style="clear: both; padding-top: 10px;">
                        <div class="alert" v-if="StoreResponse.errors.files_featuredImage" v-for="error in StoreResponse.errors.files_featuredImage">{{ error }}</div>
                    </div>

                </div>

            </div>

            <!-- POPUP media / the component of the popup media -->
            <transition name="slide-fade">
                <popup-media v-if="isMediaOpen"></popup-media>
            </transition>

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

                    <button type="button" class="btn btn-info" id="globalCancel" @click="redirect('post-list')">{{trans.__globalCancelBtn}}</button>
                </div>
            </div>

        </div>
    </div>
</template>
<style scoped>
    .urlToFrontend{
        display: block;
        color: #5497ff;
        font-size: 14px;
        background-color: #fbfbfb;
        padding: 10px;
    }
</style>
<script>
    import Datepicker from 'vuejs-datepicker';
    import VueTimepicker from 'vue2-timepicker';
    import PopupMedia from '../media/Popup.vue';
    import RelatedButtons from '../menu/RelatedButtons.vue';
    import CustomFieldGroup from '../vendor/CustomFieldGroup.vue';
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';
    import { customFields } from '../../mixins/customFields';
    import { postForm } from '../../mixins/postForm';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated, postForm, customFields],
        components: { 'popup-media':PopupMedia, Datepicker, VueTimepicker, 'related-buttons':RelatedButtons, CustomFieldGroup },
        created() {
            // translations
            this.trans = {
                __title: this.__('post.edit'),
                __formTitle: this.__('base.title'),
                __updateFormTitle: this.__('post.updateFormTitle'),
                __status: this.__('post.status'),
                __published: this.__('post.published'),
                __draft: this.__('post.draft'),
                __pending: this.__('post.pending'),
                __customFieldsTitle: this.__('customFields.title'),
                __content: this.__('base.content'),
                __remove: this.__('base.remove'),
                __featuredImage: this.__('base.featuredImage'),
                __featuredVideo: this.__('base.featuredVideo'),
                __addImage: this.__('media.addImage'),
                __change: this.__('media.change'),
                __addFile: this.__('media.addFile'),
                __addVideo: this.__('media.addVideo'),
                __true: this.__('base.booleans.true'),
                __false: this.__('base.booleans.false'),
                __slug: this.__('base.slug'),
                __prevLink: this.__('post.prevLink'),
                __categoryTitle: this.__('categories.title'),
                __tagsTitle: this.__('tags.title'),
                __visibleIn: this.__('post.visibleIn'),
                __schedulePost: this.__('post.schedulePost'),
                __multiselectCategoriesPlaceholder: this.__('post.multiselectCategoriesPlaceholder'),
                __multiselectTagPlaceholder: this.__('post.multiselectTagPlaceholder'),
                __multiselectAddTagPlaceholder: this.__('post.multiselectAddTagPlaceholder'),
                __globalUpdateBtn: this.__('base.updateBtn'),
                __globalUpdateAndCloseBtn: this.__('base.updateAndCloseBtn'),
                __globalUpdateAndNewBtn: this.__('base.updateAndNewBtn'),
                __globalCancelBtn: this.__('base.cancelBtn'),
                __pluginAppName: this.__('media.appName'),
            };
        },
        mounted() {
            this.loadUpdateInputs();
            // get plugin panels
            this.getPluginsPanel(['post', this.$route.params.post_type], 'update');
        },
        data(){
            return{
                activeLang: '',
                pluginsPanels: [],
                columns: '',
                categoriesOptions: false,
                selectedCategories: [],
                tagsOptions: [],
                selectedTags: [],
                customFieldOriginalStructure: [],
                customFieldsGroups: [],
                childrenFieldsGroups: [],
                columnSlugs: '',
                selected: [],
                form:[],
                title: {},
                content:{},
                slug:{},
                href:'',
                status: {},
                customFieldValues: {},
                languages: '',
                defaultLangSlug: '', // get the default language slug
                activeLangsSlug: "",
                dateFormat: 'd MMMM yyyy',
                published_at: {date: '', time: {HH: "",mm: ""}, dateFormatted: ''},
                createdByUserID: 0,
                savedDropdownMenuVisible: false,
                filesToBeIgnored: [],
                pluginsData: {},
                mountedPlugins: [],
            }
        },
        methods: {
            // this function checks if user has permissions to a specific language
            hasPermissionForLang(langID){
                // if is admin return true
                if(this.getGlobalPermissions.global !== undefined && this.getGlobalPermissions.global.admin !== undefined){
                    return true;
                }
                // check language permission if user is not admin
                if(this.getGlobalPermissions.Language !== undefined && this.getGlobalPermissions.Language.id){
                    let allowedLanguageIDs = this.getGlobalPermissions.Language.id.value;
                    if(allowedLanguageIDs.indexOf(langID) === -1){
                        return false;
                    }
                }
                return true;
            },
            // adding new tag
            addTag (title, languageSlug) {
                const tag = {
                    tagID: 0,
                    title: title,
                    description: "",
                    slug: "",
                }
                this.tagsOptions.push(tag);
                let selectedTagsInLang = [];
                for(let k in this.selectedTags[languageSlug]){
                    selectedTagsInLang.push(this.selectedTags[languageSlug][k]);
                }
                selectedTagsInLang.push(tag);
                this.selectedTags[languageSlug] = selectedTagsInLang;
            },
            // @returns custom field arrays
            // used to construct custom fields values of the input type 'db'
            populateValuesFromData(formData){
                var result = [];
                for(let k in formData){
                    // if custom field type is "Dropdown from DB"
                    if(formData[k].type.inputType == 'db'){

                        if(formData[k].value === undefined){
                            formData[k].value = "";
                            result.push(formData[k]);
                            continue;
                        }

                        // primary key name
                        if(formData[k].dbTable.belongsTo == "User"){
                            var id = "userID";
                        }else if(formData[k].dbTable.belongsTo == "PostType"){
                            var id = "postID";
                        }

                        // if custom field is translatable
                        if(formData[k].translatable){
                            var value = {};
                            // loop throw the data options of the field
                            for(let dataKey in formData[k].data){
                                // array of objects for each language if it is a multi options custom field
                                if(formData[k].isMultiple){
                                    for(let langKey in formData[k].value){
                                        if(value[langKey] === undefined && formData[k].value[langKey] != ""){
                                            value[langKey] = [];
                                        }
                                        for(let idKey in formData[k].value[langKey]){
                                            if(parseInt(formData[k].data[dataKey][id]) == parseInt(formData[k].value[langKey][idKey])){
                                                value[langKey][idKey.replace("k_", "")] = formData[k].data[dataKey];
                                            }
                                        }
                                    }
                                }else{
                                    // a object for each language if it is not a multi options custom field
                                    for(let langKey in formData[k].value){
                                        if(parseInt(formData[k].data[dataKey][id]) == parseInt(formData[k].value[langKey])){
                                            value[langKey] = formData[k].data[dataKey];
                                        }
                                    }
                                }
                            }
                            if(Object.keys(value).length){
                                formData[k].value = value;
                            }
                        }else{
                        // if custom field is not translatable
                            var value = [];
                            for(let dataKey in formData[k].data){
                                // array of object if custom field is multi options
                                if(formData[k].isMultiple){
                                    if(formData[k].value !== undefined){
                                        var formDataValue = JSON.parse(formData[k].value);
                                        var count = 0;
                                        for(let key in formDataValue){
                                            if(parseInt(formData[k].data[dataKey][id]) == parseInt(formDataValue[key])){
                                                value[count] = formData[k].data[dataKey];
                                                count++;
                                            }
                                        }
                                    }else{
                                        value = null;
                                    }
                                }else{
                                    // a object if custom field is not multi options
                                    if(parseInt(formData[k].data[dataKey][id]) == parseInt(formData[k].value)){
                                        value = formData[k].data[dataKey];
                                    }
                                }
                            }
                            if(Object.keys(value).length){
                                formData[k].value = value;
                            }
                        }
                        result.push(formData[k]);
                    }else{
                        result.push(formData[k]);
                    }
                }
                return result;
            },
            resetForm(){
                $("form input, form textarea").each(function(){
                    $(this).val("");
                });
            },
            // used in custom field to hide or show the field when it should or shouldn't displayed in a category
            hasCategory(categories){
                // if it is all
                if(Object.keys(categories).length === 0){
                    return true;
                }

                for(let k in categories){
                    if(categories[k].slug == 0){
                        return true;
                    }
                }

                for(let k in categories){
                    for(let sKey in this.selectedCategories){
                        if(categories[k].slug == this.selectedCategories[sKey].slug){
                            return true;
                        }
                    }
                }
                return false;
            },
            // store post in the database
            store(redirectChoice){
                this.$store.dispatch('openLoading');

                // gets media files of custom fields and writes them to their v-models
                this.constructMediaForCustomFields();

                var dateFormatted = "";
                if(this.published_at.date != ""){
                    var date = this.published_at.date;
                    var month = parseInt(date.getMonth())+1;
                    dateFormatted = date.getDate() + "-" + month + "-" + date.getFullYear();
                }
                this.published_at.dateFormatted = dateFormatted;
                var request = {
                    formData: this.form,
                    pluginsData: this.pluginsData,
                    customFieldValues: this.customFieldValues,
                    status: this.status,
                    files: this.mediaSelectedFiles,
                    filesToBeIgnored: this.filesToBeIgnored,
                    languages: this.languages,
                    postType: this.$route.params.post_type,
                    postID : this.getID,
                    title: this.title,
                    content: this.content,
                    slug: this.slug,
                    selectedCategories: this.selectedCategories,
                    selectedTags: this.selectedTags,
                    isCategoryRequired: this.isCategoryRequired,
                    isTagRequired: this.isTagRequired,
                    isFeaturedImageRequired: this.isFeaturedImageRequired,
                    published_at: this.published_at,
                    createdByUserID: this.createdByUserID,
                    redirect: redirectChoice,
                };

                this.$store.dispatch('store',{
                    data: request,
                    url: this.basePath+'/'+this.$route.params.adminPrefix+"/json/posts/store",
                    error: "Post could not be created. Please try again later."
                }).then((resp) => {
                    if(resp.code == 200){
                        this.onStoreBtnClicked('post-',redirectChoice);

                        // Fire noty
                        if(resp.noty){
                            for(let k in resp.noty){
                                if(resp.noty[k]['type'] == 'error'){
                                    this.noty(resp.noty[k]['type'],'bottomLeft',resp.noty[k]['message']);
                                }else{
                                    this.noty(resp.noty[k]['type'],'bottomLeft',resp.noty[k]['message'],3000);
                                }
                            }
                        }

                        // Fire plugin events
                        for(let k in this.mountedPlugins){
                            let pluginInstance = this.mountedPlugins[k];
                            pluginInstance.$emit('onUpdated', resp);
                        }
                    }
                });
            },
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
            openMediaForFeatured(inputName, formatType){
                this.$store.commit('setOpenMediaOptions', { multiple: false, has_multile_files: false, multipleInputs: false, format : formatType, inputName: inputName, langSlug: '', clear: false });
                this.$store.commit('setIsMediaOpen', true);
            },
            // repair url to get the thumb
            constructUrl(image){
                var url = "";
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
            // remove feature image
            removeMediaFiles(){
                this.$store.commit('setMediaSelectedFiles', "");
            },
            changeDefault(option){
                this.form.isDefault = option;
            },
            change(option, index){
                this.form[index].value = option;
            },
            storeValue(event, index, lang){
                var value = event.target.value;
                this.form[index].value[lang] = value;
            },
            // this function activated when languages dropdown is changed
            languagesDropdownChanged(event){
                this.activeLangsSlug = event.target.value;
            },
            // this function is used to remove the selected images in custom fields
            deleteSelectedMediaFile(key, mediaID){
                var mediaArr = this.mediaSelectedFiles;
                for(var k in mediaArr[key]){
                    if(key == "featuredImage" || key == "featuredVideo"){
                        delete mediaArr[key];
                        continue;
                    }
                    if(mediaArr[key][k].mediaID == mediaID){
                        mediaArr[key].splice(mediaArr[key][k], 1);
                    }
                }
                this.$store.commit('setMediaSelectedFiles', "");
                this.$store.commit('setMediaSelectedFiles', mediaArr);
            },
            removeReadonly(id){
                $("#"+id).attr("readonly",false);
            },
            // get user options for input type dropdown from db and create full name as a new key
            createFullName(data){
                let tmp = [];
                for(let k in data){
                    data[k].fullName = data[k].firstName+" "+ data[k].lastName;
                    tmp.push(data[k]);
                }
                this.userOptionsFromDBDropdown = tmp;
            }
        },
        computed: {
            isMediaOpen(){
                // return if media popup is open (true or false)
                return this.$store.getters.get_is_media_open;
            },
            mediaSelectedFiles(){
                // return when user chose files form media
                return this.$store.getters.get_media_selected_files;
            },
            languagesComputed() {
                return this.$store.getters.get_languages;
            },
            getActionReturnedData(){
                // return if we are in the advanced search
                return this.$store.getters.get_action_returned_data;
            },
            froalaConfig(){
                return this.$store.getters.get_froala_full_config;
            },
        },

        watch:{
            // watch for url changes and component doesn't change
            '$route': function(){
                this.loadUpdateInputs();
            }
        }
    }
</script>
