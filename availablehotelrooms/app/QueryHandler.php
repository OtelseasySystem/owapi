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
    public function roomwisepaxdata($key,$data,$contract,$agent_id) {
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
        $implode_data2 = implode("','", array_unique($contract));
        $RoomChildAge1 = 0; 
        $RoomChildAge2 = 0; 
        $RoomChildAge3 = 0; 
        $RoomChildAge4 = 0; 
        
        if (isset($data['Room'.($key+1).'ChildAge'][0])) {
          $RoomChildAge1 = $data['Room'.($key+1).'ChildAge'][0]; 
        }
        if (isset($data['Room'.($key+1).'ChildAge'][1])) {
          $RoomChildAge2 = $data['Room'.($key+1).'ChildAge'][1]; 
        }
        if (isset($data['Room'.($key+1).'ChildAge'][2])) {
          $RoomChildAge3 = $data['Room'.($key+1).'ChildAge'][2]; 
        }
        if (isset($data['Room'.($key+1).'ChildAge'][3])) {
          $RoomChildAge4 = $data['Room'.($key+1).'ChildAge'][3]; 
        }
        $imgurl = 'http://dev.otelseasy.com/';
        $markup = $this->mark_up_get($agent_id);
        $general_markup = $this->general_mark_up_get($agent_id);
        
        $stmt = $this->db->prepare("SELECT RoomIndex,board,RoomName,ImageUrl,(select GROUP_CONCAT(Room_Facility SEPARATOR  '|') from hotel_tbl_room_facility where id in (room_facilities)) as Amenities ,RequestType,extraLabel,extraChildLabel,TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday)) as Price 
        FROM (
          SELECT *,sum(FinalAmnt) as TotalPrice,count(*) as counts,IF(min(allotment)<=0,'On Request','Book') as RequestType,sum(exAmount) as exAmountTot,sum(exChildAmount) as exChildAmountTot
        ,sum(boardChildAmount) as boardChildAmountTot,sum(generalsubAmount) as generalsubAmountTot,IF(sum(exAmount)!=0,'Adult Extrabed','') as extraLabel,
        IF(sum(exChildAmount)!=0,'Child Extrabed','') as extraChildLabel,IF(sum(boardChildAmount)!=0,'Child supplements','') as boardChildLabel 
           FROM (
         SELECT *,IF(".$data['adults'][$key]."
            +IF(0=".$RoomChildAge1.",0,IF(max_child_age< ".$RoomChildAge1.",1,0))
            +IF(0=".$RoomChildAge2.",0,IF(max_child_age< ".$RoomChildAge2.",1,0))
            +IF(0=".$RoomChildAge3.",0,IF(max_child_age< ".$RoomChildAge3.",1,0))
            +IF(0=".$RoomChildAge4.",0,IF(max_child_age< ".$RoomChildAge4.",1,0)) > standard_capacity && extrabed=0,0,TtlPrice) as FinalAmnt,
      IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0) as exAmount,
      IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
      IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100)) as boardChildAmount,
      IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0) as generalsubAmount

      FROM (select con.board,CONCAT(f.room_name,' ',g.Room_Type) as RoomName, f.room_facilities,
      IF(f.images!='',
      concat('".$imgurl."uploads/rooms/',a.room_id,'/',f.images),'') as ImageUrl,a.hotel_id,a.contract_id,a.room_id,
      if(con.contract_type='Sub',(select GetAllotmentCount1(a.allotement_date,a.hotel_id,CONCAT('CON0',linkedcontract),a.room_id ,'".date('Y-m-d')."',".$tot_days.",".count($data['adults']).")),(select GetAllotmentCount1(a.allotement_date,a.hotel_id,a.contract_id,a.room_id ,'".date('Y-m-d')."',".$tot_days.",".count($data['adults'])."))) as allotment, a.amount as TtlPrice1,dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,CONCAT(con.contract_id,'-',a.room_id) as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,f.standard_capacity,con.max_child_age,

        ((a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

         (select GetDiscount(a.allotement_date,a.hotel_id,a.contract_id,a.room_id,'".date('Y-m-d')."',".$tot_days.")/100)
         ) as TtlPrice,
        (select IF(count(1)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100)+(amount*".$markup."/100),amount+ExtrabedMarkup+(amount*".$markup."/100)),amount+(sum(amount)*".($markup+$general_markup)."/100)),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['adults'][$key]."
            +IF(0=".$RoomChildAge1.",0,IF(con.max_child_age< ".$RoomChildAge1.",1,0))
            +IF(0=".$RoomChildAge2.",0,IF(con.max_child_age< ".$RoomChildAge2.",1,0))
            +IF(0=".$RoomChildAge3.",0,IF(con.max_child_age< ".$RoomChildAge3.",1,0))
            +IF(0=".$RoomChildAge4.",0,IF(con.max_child_age< ".$RoomChildAge4.",1,0)) > f.standard_capacity ) as extrabed, 

          if(".$data['adults'][$key]."
            +IF(0=".$RoomChildAge1.",0,IF(con.max_child_age< ".$RoomChildAge1.",1,0))
            +IF(0=".$RoomChildAge2.",0,IF(con.max_child_age< ".$RoomChildAge2.",1,0))
            +IF(0=".$RoomChildAge3.",0,IF(con.max_child_age< ".$RoomChildAge3.",1,0))
            +IF(0=".$RoomChildAge4.",0,IF(con.max_child_age< ".$RoomChildAge4.",1,0)) > f.standard_capacity,
        (select IF(count(1)=0,'',IF(0=".$RoomChildAge1.",0,IF(ChildAgeFrom < ".$RoomChildAge1." && ChildAgeTo >= ".$RoomChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['adults'][$key]+$data['child'][$key])." > f.standard_capacity),0) as extrabedChild, 

        (select IF(count(1)=0,0,IF(0=IF(0=".$RoomChildAge1.",0,IF(con.max_child_age >= ".$RoomChildAge1.",1,0)),0,sum(IF(startAge <= ".$RoomChildAge1." && finalAge >= ".$RoomChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))

        +IF(count(1)=0,0,IF(0=IF(0=".$RoomChildAge2.",0,IF(con.max_child_age >= ".$RoomChildAge2.",1,0)),0,sum(IF(startAge <= ".$RoomChildAge2." && finalAge >= ".$RoomChildAge2.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))

        +IF(count(1)=0,0,IF(0=IF(0=".$RoomChildAge3.",0,IF(con.max_child_age >= ".$RoomChildAge3.",1,0)),0,sum(IF(startAge <= ".$RoomChildAge3." && finalAge >= ".$RoomChildAge3.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0))))

        +IF(count(1)=0,0,IF(0=IF(0=".$RoomChildAge4.",0,IF(con.max_child_age >= ".$RoomChildAge4.",1,0)),0,sum(IF(startAge <= ".$RoomChildAge4." && finalAge >= ".$RoomChildAge4.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',amount+(amount*BoardSupMarkup/100)+(amount*".$markup."/100),amount+(BoardSupMarkup)+(amount*".$markup."/100)),amount+(amount*".($markup+$general_markup)."/100)),0)))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner')))))  as extrabedChild1,

        (select IF(count(1)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['adults'][$key].")+(adultAmount*".$data['adults'][$key].")*GeneralSupMarkup/100,(adultAmount*".$data['adults'][$key].")+(GeneralSupMarkup*".$data['adults'][$key].")),(adultAmount*".$data['adults'][$key].")+((adultAmount*".$data['adults'][$key].")*".$general_markup."/100)) + ((adultAmount*".$data['adults'][$key].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(1)=0,0, IF(0=".$RoomChildAge1." && childAmount=0,0,IF(MinChildAge < ".$RoomChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(1)=0,0, IF(0=".$RoomChildAge2." && childAmount=0,0,IF(MinChildAge < ".$RoomChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(1)=0,0, IF(0=".$RoomChildAge3." && childAmount=0,0,IF(MinChildAge < ".$RoomChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(1)=0,0, IF(0=".$RoomChildAge4." && childAmount=0,0,IF(MinChildAge < ".$RoomChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

      (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND Extrabed = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as exdis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND Board = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as boarddis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0  AND General = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) order by Bkbefore desc limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0 AND FIND_IN_SET(".
      $agent_id.", IFNULL(rev.Agents,'')) > 0 AND rev.FromDate <= a.allotement_date AND  rev.ToDate >= a.allotement_date

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id INNER JOIN hotel_tbl_room_type g ON g.id = f.room_type  where (f.max_total >= ".($data['adults'][$key]+$data['child'][$key])." AND f.occupancy >= ".$data['adults'][$key]." AND f.occupancy_child >= ".$data['child'][$key].") AND f.delflg = 1 AND a.allotement_date IN ('".$implode_data."') AND a.contract_id IN ('".$implode_data2."') AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0 AND a.hotel_id = ".$data['hotelcode']." AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) extra) discal where discal.FinalAmnt!=0 GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x order by price asc");
        $stmt->execute();
        $rooms = $stmt->fetchAll();

        $roomsdata  = array();
        foreach ($rooms as $key => $value) {
            $roomsdata[$key] = $value; 
            $explodeRoomIndex = explode("-", $value['RoomIndex']);
            $Cancellation =  $this->get_CancellationPolicy_table($data,$explodeRoomIndex[0],$explodeRoomIndex[1]);
            $roomsdata[$key]['CancelPolicies'] = $Cancellation;
            $roomsdata[$key]['DefaultPolicy'] = '';
        }
        if(empty($rooms)) {
            return null;
        } else {
          return $roomsdata;
        }   
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
              $discount = $query[0]['discount'];
              $NRF = $query[0]['NonRefundable'];
            }

        }
        $return['discount'] = $discount;
        $return['NRF'] = $NRF;
     return $return;
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
    public function xmlroomwisepaxdata($data,$agent_id) {
        $agent_markup = $this->mark_up_get($agent_id);
        $admin_markup = $this->general_mark_up_get($agent_id);
        $revenue_markup =  $this->xmlrevenue_markup('tbo',$agent_id,$data);
        $total_markup = $agent_markup+$admin_markup;
        if ($revenue_markup!='') {
          $total_markup = $agent_markup+$revenue_markup;
        }

      $agent_currency = 'AED';
        $return  =array();
      // $HotelInfo = array();
      //  $inp_arr_hotel = [
      //     "ResultIndex" => [
      //       "value" => $data['ResultIndex']
      //     ],
      //     "SessionId" => [
      //       "value" => $data['sessionid']
      //     ],
      //     "HotelCode" => [
      //       "value" => $data['hotelcode']
      //     ],
      //   ];
      // $data['HotelInfo'] = $this->List_Model->HotelDetails($inp_arr_hotel);
     // print_r($HotelInfo['HotelDetails']['Description']);exit;
      $HotelRoom = array();
      $cancelinfo = array();
      // Available hotel rooms request start
          $inp_arr_hotel1 = [
          "SessionId" => [
            "value" => $data['sessionid']
          ],
          "ResultIndex" => [
            "value" => $data['ResultIndex']
          ],
          "HotelCode" => [
            "value" => $data['hotelcode']
          ],
          "IsCancellationPolicyRequired" => [
            "value" => true
          ],
          "ResponseTime" => [
            "value" => 0
          ],
        ];
          
        $AvailableRooms =  $this->AvailableHotelRooms($inp_arr_hotel1);
        if ($AvailableRooms['Status']['StatusCode']==01) {
            $key = $data['sessionid'].'-'.$data['hotelcode'];
            $CachedString = $this->cache->getItem($key);
            $CachedString->set($AvailableRooms)->expiresAfter(18000);
            $this->cache->save($CachedString);
            $return['OptionsForBooking'] = $AvailableRooms['OptionsForBooking']['FixedFormat'];
            if (isset($AvailableRooms['HotelRooms']['HotelRoom'][0])) {
              $Rooms = $AvailableRooms['HotelRooms']['HotelRoom'];
            } else {
              $Rooms[0] = $AvailableRooms['HotelRooms']['HotelRoom'];
            }
            foreach ($Rooms as $key => $value) {
                $rooms[$key]['RoomIndex'] = $value['RoomIndex'];
                $rooms[$key]['board'] = $value['MealType']=="" || is_array($value['MealType']) ? 'Room Only' : $value['MealType']; 
                $rooms[$key]['RoomName'] = $value['RoomTypeName'];
                $rooms[$key]['ImageUrl'] = isset($value['RoomAdditionalInfo']['ImageURLs']['URL']) ? $value['RoomAdditionalInfo']['ImageURLs']['URL'] : '';
                $rooms[$key]['Amenities'] = $value['Amenities'];
                $rooms[$key]['RequestType'] = 'Book';
                $rooms[$key]['extraLabel'] = '';
                $rooms[$key]['extraChildLabel'] = '';
                $DayRates = $value['RoomRate']['@attributes']['TotalFare'];
                $DayRates = ($DayRates*$total_markup)/100+$DayRates;

                $rooms[$key]['Price'] = $this->xml_currency_change($DayRates,$value['RoomRate']['@attributes']['Currency'],$agent_currency);

                if (isset($value['CancelPolicies']['CancelPolicy'][0])) {
                  $cancelList[$key] = $value['CancelPolicies']['CancelPolicy'];
                } else {
                  $cancelList[$key][0] = $value['CancelPolicies']['CancelPolicy'];
                } 

                if (isset($cancelList[$key][0]['@attributes'])) {
                    foreach ($cancelList[$key] as $key1 => $value1) {
                        $rooms[$key]['CancelPolicies'][$key1]['RoomTypeName'] = $value1['@attributes']['RoomTypeName'];
                        $rooms[$key]['CancelPolicies'][$key1]['FromDate'] = $value1['@attributes']['FromDate'];
                        $rooms[$key]['CancelPolicies'][$key1]['ToDate'] = $value1['@attributes']['ToDate'];
                        $rooms[$key]['CancelPolicies'][$key1]['ChargeType'] = $value1['@attributes']['ChargeType'];
                        $rooms[$key]['CancelPolicies'][$key1]['CancellationCharge'] = $value1['@attributes']['CancellationCharge'];
                        $rooms[$key]['CancelPolicies'][$key1]['Currency'] = $value1['@attributes']['Currency'];
                        if ($value1['@attributes']['CancellationCharge']==0) {
                            $rooms[$key]['CancelPolicies'][$key1]['application'] = 'Free of charge';
                        } else {
                            $rooms[$key]['CancelPolicies'][$key1]['application'] = 'Stay';
                        }
                    }
                }
                $rooms[$key]['DefaultPolicy'] = $value['CancelPolicies']['DefaultPolicy'];

            }

            for ($i=0; $i < $data['no_of_rooms'] ; $i++) { 
                $return['room'.($i+1)] = $rooms;
            }

            $return['RoomCombination'] = $AvailableRooms['OptionsForBooking']['RoomCombination'];
        }
        return $return;
    }
    public function AvailableHotelRooms($arg){
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
  public function xmlrevenue_markup($provider,$agent_id,$request) {  
    $stmt1 = $this->db->prepare("SELECT IFNULL(MAX(Markup),'') as Markup FROM `hotel_tbl_revenue` where ".$provider." = 1 AND FIND_IN_SET(".$agent_id.", IFNULL(Agents,'')) > 0 AND FromDate <= '".date('Y-m-d',strtotime($request['check_in']))."' AND  ToDate >= '".date('Y-m-d',strtotime($request['check_out']))."'");
    $stmt1->execute();
    $query = $stmt1->fetchAll();
    return $query[0]['Markup'];
  }
}