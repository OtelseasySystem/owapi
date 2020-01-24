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
        // if(!isset($data['location']) || $data['location'] == '') {
        //     $return['location'] = 'Location is mandatory';
        // }
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

        if (isset($data['check_in']) &&  date('Y-m-d',strtotime($data['check_in'])) < date('Y-m-d')) {
            $return['check_in_err'] = 'Check in date must be equal or greater than the current date ('.date('d-m-Y').')';
        }
        if (isset($data['check_in']) && isset($data['check_out']) &&  date('Y-m-d',strtotime($data['check_in'])) >= date('Y-m-d',strtotime($data['check_out']))) {
            $return['check_out_err'] = 'Check out date must be  greater than the checkin date ('.$data['check_in'].')';
        }
        if (isset($data['check_in'])) {
          if (preg_match("/^([0-9]{2})-([0-9]{2})-([0-9]{4})$/",$data['check_in'])) {
          } else {
            $return['check_in_err'] = 'Invalid Check in date';
          }
        }

        if (isset($data['check_out'])) {
          if (preg_match("/^([0-9]{2})-([0-9]{2})-([0-9]{4})$/",$data['check_out'])) {
          } else {
            $return['check_out_err'] = 'Invalid Check out date';
          }
        }

        if (isset($data['no_of_rooms']) && $data['no_of_rooms'] > 6) {
            $return['no_of_rooms_err'] = 'Max number of rooms 6';
        }

        if (isset($data['no_of_rooms']) && $data['no_of_rooms'] <= 0) {
            $return['no_of_rooms_err'] = 'Min number of rooms 1';
        }
        if (!isset($data['Room'])) {
            $return['Room_err'] = 'Room is required and mention all RoomGuests details';
        }
        if (isset($data['Room']) && isset($data['no_of_rooms']) && $data['no_of_rooms'] != count($data['Room'])) {
          $return['Room'] = 'No of rooms and Room array Should be equal';
        }
        if(isset($data['no_of_rooms']) && $data['no_of_rooms'] != '' && isset($data['Room']) && $data['no_of_rooms'] <= 6) {
            for($i=0;$i<$data['no_of_rooms'];$i++) {
              $condition = $data['Room'];
                if(!isset($condition[$i]['adults']) || $condition[$i]['adults'] == '') {
                  $return['Room-'.($i+1).'-adults'] = 'Room '.($i+1).' adults value is missing';
                }
                if(!isset($condition[$i]['child']) ||  $condition[$i]['child'] == '') {
                  $return['Room-'.($i+1).'-child'] = 'Room '.($i+1).' child value is missing';
                }

                if(isset($condition[$i]['adults']) && !is_numeric($condition[$i]['adults'])) {
                  $return['Room-'.($i+1).'-adults'] = 'Room '.($i+1).' adults value is Should numeric';
                }

                if(isset($condition[$i]['child']) && !is_numeric($condition[$i]['child'])) {
                  $return['Room-'.($i+1).'-child'] = 'Room '.($i+1).' child value is Should numeric';
                }

                if(isset($condition[$i]['adults']) && $condition[$i]['adults'] > 10) {
                  $return['Room-'.($i+1).'-adults'] = 'Room '.($i+1).' adults value is Should equal or less than 10';
                }

                if(isset($condition[$i]['child']) && $condition[$i]['child'] > 4) {
                  $return['Room-'.($i+1).'-child'] = 'Room '.($i+1).' child value is Should equal or less than 4';
                }

                if(isset($condition[$i]['child']) && $condition[$i]['child']!=0  && $condition[$i]['child'] <= 4) {
                    for($j=0;$j<$condition[$i]['child'];$j++) {
                        if(!isset($condition[$i]['Room'.($i+1).'ChildAge']) || (!is_array($condition[$i]['Room'.($i+1).'ChildAge']) || !isset($condition[$i]['Room'.($i+1).'ChildAge'][$j]))) {
                          $return['Room-'.($i+1).'-ChildAge-'.($j+1)] = 'Room '.($i+1).' child '.($j+1).' age value is missing';
                        }
                        if (isset($condition[$i]['Room'.($i+1).'ChildAge'][$j]) && !is_numeric($condition[$i]['Room'.($i+1).'ChildAge'][$j])) {
                          $return['Room-'.($i+1).'-ChildAge-'.($j+1)] = 'Room '.($i+1).' child '.($j+1).' age value is Should numeric';
                        }

                        if (isset($condition[$i]['Room'.($i+1).'ChildAge'][$j]) && is_numeric($condition[$i]['Room'.($i+1).'ChildAge'][$j]) && $condition[$i]['Room'.($i+1).'ChildAge'][$j] > 17) {
                          $return['Room-'.($i+1).'-ChildAge-'.($j+1)] = 'Room '.($i+1).' child '.($j+1).' age value is Should equal or less than 17';
                        }
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
    function getHotelList($data,$agent_id) {
      $outData = array();
      $search = '';
      $hotelName = '';

      $checkin_date=date_create($data['check_in']);
      $checkout_date=date_create($data['check_out']);
      $no_of_days=date_diff($checkin_date,$checkout_date);
      $tot_days = $no_of_days->format("%a");


      if (!empty($data['location'])) {
        $str = explode('-',$data['location']);
        if (!isset($str[1])) {
          $str = explode(',',$data['location']);
        }
        $data['location'] = $str[0];
        // $search = "a.location LIKE '%".$data['location']."%' OR a.keywords LIKE '%".$data['location']."%' OR a.city LIKE '%".$data['location']."%' OR b.name  = '".$data['location']."' ";
      }
      $search = "b.name  = '".$data['cityname']."' ";
      
      if ($search!='') {
        $search = '('.$search.') AND ';
      }

      if (isset($data['HotelName']) && $data['HotelName']!="") {
        $hotelName = " hotel_name  LIKE '%".$data['HotelName']."%' AND ";
      }
      $room2 = "";
      $room3 = "";
      $room4 = "";
      $room5 = "";
      $room6 = "";

      for ($v=0; $v <=6 ; $v++) { 
        if (isset($data['Room'][$v]['adults'])) {
          $data['adults'][$v] = $data['Room'][$v]['adults'];
          
        }
        if (isset($data['Room'][$v]['child'])) {
          $data['child'][$v] = $data['Room'][$v]['child'];
          for ($u=0; $u <=4 ; $u++) { 
            if (isset($data['Room'][$v]['Room'.($v+1).'ChildAge'][$u])) {
              $data['Room'.($v+1).'ChildAge'][$u] = $data['Room'][$v]['Room'.($v+1).'ChildAge'][$u];
            }
          }
        }
      }
      
      if (isset($data['adults'][1])) {
        $room2 =" OR (c.max_total >= ".($data['adults'][1]+$data['child'][1])." AND c.occupancy >= ".$data['adults'][1]." AND c.occupancy_child >= ".$data['child'][1].")";
      }
      if (isset($data['adults'][2])) {
        $room3 =" OR (c.max_total >= ".($data['adults'][2]+$data['child'][2])." AND c.occupancy >= ".$data['adults'][2]." AND c.occupancy_child >= ".$data['child'][2].")";
      }
      if (isset($data['adults'][3])) {
        $room4 =" OR (c.max_total >= ".($data['adults'][3]+$data['child'][3])." AND c.occupancy >= ".$data['adults'][3]." AND c.occupancy_child >= ".$data['child'][3].")";
      }
      if (isset($data['adults'][4])) {
        $room5 =" OR (c.max_total >= ".($data['adults'][4]+$data['child'][4])." AND c.occupancy >= ".$data['adults'][4]." AND c.occupancy_child >= ".$data['child'][4].")";
      }
      if (isset($data['adults'][5])) {
        $room6 =" OR (c.max_total >= ".($data['adults'][5]+$data['child'][5])." AND c.occupancy >= ".$data['adults'][5]." AND c.occupancy_child >= ".$data['child'][5].")";
      }
      
      $stmt = $this->db->prepare("SELECT a.id FROM hotel_tbl_hotels a INNER JOIN states b ON  b.id = IF(a.state!='',a.state,0) INNER JOIN hotel_tbl_hotel_room_type c ON c.hotel_id = a.id WHERE ".$search.$hotelName."  a.delflg = 1 and ((c.max_total >= ".($data['adults'][0]+$data['child'][0])." AND c.occupancy >= ".$data['adults'][0]." AND c.occupancy_child >= ".$data['child'][0].") ".$room2.$room3.$room4.$room5.$room6.")  and c.delflg = 1 group by a.id");
        $stmt->execute();
        $hotelidArr = array();
        foreach ($stmt->fetchAll() as $key => $value) {
          $hotelidArr[$key] = $value['id'];
        }
        if (count($hotelidArr)!=0) {
          $searchHotel_id = implode(",", array_unique($hotelidArr));
        } else {
          $searchHotel_id = 0;
        }
      $nat = $this->db->prepare("select id from countries where name = '".$data['nationality']."' ");
      $nat->execute();
      $tt = $nat->fetchAll();
      if (isset($tt[0]['id'])) {
        $nationality = $tt[0]['id'];
      } else {
        $nat = $this->db->prepare("select Country from hotel_tbl_agents where id = ".$agent_id." ");
        $nat->execute();
        $tt = $nat->fetchAll();
        $nationality = $tt[0]['Country'];
      }

      $ot = $this->db->prepare("SELECT id,hotel_id,contract_type,linkedContract FROM hotel_tbl_contract a WHERE not exists (select 1 from  hotel_agent_permission b where   a.contract_id = b.contract_id  AND FIND_IN_SET('".$agent_id."', IFNULL(permission,'')) > 0) AND FIND_IN_SET('".$nationality."', IFNULL(nationalityPermission,'')) = 0 
     AND not exists (select 1 from hotel_country_permission c where a.contract_id = c.contract_id and FIND_IN_SET('AED', IFNULL(permission,'')) > 0) AND hotel_id IN (".$searchHotel_id.") AND from_date <= '".date('Y-m-d',strtotime($data['check_in']))."' AND to_date >= '".date('Y-m-d',strtotime($data['check_in']))."' AND  from_date < '".date('Y-m-d',strtotime($data['check_out']. ' -1 days'))."' AND to_date >= '".date('Y-m-d',strtotime($data['check_out']. ' -1 days'))."' AND contract_flg  = 1");
      $ot->execute();
      foreach ($ot->fetchAll() as $key5 => $value5) {
        $outData['hotel_id'][$key5] = $value5['hotel_id'];
        $outData['contract_id'][$key5] = $value5['id'];
      }
      $dateAlt = array();
      for($i = 0; $i < $tot_days; $i++) {
        $dateAlt[$i] = date('Y-m-d', strtotime($data['check_in']. ' + '.$i.'  days'));
      }

      $implode_data = implode("','", $dateAlt);
      if (isset($outData['hotel_id'][0]) && $outData['hotel_id'][0]!='') {
        $implode_data1 = implode(",", array_unique($outData['hotel_id']));
      } else {
        $implode_data1 = "0";
      }
      if (isset($outData['contract_id'][0]) && $outData['contract_id'][0]!='') {
        $implode_data2 = implode(",", array_unique($outData['contract_id']));
      } else {
        $implode_data2 = "0";
      }

      $room1 = "";
      $room2 = "";
      $room3 = "";
      $room4 = "";
      $room5 = "";
      $room6 = "";
      // Room 1
      $markup = $this->mark_up_get($agent_id);
      $general_markup = $this->general_mark_up_get($agent_id);

      if (isset($data['adults'][0])) {
      $Room1ChildAge1 = 0; 
      $Room1ChildAge2 = 0; 
      $Room1ChildAge3 = 0; 
      $Room1ChildAge4 = 0; 
      if (isset($data['Room1ChildAge'][0])) {
        $Room1ChildAge1 = $data['Room1ChildAge'][0]; 
      }
      if (isset($data['Room1ChildAge'][1])) {
        $Room1ChildAge2 = $data['Room1ChildAge'][1]; 
      }
      if (isset($data['Room1ChildAge'][2])) {
        $Room1ChildAge3 = $data['Room1ChildAge'][2]; 
      }
      if (isset($data['Room1ChildAge'][3])) {
        $Room1ChildAge4 = $data['Room1ChildAge'][3]; 
      }

      $room1 = "SELECT *,min(TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday))) as dd FROM (
        SELECT *,IF(".$data['adults'][0]."
            +IF(0=".$Room1ChildAge1.",0,IF(max_child_age< ".$Room1ChildAge1.",1,0))
            +IF(0=".$Room1ChildAge2.",0,IF(max_child_age< ".$Room1ChildAge2.",1,0))
            +IF(0=".$Room1ChildAge3.",0,IF(max_child_age< ".$Room1ChildAge3.",1,0))
            +IF(0=".$Room1ChildAge4.",0,IF(max_child_age< ".$Room1ChildAge4.",1,0)) > standard_capacity && extrabed=0,0,sum(TtlPrice))  as TotalPrice,count(*) as counts,IF(min(allotment)=0,'On Request','Book') as RequestType, IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0) as exAmount,
         sum(IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0)) as exAmountTot
        ,IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
        sum(IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100))) as exChildAmountTot ,
        IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))  as boardChildAmount
      ,sum(IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))) as boardChildAmountTot,
      IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0) as generalsubAmount,
     sum(IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0)) as generalsubAmountTot

      FROM (select a.hotel_id,a.contract_id,a.room_id,a.allotement as allotment, dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,1 as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,con.max_child_age,f.standard_capacity,

        (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

       ((select GetDiscount(a.allotement_date,a.hotel_id,a.contract_id,a.room_id,'".date('Y-m-d')."',".$tot_days.")/100)) as TtlPrice,


        (select IF(count(*)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100)+(amount*".$markup."/100),amount+ExtrabedMarkup+(amount*".$markup."/100)),amount+(sum(amount)*".($markup+$general_markup)."/100)),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['adults'][0]."
            +IF(0=".$Room1ChildAge1.",0,IF(con.max_child_age< ".$Room1ChildAge1.",1,0))
            +IF(0=".$Room1ChildAge2.",0,IF(con.max_child_age< ".$Room1ChildAge2.",1,0))
            +IF(0=".$Room1ChildAge3.",0,IF(con.max_child_age< ".$Room1ChildAge3.",1,0))
            +IF(0=".$Room1ChildAge4.",0,IF(con.max_child_age< ".$Room1ChildAge4.",1,0)) > f.standard_capacity ) as extrabed, 


        (select IF(count(*)=0,'',IF(0=".$Room1ChildAge1.",0,IF(ChildAgeFrom < ".$Room1ChildAge1." && ChildAgeTo >= ".$Room1ChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['adults'][0]+$data['child'][0])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=IF(0=".$Room1ChildAge1.",0,IF(con.max_child_age >= ".$Room1ChildAge1.",1,0)),0,sum(IF(startAge <= ".$Room1ChildAge1." && finalAge >= ".$Room1ChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))

        +IF(count(*)=0,0,IF(0=IF(0=".$Room1ChildAge2.",0,IF(con.max_child_age >= ".$Room1ChildAge2.",1,0)),0,sum(IF(startAge <= ".$Room1ChildAge2." && finalAge >= ".$Room1ChildAge2.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))
        +IF(count(*)=0,0,IF(0=IF(0=".$Room1ChildAge3.",0,IF(con.max_child_age >= ".$Room1ChildAge3.",1,0)),0,sum(IF(startAge <= ".$Room1ChildAge3." && finalAge >= ".$Room1ChildAge3.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))
        +IF(count(*)=0,0,IF(0=IF(0=".$Room1ChildAge4.",0,IF(con.max_child_age >= ".$Room1ChildAge3.",1,0)),0,sum(IF(startAge <= ".$Room1ChildAge4." && finalAge >= ".$Room1ChildAge4.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0)))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['adults'][0].")+(adultAmount*".$data['adults'][0].")*GeneralSupMarkup/100,(adultAmount*".$data['adults'][0].")+(GeneralSupMarkup*".$data['adults'][0].")),(adultAmount*".$data['adults'][0].")+((adultAmount*".$data['adults'][0].")*".$general_markup."/100)) + ((adultAmount*".$data['adults'][0].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(*)=0,0, IF(0=".$Room1ChildAge1." && childAmount=0,0,IF(MinChildAge < ".$Room1ChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(*)=0,0, IF(0=".$Room1ChildAge2." && childAmount=0,0,IF(MinChildAge < ".$Room1ChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room1ChildAge3." && childAmount=0,0,IF(MinChildAge < ".$Room1ChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room1ChildAge4." && childAmount=0,0,IF(MinChildAge < ".$Room1ChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

        (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND Extrabed = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as exdis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND Board = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as boarddis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND General = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0 AND  FIND_IN_SET(".$agent_id.", IFNULL(rev.Agents,'')) > 0  AND rev.FromDate <= a.allotement_date AND  rev.ToDate >= a.allotement_date 

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id where (f.max_total >= ".($data['adults'][0]+$data['child'][0])." AND f.occupancy >= ".$data['adults'][0]."
        +IF(0=".$Room1ChildAge1.",0,IF(con.max_child_age< ".$Room1ChildAge1.",1,0))
        +IF(0=".$Room1ChildAge2.",0,IF(con.max_child_age< ".$Room1ChildAge2.",1,0))
        +IF(0=".$Room1ChildAge3.",0,IF(con.max_child_age< ".$Room1ChildAge3.",1,0))
        +IF(0=".$Room1ChildAge4.",0,IF(con.max_child_age< ".$Room1ChildAge4.",1,0))


        AND f.occupancy_child >= ".$data['child'][0].") AND f.delflg = 1 AND a.allotement_date BETWEEN  '".date('Y-m-d',strtotime($data['check_in']))."' AND '".date('Y-m-d',strtotime('-1 day', strtotime($data['check_out'])))."' AND a.contract_fr_id IN (".$implode_data2.") AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0  AND a.hotel_id IN (".$implode_data1.") AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x where x.TotalPrice != 0 GROUP By x.hotel_id";
    }

    if (isset($data['adults'][1])) {

      $Room2ChildAge1 = 0; 
      $Room2ChildAge2 = 0; 
      $Room2ChildAge3 = 0; 
      $Room2ChildAge4 = 0; 
      if (isset($data['Room2ChildAge'][0])) {
        $Room2ChildAge1 = $data['Room2ChildAge'][0]; 
      }
      if (isset($data['Room2ChildAge'][1])) {
        $Room2ChildAge2 = $data['Room2ChildAge'][1]; 
      }
      if (isset($data['Room2ChildAge'][2])) {
        $Room2ChildAge3 = $data['Room2ChildAge'][2]; 
      }
      if (isset($data['Room2ChildAge'][3])) {
        $Room2ChildAge4 = $data['Room2ChildAge'][3]; 
      }

      $room2 = " UNION SELECT *,min(TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday))) as dd FROM (
        SELECT *,IF(".$data['adults'][1]."
            +IF(0=".$Room2ChildAge1.",0,IF(max_child_age< ".$Room2ChildAge1.",1,0))
            +IF(0=".$Room2ChildAge2.",0,IF(max_child_age< ".$Room2ChildAge2.",1,0))
            +IF(0=".$Room2ChildAge3.",0,IF(max_child_age< ".$Room2ChildAge3.",1,0))
            +IF(0=".$Room2ChildAge4.",0,IF(max_child_age< ".$Room2ChildAge4.",1,0)) > standard_capacity && extrabed=0,0,sum(TtlPrice))  as TotalPrice,count(*) as counts,IF(min(allotment)=0,'On Request','Book') as RequestType, IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0) as exAmount,
         sum(IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0)) as exAmountTot
        ,IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
        sum(IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100))) as exChildAmountTot ,
        IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))  as boardChildAmount
      ,sum(IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))) as boardChildAmountTot,
      IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0) as generalsubAmount,
     sum(IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0)) as generalsubAmountTot

      FROM (select a.hotel_id,a.contract_id,a.room_id,a.allotement as allotment, dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,2 as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,con.max_child_age,f.standard_capacity,

        (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

       ((select GetDiscount(a.allotement_date,a.hotel_id,a.contract_id,a.room_id,'".date('Y-m-d')."',".$tot_days.")/100)) as TtlPrice,


        (select IF(count(*)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100)+(amount*".$markup."/100),amount+ExtrabedMarkup+(amount*".$markup."/100)),amount+(sum(amount)*".($markup+$general_markup)."/100)),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['adults'][1]."
            +IF(0=".$Room2ChildAge1.",0,IF(con.max_child_age< ".$Room2ChildAge1.",1,0))
            +IF(0=".$Room2ChildAge2.",0,IF(con.max_child_age< ".$Room2ChildAge2.",1,0))
            +IF(0=".$Room2ChildAge3.",0,IF(con.max_child_age< ".$Room2ChildAge3.",1,0))
            +IF(0=".$Room2ChildAge4.",0,IF(con.max_child_age< ".$Room2ChildAge4.",1,0)) > f.standard_capacity ) as extrabed, 


        (select IF(count(*)=0,'',IF(0=".$Room2ChildAge1.",0,IF(ChildAgeFrom < ".$Room2ChildAge1." && ChildAgeTo >= ".$Room2ChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['adults'][1]+$data['child'][1])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=IF(0=".$Room2ChildAge1.",0,IF(con.max_child_age >= ".$Room2ChildAge1.",1,0)),0,sum(IF(startAge <= ".$Room2ChildAge1." && finalAge >= ".$Room2ChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))

        +IF(count(*)=0,0,IF(0=IF(0=".$Room2ChildAge2.",0,IF(con.max_child_age >= ".$Room2ChildAge2.",1,0)),0,sum(IF(startAge <= ".$Room2ChildAge2." && finalAge >= ".$Room2ChildAge2.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))
        +IF(count(*)=0,0,IF(0=IF(0=".$Room2ChildAge3.",0,IF(con.max_child_age >= ".$Room2ChildAge3.",1,0)),0,sum(IF(startAge <= ".$Room2ChildAge3." && finalAge >= ".$Room2ChildAge3.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))
        +IF(count(*)=0,0,IF(0=IF(0=".$Room2ChildAge4.",0,IF(con.max_child_age >= ".$Room2ChildAge3.",1,0)),0,sum(IF(startAge <= ".$Room2ChildAge4." && finalAge >= ".$Room2ChildAge4.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0)))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['adults'][1].")+(adultAmount*".$data['adults'][1].")*GeneralSupMarkup/100,(adultAmount*".$data['adults'][1].")+(GeneralSupMarkup*".$data['adults'][1].")),(adultAmount*".$data['adults'][1].")+((adultAmount*".$data['adults'][1].")*".$general_markup."/100)) + ((adultAmount*".$data['adults'][1].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(*)=0,0, IF(0=".$Room2ChildAge1." && childAmount=0,0,IF(MinChildAge < ".$Room2ChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(*)=0,0, IF(0=".$Room2ChildAge2." && childAmount=0,0,IF(MinChildAge < ".$Room2ChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room2ChildAge3." && childAmount=0,0,IF(MinChildAge < ".$Room2ChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room2ChildAge4." && childAmount=0,0,IF(MinChildAge < ".$Room2ChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

        (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND Extrabed = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as exdis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND Board = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as boarddis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND General = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0 AND  FIND_IN_SET(".$agent_id.", IFNULL(rev.Agents,'')) > 0  AND rev.FromDate <= a.allotement_date AND  rev.ToDate >= a.allotement_date 

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id where (f.max_total >= ".($data['adults'][1]+$data['child'][1])." AND f.occupancy >= ".$data['adults'][1]."
        +IF(0=".$Room2ChildAge1.",0,IF(con.max_child_age< ".$Room2ChildAge1.",1,0))
        +IF(0=".$Room2ChildAge2.",0,IF(con.max_child_age< ".$Room2ChildAge2.",1,0))
        +IF(0=".$Room2ChildAge3.",0,IF(con.max_child_age< ".$Room2ChildAge3.",1,0))
        +IF(0=".$Room2ChildAge4.",0,IF(con.max_child_age< ".$Room2ChildAge4.",1,0))


        AND f.occupancy_child >= ".$data['child'][1].") AND f.delflg = 1 AND a.allotement_date BETWEEN  '".date('Y-m-d',strtotime($data['check_in']))."' AND '".date('Y-m-d',strtotime('-1 day', strtotime($data['check_out'])))."' AND a.contract_fr_id IN (".$implode_data2.") AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0  AND a.hotel_id IN (".$implode_data1.") AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x where x.TotalPrice != 0 GROUP By x.hotel_id";
    }
    if (isset($data['adults'][2])) {
      $Room3ChildAge1 = 0; 
      $Room3ChildAge2 = 0; 
      $Room3ChildAge3 = 0; 
      $Room3ChildAge4 = 0; 
      if (isset($data['Room3ChildAge'][0])) {
        $Room3ChildAge1 = $data['Room3ChildAge'][0]; 
      }
      if (isset($data['Room3ChildAge'][1])) {
        $Room3ChildAge2 = $data['Room3ChildAge'][1]; 
      }
      if (isset($data['Room3ChildAge'][2])) {
        $Room3ChildAge3 = $data['Room3ChildAge'][2]; 
      }
      if (isset($data['Room3ChildAge'][3])) {
        $Room3ChildAge4 = $data['Room3ChildAge'][3]; 
      }

      $room3 = " UNION SELECT *,min(TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday))) as dd FROM (
        SELECT *,IF(".$data['adults'][2]."
            +IF(0=".$Room3ChildAge1.",0,IF(max_child_age< ".$Room3ChildAge1.",1,0))
            +IF(0=".$Room3ChildAge2.",0,IF(max_child_age< ".$Room3ChildAge2.",1,0))
            +IF(0=".$Room3ChildAge3.",0,IF(max_child_age< ".$Room3ChildAge3.",1,0))
            +IF(0=".$Room3ChildAge4.",0,IF(max_child_age< ".$Room3ChildAge4.",1,0)) > standard_capacity && extrabed=0,0,sum(TtlPrice))  as TotalPrice,count(*) as counts,IF(min(allotment)=0,'On Request','Book') as RequestType, IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0) as exAmount,
         sum(IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0)) as exAmountTot
        ,IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
        sum(IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100))) as exChildAmountTot ,
        IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))  as boardChildAmount
      ,sum(IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))) as boardChildAmountTot,
      IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0) as generalsubAmount,
     sum(IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0)) as generalsubAmountTot

      FROM (select a.hotel_id,a.contract_id,a.room_id,a.allotement as allotment, dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,3 as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,con.max_child_age,f.standard_capacity,

        (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

       ((select GetDiscount(a.allotement_date,a.hotel_id,a.contract_id,a.room_id,'".date('Y-m-d')."',".$tot_days.")/100)) as TtlPrice,


        (select IF(count(*)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100)+(amount*".$markup."/100),amount+ExtrabedMarkup+(amount*".$markup."/100)),amount+(sum(amount)*".($markup+$general_markup)."/100)),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['adults'][2]."
            +IF(0=".$Room2ChildAge1.",0,IF(con.max_child_age< ".$Room2ChildAge1.",1,0))
            +IF(0=".$Room2ChildAge2.",0,IF(con.max_child_age< ".$Room2ChildAge2.",1,0))
            +IF(0=".$Room2ChildAge3.",0,IF(con.max_child_age< ".$Room2ChildAge3.",1,0))
            +IF(0=".$Room2ChildAge4.",0,IF(con.max_child_age< ".$Room2ChildAge4.",1,0)) > f.standard_capacity ) as extrabed, 


        (select IF(count(*)=0,'',IF(0=".$Room2ChildAge1.",0,IF(ChildAgeFrom < ".$Room2ChildAge1." && ChildAgeTo >= ".$Room2ChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['adults'][2]+$data['child'][2])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=IF(0=".$Room2ChildAge1.",0,IF(con.max_child_age >= ".$Room2ChildAge1.",1,0)),0,sum(IF(startAge <= ".$Room2ChildAge1." && finalAge >= ".$Room2ChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))

        +IF(count(*)=0,0,IF(0=IF(0=".$Room2ChildAge2.",0,IF(con.max_child_age >= ".$Room2ChildAge2.",1,0)),0,sum(IF(startAge <= ".$Room2ChildAge2." && finalAge >= ".$Room2ChildAge2.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))
        +IF(count(*)=0,0,IF(0=IF(0=".$Room2ChildAge3.",0,IF(con.max_child_age >= ".$Room2ChildAge3.",1,0)),0,sum(IF(startAge <= ".$Room2ChildAge3." && finalAge >= ".$Room2ChildAge3.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))
        +IF(count(*)=0,0,IF(0=IF(0=".$Room2ChildAge4.",0,IF(con.max_child_age >= ".$Room2ChildAge3.",1,0)),0,sum(IF(startAge <= ".$Room2ChildAge4." && finalAge >= ".$Room2ChildAge4.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0)))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['adults'][2].")+(adultAmount*".$data['adults'][2].")*GeneralSupMarkup/100,(adultAmount*".$data['adults'][2].")+(GeneralSupMarkup*".$data['adults'][2].")),(adultAmount*".$data['adults'][2].")+((adultAmount*".$data['adults'][2].")*".$general_markup."/100)) + ((adultAmount*".$data['adults'][2].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(*)=0,0, IF(0=".$Room2ChildAge1." && childAmount=0,0,IF(MinChildAge < ".$Room2ChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(*)=0,0, IF(0=".$Room2ChildAge2." && childAmount=0,0,IF(MinChildAge < ".$Room2ChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room2ChildAge3." && childAmount=0,0,IF(MinChildAge < ".$Room2ChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room2ChildAge4." && childAmount=0,0,IF(MinChildAge < ".$Room2ChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

        (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND Extrabed = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as exdis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND Board = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as boarddis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND General = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0 AND  FIND_IN_SET(".$agent_id.", IFNULL(rev.Agents,'')) > 0  AND rev.FromDate <= a.allotement_date AND  rev.ToDate >= a.allotement_date 

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id where (f.max_total >= ".($data['adults'][2]+$data['child'][2])." AND f.occupancy >= ".$data['adults'][2]."
        +IF(0=".$Room2ChildAge1.",0,IF(con.max_child_age< ".$Room2ChildAge1.",1,0))
        +IF(0=".$Room2ChildAge2.",0,IF(con.max_child_age< ".$Room2ChildAge2.",1,0))
        +IF(0=".$Room2ChildAge3.",0,IF(con.max_child_age< ".$Room2ChildAge3.",1,0))
        +IF(0=".$Room2ChildAge4.",0,IF(con.max_child_age< ".$Room2ChildAge4.",1,0))


        AND f.occupancy_child >= ".$data['child'][2].") AND f.delflg = 1 AND a.allotement_date BETWEEN  '".date('Y-m-d',strtotime($data['check_in']))."' AND '".date('Y-m-d',strtotime('-1 day', strtotime($data['check_out'])))."' AND a.contract_fr_id IN (".$implode_data2.") AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0  AND a.hotel_id IN (".$implode_data1.") AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x where x.TotalPrice != 0 GROUP By x.hotel_id";
    }
    if (isset($data['adults'][3])) {
    
      $Room4ChildAge1 = 0; 
      $Room4ChildAge2 = 0; 
      $Room4ChildAge3 = 0; 
      $Room4ChildAge4 = 0; 
      if (isset($data['Room4ChildAge'][0])) {
        $Room4ChildAge1 = $data['Room4ChildAge'][0]; 
      }
      if (isset($data['Room4ChildAge'][1])) {
        $Room4ChildAge2 = $data['Room4ChildAge'][1]; 
      }
      if (isset($data['Room4ChildAge'][2])) {
        $Room4ChildAge3 = $data['Room4ChildAge'][2]; 
      }
      if (isset($data['Room4ChildAge'][3])) {
        $Room4ChildAge4 = $data['Room4ChildAge'][3]; 
      }
      $room4 = " UNION SELECT *,min(TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday))) as dd FROM (
        SELECT *,IF(".$data['adults'][3]."
            +IF(0=".$Room4ChildAge1.",0,IF(max_child_age< ".$Room4ChildAge1.",1,0))
            +IF(0=".$Room4ChildAge2.",0,IF(max_child_age< ".$Room4ChildAge2.",1,0))
            +IF(0=".$Room4ChildAge3.",0,IF(max_child_age< ".$Room4ChildAge3.",1,0))
            +IF(0=".$Room4ChildAge4.",0,IF(max_child_age< ".$Room4ChildAge4.",1,0)) > standard_capacity && extrabed=0,0,sum(TtlPrice))  as TotalPrice,count(*) as counts,IF(min(allotment)=0,'On Request','Book') as RequestType, IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0) as exAmount,
         sum(IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0)) as exAmountTot
        ,IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
        sum(IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100))) as exChildAmountTot ,
        IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))  as boardChildAmount
      ,sum(IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))) as boardChildAmountTot,
      IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0) as generalsubAmount,
     sum(IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0)) as generalsubAmountTot

      FROM (select a.hotel_id,a.contract_id,a.room_id,a.allotement as allotment, dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,4 as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,con.max_child_age,f.standard_capacity,

        (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

       ((select GetDiscount(a.allotement_date,a.hotel_id,a.contract_id,a.room_id,'".date('Y-m-d')."',".$tot_days.")/100)) as TtlPrice,


        (select IF(count(*)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100)+(amount*".$markup."/100),amount+ExtrabedMarkup+(amount*".$markup."/100)),amount+(sum(amount)*".($markup+$general_markup)."/100)),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['adults'][3]."
            +IF(0=".$Room4ChildAge1.",0,IF(con.max_child_age< ".$Room4ChildAge1.",1,0))
            +IF(0=".$Room4ChildAge2.",0,IF(con.max_child_age< ".$Room4ChildAge2.",1,0))
            +IF(0=".$Room4ChildAge3.",0,IF(con.max_child_age< ".$Room4ChildAge3.",1,0))
            +IF(0=".$Room4ChildAge4.",0,IF(con.max_child_age< ".$Room4ChildAge4.",1,0)) > f.standard_capacity ) as extrabed, 


        (select IF(count(*)=0,'',IF(0=".$Room4ChildAge1.",0,IF(ChildAgeFrom < ".$Room4ChildAge1." && ChildAgeTo >= ".$Room4ChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['adults'][3]+$data['child'][3])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=IF(0=".$Room4ChildAge1.",0,IF(con.max_child_age >= ".$Room4ChildAge1.",1,0)),0,sum(IF(startAge <= ".$Room4ChildAge1." && finalAge >= ".$Room4ChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))

        +IF(count(*)=0,0,IF(0=IF(0=".$Room4ChildAge2.",0,IF(con.max_child_age >= ".$Room4ChildAge2.",1,0)),0,sum(IF(startAge <= ".$Room4ChildAge2." && finalAge >= ".$Room4ChildAge2.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))
        +IF(count(*)=0,0,IF(0=IF(0=".$Room4ChildAge3.",0,IF(con.max_child_age >= ".$Room4ChildAge3.",1,0)),0,sum(IF(startAge <= ".$Room4ChildAge3." && finalAge >= ".$Room4ChildAge3.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))
        +IF(count(*)=0,0,IF(0=IF(0=".$Room4ChildAge4.",0,IF(con.max_child_age >= ".$Room4ChildAge3.",1,0)),0,sum(IF(startAge <= ".$Room4ChildAge4." && finalAge >= ".$Room4ChildAge4.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0)))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['adults'][3].")+(adultAmount*".$data['adults'][3].")*GeneralSupMarkup/100,(adultAmount*".$data['adults'][3].")+(GeneralSupMarkup*".$data['adults'][3].")),(adultAmount*".$data['adults'][3].")+((adultAmount*".$data['adults'][3].")*".$general_markup."/100)) + ((adultAmount*".$data['adults'][3].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(*)=0,0, IF(0=".$Room4ChildAge1." && childAmount=0,0,IF(MinChildAge < ".$Room4ChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(*)=0,0, IF(0=".$Room4ChildAge2." && childAmount=0,0,IF(MinChildAge < ".$Room4ChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room4ChildAge3." && childAmount=0,0,IF(MinChildAge < ".$Room4ChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room4ChildAge4." && childAmount=0,0,IF(MinChildAge < ".$Room4ChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

        (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND Extrabed = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as exdis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND Board = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as boarddis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND General = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0 AND  FIND_IN_SET(".$agent_id.", IFNULL(rev.Agents,'')) > 0  AND rev.FromDate <= a.allotement_date AND  rev.ToDate >= a.allotement_date 

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id where (f.max_total >= ".($data['adults'][3]+$data['child'][3])." AND f.occupancy >= ".$data['adults'][3]."
        +IF(0=".$Room4ChildAge1.",0,IF(con.max_child_age< ".$Room4ChildAge1.",1,0))
        +IF(0=".$Room4ChildAge2.",0,IF(con.max_child_age< ".$Room4ChildAge2.",1,0))
        +IF(0=".$Room4ChildAge3.",0,IF(con.max_child_age< ".$Room4ChildAge3.",1,0))
        +IF(0=".$Room4ChildAge4.",0,IF(con.max_child_age< ".$Room4ChildAge4.",1,0))


        AND f.occupancy_child >= ".$data['child'][3].") AND f.delflg = 1 AND a.allotement_date BETWEEN  '".date('Y-m-d',strtotime($data['check_in']))."' AND '".date('Y-m-d',strtotime('-1 day', strtotime($data['check_out'])))."' AND a.contract_fr_id IN (".$implode_data2.") AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0  AND a.hotel_id IN (".$implode_data1.") AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x where x.TotalPrice != 0 GROUP By x.hotel_id";
    }
    if (isset($data['adults'][4])) {
      $Room5ChildAge1 = 0; 
      $Room5ChildAge2 = 0; 
      $Room5ChildAge3 = 0; 
      $Room5ChildAge4 = 0; 
      if (isset($data['Room5ChildAge'][0])) {
        $Room5ChildAge1 = $data['Room5ChildAge'][0]; 
      }
      if (isset($data['Room5ChildAge'][1])) {
        $Room5ChildAge2 = $data['Room5ChildAge'][1]; 
      }
      if (isset($data['Room5ChildAge'][2])) {
        $Room5ChildAge3 = $data['Room5ChildAge'][2]; 
      }
      if (isset($data['Room5ChildAge'][3])) {
        $Room5ChildAge4 = $data['Room5ChildAge'][3]; 
      }
      $room5 = " UNION SELECT *,min(TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday))) as dd FROM (
        SELECT *,IF(".$data['adults'][4]."
            +IF(0=".$Room5ChildAge1.",0,IF(max_child_age< ".$Room5ChildAge1.",1,0))
            +IF(0=".$Room5ChildAge2.",0,IF(max_child_age< ".$Room5ChildAge2.",1,0))
            +IF(0=".$Room5ChildAge3.",0,IF(max_child_age< ".$Room5ChildAge3.",1,0))
            +IF(0=".$Room5ChildAge4.",0,IF(max_child_age< ".$Room5ChildAge4.",1,0)) > standard_capacity && extrabed=0,0,sum(TtlPrice))  as TotalPrice,count(*) as counts,IF(min(allotment)=0,'On Request','Book') as RequestType, IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0) as exAmount,
         sum(IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0)) as exAmountTot
        ,IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
        sum(IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100))) as exChildAmountTot ,
        IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))  as boardChildAmount
      ,sum(IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))) as boardChildAmountTot,
      IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0) as generalsubAmount,
     sum(IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0)) as generalsubAmountTot

      FROM (select a.hotel_id,a.contract_id,a.room_id,a.allotement as allotment, dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,5 as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,con.max_child_age,f.standard_capacity,

        (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

       ((select GetDiscount(a.allotement_date,a.hotel_id,a.contract_id,a.room_id,'".date('Y-m-d')."',".$tot_days.")/100)) as TtlPrice,


        (select IF(count(*)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100)+(amount*".$markup."/100),amount+ExtrabedMarkup+(amount*".$markup."/100)),amount+(sum(amount)*".($markup+$general_markup)."/100)),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['adults'][4]."
            +IF(0=".$Room5ChildAge1.",0,IF(con.max_child_age< ".$Room5ChildAge1.",1,0))
            +IF(0=".$Room5ChildAge2.",0,IF(con.max_child_age< ".$Room5ChildAge2.",1,0))
            +IF(0=".$Room5ChildAge3.",0,IF(con.max_child_age< ".$Room5ChildAge3.",1,0))
            +IF(0=".$Room5ChildAge4.",0,IF(con.max_child_age< ".$Room5ChildAge4.",1,0)) > f.standard_capacity ) as extrabed, 


        (select IF(count(*)=0,'',IF(0=".$Room5ChildAge1.",0,IF(ChildAgeFrom < ".$Room5ChildAge1." && ChildAgeTo >= ".$Room5ChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['adults'][4]+$data['child'][4])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=IF(0=".$Room5ChildAge1.",0,IF(con.max_child_age >= ".$Room5ChildAge1.",1,0)),0,sum(IF(startAge <= ".$Room5ChildAge1." && finalAge >= ".$Room5ChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))

        +IF(count(*)=0,0,IF(0=IF(0=".$Room5ChildAge2.",0,IF(con.max_child_age >= ".$Room5ChildAge2.",1,0)),0,sum(IF(startAge <= ".$Room5ChildAge2." && finalAge >= ".$Room5ChildAge2.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))
        +IF(count(*)=0,0,IF(0=IF(0=".$Room5ChildAge3.",0,IF(con.max_child_age >= ".$Room5ChildAge3.",1,0)),0,sum(IF(startAge <= ".$Room5ChildAge3." && finalAge >= ".$Room5ChildAge3.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))
        +IF(count(*)=0,0,IF(0=IF(0=".$Room5ChildAge4.",0,IF(con.max_child_age >= ".$Room5ChildAge3.",1,0)),0,sum(IF(startAge <= ".$Room5ChildAge4." && finalAge >= ".$Room5ChildAge4.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0)))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['adults'][4].")+(adultAmount*".$data['adults'][4].")*GeneralSupMarkup/100,(adultAmount*".$data['adults'][4].")+(GeneralSupMarkup*".$data['adults'][4].")),(adultAmount*".$data['adults'][4].")+((adultAmount*".$data['adults'][4].")*".$general_markup."/100)) + ((adultAmount*".$data['adults'][4].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(*)=0,0, IF(0=".$Room5ChildAge1." && childAmount=0,0,IF(MinChildAge < ".$Room5ChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(*)=0,0, IF(0=".$Room5ChildAge2." && childAmount=0,0,IF(MinChildAge < ".$Room5ChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room5ChildAge3." && childAmount=0,0,IF(MinChildAge < ".$Room5ChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room5ChildAge4." && childAmount=0,0,IF(MinChildAge < ".$Room5ChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

        (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND Extrabed = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as exdis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND Board = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as boarddis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND General = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0 AND  FIND_IN_SET(".$agent_id.", IFNULL(rev.Agents,'')) > 0  AND rev.FromDate <= a.allotement_date AND  rev.ToDate >= a.allotement_date 

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id where (f.max_total >= ".($data['adults'][4]+$data['child'][4])." AND f.occupancy >= ".$data['adults'][4]."
        +IF(0=".$Room5ChildAge1.",0,IF(con.max_child_age< ".$Room5ChildAge1.",1,0))
        +IF(0=".$Room5ChildAge2.",0,IF(con.max_child_age< ".$Room5ChildAge2.",1,0))
        +IF(0=".$Room5ChildAge3.",0,IF(con.max_child_age< ".$Room5ChildAge3.",1,0))
        +IF(0=".$Room5ChildAge4.",0,IF(con.max_child_age< ".$Room5ChildAge4.",1,0))


        AND f.occupancy_child >= ".$data['child'][4].") AND f.delflg = 1 AND a.allotement_date BETWEEN  '".date('Y-m-d',strtotime($data['check_in']))."' AND '".date('Y-m-d',strtotime('-1 day', strtotime($data['check_out'])))."' AND a.contract_fr_id IN (".$implode_data2.") AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0  AND a.hotel_id IN (".$implode_data1.") AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x where x.TotalPrice != 0 GROUP By x.hotel_id";
    }
    if (isset($data['adults'][5])) {
      $Room6ChildAge1 = 0; 
      $Room6ChildAge2 = 0; 
      $Room6ChildAge3 = 0; 
      $Room6ChildAge4 = 0; 
      if (isset($data['Room6ChildAge'][0])) {
        $Room6ChildAge1 = $data['room6ChildAge'][0]; 
      }
      if (isset($data['Room6ChildAge'][1])) {
        $Room6ChildAge2 = $data['Room6ChildAge'][1]; 
      }
      if (isset($data['Room6ChildAge'][2])) {
        $Room6ChildAge3 = $data['Room6ChildAge'][2]; 
      }
      if (isset($data['Room6ChildAge'][3])) {
        $Room6ChildAge4 = $data['Room6ChildAge'][3]; 
      }
      $room6 = " UNION SELECT *,min(TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday))) as dd FROM (
        SELECT *,IF(".$data['adults'][5]."
            +IF(0=".$Room6ChildAge1.",0,IF(max_child_age< ".$Room6ChildAge1.",1,0))
            +IF(0=".$Room6ChildAge2.",0,IF(max_child_age< ".$Room6ChildAge2.",1,0))
            +IF(0=".$Room6ChildAge3.",0,IF(max_child_age< ".$Room6ChildAge3.",1,0))
            +IF(0=".$Room6ChildAge4.",0,IF(max_child_age< ".$Room6ChildAge4.",1,0)) > standard_capacity && extrabed=0,0,sum(TtlPrice))  as TotalPrice,count(*) as counts,IF(min(allotment)=0,'On Request','Book') as RequestType, IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0) as exAmount,
         sum(IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0)) as exAmountTot
        ,IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
        sum(IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100))) as exChildAmountTot ,
        IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))  as boardChildAmount
      ,sum(IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))) as boardChildAmountTot,
      IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0) as generalsubAmount,
     sum(IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0)) as generalsubAmountTot

      FROM (select a.hotel_id,a.contract_id,a.room_id,a.allotement as allotment, dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,6 as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,con.max_child_age,f.standard_capacity,

        (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

       ((select GetDiscount(a.allotement_date,a.hotel_id,a.contract_id,a.room_id,'".date('Y-m-d')."',".$tot_days.")/100)) as TtlPrice,


        (select IF(count(*)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100)+(amount*".$markup."/100),amount+ExtrabedMarkup+(amount*".$markup."/100)),amount+(sum(amount)*".($markup+$general_markup)."/100)),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['adults'][5]."
            +IF(0=".$Room6ChildAge1.",0,IF(con.max_child_age< ".$Room6ChildAge1.",1,0))
            +IF(0=".$Room6ChildAge2.",0,IF(con.max_child_age< ".$Room6ChildAge2.",1,0))
            +IF(0=".$Room6ChildAge3.",0,IF(con.max_child_age< ".$Room6ChildAge3.",1,0))
            +IF(0=".$Room6ChildAge4.",0,IF(con.max_child_age< ".$Room6ChildAge4.",1,0)) > f.standard_capacity ) as extrabed, 


        (select IF(count(*)=0,'',IF(0=".$Room6ChildAge1.",0,IF(ChildAgeFrom < ".$Room6ChildAge1." && ChildAgeTo >= ".$Room6ChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['adults'][5]+$data['child'][5])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=IF(0=".$Room6ChildAge1.",0,IF(con.max_child_age >= ".$Room6ChildAge1.",1,0)),0,sum(IF(startAge <= ".$Room6ChildAge1." && finalAge >= ".$Room6ChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))

        +IF(count(*)=0,0,IF(0=IF(0=".$Room6ChildAge2.",0,IF(con.max_child_age >= ".$Room6ChildAge2.",1,0)),0,sum(IF(startAge <= ".$Room6ChildAge2." && finalAge >= ".$Room6ChildAge2.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))
        +IF(count(*)=0,0,IF(0=IF(0=".$Room6ChildAge3.",0,IF(con.max_child_age >= ".$Room6ChildAge3.",1,0)),0,sum(IF(startAge <= ".$Room6ChildAge3." && finalAge >= ".$Room6ChildAge3.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))
        +IF(count(*)=0,0,IF(0=IF(0=".$Room6ChildAge4.",0,IF(con.max_child_age >= ".$Room6ChildAge3.",1,0)),0,sum(IF(startAge <= ".$Room6ChildAge4." && finalAge >= ".$Room6ChildAge4.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0)))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['adults'][5].")+(adultAmount*".$data['adults'][5].")*GeneralSupMarkup/100,(adultAmount*".$data['adults'][5].")+(GeneralSupMarkup*".$data['adults'][5].")),(adultAmount*".$data['adults'][5].")+((adultAmount*".$data['adults'][5].")*".$general_markup."/100)) + ((adultAmount*".$data['adults'][5].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(*)=0,0, IF(0=".$Room6ChildAge1." && childAmount=0,0,IF(MinChildAge < ".$Room6ChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(*)=0,0, IF(0=".$Room6ChildAge2." && childAmount=0,0,IF(MinChildAge < ".$Room6ChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room6ChildAge3." && childAmount=0,0,IF(MinChildAge < ".$Room6ChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room6ChildAge4." && childAmount=0,0,IF(MinChildAge < ".$Room6ChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

        (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND Extrabed = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as exdis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND Board = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as boarddis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND General = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0 AND  FIND_IN_SET(".$agent_id.", IFNULL(rev.Agents,'')) > 0  AND rev.FromDate <= a.allotement_date AND  rev.ToDate >= a.allotement_date 

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id where (f.max_total >= ".($data['adults'][5]+$data['child'][5])." AND f.occupancy >= ".$data['adults'][5]."
        +IF(0=".$Room6ChildAge1.",0,IF(con.max_child_age< ".$Room6ChildAge1.",1,0))
        +IF(0=".$Room6ChildAge2.",0,IF(con.max_child_age< ".$Room6ChildAge2.",1,0))
        +IF(0=".$Room6ChildAge3.",0,IF(con.max_child_age< ".$Room6ChildAge3.",1,0))
        +IF(0=".$Room6ChildAge4.",0,IF(con.max_child_age< ".$Room6ChildAge4.",1,0))


        AND f.occupancy_child >= ".$data['child'][5].") AND f.delflg = 1 AND a.allotement_date BETWEEN  '".date('Y-m-d',strtotime($data['check_in']))."' AND '".date('Y-m-d',strtotime('-1 day', strtotime($data['check_out'])))."' AND a.contract_fr_id IN (".$implode_data2.") AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0  AND a.hotel_id IN (".$implode_data1.") AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x where x.TotalPrice != 0 GROUP By x.hotel_id";
    }
      $imgurl = 'http://dev.otelseasy.com/';
      $agent_currency = 'AED';
      $arr = array('Type' => '1','date' => date('Y-m-d H:i:s'));
      $finarr = array_merge($arr,$data);

      $token = base64_encode(serialize($finarr));
      $OtelseasyHotels =  $this->db->prepare("SELECT hotel_id as HotelCode,min(amount) as TotalPrice,h.hotel_name as HotelName,h.location as HotelAddress,concat('".$imgurl."uploads/gallery/',n.hotel_id,'/',h.Image1) as HotelPicture,h.hotel_description as HotelDescription, h.rating as Rating,'".$agent_currency."' as Currency,IF(IFNULL('',h.starsrating)='','',h.starsrating) as reviews ,'' as Inclusion ,CONCAT(h.lattitude,'|',h.longitude) as mapdetails,'".$token."' as token  FROM (SELECT m.*,sum(dd) as amount ,count(*) as roomcount  FROM ( ".$room1.$room2.$room3.$room4.$room5.$room6.") m GROUP BY m.hotel_id HAVING roomcount >= ".count($data['adults']).") n INNER JOIN hotel_tbl_hotels h ON h.id = n.hotel_id GROUP BY n.hotel_id order by TotalPrice asc ,h.rating desc");
      $OtelseasyHotels->execute();
      $OEhotels =  $OtelseasyHotels->fetchAll();

      $TBOHotels = array();
      $per = $this->tbosearchpermission($agent_id);
      if ($per!=0) {
        if ($data['countryname']!="United Arab Emirates") {
          $TBOHotels = $this->xmlrequest($data,$agent_id);
        }
      }

      return array_merge($OEhotels,$TBOHotels);
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
    public function tbosearchpermission($id) {
        $stmt = $this->db->prepare("SELECT tboStatus FROM hotel_tbl_agents where id = ".$id."");
        $stmt->execute();
        $final = $stmt->fetchAll();
        return  $final[0]['tboStatus'];
    }
    public function xmlrequest($request,$agent_id) {
      $agent_currency = 'AED';

      $stmt = $this->db->prepare("select xmlproviderFlg from xml_providers_tbl where id = 1");
      $stmt->execute();
      $final = $stmt->fetchAll();
      $TBOFlg = isset($final[0]['xmlproviderFlg']) ? $final[0]['xmlproviderFlg'] : 0;

      $Room1ChildAges='';
      $Room2ChildAges='';
      $Room3ChildAges='';
      $Room4ChildAges='';
      $Room5ChildAges='';
      $Room6ChildAges='';
      $Room7ChildAges='';
      $Room8ChildAges='';
      $Room9ChildAges='';
      $Room10ChildAges='';

      if ($request['check_in']!='') {
        $checkin = $request['check_in'];
      }
      if($request['check_out']!='') {
        $checkout=$request['check_out'];
      }
      if ($request['adults']!='') {
        $adults = implode(",", $request['adults']) ;
      }
      if ($request['child']!='') {
        $child = implode(",", $request['child']) ;
      }
      if (isset($request['Room1ChildAge']) &&  $request['child'][0]!=0) {
        $Room1ChildAges = implode(",", $request['Room1ChildAge']) ;
      }
      if (isset($request['child'][1]) && $request['child'][1]!=0) {
        $Room2ChildAges = implode(",", $request['Room2ChildAge']) ;
      }
      if (isset($request['child'][2]) &&  $request['child'][2]!=0) {
        $Room3ChildAges = implode(",", $request['Room3ChildAge']) ;
      }
      if (isset($request['child'][3]) &&  $request['child'][3]!=0) {
        $Room4ChildAges = implode(",", $request['Room4ChildAge']) ;
      }
      if (isset($request['child'][4]) &&  $request['child'][4]!=0) {
        $Room5ChildAges = implode(",", $request['Room5ChildAge']) ;
      }
      if (isset($request['child'][5]) &&  $request['child'][5]!=0) {
        $Room6ChildAges = implode(",", $request['Room6ChildAge']) ;
      }
      if (isset($request['child'][6]) &&  $request['child'][6]!=0) {
        $Room7ChildAges = implode(",", $request['Room7ChildAge']) ;
      }
      if (isset($request['child'][7]) &&  $request['child'][7]!=0) {
        $Room8ChildAges = implode(",", $request['Room8ChildAge']) ;
      }
      if (isset($request['child'][8]) &&  $request['child'][8]!=0) {
        $Room9ChildAges = implode(",", $request['Room9ChildAge']) ;
      }
      if (isset($request['child'][9]) &&  $request['child'][9]!=0) {
        $Room10ChildAges = implode(",", $request['Room10ChildAge']) ;
      }


    $stmt1 = $this->db->prepare('SELECT sortname FROM countries where name = "'.$request['nationality'].'"');
    $stmt1->execute();
    $nationality = $stmt1->fetchAll();
    $nationality = count($nationality)!=0 ? $nationality[0]['sortname'] : 'IN';

    $stmt3 = $this->db->prepare('SELECT CityCode FROM xml_city_tbl a inner join countries c ON a.CountryCode = c.sortname where c.name = "'.$request['countryname'].'" AND a.CityName = "'.$request['cityname'].'"');

    $stmt3->execute();
    $citycode = $stmt3->fetchAll();
    $citycode = count($citycode)!=0 ? $citycode[0]['CityCode'] : 0;

      foreach ($request['adults'] as $key => $value) {
        if (!isset($request['Child'][$key]) || $request['Child'][$key]=="") {
          $request['Child'][$key] = 0;
        }
        $childAge  = array();
        if ($request['Child'][$key]!=0) {
            for ($i=1; $i <= $request['Child'][$key] ; $i++) { 
              foreach ($request['Room'.($key+1).'ChildAge'] as $reCAkey => $reCAvalue) {
                $childAge[$reCAkey] = [
                      "ChildAge" => [
                        "value" => [
                          "int" => [
                            "value" => $reCAvalue
                          ]
                        ]
                      ]
                    ];
              }
            }

          }
        $RoomGuest[] = ["RoomGuest"=>[
                            "attr"=>[
                                "AdultCount"=>$value,
                                "ChildCount"=> $request['Child'][$key]
                            ],
                            "value"=> $childAge
                        ]];
      }


   // $HotelNameReq =  
    $inp_arr_hotel = [
        "CheckInDate"=>[
            "value"=>date('Y-m-d',strtotime($request['check_in']))
        ],
        "CheckOutDate"=>[
            "value"=>date('Y-m-d',strtotime($request['check_out']))
        ],
        "CountryName"=>[
            "value"=>$request['countryname']
        ],
        "CityName"=>[
            "value"=>$request['cityname']
        ],
        "CityId"=>[
            "value"=>$citycode
        ],
        "IsNearBySearchAllowed"=>[
            "value"=>'false'
        ],
        "NoOfRooms"=>[
            "value"=>count($request['adults'])
        ],
        "GuestNationality"=>[
            "value"=>$nationality
        ],
        "RoomGuests"=>[
            "value"=>
            $RoomGuest
        ],
        "PreferredCurrencyCode" =>[
            "value"=>'AED'
        ],
        "ResultCount" => [
            "value" => 200
        ],
        "Filters" => [
            "value" => [
                "HotelName" =>[
                  "value"=>''
                ],
                "StarRating" =>[
                    "value"=>"All"
                ],
                "OrderBy" =>[
                    "value"=>"PriceAsc"
                ]
            ]
        ],
        "ResponseTime" => [
              "value" => 0
          ]
    ];
    $return = array();


    if ($TBOFlg==1) {
      
      $revenue_markup =  $this->xmlrevenue_markup('tbo',$agent_id,$request);
      $total_markup =  $this->mark_up_get($agent_id)+ $this->general_mark_up_get($agent_id);
      if ($revenue_markup!='') {
        $total_markup =  $this->mark_up_get($agent_id)+$revenue_markup;
      }
      
      $Tbohotels = $this->HotelSearch($inp_arr_hotel);
      if (isset($Tbohotels['Status']['StatusCode']) && $Tbohotels['Status']['StatusCode']==01) {
        if (isset($Tbohotels['HotelResultList']['HotelResult'][1])) {
          foreach ($Tbohotels['HotelResultList']['HotelResult'] as $key => $value) {
              $return[$key]['HotelCode'] = $value['HotelInfo']['HotelCode'];

              $TotalPrice = $value['MinHotelPrice']['@attributes']['TotalPrice'];

              $TotalPrice = ($TotalPrice*$total_markup/100)+$TotalPrice;

              $return[$key]['TotalPrice'] = $this->xml_currency_change($TotalPrice,$value['MinHotelPrice']['@attributes']['Currency'],$agent_currency);
              $return[$key]['HotelName'] = $value['HotelInfo']['HotelName'];
              $return[$key]['HotelAddress'] = $value['HotelInfo']['HotelAddress'];
              $return[$key]['HotelPicture'] = isset($value['HotelInfo']['HotelPicture']) ? $value['HotelInfo']['HotelPicture'] : '';
              $return[$key]['HotelDescription'] = is_array($value['HotelInfo']['HotelDescription']) ? implode(",", $value['HotelInfo']['HotelDescription']) : $value['HotelInfo']['HotelDescription'];
              
              if ($value['HotelInfo']['Rating']=="FiveStar") {
                $star = 5;
              } 
              if ($value['HotelInfo']['Rating']=="FourStar") {
                $star = 4;
              } 
              if ($value['HotelInfo']['Rating']=="ThreeStar") {
                $star = 3;
              } 
              if ($value['HotelInfo']['Rating']=="TwoStar") {
                $star = 2;
              }
              if ($value['HotelInfo']['Rating']=="OneStar") {
                $star = 1;
              }
              $return[$key]['Rating'] = $star;
              $return[$key]['Currency'] = $agent_currency;
              
              if (!isset($value['HotelInfo']['TripAdvisorRating'])) {
                $value['HotelInfo']['TripAdvisorRating'] = '0.0';
              }
              $return[$key]['reviews'] = ceil($value['HotelInfo']['TripAdvisorRating']);


              $return[$key]['Inclusion'] = '';
              $return[$key]['mapdetails'] =  $value['HotelInfo']['Latitude'].'|'.$value['HotelInfo']['Longitude'];


              $arr = array('Type' => '2','date' => date('Y-m-d H:i:s'),'sessionid'=>$Tbohotels['SessionId'], 'ResultIndex' => $value['ResultIndex']);
              $finarr = array_merge($arr,$request);

              $token = base64_encode(serialize($finarr));


              $return[$key]['token'] = $token;
          }
        } else {
          $value = $Tbohotels['HotelResultList']['HotelResult'];
          $return[0]['HotelCode'] = $value['HotelInfo']['HotelCode'];

          $TotalPrice = $value['MinHotelPrice']['@attributes']['TotalPrice'];

          $TotalPrice = ($TotalPrice*$total_markup/100)+$TotalPrice;
          $return[0]['TotalPrice'] = $this->xml_currency_change($TotalPrice,$value['MinHotelPrice']['@attributes']['Currency'],$agent_currency);
          $return[0]['HotelName'] = $value['HotelInfo']['HotelName'];
          $return[0]['HotelAddress'] = $value['HotelInfo']['HotelAddress'];
          $return[0]['HotelPicture'] = isset($value['HotelInfo']['HotelPicture']) ? $value['HotelInfo']['HotelPicture'] : '';
          $return[0]['HotelDescription'] = is_array($value['HotelInfo']['HotelDescription']) ? implode(",", $value['HotelInfo']['HotelDescription']) : $value['HotelInfo']['HotelDescription'];
          if ($value['HotelInfo']['Rating']=="FiveStar") {
            $star = 5;
          } 
          if ($value['HotelInfo']['Rating']=="FourStar") {
            $star = 4;
          } 
          if ($value['HotelInfo']['Rating']=="ThreeStar") {
            $star = 3;
          } 
          if ($value['HotelInfo']['Rating']=="TwoStar") {
            $star = 2;
          }
          if ($value['HotelInfo']['Rating']=="OneStar") {
            $star = 1;
          }
          $return[0]['Rating'] = $star;
          $return[0]['Currency'] = $agent_currency;

          if (!isset($value['HotelInfo']['TripAdvisorRating'])) {
            $value['HotelInfo']['TripAdvisorRating'] = '0.0';
          }
          $return[0]['reviews'] = ceil($value['HotelInfo']['TripAdvisorRating']);
          
          $return[$key]['Inclusion'] = '';

          $arr = array('Type' => '2','date' => date('Y-m-d H:i:s'),'sessionid'=>$Tbohotels['SessionId'], 'ResultIndex' => $value['ResultIndex']);
          $finarr = array_merge($arr,$request);

          $token = base64_encode(serialize($finarr));


          $return[$key]['token'] = $token;
        }
      }
    }
    return $return;
  }
  public function xmlrevenue_markup($provider,$agent_id,$request) {  
    $stmt1 = $this->db->prepare("SELECT IFNULL(MAX(Markup),'') as Markup FROM `hotel_tbl_revenue` where ".$provider." = 1 AND FIND_IN_SET(".$agent_id.", IFNULL(Agents,'')) > 0 AND FromDate <= '".date('Y-m-d',strtotime($request['check_in']))."' AND  ToDate >= '".date('Y-m-d',strtotime($request['check_out']))."'");
    $stmt1->execute();
    $query = $stmt1->fetchAll();
    return $query[0]['Markup'];
  }
  public function HotelSearch($arg){
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