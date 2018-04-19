export default {
    state: {
        menuMode: '',
        menuLinkList: {},
    },
    getters: {
        get_menu_mode(state){
            return state.menuMode;
        },
        get_menu_link_list(state){
            return state.menuLinkList;
        },
    },
    mutations: {
        setMenuMode(state, menuMode){
            state.menuMode = menuMode;
        },
        setMenuLinkList(state, menuLinkList){
            state.menuLinkList = menuLinkList;
        },
        addItemToMenuLinkList(state, item){
            state.menuLinkList[item.key] = item.value;
        }
    },
    actions: {}
};