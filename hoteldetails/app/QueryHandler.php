<?php

class QueryHandler {

    protected $db;
    function __construct($db,$cache){ 
      $this->db = $db;
      $this->cache = $cache;
    }
    public function getHotelDetails($data) {
      print_r($data);exit;
        // $stmt = $this->db->prepare("SELECT * FROM hotel_tbl_hotels WHERE id = ".$id."");
        // // $stmt->bind_param("i", $id);
        // $stmt->execute();
        // $user = $stmt->fetch();
        // return $user;
        
    }
}