<?php
declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use \Firebase\JWT\JWT;
require_once 'QueryHandler.php';

return function (App $app) {
  
  $container = $app->getContainer();
  

	$app->post('/', function (Request $request, Response $response, array $args) {
    	$input = $request->getAttribute('decoded_token_data');
    	$query = new QueryHandler($this->get('db'),$this->get('cache'));
      $contentType = $request->getHeaderLine('Content-Type');
      $logger = $this->get('logwriter');
      $ip = $query->getRealIPAddr();
      $GUID = $query->createGUID();
      try {
        $StartTime = microtime(TRUE);
        $tracking = array('UserID'=>$input['id'],'GUID' => $GUID,'IP' => $ip,'Type' => 'Start','Time' => '0');
        $trackingType = 'Info';
        $logger->addInfo(implode(",", $tracking));

        $auth = $query->UserAuthCheck($input['id']);

        if ($auth==0) {
            $data = array('status'=>false,'errorID' => $GUID, 'message' => 'Authentication Failed');
            $payload = json_encode($data);

            $response->getBody()->write($payload);
            $tracking['Type'] = 'APISTATUSNOTSET';
            $trackingType = 'Error';
            return $response
                     ->withHeader('Content-Type', 'application/json');
        }
            
        if ($contentType=="application/json") {
            $data = $request->getBody()->getContents();
            $data = json_decode($data,true);
        } else {
            $data = array('status'=>false,'errorID' => $GUID, 'message' => 'contentType should be application/json');
            $payload = json_encode($data);

            $response->getBody()->write($payload);

            $tracking['Type'] = 'ContentTypeMismatch';
            $trackingType = 'Error';
            return $response
                     ->withHeader('Content-Type', 'application/json');
        }

        if ($data=="") {
          $data = array('status'=>false,'errorID' => $GUID ,'message' => 'Error in Payload request');
          $payload = json_encode($data);

          $response->getBody()->write($payload);
          $tracking['Type'] = 'PayloadError';
          $trackingType = 'Error';
          return $response
                   ->withHeader('Content-Type', 'application/json');
        }
        
      	$validation = $query->validateparametersavailablerooms($data);
      	if ($validation['status']!='true') {
          $validation['errorID'] = $GUID;
          $tracking['Type'] = 'Validation';
          $trackingType = 'Error';
      		$response->getBody()->write(json_encode($validation));
      		return $response
           ->withHeader('Content-Type', 'application/json');
      	} else {

        $data1 = unserialize(base64_decode($data['token']));
        $data = array_merge($data1,$data);
        if (isset($data['sessionid'])) {
          $rooms = $query->xmlroomwisepaxdata($data,$input['id']);
          if (count($rooms)!=0) {
            $tracking['Type'] = 'End';
            $trackingType = 'INFO';
            $data1 = array('status' => true, 'message' => 'Successfull');
            $data1['AvailableHotelRooms'] = $rooms;
          } else {
            $tracking['Type'] = 'EmptyData';
            $trackingType = 'INFO';
            $data1 = array('status'=>false,'errorID' => $GUID, 'message' => 'No Records');
          }
        } else {

    			// $hoteldetails = $query->getHotelDetails($data['hotelcode']);
       //        	$hotel_facilities = explode(",",$hoteldetails['hotel_facilities']); 
       //        	foreach ($hotel_facilities as $key => $value) {
       //          	$hotelfacilities[$key] = $query->hotel_facilities_data($value);
       //        	}
       //        	$room_facilities = explode(",",$hoteldetails['room_facilities']); 
       //        	foreach ($room_facilities as $key => $value) {
       //          	$roomfacilities[$key] = $query->room_facilities_data($value);
       //          } 
        	$contracts = $query->contractChecking($data);
        	$rooms = array();
        	if (!empty($contracts)) {
          	for ($i=0; $i < $data['no_of_rooms']; $i++) { 
            		$roomdet[$i] = $query->roomwisepaxdata($i,$data,$contracts['contract_id'],$input['id']);
            		if(!empty($roomdet[$i])) {
              		$rooms[$i] = $roomdet[$i];
            		}
          	}
        	}
        	if(count($rooms)== $data['no_of_rooms']) {
  	    		$data1 = array('status' => true, 'message' => 'Successfull');
            $data1['token'] = $data['token'];

            for ($i=0; $i < $data['no_of_rooms']; $i++) {
              $data1['AvailableHotelRooms']['room'.($i+1)] = $rooms[$i];
              $data1['RoomCombination'] = 'All';
            }      
            $tracking['Type'] = 'End';
            $trackingType = 'INFO';   
    			} else {
    				$tracking['Type'] = 'EmptyData';
            $trackingType = 'INFO';
            $data1 = array('status'=>false,'errorID' => $GUID, 'message' => 'No Records');
    			}
        }
  			$response->getBody()->write(json_encode($data1));
      		return $response
           ->withHeader('Content-Type', 'application/json');
      	}
      } catch (Exception $ex) {
        $tracking['Type'] = $ex->getMessage();
        $trackingType = 'Critical';
        $data = array('status'=>false,'errorID' => $GUID,'message'=>'Technical Failure');
        $payload = json_encode($data);
        $response->getBody()->write($payload);
        return $response
             ->withHeader('Content-Type', 'application/json');
      } finally {
        $tracking['Time'] = microtime(TRUE)-$StartTime;
        $trackingType = 'add'.$trackingType;
        $logger->$trackingType(implode(",", $tracking));
      }
    });
};
