<?php
namespace Accio\App\Traits;

use App\Models\Media;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Mockery\Exception;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Database\Eloquent\Model;
use DB;
use Auth;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


trait MediaTrait{

    /**
     * Upload files, create thumbs and save it to db
     *
     * @param  Request $request The request that contains $_FILES
     * @param  string  $belongsToApp The name of the app for which thumbs should be created. If empty, only general thumbs will be craeted
     *
     * @return array
     */
    public static function upload($request, $belongsToApp = ''){
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
            if(!in_array($extension, \App\Models\Media::allowedExtensions())){
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

                if(in_array($extension, Media::$imageExtensions)){
                    $media->type = "image";
                    // if the uploaded file is a image set his dimensions in the database
                    $img = Image::make($destinationOriginalPath);
                    $width = $img->width();
                    $height = $img->height();
                    $media->dimensions = $width."x".$height;

                }else if(in_array($extension, Media::$documentExtensions)){
                    $media->type = "document";
                }else if(in_array($extension, Media::$audioExtensions)){
                    $media->type = "audio";
                }else if(in_array($extension, Media::$videoExtensions)){
                    $media->type = "video";
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

                    // Create thumbs
                    if(in_array($extension, Media::$imageExtensions)){
                        foreach(Media::$thumbSizes as $thumKey => $thumValue){
                            if ($thumKey == "default" || $thumKey == $belongsToApp){ // only thumbs that are default and which belongs to this current app
                                foreach ($thumValue as $thumbParams){
                                    self::createThumb($media, $width, $height);

                                    if (in_array($extension, Media::$imageExtensions)){
                                        //@TODO check if thumb paths are writable
                                        $thumbDir = $destinationPath."/".$thumbParams[0]."x".$thumbParams[1];

                                        if(!File::exists($thumbDir)){
                                            if(!File::makeDirectory($thumbDir, 0755, true)){
                                                array_push($results, ['result_code' => 'failed','filename'=> $fileName, 'extension' => $extension, 'msg' => 'The directory  "'.$thumbDir.'" could not be created. Please contact your administrator', 'url' => '']);
                                                continue;
                                            }
                                        }

                                        //check if paths are writable
                                        if(!File::isWritable($thumbDir)){
                                            array_push($results, ['result_code' => 'failed','filename'=> $fileName, 'extension' => $extension, 'msg' => 'Thumb '.$thumbParams[0]."x".$thumbParams[1].' could not be create because the directory  "'.$thumbDir.'" is not writable. Please contact your administrator', 'url' => '']);
                                            continue;
                                        }

                                        $img = Image::make($destinationOriginalPath);
//                                    $resizedHeight = $thumbParams[0] * 2;
//                                    $img->resize($resizedHeight, null, function ($constraint) {
//                                        $constraint->aspectRatio();
//                                    });

                                        //$img->resizeCanvas($thumbParams[0], $thumbParams[1], 'center', false);
                                        $img->fit($thumbParams[0], $thumbParams[1]);
                                        $img->save($thumbDir.'/'.$fileName, 60);
                                    }
                                }
                            }
                        }
                    }

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
            if(self::createThumb($imageObj, $width, $height)){
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
        self::createThumb($this, $width, $height);
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
    public static function createThumb($imageObj, $width, $height=null){
        $extension = File::extension( $imageObj->url);
        $basePath = base_path('/');

        if (in_array($extension, Media::$imageExtensions)){
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
                $img = Image::make($imageObj->url);
                $resizedHeight = $width * 2;
//                $img->resize($resizedHeight, null, function ($constraint) {
//                    $constraint->aspectRatio();
//                });
                $img->fit($width, $height);

                if ($img->save($thumbDir . '/' . $imageObj->filename, 60)) {
                    return true;
                }
            }
        }
        return false;
    }
}