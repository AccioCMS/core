<?php

namespace Accio\App\Http\Controllers\Backend;

use App\Models\MediaRelation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Image;
use App\Models\Media;
use Accio\Support\Facades\Pagination;
use Accio\Support\Facades\Search;


class BaseMediaController extends MainController{
    /**
     * This function uses upload function that is defined in Media model that uploads files.
     *
     * @param Request $request
     * @return array
     */
    public function store(Request $request){
        // check if user has permissions to access this link
        if(!User::hasAccess('Media','create')){
            return $this->noPermission();
        }
        return (new Media())->upload($request);
    }

    /**
     * Get the list of media files.
     *
     * @param string $lang
     * @param int $pagination
     * @return array
     */
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
     * Search media (simple search).
     *
     * @param string $term
     * @param int $pagination
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function search($term, $pagination){
        $view = 'search';
        // check if user has permissions to access this link
        if(!User::hasAccess('Media','create')){
            return view('errors.permissions', compact('view','term','pagination'));
        }
        return view('content');
    }

    /**
     * Perform search in media.
     * TODO Use elastic search
     *
     * @param Request $request
     * @return mixed
     */
    public function searchMedia(Request $request){
        return Search::media($request->term, $request->from, $request->to, $request->type, "created_at", "DESC", $request->page);
    }

    /**
     * Edit a specific media file information.
     *
     * @param Request $request
     * @return array|string
     */
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
            return "OK";
        }
        return "ERR";
    }

    /**
     * Delete media file.
     *
     * @param Request $request
     * @return array|string
     */
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

            // get file info from database
            $media = Media::find($file['mediaID']);
            MediaRelation::where("mediaID", $file['mediaID'])->delete();
            if ($media->delete()){ // delete from database
                if(file_exists($media->url)) {
                    unlink($media->url); // delete original file
                }
                $media->deleteThumbs();
            }else{
                $isOk =  "ERR";
            }
        }
        return $isOk;
    }

    /**
     * Get watermark.
     *
     * @return array
     */
    private function getWatermak(){
        // Verify watermark
        $watermarkMediaID = settings( "watermark");
        if(!$watermarkMediaID){
            return $this->response("No watermark is available. Go to settings and set a watermark", 500);
        }

        $watermarImage = Media::find($watermarkMediaID);
        if(!$watermarImage){
            return $this->response("Watermark Image is not selected. Please go to settings and choose a new watermark", 500);
        }

        $watermarkUrl = $watermarImage->url;
        if(!File::exists(base_path($watermarkUrl))){
            return $this->response("Watermark URL is not avialable. Please go to settings and choose a new watermark", 500);
        }

        return $watermarImage;
    }

    /**
     * Assign watermark to media images.
     *
     * @param Request $request
     * @return array|string
     * @throws \Exception
     */
    public function assignWatermark(Request $request){
        $getWatermak = $this->getWatermak();

        if(!$getWatermak){
            return $getWatermak;
        }

        // go through each select image
        foreach ($request->all() as $key => $file){
            $image = new Media($file);

            // check if user has permissions to access this link
            if(!User::hasAccess('Media','update',$image->mediaID, true)){
                return $this->noPermission();
            }

            if ($image->type == "image"){

                // mark original image
                $url = strpos($image->url, '?');
                if($url == false){
                    $url = base_path($image->url);
                }else{
                    $url = base_path(explode('?',$image->url)[0]);
                }

                // does file exist
                if(!File::exists($url)){
                    throw new \Exception("Original images ".$url." doesn't exists.");
                }

                $originalImage = Image::make($url);

                // get width of the original image
                $originalWidth = (integer) $originalImage->width();

                // calculate a width for the watermark
                $width = round($originalWidth / 3);

                // get watermark
                $watermark = Image::make($getWatermak->url);

                // resize watermark
                $watermark->resize($width, null, function ($constraint){
                    $constraint->aspectRatio();
                });
                $originalImage->insert($watermark, 'center');
                $originalImage->save($url, 100);

                $image->deleteThumbs();
                $image->createDefaultThumbs();

                // delete cache
                Cache::flush();
            }else{
                return "Some files could not be watermaked. Please select only image files.";
            }
        }
        return "OK";
    }


    /**
     * Crop the image with the specific dimensions.
     *
     * @param Request $request
     * @return string
     */
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

        $media->deleteThumbs();
        $media->createDefaultThumbs(null, $app);

        return "OK";
    }
}
