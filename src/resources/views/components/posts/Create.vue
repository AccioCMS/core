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
            <div class="col-lg-8 col-md-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>{{trans.__createFormTitle}}</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <!-- Loading component -->
                        <spinner v-if="spinner" :width="'40px'" :height="'40px'" :border="'10px'"></spinner>

                        <form class="form-horizontal form-label-left" id="store" v-if="!spinner">
                            <div class="tabBtn">
                                <ul class="nav nav-tabs bar_tabs" v-if="Object.keys(languages).length > 1">
                                    <li role="presentation" class="langTabs" :class="{active: activeLang == lang.slug}"
                                        v-if="hasPermissionForLang(lang.languageID)"
                                        :id="'tabBtn-'+lang.slug"
                                        v-for="(lang, count) in languagesComputed"
                                        :key="count"
                                        :data-lang="lang.slug"
                                        @click="activeLang = lang.slug">

                                        <a :href="'#tab_content'+ ++count" :id="'lang-tab'+count" role="tab" data-toggle="tab" aria-expanded="true">{{ lang.name }}</a>

                                    </li>
                                </ul>

                                <!-- TAB CONTENT -->
                                <div class="tabBody">
                                    <div class="tab-pane fade in"
                                         v-for="(lang, count) in languagesComputed"
                                         :key="count"
                                         v-if="hasPermissionForLang(lang.languageID) && activeLang == lang.slug">

                                        <!-- Title default field -->
                                        <div class="form-group" :id="'form-group-title_'+ lang.slug">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__formTitle}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <input type="text" class="form-control" :id="'title-'+lang.slug" v-model="title[lang.slug]" @change="createSlug(title[lang.slug], count, lang.slug)">
                                                <div class="alert" v-if="StoreResponse.errors['title_'+ lang.slug]" v-for="error in StoreResponse.errors['title_'+ lang.slug]">{{ error }}</div>
                                            </div>
                                        </div>

                                        <div class="form-group" :id="'form-group-slug_'+ lang.slug">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__slug}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <input type="text" class="form-control" :id="'slug_'+lang.slug" v-model="slug[lang.slug]" @dblclick="isSlugDisabled = false" :readonly="isSlugDisabled">
                                                <img :src="resourcesUrl('/images/loading.svg')" class="slugLoading">
                                                <div class="alert" v-if="StoreResponse.errors['slug_'+ lang.slug]" v-for="error in StoreResponse.errors['slug_'+ lang.slug]">{{ error }}</div>
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

                                        <div v-for="(value, key, index) in form" :key="index" class="form-group" :id="'form-group-'+value.slug+'_'+ lang.slug" v-if="hasCategory(value.categories)">
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
                                                    <froala :tag="'textarea'" :config="froalaCompactConfig" v-model="value.value[lang.slug]" v-if="value.translatable" class="froala" :id="'froala-field-'+key+'-'+lang.slug"></froala>
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
                                                        <img :src="constructMediaUrl(file)">
                                                    </div>

                                                    <div class="clearfix"></div>

                                                    <a class="btn btn-info" @click="openMedia('image', value.slug, lang.slug, value.isMultiple, true)" v-if="mediaSelectedFiles[value.slug+'__lang__'+lang.slug]">
                                                        {{trans.__change}}
                                                    </a>

                                                    <a class="btn btn-info" @click="openMedia('image', value.slug, lang.slug, value.isMultiple, false)">
                                                        {{trans.__addImage}}
                                                    </a>

                                                    <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                                </div>

                                                <!-- If it is not translatable -->
                                                <div class="imagePrevContainer col-md-10 col-sm-10 col-xs-12" v-if="!value.translatable">
                                                    <div class="imageSingleThumb" v-for="(file, count) in mediaSelectedFiles[value.slug]" :key="count">
                                                        <i class="fa fa-close closeBtnForPrevImages" @click="deleteSelectedMediaFile(value.slug, file.mediaID)"></i>
                                                        <img :src="constructMediaUrl(file)">
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
                                                        <img :src="documentIconUrl">
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
                                                        <img :src="documentIconUrl">
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
                                                        <i class="fa fa-close closeBtnForPrevImages" style="margin-left: 100px;" @click="deleteSelectedMediaFile(value.slug+'__lang__'+lang.slug, file.mediaID)"></i>
                                                        <img :src="videoIconUrl">
                                                        <p style="word-wrap: break-word; padding:5px;">{{ file.title }}</p>
                                                    </div>

                                                    <div class="clearfix"></div>

                                                    <a class="btn btn-info" @click="openMedia('video', value.slug, lang.slug, value.isMultiple, true)" v-if="mediaSelectedFiles[value.slug] && mediaSelectedFiles[value.slug].length">
                                                        {{trans.__change}}
                                                    </a>
                                                    <a class="btn btn-info" @click="openMedia('video', value.slug, lang.slug, value.isMultiple, false)">{{trans.__addVideo}}</a>

                                                    <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
                                                </div>

                                                <!-- If it is not translatable -->
                                                <div class="videoPrevContainer col-md-10 col-sm-10 col-xs-12" v-if="!value.translatable">
                                                    <div class="videoSingleThumb" v-for="(file, count) in mediaSelectedFiles[value.slug]" :key="count">
                                                        <i class="fa fa-close closeBtnForPrevImages" style="margin-left: 100px;" @click="deleteSelectedMediaFile(value.slug, file.mediaID)"></i>
                                                        <img :src="videoIconUrl">
                                                        <p style="word-wrap: break-word; padding:5px;">{{ file.title }}</p>
                                                    </div>

                                                    <div class="clearfix"></div>

                                                    <a class="btn btn-info" @click="openMedia('video', value.slug, '', value.isMultiple, true)" v-if="mediaSelectedFiles[value.slug] && mediaSelectedFiles[value.slug].length">
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
                                            <div class="col-md-10 col-sm-10 col-xs-12" v-if="value.type.inputType == 'boolean'">
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
                                                    <input type="checkbox" :id="value.slug+'_'+i" v-model="value.value[lang.slug]" :value="option[0]" v-if="value.translatable">
                                                    <input type="checkbox" :id="value.slug+'_'+i" v-model="value.value" :value="option[0]" v-else>
                                                    <label :for="value.slug+'_'+i">{{option[1]}}</label>
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
                                                                track-by="userID"></multiselect>
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
                                                                track-by="userID"></multiselect>
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
                                                    </div>
                                                </div>

                                                <div class="alert" v-if="StoreResponse.errors[value.slug+'_'+lang.slug]" v-for="error in StoreResponse.errors[value.slug+'_'+lang.slug]">{{ error }}</div>
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

                                        <!-- CHANGE AUTHOR -->
                                        <div class="form-group status" :id="'form-group-author_'+ lang.slug" v-if="isAdmin()">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12" for="author">{{trans.__author}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <select name="status" id="author" v-model="createdByUserID" class="form-control">
                                                    <option v-for="user in users" :value="user.userID">{{ user.firstName }} {{ user.lastName }}</option>
                                                </select>
                                                <div class="alert" v-if="StoreResponse.errors['author_'+lang.slug]" v-for="error in StoreResponse.errors['author_'+lang.slug]">{{ error }}</div>
                                            </div>
                                        </div>

                                        <hr>

                                        <!-- CATEGORIES -->
                                        <div class="form-group" :id="'form-group-categories_'+ lang.slug" v-if="hasCategories">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__categoryTitle}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <multiselect
                                                        v-model="selectedCategories"
                                                        :options="categoriesOptions"
                                                        :multiple="true"
                                                        :close-on-select="true"
                                                        :clear-on-select="false"
                                                        :hide-selected="true"
                                                        :placeholder="trans.__multiselectCategoriesPlaceholder"
                                                        label="title"
                                                        track-by="categoryID"></multiselect>
                                                <div class="alert" v-if="StoreResponse.errors['categories']" v-for="error in StoreResponse.errors['categories']">{{ error }}</div>
                                            </div>
                                        </div>

                                        <!-- TAGS -->
                                        <div class="form-group" :id="'form-group-tags_'+ lang.slug" v-if="hasTags">
                                            <label class="control-label col-md-2 col-sm-2 col-xs-12">{{trans.__tagsTitle}}</label>
                                            <div class="col-md-10 col-sm-10 col-xs-12">
                                                <multiselect
                                                        v-model="selectedTags[lang.slug]"
                                                        :options="tagsOptions"
                                                        label="title"
                                                        track-by="title"
                                                        placeholder="Type to search"
                                                        open-direction="bottom"
                                                        :multiple="true"
                                                        :searchable="true"
                                                        :loading="areTagsLoading"
                                                        :internal-search="false"
                                                        :clear-on-select="true"
                                                        :close-on-select="true"
                                                        :show-no-results="false"
                                                        :hide-selected="true"
                                                        :taggable="true"
                                                        @tag="addTag($event, lang.slug)"
                                                        @search-change="searchTagsOptions">
                                                </multiselect>

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
                        <label class="control-label col-md-12 col-sm-12 col-xs-12">{{trans.__publishedAt}}</label>
                        <div class="col-md-12 col-sm-12 col-xs-12 datepickerContainer postsDatepicker">
                            <datepicker v-model="published_at.date" name="date" class="col-md-8 col-sm-8 removePaddingAndMargin" :format="dateFormat"></datepicker>
                            <vue-timepicker v-model="published_at.time" class="col-md-4 col-sm-4"></vue-timepicker>
                        </div>
                    </div>

                    <div class="imageContainer" style="margin-top:15px; margin-left:10px;" id="form-group-featuredImage">
                        <img v-if="mediaSelectedFiles['featuredImage']" :src="constructMediaUrl(mediaSelectedFiles['featuredImage'][0])+'?'+mediaSelectedFiles['featuredImage'][0].updated_at" class="featuredImagePreview">
                        <br>
                        <a class="btn btn-info" v-if="!mediaSelectedFiles['featuredImage']" @click="openMediaForFeatured('featuredImage','image')" id="openMediaChangeFeatureImage">{{trans.__featuredImage}}</a>
                        <a class="btn btn-info" v-if="mediaSelectedFiles['featuredImage']" @click="openMediaForFeatured('featuredImage','image')">{{trans.__change}}</a>
                        <a class="btn btn-danger" v-if="mediaSelectedFiles['featuredImage']" @click="deleteSelectedMediaFile('featuredImage', 0)">{{trans.__remove}}</a>
                    </div>

                    <div class="imageContainer" style="margin-top:15px; margin-left:10px;" id="form-group-featuredVideo" v-if="hasFeaturedVideo">
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

            <!-- POPUP media component -->
            <transition name="slide-fade">
                <popup-media v-if="isMediaOpen"></popup-media>
            </transition>

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

                    <button type="button" class="btn btn-info" id="globalCancel" @click="redirect('post-list', '', '', $route.query)">{{trans.__globalCancelBtn}}</button>
                </div>
            </div>

        </div>
    </div>
</template>
<style scoped>
    .hide{
        display: none !important;
    }
</style>
<script>
    import Datepicker from 'vuejs-datepicker';
    import VueTimepicker from 'vue2-timepicker';
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';
    import { postForm } from '../../mixins/postForm';
    import { customFields } from '../../mixins/customFields';
    import CustomFieldGroup from '../vendor/CustomFieldGroup.vue';

    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated, postForm, customFields],
        components: { Datepicker, VueTimepicker, CustomFieldGroup },
        created() {
            // translations
            this.trans = {
                __createFormTitle: this.__('post.createFormTitle'),
                __title: this.__('post.add'),
                __status: this.__('post.status'),
                __author: this.__('post.author'),
                __published: this.__('post.published'),
                __draft: this.__('post.draft'),
                __pending: this.__('post.pending'),
                __customFieldsTitle: this.__('customFields.title'),
                __formTitle: this.__('base.title'),
                __content: this.__('base.content'),
                __featuredImage: this.__('base.featuredImage'),
                __featuredVideo: this.__('base.featuredVideo'),
                __remove: this.__('base.remove'),
                __addImage: this.__('media.addImage'),
                __change: this.__('media.change'),
                __addFile: this.__('media.addFile'),
                __addVideo: this.__('media.addVideo'),
                __true: this.__('base.booleans.true'),
                __false: this.__('base.booleans.false'),
                __slug: this.__('base.slug'),
                __categoryTitle: this.__('categories.title'),
                __tagsTitle: this.__('tag.title'),
                __visibleIn: this.__('post.visibleIn'),
                __publishedAt: this.__('post.publishedAt'),
                __multiselectCategoriesPlaceholder: this.__('post.multiselectCategoriesPlaceholder'),
                __multiselectTagPlaceholder: this.__('post.multiselectTagPlaceholder'),
                __multiselectAddTagPlaceholder: this.__('post.multiselectAddTagPlaceholder'),
                __globalSaveBtn: this.__('base.saveBtn'),
                __globalSaveAndCloseBtn: this.__('base.saveAndCloseBtn'),
                __globalSaveAndNewBtn: this.__('base.saveAndNewBtn'),
                __globalCancelBtn: this.__('base.cancelBtn'),
                __pluginAppName: this.__('plugin.appName'),
            };

            this.loadCreateInputs();
        },

        data(){
            return{
                pluginsPanels: [],
                categoriesOptions: false,
                selectedCategories: [],
                tagsOptions: [],
                selectedTags: {},
                areTagsLoading: false,
                customFieldsGroups: [],
                childrenFieldsGroups: [],
                customFieldOriginalStructure: {},
                customFieldValues: {},
                columns: '',
                selected: [],
                isSlugDisabled: true,
                form:[],
                title:{},
                content:{},
                slug:{},
                status: {},
                postTypeID: 0,
                hasFeaturedVideo: false,
                hasCategories: false,
                hasTags: false,
                isCategoryRequired: false,
                isTagRequired: false,
                isFeaturedImageRequired: false,
                pluginsData: {},
                languages: '',
                defaultLangSlug: '', // get the default language slug
                dateFormat: 'd MMMM yyyy',
                published_at: {date: '', time: {HH: "",mm: ""}, dateFormatted: ''},
                savedDropdownMenuVisible: false,
                filesToBeIgnored: [],
                users: [],
                createdByUserID: this.createdByUserID,
            }
        },
        methods: {
            /**
             * Used in custom field to hide or show the field when it should or shouldn't displayed in a category
             * @param categories
             * @returns {boolean}
             */
            hasCategory(categories){
                // if it is all
                if(categories === undefined || categories.length == 0 || categories == 0){
                    return true;
                }
                let hasAll = false;
                categories.forEach((cat) => {
                    if(cat['slug'] == 0){
                        hasAll = true;
                        return;
                    }
                });

                if(hasAll){
                    return true;
                }
                // loop throw the selected categories and check if it should displayed in one of them
                for(let k in this.selectedCategories){
                    let slug = this.selectedCategories[k].slug;
                    if(categories.indexOf(slug) !== -1){
                        return true;
                    }
                }
                return false;
            },

            /**
             * store post in the database (makes store ajax request)
             * @param redirectChoice where should user be redirected afet post is added
             */
            store(redirectChoice){
                this.$store.dispatch('openLoading');
                // gets media files of custom fields and writes them to their v-models
                this.constructMediaForCustomFields();

                let dateFormatted = "";
                if(this.published_at.date != ""){
                    let date = this.published_at.date;
                    let month = parseInt(date.getMonth())+1;
                    dateFormatted = date.getDate() + "-" + month + "-" + date.getFullYear();
                }
                this.published_at.dateFormatted = dateFormatted;

                // request for the backend
                let request = {
                    title: this.title,
                    content: this.content,
                    isCategoryRequired: this.isCategoryRequired,
                    isTagRequired: this.isTagRequired,
                    isFeaturedImageRequired: this.isFeaturedImageRequired,
                    formData: this.form,
                    pluginsData: this.pluginsData,
                    customFieldValues: this.customFieldValues,
                    status: this.status,
                    files: this.mediaSelectedFiles,
                    filesToBeIgnored: this.filesToBeIgnored,
                    languages: this.languages,
                    postType: this.$route.params.post_type,
                    slug: this.slug,
                    selectedCategories: this.selectedCategories,
                    selectedTags: this.selectedTags,
                    published_at: this.published_at,
                    redirect: redirectChoice,
                    createdByUserID: this.createdByUserID
                };

                this.$store.dispatch('store',{
                    data: request,
                    url: this.basePath+'/'+this.$route.params.adminPrefix+"/json/posts/store",
                    error: "Post could not be created. Please try again later."
                }).then((resp) => {
                    if(resp.code == 200){
                        this.onStoreBtnClicked('post-',redirectChoice,resp.id);
                        this.loadCreateInputs();
                        this.$emit('emitStore', {
                            on: "create",
                            response: resp
                        });
                    }
                });
            },

            /**
             * Open the media popup with his options
             * @param format: what kind of files are we looking to select
             * @param inputName: for wich field are the files being selected
             * @param langSlug: in wich language
             * @param multiple: does the field require multiple files
             * @param clear: should the previous files be deselected
             */
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
             * Open media just for the feature image
             * @param inputName
             * @param formatType
             */
            openMediaForFeatured(inputName, formatType){
                this.$store.commit('setOpenMediaOptions', { multiple: false, has_multile_files: false, multipleInputs: false, format : formatType, inputName: inputName, langSlug: '', clear: false });
                this.$store.commit('setIsMediaOpen', true);
            },

            /**
             * Used to remove the selected images in custom fields
             * @param key field key
             * @param mediaID
             */
            deleteSelectedMediaFile(key, mediaID){
                let mediaArr = this.mediaSelectedFiles;
                for(let k in mediaArr[key]){
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

            /**
             * Call the createSlug function in vuejs
             * @param title
             * @param index
             * @param langSlug
             */
            createSlug(title, index, langSlug){
                this.$store.dispatch('createSlug', {title: title, url: this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/posts/check-slug/'+this.$route.params.post_type+"/"})
                    .then((response) => {
                        this.slug[langSlug] = response;
                        $("#slug_"+langSlug).val(response);
                    });
            },
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
            mediaOptions(){
                // return the options for opening the media popup
                return this.$store.getters.get_open_media_options;
            },
            languagesComputed() {
                return this.$store.getters.get_languages;
            },
            getSubFields(){
                return this.$store.getters.get_sub_custom_fields;
            }
        },

        watch:{
            // watch for url changes and component doesn't change
            '$route': function(){
                this.loadCreateInputs();
            },
            areTagsLoading: function(newVal, oldVal) { // watch it
                // $(".multiselect__content-wrapper")
                if(newVal){
                    $(".multiselect__content-wrapper").addClass("hide");
                }else {
                    $(".multiselect__content-wrapper").removeClass("hide");
                }
            }
        }
    }
</script>
