<?php

namespace Modules\CVCrawler\Validations;

use App\Base\Validator;

class CVCrawlerValidator extends Validator
{

    /**
     * Get validation rules
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Get validation labels
     *
     * @return array
     */
    public function labels()
    {
        return [

        ];
    }

    /**
     * Custom validate
     */
    protected function customValidate()
    {
        if (!$this->input('jd')) {
            $this->errors()->add('jd', 'JD can not be null.');
            return false;
        }

         if (!$this->input('source')) {
            $this->errors()->add('source', 'Source can not be null.');
            return false;
        }

         if (!$this->input('cv_source_id')) {
            $this->errors()->add('cv_source_id', 'CV id can not be null.');
            return false;
        }

        if (!$this->input('content_html')) {
            $this->errors()->add('content_html', 'Content html can not be null.');
            return false;
        }

    }
}