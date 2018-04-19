export const globalData = {
    created() {
        this.videoIconUrl = this.resourcesUrl('/images/video.png');
        this.documentIconUrl = this.resourcesUrl('/images/document.png');
        this.audioIconUrl = this.resourcesUrl('/images/audio.png');
    },
    data(){
        return{
            trans:{},
            openedItemActionBar: '', // which action bar dropdown is open
            activeLang: '',// current active language (usually used in tabs)
            videoIconUrl: '',
            documentIconUrl: '',
            audioIconUrl: '',
        }
    }
};
