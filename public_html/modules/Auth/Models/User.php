<?php

namespace Modules\Auth\Models;

use App\Base\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\UnauthorizedException;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Auth\Authenticatable;
use Modules\CV\Models\CurriculumVitae;
use Modules\JD\Models\JdShare;
use Modules\JD\Models\JobDescription;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableInterface;
use Tymon\JWTAuth\Facades\JWTAuth;

class User extends Model implements JWTSubject, AuthenticatableInterface
{
    use Authorizable, Authenticatable;

    protected $fillable = [
        'name', 'phone', 'username', 'email', 'picture', 'type', 'auth0_id', 'remember_token', 'access_token', 'permissions', 'company_id','freshchat_data','user_settings'
    ];

    protected $casts = [
        'permissions' => 'array',
        'user_settings' => 'json',

    ];

    const USER_TYPE = 'user';
    const REVIEW_TYPE = 'review';
    const APPROVE_TYPE = 'approve';

    /*
     * Get user by token sharing
     */
    public static function userSharing()
    {
        $authorization = request()->header('Authorization');

        if(empty($authorization)){
            throw new UnauthorizedHttpException('jwt-auth', 'Token not provided');
        }

        if (!preg_match('/Bearer\s(\S+)/', $authorization, $matches)) {
            throw new UnauthorizedHttpException('jwt-auth', 'Token not provided');
        }

        $token_sharing = TokenSharing::where('access_token_sharing', $matches[1])->firstOrFail();

        $user = User::findOrFail($token_sharing->user_id);

        $user->token_sharing = $token_sharing;

        return $user;
    }

    /*
     * Get user by email sharing
     */
    public static function userBonus()
    {
        $user = \Auth::user();
        $user->token_sharing = TokenSharing::where('email', $user->email)->first();

        return $user;
    }

    public function jobDescriptions()
    {
        return $this->hasMany(JobDescription::class);
    }


    public function resumes()
    {
        return $this->hasManyThrough(
            CurriculumVitae::class, JobDescription::class, 'user_id', 'jd_id');
    }

    /*
     * User được assign những jd nào
     */
    public function jdAssign()
    {
        return $this->belongsToMany(JobDescription::class, 'jd_handler', 'assign_id', 'jd_id');
    }

    public function jdShares()
    {
        return $this->belongsToMany(JobDescription::class, 'jd_shares', 'user_id', 'jd_id')
            ->withTimestamps()
            ->withPivot('role');
    }

    public function token_sharing()
    {
        return $this->hasMany(TokenSharing::class, 'user_id','id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id')->withCount('users');
    }

    public static function createOrUpdateFromAuth0($userInfo)
    {
        return User::updateOrCreate(
            ['auth0_id' => $userInfo['sub']],
            Arr::only($userInfo, ['name', 'email', 'picture', 'access_token', 'permissions', 'username'])
        );
    }

    public function isSuperAdmin()
    {
        return $this->is_superadmin == 1;
    }

    public function isReviewer()
    {
        return $this->type == self::REVIEW_TYPE;
    }

    public function isApprover()
    {
        return $this->type == self::APPROVE_TYPE;
    }

    public function isReview()
    {
        return $this->isSuperAdmin() || ($this->type == 'review');
    }

    public function hasPermission($permission)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissions);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            //
        ];
    }
}
