<?php

namespace Modules\CVCrawler\Command;

use App\Services\Zttp;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\CV\Models\CurriculumVitae;
use Modules\CVCrawler\Models\CVCrawler;
use Modules\JD\Events\JDApproved;
use Modules\JD\Models\JdCv;
use Modules\JD\Models\JobDescription;
use Vinkla\Hashids\Facades\Hashids;

class DynoFeedBack extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'dyno:feedback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'feedback for a cv';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        $jd = JobDescription::whereHas('cvs', function ($query) {
            $query->where('jd_cv.status', 2)
                ->where('jd_cv.is_feedback', 0)
                ->distinct();
        })->first();

        $cv = $jd->cvs()
            ->select('dyno_id', DB::raw('jd_cv.score as score'))
            ->get()
            ->pluck('score', 'dyno_id')
            ->all();

        $formData = [
            "title" => strip_tags($jd->title),
            "description" => strip_tags($jd->description),
            "requirement" => strip_tags($jd->requirement),
            "location" => $jd->province_id,
            "cv_score" => (object) $cv,
            'id_jd' => Hashids::encode($jd->id)
        ];

        $response = Zttp::withHeaders(['Authorization' => config('dyno.api_key')])
            ->asJson()
            ->post(config('dyno.url_cv_score'), $formData);
        if ($response->isOk()) {
            $data = $response->json();

        } else {
            throw new \Exception('Có lỗi xảy ra. Vui lòng thử lại!');
        }
    }
}
