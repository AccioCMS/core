export default {
    state: {
        auth: {
            user: {},
        },
    },
    getters: {
        get_auth(state){
            return state.auth;
        },
    },
    mutations: {
        setAuth(state, auth){
            state.auth = auth;
        },
    },
    actions: {

    }
};