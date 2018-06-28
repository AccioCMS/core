/**
 * NOTE : To use these function (or this mixin) you need to implement the following vars in the data().. method of the component :
 *      customFieldsGroups: [],
 *      childrenFieldsGroups: [],
 *      customFieldOriginalStructure: {},
 *      customFieldValues: {},
 *
 * @type {{methods: {loadCustomFields(*, *): void, constructMediaForCustomFields(): void, pupulateCustomFieldsValues(*): void}}}
 */
export const customFields = {
    methods:{
        // load the data of custom fields
        loadCustomFields(customFieldsGroups, type){
            let customFieldGroupFinal = [];
            // loop throw custom field group
            for(let groupKey in customFieldsGroups){
                let groupSlug = customFieldsGroups[groupKey].slug;

                let tmpFields = [];
                for(let fieldKey in customFieldsGroups[groupKey].fields){
                    let field = customFieldsGroups[groupKey].fields[fieldKey];

                    if(!field.parentID){
                        tmpFields.push(field);
                        // make original fields structure with children
                        this.customFieldOriginalStructure[groupSlug+'__'+field.slug] = field;
                        this.customFieldOriginalStructure[groupSlug+'__'+field.slug].subFields = [];

                        if(field.isTranslatable){
                            this.customFieldValues[groupSlug+'__'+field.slug] = {};
                            let tmpLangKeys = {};
                            for(let langKey in this.languages){
                                this.customFieldValues[groupSlug+'__'+field.slug][this.languages[langKey].slug] = [];
                                tmpLangKeys[this.languages[langKey].slug] = [];
                            }

                            // populate sub field keys for each repeater
                            if(field.type == 'repeater'){
                                this.$store.commit('addSubCustomFieldGroup', {key: groupSlug+'__'+field.slug, value: tmpLangKeys});
                            }

                        }else{
                            this.customFieldValues[groupSlug+'__'+field.slug] = [];

                            // populate sub field keys for each repeater
                            if(field.type == 'repeater'){
                                this.$store.commit('addSubCustomFieldGroup', {key: groupSlug+'__'+field.slug, value: []});
                            }
                        }
                    }else{
                        if(this.childrenFieldsGroups[field.parentID] === undefined){
                            this.childrenFieldsGroups[field.parentID] = [];
                        }
                        this.childrenFieldsGroups[field.parentID].push(field);

                        // make original fields structure with children
                        for(let fKey in this.customFieldOriginalStructure){
                            if(this.customFieldOriginalStructure[fKey].customFieldID == field.parentID){
                                this.customFieldOriginalStructure[groupSlug+'__'+this.customFieldOriginalStructure[fKey].slug].subFields[groupSlug+'__'+field.slug] = field;
                            }
                        }
                    }
                }
                customFieldsGroups[groupKey].fields = tmpFields;
                customFieldGroupFinal.push(customFieldsGroups[groupKey]);
            }
            this.customFieldsGroups = customFieldGroupFinal;
        },

        // get media files from VUEX and store them to the customFieldsValues variable
        constructMediaForCustomFields(){
            this.filesToBeIgnored = [];
            for(let k in this.mediaSelectedFiles){
                // if media file is custom field
                if(this.customFieldValues[k] !== undefined){
                    this.customFieldValues[k] = [];
                    for(let mediaKey in this.mediaSelectedFiles[k]){
                        if(this.customFieldValues[k].indexOf(this.mediaSelectedFiles[k][mediaKey].mediaID) == -1){
                            this.customFieldValues[k].push(this.mediaSelectedFiles[k][mediaKey].mediaID);
                            this.filesToBeIgnored.push(k);
                        }
                    }
                }else{
                    let keysArray = k.split("___");
                    /* if non translatable sub fields */
                    if(keysArray.length == 3){
                        // check if keys structure is ok
                        if(this.customFieldValues[keysArray[0]] !== undefined
                            && this.customFieldValues[keysArray[0]][keysArray[1]] !== undefined
                            && this.customFieldValues[keysArray[0]][keysArray[1]][keysArray[2]] !== undefined
                        ){
                            // insert media IDs in sub custom field values
                            this.customFieldValues[keysArray[0]][keysArray[1]][keysArray[2]] = [];
                            for(let mediaKey in this.mediaSelectedFiles[k]){
                                if(this.customFieldOriginalStructure[keysArray[0]].subFields[keysArray[2]].isMultiple){
                                    this.customFieldValues[keysArray[0]][keysArray[1]][keysArray[2]].push(this.mediaSelectedFiles[k][mediaKey].mediaID);
                                }else{
                                    this.customFieldValues[keysArray[0]][keysArray[1]][keysArray[2]] = this.mediaSelectedFiles[k][mediaKey].mediaID;
                                }
                            }
                            this.filesToBeIgnored.push(k);
                        }
                        // if translatable sub fields
                    }else if(keysArray.length == 5){
                        // check if keys structure is ok
                        if(this.customFieldValues[keysArray[0]] !== undefined
                            && this.customFieldValues[keysArray[0]][keysArray[4]] !== undefined
                            && this.customFieldValues[keysArray[0]][keysArray[4]][keysArray[1]] !== undefined
                            && this.customFieldValues[keysArray[0]][keysArray[4]][keysArray[1]][keysArray[2]] !== undefined
                        ){
                            // insert media IDs in sub custom field values
                            this.customFieldValues[keysArray[0]][keysArray[4]][keysArray[1]][keysArray[2]] = [];
                            for(let mediaKey in this.mediaSelectedFiles[k]){
                                if(this.customFieldOriginalStructure[keysArray[0]].subFields[keysArray[2]].isMultiple){
                                    this.customFieldValues[keysArray[0]][keysArray[4]][keysArray[1]][keysArray[2]].push(this.mediaSelectedFiles[k][mediaKey].mediaID);
                                }else{
                                    this.customFieldValues[keysArray[0]][keysArray[4]][keysArray[1]][keysArray[2]] = this.mediaSelectedFiles[k][mediaKey].mediaID;
                                }
                            }
                            this.filesToBeIgnored.push(k);
                        }
                    }else{
                        /* FOR TRANSLATABLE MEDIA CUSTOM FIELDS */
                        let keysArray = k.split("__lang__");
                        if(keysArray.length == 2){
                            if(this.customFieldValues[keysArray[0]] !== undefined
                                && this.customFieldValues[keysArray[0]][keysArray[1]] !== undefined
                            ){
                                this.customFieldValues[keysArray[0]][keysArray[1]] = [];
                                for(let mediaKey in this.mediaSelectedFiles[k]){
                                    if(this.customFieldOriginalStructure[keysArray[0]].isMultiple){
                                        this.customFieldValues[keysArray[0]][keysArray[1]].push(this.mediaSelectedFiles[k][mediaKey].mediaID);
                                    }else{
                                        this.customFieldValues[keysArray[0]][keysArray[1]] = this.mediaSelectedFiles[k][mediaKey].mediaID;
                                    }
                                }
                                this.filesToBeIgnored.push(k);
                            }
                        }
                    }
                }
            }
        },

        // Populates the custom field values (Used in updates)
        pupulateCustomFieldsValues(customFieldsValues){
            let customFieldsValuesTmp = customFieldsValues;
            for(let k in this.customFieldValues){
                if(customFieldsValuesTmp[k] !== undefined){
                    this.customFieldValues[k] = customFieldsValuesTmp[k];
                }
            }
            // populate sub custom field in vuex (store.js)
            for(let groupKey in this.customFieldsGroups){
                for(let fieldKey in this.customFieldsGroups[groupKey].fields){
                    let field = this.customFieldsGroups[groupKey].fields[fieldKey];
                    let groupSlug = this.customFieldsGroups[groupKey].slug;

                    if(this.childrenFieldsGroups[field.customFieldID] !== undefined){
                        let fields = this.childrenFieldsGroups[field.customFieldID];
                        if(field.isTranslatable){
                            for(let langKey in this.languages){
                                if(Object.keys(this.customFieldValues[groupSlug+'__'+field.slug][langKey]).length > 0){
                                    this.$store.commit("addSubCustomField", {key: groupSlug+'__'+field.slug, value: fields, lang: langKey});
                                }
                            }
                        }else{
                            for(let k in this.customFieldValues[groupSlug+'__'+field.slug]){
                                this.$store.commit("addSubCustomField", {key: groupSlug+'__'+field.slug, value: fields});
                            }
                        }
                    }
                }
            }
        }

    }
};