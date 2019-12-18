<?php
/**
 * Created by PhpStorm.
 * User: phamduchung
 * Date: 7/5/19
 * Time: 4:41 PM
 */

namespace Modules\Auth\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use League\Fractal\TransformerAbstract;
use Modules\Auth\Models\Company;
use Modules\Auth\Models\User;
use Modules\Auth\Transformers\CompanyTransformer;
use Modules\Auth\Transformers\CustomerTransformer;
use Modules\Auth\Transformers\UserTransformer;
use Modules\Auth\Validations\UserValidation;
use Modules\JD\Models\CreditTransactions;
use Modules\JD\Models\JobDescription;
use Webpatser\Uuid\Uuid;

class CompanyController extends Controller
{
    /**
     * Lấy danh sách công ty
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request){
        $company = Company::query()
            ->withCount('users')
            ->orderBy('company.id','DESC');

        if($request->filled('q')){
            $company->where('name', 'LIKE', '%' . $request->get('q') . '%');
        }


        $company =  $company->paginate($request->input('limit', 5));
        return api_response()->withPaginator($company, new CompanyTransformer);
    }

    /**
     * Xem chi tiết công ty
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id, Request $request){
        $company = Company::query()
            ->whereId($id)
            ->withCount('users')
            ->firstOrFail();

        $jds = JobDescription::query()
            ->select('job_descriptions.*')
            ->with('skills')
            ->with('province')
            ->join('users', 'users.id', '=', 'job_descriptions.user_id')
            ->where('users.company_id', '=', $id)
            ->orderBy('job_descriptions.id','DESC')
            ->limit(5)
            ->get();
        return api_response()->json(array_merge($company->toArray(), [
            'jds' => $jds
        ]));
    }

    /**
     * Lấy danh sách jd thuộc công ty
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function jds($id, Request $request){
        $jds = JobDescription::query()
            ->select('job_descriptions.*')
            ->join('users', 'users.id', '=', 'job_descriptions.user_id')
            ->where('users.company_id', '=', $id)
            ->orderBy('job_descriptions.id','DESC')
            ->paginate($request->input('limit', 5));

        return api_response()->withPaginator($jds, new class extends TransformerAbstract{
            public function transform($jd)
            {
                return $jd->toArray();
            }
        });
    }

    /**
     * Tạo công ty
     */
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'address' => 'required|max:255',
            'description' => 'required',
        ]);

        if($validator->fails()){
            return api_response()->errorUnprocessableEntity($validator->errors());
        }

        $company = new Company();
        $company->name = $request->name;
        $company->address = $request->address;
        $company->description = $request->description;
        $company->status = 1;

        $company->saveOrFail();

        if($request->has('cover')){
            $path = env('APP_ENV') . '/company/cover/' . date('Y-m-d') . '/' . $company->id . '.jpg';
            Storage::disk('s3')->put($path, base64_decode($request->cover));
            $company->cover = $path;
            $company->save();
        }

        if($request->has('logo')){
            $path = env('APP_ENV') . '/company/logo/' . date('Y-m-d') . '/'. $company->id . '.jpg';
            Storage::disk('s3')->put($path, base64_decode($request->logo));
            $company->logo = $path;
            $company->save();
        }

        return api_response()->json($company);
    }

    /**
     * Sửa công ty
     */
    public function edit($id, Request $request){
        $company = Company::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'address' => 'required|max:255',
            'description' => 'required',
        ]);

        if($validator->fails()){
            return api_response()->errorUnprocessableEntity($validator->errors());
        }

        $company->name = $request->name;
        $company->address = $request->address;
        $company->description = $request->description;
        $company->save();

        if($request->has('cover')){
            $path = env('APP_ENV') . '/company/cover/' . date('Y-m-d') . '/' . $company->id . '.jpg';
            Storage::disk('s3')->put($path, base64_decode($request->cover));
            $company->cover = $path;
            $company->save();
        }

        if($request->has('logo')){
            $path = env('APP_ENV') . '/company/logo/' . date('Y-m-d') . '/'. $company->id . '.jpg';
            Storage::disk('s3')->put($path, base64_decode($request->logo));
            $company->logo = $path;
            $company->save();
        }

        return api_response()->json($company);
    }

    /**
     * Sửa credit
     */
    public function editCredit($id, Request $request){
        $company = Company::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer',
            'content' => 'required|max:255'
        ]);

        if($validator->fails()){
            return api_response()->errorUnprocessableEntity($validator->errors());
        }



        $creditTransactions = CreditTransactions::create([
                    'created_by' => \Auth::user()->id,
                    'code' => Uuid::generate()->string,
                    'company_id' => $id,
                    'type' => CreditTransactions::DEPOSIT_TRANSACTION,
                    'content' => $request->input('content'),
                    'before_balance_credit' => $company->credit,
                    'amount' => (int)$request->input('amount', 0),
                    'balance_credit' => intval($request->input('amount', 0)) + intval($company->credit)
        ]);
        $creditTransactions->load(['user' => function($q){
            $q->select('id', 'name');
        },
        'company' => function($q){
            $q->select('id', 'name');
        }]);

        $company->credit = $creditTransactions->balance_credit;
        $company->save();

        return api_response()->json($creditTransactions);
    }

    /**
     * Xóa công ty
     */
    public function destroy($id){
        Company::findOrFail($id)->delete();
        return api_response()->json([]);
    }

    /**
     *
     */
    public function creditTransactions($id, Request $request){
        $creditTransactions = CreditTransactions::query()
            ->with(['company' => function($q){
                $q->select('id', 'name');
            }])
            ->with(['user' => function($q){
                $q->select('id', 'name');
            }])
            ->where('company_id', $id)
            ->orderBy('id','DESC')
            ->paginate($request->input('limit', 5));

        return api_response()->withPaginator($creditTransactions, new class extends TransformerAbstract{
            public function transform($obj)
            {
                return $obj->toArray();
            }
        });
    }
}