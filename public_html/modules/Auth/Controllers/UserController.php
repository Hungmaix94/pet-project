<?php
/**
 * Created by PhpStorm.
 * User: phamduchung
 * Date: 7/5/19
 * Time: 4:41 PM
 */

namespace Modules\Auth\Controllers;


use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;
use App\Services\Zttp;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;
use Modules\Auth\Models\Company;
use Modules\Auth\Models\User;
use Modules\Auth\Services\Auth0Service;
use Modules\Auth\Transformers\CustomerTransformer;
use Modules\Auth\Transformers\UserTransformer;
use Modules\Auth\Validations\UserValidation;
use Illuminate\Support\Facades\Validator;
use Modules\JD\Models\Email;
use Webpatser\Uuid\Uuid;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->select('name', 'id', 'email', 'permissions', 'is_superadmin','user_settings')
            ->where('type', 'user')
            ->latest()
            ->get();

        return api_response()->json($users);
    }

    public function approvers(Request $request)
    {
        $users = User::query()
            ->select('username as name' , 'id', 'picture', 'email', 'permissions', 'is_superadmin')
            ->where('type', User::APPROVE_TYPE)
            ->where('is_superadmin', 0)
            ->orderBy('users.id', 'DESC')
            ->paginate($request->get('limit', 20));

        return api_response()->json($users);
    }

    public function customer(Request $request){
        $user = User::query()
            ->where('type', '=', 'user')
            //->where('is_superadmin', '=', 0)
            ->with('company')
            ->orderBy('users.id', 'DESC')
            ->paginate($request->get('limit', 5));

        return api_response()->withPaginator($user, new CustomerTransformer);
    }

    public function store($id, Request $request)
    {
        $input = array_merge($request->all(),['id'=> $id]);
        $validator = new UserValidation($input);

        if ($validator->fails()) {
            return api_response()->errorUnprocessableEntity($validator->errors());
        }

        $user = User::findOrFail($id);

        $user->update([
            'user_settings' => json_encode($input['user_settings'])
        ]);

        return api_response()->withItem($user->refresh(), new UserTransformer());
    }

    public function assignCompany($userId, $companyId){
        $user = User::findOrFail($userId);
        $company = Company::findOrFail($companyId);

        $user->company_id = $company->id;
        $user->save();
        $user->company = $company;

        return api_response()->json($user);
    }

    protected $authService;

    public function __construct(Auth0Service $service)
    {
        $this->authService = $service;
    }

    public function register(Request $request)
    {
        try {
            $username = CommonHelper::createAnUniqueUserNameFromAName('jom' . '_' . Str::lower(convertName($request->get('name'),'')));

            $validator = Validator::make($request->all(), [
                "company" => "required",
                "name" => "required",
            ]);


           $emailFromToken =  User::userSharing()->token_sharing->email;
            \Log::info('email-register',[$emailFromToken]);
            if($validator->fails()){
                return api_response()->errorUnprocessableEntity($validator->errors());
            }

            $password = Str::random(8);
//            $email = CommonHelper::createAnUniqueEmailFromOriginalEmail($emailFromToken);
            $email = $emailFromToken;

            $uid = Uuid::generate()->string;

            $formData = [
                "email" =>  $email,
                "user_metadata" => [],
                "blocked" => 'false',
                "email_verified" => 'false',
                "app_metadata" => [],
                "name" => $request->get('name'),
                "nickname" => $request->get('name'),
                "connection" => "Username-Password-Authentication",
                "password" => $password,
                "verify_email" => 'false',
                "username" => $username,
            ];

            $tokenData = $this->authService->getToken();

            $response = Zttp::withHeaders(['Authorization' => 'Bearer ' . $tokenData['access_token']])
                ->asFormParams()
                ->post(config('auth.domain') . '/api/v2/users', $formData);

            $data = $response->json();

            if (!$response->isOk()) {
                return api_response()->errorUnprocessableEntity("({$data['statusCode']}-{$data['errorCode']}-{$data['error']}) {$data['message']}");
            }

            $data['sub'] = $data['user_id'];

            $response = Zttp::withHeaders(['Authorization' => 'Bearer ' . $tokenData['access_token']])
                ->asJson()
                ->post(config('auth.domain') . '/api/v2/users/' . $data['user_id'] . '/roles', [
                    'roles' => [ env('ROLE_ICVS_CUSTOMER')]
                ]);

            $user = User::createOrUpdateFromAuth0($data);

            if($user) {
                $company = Company::updateOrCreate(
                    ['name'=> $request->get('company')],
                    [
                        'description' => $request->get('company'),
                        'status' => 1
                    ]
                );
                $user->update([
                    'username' => $username,
                    'type' => 'user',
                    'is_superadmin' => $request->get('is_superadmin',0),
                    'company_id' => $company->id
                ]);

                $subject = 'Account đăng nhập hệ thống JobMatching';
                $body = view('email.user.create',$formData)->render();

                CommonHelper::sendEmail([
                    'to' => $user->email,
                    'subject' => $subject,
                    'body' => $body,
                    'campaign'=> 'REGISTER',
                    'jd' => null,
                    'userId' => $user->id,
                    'uid' => $uid
                ]);
            }

            return api_response()->withItem($user->refresh(), new UserTransformer());

        } catch (Exception $e) {
            \Log::error($e->getTraceAsString());
            return api_response()->errorInternalError($e->getMessage());
        }

    }

    public function createUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "role" => "required",
                "company_id" => "required|exists:company,id",
                "email" => "required|unique:users,email",
                "username" => "required",
                "nickname" => "required",
                "name" => "required",
                "type" => "required"
            ]);

            if($validator->fails()){
                return api_response()->errorUnprocessableEntity($validator->errors());
            }

            $uid = Uuid::generate()->string;

            $formData = [
                "email" => $request->get('email'),
                "user_metadata" => [],
                "blocked" => 'false',
                "email_verified" => 'false',
                "app_metadata" => [],
                //"given_name" => "John",
                //"family_name" => "Doe",
                "name" => $request->get('name'),
                "nickname" => $request->get('nickname'),
                //"picture" => $request->get('picture'),
                "connection" => "Username-Password-Authentication",
                "password" => $request->get('password'),
                "verify_email" => 'false',
                "username" => $request->get('username'),
            ];

            $tokenData = $this->authService->getToken();

            $response = Zttp::withHeaders(['Authorization' => 'Bearer ' . $tokenData['access_token']])
                ->asFormParams()
                ->post(config('auth.domain') . '/api/v2/users', $formData);

            $data = $response->json();

            if (!$response->isOk()) {
                return api_response()->errorUnprocessableEntity("({$data['statusCode']}-{$data['errorCode']}-{$data['error']}) {$data['message']}");
            }

            $data['sub'] = $data['user_id'];

            if ($request->filled('role')) {
                $response = Zttp::withHeaders(['Authorization' => 'Bearer ' . $tokenData['access_token']])
                    ->asJson()
                    ->post(config('auth.domain') . '/api/v2/users/' . $data['user_id'] . '/roles', [
                        'roles' => [
                            $request->get('role')
                        ]
                    ]);
            }


            $user = User::createOrUpdateFromAuth0($data);

            if($user){
                $user->update([
                    'username' => $request->get('username'), 'type' => $request->get('type'), 'is_superadmin' => $request->get('is_superadmin',0), 'company_id' => $request->get('company_id')
                ]);

                $subject = 'Account đăng nhập hệ thống JobMatching';
                $body = view('email.user.create',$formData)->render();

                CommonHelper::sendEmail([
                    'is_send_immediate' => $request->get('is_send_immediate'),
                    'to' => $user->email,
                    'subject' => $subject,
                    'body' => $body,
                    'campaign'=> 'CREATE_USER',
                    'jd' => null,
                    'userId' => $user->id,
                    'uid' => $uid
                ]);
            }

            return api_response()->withItem($user->refresh(), new UserTransformer());

        } catch (Exception $e) {
            \Log::error($e->getTraceAsString());

            return api_response()->errorInternalError($e->getMessage());
        }
    }

    public function getRoles(Request $request)
    {
        $data = $this->authService->getToken();

        $response = Zttp::withHeaders(['Authorization' => 'Bearer '. $data['access_token']])
            ->get(config('auth.domain').'/api/v2/roles', ['name']);

        $data = $response->json();

        if (! $response->isOk()) {
            throw new Exception(print_r($data, true));
        }

        return api_response()->json([
            'data' => $data,
        ]);
    }
}
