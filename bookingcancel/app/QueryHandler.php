<?php

class QueryHandler {

    protected $db;
    function __construct($db){ 
      $this->db = $db;
    }
    public function validateparametersbookingcancel($data) {
      $return = array();
      if(!isset($data['ConfirmationNo']) || $data['ConfirmationNo'] == '') {
          $return['ConfirmationNo_error'] = 'ConfirmationNo is mandatory';
      }
      if(!isset($data['Remarks']) || $data['Remarks'] == '') {
          $return['Remarks_error'] = 'Remarks is mandatory';
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
    public function cancellationrequest($data,$agent_id) {
      $stmt = $this->db->prepare("select booking_flag from hotel_tbl_booking where booking_id='".$data['ConfirmationNo']."' and Created_By = ".$agent_id."");
      $stmt->execute();
      $query = $stmt->fetchAll();
      if (count($query)!=0) {
        if($query[0]['booking_flag']!=5) {
            $stmt = $this->db->prepare("update hotel_tbl_booking set booking_flag=5 where booking_id='".$data['ConfirmationNo']."' and Created_By = ".$agent_id."");
            $stmt->execute();
            return "process";
        } else {
            return "send";
        }
      } else {
        return "invalid";
      }
    }
    public function xmlcancellationrequest($data,$agent_id) {
      $return = array();
      $stmt = $this->db->prepare("select * from xml_hotel_booking  where ConfirmationNo='".$data['ConfirmationNo']."' and agent_id = ".$agent_id."");
      $stmt->execute();
      $query = $stmt->fetchAll();

      
      if (count($query)==0) {
        return $return;
      }

      if ($query[0]['bookingFlg']==5 || $query[0]['bookingFlg']==3) {
        $return['status'] = 'Failed';
        $return['Msg'] = 'Please try cancel status method.Cancellation Request already sent';
        return $return;
      }

      $inp_arr =[
          "ConfirmationNo"=>[
            "value" => $data['ConfirmationNo']
          ],
          "RequestType"=>[
            "value" => 'HotelCancel'
          ],
          "Remarks"=>[
            "value" => $data['Remarks']
          ]
      ];
      
      $xmlData =  $this->HotelCancel($inp_arr);

      if ($xmlData["Status"]['StatusCode']=="05") {
        $CancellationCharge = is_array($xmlData['CancellationCharge']) ? 0 : $xmlData['CancellationCharge'];
        $RefundAmount = is_array($xmlData['RefundAmount']) ? 0 : $xmlData['RefundAmount'];
        $ProviderStatus = $xmlData['RequestStatus'];
        $booking_flag = 5;
        $this->xmlCancelUpdate($query[0]['id'],$CancellationCharge,$RefundAmount,$ProviderStatus,$booking_flag,$data['Remarks'],$agent_id);
        $return['Status'] = $xmlData['RequestStatus'];
        $return['Msg'] = "This booking will be automatically cancelled in 24 hours. If not, please contact our Operations Team at Operations@otelseasy.com";
       
      } else if ($xmlData["Status"]['StatusCode']=="01") {
        $CancellationCharge = is_array($xmlData['CancellationCharge']) ? 0 : $xmlData['CancellationCharge'];
        $booking_flag = 3;
        $RefundAmount = is_array($xmlData['RefundAmount']) ? 0 : $xmlData['RefundAmount'];
        $ProviderStatus = $xmlData['RequestStatus'];
        $this->xmlCancelUpdate($query[0]['id'],$CancellationCharge,$RefundAmount,$ProviderStatus,$booking_flag,$data['Remarks'],$agent_id);
        $return['Status'] = $xmlData['RequestStatus'];
        $return['Msg'] = "Booking Cancelled Successfully";
      } else {
        $return['Status'] = "Failed";
        $return['Msg'] = "Booking Cancelled failed. please contact our Operations Team at Operations@otelseasy.com";
      }
      return $return;
    }
    public function HotelCancel($arg){
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
    public function xmlCancelUpdate($id,$CancellationCharge,$RefundAmount,$ProviderStatus,$booking_flag,$Remarks,$agent_id) {

      $sql = "UPDATE xml_hotel_booking SET CancellationCharge=?, RefundAmount=?, ProviderStatus=?,CancelledDate=?,
         bookingFlg=?,UpdatedDate=?,UpdatedBy=?,Remarks=? WHERE id=?";
      $datass = array(
         $CancellationCharge,
         $RefundAmount,
         $ProviderStatus,
         date('Y-m-d h:i:s'),
         $booking_flag,
         date('Y-m-d h:i:s'),
         $agent_id,
         $Remarks,
         $id
      );
      $this->db->prepare($sql)->execute($datass);
      return true;
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