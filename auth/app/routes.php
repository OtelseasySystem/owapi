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
    	$logger = $this->get('logwriter');
		$query = new QueryHandler($this->get('db'));
		$ip = $query->getRealIPAddr();
		$GUID = $query->createGUID();
		try {
		$StartTime = microtime(TRUE);
		$tracking = array('UserID'=>'','GUID' => $GUID,'IP' => $ip,'Type' => 'Start','Time' => '0');
		$trackingType = 'Info';
    	$logger->addInfo(implode(",", $tracking));


     	$contentType = $request->getHeaderLine('Content-Type');
		if ($contentType=="application/json") {
			$data = $request->getBody()->getContents();
			$data = json_decode($data,true);
		} else {
			$data = array('status'=>false,'errorID' => $GUID ,'message' => 'contentType should be application/json');
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

    	if (!isset($data['Agent_Code']) && !isset($data['Username']) && !isset($data['password'])) {
    		$data = array('status'=>false,'errorID' => $GUID, 'message' => 'These credentials do not match our records.');
			$payload = json_encode($data);

			$response->getBody()->write($payload);
			$tracking['Type'] = 'CredentialsMismatch';
			$trackingType = 'Error';
			return $response
					 ->withHeader('Content-Type', 'application/json');
    	} else if(!isset($data['Agent_Code'])) {
    		$data = array('status'=>false,'errorID' => $GUID , 'message' => 'These credentials do not match our records.');
			$payload = json_encode($data);

			$response->getBody()->write($payload);
			$tracking['Type'] = 'CredentialsMismatch';
			$trackingType = 'Error';
			return $response
					 ->withHeader('Content-Type', 'application/json');
	 	} else if(!isset($data['Username'])) {
    		$data = array('status'=>false ,'errorID' => $GUID , 'message' => 'These credentials do not match our records.');
			$payload = json_encode($data);

			$response->getBody()->write($payload);
			$tracking['Type'] = 'CredentialsMismatch';
			$trackingType = 'Error';
			return $response
					 ->withHeader('Content-Type', 'application/json');
	    } else if(!isset($data['password'])) {
    		$data = array('status'=>false,'errorID' => $GUID , 'message' => 'These credentials do not match our records.');
			$payload = json_encode($data);

			$response->getBody()->write($payload);
			$tracking['Type'] = 'CredentialsMismatch';
			$trackingType = 'Error';
			return $response
					 ->withHeader('Content-Type', 'application/json');
    	}

	    $input = $request->getParsedBody();
	    $db = $this->get('db');
	    $header['Agent_Code'] = $data['Agent_Code'];
	    $header['Username'] = $data['Username'];
	    $header['password'] = $data['password'];
	    $sql = "SELECT * FROM hotel_tbl_agents WHERE Agent_Code = :Agent_Code AND Username= :Username and api_status = 1 limit 1";
	    $sth = $db->prepare($sql);
	    $sth->bindParam("Agent_Code", $header['Agent_Code']);
	    $sth->bindParam("Username", $header['Username']);
	    $sth->execute();
	    $user = $sth->fetchObject();
	    // verify email address.

	    if(!$user) {
	        $data = array('status'=>false,'errorID' => $GUID, 'message' => 'These credentials do not match our records.');
			$payload = json_encode($data);

			$response->getBody()->write($payload);
			$tracking['Type'] = 'CredentialsMismatch';
			$trackingType = 'Error';
			return $response
					 ->withHeader('Content-Type', 'application/json');
	    }
	    // verify password.
	    if (md5($header['password']) != $user->password) {
	    	$data = array('status'=>false ,'errorID' => $GUID, 'message' => 'These credentials do not match our records.');
			$payload = json_encode($data);

			$response->getBody()->write($payload);
			$tracking['Type'] = 'CredentialsMismatch';
			$trackingType = 'Error';
			return $response
					 ->withHeader('Content-Type', 'application/json');
	    }
	    $jwt = $this->get('jwt'); // get settings array.
    	
    	$now = new DateTime();
    	$future = new DateTime("+35 minutes");
		$payload = [
			"id" => $user->id,
			"iat" => $now->getTimeStamp(),
			"exp" => $future->getTimeStamp(),
			'GUID' => $GUID
		];
	    $token = JWT::encode($payload, $jwt['secret'], "HS256");
	    $data = array('status'=>true,'message'=>'Successfull','token' => $token);
		$payload = json_encode($data);

		$response->getBody()->write($payload);

		$tracking['Type'] = 'End';
		$tracking['Time'] = microtime(TRUE)-$StartTime;
		$trackingType = 'Info';

    	// $logger->addInfo(implode(",", $tracking));

		return $response
				 ->withHeader('Content-Type', 'application/json');
	 	} catch (Exception $ex) {
	 		$tracking['Type'] = $ex->getMessage();
			$trackingType = 'Critical';

    		$data = array('status'=>false,'message'=>'Technical Failure','errorID' => $GUID);
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
