<?php

class QueryHandler {

    protected $db;
    function __construct($db,$cache){ 
      $this->db = $db;
      $this->cache = $cache;
    }
    public function getHotelDetails($id) {
        $stmt = $this->db->prepare("SELECT h.id as hotelcode,
                                           h.hotel_name as HotelName,
                                           h.rating as HotelRating,
                                           h.sale_address as Address,
                                           c.name as CountryName,
                                           h.hotel_description as Description,
                                           CONCAT(h.lattitude,'|',h.longitude) as Map,
                                           h.sale_number as PhoneNumber,
                                           h.hotel_facilities as HotelFacilities,
                                           h.Image1,
                                           h.Image2,
                                           h.Image3,
                                           h.Image4
                                    FROM hotel_tbl_hotels h 
                                    inner join countries c ON c.id = h.country  
                                    WHERE h.id = ".$id."");
        // $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->fetch();

        $stmt1 = $this->db->prepare("select Hotel_Facility as HotelFacility from hotel_tbl_hotel_facility where id in (".$user['HotelFacilities'].") ");
        $stmt1->execute();
        $facilities = $stmt1->fetchAll();
         $user['HotelFacilities'] = array();
        foreach ($facilities as $key => $value) {
         $user['HotelFacilities']['HotelFacility'][] = $value['HotelFacility'];
        }

        $imgurl = 'http://dev.otelseasy.com/';
        
        // "uploads/gallery/',n.hotel_id,'/',h.Image1
        $user['ImageUrls']  = array(); 
        for ($i=1; $i <= 4 ; $i++) { 
          if ($user['Image'.$i]!="") {
            $user['ImageUrls']['ImageUrl'][] = $imgurl."uploads/gallery/".$user['hotelcode']."/".$user['Image'.$i];
          }
          unset($user['Image'.$i]);
        }
        return $user;
        
    }
    public function validateparametersavailablerooms($data) {
        $return = array();
        if(!isset($data['hotelcode']) || $data['hotelcode'] == '') {
            $return['hotelcode'] = 'Hotel Code is mandatory';
        }
        if(!isset($data['token']) || $data['token'] == '') {
            $return['token'] = 'token is mandatory';
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
    public function getxmlHotelDetails($data,$hotelcode) {
      $return  =array();
       $inp_arr_hotel = [
          "ResultIndex" => [
            "value" => $data['ResultIndex']
          ],
          "SessionId" => [
            "value" => $data['sessionid']
          ],
          "HotelCode" => [
            "value" => $data['hotelcode']
          ],
        ];
      $HotelInfo = $this->HotelDetails($inp_arr_hotel);

      if ($HotelInfo['Status']['StatusCode']=='01') {
        $return['hotelcode'] = $HotelInfo['HotelDetails']['@attributes']['HotelCode'];
        $return['HotelName'] = $HotelInfo['HotelDetails']['@attributes']['HotelName'];
        
        if ($HotelInfo['HotelDetails']['@attributes']['HotelRating']=="FiveStar") {
          $star = 5;
        } 
        if ($HotelInfo['HotelDetails']['@attributes']['HotelRating']=="FourStar") {
          $star = 4;
        } 
        if ($HotelInfo['HotelDetails']['@attributes']['HotelRating']=="ThreeStar") {
          $star = 3;
        } 
        if ($HotelInfo['HotelDetails']['@attributes']['HotelRating']=="TwoStar") {
          $star = 2;
        }
        if ($HotelInfo['HotelDetails']['@attributes']['HotelRating']=="OneStar") {
          $star = 1;
        }

        $return['HotelRating'] = $star;
        $return['Address'] = $HotelInfo['HotelDetails']['Address'];
        $return['CountryName'] = $HotelInfo['HotelDetails']['CountryName'];
        $return['Description'] = $HotelInfo['HotelDetails']['Description'];
        $return['Map'] = $HotelInfo['HotelDetails']['Map'];
        $return['PhoneNumber'] = $HotelInfo['HotelDetails']['PhoneNumber'];
        $return['HotelFacilities'] = $HotelInfo['HotelDetails']['HotelFacilities'];
        $return['ImageUrls'] = $HotelInfo['HotelDetails']['ImageUrls'];
      }
      return $return;
     
    }
    public function HotelDetails($arg){
          $this->responseTemplate(__FUNCTION__,$arg);
          return $this->result;
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
  private function responseTemplate($function,$arg=[]){
    if ($function=="AvailableHotelRooms") {
      $this->array_key_search($this->xmlstr_to_array($this->loadRequest($function,$arg)),'HotelRoomAvailabilityResponse');
    } else {
      $this->array_key_search($this->xmlstr_to_array($this->loadRequest($function,$arg)),$function.'Response');
    }
  }
  private function array_key_search($array,$key){
      foreach($array as $k => $v){
          if($k == $key){
              $this->result = $array[$k];
              break;
          } else {
              if(is_array($v)){
                  $this->array_key_search($v,$key);
              }
          }
      }

  }
  private function xmlstr_to_array($xmlstr) {
      $doc = new DOMDocument();
      $doc->loadXML($xmlstr);
      return $this->domnode_to_array($doc->documentElement);
  }
  private function domnode_to_array($node) {
        $output = array();
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
            case XML_ELEMENT_NODE:
                for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = $this->domnode_to_array($child);
                    if(isset($child->tagName)) {
                        $t = $child->tagName;
                        if(!isset($output[$t])) {
                            $output[$t] = array();
                        }
                        $output[$t][] = $v;
                    }
                    elseif($v) {
                        $output = (string) $v;
                    }
                }
                if(is_array($output)) {
                    if($node->attributes->length) {
                        $a = array();
                        foreach($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string) $attrNode->value;
                        }
                        $output['@attributes'] = $a;
                    }
                    foreach ($output as $t => $v) {
                        if(is_array($v) && count($v)==1 && $t!='@attributes') {
                            $output[$t] = $v[0];
                        }
                    }
                }
                break;
        }
        return $output;
  }
  public function loadRequest($action,$arr_value) {
    $stmt = $this->db->prepare("select * from xml_providers_tbl where Name = 'TBO'");
    $stmt->execute();
    $query = $stmt->fetchAll();


    $this->xml = new DOMDocument("1.0", "UTF-8");
    $xml_env = $this->xml->createElement("soap:Envelope");
    $xml_env->setAttribute("xmlns:soap", "http://www.w3.org/2003/05/soap-envelope");
    $xml_env->setAttribute("xmlns:hot", "http://TekTravel/HotelBookingApi");

    /*create header*/
    $xml_hed = $this->xml->createElement("soap:Header");
    $xml_hed->setAttribute("xmlns:wsa", "http://www.w3.org/2005/08/addressing");

    $xml_cred = $this->xml->createElement("hot:Credentials");
    $xml_cred->setAttribute("UserName", $query[0]['UserName']);
    $xml_cred->setAttribute("Password", $query[0]['password']);

    $xml_wsaa = $this->xml->createElement("wsa:Action", "http://TekTravel/HotelBookingApi/$action");
    $xml_wsat = $this->xml->createElement("wsa:To", $query[0]['url']);

    $xml_hed->appendChild($xml_cred);
    $xml_hed->appendChild($xml_wsaa);
    $xml_hed->appendChild($xml_wsat);

    $xml_env->appendChild($xml_hed);

    /*create body*/
    $xml_bdy = $this->xml->createElement("soap:Body");
    if ($action=="AvailableHotelRooms") {
      $xml_bdyreq= $this->xml->createElement("hot:HotelRoomAvailabilityRequest");
    } else {
      $xml_bdyreq= $this->xml->createElement("hot:$action"."Request");
    }
    

    foreach ($arr_value as $key => $value ) {
        $this->recursion($key,$value,$xml_bdyreq);
    }


    $xml_bdy->appendChild($xml_bdyreq);
    $xml_env->appendChild($xml_bdy);

    $this->xml->appendChild($xml_env);
    $request = $this->xml->saveXML();
    $location = $query[0]['url'];
    $action = "http://TekTravel/HotelBookingApi/$action";
    $restore = error_reporting(0);
    $result = '';
     try {
      $client = new SoapClient($query[0]['url']."?wsdl", ['exceptions' => true]);
      $this->result = $client->__doRequest($request, $location, $action, 2);
      $result = $this->result;
      // print_r(htmlentities($result));exit;
    } catch (SoapFault $exception) {
       return true;
    }
    return $result;
  }
  public function xml_currency_change($amount,$dc_type,$c_type) {
    $stmt1 = $this->db->prepare("SELECT amount FROM currency_update where currency_type = '".$dc_type."'");
    $stmt1->execute();
    $final = $stmt1->fetchAll();
    $dc_out = $final[0]['amount'];

    $dc_divided = $amount/$dc_out;

    $stmt2 = $this->db->prepare("SELECT amount FROM currency_update where currency_type = '".$c_type."'");
    $stmt2->execute();
    $final1 = $stmt2->fetchAll();
    $c_out = $final1[0]['amount'];

    $converted_amount = $c_out*$dc_divided;
    return number_format((float)$converted_amount, 2, '.', '');
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