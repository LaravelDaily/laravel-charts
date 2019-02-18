<?php

namespace LaravelDaily\LaravelCharts\Classes;

use Illuminate\Support\Facades\Validator;

class LaravelChart {

    private $options = [];
    private $data = [];

    const GROUP_PERIODS = [
        'day' => 'Y-m-d',
        'week' => 'Y-W',
        'month' => 'Y-m',
        'year' => 'Y',
    ];

    public function __construct($chart_options)
    {
        $this->options = $chart_options;
        $this->options['chart_name'] = strtolower(str_slug($chart_options['chart_title']));
        $this->data = $this->prepareData();
    }

    private function prepareData()
    {
        $this->validateOptions($this->options);

        try {
            return $this->options['model']::orderBy($this->options['date_field'])->get()
                ->groupBy(function ($entry) {
                    if ($entry->created_at instanceof \Carbon\Carbon) {
                        return $entry->{$this->options['date_field']}
                            ->format(self::GROUP_PERIODS[$this->options['group_by']]);
                    } else {
                        return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',
                            $entry->{$this->options['date_field']})
                            ->format(self::GROUP_PERIODS[$this->options['group_by']]);
                    }
                })
                ->map(function ($entries) {
                    return $entries->{$this->options['number_function']}($this->options['number_field']);
                });
        } catch (\Error $ex) {
            throw new \Exception('Laravel Charts error: ' . $ex->getMessage());
        }
    }

    private function validateOptions(array $options)
    {
        $rules = [
            'model' => 'required|bail',
            'date_field' => 'required|bail',
            'group_by' => 'required|in:day,week,month,year|bail',
            'number_function' => 'required|in:count,sum,avg|bail',
            'number_field' => 'required|bail',
            'chart_type' => 'required|in:line,bar,pie|bail',
            'chart_title' => 'required',
        ];

        $messages = [
            'required' => 'please specify :attribute option',
            'group_by.in' => 'group_by option should contain one of these values - day/week/month/year',
            'number_function.in' => 'number_function option should contain one of these values - count/sum/avg',
            'chart_type.in' => 'chart_type option should contain one of these values - line/bar/pie',
        ];

        $attributes = [
            'date_field' => 'date_field',
            'group_by' => 'group_by',
            'number_function' => 'number_function',
            'number_field' => 'number_field',
            'chart_type' => 'chart_type',
            'chart_title' => 'chart_title',
        ];

        $validator = Validator::make($options, $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw new \Exception('Laravel Charts options validator: ' . $validator->errors()->first());
        }
    }

    public function renderHtml()
    {
        return view('laravelchart::html', ['options' => $this->options]);
    }

    public function renderJs()
    {
        return view('laravelchart::javascript', ['options' => $this->options, 'data' => $this->data]);
    }

}