/**
 * *****************************************
 *  BOOTSTRAP VUEX MODULE
 *  ****************************************
 *  Basic states that vuex must have
 *  NOTE: plugin won't work without them
 */

export default {
    state: {
        baseURL: '',
        basePath: '',
        global_data: [],
        pluginsConfigs: [],
        labels: {},
        openModule: '',
        languages: {},
        auth: {
            user: {},
        },
        menuMode: '',
        menuLinkList: {},
        logout_link: '',
        navigationMenuStateIsMobile: false,
        translation: '',
    },
    getters: {
        get_base_url(state){
            return state.base_url;
        },
        get_base_path(state){
            return state.base_path;
        },
        get_global_data(state){
            return state.global_data;
        },
        get_plugins_configs(state){
            return state.pluginsConfigs;
        },
        get_labels(state){
            return state.labels;
        },
        get_auth(state){
            return state.auth;
        },
        get_menu_mode(state){
            return state.menuMode;
        },
        get_languages(state){
            return state.languages;
        },
        get_menu_link_list(state){
            return state.menuLinkList;
        },
        get_open_module(state){
            return state.openModule;
        },
        get_logout_link(state){
            return state.logout_link;
        },
        get_navigation_menu_state_is_mobile(state){
            return state.navigationMenuStateIsMobile;
        },
        get_translation(state){
            return state.translation;
        }
    },
    mutations: {
        setLabels(state, labels){
            state.labels = labels;
        },
        setGlobalData(state, global_data){
            state.global_data = global_data;
        },
        setPluginsConfigs(state, pluginsConfigs){
            state.pluginsConfigs = pluginsConfigs;
        },
        setBaseURL(state, base_url){
            state.base_url = base_url;
        },
        setBasePath(state, base_path){
            state.base_path = base_path;
        },
        setAuth(state, auth){
            state.auth = auth;
        },
        setMenuMode(state, menuMode){
            state.menuMode = menuMode;
        },
        setLanguages(state, languages){
            state.languages = languages;
        },
        setMenuLinkList(state, menuLinkList){
            state.menuLinkList = menuLinkList;
        },
        addItemToMenuLinkList(state, item){
            state.menuLinkList[item.key] = item.value;
        },
        setOpenModule(state, openModule){
            state.openModule = openModule;
        },
        setLogoutLink(state,logoutLink){
            state.logout_link = logoutLink;
        },
        setNavigationMenuStateIsMobile(state, navigationMenuStateIsMobile){
            state.navigationMenuStateIsMobile = navigationMenuStateIsMobile;
        },
        setTranslation(state, translation){
            state.translation = translation;
        }
    },
    actions: {
        // used to get a label in the current language
        __(context, request){
            request = request.replace('/','.');
            let transPathArr = request.split('.');
            let allLabels = context.getters.get_labels;
            let isOK = true;
            let translationGroupName = request.split('::');

            // Handle group translations
            if(request.indexOf('::') !== -1){
                let groupName = translationGroupName[0];
                let labelKey = request.replace(groupName+'::','');
                let transPathArr = labelKey.split('.');
                let groupLabels = allLabels[groupName];

                for(let key in transPathArr){
                    if (groupLabels[transPathArr[key]] === undefined) {
                        isOK = false;
                        context.commit('setTranslation', "* " + request);
                        break;
                    }
                    groupLabels = groupLabels[transPathArr[key]];
                }
                allLabels = groupLabels;
            }else {
                for (let key in transPathArr) {
                    if (allLabels[transPathArr[key]] === undefined) {
                        isOK = false;
                        context.commit('setTranslation', "* " + request);
                        break;
                    }
                    allLabels = allLabels[transPathArr[key]];
                }
            }
            if (isOK){
                context.commit('setTranslation', allLabels);
            }
        }
    }
};