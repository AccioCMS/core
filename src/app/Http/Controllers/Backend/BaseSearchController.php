<?php

namespace Accio\App\Http\Controllers\Backend;

use Accio\Support\Facades\Search;


class BaseSearchController extends MainController{
    // Check authentification in the constructor
    public function __construct(){
        parent::__construct();
        $this->middleware('auth');
    }

    public function search($tableName,$searchTerm){
        return Search::searchByTerm($tableName,$searchTerm);
    }

    public function advancedSearch(){

    }
}
