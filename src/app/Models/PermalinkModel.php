<?php

namespace Accio\App\Models;

use App\Models\Permalink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Accio\App\Traits;
use Spatie\Activitylog\Traits\LogsActivity;

class PermalinkModel extends Model
{

    use
      Traits\PermalinkTrait,
      LogsActivity,
      Traits\CacheTrait,
      Traits\BootEventsTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permalinks';

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    public $primaryKey = "permalinkID";

    /**
     * Fields that can be filled in CRUD
     *
     * @var array $fillable
     */
    protected $fillable = ['permalinkID','url','controller','method','http_method'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

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
        Event::fire('permalink:construct', [$this]);
    }

    /**
     * Destruct model instance
     */
    public function __destruct()
    {
        Event::fire('permalink:destruct', [$this]);
    }
}
