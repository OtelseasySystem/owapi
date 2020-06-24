<?php

class QueryHandler {

    protected $db;
    function __construct($db){ 
      $this->db = $db;
    }
    public function getHotelDetails($id) {
        $stmt = $this->db->prepare("SELECT * FROM hotel_tbl_hotels WHERE id = ".$id."");
        // $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->fetch();
        return $user;
        
    }
    public function validateparameters($data) {
        $return = array();
        if(!isset($data['cityname']) || $data['cityname'] == '') {
            $return['cityname'] = 'City name is mandatory';
        }
        if(!isset($data['countryname']) || $data['countryname'] == '') {
            $return['countryname'] = 'Country name is mandatory';
        }
        if(!isset($data['activitydate']) || $data['activitydate'] == '') {
            $return['activitydate'] = 'Activity date is mandatory';
        }
        if (isset($data['activitydate']) &&  date('Y-m-d',strtotime($data['activitydate'])) < date('Y-m-d')) {
            $return['activity_date_err'] = 'Activity date date must be equal or greater than the current date ('.date('d-m-Y').')';
        }
        if (isset($data['activitydate'])) {
          if (preg_match("/^([0-9]{2})-([0-9]{2})-([0-9]{4})$/",$data['activitydate'])) {
          } else {
            $return['activity_date_err'] = 'Invalid activity date';
          }
        }    
        if(empty($return)) {
            $response['status'] = "true";
            $response['message'] = "success";
        } else {
            $response['status'] = "false";
            $response['message'] = "failed";
            $response['error'] = $return;
        }
        return $response;
    }
    function getActivityList($data,$agent_id) {
      $outData = array();
      $search = '';
      $activitydate = date('Y-m-d',strtotime($data['activitydate'])); 
      $search = "b.name  = '".$data['cityname']."' ";
      
      if ($search!='') {
        $search = '('.$search.') AND ';
      }
    
      $imgurl = 'http://dev.otelseasy.com/';
      $agent_currency = 'AED';
      $arr = array('Type' => '3','date' => date('Y-m-d H:i:s'));
      $finarr = array_merge($arr,$data);
      $token = base64_encode(serialize($finarr));
      $activityList =  $this->db->prepare("select a.id as ActivityId,a.title as Activity,a.Type as TypeofActivity,MIN(c.Selling+c.AdultSelling) as MinRate,a.overview as Overview,a.address as Location,a.duration as Duration,a.inclusion as Inclusion,a.exclusion as Exclusion,a.terms as TermsandConditions, a.hours as OperationalHours,a.cancelPolicy as CancellationPolicy,a.childPolicy as ChildPolicy,concat('".$imgurl."uploads/package/',a.id,'/',a.image1) as ActivityImage,a.MinAge,a.MaxAge,'".$agent_currency."' as Currency from hotel_tbl_package a inner join states b on a.State=b.id inner join package_tbl_transfer c on c.activityid=a.id where b.name='".$data['cityname']."' and '".$activitydate."' BETWEEN a.fromDate and a.toDate and a.delflg = 1 group by a.id");
      //print_r($activityList);exit;
      $activityList->execute();
      $activities =  $activityList->fetchAll();

      return $activities;
    }
  public function recursion($key,$value,&$xml_elem) {

      $attr = (isset($value['attr'])) ? $value['attr'] : null;
      $value['value'] = (isset($value['value'])) ? $value['value'] : '';
      if (is_array($value['value'])) {
          $xml_bdyreqele = $this->xml->createElement("hot:$key");
          if ($attr) {
              foreach ($attr as $k => $v) {
                  $xml_bdyreqele->setAttribute($k, $v);
              }
          }
          foreach ($value['value'] as $key2 => $value2) {
                  
              if (is_numeric($key2)) {
                $this->recursion(array_keys($value2)[0],array_values($value2)[0],$xml_bdyreqele);
              } else {
                $this->recursion($key2,$value2,$xml_bdyreqele);
              }
          }
          $xml_elem->appendChild($xml_bdyreqele);
      } else {
          $xml_bdyreqele = $this->xml->createElement("hot:$key", htmlspecialchars($value['value']));
          if ($attr) {
              foreach ($attr as $k => $v) {
                  $xml_bdyreqele->setAttribute($k, $v);
              }
          }
          $xml_elem->appendChild($xml_bdyreqele);
      }
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
  public function UserAuthCheck($id) {
    $stmt1 = $this->db->prepare("SELECT id FROM hotel_tbl_agents WHERE id = ".$id." and api_status = 1 limit 1");
    $stmt1->execute();
    $final = $stmt1->fetchAll();
    return count($final);
  }
}