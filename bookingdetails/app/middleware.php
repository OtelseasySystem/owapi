<?php
declare(strict_types=1);

use App\Application\Middleware\SessionMiddleware;
use Slim\App;
// add(new \Slim\Csrf\Guard);

return function (App $app) {
	$configs = include('../config.php');
    $app->add(SessionMiddleware::class);

    $app->add(new \Tuupola\Middleware\JwtAuthentication([
	    "path" => "/v1", /* or ["/api", "/admin"] */
	    "attribute" => "decoded_token_data",
	    "relaxed" => [$config['BookingDetail-link'],"headers"],
	    "secure" => true,
	    "secret" => $configs['secret'],
	    "algorithm" => ["HS256"],
	    "error" => function ($response, $arguments) {
	        $data["status"] = "error";
	        $data["message"] = $arguments["message"];
			$payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

            $response->getBody()->write($payload);
	        return $response
	            ->withHeader("Content-Type", "application/json");
	    }
	]));

};
