<?php
declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use \Firebase\JWT\JWT;
use Phpfastcache\Helper\Psr16Adapter;
require_once 'QueryHandler.php';

return function (App $app) {

    $container = $app->getContainer();
    $app->post('/', function (Request $request, Response $response,array $args) {
    	$input = $request->getAttribute('decoded_token_data');
    	$query = new QueryHandler($this->get('db'),$this->get('cache'));
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
        
        $contentType = $request->getHeaderLine('Content-Type');
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

        
        $BookingReviewvalidation = $query->validateparametersbookingreview($data);
        if($BookingReviewvalidation['status']!='true') {
          $BookingReviewvalidation['errorID'] = $GUID;
          $tracking['Type'] = 'Validation';
          $trackingType = 'Error';
      		$response->getBody()->write(json_encode($BookingReviewvalidation));
      		return $response
            ->withHeader('Content-Type', 'application/json');
      	} else {
          $data1 = unserialize(base64_decode($data['token']));
          $data = array_merge($data1,$data);

          $BookingReviewvalidation = $query->validateRoomParameter($data);
          if($BookingReviewvalidation['status']!='true') {
            $BookingReviewvalidation['errorID'] = $GUID;
            $tracking['Type'] = 'Validation';
            $trackingType = 'Error';
            $response->getBody()->write(json_encode($BookingReviewvalidation));
            return $response
              ->withHeader('Content-Type', 'application/json');
          } else {
            if (isset($data['sessionid'])) {
              $rooms = $query->xmlbookingreview($data,$input['id']);
              if (count($rooms)!=0) {
                $data1 = array('status' => true, 'message' => 'Successfull');
                $data1['details'] = $rooms;
                $tracking['Type'] = 'End';
                $trackingType = 'INFO';
              } else {
                $tracking['Type'] = 'EmptyData';
                $trackingType = 'INFO';
                $data1 = array('status'=>false,'errorID' => $GUID, 'message' => 'No Records');
              }
              $response->getBody()->write(json_encode($data1));
              return $response
                ->withHeader('Content-Type', 'application/json');
            } else {
              	$rooms = $query->bookingreview($data,$input['id']);
              	if (count($rooms)!=0) {
              		$data1 = array('status' => true, 'message' => 'Successfull');
              		$data1['details'] = $rooms;
                  $tracking['Type'] = 'End';
                  $trackingType = 'INFO';
              	} else {
                  $tracking['Type'] = 'EmptyData';
                  $trackingType = 'INFO';
    				      $data1 = array('status'=>false,'errorID' => $GUID, 'message' => 'No Records');
              	}
              	$response->getBody()->write(json_encode($data1));
        		    return $response
                  ->withHeader('Content-Type', 'application/json');
            }
          }
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
