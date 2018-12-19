<?php

namespace Accio\App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Accio\App\Traits;
use Spatie\Activitylog\Traits\LogsActivity;

class PermalinkModel extends Model
{

    use
      Traits\PermalinkTrait,
      LogsActivity,
      Cachable,
      Traits\BootEventsTrait,
      Traits\CollectionTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permalinks';

    /**
     * The primary key of the table.
     *
     * @var string $primaryKey
     */
    public $primaryKey = "permalinkID";

    /**
     * Fields that can be filled in CRUD.
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
     * Destruct model instance.
     */
    public function __destruct()
    {
        Event::fire('permalink:destruct', [$this]);
    }
}
