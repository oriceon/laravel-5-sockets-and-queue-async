<?php namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

use App\Services\Jwt;
use App\Services\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

abstract class Controller extends BaseController {

	use DispatchesCommands, ValidatesRequests;

    protected $client;

    public function __construct() {
        $this->client = new Client();
    }

    public function getJwt() {
        if(Auth::check()) {
            return Response::json(['token' => Jwt::encode(['id' => Auth::user()->id])], 200);
        }

        return Response::json(['message', 'You could not get your auth token, please try agian'], 400);
    }
}