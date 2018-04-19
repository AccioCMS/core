<?php
/**
 * The Model of MenuLinks configurations
 *
 * It defines model of relationships with MenuLinks
 * @author Jetmir Haxhisefa <jetmir.haxhisefa@manaferra.com>
 * @version 1.0
 */
namespace Accio\App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuLinkConfigModel extends Model{

    /**
     * Fields that can be filled in CRUD
     *
     * @var array $fillable
     */
    protected $fillable = ['menuLinkConfigID', 'menuLinkID', 'belongsTo', 'belongsToID', 'postIDs'];

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "menuLinkID";

    /**
     * How many rows to show in the pagination
     *
     * @var integer $rowsPerPage
     */
    public static $rowsPerPage = 100;

    /**
     * The table associated with the model.
     *
     * @var string $table
     */
    public $table = "menu_link_config";

    /**
     * Define if timestamps should be created after an insert/update
     *
     * @var boolean $timestamps
     */
    public $timestamps = false;

    /**
     * Define relation between menu link config and menu link
     * */
    public function menuLink(){
        return $this->belongsTo('App\Models\MenuLink', 'menuLinkID');
    }
}
