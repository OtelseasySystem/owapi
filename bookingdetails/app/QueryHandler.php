<?php

class QueryHandler {

    protected $db;
    function __construct($db){ 
      $this->db = $db;
    }
    public function validateparametersbookingdetail($data) {
        $return = array();
        if(!isset($data['ConfirmationNo']) || $data['ConfirmationNo']=="") {
            $return['error'] = 'ConfirmationNo is mandatory';
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
    public function getBookingDetail($id,$agent_id) {
        $return = array();
        $stmt = $this->db->prepare("select a.*,b.hotel_name,b.rating,b.location,CONCAT(b.lattitude,'|',b.longitude) as map,b.city from hotel_tbl_booking a inner join hotel_tbl_hotels b on a.hotel_id=b.id where a.booking_id='".$id."' and a.Created_By = ".$agent_id."");
        $stmt->execute();
        $query = $stmt->fetchAll();

         if (count($query)==0) {
          return $return;
        } else {
          $stmt1 = $this->db->prepare("select * from bookingextrabed where bookid = ".$query[0]['id']."");
          $stmt1->execute();
          $ExBed = $stmt1->fetchAll();

          $stmt2 = $this->db->prepare("select * from hotel_tbl_bookgeneralsupplement where bookingID = ".$query[0]['id']."");
          $stmt2->execute();
          $general = $stmt2->fetchAll();

          $stmt3 = $this->db->prepare("select * from hotel_tbl_bookingboard where bookingID = ".$query[0]['id']."");
          $stmt3->execute();
          $board = $stmt3->fetchAll();

          $stmt4 = $this->db->prepare("select roomindex from traveller_details where bookingid = ".$query[0]['id']." group by roomindex");
          $stmt4->execute();
          $rooms = $stmt4->fetchAll();

          $return['HotelName'] = $query[0]['hotel_name'];
          $return['Rating'] = $query[0]['rating'];
          $return['Address'] = $query[0]['location'];
          $return['Map'] = $query[0]['map'];
          $return['City'] = $query[0]['city'];
          $return['CheckIn'] =  $query[0]['check_in'];
          $return['CheckOut'] =  $query[0]['check_out'];
          $return['BookingDate'] =  $query[0]['Created_Date'];
          $return['BookingId'] =  $query[0]['booking_id'];
          $return['ConfirmationNo']=  $query[0]['booking_flag']==1 ? $query[0]['confirmationNumber'] : $query[0]['booking_id'];
          if($query[0]['booking_flag'] == 1) {
            $return['BookingStatus'] = "Accepted";
          } else if($query[0]['booking_flag'] == 2) {
            $return['BookingStatus'] = "Pending";
          } else if($query[0]['booking_flag'] == 3) {
            $return['BookingStatus'] = "Cancelled";
          } else if($query[0]['booking_flag'] == 4) {
            $return['BookingStatus'] = "Hotel Approved";
          } else if($query[0]['booking_flag'] == 5) {
            $return['BookingStatus'] = "Cancellation Pending";
          } else if($query[0]['booking_flag'] == 8) {
            $return['BookingStatus'] = "On Request";
          } else if($query[0]['booking_flag'] == 9) {
            $return['BookingStatus'] = "Amendmemt";
          }

          $return['No_of_rooms'] =  $query[0]['book_room_count'];
          $return['No_of_days'] =  $query[0]['no_of_days'];
          $return['Currency'] = 'AED';
          // $return['Adult_count'] =  $query[0]['adults_count'];
          // $return['Child_count'] =  $query[0]['childs_count'];
          // $return['Currency'] =  'AED';
          $adultsexp = explode(",", $query[0]['Rwadults']);
          $childexp = explode(",", $query[0]['Rwchild']);
          // Traveller details
          foreach ($adultsexp as $key => $value) {
              $return['TravellerDetails']['Room'.($key+1)]['AdultCount'] = $value;
              $return['TravellerDetails']['Room'.($key+1)]['ChildCount'] = $childexp[$key];
          } 
          foreach ($rooms as $key => $value) {
              $roomindex = $value['roomindex'];
              $stmt5 = $this->db->prepare("select * from traveller_details where roomindex = '".$roomindex."' and bookingid = ".$query[0]['id']."");
              $stmt5->execute();
              $traveller_details = $stmt5->fetchAll();
              foreach ($traveller_details as $key1 => $value1) {
                $return['TravellerDetails']['Room'.($key+1)]['Guest'][$key1+1]['Title'] =  $value1['title'];
                $return['TravellerDetails']['Room'.($key+1)]['Guest'][$key1+1]['Name'] = $value1['firstname']." ".$value1['lastname'];
                 $return['TravellerDetails']['Room'.($key+1)]['Guest'][$key1+1]['Age'] = $value1['age'];
              }
          }
          // Traveller details 
         // print_r($return);
         // exit();
          // return $return;
          $revenueMarkup = explode(",", $query[0]['revenueMarkup']);
          $revenueMarkupType = explode(",", $query[0]['revenueMarkupType']);
          $revenueExtrabedMarkup = explode(",", $query[0]['revenueExtrabedMarkup']);
          $revenueExtrabedMarkupType = explode(",", $query[0]['revenueExtrabedMarkupType']);
          $revenueBoardMarkup = explode(",", $query[0]['revenueBoardMarkup']);
          $revenueBoardMarkupType = explode(",", $query[0]['revenueBoardMarkupType']);
          $revenueGeneralMarkupType = explode(",", $query[0]['revenueGeneralMarkupType']);
          $revenueGeneralMarkup = explode(",", $query[0]['revenueGeneralMarkup']);

          for($i=1;$i<=$query[0]['book_room_count'];$i++) {
            # code...
            $Fdays = 0;
            $discountType = "";
            $DisTypExplode = explode(",", $query[0]['discountType']);
            $DisStayExplode = explode(",", $query[0]['discountStay']);
            $DisPayExplode = explode(",", $query[0]['discountPay']);
            $discountCode = explode(",", $query[0]['discountCode']);
            if (!isset($DisTypExplode[$i])) {
              $DisTypExplode[$i] = $DisTypExplode[0];
            }
            if (!isset($DisStayExplode[$i])) {
              $DisStayExplode[$i] = $DisStayExplode[0];
            }
            if (!isset($DisTypExplode[$i])) {
              $DisPayExplode[$i] = $DisPayExplode[0];
            }
            if (!isset($discountCode[$i])) {
              $discountCode[$i] = $discountCode[0];
            }

            if (isset($DisTypExplode[$i]) && $DisTypExplode[$i]=="stay&pay") {
              $Cdays = $tot_days/$DisStayExplode[$i];
              $parts = explode('.', $Cdays);
              $Cdays = $parts[0];
              $Sdays = $DisStayExplode[$i]*$Cdays;
              $Pdays = $DisPayExplode[$i]*$Cdays;
              $Tdays = $tot_days-$Sdays;
              $Fdays = $Pdays+$Tdays;
              $discountType = $DisTypExplode[$i];
            }

          // Booking amount breakup
          

          $varIndividual = 'Room'.$i.'individual_amount';
          if($query[0][$varIndividual]!="") {
            $individual_amount = explode(",", $query[0][$varIndividual]);
          }

          $varIndividualDis = 'Room'.$i.'Discount';
          if($query[0][$varIndividual]!="") {
            $individual_discount = explode(",", $query[0][$varIndividualDis]);
          }

          $ExtrabedDiscount = explode(",", $query[0]['ExtrabedDiscount']);
          $GeneralDiscount = explode(",", $query[0]['GeneralDiscount']);
          $BoardDiscount = explode(",", $query[0]['BoardDiscount']);
          $RequestType = explode(",", $query[0]['RequestType']);

          $roomExp = explode(",", $query[0]['room_id']);
          $boardName = explode(",", $query[0]['board']);
          $admin_markup = explode(",", $query[0]['admin_markup']);
            if (!isset($roomExp[$i])) {
              $room_id = $roomExp[0];
            } else {
              $room_id = $roomExp[$i];
            }
            $room_name = $this->roomnameGET($room_id,$query[0]['hotel_id']);
            if (!isset($boardName[$i])) {
              $boardName[$i] = $boardName[0];
            }

            if (isset($admin_markup[$i])) {
              $total_markup = $query[0]['agent_markup']+$admin_markup[$i];
            } else {
              $total_markup = $query[0]['agent_markup']+$admin_markup[0];
            }

            if (!isset($ExtrabedDiscount[$i])) {
              $ExtrabedDiscount[$i] = 0;
            }
            if (!isset($GeneralDiscount[$i])) {
              $GeneralDiscount[$i] = 0;
            }
            if (!isset($BoardDiscount[$i])) {
              $BoardDiscount[$i] = 0;
            }


            if (!isset($revenueMarkup[$i])) {
              $revenueMarkup[$i] = $revenueMarkup[0];
            }
            if (!isset($revenueMarkupType[$i])) {
              $revenueMarkupType[$i] = $revenueMarkupType[0];
            }

            if (!isset($revenueExtrabedMarkup[$i])) {
              $revenueExtrabedMarkup[$i] = $revenueExtrabedMarkup[0];
            }
            if (!isset($revenueExtrabedMarkupType[$i])) {
              $revenueExtrabedMarkupType[$i] = $revenueExtrabedMarkupType[0];
            }

            if (!isset($revenueGeneralMarkup[$i])) {
              $revenueGeneralMarkup[$i] = $revenueGeneralMarkup[0];
            }
            if (!isset($revenueGeneralMarkupType[$i])) {
              $revenueGeneralMarkupType[$i] = $revenueGeneralMarkupType[0];
            }

            $varRoomrevenueMarkup = 'Room'.$i.'revenueMarkup';
            $varRoomrevenueMarkupType = 'Room'.$i.'revenueMarkupType';
            if ($query[0][$varRoomrevenueMarkup]!="") {
              $$varRoomrevenueMarkup = explode(",", $query[0][$varRoomrevenueMarkup]);
              $$varRoomrevenueMarkupType = explode(",", $query[0][$varRoomrevenueMarkupType]);
            }

            $varRoomrevenueExtrabedMarkup = 'Room'.$i.'revenueExtrabedMarkup';
            $varRoomrevenueExtrabedMarkupType = 'Room'.$i.'revenueExtrabedMarkupType';
            if ($query[0][$varRoomrevenueExtrabedMarkup]!="") {
              $$varRoomrevenueExtrabedMarkup = explode(",", $query[0][$varRoomrevenueExtrabedMarkup]);
              $$varRoomrevenueExtrabedMarkupType = explode(",", $query[0][$varRoomrevenueExtrabedMarkupType]);
            }

            $varRoomrevenueBoardMarkup = 'Room'.$i.'revenueBoardMarkup';
            $varRoomrevenueBoardMarkupType = 'Room'.$i.'revenueBoardMarkupType';
            if ($query[0][$varRoomrevenueBoardMarkup]!="") {
              $$varRoomrevenueBoardMarkup = explode(",", $query[0][$varRoomrevenueBoardMarkup]);
              $$varRoomrevenueBoardMarkupType = explode(",", $query[0][$varRoomrevenueBoardMarkupType]);
            }

            $varRoomrevenueGeneralMarkup = 'Room'.$i.'revenueGeneralMarkup';
            $varRoomrevenueGeneralMarkupType = 'Room'.$i.'revenueGeneralMarkupType';
            if ($query[0][$varRoomrevenueGeneralMarkup]!="") {
              $$varRoomrevenueGeneralMarkup = explode(",", $query[0][$varRoomrevenueGeneralMarkup]);
              $$varRoomrevenueGeneralMarkupType = explode(",", $query[0][$varRoomrevenueBoardMarkupType]);
            }



            $x = 0;
            for ($j=0; $j < $query[0]['no_of_days']; $j++) { 
              if (!isset($individual_discount[$j])) {
                $individual_discount[$j] = 0;
              }
              $TExAmount[$j] = 0;
              $GAamount[$j] = 0;
              $GCamount[$j] = 0;
              $TBAamount[$j] = 0;
              $TBCamount[$j] = 0;
              if (isset($$varRoomrevenueMarkup[$j])) {
                $revenueMarkup[$i-1] = $$varRoomrevenueMarkup[$j];
                $revenueMarkupType[$i-1] = $$varRoomrevenueMarkupType[$j];
              }

              if (isset($$varRoomrevenueExtrabedMarkup[$j])) {
                $revenueExtrabedMarkup[$i-1] = $$varRoomrevenueExtrabedMarkup[$j];
                $revenueExtrabedMarkupType[$i-1] = $$varRoomrevenueExtrabedMarkupType[$j];
              }

              if (isset($$varRoomrevenueBoardMarkup[$j])) {
                $revenueBoardMarkup[$i-1] = $$varRoomrevenueBoardMarkup[$j];
                $revenueBoardMarkupType[$i-1] = $$varRoomrevenueBoardMarkupType[$j];
              }

              if (isset($$varRoomrevenueGeneralMarkup[$j])) {
                $revenueGeneralMarkup[$i-1] = $$varRoomrevenueGeneralMarkup[$j];
                $revenueGeneralMarkupType[$i-1] = $$varRoomrevenueGeneralMarkupType[$j];
              }

              $return['RoomRate']['Room'.($i)][$x]['Date'] = date('d/m/Y', strtotime($query[0]['check_in']. ' + '.$j.'  days'));
              $return['RoomRate']['Room'.($i)][$x]['RoomName'] = $room_name;
              $return['RoomRate']['Room'.($i)][$x]['Board'] = $boardName[$i];

              $rmAmount = 0;
              if ($revenueMarkup[$i-1]!="" && $revenueMarkup[$i-1]!=0) {
                if ($revenueMarkupType[$i-1]=='Percentage') {
                  $rmAmount = ($individual_amount[$j]*$revenueMarkup[$i-1])/100;
                } else {
                  $rmAmount = $revenueMarkup[$i-1];
                }
              }
              $roomAmount[$j] = (($individual_amount[$j]*$total_markup)/100)+$individual_amount[$j]+$rmAmount;

              $DisroomAmount[$j] = $roomAmount[$j]-($roomAmount[$j]*$individual_discount[$j])/100;
              $return['RoomRate']['Room'.($i)][$x]['TotalFare'] = $DisroomAmount[$j];
              
              // Extrabed list start

              if (count($ExBed)!=0) {
                foreach ($ExBed as $Exkey => $Exvalue) {

                  if ($Exvalue['date']==date('Y-m-d', strtotime($query[0]['check_in']. ' + '.$j.'  days'))) {
                    $exroomExplode = explode(",", $Exvalue['rooms']);
                    $examountExplode = explode(",", $Exvalue['Exrwamount']);
                    $exTypeExplode = explode(",", $Exvalue['Type']);
                    foreach ($exroomExplode as $Exrkey => $EXRvalue) {
                      if ($EXRvalue==($i+1)) {
                        $x++;
                        $return['RoomRate']['Room'.($i+1)][$x]['Date'] = date('d/m/Y', strtotime($query[0]['check_in']. ' + '.$j.'  days'));
                        $return['RoomRate']['Room'.($i+1)][$x]['Type'] = $exTypeExplode[$Exrkey];
                        $return['RoomRate']['Room'.($i+1)][$x]['Board'] = $boardName[$i];
                        $ExMAmount = 0;

                        if ($revenueMarkup[$i-1]!="") {
                          if ($exTypeExplode[$Exrkey]=="Adult Extrabed" || $exTypeExplode[$Exrkey]=="Child Extrabed") {
                            if ($revenueExtrabedMarkupType[$i-1]=='Percentage') {
                              $ExMAmount = ($examountExplode[$Exrkey]*$revenueExtrabedMarkup[$i-1])/100;
                            } else {
                              $ExMAmount = $revenueExtrabedMarkup[$i-1];
                            }
                          } else {
                            if ($revenueBoardMarkupType[$i-1]=='Percentage') {
                              $ExMAmount = ($examountExplode[$Exrkey]*$revenueBoardMarkup[$i-1])/100;
                            } else {
                              $ExMAmount = $revenueBoardMarkup[$i-1];
                            }
                          }
                        }
                        $ExDis = 0;
                        if ($ExtrabedDiscount[$i]==1) {
                          $ExDis = $individual_discount[$j];
                        }
                        $ExAmount[$j] = (($examountExplode[$Exrkey]*$total_markup)/100)+$examountExplode[$Exrkey]+$ExMAmount-(((($examountExplode[$Exrkey]*$total_markup)/100)+$examountExplode[$Exrkey]+$ExMAmount)*$ExDis/100);
                        $TExAmount[$j] +=(($examountExplode[$Exrkey]*$total_markup)/100)+$examountExplode[$Exrkey]+$ExMAmount-(((($examountExplode[$Exrkey]*$total_markup)/100)+$examountExplode[$Exrkey]+$ExMAmount)*$ExDis/100);

                        $return['RoomRate']['Room'.($i+1)][$x]['TotalFare'] = $ExAmount[$j];

                      } 
                    } 
                  } 
                } 
              }
              // Extrabed list end

              // General suppliment list start
              if (count($general)!=0) {
                foreach ($general as $gskey => $gsvalue) {
                  if ($gsvalue['gstayDate']==date('d/m/Y', strtotime($query[0]['check_in']. ' + '.$j.'  days'))) {
                    // Adult and room General supplement list end
                    $gsadultExplode = explode(",", $gsvalue['Rwadult']);
                    $gsadultAmountExplode = explode(",", $gsvalue['Rwadultamount']);
                    foreach ($gsadultExplode as $gsakey => $gsavalue) {
                      if ($gsavalue==($i+1)) {
                        $x++;
                        $return['RoomRate']['Room'.($i+1)][$x]['Date'] = date('d/m/Y', strtotime($query[0]['check_in']. ' + '.$j.'  days'));
                        $return['RoomRate']['Room'.($i+1)][$x]['Type'] = $gsvalue['generalType'];
                        $return['RoomRate']['Room'.($i+1)][$x]['Board'] = $boardName[$i];
                        $GSMAmount = 0;
                        if ($revenueGeneralMarkup[$i-1]!="") {
                          if ($revenueGeneralMarkupType[$i-1]=='Percentage') {
                            $GSMAmount = ($gsadultAmountExplode[$gsakey]*$revenueGeneralMarkup[$i-1])/100;
                          } else {
                            $GSMAmount = $revenueGeneralMarkup[$i-1];
                          }
                        }
                        $GSDis = 0;
                        if ($GeneralDiscount[$i]==1) {
                          $GSDis = $individual_discount[$j];
                        }
                        $GAamount[$j] = (($gsadultAmountExplode[$gsakey]*$total_markup)/100)+$gsadultAmountExplode[$gsakey]+$GSMAmount-(((($gsadultAmountExplode[$gsakey]*$total_markup)/100)+$gsadultAmountExplode[$gsakey]+$GSMAmount)*$GSDis/100);

                        $return['RoomRate']['Room'.($i+1)][$x]['TotalFare'] = $GAamount[$j];
                      } 
                    }


                    // Adult and room General supplement list end
                    // Child general supplement list start
                    $gschildExplode = explode(",", $gsvalue['Rwchild']);
                    $gschildAmountExplode = explode(",", $gsvalue['RwchildAmount']);
                    foreach ($gschildExplode as $gsckey => $gscvalue) {
                      if ($gscvalue==($i+1)) {
                        $return['AmountDetails']['Room'.($i+1)]['generalSupplementRate'][$gskey]['date'] = date('d/m/Y', strtotime($query[0]['check_in']. ' + '.$j.'  days'));
                        $return['AmountDetails']['Room'.($i+1)]['generalSupplementRate'][$gskey]['type'] = 'Child '.$gsvalue['generalType'];
                        $return['AmountDetails']['Room'.($i+1)]['generalSupplementRate'][$gskey]['board'] = $boardName[$i]; 
                        $GSMAmount = 0;
                        if ($revenueGeneralMarkup[$i-1]!="") {
                          if ($revenueGeneralMarkupType[$i-1]=='Percentage') {
                            $GSMAmount = ($gschildAmountExplode[$gsckey]*$revenueGeneralMarkup[$i-1])/100;
                          } else {
                            $GSMAmount = $revenueGeneralMarkup[$i-1];
                          }
                        }

                        $GCamount[$j] = ((($gschildAmountExplode[$gsckey]*$total_markup)/100)+$gschildAmountExplode[$gsckey]+$GSMAmount)-((($gschildAmountExplode[$gsckey]*$total_markup)/100)+$gschildAmountExplode[$gsckey]+$GSMAmount)*$GSDis/100;

                        $return['AmountDetails']['Room'.($i+1)]['generalSupplementRate'][$gskey]['amount'] = $GCamount[$j];
                      }
                    }

                    // Child general supplement list end
                  }
                }
              }
              // General suppliment list end

              // Board supplement list start

              foreach ($board as $bkey => $bvalue) { 
                if ($bvalue['stayDate']==date('d/m/Y', strtotime($query[0]['check_in']. ' + '.$j.'  days'))) {
                  // Adult Board supplement list start
                  $ABReqwadultexplode = explode(",", $bvalue['Breqadults']);
                  $ABRwadultexplode = explode(",", $bvalue['Rwadult']);
                  $ABRwadultamountexplode = explode(",", $bvalue['RwadultAmount']);
                  foreach ($ABRwadultexplode as $ABRwkey => $ABRwvalue) {
                    if ($ABRwvalue==$i) {
                      $x++;
                      $return['RoomRate']['Room'.($i+1)][$x]['Date'] = date('d/m/Y', strtotime($query[0]['check_in']. ' + '.$j.'  days'));
                      $return['RoomRate']['Room'.($i+1)][$x]['Type'] = $bvalue['board'];
                      $return['RoomRate']['Room'.($i+1)][$x]['Board'] = $boardName[$i]; 
                      $BSMAmount = 0;

                      if ($revenueBoardMarkup[$i-1]!="") {
                        if ($revenueBoardMarkupType[$i-1]=='Percentage') {
                          $BSMAmount = ($ABRwadultamountexplode[$ABRwkey]*$revenueBoardMarkup[$i-1])/100;
                        } else {
                          $BSMAmount = $revenueBoardMarkup[$i-1]*$ABReqwadultexplode[$ABRwkey];
                        }
                      }

                      $BSDis = 0;
                      if ($BoardDiscount[$i]==1) {
                        $BSDis = $individual_discount[$j];
                      }
                      $BAamount[$j] = ((($ABRwadultamountexplode[$ABRwkey]*$total_markup)/100)+$ABRwadultamountexplode[$ABRwkey]+$BSMAmount)-((($ABRwadultamountexplode[$ABRwkey]*$total_markup)/100)+$ABRwadultamountexplode[$ABRwkey]+$BSMAmount)*$BSDis/100;
                      $TBAamount[$j] += $BAamount[$j];
                      $return['RoomRate']['Room'.($i+1)][$x]['TotalFare'] = $BAamount[$j];

                    } 
                  }
                  // Adult Board supplement list end

                  // Child Board supplement list start
                  $CBReqwchildexplode = explode(",", $bvalue['BreqchildCount']);
                  $CBRwchildexplode = explode(",", $bvalue['Rwchild']);
                  $CBRwchildamountexplode = explode(",", $bvalue['RwchildAmount']);
                  foreach ($CBRwchildexplode as $CBRwkey => $CBRwvalue) {
                    if ($CBRwvalue==$i) {
                      $x++;
                      $return['RoomRate']['Room'.($i+1)][$x]['Date'] = date('d/m/Y', strtotime($query[0]['check_in']. ' + '.$j.'  days'));
                      $return['RoomRate']['Room'.($i+1)][$x]['Type'] = $bvalue['board'];
                      $return['RoomRate']['Room'.($i+1)][$x]['Board'] = $boardName[$i]; 
                      if ($revenueBoardMarkup[$i-1]!="") {
                        if ($revenueBoardMarkupType[$i-1] == 'Percentage') {
                          $BSMAmount = ($CBRwchildamountexplode[$CBRwkey]*$revenueBoardMarkup[$i-1])/100;
                        } else {
                          $BSMAmount = $revenueBoardMarkup[$i-1]*$CBReqwchildexplode[$CBRwkey];
                        }
                      }

                      $BSDis = 0;
                      if ($BoardDiscount[$i]==1) {
                        $BSDis = $individual_discount[$j];
                      }
                      $BCamount[$j] = ((($CBRwchildamountexplode[$CBRwkey]*$total_markup)/100)+$CBRwchildamountexplode[$CBRwkey]+$BSMAmount)-((($CBRwchildamountexplode[$CBRwkey]*$total_markup)/100)+$CBRwchildamountexplode[$CBRwkey]+$BSMAmount)*$BSDis/100;
                      $TBCamount[$j] += $BCamount[$j];
                      $return['RoomRate']['Room'.($i+1)][$x]['TotalFare'] = $BCamount[$j];


                    }
                  }
                  // Child Board supplement list end

                }
              }

              // Board supplement list end
              $x++;
            }
              if (isset($DisTypExplode[$i]) && $DisTypExplode[$i]=="stay&pay" && $Fdays!=0) {
                array_splice($DisroomAmount, 1,$Fdays);
                if ($ExtrabedDiscount[$i]==1) {
                  array_splice($TExAmount,1,$Fdays);
                }
                if ($GeneralDiscount[$i]==1) {
                  array_splice($GAamount,1,$Fdays);
                  array_splice($GCamount,1,$Fdays);
                }
                if ($BoardDiscount[$i]==1) {
                  array_splice($TBAamount,1,$Fdays);
                  array_splice($TBCamount,1,$Fdays);
                }
              } 


              $totRmAmt[$i] = array_sum($DisroomAmount)+array_sum($TExAmount)+array_sum($GAamount)+array_sum($GCamount)+array_sum($TBAamount)+array_sum($TBCamount); 
          }
          
          // $return['AmountDetails']['GrandTotal'] = (array_sum($totRmAmt)*$query[0]['tax'])/100+array_sum($totRmAmt);
          $stmt4 = $this->db->prepare("select * from hotel_tbl_bookcancellationpolicy where bookingId = ".$query[0]['id']." order by daysInAdvance asc");
          $stmt4->execute();
          $cancelation = $stmt4->fetchAll();

          $roomExp = explode(",", $query[0]['room_id']);
          foreach ($roomExp as $key => $value) {
            foreach ($cancelation as $Canckey => $Cancvalue) {
              if ($Cancvalue['roomIndex']==($key+1) || $Cancvalue['roomIndex']=="") {
                if ($Cancvalue['application']=="NON REFUNDABLE") {
                  $return['CancellationPolicy'][$Canckey]['RoomName'] = $this->roomnameGET($Cancvalue['room_id'],$query[0]['hotel_id']);
                  $return['CancellationPolicy'][$Canckey]['RoomIndex'] = $key+1;
                  $return['CancellationPolicy'][$Canckey]['FromDate'] = date('Y-m-d',strtotime($query[0]['Created_Date']));
                  $return['CancellationPolicy'][$Canckey]['ToDate'] = date('Y-m-d',strtotime($query[0]['check_in']));
                  $return['CancellationPolicy'][$Canckey]['ChargeType'] = 'Percentage';
                  $return['CancellationPolicy'][$Canckey]['CancellationCharge'] = $Cancvalue['cancellationPercentage'];
                  $return['CancellationPolicy'][$Canckey]['Application'] = $Cancvalue['application'];
                  $return['CancellationPolicy'][$Canckey]['Currency'] = 'AED';
                } else {
                  $return['CancellationPolicy'][$Canckey]['RoomName'] = $this->roomnameGET($Cancvalue['room_id'],$query[0]['hotel_id']);
                  $return['CancellationPolicy'][$Canckey]['RoomIndex'] = $key+1;
                  $return['CancellationPolicy'][$Canckey]['FromDate'] = date('Y-m-d' , strtotime('-'.($Cancvalue['daysFrom']).' days', strtotime($query[0]['check_in'])));
                  $return['CancellationPolicy'][$Canckey]['ToDate'] = date('Y-m-d' , strtotime('-'.$Cancvalue['daysTo'].' days', strtotime($query[0]['check_in'])));
                  $return['CancellationPolicy'][$Canckey]['ChargeType'] = 'Percentage';
                  $return['CancellationPolicy'][$Canckey]['CancellationCharge'] = $Cancvalue['cancellationPercentage'];
                  $return['CancellationPolicy'][$Canckey]['Application'] = $Cancvalue['application'];
                  $return['CancellationPolicy'][$Canckey]['Currency'] = 'AED';
                }
              }
            }
          }
          // Booking amount breakup

        $return['ImportantPolicy'] = '';
        return $return;
        }
    }
    public function getxmlBookingDetail($confirmationNumber,$agent_id) {
      $AgentCurrency = 'AED';
      $return = array();
      $stmt = $this->db->prepare("select * from xml_hotel_booking  where ConfirmationNo='".$confirmationNumber."' and agent_id = ".$agent_id."");
      $stmt->execute();
      $query = $stmt->fetchAll();

      if (count($query)==0) {
        return $return;
      }

      $inp_arr =[
          "ConfirmationNo"=>[
            "value" => $query[0]['ConfirmationNo']
          ]
      ];
         
      $xmlData =  $this->HotelBookingDetail($inp_arr);

      if ($xmlData['Status']['StatusCode']=='01') {

        $return['HotelName'] = $xmlData['BookingDetail']['HotelName'];
        if ($xmlData['BookingDetail']['Rating']=="FiveStar") {
                $star = 5;
        } 
        if ($xmlData['BookingDetail']['Rating']=="FourStar") {
          $star = 4;
        } 
        if ($xmlData['BookingDetail']['Rating']=="ThreeStar") {
          $star = 3;
        } 
        if ($xmlData['BookingDetail']['Rating']=="TwoStar") {
          $star = 2;
        }
        if ($xmlData['BookingDetail']['Rating']=="OneStar") {
          $star = 1;
        }

        $return['Rating'] = $star;
        $return['Address'] = $xmlData['BookingDetail']['AddressLine1'];
        $return['Map'] = $xmlData['BookingDetail']['Map'];
        $return['City'] = $xmlData['BookingDetail']['City'];
        $return['CheckIn'] = $xmlData['BookingDetail']['CheckInDate'];
        $return['CheckOut'] = $xmlData['BookingDetail']['CheckOutDate'];
        $return['BookingDate'] = $xmlData['BookingDetail']['BookingDate'];
        $return['BookingId'] = $xmlData['BookingDetail']['@attributes']['BookingId'];
        $return['ConfirmationNo'] = $xmlData['BookingDetail']['@attributes']['ConfirmationNo'];
        $return['BookingStatus'] = $xmlData['BookingDetail']['@attributes']['BookingStatus'];
        $return['No_of_rooms'] = $query[0]['no_of_rooms'];
        $return['No_of_days'] = $query[0]['no_of_days'];
        $return['Currency'] = 'AED';

        if (isset($xmlData['BookingDetail']['Roomtype']['RoomDetails'][0])) {
          $RoomDetails = $xmlData['BookingDetail']['Roomtype']['RoomDetails'];
        } else {
          $RoomDetails[0] = $xmlData['BookingDetail']['Roomtype']['RoomDetails'];
        }

        $i= 0;
        foreach ($RoomDetails as $key => $value) {
          $return['TravellerDetails']['Room'.($key+1)]['AdultCount'] = $value['AdultCount'];
          $return['TravellerDetails']['Room'.($key+1)]['ChildCount'] = is_array($value['ChildCount']) ? 0 : $value['ChildCount'];
          if (isset($value['GuestInfo']['Guest'][0])) {
            $GuestInfo = $value['GuestInfo']['Guest'];
          } else {
            $GuestInfo[0] = $value['GuestInfo']['Guest'];
          }
          foreach ($GuestInfo as $key1 => $value1) { 
            $return['TravellerDetails']['Room'.($key+1)]['Guest'][$key1]['Title'] = $value1['Title'];
            $return['TravellerDetails']['Room'.($key+1)]['Guest'][$key1]['Name'] = $value1['FirstName'].' '.$value1['LastName'];
            $return['TravellerDetails']['Room'.($key+1)]['Guest'][$key1]['Age'] = $value1['Age'];
          }


          $return['RoomRate']['Room'.($key+1)][$i]['Date'] = date('d/m/Y',strtotime($query[0]['Check_in'])) .' to '.date('d/m/Y',strtotime($query[0]['Check_out']));
          $return['RoomRate']['Room'.($key+1)][$i]['RoomName'] = $value['RoomName'];
          $return['RoomRate']['Room'.($key+1)][$i]['Board'] = $value['MealType']=="" || is_array($value['MealType']) ? 'Room Only' : $value['MealType'];
          $return['RoomRate']['Room'.($key+1)][$i]['TotalFare'] = $this->xml_currency_change($value['RoomRate']['@attributes']['RoomFare'],$value['RoomRate']['@attributes']['Currency'],$AgentCurrency);

          if ($value['RoomRate']['@attributes']['RoomTax']!=0) {
            $i++;
            $return['RoomRate']['Room'.($key+1)][$i]['Date'] = date('d/m/Y',strtotime($query[0]['Check_in'])) .' to '.date('d/m/Y',strtotime($query[0]['Check_out']));
            $return['RoomRate']['Room'.($key+1)][$i]['Type'] = 'Room Tax';
            $return['RoomRate']['Room'.($key+1)][$i]['Board'] = $value['MealType']=="" || is_array($value['MealType']) ? 'Room Only' : $value['MealType'];
            $return['RoomRate']['Room'.($key+1)][$i]['TotalFare'] = $this->xml_currency_change($value['RoomRate']['@attributes']['RoomTax'],$value['RoomRate']['@attributes']['Currency'],$AgentCurrency);
          }

          if (count($value['Supplements'])!=0) {
            $i++;
            $return['RoomRate']['Room'.($key+1)][$i]['Date'] = date('d/m/Y',strtotime($query[0]['Check_in'])) .' to '.date('d/m/Y',strtotime($query[0]['Check_out']));
            $return['RoomRate']['Room'.($key+1)][$i]['Type'] = $value['Supplements']['Supp_info']['@attributes']['SuppName'];
            $return['RoomRate']['Room'.($key+1)][$i]['Board'] = $value['MealType']=="" || is_array($value['MealType']) ? 'Room Only' : $value['MealType'];
            $return['RoomRate']['Room'.($key+1)][$i]['TotalFare'] = $this->xml_currency_change($value['Supplements']['Supp_info']['@attributes']['Price'],$value['RoomRate']['@attributes']['Currency'],$AgentCurrency);
          }

          $i++;
        }
        if(isset($xmlData['BookingDetail']['HotelCancelPolicies']['CancelPolicy']) && count($xmlData['BookingDetail']['HotelCancelPolicies']['CancelPolicy'])!=0) { 
          $x=0;
          foreach ($xmlData['BookingDetail']['HotelCancelPolicies']['CancelPolicy'] as $key => $value) {
            $return['CancellationPolicy'][$x]['RoomName'] = $value['@attributes']['RoomTypeName'];
            $return['CancellationPolicy'][$x]['RoomIndex'] = $value['@attributes']['RoomIndex'];
            $return['CancellationPolicy'][$x]['FromDate'] = $value['@attributes']['FromDate'];
            $return['CancellationPolicy'][$x]['ToDate'] = $value['@attributes']['ToDate'];
            $return['CancellationPolicy'][$x]['ChargeType'] = $value['@attributes']['ChargeType'];
            $return['CancellationPolicy'][$x]['CancellationCharge'] = $value['@attributes']['CancellationCharge'];
            $return['CancellationPolicy'][$x]['Currency'] = $value['@attributes']['Currency'];
            if($value['@attributes']['CancellationCharge']==0) {
              $return['CancellationPolicy'][$x]['Application'] = 'Free of Charge';
            } else {
              $return['CancellationPolicy'][$x]['Application'] = 'Stay';
            }
            $x++;
          }
          foreach ($xmlData['BookingDetail']['HotelCancelPolicies']['NoShowPolicy'] as $key => $value) {
            $x++;
            $return['CancellationPolicy'][$x]['RoomName'] = $xmlData['BookingDetail']['HotelCancelPolicies']['NoShowPolicy']['@attributes']['RoomTypeName'];
            $return['CancellationPolicy'][$x]['RoomIndex'] = $xmlData['BookingDetail']['HotelCancelPolicies']['NoShowPolicy']['@attributes']['RoomIndex'];
            $return['CancellationPolicy'][$x]['FromDate'] = $xmlData['BookingDetail']['HotelCancelPolicies']['NoShowPolicy']['@attributes']['FromDate'];
            $return['CancellationPolicy'][$x]['ToDate'] = $xmlData['BookingDetail']['HotelCancelPolicies']['NoShowPolicy']['@attributes']['ToDate'];
            $return['CancellationPolicy'][$x]['ChargeType'] = $xmlData['BookingDetail']['HotelCancelPolicies']['NoShowPolicy']['@attributes']['ChargeType'];
            $return['CancellationPolicy'][$x]['CancellationCharge'] = $xmlData['BookingDetail']['HotelCancelPolicies']['NoShowPolicy']['@attributes']['CancellationCharge'];
            $return['CancellationPolicy'][$x]['Currency'] = $xmlData['BookingDetail']['HotelCancelPolicies']['NoShowPolicy']['@attributes']['Currency'];
            if($xmlData['BookingDetail']['HotelCancelPolicies']['NoShowPolicy']['@attributes']['CancellationCharge']==0) {
              $return['CancellationPolicy'][$x]['Application'] = 'Free of Charge';
            } else {
              $return['CancellationPolicy'][$x]['Application'] = 'Stay';
            }
          }
          
        // RoomRate
        }
        $return['ImportantPolicy'] = $xmlData['BookingDetail']['HotelPolicyDetails'];
      }
      return  $return;
    }
    public function HotelBookingDetail($arg){
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