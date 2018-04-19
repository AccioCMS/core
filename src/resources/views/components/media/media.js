export default {
    state: {
        mediaList: [],
        pagination: '',
        imagesExtensions: '',
        videoExtensions: '',
        audioExtensions: '',
        documentExtensions: '',
        isCropOpen: false,
        selectedFiles: [],
        isMediaOpen: false,
        popUpActiveMediaView: '',
        openMediaOptions: { format : '', inputName: '', langSlug: '' },
        mediaSelectedFiles: {},
        selectedAlbumID: 0,
        // saved state for the library of all media files and files of a album
        librarySavedState: {
            library: {
                searchTerm: '',
                type: 'false',
                from: '',
                to: '',
                filtered: false,
                selectedFiles: [],
            },
            album: {
                searchTerm: '',
                type: 'false',
                from: '',
                to: '',
                filtered: false,
                selectedFiles: [],
            },
            upload: {
                fromAlbum: false,
            }
        },
    },
    getters: {
        get_media_list(state){
            return state.mediaList;
        },
        get_is_media_open(state){
            return state.isMediaOpen;
        },
        get_popup_active_media_view(state){
            return state.popUpActiveMediaView;
        },
        get_media_selected_files(state){
            return state.mediaSelectedFiles;
        },
        get_selected_album_ID(state){
            return state.selectedAlbumID;
        },
        get_library_saved_state(state){
            return state.librarySavedState;
        },
        get_open_media_options(state){
            return state.openMediaOptions;
        },
        get_is_crop_open(state){
            return state.isCropOpen;
        }
    },
    mutations: {
        setMediaList(state, mediaList){
            state.mediaList = mediaList;
        },
        setIsMediaOpen(state, isMediaOpen){
            state.isMediaOpen = isMediaOpen;
        },
        setPopUpActiveMediaView(state, popUpActiveMediaView){
            state.popUpActiveMediaView = popUpActiveMediaView;
        },
        setMediaSelectedFiles(state, mediaSelectedFiles){
            state.mediaSelectedFiles = mediaSelectedFiles;
        },
        setSelectedAlbumID(state, selectedAlbumID){
            state.selectedAlbumID = selectedAlbumID;
        },
        setLibrarySavedState(state, librarySavedState){
            state.librarySavedState = librarySavedState;
        },
        setLibrarySavedStateForLibrary(state, savedState){
            state.librarySavedState.library = savedState;
        },
        setLibrarySavedStateForAlbum(state, savedState){
            state.librarySavedState.album = savedState;
        },
        setLibrarySavedStateForUpload(state, savedState){
            state.librarySavedState.upload = savedState;
        },
        setOpenMediaOptions(state, openMediaOptions){
            state.openMediaOptions = openMediaOptions;
        },
        setMediaSelectedFilesNested(state, mediaSelectedFilesArr){
            state.mediaSelectedFiles[mediaSelectedFilesArr[0]] = mediaSelectedFilesArr[1];
        },
        setIsCropOpen(state, isCropOpen){
            state.isCropOpen = isCropOpen;
        },
        // remove media files group with key
        removeSpecificMediaKey(state, key){
            let tmpFiles = state.mediaSelectedFiles;
            state.mediaSelectedFiles = {};
            let result = {};
            for(let k in tmpFiles){
                if(k != key){
                    result[k] = tmpFiles[k];
                }
            }
            state.mediaSelectedFiles = result;
        }
    },
    actions: {}
};