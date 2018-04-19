<?php

namespace Accio\App\Http\Controllers\Backend;

use App\Models\Album;

use App\Models\Language;
use App\Models\Media;
use App\Models\MenuLinkConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class BaseAlbumController extends MainController{

    /**
     * @return array get the list of albums with their images
     * */
    public function getAll($lang = "", $page = 1){
        $orderBy = (isset($_GET['order'])) ? $_GET['order'] : 'albumID';
        $orderType = (isset($_GET['type'])) ? $_GET['type'] : 'DESC';

        // all albums related media files
        $mediaList = DB::table('media')->join('album_relations', 'media.mediaID', 'album_relations.mediaID')->where('media.type','image')->get();
        $mediaArr = [];
        foreach ($mediaList as $row){
            $mediaArr[$row->albumID][] = $row;
        }

        // albums as pagination obj
        if($page == 0){
            $nrToShow = 100000;
        }else{
            $nrToShow = Album::$rowsPerPage;
        }

        if(isset($_GET['menu_link_id'])){
            $menu_link_id = $_GET['menu_link_id'];
            $albumsObj = DB::table('albums')->join('menu_link_config','albums.albumID','menu_link_config.belongsToID')->where('menuLinkID',$menu_link_id);
            $paginationObj = $albumsObj->orderBy($orderBy, $orderType)->paginate($nrToShow, ['*'], 'page', $page);
        }else{
            $paginationObj = DB::table('albums')->orderBy($orderBy, $orderType)->paginate($nrToShow, ['*'], 'page', $page);
        }
        $paginationObj = Language::filterRows($paginationObj);

        $albumList = [];
        foreach ($paginationObj['data'] as $row){
            $row['mediaList'] = [];
            if (isset($mediaArr[$row['albumID']])){
                $row['mediaList'] = $mediaArr[$row['albumID']];
            }
            $albumList[] = $row;
        }
        $paginationObj['data'] = $albumList;
        return $paginationObj;
    }

    /**
     * @param string $lang language slug
     * @param integer $albumID the id of the album
     * @return array with album data for each language and list of images
     * */
    public function details($lang, $albumID){
        $languages = Language::getFromCache();
        $mediaList = [];
        /* if album id is not 0*/
        if($albumID){
            $mediaList = DB::table('media')
                ->join('album_relations', 'media.mediaID', 'album_relations.mediaID')
                ->where('media.type','image')
                ->where('album_relations.albumID',$albumID)
                ->orderBy("media.mediaID", "DESC")
                ->get();
            $albumDetails = Album::where("albumID",$albumID)->first();
        }

        $list = [];
        // loop throw the languages
        foreach ($languages as $language){
            $langSlug = $language->slug;
            // if album ID is set get the data of the album from each language and store them
            if($albumID){
                $list[$langSlug]['albumID'] = $albumDetails->albumID;
                $list[$langSlug]['langName'] = $language->name;
                $list[$langSlug]['languageID'] = $language->languageID;
                $list[$langSlug]['title'] = (isset(json_decode($albumDetails->title)->$langSlug)) ? json_decode($albumDetails->title)->$langSlug : '';
                $list[$langSlug]['description'] = (isset(json_decode($albumDetails->description)->$langSlug)) ? json_decode($albumDetails->description)->$langSlug : '';
                $list[$langSlug]['isVisible'] = (isset(json_decode($albumDetails->isVisible)->$langSlug)) ? json_decode($albumDetails->isVisible)->$langSlug : '';
            }else{
                // if album id is 0 return empty data
                $list[$langSlug]['albumID'] = $albumID;
                $list[$langSlug]['langName'] = $language->name;
                $list[$langSlug]['languageID'] = $language->languageID;
                $list[$langSlug]['title'] = '';
                $list[$langSlug]['description'] = '';
                $list[$langSlug]['isVisible'] = false;
            }
        }

        $response = array(
            'album'              => $list,
            'images'             => $mediaList,
            'pagination'         => 1,
            'imagesExtensions'   => Media::$imageExtensions,
            'videoExtensions'    => Media::$videoExtensions,
            'videoIconUrl'       => Media::$videoIconUrl,
            'audioExtensions'    => Media::$audioExtensions,
            'audioIconUrl'       => Media::$audioIconUrl,
            'documentExtensions' => Media::$documentExtensions,
            'documentIconUrl'    => Media::$documentIconUrl,
        );

        $response['events'] = Event::fire('album:pre_update', [$response]);

        return $response;
    }

    /**
     * Stores or edits the album in the database
     *
     * @param Request $request
     * @return array
     * */
    public function store(Request $request){
        if($request->albumID){
            $albumModel =  Album::find($request->albumID);
        }else{
            $albumModel = new Album();
        }

        // Prepare array
        $errors = [];
        $albumData = array(
            'title' => [],
            'description' => [],
            'isVisible' => [],
        );
        foreach ($request->album as $key => $data){
            // title is required in visible items
            if($data['title'] == '' && $data['isVisible']){
                $errors['title_'.$key][] = 'The title in '.$data['langName'].' is required';
            }
            $albumData['title'][$key] = $data['title'];
            $albumData['description'][$key] = $data['description'];
            $albumData['isVisible'][$key] = $data['isVisible'];
        }

        $albumModel->title = json_encode($albumData['title']);
        $albumModel->description = json_encode($albumData['description']);
        $albumModel->isVisible = json_encode($albumData['isVisible']);

        // return errors if there are any
        if(count($errors)){
            return $this->response("Please fill all required fields. Check all languages", 400, null, false, false, true, $errors);
        }

        // if album id is 0 create new album otherwise update the selected one
        if($request->albumID){
            if($albumModel->save()){
                if($request->menu_link_id != ''){
                    $this->createMenuLinksCofig($request->menu_link_id, $albumModel->albumID);
                }
                return $this->response('Album successfully updated');
            }
        }else{
            $albumModel->createdByUserID = Auth::user()->userID;

            // Fire event
            if($albumModel->save()) {
                if($request->menu_link_id){
                    $this->createMenuLinksCofig($request->menu_link_id, $albumModel->albumID);
                }
                return $this->response('Album successfully created');
            }
        }

        return $this->response( 'Internal server error. Please try again later', 500);
    }

    private function createMenuLinksCofig(int $menu_link_id, int $albumID){
        MenuLinkConfig::create([
            'menuLinkID' => $menu_link_id,
            'belongsTo' => 'albums',
            'belongsToID' => $albumID,
            'postIDs' => NULL,
        ]);
    }

    /**
     * Delete album and his relations with images
     *
     * @param string $lang
     * @param int $albumID
     * @return array
     * */
    public function delete($lang, $albumID){
        $album = Album::find($albumID);

        if($album){
            if($album->delete()){
                DB::table('album_relations')->where('albumID', $albumID)->delete();
                return $this->response('Album deleted');
            }
        }

        return $this->response('This album could not be deleted. Please try again later', 500);
    }

    /**
     * @return array of media ids od a album
     * */
    public function getMediaIDsOfAlbum($lang, $albumID){
        $album_relations = DB::table('album_relations')->where('albumID', '=' ,$albumID)->get();
        $mediIDs = [];
        foreach ($album_relations as $relation){
            $mediIDs[] = $relation->mediaID;
        }
        return $mediIDs;
    }

    /**
     * @return array List of related albums for a media file
     * */
    public function getAlbumRelation($lang, $mediaID){
        $albums_relation = DB::table('album_relations')->join('albums','album_relations.albumID','albums.albumID')->where('mediaID', $mediaID)->get();
        return Language::filterRows($albums_relation->toArray(), false);
    }

}
