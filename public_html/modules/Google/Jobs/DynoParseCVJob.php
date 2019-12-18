<?php

namespace Modules\CVCrawler\Jobs;

use App\Base\Job;
use App\Services\Zttp;
use Illuminate\Support\Facades\Log;
use Modules\CVCrawler\Models\CVCrawler;

class DynoParseCVJob extends Job
{
    protected $cvCrawler;

    public function __construct(CVCrawler $cvCrawler)
    {
        $this->cvCrawler = $cvCrawler;
    }

    public function handle()
    {
        $formData = [
            'source_id' => strip_tags($this->cvCrawler->cv_source_id),
            'source' => 'ihr',
            "html" => $this->cvCrawler->content_html,
            "site" => strip_tags($this->cvCrawler->source),
        ];
        \Log::info('DynoParseCVJob',[$formData]);

        $response = Zttp::withHeaders(['Authorization' => config('dyno.api_key')])->asFormParams()
            ->post(config('dyno.url_import'), $formData);
        if ($response->isOk()) {
            $data = $response->json();
            \Log::info('DynoParseCVJob data',[$data]);
            $this->cvCrawler->update([
                'cv_dyno_id' => $data['id'],
                'state_sync' => CVCrawler::PROCESSING_STATUS,
            ]);

        } else {
            throw new \Exception('Có lỗi xảy ra. Vui lòng thử lại!');
        }
    }
}