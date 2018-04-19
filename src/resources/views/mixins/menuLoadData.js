export const menuLoadData = {
    methods:{
        // change the selected menu
        loadMenuData(){
            var globalThis = this;
            this.$store.commit('setSpinner', true);
            // get all menus from DB and set the selected one by using the ID parameter in link
            var menuPromise = this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/menu/get-all')
                .then((resp) => {
                    this.menuList = resp.body.data;
                    this.menuListLength = this.menuList.length;
                    for(var k in this.menuList){
                        if(this.menuList[k].menuID == this.$route.params.id){
                            this.selectedMenu = this.menuList[k];
                            this.selectedMenuID = this.menuList[k].menuID;
                            break;
                        }
                    }
                }, response => {
                    // if a error happens
                    this.$store.commit('setSpinner', false);
                    new Noty({
                        type: "error",
                        layout: 'bottomLeft',
                        text: response.statusText
                    }).show();
                });

            // get all details of the selected menu (Menu links of the selected menu)
            var menuLinksPromise = this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/json/menu/details/'+this.$route.params.id)
                .then((resp) => {
                    if(resp.body.list.length !== 0){
                        this.$store.commit('setMenuLinkList', resp.body.list);
                    }else{
                        this.$store.commit('setMenuLinkList', {});
                    }

                    this.languages = resp.body.languages;
                    var languagesTemp = resp.body.languages;
                    for(var l in languagesTemp){
                        this.selectedMenuInfoToChange.label[languagesTemp[l].slug] = "";
                        if(languagesTemp[l].isDefault){
                            this.editPanelActiveLanguage = languagesTemp[l].slug;
                            this.defaultLanguagesSlug = languagesTemp[l].slug;
                        }
                    }
                }, response => {
                    // if a error happens
                    this.$store.commit('setSpinner', false);
                    new Noty({
                        type: "error",
                        layout: 'bottomLeft',
                        text: response.statusText
                    }).show();
                });

            var panelsPromise = this.$http.get(this.basePath+'/'+this.$route.params.adminPrefix+'/'+this.$route.params.lang+'/menuLinkPanels')
                .then((resp) => {
                    this.linkPanels = resp.body;
                }, response => {
                    // if a error happens
                    this.$store.commit('setSpinner', false);
                    new Noty({
                        type: "error",
                        layout: 'bottomLeft',
                        text: response.statusText
                    }).show();
                });

            // when all ajax request are done
            Promise.all([menuPromise,menuLinksPromise,panelsPromise]).then(([v1,v2,v3]) => {
                this.$store.commit('setSpinner', false);
            });

        }
    },
};
