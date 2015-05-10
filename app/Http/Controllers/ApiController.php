<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class ApiController
 * @package App\Http\Controllers
 */
class ApiController extends Controller
{

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function Auth(Request $request)
    {
        try {
            Auth::attempt([
                'username' => trim(strip_tags($request->get('username'))),
                'password' => trim(strip_tags($request->get('password')))
            ]);
        } catch (\Exception $e){}

        return $this->getJwt();
    }
}