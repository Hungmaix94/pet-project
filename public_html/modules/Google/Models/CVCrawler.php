<?php

namespace Modules\CVCrawler\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\CV\Models\CurriculumVitae;
use Modules\JD\Models\JobDescription;
use Modules\JD\Models\Skill;

class CVCrawler extends Model
{
    protected $table = 'cv_crawlers';

    protected $fillable = [
        'cv_dyno_id',
        'cv_id',
        'is_scan',
        'content_html',
        'jd_id',
        'source',
        'state_sync',
        'cv_source_id',
        'type'
    ];

    protected $touches = ['cvs'];

    protected $dates = [];

    protected $casts = [
    ];

    const PENDING_STATUS = 0;
    const PROCESSING_STATUS = 1;
    const DONE_STATUS = 2;
    const FAILED_STATUS = 3;

    public function cvs()
    {
        return $this->belongsTo(CurriculumVitae::class, 'cv_id');
    }

    public function scopeFilter($query, Request $request)
    {
        return $query;
    }

}