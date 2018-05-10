<?php

namespace Accio\App\Http\Controllers\Backend;

use App\Models\Settings;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Image;
use App\Models\Media;
use Accio\Support\Facades\Pagination;
use Accio\Support\Facades\Search;


class BaseMediaController extends MainController{

    public function __construct(){
        parent::__construct();
    }

    /**
     * This function uses upload function that is defined in Media model that uploads files
     * */
    public function store(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Media','create')){
            return $this->noPermission();
        }
        return (new Media())->upload($request);
    }

    /**
     * Get the list of media files
     * @show 100
     * */
    public function getList($lang, $pagination){
        // check if user has permissions to access this link
        if(!User::hasAccess('Media','read')){
            return $this->noPermission();
        }
        $list = Pagination::infiniteScrollPagination('media', $pagination, Media::$infinitPaginationShow);
        $results = [
            'list'               => $list,
            'count'              => $list->count(),
            'pagination'         => $pagination,
            'imagesExtensions'   => config('media.image_extensions'),
            'videoExtensions'    => config('media.video_extensions'),
            'videoIconUrl'       => config('media.video_icon_url'),
            'audioExtensions'    => config('media.video_extensions'),
            'audioIconUrl'       => config('media.audio_icon_url'),
            'documentExtensions' => config('media.document_extensions'),
            'documentIconUrl'    => config('media.document_icon_url'),
        ];

        $results['events'] = Event::fire('media:pre_update', [$results]);

        return $results;
    }

    /**
     *  Search media (simple search)
     * */
    public function search($term, $pagination){
        $view = 'search';
        // check if user has permissions to access this link
        if(!User::hasAccess('Media','create')){
            return view('errors.permissions', compact('view','term','pagination'));
        }
        return view(Media::$backendPathToView.'all', compact('view','term','pagination'));
    }

    public function searchMedia(Request $request){
        return Search::media($request->term, $request->from, $request->to, $request->type, "created_at", "DESC", $request->page);
    }

    /**
     * Edit a specific media file information
     * */
    public function edit(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Media','update')){
            return $this->noPermission();
        }
        $media = Media::find($request->mediaID);
        $media->title = $request->title;
        $media->description = $request->description;
        $media->credit = $request->credit;

        if ($media->save()){
            // delete previous relation
            DB::table('album_relations')->where('mediaID',$request->mediaID)->delete();

            // add relations
            foreach ($request->selectedFileAlbums as $album){
                DB::table('album_relations')->insert([
                    'mediaID' => $request->mediaID,
                    'albumID' => $album['albumID'],
                    "created_at" =>  \Carbon\Carbon::now(),
                    "updated_at" => \Carbon\Carbon::now(),
                ]);
            }

            // delete cache
            Cache::flush();

            return "OK";
        }
        return "ERR";
    }

    /**
     * Delete media file
     * */
    public function delete(Request $request){
        $isOk = "OK";
        // loop throw file array
        foreach ($request->all() as $key => $file){
            if ($key === "postTypes"){
                continue;
            }
            // check if user has permissions to access this link
            if(!User::hasAccess('Media','delete',$file['mediaID'], true)){
                return $this->noPermission();
            }

            // delete from album relations
            $relationDeletePass = true;
            $albumRelations = DB::table("album_relations")->where('mediaID', $file['mediaID']);
            if($albumRelations->count() && !$albumRelations->delete()){
                $relationDeletePass = false;
            }

            // get file info from database
            $media = \App\Models\Media::find($file['mediaID']);
            if ($relationDeletePass && $media->delete()){ // delete from database
                // delete cache
                Cache::flush();

                if(file_exists($media->url)) {
                    unlink($media->url); // delete original file
                }
                foreach(config('media.default_thumb_size') as $thumKey => $thumValue){ // loop throw all thumbs and delete them
                    foreach ($thumValue as $thumParams){
                        $folderName = $thumParams[0] ."x".$thumParams[1];
                        $path = $media->fileDirectory."/".$folderName."/".$media->filename;
                        if(file_exists($path)){
                            unlink($path);
                        }
                    }
                }

            }else{
                $isOk =  "ERR";
            }
        }
        return $isOk;
    }

    /**
     *  Assign watermark to media images
     * */
    public function assignWatermark(Request $request){
        // Verify watermark
        $watermarkMediaID = Settings::where("settingsKey", "watermark")->first();
        if(!$watermarkMediaID){
            return $this->response("Watermark is not selected, Go to settings select watermark", 500);
        }
        $watermarkUrl = Media::find($watermarkMediaID)->first();
        if(!$watermarkUrl){
            return $this->response("Watermark is not selected, Go to settings select watermark", 500);
        }
        $watermarkUrl = $watermarkUrl->url;
        // does watermark exist
        if(!File::exists(base_path($watermarkUrl))){
            return $this->response("Watermark is not selected, Go to settings select watermark", 500);
        }

        foreach ($request->all() as $key => $file){
            // check if user has permissions to access this link
            if(!User::hasAccess('Media','update',$file['mediaID'], true)){
                return $this->noPermission();
            }

            if ($file['type'] == "image"){

                // mark original image
                // original image
                $url = strpos($file['url'], '?');
                if($url == false){
                    $url = base_path($file['url']);
                }else{
                    $url = base_path(explode('?',$file['url'])[0]);
                }

                $originalImage = Image::make($url);
                // get width of the original image
                $originalWidth = (integer) $originalImage->width();
                // calculate a width for the watermark
                $width = round($originalWidth / 3);
                // does file exist
                if(!File::exists($url)){
                    throw new \Exception("File: ".$url." doesn't exists.");
                }

                // get watermark
                $watermark = Image::make($watermarkUrl);

                // resize watermark
                $watermark->resize($width, null, function ($constraint){
                    $constraint->aspectRatio();
                });
                $originalImage->insert($watermark, 'center');
                $originalImage->save($url, 100);
                $this->deleteThumb($file);

                foreach(config('media.default_thumb_size') as $thumKey => $thumValue){
                    foreach ($thumValue as $thumParams){
                        $originalThumbWidth = (integer)$thumParams[0];
                        if($originalThumbWidth >= 300 || $originalThumbWidth == 200){
                            $thumbWidth = round($originalThumbWidth / 3);
                            $folderName = $thumParams[0] ."x".$thumParams[1];
                            $path = base_path($file['fileDirectory']."/".$folderName."/".$file['filename']);
                            if(!File::exists($path)){
                                throw new \Exception("File: ".$path." doesn't exists.");
                            }
                            if(file_exists($path)){
                                // mark thumb image
                                $thumb = Image::make($path);
                                $watermark = Image::make($watermarkUrl);
                                // resize watermark
                                $watermark->resize($thumbWidth, null, function ($constraint) {
                                    $constraint->aspectRatio();
                                });
                                $thumb->insert($watermark, 'center');
                                $thumb->save($path, 100);
                            }
                        }
                    }
                }

                // delete cache
                Cache::flush();
            }else{
                return "Some files could not be watermaked. Please select only image files.";
            }
        }
        return "OK";
    }

    /**
     * Deletes thumbs (not the default ones of cms) for a image
     * @param $image
     */
    private function deleteThumb($image){
        // list default thumb sizes in a array (use to ignore them, not delete them)
        $thumbSizesToNotBeDeleted = [];
        foreach(config('media.default_thumb_size') as $thumbSizesArr){
            foreach($thumbSizesArr as $thumbSize){
                if(count($thumbSize) == 2){
                    $thumbSizesToNotBeDeleted[] = $thumbSize[0] . "x" . $thumbSize[1];
                }
            }
        }

        // delete all thumbs of a image
        $allDirs = [];
        if (is_dir(base_path($image['fileDirectory']))){
            if ($dh = opendir(base_path($image['fileDirectory']))){
                while (($thumbDir = readdir($dh)) !== false){
                    if(is_dir(base_path($image['fileDirectory']."/$thumbDir")) && $thumbDir != "original"){
                        if(!in_array($thumbDir, $thumbSizesToNotBeDeleted) && file_exists(base_path($image['fileDirectory']."/$thumbDir/".$image['filename']))){
                            unlink(base_path($image['fileDirectory']."/$thumbDir/".$image['filename']));
                        }
                    }
                    $allDirs[] = $thumbDir;
                }
                closedir($dh);
            }
        }
    }

    /**
     *  Crop the image with the specific dimensions
     * */
    public function cropImage(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Media','update')){
            return $this->noPermission();
        }
        // get all inputs
        $inputs = $request->all();
        // Where is this file beeing croped
        $app = $inputs[4];
        // extension of the current image that is in crop process
        $mediaID = $inputs[0]['mediaID'];
        // check if user has permissions to access this link
        if(!User::hasAccess('Media','update',$mediaID, true)){
            return $this->noPermission();
        }

        $media = Media::find($mediaID);
        // extension of the current image that is in crop process
        $extension = $inputs[0]['extension'];
        // destination folder
        $destinationPath = $inputs[0]['fileDirectory'];
        // width and heigth that appear in frontend
        $imageApperanceWidth = $inputs[2];
        $imageApperanceHeight = $inputs[3];
        // original image
        $url = strpos($inputs[0]['url'], '?');
        if($url == false){
            $url = $inputs[0]['url'];
        }else{
            $url = explode('?',$inputs[0]['url'])[0];
        }
        $img = Image::make($url);
        // $croppedWidth = the width of the cropped window
        $croppedWidth = $inputs[1]['width'];
        // $newWidth = the new width calculated for the original dimensions of the image
        $newWidth = round($img->width() / ($imageApperanceWidth/$croppedWidth));

        // $croppedHeight = the height of the cropped window
        $croppedHeight = $inputs[1]['height'];
        // $newHeight = the new height calculated for the original dimensions of the image
        $newHeight = round($img->height() / ($imageApperanceHeight/$croppedHeight));

        // X and Y of the crop window
        $croppedX = $request->all()[1]['x'];
        $croppedY = $request->all()[1]['y'];
        // new X and Y calculated for the original dimensions of the image
        $newX = round(($img->width() / $imageApperanceWidth) * $croppedX);
        $newY = round(($img->height() / $imageApperanceHeight) * $croppedY);

        // crop image and save the new image
        $img->crop($newWidth, $newHeight, $newX, $newY);
        $img->save($url, 100);
        $media->touch();

        $this->deleteThumb($media);

        // delete cache
        Cache::flush();

        // Create thumbs
        foreach(config('media.default_thumb_size') as $thumKey => $thumValue){
            if ($thumKey == "default" || $thumKey == $app){ // create only the thumbs that are needed for a app
                foreach ($thumValue as $thumParams){
                    if (in_array($extension, config('media.image_extensions'))){
                        $thumbDir = $destinationPath."/".$thumParams[0]."x".$thumParams[1];
                        if(!is_dir($thumbDir)){
                            mkdir($thumbDir, 0700);
                        }

                        $img = Image::make($url);
                        $resizedHeight = $thumParams[0] * 2;
                        $img->resize($resizedHeight, null, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                        //$img->resizeCanvas($thumParams[0], $thumParams[1], 'center', false);
                        $img->fit($thumParams[0], $thumParams[1]);
                        $thumbName = $inputs[0]['filename'];
                        $img->save($thumbDir.'/'.$thumbName, 100);
                    }
                }
            }
        }

        return "OK";
    }

}
