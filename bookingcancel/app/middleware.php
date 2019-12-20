<?php
declare(strict_types=1);

use App\Application\Middleware\SessionMiddleware;
use Slim\App;
// add(new \Slim\Csrf\Guard);

return function (App $app) {
    $app->add(SessionMiddleware::class);

    $app->add(new \Tuupola\Middleware\JwtAuthentication([
	    "path" => "/", /* or ["/api", "/admin"] */
	    "attribute" => "decoded_token_data",
	    "relaxed" => ["test_webapi.bookingcancel.otelseasy.com"],
	    "secret" => "subinrabin",
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
