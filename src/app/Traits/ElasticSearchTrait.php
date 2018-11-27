<?php
namespace Accio\App\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Input;

trait ElasticSearchTrait{

    protected $elasticQuery = [];

    /**
     * Return mapping.
     *
     * @return array
     */
    public function getMapping() : array{
        return $this->elastic->indices()->getMapping();
    }

    /**
     * Get all data from elastic index.
     *
     * @param string $sortBy
     * @param string $sortType
     * @return mixed
     */
    public function getAllES(string $sortBy = "", string $sortType = "ASC"){
        $data = $this->getESItems(10000, 0, $sortBy, $sortType);
        return $data['items'];
    }

    /**
     * Get data as pagination from elastic index.
     *
     * @param int $size
     * @param string $sortBy
     * @param string $sortType
     * @param int|null $page
     * @return LengthAwarePaginator
     */
    public function paginateES(int $size = 20, string $sortBy = "", string $sortType = "ASC", int $page = null) : LengthAwarePaginator {
        if(!$page){
            $page = (Input::get("page") ? Input::get("page") : 1);
        }

        $from = (($page * $size) - $size);

        $data = $this->getESItems($size, $from, $sortBy, $sortType);
        return new LengthAwarePaginator($data['items'], $data['elasticData']['hits']['total'], $size, $page);
    }

    /**
     * Used to get data and return them as Elequent.
     *
     * @param $size
     * @param $from
     * @param string $sortBy
     * @param string $sortType
     * @return array
     */
    private function getESItems($size, $from, string $sortBy = "", string $sortType = "ASC"){
        $query = [
            "match_all" => (object) []
        ];

        if($this->elasticQuery){
            $query = [
                "bool" => [
                    "must" => $this->elasticQuery
                ]
            ];
        }

        $params = [
            "index" => $this->table,
            "type" => "_doc",
            "body" => [
                "size" => $size,
                "from" => $from,
                "query" => $query
            ]
        ];

        if($sortBy){
            $params['body']['sort'] = [
                [
                    $sortBy => [ "order" => strtoupper($sortType) ]
                ]
            ];
        }

        $elasticData = $this->elastic->search($params);
        $items = collect();
        $count = 0;
        foreach ($elasticData['hits']['hits'] as $item){
            $data = $item["_source"];
            $data[$this->primaryKey] = $item["_id"];
            $items->put($count++, $this->toElequent($data));
        }

        return [
            "items" => $items,
            "elasticData" => $elasticData,
        ];
    }

    /**
     * Multi match, match query in multiple fields
     *
     * @param string $query
     * @param array $fields
     * @param string $fuzziness
     * @return $this
     */
    public function whereMultiMatch(string $query, array $fields, $fuzziness = "auto") {
        $param = [
            "multi_match" => [
                "query" => $query,
                "fields" => $fields,
                "fuzziness" => $fuzziness
            ]
        ];
        $this->elasticQuery[] = $param;
        return $this;
    }

    /**
     * Where query matches field
     *
     * @param string $field
     * @param string $query
     * @param string $operator
     * @param int $fuzziness
     * @return $this
     */
    public function whereMatch(string $field, string $query, $operator = "or", $fuzziness = 0){
        $param = [
            "match" => [
                $field => [
                    "query" => $query,
                    "operator" => $operator,
                    "fuzziness" => $fuzziness
                ]
            ]
        ];

        $this->elasticQuery[] = $param;
        return $this;
    }

    /**
     * Where field uses multiple terms
     *
     * @param string $field
     * @param array $query
     * @return $this
     */
    public function whereTerms(string $field, array $query){
        $param = [
            "terms" => [
                $field => $query
            ]
        ];

        $this->elasticQuery[] = $param;
        return $this;
    }

    /**
     * Where field uses single term
     *
     * @param string $field
     * @param $term
     * @return $this
     */
    public function whereTerm(string $field, $term){
        $param = [
            "term" => [
                $field => $term
            ]
        ];

        $this->elasticQuery[] = $param;
        return $this;
    }

    /**
     * Applies range search in query
     *
     * @param string $field
     * @param null $gt
     * @param null $lt
     * @param null $format
     * @param string $gtConfig
     * @param string $ltConfig
     * @return $this
     */
    public function whereRange(string $field, $gt = null, $lt = null, $format = null, $gtConfig = "gte", $ltConfig = "lte"){
        if($gt && $lt){
            $field = [];

            if($gt){
                $field[$gtConfig] = $gt;
            }

            if($lt){
                $field[$ltConfig] = $lt;
            }

            if($format){
                $field["format"] = $format;
            }

            $param = [
                "range" => [
                    $field
                ]
            ];

            $this->elasticQuery[] = $param;
        }

        return $this;
    }



    public function addItemOnES(array $item)  : bool{

    }

    public function addItemsOnElastic(string $index, array $items)  : bool{

    }

    public function hasElasticConnection() : bool{

    }

    public function deleteWithIDES($id){

    }

    /**
     * Used to change data to the selected elequent
     *
     * @param array $attributes
     * @return ElasticSearchTrait
     */
    private function toElequent(array $attributes){
        $obj = new static();
        $casts = $obj->casts;
        $obj->casts = [];
        $obj->setRawAttributes($attributes);
        $obj->casts = $casts;

        return $obj;
    }
}