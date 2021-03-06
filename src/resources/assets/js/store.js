import Vuex from 'vuex'
import Vue from 'vue'
import Bootstrap from './bootstrap-vuex';
import media from '../../views/components/media/media'
import users  from '../../views/components/users/users'
import tags  from '../../views/components/tags/tags'
import posts  from '../../views/components/posts/posts'
import post_type  from '../../views/components/post_type/post_type'
import permissions  from '../../views/components/permissions/permissions'
import menu  from '../../views/components/menu/menu'
import language  from '../../views/components/language/language'
import custom_fields  from '../../views/components/custom_fields/custom_fields'
import category  from '../../views/components/category/category'

Vue.use(Vuex);

export const store = new Vuex.Store({
    modules: {
        Bootstrap, media, users, tags, posts, post_type, permissions, menu, language, custom_fields, category
    },
    state: {
        id: '',
        spinner: '',
        maxPaginationNr: '',
        list: '',
        inputErrorsExist: false,
        inputErrorsMsg: [],
        postType: '',
        actionReturnedData: {},
        hasPermission: false,
        translation: '',
        storeResponse:{
            errors: []
        }
    },
    getters: {
        get_id(state){
            return state.id;
        },
        get_spinner(state){
            return state.spinner;
        },
        get_maxPaginationNr(state){
            return state.maxPaginationNr;
        },
        get_list(state){
            return state.list;
        },
        get_post_type(state){
            return state.postType;
        },
        get_action_returned_data(state){
            return state.actionReturnedData;
        },
        get_has_permission(state){
            return state.hasPermission;
        },
        get_store_response(state){
            return state.storeResponse;
        }
    },
    mutations: {
        setID(state, id){
            state.id = id;
        },
        setSpinner(state, spinner){
            state.spinner = spinner;
        },
        setMaxPaginationNr(state, maxPaginationNr){
            state.maxPaginationNr = maxPaginationNr;
        },
        setList(state, list){
            state.list = list;
        },
        pushToList(state, obj){
            state.list.push(obj);
        },
        setPostType(state, postType){
            state.postType = postType;
        },
        setActionReturnedData(state, actionReturnedData){
            state.actionReturnedData = actionReturnedData;
        },
        setActionReturnedDataNested(state, actionReturnedDataArr){
            state.actionReturnedData[actionReturnedDataArr[0]] = actionReturnedDataArr[1];
        },
        setHasPermission(state, hasPermission){
            state.hasPermission = hasPermission;
        },
        setStoreResponse(state, storeResponse){
            state.storeResponse = storeResponse;
        }
    },
    actions: {
        /**
         * Set list.
         * @param context
         * @param responseBody
         */
        setList(context, responseBody){
            context.dispatch('filterTranslatedValues', {input: responseBody});
        },

        /**
         * Get data from current language.
         *
         * @param context
         * @param data
         * @returns {Array}
         */
        filterTranslatedValues(context, data){
            let translatedData = []
            let response = []
            let li = 0
            let items = {}

            let input = data.input;
            let languageSlug = data.languageSlug;

            if(languageSlug == null){
                languageSlug = context.getters.get_route.params.lang
            }

            // list response comes with a data key
            if(input.data !== undefined){
                items = input.data;
            }else{
                items = input;
            }

            items.map((item) => {
                // we need an empty object to fill it later
                if(translatedData[li] === undefined){
                    translatedData[li] = {}
                }

                // add attibutes for each item
                for(let key in item){
                    let value = item[key];

                    try{
                        value = JSON.parse(value);
                    }catch(e){}

                    if(typeof value === 'object' && value !== null && value[languageSlug] !== undefined){
                        translatedData[li][key] = value[languageSlug]
                    }else{
                        translatedData[li][key] = value
                    }
                }

                li++;
            });

            if(input.data !== undefined){
                response = input
                response.data = translatedData
            }else{
                response = translatedData;
            }

            context.commit('setList', response);

            return response;
        },

        openLoading() {
            $("#loading").css("display","flex");
            $("#loading").addClass("loadingOpened");
        },
        closeLoading() {
            $("#loading").css("display","none");
            $("#loading").removeClass("loadingOpened");
        },

        // this function is used to handle errors (mostly on ajax requests)
        handleErrors(context, {response}){
            // $(".form-group div .alert").text("");
            // $(".bad").removeClass("bad"); // reset .bad class in inputs

            var returnedMessage = ''; // return message for noty notifications
            var type = ''; // type of notification
            if(response.code == 200){ // if there are no errors
                returnedMessage = response.message;
                type = "success";
            }else if(response.code == 400){
                //context.commit('setInputErrorsExist', true);
                type = "error";
                returnedMessage = response.message;
            }else if(response.code == 500){
                returnedMessage = response.message;
                type = "error";
            }else if(response.code == 403){
                returnedMessage = response.message;
                type = "error";
            }

            // noty notification
            new Noty({
                type: type,
                layout: 'bottomLeft',
                text: returnedMessage,
                timeout: 3000,
                closeWith: ['button']
            }).show();

        },

        // this function is used to store data in database
        store(context, object){
            return Vue.http.post(object.url, object.data)
                .then((resp) => {
                    context.commit('setStoreResponse', resp.body);
                    if(resp.status == 200){
                        var response = resp.body;
                        context.dispatch('handleErrors', {response});
                        context.dispatch('closeLoading');
                    }else{
                        new Noty({
                            type: "error",
                            layout: 'bottomLeft',
                            text: object.error
                        }).show();
                        context.dispatch('closeLoading');
                    }
                    return resp.body;
                }, response =>{
                    // if a error happens
                    context.commit('setSpinner', false);
                    context.dispatch('closeLoading');
                    new Noty({
                        type: "error",
                        layout: 'bottomLeft',
                        text: response.statusText
                    }).show();
                });
        },

        // this function is used to make the order request in php
        sort(context, object){
            var ids = [];
            $("tbody.sortable tr").each(function(i,e){
                var id = $(this).attr("id");
                ids.push(id);
            });

            Vue.http.post(object.url, ids)
                .then((resp) => {
                    if(resp.code == 200){
                        var response = resp.body;
                        context.dispatch('handleErrors', {response});
                    }else{
                        new Noty({
                            type: "error",
                            layout: 'bottomLeft',
                            text: object.error
                        }).show();
                    }

                });
        },

        // this makes the ajax request to generate the slug
        createSlug(context, object){
            if(object.title != ""){
                $(".slugLoading").show();
                return Vue.http.get(object.url+object.title)
                    .then((resp) => {
                        $(".slugLoading").hide();
                        return resp.body;
                    });
            }
        },

        // check if user has permission for a action
        checkPermission(context, object){
            let app = object.app;
            let key = object.key;
            let permissions = context.getters.get_global_data.permissions;
            let postTypes = context.getters.get_global_data.post_type_slugs;
            let appPermission = false;
            let hasSinglePermission = false;


            // handle global permissions (ex. Editor, Author)
            if(permissions.global !== undefined){

                //admin has access into all permissions
                if(permissions.global.admin !== undefined){
                    context.commit('setHasPermission', true);
                    return true;
                }

                //editors and authors have access only in certain apps
                if(permissions.global.editor !== undefined || permissions.global.author !== undefined) {
                    let allowedApps = ['Pages','Category','Tags','Media'];

                    if(allowedApps.indexOf(app) !== -1 || postTypes.indexOf(app) !== -1){
                        appPermission = true;
                    }
                }

                if(permissions.global.author !== undefined){
                    if(key == 'read' || app.startsWith("post_")){
                        hasSinglePermission = true;
                    }
                }else{
                    hasSinglePermission = true;
                }
            }

            // if the user has a particular permission
            if(permissions[app] !== undefined){
                if (permissions[app][key] !== undefined){
                    hasSinglePermission = true;
                }
            }

            //check author
            if(permissions.global !== undefined && permissions.global.author !== undefined){

                if(!appPermission && !hasSinglePermission){
                    context.commit('setHasPermission', false);
                    return false;
                }

                // has a specific permission and has ownership
                if(appPermission || hasSinglePermission){
                    context.commit('setHasPermission', true);
                    return true;
                }
            }else if(permissions.global !== undefined && permissions.global.author !== undefined && permissions.global.editor !== undefined){
                //check editor
                if(appPermission || hasSinglePermission){
                    context.commit('setHasPermission', true);
                    return true;
                }
            }else if (hasSinglePermission){
                //check specific permission
                context.commit('setHasPermission', true);
                return true;
            }

            context.commit('setHasPermission', false);
            return false;
        }
    }
});