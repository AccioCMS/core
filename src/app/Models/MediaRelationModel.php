<?php

namespace Accio\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaRelationModel extends Model{
    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    protected $table = "media_relations";

    /**
     * The primary key of the table.
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "mediaRelationID";

    /**
     * Media that belong to a Media Relation.
     *
     * @return BelongsTo
     */
    public function media(){
        return $this->belongsTo('App\Models\Media','mediaID','mediaID');
    }
}
