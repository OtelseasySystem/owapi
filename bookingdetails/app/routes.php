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
    $app->post('/v1', function (Request $request, Response $response) {
    	$input = $request->getAttribute('decoded_token_data');
    	$query = new QueryHandler($this->get('db'));
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
        
        	$validation = $query->validateparametersbookingdetail($data);
        	if ($validation['status']!='true') {
                $validation['errorID'] = $GUID;
                $tracking['Type'] = 'Validation';
                $trackingType = 'Error';
        		$response->getBody()->write(json_encode($validation));
        		return $response
                    ->withHeader('Content-Type', 'application/json');
        	} else {
                $exp = explode("HAB0", $data['ConfirmationNo']);
                $bookingdetails = array();
                if (isset($exp[1])) {
    		       $bookingdetails = $query->getBookingDetail($data['ConfirmationNo'],$input['id']);
                } else {
                   $bookingdetails = $query->getxmlBookingDetail($data['ConfirmationNo'],$input['id']);
                }
    			if (count($bookingdetails)!=0) {
                    $tracking['Type'] = 'End';
                    $trackingType = 'INFO';
    				$data1 = array('status' => true, 'message' => 'Successfull','Result'=>$bookingdetails);
    			} else {
                    $tracking['Type'] = 'EmptyData';
                    $trackingType = 'INFO';
    				$data1 = array('status' => false, 'message' => 'No Records');
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
