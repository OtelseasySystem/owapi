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
        
      	$validation = $query->validateparameters($data);
      	$hotelbookvalidation = $query->validateparametershotelbook($data);
      	if($hotelbookvalidation['status']!='true') {
          $hotelbookvalidation['errorID'] = $GUID;
          $tracking['Type'] = 'Validation';
          $trackingType = 'Error';
      		$response->getBody()->write(json_encode($hotelbookvalidation));
      		return $response;
      	} else {
          $data1 = unserialize(base64_decode($data['token']));
          $data2 = array_merge($data,$data1);
          $hotelbookvalidation = $query->validateparametershotelbook1($data2);
          if($hotelbookvalidation['status']!='true') {
            $hotelbookvalidation['errorID'] = $GUID;
            $tracking['Type'] = 'Validation';
            $trackingType = 'Error';
            $response->getBody()->write(json_encode($hotelbookvalidation));
            return $response;
          } else {
            if (isset($data2['sessionid'])) {
              $review = $query->xmlhotelbookingfun($data2,$input['id']);
              if (count($review)!=0) {
                $tracking['Type'] = 'End';
                $trackingType = 'INFO';
                $data1 = array('status' => true, 'message' => 'Successfull');
                $data1['ConfirmationNo'] = $review['ConfirmationNo'];
              } else {
                $tracking['Type'] = 'BOOKINGFAILED';
                $trackingType = 'ERROR';
                $data1 = array('status'=>false,'errorID' => $GUID, 'message' => 'Booking Failed');
              }
              $response->getBody()->write(json_encode($data1));
              return $response;
            } else {
              $review = $query->hotelbookingfun($data2,$input['id']);
              if (count($review)!=0) {
                $tracking['Type'] = 'End';
                $trackingType = 'INFO';
                $data1 = array('status' => true, 'message' => 'Successfull');
                $data1['ConfirmationNo'] = $review['ConfirmationNo'];
              } else {
                $tracking['Type'] = 'BOOKINGFAILED';
                $trackingType = 'ERROR';
                $data1 = array('status'=>false,'errorID' => $GUID, 'message' => 'Booking Failed');
              }
              $response->getBody()->write(json_encode($data1));
              return $response;
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
