export const globalMethods = {
    methods:{
        // use translation method of vuex
        __(key){
            this.$store.dispatch('__', key);
            return this.getTranslation;
        },
        // call checkPermission method of vuex and return his answer
        hasPermission(app,key){
            this.$store.dispatch('checkPermission', {app: app, key: key});
            return this.getHasPermission;
        },
        redirect(name, id, path = '', params = '', query = ''){
            if(id === undefined){
                this.$router.push({ name: name });
            }else{
                this.$router.push({ name: name, params: {id: id}});
            }
        },
        // used to filter where to redirect depending which store btn is clicked
        onStoreBtnClicked(routeNamePrefix, redirectChoice, id){
            if(redirectChoice == 'save'){
                this.redirect(routeNamePrefix+'update',id);
            }else if(redirectChoice == 'close'){
                this.redirect(routeNamePrefix+'list');
            }else if(redirectChoice == 'new'){
                this.redirect(routeNamePrefix+'create');
            }else{
                alert("Some error occurred");
            }
        },
        // this function displays a noty message
        noty(type, layout, message, timeout){
            new Noty({
                type: type,
                layout: 'bottomLeft',
                text: message,
                timeout: timeout,
                closeWith: ['button']
            }).show();
        },
        // repair url with the base
        generateUrl(url){
            return this.baseURL+url;
        },

        /**
         * get the urls for the files
         * @param media
         */
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
        // base url to resources folder
        resourcesUrl(url){
            return this.generateUrl('/public'+url);
        },
        // used to generate a array of plugins panel names for the current view
        getPluginsPanel(app,type){
            let global = this;
            this.pluginsConfigs.map(function(config, key){
                let prefix = config.namespace.replace("/","_");
                let panels = [];
                for(let panelKey in config.panels){
                    let panel = config.panels[panelKey];
                    if((app.indexOf(panelKey) != -1 && panel.placement == type) || (app.indexOf(panelKey) != -1 && panel.placement =='all')){
                        panels.push(prefix+"_"+panel.name);
                        global.pluginsData[prefix+"_"+panel.name] = {};
                    }
                }
                if(panels.length){
                    global.pluginsPanels.push({ name: config.title, panels: panels});
                }
            });
        },
        // toggle the action bar in tables (when listing items)
        toggleListActionBar(index){
            if(this.openedItemActionBar === index){
                this.openedItemActionBar = '';
            }else{
                this.openedItemActionBar = index;
            }
        },
        // this function checks if user has permissions to a specific language
        hasPermissionForLang(langID){
            // if is admin return true
            if(this.getGlobalPermissions.global !== undefined && this.getGlobalPermissions.global.isDefault !== undefined){
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
    },
};
