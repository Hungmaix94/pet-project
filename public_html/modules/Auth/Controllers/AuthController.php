<?php

namespace Modules\Auth\Controllers;


use App\Helpers\CommonHelper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Modules\Auth\Services\Auth0Service;
use Modules\Auth\Transformers\UserTransformer;
use Tymon\JWTAuth\Facades\JWTAuth;
use Zttp\Zttp;

class AuthController extends Controller
{
    protected $authService;
    const REVIEW = 'review';
    const APPROVE = 'approve';

    public function __construct(Auth0Service $service)
    {
        $this->authService = $service;
    }

    public function callback(Request $request)
    {
        try {
            if ($request->get('is_sharing') == true) {
                $user = User::userSharing();
                $userLogged = $user;
            } else {
                $this->validate($request, [
                    'code' => 'required',
                    'state' => 'required'
                ]);

                $data = $this->authService->exchangeToken($request->get('code'));

                $userInfo = $this->authService->getUser($data['access_token']);

                $payload = $this->authService->parseToken($data['access_token']);

                $user = User::createOrUpdateFromAuth0(array_merge($userInfo, [
                    'access_token' => $data['access_token'],
                    'permissions' => $payload->permissions,
                ]));

                $userLogged = User::where('name', $userInfo['name'])->first();
            }

            $clientUrl = in_array($userLogged->type, [self::REVIEW, self::APPROVE]) ? config('auth.admin_client_url') : config('auth.client_url');

            return redirect()->to($clientUrl . '?token=' . JWTAuth::fromUser($user));
        } catch (\Exception $e) {

            \Log::error($e->getTraceAsString());

            return api_response()->errorInternalError($e->getMessage());
        }
    }

    public function user(Request $request)
    {
        try {
            if ($request->get('is_sharing') == true) {
                $user = User::userSharing();
            } else {
                $user = Auth::user();
            }

            if (!$user instanceof User) {
                return api_response()->errorUnauthorized('Unauthorized');
            }

            return api_response()->withItem($user, new UserTransformer());

        } catch (\Exception $e) {
            return api_response()->errorUnauthorized($e->getMessage());
        }
    }

}
