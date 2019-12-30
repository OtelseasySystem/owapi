<?php

class QueryHandler {

    protected $db;
    function __construct($db,$cache){ 
      $this->db = $db;
      $this->cache = $cache;
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
        if(!isset($data['location']) || $data['location'] == '') {
            $return['location'] = 'Location is mandatory';
        }
        if(!isset($data['cityname']) || $data['cityname'] == '') {
            $return['cityname'] = 'City name is mandatory';
        }
        if(!isset($data['countryname']) || $data['countryname'] == '') {
            $return['countryname'] = 'Country name is mandatory';
        }
        if(!isset($data['nationality']) || $data['nationality'] == '') {
            $return['nationality'] = 'Nationality is mandatory';
        }
        if(!isset($data['check_in']) || $data['check_in'] == '') {
            $return['check_in'] = 'Check in is mandatory';
        }
        if(!isset($data['check_out']) || $data['check_out'] == '') {
            $return['check_out'] = 'Check Out is mandatory';
        }
        if(!isset($data['no_of_rooms']) || $data['no_of_rooms'] == '') {
            $return['no_of_rooms'] = 'Number of rooms is mandatory';
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
    public function validateparametershotelbook($data) {

        $response = array(); 
        if(!isset($data['hotelcode']) || $data['hotelcode'] == '') {
            $response['hotelcode'] = 'Hotel Code is mandatory';
        }
        if(!isset($data['token']) || $data['token'] == '') {
            $response['token'] = 'token is mandatory';
        }
        if(empty($response)) {
            $return['status'] = "true";
            $return['message'] = "success";
        } else {
            $return['status'] = "false";
            $return['message'] = "failed";
            $return['error'] = $response;
        }
        return $return;
    }
    public function validateparametershotelbook1($data) {

        $response = array(); 
        if(!isset($data['hotelcode']) || $data['hotelcode'] == '') {
            $response['hotelcode'] = 'Hotel Code is mandatory';
        }
        if(!isset($data['token']) || $data['token'] == '') {
            $response['token'] = 'token is mandatory';
        }
        if(isset($data['token'])) {
          for($i=0;$i<$data['no_of_rooms'];$i++){
              for($j=0;$j < ($data['adults'][$i]);$j++) {
                  if(!isset($data['Room'.($i+1).'AdultTitle'][$j]) || $data['Room'.($i+1).'AdultTitle'][$j] == '') {
                      $response['adult_detail_error_title'][$i][$j] = 'Room'.($i+1).'AdultTitle'.($j+1).' is missing';
                  }
                  if(!isset($data['Room'.($i+1).'AdultFirstname'][$j]) || $data['Room'.($i+1).'AdultFirstname'][$j] == '') {
                      $response['adult_detail_error_firstname'][$i][$j] = 'Room'.($i+1).'AdultFirstname'.($j+1).' is missing';
                  }
                  if(!isset($data['Room'.($i+1).'AdultLastname'][$j]) || $data['Room'.($i+1).'AdultLastname'][$j] == '') {
                      $response['adult_detail_error_lastname'][$i][$j] = 'Room'.($i+1).'AdultLastname'.($j+1).' is missing';
                  }
                  if(!isset($data['Room'.($i+1).'AdultAge'][$j]) || $data['Room'.($i+1).'AdultAge'][$j] == '') {
                      $response['adult_detail_error_age'][$i][$j] = 'Room'.($i+1).'AdultAge'.($j+1).' age details missing';
                  }
              }
              for($j=0;$j<$data['child'][$i];$j++) {
                  if(!isset($data['Room'.($i+1).'ChildTitle'][$j]) || $data['Room'.($i+1).'ChildTitle'][$j] == '') {
                      $response['child_detail_error_title'][$j] = 'Room'.($i+1).'ChildTitle'.($j+1).' is missing';
                  }
                  if(!isset($data['Room'.($i+1).'ChildFirstname'][$j]) || $data['Room'.($i+1).'ChildFirstname'][$j] == '') {
                      $response['child_detail_error_firstname'][$j] = 'Room'.($i+1).'ChildFirstname'.($j+1).' is missing';
                  }
                  if(!isset($data['Room'.($i+1).'ChildLastname'][$j]) || $data['Room'.($i+1).'ChildLastname'][$j] == '') {
                      $response['child_detail_error_lastname'][$j] = 'Room'.($i+1).'ChildLastname'.($j+1).' is missing';
                  }
                  if(!isset($data['Room'.($i+1).'ChildAge'][$j]) || $data['Room'.($i+1).'ChildAge'] == '') {
                      $response['child_detail_error_age'][$j] = 'Room'.($i+1).'ChildAge'.($j+1).' is missing';
                  }
              }
              if(!isset($data['RoomIndex'][$i]) || $data['RoomIndex'][$i] == '') {
                  $response['RoomIndex['.$i.']'] = 'RoomIndex '.($i).' is required';
              } else {
                  $roomindex = explode('-',$data['RoomIndex'][$i]);
                  if(!isset($roomindex[0]) || !isset($roomindex[1])) {
                    $response['RoomIndex['.$i.'] format'] = 'RoomIndex '.($i).' is should be in the contractId - roomId format(eg:CON010-348)';
                  }
              }
          }
        }
        if(empty($response)) {
            $return['status'] = "true";
            $return['message'] = "success";
        } else {
            $return['status'] = "false";
            $return['message'] = "failed";
            $return['error'] = $response;
        }
        return $return;
    }
    public function bookingreview($data) {
      $return = array();
      $response = array();

      $checkin_date=date_create($data['check_in']);
      $checkout_date=date_create($data['check_out']);
      $no_of_days=date_diff($checkin_date,$checkout_date);
      $tot_days = $no_of_days->format("%a");
      $result = array();
      for($i = 0; $i < $tot_days; $i++) {
        $result[1+$i]['day'] = date('l', strtotime($data['check_in']. ' + '.$i.' days'));
        $result[1+$i]['date'] = date('m/d/Y', strtotime($data['check_in']. ' + '.$i.'  days'));
      }  
      for ($i=0; $i < $data['no_of_rooms']; $i++) { 
        $roomindex = explode('-',$data['RoomIndex'][$i]);
        if(isset($roomindex[1])) {
          $roomid[$i] = $roomindex[1];
        } else {
          $roomid[$i] = "";
        }
        if(isset($roomindex[0])) {
          $contractid[$i] = $roomindex[0];
        } else {
           $contractid[$i] = "";
        }
        $contractBoardCheck = $this->contractBoardCheck($contractid[$i]);
        for ($j=1; $j <=$tot_days ; $j++) {
          $result[$j]['amount'] = $this->special_offer_amount($result[$j]['date'],$roomid[$i],$data['hotelcode'],$contractid[$i]);
          $result[$j]['roomName'] = $this->roomnameGET($roomid[$i],$data['hotelcode']);
          $return[$j]['date']= date('d/m/Y' ,strtotime($result[$j]['date']));
          $return[$j]['room'] = $result[$j]['roomName'];
          $return[$j]['boardname'] = $contractBoardCheck[0]['board'];
          $return[$j]['amount'] = $result[$j]['amount'];

          }
          $response['room'.($i+1)] =$return; 
        }
      return $response;
    }
    public function special_offer_amount($date,$room_id,$hotel_id,$contract_id) {
      $date = date('Y-m-d', strtotime($date));
      $stmt = $this->db->prepare('SELECT amount FROM hotel_tbl_allotement WHERE room_id ="'.$room_id.'" and hotel_id ="'.$hotel_id.'" and allotement_date="'.$date.'" and contract_id="'.$contract_id.'"');
      $stmt->execute();
      $rooms = $stmt->fetchAll();

      if (count($rooms)!=0) {
        $amount = $rooms[0]['amount'];
      } else {
        $amount = 0; 
      }
      return $amount;
    }
    public function roomnameGET($room_id,$hotel_id) {
        $stmt = $this->db->prepare('SELECT CONCAT(a.room_name," ",b.Room_Type) as name FROM hotel_tbl_hotel_room_type a inner join  hotel_tbl_room_type b on b.id=a.room_type WHERE a.id ="'.$room_id.'" and a.hotel_id ="'.$hotel_id.'"');
        $stmt->execute();
        $query = $stmt->fetchAll();
        if (count($query)!=0) {
            $name = $query[0]['name'];   
        } else {
           $name = ''; 
        } 
        return $name;
    }
    public function DateWisediscount($date,$hotel_id,$room_id,$contract_id,$type,$checkIn,$checkOut,$status='false') {
      $chIn = date_create($checkIn);
      $chOut = date_create($checkOut);
      $noOfDays=date_diff($chIn,$chOut);
      $totalDays = $noOfDays->format("%a");
      $checkin_date=date_create($date);
      $checkout_date=date_create(date('Y-m-d'));
      $no_of_days=date_diff($checkin_date,$checkout_date);
      $tot_days = $no_of_days->format("%a");
      $return['discount'] = 0;
      $return['Extrabed'] = 0;
      $return['General'] = 0;
      $return['Board'] = 0;
      $hotelidCheck = array();
      $contractCheck = array();
      $roomCheck = array();
      $BlackoutDateCheck = array();
      $query = array();
      if ($status=='false' && $room_id!="" && $contract_id!="") {
        $stmt = $this->db->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND
        FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "MLOS" AND numofnights <= '.$totalDays.') AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "MLOS" AND numofnights <= '.$totalDays.')');
        $stmt->execute();  
        $query = $stmt->fetchAll();
        if (count($query)==0) {
            $stmt = $this->db->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND
            FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "")');
            $stmt->execute();  
            $query = $stmt->fetchAll();
        }
        if (count($query)==0) {
            $stmt = $this->db->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND
            FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND discount_type = "EB") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND discount_type = "EB")');
            $stmt->execute();  
            $query = $stmt->fetchAll();
        }
        if (count($query)==0) {
            $stmt = $this->db->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND
                FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'") AND Bkbefore < '.$tot_days.' AND discount_type = "REB") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'") AND Bkbefore < '.$tot_days.' AND discount_type = "REB")');
            $stmt->execute();  
            $query = $stmt->fetchAll();
        }
        if (count($query)!=0) {
            $BlackoutDate = explode(",", $query[0]['BlackOut']); 
            if($query[0]['BlackOut']!="")  {
              foreach ($BlackoutDate as $key0 => $value0) {
                  if ($value0==$date) {
                      $BlackoutDateCheck[$key0] = 1;
                  }
              }
            }
            if (array_sum($BlackoutDateCheck)==0) {
                $return['discountType'] = 'discount';
                if ($query[0]['discount_type']!="") {
                    $return['discountType'] = $query[0]['discount_type'];
                }

                if ($query[0]['discountCode']!="") {
                  $return['discountCode'] = $query[0]['discountCode'];
                } else {
                  $stmt1 = $this->db->prepare('select BookingCode from hotel_tbl_contract where contract_id = "'.$contract_id.'"');
                  $stmt1->execute();  
                  $BookingCode = $stmt1->fetchAll();
                  $return['discountCode'] = $BookingCode[0]['BookingCode'];
                }
                
                $return['discount'] = $query[0]['discount'];
                $return['Extrabed'] = $query[0]['Extrabed'];
                $return['General'] = $query[0]['General'];
                $return['Board'] = $query[0]['Board'];
             }
        }
      }
      return $return;
    }
    public function Alldiscount($startdate,$enddate,$hotel_id,$room_id,$contract_id,$type) {
      $checkin_date=date_create($startdate);
      $checkout_date=date_create($enddate);
      $no_of_days=date_diff($checkin_date,$checkout_date);
      $tot_days = $no_of_days->format("%a");
      $discount['stay'] = 0;
      $discount['pay'] = 0;
      $discount['dis'] = 'false';
      $hotelidCheck = array();
      $contractCheck = array();
      $roomCheck = array();
      $BlackoutDateCheck = array();
      $query = array();
      if($room_id!="" && $contract_id!="") {
        $stmt = $this->db->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1  AND
        FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$startdate.'" AND Styto >= "'.$startdate.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND stay_night <= '.$tot_days.'  AND discount_type = "stay&pay") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$startdate.'" AND Styto >= "'.$startdate.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND stay_night <= '.$tot_days.' AND discount_type = "stay&pay" order by stay_night desc) order by stay_night desc');
        $stmt->execute();  
        $query = $stmt->fetchAll(); 

        if (count($query)!=0) {
            if($query[0]['BlackOut']!="")  {
              $BlackoutDate = explode(",", $query[0]['BlackOut']);
              for ($j=0; $j < $tot_days ; $j++) { 
                $dates[$j] =  date('Y-m-d', strtotime($startdate. ' + '.$j.'  days'));
                  if (is_numeric(array_search($dates[$j],$BlackoutDate))) {
                      $BlackoutDateCheck[] = 1;              
                  }
              }
            }
            if (array_sum($BlackoutDateCheck)==0) {
              $discount['stay'] = $query[0]['stay_night'];
              $discount['pay'] = $query[0]['pay_night'];
              $discount['dis'] = 'true';
              $discount['type'] = $query[0]['discount_type'];
              $discount['discountCode'] = $query[0]['discountCode'];
              $discount['Extrabed'] = $query[0]['Extrabed'];
              $discount['General'] = $query[0]['General'];
              $discount['Board'] = $query[0]['Board'];
            }
          }
        }
      return $discount;
    }
    public function contractBoardCheck($contract_id) {
        $stmt = $this->db->prepare("SELECT board FROM hotel_tbl_contract WHERE contract_id = '".$contract_id."'");
        $stmt->execute();  
        $query = $stmt->fetchAll(); 
        if (count($query)!=0) {
          return $query;
        } else {
          return null;
        }
    }
    public function general_tax($id) {
        $stmt = $this->db->prepare('SELECT tax_percentage FROM hotel_tbl_contract WHERE hotel_id = "'.$id.'"');
        $stmt->execute();  
        $query = $stmt->fetchAll();

        if (count($query)!=0) {
            return $query[0]['tax_percentage'];
        }
        return 0;
    }
    public function get_CancellationPolicy_contractConfirm($request,$contract_id,$room_id,$hotel_id) {
        $checkin_date=date_create($request['check_in']);
        $checkout_date=date_create($request['check_out']);
        $no_of_days=date_diff($checkin_date,$checkout_date);
        $tot_days = $no_of_days->format("%a");

        $refund=array();
        $stmt = $this->db->prepare("SELECT id FROM hotel_tbl_contract WHERE contract_id = '".$contract_id."' AND nonRefundable = 1");

        $stmt->execute();  
        $refund = $stmt->fetchAll(); 
        if(count($refund)!=0) {
          $disNRFVal = array();
          for ($i=0; $i < $tot_days ; $i++) {
              $dateOut = date('Y-m-d', strtotime($request['check_in']. ' + '.$i.'  days'));
              $stmt = $this->db->prepare("SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND FIND_IN_SET('".$dateOut."',BlackOut)=0 AND NonRefundable = 1 AND  FIND_IN_SET(".$hotel_id." ,hotelid) > 0 AND FIND_IN_SET(".$room_id.",room) > 0 AND FIND_IN_SET('".$contract_id."',contract) > 0 AND ((Styfrom <= '".$dateOut."' AND Styto >= '".$dateOut."'  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF('".$dateOut."','".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS') OR (Styfrom <= '".$dateOut."' AND Styto >= '".$dateOut."'  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF('".$dateOut."','".date('Y-m-d')."')  AND discount_type = '')  OR (Styfrom <= '".$dateOut."' AND Styto >= '".$dateOut."'  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= '".$dateOut."' AND Styto >= '".$dateOut."' AND Bkbefore < DATEDIFF('".$dateOut."','".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1");


              $stmt->execute();  
              $query = $stmt->fetchAll(); 
              if (count($query)!=0) {
                 $disNRFVal[$i] = 1;
              }
          }

          if (count($refund)!=0) {
            $data[0]['msg'] = "This booking is Nonrefundable";
            $data[0]['percentage'] = 100;
            $data[0]['daysInAdvance'] = 0;
            $data[0]['application'] = 'NON REFUNDABLE';
            $data[0]['daysFrom'] = '365';
            $data[0]['daysTo'] = '0';
          } else if(count($disNRFVal)!=0) {
            $data[0]['msg'] = "This booking is Nonrefundable";
            $data[0]['percentage'] = 100;
            $data[0]['daysInAdvance'] = 0;
            $data[0]['application'] = 'NON REFUNDABLE';
            $data[0]['daysFrom'] = '365';
            $data[0]['daysTo'] = '0';
          } else {
            $stmt = $this->db->prepare("SELECT CONCAT(a.room_name,' ',b.Room_Type) as Name FROM hotel_tbl_hotel_room_type a INNER JOIN hotel_tbl_room_type b ON b.id = a.room_type WHERE a.id = '".$room_id."'");
            $stmt->execute();  
            $roomType = $stmt->fetchAll();

            $start=date_create(date('m/d/Y'));
            $end=date_create($request['check_in']);
            $nod=date_diff($start,$end);
            $tot_days1 = $nod->format("%a");

            for ($i=0; $i < $tot_days ; $i++) {
              $date[$i] = date('Y-m-d', strtotime($request['check_in']. ' + '.$i.'  days'));
              $stmt = $this->db->prepare("SELECT * FROM hotel_tbl_cancellationfee WHERE '".$date[$i]."' BETWEEN fromDate AND toDate AND contract_id = '".$contract_id."'  AND FIND_IN_SET('".$room_id."', IFNULL(roomType,'')) > 0 AND hotel_id = '".$hotel_id."' AND daysTo <= '".$tot_days1."' order by daysFrom desc");
                $stmt->execute();  
                $CancellationPolicyCheck[$i] = $stmt->fetchAll(); 
              if (count($CancellationPolicyCheck[$i])!=0) {
                  foreach ($CancellationPolicyCheck[$i] as $key => $value) {
                    
                    $data[$key]['daysFrom'] = $value['daysFrom'];
                    $data[$key]['daysTo'] = $value['daysTo'];

                    if ($value['daysFrom']==0) {
                      $daysInAdvance = 'your check-in date';
                    } else if($value['daysFrom']==1) {
                      $daysInAdvance = 'within 24 hours of your check-in';
                    } else {
                      $daysInAdvance = 'within '.$value['daysFrom'].' days of your check-in';
                    }
                    $data[$key]['percentage'] = $value['cancellationPercentage'];
                    $data[$key]['application'] = $value['application'];
                    
                    if ($value['application']=="FIRST NIGHT") {
                      $data[$key]['msg'] = 'If you cancel '.$daysInAdvance.',you will pay '.$value['cancellationPercentage'].'% of one night stay with supplementary charges no matter the number of stay days.';
                    } else if ($value['application']=="STAY") {
                        $data[$key]['msg'] = 'If you cancel '.$daysInAdvance.', you will pay '.$value['cancellationPercentage'].'% of the booking amount.';
                    } else {
                      $data[$key]['msg'] = 'If you cancel '.$daysInAdvance.',  Cancellation charge is free .';
                    }

                  }
                } 
            }
          } 
          return $data;
        } else {
          return null;
        }
        
    }
    public function get_policy_contract($hotel_id,$contract_id){
        $stmt = $this->db->prepare("SELECT Important_Remarks_Policies,Important_Notes_Conditions,cancelation_policy FROM hotel_tbl_policies WHERE hotel_id ='".$hotel_id."' and contract_id = '".$contract_id."'");
        $stmt->execute();  
        $query = $stmt->fetchAll();
        if (count($query)!=0) {
          return $query;
        } else {
          return null;
        }
    }
    public function get_PaymentConfirmextrabedAllotment($request,$hotel_id,$contract_id,$room_id,$index) {
        $extrabedAmount  = array();
        $extraBedtotal  = array();
        $exrooms = array();
        $extrabedType = array();
        $stmt = $this->db->prepare("SELECT tax_percentage,max_child_age,board FROM hotel_tbl_contract WHERE hotel_id= '".$hotel_id."' and contract_id = '".$contract_id."'");
        $stmt->execute();  
        $row_values = $stmt->fetchAll();
        
        $tax = $row_values[0]['tax_percentage'];
        $max_child_age = $row_values[0]['max_child_age'];
        $contract_board = $row_values[0]['board'];
        $stmt = $this->db->prepare("SELECT occupancy,occupancy_child,standard_capacity,max_total FROM hotel_tbl_hotel_room_type WHERE hotel_id= '".$hotel_id."' and id = '".$room_id."'");
        $stmt->execute();  
        $Rmrow_values = $stmt->fetchAll();
        $occupancyAdult = $Rmrow_values[0]['occupancy'];
        $occupancyChild = $Rmrow_values[0]['occupancy_child'];
        $standard_capacity = $Rmrow_values[0]['standard_capacity'];
        $max_capacity = $Rmrow_values[0]['max_total'];
        $Room_Type = $room_id;
        $start_date = $request['check_in'];
        $end_date = $request['check_out'];
        $checkin_date=date_create($start_date);
        $checkout_date=date_create($end_date);
        $no_of_days=date_diff($checkin_date,$checkout_date);
        $tot_days = $no_of_days->format("%a");
        for($i = 0; $i < $tot_days; $i++) {
            /*Extrabed allotment start*/
            $date[$i] = date('Y-m-d', strtotime($start_date. ' + '.$i.'  days'));
            if ($contract_board=="BB") {
                $contract_boardRequest = array('Breakfast');
            } else if($contract_board=="HB") {
                $contract_boardRequest = array('Breakfast','Dinner');
            } else if($contract_board=="FB") {
                $contract_boardRequest = array('Breakfast','Dinner','Lunch');
            } else {
                $contract_boardRequest = array();
            }
            $implodeboardRequest = implode("','", $contract_boardRequest);
            $stmt = $this->db->prepare("SELECT * FROM hotel_tbl_extrabed WHERE '".$date[$i]."' BETWEEN from_date AND to_date AND contract_id = '".$contract_id."' AND  hotel_id = '".$hotel_id."' AND FIND_IN_SET('".$Room_Type."', IFNULL(roomType,'')) > 0");
            $stmt->execute();  
            $extrabedallotment[$i] = $stmt->fetchAll();

            $boardalt[$i] = array();
            if (count($extrabedallotment[$i])!=0) {
                foreach ($extrabedallotment[$i] as $key15 => $value15) {

                    if (($request['adults'][$index]+$request['child'][$index]) > $standard_capacity) {
                        if (isset($request['Room'.($index+1).'ChildAge'])) {
                            foreach ($request['Room'.($index+1).'ChildAge'] as $key18 => $value18) {
                              if ($max_child_age < $value18) {
                                $extrabedAmount[$i][$index][] =  $value15['amount'];
                                $exrooms[$i][$index][] = $index+1;
                                $extrabedType[$i][$index][] =  'Adult Extrabed';
                              } else {
                                if ($value15['ChildAmount']!=0 && $value15['ChildAmount']!="") {
                                    if ($value15['ChildAgeFrom'] <= $value18 && $value15['ChildAgeTo'] >= $value18) {
                                      $extrabedAmount[$i][$index][$key18] =  $value15['ChildAmount'];
                                      $extrabedType[$i][$index][$key18] =  'Child Extrabed';
                                      $exrooms[$i][$index][$key18] = $index+1;
                                    }
                                } else {
                                    $stmt = $this->db->prepare("SELECT * FROM hotel_tbl_boardsupplement WHERE '".$date[$i]."' BETWEEN fromDate AND toDate AND contract_id = '".$contract_id."' AND board IN ('".$implodeboardRequest."') AND FIND_IN_SET('".$Room_Type."', IFNULL(roomType,'')) > 0");
                                    $stmt->execute();  
                                    $boardalt[$i] = $stmt->fetchAll();
                                    
                                    if (count($boardalt[$i])!=0) {
                                        foreach ($boardalt[$i] as $key21 => $value21) {
                                          if ($value21['startAge'] <= $value18 && $value21['finalAge'] >= $value18) {
                                            $extrabedAmount[$i][$index][$key21] =  $value21['amount'];
                                            $exrooms[$i][$index][$key18] = $index+1;
                                            $extrabedType[$i][$index][$key21] =  'Child '.$value21['board'];
                                          }
                                        }
                                    }
                                } 
                              }
                            } 
                        }
                        if ($request['adults'][$index] > $standard_capacity) {
                            $extrabedAmount[$i][$index][] =  $value15['amount'];
                            $exrooms[$i][$index][] = $index+1;
                            $extrabedType[$i][$index][] =  'Adult Extrabed';
                        }
                    }
                }
            }
            if (count($extrabedallotment[$i])==0) {
                $stmt = $this->db->prepare("SELECT * FROM hotel_tbl_boardsupplement WHERE '".$date[$i]."' BETWEEN fromDate AND toDate AND contract_id = '".$contract_id."' AND board IN ('".$implodeboardRequest."') AND FIND_IN_SET('".$Room_Type."', IFNULL(roomType,'')) > 0");
                $stmt->execute();  
                $boardalt[$i] = $stmt->fetchAll();

                if (($request['adults'][$index]+$request['child'][$index]) > $standard_capacity) {
                    if (isset($request['Room'.($index+1).'ChildAge'])) {
                        foreach ($request['Room'.($index+1).'ChildAge'] as $key18 => $value18) {
                            if (count($boardalt[$i])!=0) {
                              foreach ($boardalt[$i] as $key21 => $value21) {
                                if ($value21['startAge'] <= $value18 && $value21['finalAge'] >= $value18) {
                                  $extrabedAmount[$i][$index][$key21] =  $value21['amount'];
                                  $exrooms[$i][$index][$key18] = $index+1;
                                  $extrabedType[$i][$index][$key21] =  'Child '.$value21['board'];
                                }
                              }
                            }
                        }
                    }
                }
            }
            /* Board wise supplement check start */
            $boardSp[$i] = array();
            if($contract_board=="HB") {
                $stmt = $this->db->prepare("SELECT startAge,finalAge,amount,board FROM hotel_tbl_boardsupplement WHERE '".$date[$i]."' BETWEEN fromDate AND toDate AND contract_id = '".$contract_id."' AND board = 'Half Board' AND FIND_IN_SET('".$Room_Type."', IFNULL(roomType,'')) > 0");
                $stmt->execute();  
                $boardSp[$i] = $stmt->fetchAll();
                
            } else if($contract_board=="FB") {
                $stmt = $this->db->prepare("SELECT startAge,finalAge,amount,board FROM hotel_tbl_boardsupplement WHERE '".$date[$i]."' BETWEEN fromDate AND toDate AND contract_id = '".$contract_id."' AND board = 'Full Board' AND FIND_IN_SET('".$Room_Type."', IFNULL(roomType,'')) > 0");
                $stmt->execute();  
                $boardSp[$i] = $stmt->fetchAll();
            }
            if (count($boardSp[$i])!=0) {
                foreach ($boardSp[$i] as $key21 => $value21) {
                  if (($request['adults'][$index]+$request['child'][$index]) > $standard_capacity) {
                    if (isset($request['Room'.($index+1).'ChildAge'])) {
                      foreach ($request['Room'.($index+1).'ChildAge'] as $key18 => $value18) {
                        if ($value21['startAge'] <= $value18 && $value21['finalAge'] >= $value18) {
                          if (round($value21['amount'])!=0) {
                            $extrabedAmount[$i][$index][] =  $value21['amount'];
                            $extrabedType[$i][$index][] =  'Child '.$value21['board'];
                          }
                        }
                      }
                    }
                  }
                  if ($value21['startAge'] >= 18) {
                    if (round($value21['amount'])!=0) {
                      $extrabedAmount[$i][$index][] =  $value21['amount'];
                      $extrabedType[$i][$index][] =  'Adult '.$value21['board'];
                    }
                  }
                }
            }
            /* Board wise supplement check end */
            if (isset($extrabedAmount[$i])) {
              $Texamount[$i] = array();
              foreach ($extrabedAmount[$i] as $Texamkey => $Texam) {
                  $Texamount[$i][] = array_sum($Texam);
              }
              $extraBedtotal[$i] = array_sum($Texamount[$i]);
            }
        }
        if (count($extraBedtotal)!=0) {
            $return['date'] = $date;
            $return['extrabedAmount'] = $extraBedtotal;
            $return['extrabedType'] = $extrabedType;
            $return['RwextrabedAmount'] = $extrabedAmount;
            $return['Exrooms'] = $exrooms;
            $return['count'] = count($extraBedtotal);
        } else {
            $return['count'] = 0;
        }
        return $return;
    }
    public function get_Confirmgeneral_supplement($request,$contract_id,$room_id,$j,$hotel_id) {
        /*Standard capacity get from rooms start*/
        $stmt = $this->db->prepare("SELECT occupancy,occupancy_child,standard_capacity FROM hotel_tbl_hotel_room_type WHERE hotel_id = '".$hotel_id."' AND id = '".$room_id."'");
        $stmt->execute();  
        $Rmrow_values = $stmt->fetchAll();
      
        $occupancyAdult = $Rmrow_values[0]['occupancy'];
        $occupancyChild = $Rmrow_values[0]['occupancy_child'];
        $standard_capacity = $Rmrow_values[0]['standard_capacity'];

        /*Standard capacity get from rooms end*/

        $return = array();
        $adultAmount =array();
        $RWadultAmount = array();
        $RWadult = array();
        $RWchild = array();
        $childAmount =array();
        $RWchildAmount = array();
        $generalsupplementType = array();
        $generalsupplementapplication = array();
        $boardSplmntCheck  = array();
        $gsarraySum = array();
        $mangsarraySum = array();
        $ManadultAmount  = array();
        $MangeneralsupplementforAdults = array();
        $ManchildAmount = array();
        $MangeneralsupplementforChilds = array();
        $MangeneralsupplementType = array();
        //$generalSplmntCheck[] = array();
        $stmt = $this->db->prepare("SELECT * FROM hotel_tbl_hotel_room_type WHERE id = '".$room_id."'");
        $stmt->execute();  
        $roomType = $stmt->fetchAll();
        
        $checkin_date=date_create($request['check_in']);
        $checkout_date=date_create($request['check_out']);
        $no_of_days=date_diff($checkin_date,$checkout_date);
        $tot_days = $no_of_days->format("%a");
        for($i = 0; $i < $tot_days; $i++) {
            $date[$i] = date('Y-m-d', strtotime($request['check_in']. ' + '.$i.'  days'));
            $dateFormatdate[$i] = date('d/m/Y', strtotime($request['check_in']. ' + '.$i.'  days'));
            $dateFormatday[$i] = date('D', strtotime($request['check_in']. ' + '.$i.'  days'));
            /*Mandatory General Supplement start*/
            $adultAmount =array();
            $RWadultAmount = array();
            $stmt = $this->db->prepare("SELECT * FROM hotel_tbl_generalsupplement WHERE '".$date[$i]."' BETWEEN fromDate AND toDate AND contract_id = '".$contract_id."'  AND hotel_id = '".$hotel_id."'  AND mandatory = 1 AND FIND_IN_SET('".$room_id."', IFNULL(roomType,'')) > 0");
            $stmt->execute();  
            $generalSplmntCheck[$i] = $stmt->fetchAll();
            
            $gsarraySum[$i] = count($generalSplmntCheck[$i]);
            if (count($generalSplmntCheck[$i])!=0) {
                foreach ($generalSplmntCheck[$i] as $key1 => $value1) {
                    if ($value1['application']=="Per Person") {
                      if (round($value1['adultAmount'])!=0) {
                        $adultAmount[$value1['type']] = $value1['adultAmount']*$request['adults'][$j-1];
                      }
                      if (round($value1['adultAmount'])!=0) {
                        $RWadultAmount[$value1['type']][$j] = $value1['adultAmount']*$request['adults'][$j-1] ;
                        $RWadult[$value1['type']][$j] = $j;
                      }
                      if(!empty($request['Room'.$j.'ChildAge'])) {
                        $roomchildage = $request['Room'.$j.'ChildAge'];
                        foreach ($roomchildage as $key44 => $value44) {
                            if ($value1['MinChildAge'] < $value44) {
                                if (round($value1['childAmount'])!=0) {
                                  $childAmount[$value1['type']] = $value1['childAmount'];
                                  $RWchildAmount[$value1['type']][$j][$key44] = $value1['childAmount'];
                                  $RWchild[$value1['type']][$j] = $j;
                                }
                            } 
                        }
                      } 
                    } else {
                      if (round($value1['adultAmount'])!=0) {
                        $adultAmount[$value1['type']] = $value1['adultAmount'];
                        $childAmount[$value1['type']] = 0;
                        $RWadultAmount[$value1['type']][1] = $value1['adultAmount'];
                        $RWadult[$value1['type']][1] = 1;
                      }
                    }
                    $generalsupplementType[$key1] = $value1['type'];
                    $generalsupplementapplication[$key1] = $value1['application'];         
                }
            }
            $return['date'][$i] = $dateFormatdate[$i];
            $return['day'][$i] = $dateFormatday[$i];
            $return['adultamount'][$i] = $adultAmount;
            $return['RWadultamount'][$i] = $RWadultAmount;
            $return['RWadult'][$i] = $RWadult;
            $return['RWchild'][$i] = $RWchild;
            $return['childamount'][$i] = $childAmount;
            $return['RWchildAmount'][$i] = $RWchildAmount;
            $return['general'][$i] = array_unique($generalsupplementType);
            $return['application'][$i] = array_unique($generalsupplementapplication);
            $return['ManadultAmount'][$i] = $ManadultAmount;
            $return['ManchildAmount'][$i] = $ManchildAmount;
            $return['ManchildAmount'][$i] = $ManchildAmount;
            $return['Manadultcount'][$i] = $MangeneralsupplementforAdults;
            $return['Manchildcount'][$i] = $MangeneralsupplementforChilds;
            $return['mangeneral'][$i] = array_unique($MangeneralsupplementType);
        }
        $return['gnlCount'] = array_sum($gsarraySum)+array_sum($mangsarraySum);
        return $return;        
    }
    public function max_booking_id() {
        $stmt = $this->db->prepare("SELECT max(id) as id FROM hotel_tbl_booking");
        $stmt->execute();
        $id = $stmt->fetchAll();
        if ($id[0]['id']=="") {
          $max_booking_id = "HAB01";
        } else {
          $booking_id = $id[0]['id']+1;
          $max_booking_id = "HAB0".$booking_id;
        }
        return $max_booking_id;
    }
    public function mark_up_get($id) {
        $stmt = $this->db->prepare("SELECT Markup FROM hotel_tbl_agents where id = ".$id."");
        $stmt->execute();
        $final = $stmt->fetchAll();
        return count($final)!=0 && $final[0]['Markup']!="" ? $final[0]['Markup'] : 0;
    }
    public function general_mark_up_get($id) {
        $stmt = $this->db->prepare("SELECT general_markup FROM hotel_tbl_agents where id = ".$id."");
        $stmt->execute();
        $final = $stmt->fetchAll();
        return count($final)!=0 && $final[0]['general_markup']!="" ? $final[0]['general_markup'] : 0;
    }
    public function hotelbookingfun($data,$agent_id) {
      $return = array();
      $review = $this->bookingreview($data);
        // Get Max booking Id start 
        $max_id = $this->max_booking_id();
        // Get Max booking Id end 

        // Get Markup start
        $agent_markup = $this->mark_up_get($agent_id);
        $agent_general_markup = $this->general_mark_up_get($agent_id);
        
        // Get markup end

        // Roomwise data finding start
        $booking_flag = 2;
        $BookingDate = date('Y-m-d');
        $checkin_date=date_create($data['check_in']);
        $checkout_date=date_create($data['check_out']);
        $no_of_days=date_diff($checkin_date,$checkout_date);
        $tot_days = $no_of_days->format("%a");
        // Default variable declaration

        for ($i=0; $i < $data['no_of_rooms'] ; $i++) { 
          foreach ($review['room'.($i+1)] as $key => $value) {
            $data['Room'.($i+1).'per_day_amount'][$key] = $value['amount'];
          }
        }

        for ($x=0; $x < 6; $x++) { 
          if (!isset($data['Room'.($x+1).'per_day_amount'])) {
            $data['Room'.($x+1).'per_day_amount'] = array();
          }
          $varRoomrevenueMarkup = 'Room'.($x+1).'revenueMarkup';
          $$varRoomrevenueMarkup = array();

          $varRoomrevenueMarkupType = 'Room'.($x+1).'revenueMarkupType';
          $$varRoomrevenueMarkupType = array();

          $varRoomrevenueExtrabedMarkup = 'Room'.($x+1).'revenueExtrabedMarkup';
          $$varRoomrevenueExtrabedMarkup = array();

          $varRoomrevenueExtrabedMarkupType = 'Room'.($x+1).'revenueExtrabedMarkupType';
          $$varRoomrevenueExtrabedMarkupType = array();

          $varRoomrevenueGeneralMarkup = 'Room'.($x+1).'revenueGeneralMarkup';
          $$varRoomrevenueGeneralMarkup = array();

          $varRoomrevenueGeneralMarkupType = 'Room'.($x+1).'revenueGeneralMarkupType';
          $$varRoomrevenueGeneralMarkupType = array();

          $varRoomrevenueBoardMarkup = 'Room'.($x+1).'revenueBoardMarkup';
          $$varRoomrevenueBoardMarkup = array();

          $varRoomrevenueBoardMarkupType = 'Room'.($x+1).'revenueBoardMarkupType';
          $$varRoomrevenueBoardMarkupType = array();
        }

        // Temp booking_flg
        // $booking_flag = 2;

        for ($i=0; $i < count($data['adults']); $i++) { 
          

          $arrRoomIndex = explode("-", $data['RoomIndex'][$i]);
          $RoomID[$i] = $arrRoomIndex[1]; 
          $ContractID[$i] = $arrRoomIndex[0]; 

          $RequestType =  $this->RequestTypeGet($i+1,$data,$ContractID[$i],$RoomID[$i],$agent_id);
          $data['RequestType'][$i] = $RequestType;
        
          if ($data['RequestType'][$i]!='Book') {
            $booking_flag = 8;
          }


          $contractBoardCheck = $this->contractBoardCheck($ContractID[$i]);
          $data['BoardName'][$i] = $contractBoardCheck[0]['board'];
          // Get markup start
          // $revenue_markup = $this->revenue_markup1($data['hotelcode'],$ContractID[$i],$agent_id,$data['check_in'],$data['check_out']);
          $total_markup[$i] = $agent_markup+$agent_general_markup;
          $admin_markup[$i] = $agent_general_markup;
          // $revenueType[$i] = '';
          // $revenue[$i] = 0;
          // $revenueExtrabed[$i] = 0;
          // $revenueGeneral[$i] = 0;
          // $revenueBoard[$i] = 0;
          // $revenueExtrabedType[$i] = '';
          // $revenueGeneralType[$i] = '';
          // $revenueBoardType[$i] = '';
          // if ($revenue_markup['Markup']!='') {
          //   $total_markup[$i] = $agent_markup;
          //   $admin_markup[$i] = 0;
          //   $revenueType[$i] = $revenue_markup['Markuptype'];
          //   $revenue[$i] = $revenue_markup['Markup'];
          //   $revenueExtrabed[$i] = $revenue_markup['ExtrabedMarkup'];
          //   $revenueGeneral[$i] = $revenue_markup['GeneralSupMarkup'];
          //   $revenueBoard[$i] = $revenue_markup['BoardSupMarkup'];
          //   $revenueExtrabedType[$i] = $revenue_markup['ExtrabedMarkuptype'];
          //   $revenueGeneralType[$i] = $revenue_markup['GeneralSupMarkuptype'];
          //   $revenueBoardType[$i] = $revenue_markup['BoardSupMarkuptype'];
          // }

          // Get markup end
          // Dicount value declaration start
          $discountGet = $this->Alldiscount(date('Y-m-d',strtotime($data['check_in'])),date('Y-m-d',strtotime($data['check_out'])),$data['hotelcode'],$RoomID[$i],$ContractID[$i],'Room');
          $DiscountType[$i] = 'Null';
          $discountStay[$i] = 0;
          $discountPay[$i] = 0;
          $vardecDis = 'Room'.($i+1).'Discount';
          $$vardecDis = 0;
          $ExDis[$i] = 0;
          $GSDis[$i] = 0;
          $BSDis[$i] = 0;
          if ($discountGet['dis']=="true") {
            $DiscountType[$i] = $discountGet['type'];
            $discountCode[$i] = $discountGet['discountCode'];
            $discountStay[$i] = $discountGet['stay'];
            $discountPay[$i] = $discountGet['pay'];
            if ($discountGet['Extrabed']==1) {
              $ExDis[$i] = 1;
            }
            if ($discountGet['General']==1) {
              $GSDis[$i] = 1;
            }
            if ($discountGet['Board']==1) {
              $BSDis[$i] = 1;
            }
          } else {
            $discountCodes[$i] = array();
            $discountTypes[$i] = array();
            $ExArr[$i] = array();
            $GsArr[$i] = array();
            $BsArr[$i] = array();
            for ($j=0; $j < $tot_days ; $j++) {
              $dateOut = date('Y-m-d', strtotime($data['check_in']. ' + '.$j.'  days'));

            $revenue_markup = $this->revenue_markup2($data['hotelcode'],$ContractID[$i],$agent_id,$dateOut);

            $varRoomrevenueMarkup = 'Room'.($i+1).'revenueMarkup';
            $$varRoomrevenueMarkup[$j] = '';

            $varRoomrevenueMarkupType = 'Room'.($i+1).'revenueMarkupType';
            $$varRoomrevenueMarkupType[$j] = '';

            $varRoomrevenueExtrabedMarkup = 'Room'.($i+1).'revenueExtrabedMarkup';
            $$varRoomrevenueExtrabedMarkup[$j] = '';

            $varRoomrevenueExtrabedMarkupType = 'Room'.($i+1).'revenueExtrabedMarkupType';
            $$varRoomrevenueExtrabedMarkupType[$j] = '';

            $varRoomrevenueGeneralMarkup = 'Room'.($i+1).'revenueGeneralMarkup';
            $$varRoomrevenueGeneralMarkup[$j] = '';

            $varRoomrevenueGeneralMarkupType = 'Room'.($i+1).'revenueGeneralMarkupType';
            $$varRoomrevenueGeneralMarkupType[$j] = '';

            $varRoomrevenueBoardMarkup = 'Room'.($i+1).'revenueBoardMarkup';
            $$varRoomrevenueBoardMarkup[$j] = '';

            $varRoomrevenueBoardMarkupType = 'Room'.($i+1).'revenueBoardMarkupType';
            $$varRoomrevenueBoardMarkupType[$j] = '';

            if ($revenue_markup['Markup']!='') {
              $$varRoomrevenueMarkup[$j] = $revenue_markup['Markup'];
              $$varRoomrevenueMarkupType[$j] = $revenue_markup['Markuptype'];
              $$varRoomrevenueExtrabedMarkup[$j] = $revenue_markup['ExtrabedMarkup'];
              $$varRoomrevenueExtrabedMarkupType[$j] = $revenue_markup['ExtrabedMarkuptype'];
              $$varRoomrevenueGeneralMarkup[$j] = $revenue_markup['GeneralSupMarkup'];
              $$varRoomrevenueGeneralMarkupType[$j] = $revenue_markup['GeneralSupMarkuptype'];
              $$varRoomrevenueBoardMarkup[$j] = $revenue_markup['BoardSupMarkup'];
              $$varRoomrevenueBoardMarkupType[$j] = $revenue_markup['BoardSupMarkuptype'];

            }

            $DateWisediscount[$j] = $this->DateWisediscount($dateOut,$data['hotelcode'],$RoomID[$i],$ContractID[$i],'Room',$data['check_in'],$data['check_out']);
            $discount[$i][$j]  = 0;
            
            if (isset($DateWisediscount[$j]['discountCode'])) {
                $discountCodes[$i][$j]= $DateWisediscount[$j]['discountCode'];
                $discountTypes[$i][$j] = $DateWisediscount[$j]['discountType'];
                $discount[$i][$j] = $DateWisediscount[$j]['discount'];
                if ($DateWisediscount[$j]['Extrabed']==1) {
                  $ExArr[$i][$j] = 1;
                }
                if ($DateWisediscount[$j]['General']==1) {
                  $GsArr[$i][$j] = 1;
                }
                if ($DateWisediscount[$j]['Board']==1) {
                  $BsArr[$i][$j] = 1;
                }
              } 
            }
            $ExDis[$i] = array_sum($ExArr[$i])==0 ? 0 : 1;
            $GSDis[$i] = array_sum($GsArr[$i])==0 ? 0 : 1;
            $BSDis[$i] = array_sum($BsArr[$i])==0 ? 0 : 1;
            $$vardecDis = implode(",", $discount[$i]);
            $discountCode[$i] = implode(",", array_unique($discountCodes[$i]));
            $DiscountType[$i] = implode(",", array_unique($discountTypes[$i]));
          }
          // Dicount value declaration end

        }
        $discountStay= implode(",", $discountStay);
        $discountPay= implode(",", $discountPay);
        $discountType = implode(",", $DiscountType);
        $discountCode = implode(",", $discountCode);
        // Roomwise data finding start
        // Temp normal_price
        $normal_price = '';
        $agent_currency_type = $this->agent_currency_type($agent_id);

        // Traveller details declaration start
        $Rwadults = implode(",", $data['adults']);
        $RwChild = implode(",", $data['child']);
        $reqroom1childAge = "";
        $reqroom2childAge = "";
        $reqroom3childAge = "";
        $reqroom4childAge = "";
        $reqroom5childAge = "";
        $reqroom6childAge = "";
        $reqroom7childAge = "";
        $reqroom8childAge = "";
        $reqroom9childAge = "";
        $reqroom10childAge = "";

        if (isset($data['Room1ChildAge'])) {
          $reqroom1childAge = implode(",", $data['Room1ChildAge']);
        }
        if (isset($data['Room2ChildAge'])) {
          $reqroom2childAge = implode(",", $data['Room2ChildAge']);
        }
        if (isset($data['Room3ChildAge'])) {
          $reqroom3childAge = implode(",", $data['Room3ChildAge']);
        }
        if (isset($data['Room4ChildAge'])) {
          $reqroom4childAge = implode(",", $data['Room4ChildAge']);
        }
        if (isset($data['Room5ChildAge'])) {
          $reqroom5childAge = implode(",", $data['Room5ChildAge']);
        }
        if (isset($data['Room6ChildAge'])) {
          $reqroom6childAge = implode(",", $data['Room6ChildAge']);
        }
        if (isset($data['Room7ChildAge'])) {
          $reqroom7childAge = implode(",", $data['Room7ChildAge']);
        }
        if (isset($data['Room8ChildAge'])) {
          $reqroom8childAge = implode(",", $data['Room8ChildAge']);
        }
        if (isset($data['Room9ChildAge'])) {
          $reqroom9childAge = implode(",", $data['Room9ChildAge']);
        }
        if (isset($data['Room10ChildAge'])) {
          $reqroom10childAge = implode(",", $data['Room10ChildAge']);
        }

        for ($i=0; $i < 11; $i++) { 
          if (isset($data['Room'.($i+1).'AdultFirstname'][0])) {
            $data['first_name'][$i] = $data['Room'.($i+1).'AdultFirstname'][0];
            $data['last_name'][$i] = $data['Room'.($i+1).'AdultLastname'][0];
          } else {
            $data['Room'.($i+1).'AdultFirstname'][0] = "";
            $data['Room'.($i+1).'AdultLastname'][0] = "";
          }
        }

        $stmt1 = $this->db->prepare('SELECT id FROM countries where name = "'.$data['nationality'].'"');
        $stmt1->execute();
        $query = $stmt1->fetchAll();
        $data['nationality'] = $query[0]['id'];
        // Traveller details declaration end
        $stmt = $this->db->prepare('select Email,Mobile from hotel_tbl_agents where id = '.$agent_id.'');
        $stmt->execute();
        $query = $stmt->fetchAll();
        $data['SpecialRequest'] = isset($data['SpecialRequest']) ? $data['SpecialRequest'] : '';

        $insert = "INSERT INTO hotel_tbl_booking (
            RequestType,
            Room1Discount,
            Room2Discount,
            Room3Discount,
            Room4Discount,
            Room5Discount,
            Room6Discount,
            Room1revenueMarkup,
            Room2revenueMarkup,
            Room3revenueMarkup,
            Room4revenueMarkup,
            Room5revenueMarkup,
            Room6revenueMarkup,
            Room1revenueMarkupType,
            Room2revenueMarkupType,
            Room3revenueMarkupType,
            Room4revenueMarkupType,
            Room5revenueMarkupType,
            Room6revenueMarkupType,
            Room1revenueExtrabedMarkup,
            Room2revenueExtrabedMarkup,
            Room3revenueExtrabedMarkup,
            Room4revenueExtrabedMarkup,
            Room5revenueExtrabedMarkup,
            Room6revenueExtrabedMarkup,
            Room1revenueExtrabedMarkupType,
            Room2revenueExtrabedMarkupType,
            Room3revenueExtrabedMarkupType,
            Room4revenueExtrabedMarkupType,
            Room5revenueExtrabedMarkupType,
            Room6revenueExtrabedMarkupType,
            Room1revenueGeneralMarkup,
            Room2revenueGeneralMarkup,
            Room3revenueGeneralMarkup,
            Room4revenueGeneralMarkup,
            Room5revenueGeneralMarkup,
            Room6revenueGeneralMarkup,
            Room1revenueGeneralMarkupType,
            Room2revenueGeneralMarkupType,
            Room3revenueGeneralMarkupType,
            Room4revenueGeneralMarkupType,
            Room5revenueGeneralMarkupType,
            Room6revenueGeneralMarkupType,
            Room1revenueBoardMarkup,
            Room2revenueBoardMarkup,
            Room3revenueBoardMarkup,
            Room4revenueBoardMarkup,
            Room5revenueBoardMarkup,
            Room6revenueBoardMarkup,
            Room1revenueBoardMarkupType,
            Room2revenueBoardMarkupType,
            Room3revenueBoardMarkupType,
            Room4revenueBoardMarkupType,
            Room5revenueBoardMarkupType,
            Room6revenueBoardMarkupType,
            revenueMarkup,
            revenueMarkupType,
            revenueExtrabedMarkup,
            revenueExtrabedMarkupType,
            revenueGeneralMarkup,
            revenueGeneralMarkupType,
            revenueBoardMarkup,
            revenueBoardMarkupType,
            Room1individual_amount,
            Room2individual_amount,
            Room3individual_amount,
            Room4individual_amount,
            Room5individual_amount,
            Room6individual_amount,
            ExtrabedDiscount,
            GeneralDiscount,
            BoardDiscount,
            booking_flag,
            booking_id,
            hotel_id,
            room_id,
            currency_type,
            adults_count,
            childs_count,
            agent_markup,
            admin_markup,
            check_in,
            check_out,
            no_of_days,
            book_room_count,
            agent_id,
            search_markup,
            bk_contact_fname,
            bk_contact_lname,
            bk_contact_email,
            bk_contact_number,
            contract_id,
            board,
            Rwadults,
            Rwchild,
            Room1ChildAge,
            Room2ChildAge,
            Room3ChildAge,
            Room4ChildAge,
            Room5ChildAge,
            Room6ChildAge,
            SpecialRequest,
            Room1FName,
            Room2FName,
            Room3FName,
            Room4FName,
            Room5FName,
            Room6FName,
            Room1LName,
            Room2LName,
            Room3LName,
            Room4LName,
            Room5LName,
            Room6LName,
            discountCode,
            discountType,
            discountStay,
            discountPay,
            nationality,
            Created_Date,
            Created_By,
            fromAPI
          ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
          $stmt = $this->db->prepare($insert);

          $datass = array(
            implode(",", $data['RequestType']),
            isset($Room1Discount) ? $Room1Discount : 0,
            isset($Room2Discount) ? $Room2Discount : 0,
            isset($Room3Discount) ? $Room3Discount  : 0,
            isset($Room4Discount) ? $Room4Discount : 0,
            isset($Room5Discount) ? $Room5Discount  : 0,
            isset($Room6Discount) ? $Room6Discount : 0,
            implode(",", $Room1revenueMarkup),
            implode(",", $Room2revenueMarkup),
            implode(",", $Room3revenueMarkup),
            implode(",", $Room4revenueMarkup),
            implode(",", $Room5revenueMarkup),
            implode(",", $Room6revenueMarkup),
            implode(",", $Room1revenueMarkupType),
            implode(",", $Room2revenueMarkupType),
            implode(",", $Room3revenueMarkupType),
            implode(",", $Room4revenueMarkupType),
            implode(",", $Room5revenueMarkupType),
            implode(",", $Room6revenueMarkupType),
            implode(",", $Room1revenueExtrabedMarkup),
            implode(",", $Room2revenueExtrabedMarkup),
            implode(",", $Room3revenueExtrabedMarkup),
            implode(",", $Room4revenueExtrabedMarkup),
            implode(",", $Room5revenueExtrabedMarkup),
            implode(",", $Room6revenueExtrabedMarkup),
            implode(",", $Room1revenueExtrabedMarkupType),
            implode(",", $Room2revenueExtrabedMarkupType),
            implode(",", $Room3revenueExtrabedMarkupType),
            implode(",", $Room4revenueExtrabedMarkupType),
            implode(",", $Room5revenueExtrabedMarkupType),
            implode(",", $Room6revenueExtrabedMarkupType),
            implode(",", $Room1revenueGeneralMarkup),
            implode(",", $Room2revenueGeneralMarkup),
            implode(",", $Room3revenueGeneralMarkup),
            implode(",", $Room4revenueGeneralMarkup),
            implode(",", $Room5revenueGeneralMarkup),
            implode(",", $Room6revenueGeneralMarkup),
            implode(",", $Room1revenueGeneralMarkupType),
            implode(",", $Room2revenueGeneralMarkupType),
            implode(",", $Room3revenueGeneralMarkupType),
            implode(",", $Room4revenueGeneralMarkupType),
            implode(",", $Room5revenueGeneralMarkupType),
            implode(",", $Room6revenueGeneralMarkupType),
            implode(",", $Room1revenueBoardMarkup),
            implode(",", $Room2revenueBoardMarkup),
            implode(",", $Room3revenueBoardMarkup),
            implode(",", $Room4revenueBoardMarkup),
            implode(",", $Room5revenueBoardMarkup),
            implode(",", $Room6revenueBoardMarkup),
            implode(",", $Room1revenueBoardMarkupType),
            implode(",", $Room2revenueBoardMarkupType),
            implode(",", $Room3revenueBoardMarkupType),
            implode(",", $Room4revenueBoardMarkupType),
            implode(",", $Room5revenueBoardMarkupType),
            implode(",", $Room6revenueBoardMarkupType),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            // implode(",", $revenueType),
            // implode(",", $revenue),
            // implode(",", $revenueExtrabed),
            // implode(",", $revenueExtrabedType),
            // implode(",", $revenueGeneral),
            // implode(",", $revenueGeneralType),
            // implode(",", $revenueBoard),
            // implode(",", $revenueBoardType),
            implode(",", $data['Room1per_day_amount']),
            implode(",", $data['Room2per_day_amount']),
            implode(",", $data['Room3per_day_amount']),
            implode(",", $data['Room4per_day_amount']),
            implode(",", $data['Room5per_day_amount']),
            implode(",", $data['Room6per_day_amount']),
            implode(",", $ExDis),
            implode(",", $GSDis),
            implode(",", $BSDis),
            $booking_flag,
            $max_id,
            $data['hotelcode'],
            implode(",", $RoomID),
            $agent_currency_type,
            array_sum($data['adults']),
            array_sum($data['child']),
            $agent_markup,
            implode(",", $admin_markup),
            date('m/d/Y',strtotime($data['check_in'])),
            date('m/d/Y',strtotime($data['check_out'])),
            $tot_days,
            $data['no_of_rooms'],
            $agent_id,
            0,
            $data['first_name'][0],
            $data['last_name'][0],
            $query[0]['Email'],
            $query[0]['Mobile'],
            implode(",", $ContractID),
            implode(",", $data['BoardName']),
            $Rwadults,
            $RwChild,
            $reqroom1childAge,
            $reqroom2childAge,
            $reqroom3childAge,
            $reqroom4childAge,
            $reqroom5childAge,
            $reqroom6childAge,
            $data['SpecialRequest'],
            $data['Room1AdultFirstname'][0],
            $data['Room2AdultFirstname'][0],
            $data['Room3AdultFirstname'][0],
            $data['Room4AdultFirstname'][0],
            $data['Room5AdultFirstname'][0],
            $data['Room6AdultFirstname'][0],
            $data['Room1AdultLastname'][0],
            $data['Room2AdultLastname'][0],
            $data['Room3AdultLastname'][0],
            $data['Room4AdultLastname'][0],
            $data['Room5AdultLastname'][0],
            $data['Room6AdultLastname'][0],
            $discountCode,
            $discountType,
            $discountStay,
            $discountPay,
            $data['nationality'],
            date('Y-m-d H:i:s'),
            $agent_id,
            1
            );
          $stmt->execute($datass);
          $insert_id  = $this->db->lastInsertId();
          for($i=0;$i<$data['no_of_rooms'];$i++){
              for($j=0;$j < ($data['adults'][$i]);$j++) {
                  $insert = "INSERT INTO traveller_details (
                    title,
                    firstname,
                    lastname,
                    age,
                    type,
                    roomindex,
                    bookingid                    
                  ) VALUES (?,?,?,?,?,?,?)";
                  $stmt = $this->db->prepare($insert);
                  $travellers = array($data['Room'.($i+1).'AdultTitle'][$j],
                                      $data['Room'.($i+1).'AdultFirstname'][$j],
                                      $data['Room'.($i+1).'AdultLastname'][$j],
                                      $data['Room'.($i+1).'AdultAge'][$j],
                                      'adult',
                                      $data['RoomIndex'][$i],
                                      $insert_id);
                  $stmt->execute($travellers);
              }
              for($j=0;$j<$data['child'][$i];$j++) {
                  $insert = "INSERT INTO traveller_details (
                    title,
                    firstname,
                    lastname,
                    age,
                    type,
                    roomindex,
                    bookingid                    
                  ) VALUES (?,?,?,?,?,?,?)";
                  $stmt = $this->db->prepare($insert);
                  $travellers = array($data['Room'.($i+1).'ChildTitle'][$j],
                                      $data['Room'.($i+1).'ChildFirstname'][$j],
                                      $data['Room'.($i+1).'ChildLastname'][$j],
                                      $data['Room'.($i+1).'ChildAge'][$j],
                                      'child',
                                      $data['RoomIndex'][$i],
                                      $insert_id);
                  $stmt->execute($travellers);
              }
            }
        // print_r($insert_id);
        // exit();

        $boardData = array();
        $ABadultamount = array();
        $tmangadultamount = array();
        $tmangchildamount = array();
        $implodechildcount = 0;
        $totalBsamount = 0;
        for ($i=0; $i < count($data['adults']); $i++) { 
          $IndexSplit = explode("-", $data['RoomIndex'][$i]);

          // Cancellation Process start 
          $Cancellation[$i] = $this->get_CancellationPolicy_contractConfirm($data,$IndexSplit[0],$IndexSplit[1],$data['hotelcode']); 
          if(!empty($Cancellation[$i])) {
            foreach ($Cancellation[$i] as $Cpkey => $Cpvalue) {
              $this->addCancellationBooking($insert_id,$Cpvalue['msg'],$Cpvalue['percentage'],$Cpvalue['daysFrom'],$Cpvalue['daysTo'],$Cpvalue['application'],$IndexSplit[1],$IndexSplit[0],($i+1),$agent_id);
            }
          }
          // Cancellation Process end

          //  Extrabed process start
          $ExtrabedAmount[$i] =$this->get_PaymentConfirmextrabedAllotment($data,$data['hotelcode'],$IndexSplit[0],$IndexSplit[1],$i);
          if ($ExtrabedAmount[$i]['count']!=0) {
            foreach ($ExtrabedAmount[$i]['date'] as $key => $value){

                $date=$value;
                $amount[$key]= $ExtrabedAmount[$i]['extrabedAmount'][$key];
                
                $RwexamtarrAmount  = array();
                foreach ($ExtrabedAmount[$i]['RwextrabedAmount'][$key] as $Rwexamtarrkey => $Rwexamtarrvalue) {
                  $RwexamtarrAmount[$Rwexamtarrkey] = implode(",", $Rwexamtarrvalue);
                }
                $Exrwamount[$key] = implode(",", $RwexamtarrAmount);
               
                $RwexamtarrRoom  = array();
                foreach ($ExtrabedAmount[$i]['Exrooms'][$key] as $Rwexroomarrkey => $Rwexroomarrvalue) {
                  $RwexamtarrRoom[$Rwexroomarrkey] = implode(",", $Rwexroomarrvalue);
                }
                $Exrooms[$key] = implode(",", $RwexamtarrRoom);

                $RwexamtarrType  = array();
                foreach ($ExtrabedAmount[$i]['extrabedType'][$key] as $Rwextypearrkey => $Rwextypearrvalue) {
                  $RwexamtarrType[$Rwextypearrkey] = implode(",", $Rwextypearrvalue);
                }
                $ExrwType[$key] = implode(",", $RwexamtarrType);

                $InsertExtrabedAmount=$this->AddPaymentConfirmExtrabed($date,$amount[$key],$insert_id,$Exrooms[$key],$Exrwamount[$key],$ExrwType[$key],$IndexSplit[1],$IndexSplit[0],($i+1));
            }
            $ExtrabedAmount[$i] = array();
          }
          //  Extrabed process end

          // General Supplement details Add start
          $gadultamount = array();
          $tgadultamount = array();
          $gchildamount = array();
          $tgchildamount = array();
          $general[$i] = $this->get_Confirmgeneral_supplement($data,$IndexSplit[0],$IndexSplit[1],($i+1),$data['hotelcode']);
          if ($general[$i]['gnlCount']!=0) {
            foreach ($general[$i]['date'] as $key3 => $value3) {
              foreach ($general[$i]['general'][$key3] as $key4 => $value4) {
                $gstayDate = $value3;
                $gBookingDate = date('Y-m-d');
                $generalType = $value4;
                if (isset($general[$i]['adultamount'][$key3][$value4])) {
                  $gadultamount[$key4] = $general[$i]['adultamount'][$key3][$value4];

                  if (array_sum($data['child'])!=0 && isset($general['childamount'][$key3][$value4])) {
                    $gchildamount[$key4] = $general[$i]['childamount'][$key3][$value4];
                  } else {
                    $gchildamount[$key4] = 0;
                  }
                  $tgadultamount[] = $general[$i]['adultamount'][$key3][$value4];
                  $tgchildamount[] = $gchildamount[$key4];
                  $Rwgadult[$key4] = implode(",", $general[$i]['RWadult'][$key3][$value4]);
                  if (isset($general[$i]['RWchild'][$key3][$value4])) {
                    $Rwgchild[$key4] = implode(",", $general[$i]['RWchild'][$key3][$value4]);
                  } else {
                    $Rwgchild[$key4] = "";
                  }
                  $RwgAdultamount[$key4] = implode(",", $general[$i]['RWadultamount'][$key3][$value4]);
                  if (isset($general[$i]['RWchildAmount'][$key3][$value4])) {
                      foreach ($general[$i]['RWchildAmount'][$key3][$value4] as $gscarkey => $gscarvalue) {
                          $gscarr[] =array_sum($gscarvalue);
                      }
                     $RwgChildamount[$key4] = implode(",", $gscarr);
                  } else {
                    $RwgChildamount[$key4] = "";
                  }
                  $Rwgsapplication[$key4] = $general[$i]['application'][$key3][$key4];
                  $bkgeneralSupplementConfirm = $this->bkgeneralSupplementConfirm($gstayDate, $gBookingDate, $generalType, $gadultamount[$key4] , $gchildamount[$key4], $agent_markup, $total_markup[$i], $admin_markup[$i],$insert_id,array_sum($data['adults']),array_sum($data['child']),1,$Rwgadult[$key4],$Rwgchild[$key4],$RwgAdultamount[$key4],$RwgChildamount[$key4],$Rwgsapplication[$key4],$IndexSplit[1],$IndexSplit[0],($i+1),$agent_id);
                }
              }
            }
          }
          // General Supplement details Add end

        }
      $return['ConfirmationNo'] = $max_id;

      return $return;
    }
    public function revenue_markup1($hotel_id,$contract_id,$agent_id,$checkIn,$checkOut) {  
      $stmt = $this->db->prepare("SELECT * FROM `hotel_tbl_revenue` where FIND_IN_SET(".$hotel_id.", IFNULL(hotels,'')) > 0 AND FIND_IN_SET('".$contract_id."', IFNULL(contracts,'')) > 0 AND FIND_IN_SET(".$agent_id.", IFNULL(Agents,'')) > 0 AND FromDate <= '".date('Y-m-d',strtotime($checkIn))."' AND  ToDate >= '".date('Y-m-d',strtotime($checkOut.'-1 days'))."'");
      $stmt->execute();
      $query = $stmt->fetchAll();
      if (count($query)!=0) {
        $return['Markup'] = $query[0]['Markup'];
        $return['Markuptype'] = $query[0]['Markuptype'];
        $return['ExtrabedMarkup'] = $query[0]['ExtrabedMarkup'];
        $return['ExtrabedMarkuptype'] = $query[0]['ExtrabedMarkuptype'];
        $return['GeneralSupMarkup'] = $query[0]['GeneralSupMarkup'];
        $return['GeneralSupMarkuptype'] = $query[0]['GeneralSupMarkuptype'];
        $return['BoardSupMarkup'] = $query[0]['BoardSupMarkup'];
        $return['BoardSupMarkuptype'] = $query[0]['BoardSupMarkuptype'];
      } else {
        $return['Markup'] = '';
        $return['Markuptype'] = '';
        $return['ExtrabedMarkup'] = '';
        $return['ExtrabedMarkuptype'] = '';
        $return['GeneralSupMarkup'] = '';
        $return['GeneralSupMarkuptype'] = '';
        $return['BoardSupMarkup'] = '';
        $return['BoardSupMarkuptype'] = '';
      }
      return $return;
    }
    public function agent_currency_type($agent_id) {
      $stmt = $this->db->prepare('select Preferred_Currency from hotel_tbl_agents where id = '.$agent_id.'');
      $stmt->execute();
      $query = $stmt->fetchAll();
      return $query[0]['Preferred_Currency'];
    }
    public function RequestTypeGet($key,$data,$contract,$room_id,$agent_id) {
        $start_date = $data['check_in'];
        $end_date = $data['check_out'];
        $first_date = strtotime($start_date);
        $second_date = strtotime($end_date);
        $offset = $second_date-$first_date; 
        $result = array();
        $checkin_date=date_create($data['check_in']);
        $checkout_date=date_create($data['check_out']);
        $no_of_days=date_diff($checkin_date,$checkout_date);
        $tot_days = $no_of_days->format("%a");
        $bookDate = date_create(date('Y-m-d'));
        $rooms = array();
        for($i = 0; $i < $tot_days; $i++) {
          $dateAlt[$i] = date('Y-m-d', strtotime($start_date. ' + '.$i.'  days'));
        }
        $implode_data = implode("','", $dateAlt);
        $RoomChildAge1 = 0; 
        $RoomChildAge2 = 0; 
        $RoomChildAge3 = 0; 
        $RoomChildAge4 = 0; 
        if (isset($data['room'.($key).'-childAge'][0])) {
          $RoomChildAge1 = $data['room'.($key).'-childAge'][0]; 
        }
        if (isset($data['room'.($key).'-childAge'][1])) {
          $RoomChildAge2 = $data['room'.($key).'-childAge'][1]; 
        }
        if (isset($data['room'.($key).'-childAge'][2])) {
          $RoomChildAge3 = $data['room'.($key).'-childAge'][2]; 
        }
        if (isset($data['room'.($key).'-childAge'][3])) {
          $RoomChildAge4 = $data['room'.($key).'-childAge'][3]; 
        }
        $markup = $this->mark_up_get($agent_id);
        $general_markup = $this->general_mark_up_get($agent_id);
        
        $stmt = $this->db->prepare("SELECT RequestType
        FROM (
          SELECT IF(min(allotment)=0,'On Request','Book') as RequestType ,count(*) as counts
           FROM (
         SELECT *
      FROM (
      SELECT
      if(con.contract_type='Sub',(select GetAllotmentCount1(a.allotement_date,a.hotel_id,CONCAT('CON0',linkedcontract),a.room_id ,'".date('Y-m-d')."',".$tot_days.",".count($data['adults']).")),(select GetAllotmentCount1(a.allotement_date,a.hotel_id,a.contract_id,a.room_id ,'".date('Y-m-d')."',".$tot_days.",".count($data['adults'])."))) as allotment,a.hotel_id,a.contract_id,a.room_id


      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

       AND a.allotement_date IN ('".$implode_data."') AND a.contract_id = '".$contract."' AND a.amount !=0 AND a.hotel_id = ".$data['hotelcode']." AND a.room_id = ".$room_id.") extra) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x");

        $stmt->execute();
        $rooms = $stmt->fetchAll();
        if(empty($rooms)) {
            return 'On Request';
        } else {
            return $rooms[0]['RequestType'];
        }   
    }
    public function addCancellationBooking($booking_id,$msg,$percentage,$daysFrom,$daysTo,$application,$room_id="",$contract_id="",$index,$agent_id) {
      $insert = "INSERT INTO hotel_tbl_bookcancellationpolicy (
          bookingID               ,
          room_id                 ,
          contract_id             ,
          roomIndex                ,
          daysFrom                ,
          daysTo                  ,
          cancellationPercentage  ,
          application             ,
          msg                     ,
          createdDate             ,
          createdBy               

          ) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
          $stmt = $this->db->prepare($insert);

          $datas= array(
            $booking_id,
            $room_id,
            $contract_id,
            $index,
            $daysFrom,
            $daysTo,
            $percentage,
            $application,
            $msg,
            date('Y-m-d H:i:s'),
            $agent_id,
          );
        $stmt->execute($datas);
     return true;
    }
    public function AddPaymentConfirmExtrabed($date,$amount,$bookId,$rooms,$rwamount,$type,$room_id,$contract_id,$index) {
      $insert = "INSERT INTO bookingextrabed (
          date,
          amount,
          rooms,
          Exrwamount,
          Type,
          bookId,
          room_id,
          contract_id,
          roomIndex
          ) VALUES (?,?,?,?,?,?,?,?,?)";
          $stmt = $this->db->prepare($insert);

          $datas= array(
            $date,
            $amount,
            $rooms,
            $rwamount,
            $type,
            $bookId,
            $room_id,
            $contract_id,
            $index,
          );
        $stmt->execute($datas);
     return true;
    }
    public function bkgeneralSupplementConfirm($gstayDate, $gBookingDate, $generalType, $gadultamount , $gchildamount, $agent_markup, $total_markup, $admin_markup,$booking_id,$reqadults,$reqChild,$mand,$Rwadult,$Rwchild,$Rwadultamount,$RwchildAmount,$application,$room_id,$contract_id,$index,$agent_id) {

      $insert = "INSERT INTO hotel_tbl_bookGeneralSupplement (
          bookingID,
          gstayDate,
          gBookingDate,
          generalType,
          gadultamount,
          gchildamount,
          agent_markup,
          total_markup,
          admin_markup,
          reqadults,
          reqChild,
          mandatory,
          Rwadult,
          Rwchild,
          Rwadultamount,
          RwchildAmount,
          application,
          room_id,
          contract_id,
          roomIndex,
          createdDate,
          createdBy
          ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
          $stmt = $this->db->prepare($insert);

          $datas= array(
            $booking_id,
            $gstayDate,
            $gBookingDate,
            $generalType,
            $gadultamount,
            $gchildamount,
            $agent_markup,
            $total_markup,
            $admin_markup,
            $reqadults,
            $reqChild,
            $mand,
            $Rwadult,
            $Rwchild,
            $Rwadultamount,
            $RwchildAmount,
            $application,
            $room_id,
            $contract_id,
            $index,
            date('Y-m-d H:i:s'),
            $agent_id,
          );
          $stmt->execute($datas);
     return true;
    }
    public function xmlhotelbookingfun($data,$agent_id) {
      $agent_markup = $this->mark_up_get($agent_id);
      $admin_markup = $this->general_mark_up_get($agent_id);
      $revenue_markup =  $this->xmlrevenue_markup('tbo',$agent_id,$data);
      $total_markup = $agent_markup+$admin_markup;
      if ($revenue_markup!='') {
        $total_markup = $agent_markup+$revenue_markup;
      }

      $board = array();
      $return = array();
      for($x=0;$x<$data['no_of_rooms'];$x++){
          for ($i=0; $i < $data['adults'][$x] ; $i++) { 
            $Guest[] = [
              "Guest"=>[
                "attr"=>[
                  "LeadGuest"=> $i==0 && $x==0 ? true : 0,
                  "GuestType"=>'Adult',
                  "GuestInRoom"=>$x+1
                ],
                "value"=>[
                  "Title"=>[
                    "value"=>$data['Room'.($x+1).'AdultTitle'][$i]
                  ],
                  "FirstName"=>[
                    "value"=> $data['Room'.($x+1).'AdultFirstname'][$i]
                  ],
                  "LastName"=>[
                    "value"=>$data['Room'.($x+1).'AdultLastname'][$i]
                  ],
                  "Age"=>[
                    "value"=>$data['Room'.($x+1).'AdultAge'][$i]
                  ]
                ]
              ]
            ];
          }

          for ($i=0; $i < $data['child'][$x] ; $i++) { 
            $Guest[] = [
              "Guest"=>[
                "attr"=>[
                  "LeadGuest"=> 0,
                  "GuestType"=>'Child',
                  "GuestInRoom"=>$x+1
                ],
                "value"=>[
                  "Title"=>[
                    "value"=>$data['Room'.($x+1).'ChildTitle'][$i]
                  ],
                  "FirstName"=>[
                    "value"=> $data['Room'.($x+1).'ChildFirstname'][$i]
                  ],
                  "LastName"=>[
                    "value"=>$data['Room'.($x+1).'ChildLastname'][$i]
                  ],
                  "Age"=>[
                    "value"=>$data['Room'.($x+1).'ChildAge'][$i]
                  ]
                ]
              ]
            ];
          }


        }
        $key = $data['sessionid'].'-'.$data['hotelcode'].'1';
      $CachedString = $this->cache->getItem($key);
      $dd = $CachedString->get();

      $rooms = array();

      foreach ($data['RoomIndex'] as $key => $value) {
        foreach ($dd['HotelRooms']['HotelRoom'] as $key1 => $value1) {
          if ($value1['RoomIndex']==$value) {
            $rooms[] = $value1;
          }
        } 
      }
      $Supplements =array();

      foreach ($rooms as $key => $value) {
        if (isset($value['Supplements'])) {
          if (isset($value['Supplements']['Supplement'][0])) {
            $Supplementsdata = $value['Supplements']['Supplement'];
          } else {
            $Supplementsdata[0] = $value['Supplements']['Supplement'];
          }
          foreach ($Supplementsdata as $key1 => $value1) {
            $Supplements[$key1] = [
                      "SuppInfo"=>[
                        "attr"=>[
                          "SuppID"=> $value1['@attributes']['SuppID'],
                          "SuppChargeType"=> $value1['@attributes']['SuppChargeType'],
                          "Price"=>$value1['@attributes']['Price'],
                          "SuppIsSelected"=>true,
                        ]
                      ]
                    ];

          }
        }
        $HotelRoom[$key] = [
            "HotelRoom"=>[
              "value"=>[
                "RoomIndex"=>[
                  "value"=>$data['RoomIndex'][$key]
                ],
                "RoomTypeName"=>[
                  "value"=>$value['RoomTypeName']
                ],
                "RoomTypeCode"=>[
                  "value"=>$value['RoomTypeCode']
                ],
                "RatePlanCode"=>[
                  "value"=>$value['RatePlanCode']
                ],
                "RoomRate"=>[
                  "attr"=>[
                    "Currency"=>'USD',
                    "RoomFare" => $value['RoomRate']['@attributes']['RoomFare'],
                    "RoomTax" => $value['RoomRate']['@attributes']['RoomTax'],
                    "TotalFare"=>$value['RoomRate']['@attributes']['TotalFare']
                  ]
                ],
                "Supplements"=>[
                  "value"=>count($Supplements)!=0 ? $Supplements : ''
                ]
              ]
            ]
          ];
          $board[] = $value['MealType']=="" || is_array($value['MealType']) ? 'Room Only' : $value['MealType']; 
      }

      $stmt1 = $this->db->prepare('SELECT * FROM countries where name = "'.$data['nationality'].'"');
      $stmt1->execute();
      $query = $stmt1->fetchAll();
      $nationality = count($query)!=0 ? $query[0]['sortname'] : 'IN';
      $Country = $query[0]['name'];
      $Countryshort = $query[0]['sortname'];
      $CountryCode =$query[0]['phonecode'];

      $stmt2 = $this->db->prepare('SELECT * FROM hotel_tbl_agents where id = '.$agent_id.'');
      $stmt2->execute();
      $agentData = $stmt2->fetchAll();
      $rand = $s = substr(str_shuffle(str_repeat("abcdefghijklmnopqrstuvwxyz", 4)), 0, 4);
      $ClientReferenceNumber = date('dmyHms')."750#".$rand;
      $inp_arr_hotel = [ 
        "ClientReferenceNumber"=>[
          "value"=> $ClientReferenceNumber
        ],
        "GuestNationality"=>[
          "value"=>$Countryshort
        ],  
        "Guests"=>[
          "value"=>$Guest
        ],
        "AddressInfo"=>[
                        "value"=>[
                          "AddressLine1"=>[
                            "value"=>$agentData[0]['Address']
                          ],
                          "AddressLine2"=>[
                            "value"=>$agentData[0]['Address']
                          ],
                          "CountryCode"=>[
                            "value"=>$CountryCode
                          ],
                          "AreaCode"=>[
                            "value"=>$agentData[0]['Pincode']
                          ],
                          "PhoneNo"=>[
                            "value"=>$agentData[0]['Mobile']
                          ],
                          "Email"=>[
                            "value"=>$agentData[0]['Email']
                          ],
                          "City"=>[
                            "value"=>$data['cityname']
                          ],
                          "State"=>[
                            "value"=>$data['cityname']
                          ],
                          "Country"=>[
                            "value"=>$Country
                          ],
                          "ZipCode"=>[
                            "value"=>$agentData[0]['Pincode']
                          ]
                        ]
        ],
        "PaymentInfo"=>[
          "attr"=>[
            "VoucherBooking"=>true,
            "PaymentModeType"=>'Limit',
          ]
        ],
        "SessionId"=>[
            "value"=>$data['sessionid']
        ],
        "NoOfRooms"=>[
            "value"=>$data['no_of_rooms']
        ],
        "ResultIndex"=>[
            "value"=> $data['ResultIndex']
        ],
        "HotelCode"=>[
            "value"=>$data['hotelcode']
        ],
        "HotelName"=>[
            "value"=>$dd['@attributes']['HotelName']
        ],        
        "HotelRooms"=>[
            "value"=>$HotelRoom
        ]
      ];
      $Bookingresponse =  $this->HotelBook($inp_arr_hotel);
      if ($Bookingresponse['Status']['StatusCode']==01) {
        if (is_array($Bookingresponse['BookingId'])) {
         $BookingId =  implode(" ", $Bookingresponse['BookingId']);
        } else {
         $BookingId =  $Bookingresponse['BookingId'];          
        }
         $PriceChange = $Bookingresponse['PriceChange']['@attributes']['Status'];

         $guestfname = $data['Room1AdultFirstname'][0];
         $guestlname =  $data['Room1AdultLastname'][0];

         $tot = $dd['total'];
         $checkin_date=date_create($data['check_in']);
          $checkout_date=date_create($data['check_out']);
          $no_of_days=date_diff($checkin_date,$checkout_date);
          $tot_days = $no_of_days->format("%a");

         $insert_id = $this->TBOBookingConfirm($agent_id,$ClientReferenceNumber,$BookingId,$Bookingresponse['TripId'],$Bookingresponse['ConfirmationNo'],$Bookingresponse['BookingStatus'],$dd['@attributes']['HotelName'],$rooms[0]['RoomTypeName'],$data['check_in'],$data['check_out'],$tot,$tot_days,$data['no_of_rooms'],$data['hotelcode'],$PriceChange,$total_markup,$guestfname,$guestlname,$board);
         $return['ConfirmationNo'] = $Bookingresponse['ConfirmationNo'];
          for($i=0;$i<$data['no_of_rooms'];$i++){
              for($j=0;$j < ($data['adults'][$i]);$j++) {
                  $insert = "INSERT INTO traveller_details (
                    title,
                    firstname,
                    lastname,
                    age,
                    type,
                    roomindex,
                    bookingid                    
                  ) VALUES (?,?,?,?,?,?,?)";
                  $stmt = $this->db->prepare($insert);
                  $travellers = array($data['Room'.($i+1).'AdultTitle'][$j],
                                      $data['Room'.($i+1).'AdultFirstname'][$j],
                                      $data['Room'.($i+1).'AdultLastname'][$j],
                                      $data['Room'.($i+1).'AdultAge'][$j],
                                      'adult',
                                      $data['RoomIndex'][$i],
                                      $insert_id);
                  $stmt->execute($travellers);
              }
              for($j=0;$j<$data['child'][$i];$j++) {
                  $insert = "INSERT INTO traveller_details (
                    title,
                    firstname,
                    lastname,
                    age,
                    type,
                    roomindex,
                    bookingid                    
                  ) VALUES (?,?,?,?,?,?,?)";
                  $stmt = $this->db->prepare($insert);
                  $travellers = array($data['Room'.($i+1).'ChildTitle'][$j],
                                      $data['Room'.($i+1).'ChildFirstname'][$j],
                                      $data['Room'.($i+1).'ChildLastname'][$j],
                                      $data['Room'.($i+1).'ChildAge'][$j],
                                      'child',
                                      $data['RoomIndex'][$i],
                                      $insert_id);
                  $stmt->execute($travellers);
              }
            }
      } 
      return $return;
    }
    public function HotelBook($arg){
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
    public function TBOBookingConfirm($agent_id,$ClientReferenceNumber,$BookingId,$TripId,$ConfirmationNo,$BookingStatus,$hotel_name,$RoomTypeName,$Check_in,$Check_out,$total_amount,$no_of_days,$no_of_rooms,$Hotel_id,$PriceChange,$admin_markup,$guestfname,$guestlname,$board) {

        $insert = "INSERT INTO xml_hotel_booking (
            XMLProvider,
            agent_id,    
            ClientReferenceNumber,
            BookingId,    
            TripId,    
            ConfirmationNo, 
            BookingStatus, 
            hotel_name, 
            RoomTypeName, 
            Check_in, 
            Check_out, 
            total_amount, 
            no_of_days, 
            no_of_rooms, 
            Hotel_id, 
            agent_markup, 
            admin_markup, 
            CreatedDate,    
            CreatedBy,    
            Bookingdate,    
            PriceChange, 
            bk_contact_fname, 
            bk_contact_lname, 
            board,
            fromAPI
          ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
          $stmt = $this->db->prepare($insert);

          $datass = array(
            'TBO',
            $agent_id,
            $ClientReferenceNumber,
            $BookingId,
            $TripId,
            $ConfirmationNo,
            $BookingStatus,
            $hotel_name,
            $RoomTypeName,
            $Check_in,
            $Check_out,
            $total_amount,
            $no_of_days,
            $no_of_rooms,
            $Hotel_id,
            $this->mark_up_get($agent_id),
            $admin_markup,
            date('Y-m-d H:i:s'),
            $agent_id,
            date('m/d/Y'),
            $PriceChange,
            $guestfname,
            $guestlname,
            implode("==", $board),
            1
            );
          $stmt->execute($datass);
          $id  = $this->db->lastInsertId();

      return $id;
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
  public function xmlrevenue_markup($provider,$agent_id,$request) {  
    $stmt1 = $this->db->prepare("SELECT IFNULL(MAX(Markup),'') as Markup FROM `hotel_tbl_revenue` where ".$provider." = 1 AND FIND_IN_SET(".$agent_id.", IFNULL(Agents,'')) > 0 AND FromDate <= '".date('Y-m-d',strtotime($request['check_in']))."' AND  ToDate >= '".date('Y-m-d',strtotime($request['check_out']))."'");
    $stmt1->execute();
    $query = $stmt1->fetchAll();
    return $query[0]['Markup'];
  }
  public function revenue_markup2($hotel_id,$contract_id,$agent_id,$date) {  
    $stmt1 = $this->db->prepare("SELECT * FROM `hotel_tbl_revenue` where FIND_IN_SET(".$hotel_id.", IFNULL(hotels,'')) > 0 AND FIND_IN_SET('".$contract_id."', IFNULL(contracts,'')) > 0 AND FIND_IN_SET(".$agent_id.", IFNULL(Agents,'')) > 0 AND FromDate <= '".$date."' AND  ToDate >= '".$date."'");
    $stmt1->execute();
    $query = $stmt1->fetchAll();
    if (count($query)!=0) {
      $return['Markup'] = $query[0]['Markup'];
      $return['Markuptype'] = $query[0]['Markuptype'];
      $return['ExtrabedMarkup'] = $query[0]['ExtrabedMarkup'];
      $return['ExtrabedMarkuptype'] = $query[0]['ExtrabedMarkuptype'];
      $return['GeneralSupMarkup'] = $query[0]['GeneralSupMarkup'];
      $return['GeneralSupMarkuptype'] = $query[0]['GeneralSupMarkuptype'];
      $return['BoardSupMarkup'] = $query[0]['BoardSupMarkup'];
      $return['BoardSupMarkuptype'] = $query[0]['BoardSupMarkuptype'];
    } else {
      $return['Markup'] = '';
      $return['Markuptype'] = '';
      $return['ExtrabedMarkup'] = '';
      $return['ExtrabedMarkuptype'] = '';
      $return['GeneralSupMarkup'] = '';
      $return['GeneralSupMarkuptype'] = '';
      $return['BoardSupMarkup'] = '';
      $return['BoardSupMarkuptype'] = '';
    }
    return $return;
  }
}