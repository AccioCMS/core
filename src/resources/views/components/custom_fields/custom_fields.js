export default {
    state: {
        subFields: [],
    },
    getters: {
        get_sub_custom_fields(state){
            return state.subFields;
        },
    },
    mutations: {
        setSubCustomFields(state, subFields){
            state.subFields = subFields;
        },
        addSubCustomField(state, obj){
            if(obj.lang !== undefined) {
                state.subFields[obj.key][obj.lang].push(obj.value);
            }else{
                state.subFields[obj.key].push(obj.value);
            }
        },
        addSubCustomFieldGroup(state, obj){
            state.subFields[obj.key] = obj.value;
        }
    },
    actions: {}
};