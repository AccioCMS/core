<?php
interface ElasticSearchInterface{
    public function setMapping() : void;
    public function getMapping() : array;
    public function isMapped()  : bool;
    public function hasElasticConnection()  : bool;
    public function getAllFromElastic(int $from = 0, int $size = 20)  : array;
    public function searchElastic(array $options)  : array;
    public function addItemOnElastic(string $index, array $item)  : bool;
    public function addItemsOnElastic(string $index, array $items)  : bool;
}