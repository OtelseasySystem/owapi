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
    // public function hotel_facilities_data($id) {
    //     $stmt = $this->db->prepare("SELECT Hotel_Facility FROM hotel_tbl_hotel_facility WHERE id ='".$id."'");
    //     $stmt->execute();
    //     $details = $stmt->fetch();
    //     return $details;
    // }
    // public function room_facilities_data($id) {
    //     $stmt = $this->db->prepare("SELECT Room_Facility FROM hotel_tbl_room_facility WHERE id = '".$id."'");
    //     $stmt->execute();
    //     $details = $stmt->fetch();
    //     return $details;
    // }
    public function contractChecking($searchdet) {
        $start = $searchdet['check_in'];
        $end = $searchdet['check_out'];
        $checkin_date=date_create($searchdet['check_in']);
        $checkout_date=date_create($searchdet['check_out']);
        $no_of_days=date_diff($checkin_date,$checkout_date);
        $tot_days = $no_of_days->format("%a");
        // Contract Check start
        $contract_id = array();
        $count = array();
        $contracts = array();
        $stmt = $this->db->prepare("SELECT contract_id FROM hotel_tbl_contract a WHERE  FIND_IN_SET('".$searchdet['nationality']."', IFNULL(nationalityPermission,'')) = 0 AND from_date <= '".date('Y-m-d',strtotime($searchdet['check_in']))."' AND to_date >= '".date('Y-m-d',strtotime($searchdet['check_in']))."' AND  from_date < '".date('Y-m-d',strtotime($searchdet['check_out']. ' -1 days'))."' AND to_date >= '".date('Y-m-d',strtotime($searchdet['check_out']. ' -1 days'))."'  AND hotel_id = '".$searchdet['hotelcode']."' AND contract_flg  = 1");
        $stmt->execute();
        $contracts = $stmt->fetchAll();
        foreach ($contracts as $key5 => $value5) {
            $contract_id[] =  $value5['contract_id'];
        }
        $count[] =  count($contracts);
        $contractdet= array();
        if (count($count)!=0) {
            $array_uniquecon = array_unique($contract_id);
            foreach ($array_uniquecon as $key10 => $value10) {
                $contractdet['contract_id'][] = $value10;
                $stmt = $this->db->prepare("SELECT * FROM hotel_tbl_contract WHERE contract_id ='".$value10."'");
                $stmt->execute();
                $det = $stmt->fetch();
                $contractdet['max_child_age'][] = $det['max_child_age']; 
            }
            return $contractdet;
        } else {
            return $contractdet;
        }
    }
    public function validateparametersbookingreview($data) {
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
    public function validateRoomParameter($data) {
        $return = array();
        //print_r($data['sessionid']);exit;
        if(isset($data['token'])) {
          for($i=0;$i<$data['no_of_rooms'];$i++) {
              if(!isset($data['RoomIndex'][$i]) || $data['RoomIndex'][$i] == '') {
                  $return['RoomIndex['.$i.']'] = 'RoomIndex '.($i).' is required';
              } else if(!isset($data['sessionid'])) {
                  $roomindex = explode('-',$data['RoomIndex'][$i]);
                  if(!isset($roomindex[0]) || !isset($roomindex[1])) {
                      $return['RoomIndex['.$i.'] format'] = 'RoomIndex '.($i).' is should be in the contractId - roomId format(eg:CON010-348)';
                  }
              }
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
    public function bookingreview($data,$agent_id) {
      $agent_markup = $this->mark_up_get($agent_id);
      $general_markup = $this->general_mark_up_get($agent_id);

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
      $response['Checkin']= $data['check_in'];
      $response['Checkout']= $data['check_out'];
      $response['Adults']= $data['adults'];
      $response['Child']= $data['child'];
      $response['no_of_rooms']= $data['no_of_rooms'];
      $response['no_of_days']=  $tot_days;
      $response['Available']=  true;
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
        $response['remarks_and_policies']= $this->get_policy_contract($data['hotelcode'],$contractid[$i]);
        $contractBoardCheck = $this->contractBoardCheck($contractid[$i]);

        
        $extrabed = $this->get_PaymentConfirmextrabedAllotment($data,$data['hotelcode'],$contractid[$i],$roomid[$i],$i); 
        $general = $this->get_Confirmgeneral_supplement($data,$contractid[$i],$roomid[$i],$i+1,$data['hotelcode']); 

        $Fdays = 0;
        $discountGet = $this->Alldiscount(date('Y-m-d',strtotime($data['check_in'])),date('Y-m-d',strtotime($data['check_out'])),$data['hotelcode'],$roomid[$i],$contractid[$i],'Room'); 
        if ($discountGet['dis']=="true") {
          $Cdays = $tot_days/$discountGet['stay'];
          $parts = explode('.', $Cdays);
          $Cdays = $parts[0];
          $Sdays = $discountGet['stay']*$Cdays;
          $Pdays = $discountGet['pay']*$Cdays;
          $Tdays = $tot_days-$Sdays;
          $Fdays = $Pdays+$Tdays;
          $discountGet['stay'];
          $discountGet['pay'];
        }
        if($discountGet['dis']=="true") { 
          $data['discount']['Stay'] =  $discountGet['stay'].'nights';
          $data['discount']['Pay'] = $discountGet['pay'].'nights';
        }

        $x= 0;
        for ($j=1; $j <=$tot_days ; $j++) {
          $revenue_markup = $this->revenue_markup2($data['hotelcode'],$contractid[$i],$agent_id,date('Y-m-d' ,strtotime($result[$j]['date'])));

          $result[$j]['amount'] = $this->special_offer_amount($result[$j]['date'],$roomid[$i],$data['hotelcode'],$contractid[$i]);
          $result[$j]['roomName'] = $this->roomnameGET($roomid[$i],$data['hotelcode']);
          $FextrabedAmount[$j-1]  = 0;
          $TFextrabedAmount[$j-1]  = 0;
          $GAamount[$j-1] = 0;
          $GCamount[$j-1] = 0;
          $BBAamount[$j-1] = 0;
          $BBCamount[$j-1] = 0;
          $LAamount[$j-1] = 0;
          $LCamount[$j-1] = 0;
          $DAamount[$j-1] = 0;
          $DCamount[$j-1] = 0;
          $TGAamount[$j-1] = 0;
          $TGCamount[$j-1] = 0;

          $RMdiscount = $this->DateWisediscount(date('Y-m-d' ,strtotime($result[$j]['date'])),$data['hotelcode'],$roomid[$i],$contractid[$i],'Room',date('Y-m-d',strtotime($data['check_in'])),date('Y-m-d',strtotime($data['check_out'])),$discountGet['dis']);
          $RMdiscountval[$i] = $RMdiscount['discount'];
          $GDis = 0;
          if ($RMdiscount['discount']!=0 && $RMdiscount['General']!=0) { 
            $GDis = $RMdiscount['discount'];
          }
          $exDis = 0;
          if ($RMdiscount['discount']!=0 && $RMdiscount['Extrabed']!=0) { 
            $exDis = $RMdiscount['discount'];
          }
          $BDis = 0;
          if ($RMdiscount['discount']!=0 && $RMdiscount['Board']!=0) { 
            $BDis = $RMdiscount['discount'];
          }



          $return['amount_breakup'][$x]['Date']= date('d/m/Y' ,strtotime($result[$j]['date']));
          $return['amount_breakup'][$x]['RoomName'] = $result[$j]['roomName'];
          $return['amount_breakup'][$x]['Board'] = $contractBoardCheck[0]['board'];
          $rmamount = 0;
          $total_markup = $agent_markup+$general_markup;
          if ($revenue_markup['Markup']!='') {
              $total_markup = $agent_markup;
            if ($revenue_markup['Markuptype']=="Percentage") {
              $rmamount = (($result[$j]['amount']*$revenue_markup['Markup'])/100);
            } else {
              $rmamount = $revenue_markup['Markup'];
            }
          }

          $roomAmount[$j]  = (($result[$j]['amount']*$total_markup)/100)+$result[$j]['amount']+$rmamount;
          $DisroomAmount[$j] = $roomAmount[$j]-($roomAmount[$j]*$RMdiscount['discount'])/100;
          $WiDisroomAmount[$j] = $roomAmount[$j];

          if ($RMdiscount['discount']!=0) { 
            // $return['amount_breakup'][$x]['roomamount'] = $roomAmount[$j];
          }
          $return['amount_breakup'][$x]['Price'] = $DisroomAmount[$j];

          // General Supplement breakup start 
          if($general['gnlCount']!=0) {
            //General Supplement adult breakup start
            foreach ($general['date'] as $GAkey => $GAvalue) {
              if ($GAvalue==date('d/m/Y' ,strtotime($result[$j]['date']))) {
                foreach ($general['general'][$GAkey] as $GSNkey => $GSNvalue) {
                  if (isset($general['RWadultamount'][$GAkey][$GSNvalue])) {
                    $x++;
                    $GSAmamount = 0;
                    if ($revenue_markup['GeneralSupMarkup']!='') {
                        $total_markup = $agent_markup;
                      if ($revenue_markup['GeneralSupMarkuptype']=="Percentage") {
                        $GSAmamount = (($general['RWadultamount'][$GAkey][$GSNvalue][$i+1]*$revenue_markup['GeneralSupMarkup'])/100);
                      } else {
                        $GSAmamount = $revenue_markup['GeneralSupMarkup'];
                      }
                    }
                    $GAamount[$j-1] = ($general['RWadultamount'][$GAkey][$GSNvalue][$i+1]*$total_markup)/100+$general['RWadultamount'][$GAkey][$GSNvalue][$i+1]+$GSAmamount;
                    $TGAamount[$j-1] += $GAamount[$j-1]-($GAamount[$j-1]*$GDis)/100;
                    if ($RMdiscount['discount']!=0 && $RMdiscount['General']!=0) { 
                      // $return['generalSupplement']['adult'.$GAkey]['old-price'][]=$GAamount[$j-1];
                    }
                    $return['amount_breakup'][$x]['Date']= date('d/m/Y' ,strtotime($result[$j]['date']));
                    $return['amount_breakup'][$x]['Type']= 'Adult '.$GSNvalue;
                    $return['amount_breakup'][$x]['Board'] = $contractBoardCheck[0]['board'];
                    $return['amount_breakup'][$x]['Price'] = $GAamount[$j-1]-($GAamount[$j-1]*$GDis)/100;
                    $x++; 
                  }
                }
              }
            }
            //General Supplement child breakup start -->
            foreach ($general['date'] as $GCkey => $GCvalue) {
              if ($GCvalue==date('d/m/Y' ,strtotime($result[$j]['date']))) {
                foreach ($general['general'][$GCkey] as $GSNkey => $GSNvalue) {
                  if (isset($general['RWchildAmount'][$GCkey]) && isset($general['RWchildAmount'][$GCkey][$GSNvalue][$i+1])) {
                    $x++;
                    $GSCmamount = 0;
                    if ($revenue_markup['GeneralSupMarkup']!='') {
                        $total_markup = $agent_markup;
                      if ($revenue_markup['GeneralSupMarkuptype']=="Percentage") {
                        $GSCmamount = ((array_sum($general['RWchildAmount'][$GCkey][$GSNvalue][$i+1])*$revenue_markup['GeneralSupMarkup'])/100);
                      } else {
                        $GSCmamount = $revenue_markup['GeneralSupMarkup'];
                      }
                    }
                    $GCamount[$j-1] = (array_sum($general['RWchildAmount'][$GCkey][$GSNvalue][$i+1])*$total_markup)/100+array_sum($general['RWchildAmount'][$GCkey][$GSNvalue][$i+1])+$GSCmamount;
                    $TGCamount[$j-1] = $GCamount[$j-1]-($GCamount[$j-1]*$GDis)/100;
                    if ($RMdiscount['discount']!=0 && $RMdiscount['General']!=0) { 
                      // $return['generalSupplement']['child'.$GCkey]['old-price'][]= $GCamount[$j-1];
                    }
                    $return['amount_breakup'][$x]['Date'] = date('d/m/Y' ,strtotime($result[$j]['date']));
                    $return['amount_breakup'][$x]['Type'] = 'Child '.$GSNvalue;
                    $return['amount_breakup'][$x]['Board'] = $contractBoardCheck[0]['board'];
                    $return['amount_breakup'][$x]['Price'] = $GCamount[$j-1]-($GCamount[$j-1]*$GDis)/100;
                    $x++;
                  }
                }
              }
            }
          }

          //Extra bed breakup start
              if (isset($extrabed['date'][$j-1]) && isset($extrabed['RwextrabedAmount'][$j-1][$i])) {
                foreach ($extrabed['RwextrabedAmount'][$j-1][$i] as $exMkey => $exMvalue) {
                  $x++;
                  $EXamount = 0;
                  if ($extrabed['extrabedType'][$j-1][$i][$exMkey]=="Adult Extrabed" || $extrabed['extrabedType'][$j-1][$i][$exMkey]=="Child Extrabed") {
                    if ($revenue_markup['ExtrabedMarkup']!='') {
                          $total_markup = $agent_markup;
                          if ($revenue_markup['ExtrabedMarkuptype']=="Percentage") {
                          $EXamount = (($extrabed['RwextrabedAmount'][$j-1][$i][$exMkey]*$revenue_markup['ExtrabedMarkup'])/100);
                        } else {
                          $EXamount = $revenue_markup['ExtrabedMarkup'];
                        }
                      }
                   } else {
                    if ($revenue_markup['BoardSupMarkup']!='') {
                      $total_markup = $agent_markup;
                      if ($revenue_markup['BoardSupMarkuptype']=="Percentage") {
                        $EXamount = (($extrabed['RwextrabedAmount'][$j-1][$i][$exMkey]*$revenue_markup['BoardSupMarkup'])/100);
                      } else {
                        $EXamount = $revenue_markup['BoardSupMarkup'];
                      }
                    }
                  }                          
                  $FextrabedAmount[$j-1] =  ($extrabed['RwextrabedAmount'][$j-1][$i][$exMkey]*$total_markup)/100+$extrabed['RwextrabedAmount'][$j-1][$i][$exMkey]+$EXamount;          
                  $TFextrabedAmount[$j-1] += $FextrabedAmount[$j-1]-($FextrabedAmount[$j-1]*$exDis)/100; 
                  if ($RMdiscount['discount']!=0 && $RMdiscount['Extrabed']!=0) {   
                    // $return[$extrabed['extrabedType'][$j-1][$i][$exMkey]]['old-price'][] = $FextrabedAmount[$j-1];
                  }

                  $return['amount_breakup'][$x]['Date'] = date('d/m/Y' ,strtotime($result[$j]['date']));
                  $return['amount_breakup'][$x]['Type'] = $extrabed['extrabedType'][$j-1][$i][$exMkey];
                  $return['amount_breakup'][$x]['Board'] = $contractBoardCheck[0]['board'];
                  $return['amount_breakup'][$x]['Price'] = $FextrabedAmount[$j-1]-($FextrabedAmount[$j-1]*$exDis)/100;

                  $x++;
                }
              }
              $x++;
            }

            $witotal[$i] = array_sum($WiDisroomAmount)+array_sum($TFextrabedAmount)+array_sum($BBAamount)+array_sum($BBCamount)+array_sum($LAamount)+array_sum($LCamount)+array_sum($DAamount)+array_sum($DCamount)+array_sum($TGAamount)+array_sum($TGCamount);  
            $total[$i] = array_sum($DisroomAmount)+array_sum($TFextrabedAmount)+array_sum($BBAamount)+array_sum($BBCamount)+array_sum($LAamount)+array_sum($LCamount)+array_sum($DAamount)+array_sum($DCamount)+array_sum($TGAamount)+array_sum($TGCamount); 
            if ($discountGet['dis']=="true") {
              if ($discountGet['Extrabed']==1) {
                array_splice($TFextrabedAmount,$Fdays);
              }
              if ($discountGet['General']==1) {
                array_splice($TGAamount,$Fdays);
                array_splice($TGCamount,$Fdays);
              }
              if ($discountGet['Board']==1) {
                array_splice($BBAamount,$Fdays);
                array_splice($BBCamount,$Fdays);

                array_splice($LAamount,$Fdays);
                array_splice($LCamount,$Fdays);

                array_splice($DAamount,$Fdays);
                array_splice($DCamount,$Fdays);
              }
            }

            $totRmAmt[$i] = array_sum(array_splice($DisroomAmount, 1,$Fdays))+array_sum($TFextrabedAmount)+array_sum($BBAamount)+array_sum($BBCamount)+array_sum($LAamount)+array_sum($LCamount)+array_sum($DAamount)+array_sum($DCamount)+array_sum($TGAamount)+array_sum($TGCamount); 

            unset($DisroomAmount);
            unset($WiDisroomAmount);    

            if ($discountGet['dis']=="true") {
              $return['totalroomamount']['oldprice'] = $total[$i];
              $return['totalroomamount']['price'] = $totRmAmt[$i];
              $total[$i] = $totRmAmt[$i];
            } else {
               $return['totalroomamount']['price'] = $total[$i];  
            }
            $return['Cancellation_policy'][$i] = $this->get_CancellationPolicy_table($data,$contractid[$i],$roomid[$i],$data['hotelcode']);
            if($return['amount_breakup']=="" || $return['totalroomamount']['price']==0) {
              $response['result']['room'.($i+1)]['status'][] = 'Error'; 
              $response['result']['room'.($i+1)]['status']['description'] = 'Invalid Room Combination'; 
            } else {
              $response['room'.($i+1)]['status'] = 'Success'; 
              $response['room'.($i+1)] =$return; 
            }
            $return = array();
        }
        $finalAmount = array_sum($total);
        $tax = $this->general_tax($data['hotelcode']);
        $response['tax'] = ($finalAmount*$tax)/100;
        $finalAmount = ($finalAmount*$tax)/100+$finalAmount;
        $grandTotal = ($finalAmount*$tax)/100+$finalAmount;
        // $response['grandtotal'] = $grandTotal;

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
        $stmt = $this->db->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND
        FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "MLOS" AND numofnights <= '.$totalDays.') AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "MLOS" AND numofnights <= '.$totalDays.') order by Bkbefore desc limit 1');
        $stmt->execute();  
        $query = $stmt->fetchAll();
        if (count($query)==0) {
            $stmt = $this->db->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND
            FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "") order by Bkbefore desc limit 1');
            $stmt->execute();  
            $query = $stmt->fetchAll();
        }
        if (count($query)==0) {
            $stmt = $this->db->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND
            FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND discount_type = "EB") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND discount_type = "EB") order by Bkbefore desc limit 1');
            $stmt->execute();  
            $query = $stmt->fetchAll();
        }
        if (count($query)==0) {
            $stmt = $this->db->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND
                FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'") AND Bkbefore < '.$tot_days.' AND discount_type = "REB") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'") AND Bkbefore < '.$tot_days.' AND discount_type = "REB") order by Bkbefore desc limit 1');
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
                $return['discountCode'] = $query[0]['discountCode'];
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
        $stmt = $this->db->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1   AND
        FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$startdate.'" AND Styto >= "'.$startdate.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND stay_night <= '.$tot_days.'  AND discount_type = "stay&pay") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1  AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$startdate.'" AND Styto >= "'.$startdate.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND stay_night <= '.$tot_days.' AND discount_type = "stay&pay" order by stay_night desc) order by stay_night desc');
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
    public function get_CancellationPolicy_table($request,$contract_id,$room_id) {
      $stmt= $this->db->prepare("SELECT id FROM hotel_tbl_contract WHERE contract_id = '".$contract_id."' AND nonRefundable = 1");
      $stmt->execute();
      $refund = $stmt->fetchAll();
      $checkin_date=date_create($request['check_in']);
      $checkout_date=date_create($request['check_out']);
      $no_of_days=date_diff($checkin_date,$checkout_date);
      $tot_days = $no_of_days->format("%a");

      $disNRFVal = '';
      $stmt1 = $this->db->prepare("SELECT CONCAT(a.room_name,' ',b.Room_Type) as Name FROM hotel_tbl_hotel_room_type a INNER JOIN hotel_tbl_room_type b ON b.id = a.room_type WHERE a.id = '".$room_id."'");
      $stmt1->execute();
      $roomType = $stmt1->fetchAll();

      for ($i=0; $i < $tot_days ; $i++) {
        $dateOut = date('Y-m-d', strtotime($request['check_in']. ' + '.$i.'  days'));
        $disNRF[$i] = $this->DateWisediscountNonRefundable($dateOut,$request['hotelcode'],$room_id,$contract_id,'Room',$request['check_in'],$request['check_out']);
        if ($disNRF[$i]['NRF']==1) {
          $disNRFVal = $disNRF[$i]['discount'];
        }
      }

      $data[0]['RoomTypeName'] = $roomType[0]['Name'];

      if (count($refund)!=0) {
        $data[0]['RoomTypeName'] = $roomType[0]['Name'];
        $data[0]['FromDate'] = date("Y-m-d");
        $data[0]['ToDate'] = date("Y-m-d" ,strtotime($request['check_in']));
        $data[0]['application'] = 'Stay';
        $data[0]['ChargeType'] = "Percentage";
        $data[0]['CancellationCharge'] = "100";
      } else if($disNRFVal!='') {
        $data[0]['RoomTypeName'] = $roomType[0]['Name'];
        $data[0]['FromDate'] = date("Y-m-d");
        $data[0]['ToDate'] = date("Y-m-d" ,strtotime($request['check_in']));
        $data[0]['application'] = 'Stay';
        $data[0]['ChargeType'] = "Percentage";
        $data[0]['CancellationCharge'] = "100";
      } else {
        $data = array();
        $checkin_date=date_create($request['check_in']);
        $checkout_date=date_create($request['check_out']);
        $no_of_days=date_diff($checkin_date,$checkout_date);
        $tot_days = $no_of_days->format("%a");


        $start=date_create(date('m/d/Y'));
        $end=date_create($request['check_in']);
        $nod=date_diff($start,$end);
        $tot_days1 = $nod->format("%a");
        for($i = 0; $i < $tot_days; $i++) {
          $date[$i] = date('Y-m-d', strtotime($request['check_in']. ' + '.$i.'  days'));
          $stmt2[$i] = $this->db->prepare("SELECT * FROM hotel_tbl_cancellationfee WHERE '".$date[$i]."' BETWEEN fromDate AND toDate AND contract_id = '".$contract_id."'  AND FIND_IN_SET('".$room_id."', IFNULL(roomType,'')) > 0 AND hotel_id = '".$request['hotelcode']."' AND daysTo <= '".$tot_days1."' order by daysFrom desc");
          $stmt2[$i]->execute();
          $CancellationPolicyCheck[$i] = $stmt2[$i]->fetchAll();
          if (count($CancellationPolicyCheck[$i])!=0) {
            foreach ($CancellationPolicyCheck[$i] as $key => $value) {
              $data[$key]['RoomTypeName'] = $roomType[0]['Name'];
              $before = date('Y-m-d', strtotime('-'.$value['daysFrom'].' days', strtotime($request['check_in'])));
              $after = date('Y-m-d', strtotime('-'.$value['daysTo'].' days', strtotime($request['check_in'])));
              
              if ($before < date('Y-m-d')) {
                $data[$key]['FromDate'] = date('d/m/Y');
              } else {
                $data[$key]['FromDate'] = date('d/m/Y', strtotime('-'.$value['daysFrom'].' days', strtotime($request['check_in'])));
              }

              if ($after < date('Y-m-d')) {
                $data[$key]['ToDate'] = date('d/m/Y');
              } else {
                $data[$key]['ToDate'] = date('d/m/Y', strtotime('-'.$value['daysTo'].' days', strtotime($request['check_in'])));
              }
              
              
              $data[$key]['application'] = $value['application'];
              $data[$key]['ChargeType'] = "Percentage";
              $data[$key]['CancellationCharge'] = $value['cancellationPercentage'];
            }
          }
        }
        if (count($data)==0) {
            $data[0]['RoomTypeName'] = $roomType[0]['Name'];
            $data[0]['FromDate'] = date("Y-m-d");
            $data[0]['ToDate'] = date("Y-m-d" ,strtotime($request['check_in']));
            $data[0]['application'] = 'Stay';
            $data[0]['ChargeType'] = "Percentage";
            $data[0]['CancellationCharge'] = "100";
        }
      }
      return $data;
    }
    public function DateWisediscountNonRefundable($date,$hotel_id,$room_id,$contract_id,$type,$checkIn,$checkOut) {
      $chIn = date_create($checkIn);
      $chOut = date_create($checkOut);
      $noOfDays=date_diff($chIn,$chOut);
      $totalDays = $noOfDays->format("%a");
      
      $checkin_date=date_create($date);
      $checkout_date=date_create(date('Y-m-d'));
      $no_of_days=date_diff($checkin_date,$checkout_date);
      $tot_days = $no_of_days->format("%a");
      $discount = 0;
      $NRF = 0;
      $hotelidCheck = array();
      $contractCheck = array();
      $roomCheck = array();
      $BlackoutDateCheck = array();

      $stmt = $this->db->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND
        FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "MLOS" AND numofnights <= '.$totalDays.') AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "MLOS" AND numofnights <= '.$totalDays.')');
      $stmt->execute();
      $query = $stmt->fetchAll();

      if (count($query)==0) {
       $stmt = $this->db->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND
        FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "")');
       $stmt->execute();
       $query = $stmt->fetchAll();

      }
      if (count($query)==0) {
       $stmt = $this->db->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND
        FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND discount_type = "EB") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND discount_type = "EB")');
       $stmt->execute();
       $query = $stmt->fetchAll();
      }
      if (count($query)==0) {
       $stmt = $this->db->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND
        FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'") AND Bkbefore < '.$tot_days.' AND discount_type = "REB") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET("'.$date.'",BlackOut)=0 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'") AND Bkbefore < '.$tot_days.' AND discount_type = "REB")');
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
              $discount = $query[0]['discount'];
              $NRF = $query[0]['NonRefundable'];
            }

        }
        $return['discount'] = $discount;
        $return['NRF'] = $NRF;
     return $return;
    }
    public function get_policy_contract($hotel_id,$contract_id){
        $stmt = $this->db->prepare("SELECT Important_Remarks_Policies,Important_Notes_Conditions,cancelation_policy FROM hotel_tbl_policies WHERE hotel_id ='".$hotel_id."' and contract_id = '".$contract_id."'");
        $stmt->execute();  
        $query = $stmt->fetchAll();
        if (count($query)!=0) {
          $return1 = strip_tags($query[0]['Important_Remarks_Policies']);
          $return2 = strip_tags($query[0]['Important_Notes_Conditions']);
          $return3 = strip_tags($query[0]['cancelation_policy']);
          return $return1." ".$return2." ".$return3;
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
    public function xmlbookingreview($data,$agent_id) {
      $agent_markup = $this->mark_up_get($agent_id);
      $admin_markup = $this->general_mark_up_get($agent_id);
      $revenue_markup =  $this->xmlrevenue_markup('tbo',$agent_id,$data);
      $total_markup = $agent_markup+$admin_markup;
      if ($revenue_markup!='') {
        $total_markup = $agent_markup+$revenue_markup;
      }

      $agent_currency = 'AED';
      $availablity = 0;
      $response = array();
      $key = $data['sessionid'].'-'.$data['hotelcode'];
      $CachedString = $this->cache->getItem($key);
      $dd = $CachedString->get();
      $return = array();
      $checkin_date=date_create($data['check_in']);
      $checkout_date=date_create($data['check_out']);
      $no_of_days=date_diff($checkin_date,$checkout_date);
      $tot_days = $no_of_days->format("%a");
      $result = array();
      for($i = 0; $i < $tot_days; $i++) {
        $result[1+$i]['day'] = date('l', strtotime($data['check_in']. ' + '.$i.' days'));
        $result[1+$i]['date'] = date('m/d/Y', strtotime($data['check_in']. ' + '.$i.'  days'));
      }  
      if (isset($dd['HotelRooms']['HotelRoom'])) {
        foreach ($data['adults'] as $key => $value) {
          $RoomIndex[] = ["RoomIndex"=>[
                      "value"=> $data['RoomIndex'][$key]
                  ]];
       }
      $inp_arr = [
            "ResultIndex" =>[
              "value"=> $data['ResultIndex']
            ],
            "SessionId"=>[
              "value"=>$data['sessionid']
            ],
            "HotelCode"=>[
              "value"=>$data['hotelcode']
            ],
            "OptionsForBooking"=>[
              "value"=>[
                "FixedFormat"=>[
                  "value"=> true
                ],
                "RoomCombination"=>[
                  "value"=>$RoomIndex
                ]
              ]
            ]
        ];
        $CancellationPolicy = $this->AvailabilityAndPricing($inp_arr);
        if ($CancellationPolicy['Status']['StatusCode']==01) { 
          $response['Checkin']= $data['check_in'];
          $response['Checkout']= $data['check_out'];
          $response['Adults']= $data['adults'];
          $response['Child']= $data['child'];
          $response['no_of_rooms']= $data['no_of_rooms'];
          $response['no_of_days']=  $tot_days;
          
          if ($CancellationPolicy['Status']['StatusCode']==01) {       
              $cancelinfo = $CancellationPolicy['HotelCancellationPolicies'];
              if ($CancellationPolicy['AvailableForBook']=='false' && $CancellationPolicy['AvailableForConfirmBook']=='false') {
                 $availablity += 1;
              }
              if ($CancellationPolicy['CancellationPoliciesAvailable']=='false' || $CancellationPolicy['PriceVerification']['@attributes']['Status']=='Failed' || $CancellationPolicy['HotelDetailsVerification']['@attributes']['Status']=='Failed') {
                 $availablity += 1;
              }
              $HotelNorms = $CancellationPolicy['HotelCancellationPolicies'];
              $PriceChanged = $CancellationPolicy['PriceVerification'];
              if ($PriceChanged['@attributes']['Status']=="Successful" && $PriceChanged['@attributes']['PriceChanged']=="true" && $PriceChanged['@attributes']['AvailableOnNewPrice']=="true") {
                $availablity = 0;
              }
            } else {
              $availablity += 1;
            }

          if ($availablity==0) {
            $response['Available'] = true;
          } else {
            $response['Available'] = false;
          }
          if (is_array($HotelNorms['HotelNorms']['string'])) {
            $response['remarks_and_policies'] = implode("<br>", $HotelNorms['HotelNorms']['string']);
          } else {
            $response['remarks_and_policies'] = $HotelNorms['HotelNorms']['string'];
          }
          $total = array();
          $tax = array();
          if (isset($PriceChanged['@attributes']['PriceChanged']) && $PriceChanged['@attributes']['PriceChanged']=="true") {
            foreach ($data['RoomIndex'] as $key => $value) {
              $totalamt = 0;
              foreach ($PriceChanged['HotelRooms']['HotelRoom'] as $key1 => $value1) {
                if ($value1['RoomIndex']==$value) {

                  for ($i=0; $i <$tot_days ; $i++) {
                    if (isset($value1['RoomRate']['DayRates']['DayRate'][$i])) {
                      $DayRates = $value1['RoomRate']['DayRates']['DayRate'][$i]['@attributes']['BaseFare'];
                    } else {
                      $DayRates = $value1['RoomRate']['DayRates']['DayRate']['@attributes']['BaseFare'];
                    }
                    $DayRates = ($DayRates*$total_markup)/100+$DayRates;
                    $response['room'.($key+1)]['amount_breakup'][$i]['Date'] = date('d/m/Y' ,strtotime($result[$i+1]['date'])); 
                    $response['room'.($key+1)]['amount_breakup'][$i]['RoomName'] = $value1['RoomTypeName']; 
                    $response['room'.($key+1)]['amount_breakup'][$i]['Board'] = $value1['MealType']=="" || is_array($value1['MealType']) ? 'Room Only' : $value1['MealType'];
                    $response['room'.($key+1)]['amount_breakup'][$i]['Price'] =  $this->xml_currency_change($DayRates,$value1['RoomRate']['@attributes']['Currency'],$agent_currency); 
                    $totalamt+=$response['room'.($key+1)]['amount_breakup'][$i]['Price'];
                    $response['room'.($key+1)]['totalroomamount']['price'] = $totalamt;

                    // Cancellation policy
                      if(isset($cancelinfo) && count($cancelinfo)!=0) { 
                        if (isset($HotelNorms) && count($cancelinfo)!=0) {
                          if (isset($cancelinfo['CancelPolicies']['CancelPolicy'][0])) {
                            $cancelList = $cancelinfo['CancelPolicies']['CancelPolicy'];
                          } else {
                            $cancelList[0] = $cancelinfo['CancelPolicies']['CancelPolicy'];
                          } 

                          foreach ($cancelList as $key2 => $value2) {
                            if ($value==$value2['@attributes']['RoomIndex']) {
                              $response['room'.($key+1)]['Cancellation_policy'][$key2]['RoomTypeName'] = $value2['@attributes']['RoomTypeName']; 
                              $response['room'.($key+1)]['Cancellation_policy'][$key2]['FromDate'] =$value2['@attributes']['FromDate'];  
                              $response['room'.($key+1)]['Cancellation_policy'][$key2]['ToDate'] =$value2['@attributes']['ToDate'];  
                              $response['room'.($key+1)]['Cancellation_policy'][$key2]['ChargeType'] =$value2['@attributes']['ChargeType'];  
                              $response['room'.($key+1)]['Cancellation_policy'][$key2]['CancellationCharge'] =$value2['@attributes']['CancellationCharge'];  
                              $response['room'.($key+1)]['Cancellation_policy'][$key2]['Currency'] =$value2['@attributes']['Currency']; 
                              if ($value2['@attributes']['CancellationCharge']==0) {
                                  $response['room'.($key+1)]['Cancellation_policy'][$key2]['application'] = 'Free of charge';
                              } else {
                                  $response['room'.($key+1)]['Cancellation_policy'][$key2]['application'] = 'Stay';
                              } 
                            }
                          }

                        }
                      }

                  }

                  $taxAmount =  ($value1['RoomRate']['@attributes']['RoomTax']*$total_markup)/100+$value1['RoomRate']['@attributes']['RoomTax'];
                  $tax[$key] = $this->xml_currency_change($taxAmount,$value1['RoomRate']['@attributes']['Currency'],$agent_currency); 
                  $total[$key] = $this->xml_currency_change($value1['RoomRate']['@attributes']['TotalFare'],$value1['RoomRate']['@attributes']['Currency'],$agent_currency);
                }
              }
            }
            $response['tax'] = array_sum($tax);
            $totalAmount['total'] = array_sum($total);
            $AvailableRooms = array_merge($dd,$CancellationPolicy['HotelDetails'],$totalAmount);
            $key = $data['sessionid'].'-'.$data['hotelcode'].'1';
            $CachedString = $this->cache->getItem($key);
            $CachedString->set($AvailableRooms)->expiresAfter(18000);
            $this->cache->save($CachedString);
          } else {
              $tax = array();
              $total = array();
              foreach ($data['RoomIndex'] as $key => $value) {
                $totalamt = 0;
                foreach ($dd['HotelRooms']['HotelRoom'] as $key1 => $value1) {
                  if ($value1['RoomIndex']==$value) {
                    for ($i=0; $i <$tot_days ; $i++) {
                      if (isset($value1['RoomRate']['DayRates']['DayRate'][$i])) {
                        $DayRates = $value1['RoomRate']['DayRates']['DayRate'][$i]['@attributes']['BaseFare'];
                      } else {
                        $DayRates = $value1['RoomRate']['DayRates']['DayRate']['@attributes']['BaseFare'];
                      }
                      $DayRates = ($DayRates*$total_markup)/100+$DayRates;
                      $response['room'.($key+1)]['amount_breakup'][$i]['Date'] = date('d/m/Y' ,strtotime($result[$i+1]['date'])); 
                      $response['room'.($key+1)]['amount_breakup'][$i]['RoomName'] = $value1['RoomTypeName']; 
                      $response['room'.($key+1)]['amount_breakup'][$i]['Board'] = $value1['MealType']=="" || is_array($value1['MealType']) ? 'Room Only' : $value1['MealType'];
                      $response['room'.($key+1)]['amount_breakup'][$i]['Price'] =  $this->xml_currency_change($DayRates,$value1['RoomRate']['@attributes']['Currency'],$agent_currency); 
                      $totalamt+=$response['room'.($key+1)]['amount_breakup'][$i]['Price'];
                      $response['room'.($key+1)]['totalroomamount']['price'] = $totalamt;


                      // Cancellation policy
                      if(isset($cancelinfo) && count($cancelinfo)!=0) { 
                        if (isset($HotelNorms) && count($cancelinfo)!=0) {
                          if (isset($cancelinfo['CancelPolicies']['CancelPolicy'][0])) {
                            $cancelList = $cancelinfo['CancelPolicies']['CancelPolicy'];
                          } else {
                            $cancelList[0] = $cancelinfo['CancelPolicies']['CancelPolicy'];
                          } 

                          foreach ($cancelList as $key2 => $value2) {
                            if ($value==$value2['@attributes']['RoomIndex']) {
                              $response['room'.($key+1)]['Cancellation_policy'][$key2]['RoomTypeName'] = $value2['@attributes']['RoomTypeName']; 
                              $response['room'.($key+1)]['Cancellation_policy'][$key2]['FromDate'] =$value2['@attributes']['FromDate'];  
                              $response['room'.($key+1)]['Cancellation_policy'][$key2]['ToDate'] =$value2['@attributes']['ToDate'];  
                              $response['room'.($key+1)]['Cancellation_policy'][$key2]['ChargeType'] =$value2['@attributes']['ChargeType'];  
                              $response['room'.($key+1)]['Cancellation_policy'][$key2]['CancellationCharge'] =$value2['@attributes']['CancellationCharge'];  
                              $response['room'.($key+1)]['Cancellation_policy'][$key2]['Currency'] =$value2['@attributes']['Currency']; 
                              if ($value2['@attributes']['CancellationCharge']==0) {
                                  $response['room'.($key+1)]['Cancellation_policy'][$key2]['application'] = 'Free of charge';
                              } else {
                                  $response['room'.($key+1)]['Cancellation_policy'][$key2]['application'] = 'Stay';
                              } 
                            }
                          }

                        }
                      }
                    }
                    $taxAmount =  ($value1['RoomRate']['@attributes']['RoomTax']*$total_markup)/100+$value1['RoomRate']['@attributes']['RoomTax'];
                    $tax[$key] = $this->xml_currency_change($taxAmount,$value1['RoomRate']['@attributes']['Currency'],$agent_currency); 
                    $total[$key] = $this->xml_currency_change($value1['RoomRate']['@attributes']['TotalFare'],$value1['RoomRate']['@attributes']['Currency'],$agent_currency);
                  }
                }
              }
              $response['tax'] = array_sum($tax);
              $totalAmount['total'] = array_sum($total);
              $AvailableRooms = array_merge($dd,$CancellationPolicy['HotelDetails'],$totalAmount);
              $key = $data['sessionid'].'-'.$data['hotelcode'].'1';
              $CachedString = $this->cache->getItem($key);
              $CachedString->set($AvailableRooms)->expiresAfter(1800);
              $this->cache->save($CachedString);
          }
        }
      }
      return $response;
    }
    public function AvailabilityAndPricing($arg){
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
  public function checkContract($data) {
    for ($i=0; $i < $data['no_of_rooms']; $i++) { 
        $roomindex = explode('-',$data['RoomIndex'][$i]);
        if(isset($roomindex[0])) {
          $contractid[$i] = $roomindex[0];
        } else {
           $contractid[$i] = "";
        }
        if(isset($roomindex[1])) {
          $roomid[$i] = $roomindex[1];
        } else {
           $roomid[$i] = "";
        }
        $stmt = $this->db->prepare("SELECT * FROM hotel_tbl_contract a join hotel_tbl_hotel_room_type b on a.hotel_id=b.hotel_id where a.contract_id = '".$contractid[$i]."' and b.id=".$roomid[$i]." and a.hotel_id =".$data['hotelcode']." and b.hotel_id=".$data['hotelcode']."");
        $stmt->execute();
        $final = $stmt->fetchAll();
        return $final;
    }
  }
}