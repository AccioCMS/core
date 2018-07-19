<?php

namespace Accio\App\Models;

use App\Models\Post;
use App\Models\Tag;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Accio\App\Traits;
use App\Models\PostType;
use Accio\Support\Facades\Meta;
use Spatie\Activitylog\Traits\LogsActivity;

class TagModel extends Model{

    use
      Traits\TagTrait,
      LogsActivity,
      Traits\CacheTrait,
      Traits\BootEventsTrait,
      Traits\CollectionTrait;

    /**
     * Fields that can be filled in CRUD
     *
     * @var array $fillable
     */
    protected $fillable = ['postTypeID','title','slug','description','featuredImageID', 'createdByUserID'];

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "tagID";

    /**
     * Default number of rows per page to be shown in admin panel
     *
     * @var integer $rowsPerPage
     */
    public static $rowsPerPage = 25;

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    public $table = "tags";

    /**
     * Lang key that points to the multi language label in translate file
     * @var string
     */
    public static $label = "tags.label";

    /**
     * Default permission that will be listed in settings of permissions
     *
     * @var array $defaultPermissions
     */
    public static $defaultPermissions = ['create','read', 'update', 'delete'];

    /**
     * @var bool
     */
    protected static $logFillable = true;

    /**
     * @var bool
     */
    protected static $logOnlyDirty = true;

    /**
     * @inheritdoc
     * */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        Event::fire('tags:construct', [$this]);
    }

    /**
     * Declare columns that should be saved in MenuLinks table as 'attributes', to enable navigation in front-end
     *
     * @return array
     */
    public function menuLinkParameters()
    {
        $previousAutoTranslate = $this->getAutoTranslate();
        $this->setAutoTranslate(false);
        $postType = PostType::findByID($this->postTypeID);

        $data = [
            'tagID'    => $this->tagID,
            'tagSlug'  => $this->slug,
            'postTypeSlug'  => cleanPostTypeSlug($postType->slug)
        ];

        $this->setAutoTranslate($previousAutoTranslate);
        return $data;
    }

    /**
     * Check if a post has featured image
     *
     * @return boolean Returns true if found
     */
    public function hasFeaturedImage(){
        if($this->featuredImageID && $this->featuredImage){
            return true;
        }
        return false;
    }

    /**
     * Featured image of a tag
     * @return HasOne
     */
    public function featuredImage()
    {
        return $this->hasOne('App\Models\Media','mediaID','featuredImageID');
    }


    /**
     * Define single user's SEO Meta data
     *
     * @return array
     */
    public function metaData(){
        Meta::setTitle($this->title)
            ->set("description", $this->about)
            ->set("og:type", "website", "property")
            ->set("og:title", $this->title, "property")
            ->set("og:description", $this->description, "property")
            ->set("og:url",$this->href, "property")
            ->setImageOG(($this->hasFeaturedImage() ? $this->featuredImage : null))
            ->setCanonical($this->href)
            ->setWildcards([
                '{title}' => $this->title,
                '{siteTitle}' => settings('siteTitle')
            ]);
    }

    /**
     * Generate the URL to a tag
     *
     *
     * @return string
     */
    public function getHrefAttribute(){
        return $this->href();
    }


    /**
     * Generate a custom URL to a tag
     *
     * @param string $routeName
     * @param array $customAttributes
     *
     * @return string
     */
    public function href($routeName = 'tag.single', $customAttributes = []){
        $params = ['tagSlug'=>$this->slug];
        if($customAttributes){
            $params = array_merge($customAttributes, $params);
        }
        return route($routeName,$params);
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('tag:destruct', [$this]);
    }
}
