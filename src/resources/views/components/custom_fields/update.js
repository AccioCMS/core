export const update = {
    methods: {
        fillFormOnUpdate(){
            if(this.$route.params.id !== undefined){
                this.isInUpdate = true;
                // get table names for custom fields ( table names that can be used to make the values of a dropdown field )
                this.updatePromise = this.$http.get(this.basePath+'/'+this.getAdminPrefix+'/'+this.getCurrentLang+'/json/custom-fields/details/'+this.$route.params.id)
                    .then((resp) => {
                        this.form.id = resp.body.customFieldGroupID;
                        this.form.title = resp.body.title;
                        this.form.slug = resp.body.slug;
                        this.form.description = resp.body.description;
                        this.form.isActive = resp.body.isActive;
                        this.form.groupRules = resp.body.conditions;

                        var fields = resp.body.fields;
                        for(let k in fields){
                            var type = "";
                            for(let optKey in this.options){
                                if(this.options[optKey].inputType == fields[k].type){
                                    type = this.options[optKey];
                                    break;
                                }
                            }
                            // if type is repeater
                            if(fields[k].type == "repeater"){
                                type = { inputType: 'repeater', typeName: 'Repeater'};
                            }
                            // if type is missing open a alert error
                            if(type == ""){
                                alert("Type missing");
                            }
                            // rules / conditions
                            var rules = [];
                            if(fields[k].conditions != null){
                                rules = fields[k].conditions;
                            }

                            this.form.fields.push({
                                id: fields[k].customFieldID,
                                parent: fields[k].parentID,
                                label: fields[k].label,
                                order: fields[k].order,
                                slug: fields[k].slug,
                                placeholder: fields[k].placeholder,
                                defaultValue: fields[k].defaultValue,
                                note: fields[k].note,
                                multioptionValues: fields[k].optionsValues.string,
                                isTranslatable: fields[k].isTranslatable,
                                isRequired: fields[k].isRequired,
                                isActive: fields[k].isActive,
                                properties: fields[k].properties,
                                isMultiple: fields[k].isMultiple,
                                layout: fields[k].layout,
                                type: type,
                                wrapperStyle: fields[k].wrapperStyle,
                                fieldStyle: fields[k].fieldStyle,
                                rules: rules,
                            });
                        }
                    });
            }
        }
    }
};