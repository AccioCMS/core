<?php

namespace Accio\App\Http\Controllers\Api;

use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;

class MainController extends Controller
{

    /**
     * @var array
     */
    protected $select;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var string
     */
    protected $orderByColumn;

    /**
     * @var
     */
    protected $orderByType;

    /**
     * @var int $paginate Number of results to be returned. -1 for all
     */
    protected $paginate;

    private $defaultQueryParameters = [
      'select' => 'array',
      'limit' => 'integer',
      'orderByColumn' => 'string',
      'orderByType' => 'string',
      'paginate' => 'integer'
    ];

    /**
     * Validate default query parameters..
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validateQuery()
    {
        return Validator::make(request()->all(), $this->defaultQueryParameters);
    }

    /**
     * Get query values.
     *
     * @return $this
     */
    protected function queryValues()
    {
        foreach($this->defaultQueryParameters as $parameter => $validator){
            $value = request($parameter);
            if($value) {
                $this->{$parameter} = $value;
            }
        }

        return $this;
    }

    /**
     * Setup error response.
     *
     * @param  mixed  $errors
     * @param  string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error($errors = null, string $message = '')
    {

        if(!$errors) {
            $errors = ['Your request has not been validated by the system. Please check your request URL and parameters!'];
        }

        // make it array
        else if(!is_array($errors)) {
            $errors = [$errors];
        }

        return response()->json(['errors' => $errors], 400);
    }
}