<?php

namespace Accio\App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomFieldGroupModel extends Model{
    /**
     * Fields that can be filled in CRUD
     *
     * @var array $fillable
     */
    protected $fillable = ['customFieldGroupID','title','description','belongsTo','isActive','conditions','created_at','updated_at'];

    /**
     * Type of the columns
     * @var array
     */
    protected $casts = [
        'conditions' => 'array',
    ];

    /**
     * The primary key of the table
     *
     * @var string $primaryKey
     */
    protected $primaryKey = "customFieldGroupID";

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
    public $table = "custom_fields_groups";

    /**
     * Lang key that points to the multi language label in translate file
     * @var string
     */
    public static $label = "CustomFields.groupLabel";

    /**
     * Default permissions that will be listed in settings of permissions
     *
     * @var array $defaultPermissions
     */
    public static $defaultPermissions = ['create','read', 'update', 'delete'];

    /**
     * Relationship of the custom field group with the custom fields (Group has many fields)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fields(){
        return $this->hasMany('App\Models\CustomField', 'customFieldGroupID');
    }

    /**
     * Returns custom fields by group of a specific module/app
     *
     * @param string $module
     * @param string $formType
     * @param int $id
     * @param string $postType
     * @return array
     */
    public static function findGroups($module, $formType, $id = 0, $postType = ''){
        $customFieldGroups = self::with('fields')->get();
        $result = [];

        // loop throw all custom fields groups
        foreach ($customFieldGroups as $customFieldGroup){
            $conditionResult = [];
            // loop throw all custom fields group condition group
            foreach($customFieldGroup->conditions as $conditionGroup){
                $shouldBeShown = true;
                // loop throw conditions of each group
                foreach($conditionGroup as $condition){
                    // if wrong module
                    if($condition['app']['app'] != $module){
                        if($condition['app']['app'] == 'post'){
                            if($postType && $condition['app']['slug'] != $postType){
                                $shouldBeShown = false;
                                break;
                            }
                        }else{
                            $shouldBeShown = false;
                            break;
                        }
                    }

                    // if type is form
                    if($condition['app']['type'] == 'form'){
                        if($condition['operator']['value'] == "equals"){
                            if($condition['value']['value'] != "all"){
                                if($condition['value']['value'] != $formType){
                                    $shouldBeShown = false;
                                    break;
                                }
                            }
                        }else{
                            if($condition['value']['value'] == "all"){
                                $shouldBeShown = false;
                                break;
                            }
                        }
                    }

                    // if type is title
                    if($condition['app']['type'] == 'title'){
                        if($condition['app']['app'] == 'post-type'){
                            if($condition['operator']['value'] == "equals"){
                                if($condition['value']['value'] != $postType){
                                    $shouldBeShown = false;
                                    break;
                                }
                            }else{
                                if($condition['value']['value'] == $postType){
                                    $shouldBeShown = false;
                                    break;
                                }
                            }
                        }else{
                            if($condition['operator']['value'] == "equals"){
                                if($condition['value']['value'] != $id){
                                    $shouldBeShown = false;
                                    break;
                                }
                            }else{
                                if($condition['value']['value'] == $id){
                                    $shouldBeShown = false;
                                    break;
                                }
                            }
                        }
                    }

                    // TODO if type is status or role

                }
                $conditionResult[] = $shouldBeShown;
            }

            if(in_array(true, $conditionResult)){
                $result[] = $customFieldGroup;
            }
        }

        return $result;

    }
}
