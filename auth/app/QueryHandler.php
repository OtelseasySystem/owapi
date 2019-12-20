<?php

class QueryHandler {

    protected $db;
    function __construct($db){ 
      $this->db = $db;
    }
    public function createGUID() { 
    
	    // Create a token
	    $token      = $_SERVER['HTTP_HOST'];
	    $token     .= $_SERVER['REQUEST_URI'];
	    $token     .= uniqid(rand(), true);
	    
	    // GUID is 128-bit hex
	    $hash        = strtoupper(md5($token));
	    
	    // Create formatted GUID
	    $guid        = '';
	    
	    // GUID format is XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX for readability    
	    $guid .= substr($hash,  0,  8) . 
	         '-' .
	         substr($hash,  8,  4) .
	         '-' .
	         substr($hash, 12,  4) .
	         '-' .
	         substr($hash, 16,  4) .
	         '-' .
	         substr($hash, 20, 12);
	            
	    return $guid;

	}
	public function getRealIPAddr() {
       //check ip from share internet

       if (!empty($_SERVER['HTTP_CLIENT_IP'])) 
       {
           $ip=$_SERVER['HTTP_CLIENT_IP'];
       }
       //to check ip is pass from proxy

       elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))  
       {
           $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
       }
       else
       {
           $ip=$_SERVER['REMOTE_ADDR'];
       }
       return $ip;
    }
}