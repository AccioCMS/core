<?php
namespace Accio\App\Traits;

use App\Models\Media;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Mockery\Exception;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Database\Eloquent\Model;
use DB;
use Auth;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use ImageOptimizer;

trait MediaTrait{

    /**
     * Upload files, create thumbs and save it to db
     *
     * @param  Request $request The request that contains $_FILES
     * @param  string  $belongsToApp The name of the app for which thumbs should be created. If empty, only general thumbs will be craeted
     *
     * @return array
     */
    public function upload($request, $belongsToApp = ''){
        $results = array();
        $carbon = new Carbon();

        $destinationBasePath = uploadsPath(); // upload basic path
        $year = $carbon->year;
        $month = str_pad($carbon->month, 2, '0', STR_PAD_LEFT);
        $day = str_pad($carbon->day, 2, '0', STR_PAD_LEFT);

        $datePath = $year."/".$month.'/'.$day;
        $destinationPath = uploadsPath($datePath);
        $fileDirectory = 'public'.explode('public', $destinationPath)[1];
        $fileDirectory = str_replace('\\','/', $fileDirectory);

        $destinationOriginalDirectory = $destinationPath."/original";

        foreach ($request->all() as $key => $value){
            // continue when we are looping throw post type
            if ($key == "postTypes" || $key == "albumID" || $key == "fromAlbum"){
                continue;
            }

            // uploaded file
            $file = $request->file($key);
            $extension = $file->getClientOriginalExtension(); // getting image extension
            $fileNameWithoutExtension = str_slug(basename($file->getClientOriginalName(), '.'.$file->getClientOriginalExtension()),'-');
            $fileName = $fileNameWithoutExtension.'.'.$extension;
            $fileSizeOriginal = $file->getClientSize();
            $fileSize = round($fileSizeOriginal/1048576*1024);

            // change filename if filename already exists
            if(file_exists($destinationOriginalDirectory.'/'.$fileName)){
                $exists = true;
                while($exists === true){
                    $random = str_random(5);
                    $fileName = $fileNameWithoutExtension.'-'.$random.'.'.$extension;
                    if(!file_exists($destinationOriginalDirectory.'/'.$fileName)){
                        $exists = false;
                        break;
                    }
                }
            }

            $destinationOriginalPath = $destinationOriginalDirectory.'/'.$fileName;

            // validate file type
            if(!$this->isAllowedExtension($extension)){
                throw new FileException("File type not allowed");
            }

            //validate size
            if($fileSize > config('filesystems.upload_max_filesize')){
                throw new FileException('File is to large. Max filesize is '. config('filesystems.upload_max_filesize') .'kb');
            }

            //check if $destinationBasePath is writable
            if(!File::isWritable($destinationBasePath)){
                throw new FileException('The directory  "'.$destinationBasePath.'" is not writable. Please contact your administrator');
            }

            //crate original original size path if it doesn't excist
            if (!File::exists( $destinationOriginalDirectory)) {
                if(!File::makeDirectory($destinationOriginalDirectory, 0755, true)){
                    throw new FileException('The directory  "'.$destinationOriginalDirectory.'" could not be created. Please contact your administrator');
                }
            }

            //check if $destinationPath is writable
            if(!File::isWritable($destinationPath)){
                throw new FileException('The directory  "'.$destinationPath.'" is not writable. Please contact your administrator');
            }

            //check if $destinationOriginalDirectory is writable
            if(!File::isWritable($destinationOriginalDirectory)){
                throw new FileException('The directory  "'.$destinationOriginalDirectory.'" is not writable. Please contact your administrator');
            }

            // if file is successfully uploaded
            if($file->move($destinationOriginalDirectory, $fileName)){
                // set params to the media object
                $media = new Media();
                $media->title = $fileNameWithoutExtension;
                $media->description = '';
                $media->extension = $extension;
                $media->url = $fileDirectory.'/original/'.$fileName;
                $media->filename = $fileName;
                $media->fileDirectory = $fileDirectory;
                $media->filesize = round(($fileSize/1000),2);
                $media->createdByUserID = Auth::user()->userID;

                if($this->hasImageExtension($extension)){
                    $media->type = "image";
                    // if the uploaded file is a image set his dimensions in the database
                    $img = Image::make($destinationOriginalPath);
                    $width = $img->width();
                    $height = $img->height();
                    $media->dimensions = $width."x".$height;

                }else if($this->hasDocumentExtension($extension)){
                    $media->type = "document";
                }elseif($this->hasAudioExtension($extension)){
                    $media->type = "audio";
                }elseif($this->hasVideoExtension($extension)){
                    $media->type = "video";
                }else{
                    throw new FileException("Extension type not allowed");
                }

                // Fire event
                Event::fire('media:creating', [$media, $request]);

                if($media->save()){
                    if ($request->fromAlbum === "true" || $request->fromAlbum === true){
                        \Illuminate\Support\Facades\DB::table('album_relations')->insert([
                            'albumID' => $request->albumID,
                            'mediaID' => $media->mediaID,
                            "created_at" =>  \Carbon\Carbon::now(),
                            "updated_at" => \Carbon\Carbon::now(),
                        ]);
                    }

                    // optimize original image
                    if(config('media.optimize_original_image')) {
                        $this->optimize($destinationOriginalDirectory . '/' . $fileName);
                    }

                    // Create thumbs
                    if($this->hasImageExtension($extension)){
                        foreach(config('media.default_thumb_size') as $thumKey => $thumValue){
                            if ($thumKey == "default" || $thumKey == $belongsToApp){ // only thumbs that are default and which belongs to this current app
                                foreach ($thumValue as $thumbDimension){
                                    $this->createThumb($media, $thumbDimension[0], $thumbDimension[1]);
                                }
                            }
                        }
                    }

                    // delete cache
                    Cache::flush();

                    // Fire event
                    Event::fire('media:created', [$media, $request]);

                    $msg = 'File is successfully uploaded';
                    array_push($results, ['result_code' => 'ok','filename'=> $fileName, 'extension' => $extension, 'msg' => $msg, 'url' => $destinationOriginalPath]);
                }else{
                    unlink($destinationOriginalDirectory, $fileName);
                    $err = 'File could not be saved. Please try again later or contact your administrator';
                    array_push($results, ['result_code' => 'failed','filename'=> $fileName, 'extension' => $extension, 'msg' => $err, 'url' => '']);
                }
            }else{
                $err = 'File could not be uploaded. Please try again later or contact your administrator';
                array_push($results, ['result_code' => 'failed','filename'=> $fileName, 'extension' => $extension, 'msg' => $err, 'url' => '']);
            }

        }
        return $results;
    }

    /**
     * Get thumb by image and size.
     * If requested thumb does not exist, it automatically creates it.
     *
     * @param object $imageObj A single Image object
     * @param int $width Width of the image
     * @param int $height Height of the image
     *
     * @return string|null Returns URL of thumb, null if thumb could not found or created
     */
    public function thumb($width, $height=null, $imageObj = null){
        // get current  object's image in case there is no specific image given
        if(!$imageObj){
            $imageObj = $this;
        }
        $thumbDirectory =  $width.($height ? 'x'.$height : "");
        $thumbPath = $imageObj->fileDirectory.'/'.$thumbDirectory.'/'.$imageObj->filename;
        if(file_exists(base_path($thumbPath))){
            return asset($thumbPath);
        }else{
            if($this->createThumb($imageObj, $width, $height)){
                return asset($thumbPath);
            }
        }
        return null;
    }

    /**
     * Create thumb from image object
     *
     * @param  int $width Width of the image
     * @param  int $height Height of the image
     *
     * @return object
     */
    public function makeThumb($width, $height=null){
        $this->createThumb($this, $width, $height);
        return $this;
    }

    /**
     * Create thumb from image object
     *
     * @param  object $imageObj A single Image object
     * @param  int $width Width of the image
     * @param  int $height Height of the image
     *
     * @return boolean
     */
    public function createThumb($imageObj, $width, $height=null){
        $extension = File::extension($imageObj->url);
        $basePath = base_path('/');

        if (in_array($extension, config('media.image_extensions'))){
            //thumb can only be created if original source exist
            if(file_exists($basePath.$imageObj->url) && File::size($basePath.$imageObj->url)) {
                $thumbDir = base_path($imageObj->fileDirectory . "/" . $width.($height ? 'x'.$height : ""));

                if (!File::exists($thumbDir)) {
                    if (!File::makeDirectory($thumbDir, 0775, true)) {
                        return false;
                    }
                }

                //check if paths are writable
                if (!File::isWritable($thumbDir)){
                    return false;
                }

                //create thumb
                $img = Image::make(base_path($imageObj->url));
                $resizedHeight = $width * 2;
//                $img->resize($resizedHeight, null, function ($constraint) {
//                    $constraint->aspectRatio();
//                });
                $img->fit($width, $height);

                if ($img->save($thumbDir . '/' . $imageObj->filename, 60)) {
                    // optimize image
                    $this->optimize($thumbDir . '/' . $imageObj->filename);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if an extension is image
     *
     * @param $extension
     * @return bool
     */
    private function hasImageExtension($extension){
        if(array_intersect([strtolower($extension),strtoupper($extension)], config('media.image_extensions'))){
            return true;
        }
        return false;
    }

    /**
     * Check if an extension is video
     *
     * @param $extension
     * @return bool
     */
    private function hasVideoExtension($extension){
        if(array_intersect([strtolower($extension),strtoupper($extension)], config('media.video_extensions'))){
            return true;
        }
        return false;
    }

    /**
     * Check if an extension is audio
     *
     * @param $extension
     * @return bool
     */
    private function hasAudioExtension($extension){
        if(array_intersect([strtolower($extension),strtoupper($extension)], config('media.audio_extensions'))){
            return true;
        }
        return false;
    }

    /**
     * Check if an extension is document
     *
     * @param $extension
     * @return bool
     */
    private function hasDocumentExtension($extension){
        if(array_intersect([strtolower($extension),strtoupper($extension)], config('media.document_extensions'))){
            return true;
        }
        return false;
    }

    /**
     * Check if an extension is allowed to be uploaded
     * @param string $extension
     * @return bool
     */
    private function isAllowedExtension($extension){
        if(array_intersect([strtolower($extension),strtoupper($extension)], self::allowedExtensions())){
            return true;
        }
        return false;
    }

    /**
     * @return array all allowed extensions
     */
    public static function allowedExtensions(){
        return array_merge(
          config('media.image_extensions'),
          config('media.document_extensions'),
          config('media.audio_extensions'),
          config('media.video_extensions')
        );
    }
    /**
     * Compress & optimize an image
     *
     * @param string $pathToImage
     * @param string|null $pathToOutput
     *
     * @return $this
     */
    public function optimize(string $pathToImage, string $pathToOutput = null){
        if(config('media.optimize_image')) {
            ImageOptimizer::optimize($pathToImage, $pathToOutput);
        }
        return $this;
    }
}