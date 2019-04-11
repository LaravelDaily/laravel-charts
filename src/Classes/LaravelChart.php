<?php

namespace LaravelDaily\LaravelCharts\Classes;

use Illuminate\Support\Facades\Validator;

class LaravelChart {

    public $options = [];
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
        $this->options['chart_name'] = strtolower(str_slug($chart_options['chart_title'], '_'));
        $this->data = $this->prepareData();
    }

    private function prepareData()
    {
        $this->validateOptions($this->options);

        try {
            return $this->options['model']::orderBy($this->options['group_by_field'])
                ->when(isset($this->options['filter_field']), function($query) {
                    if (isset($this->options['filter_days'])) {
                        return $query->where($this->options['filter_field'], '>=',
                            now()->subDays($this->options['filter_days'])->format('Y-m-d'));
                    } else if (isset($this->options['filter_period'])) {
                        switch ($this->options['filter_period']) {
                            case 'week': $start = date('Y-m-d', strtotime('last Monday')); break;
                            case 'month': $start = date('Y-m') . '-01'; break;
                            case 'year': $start = date('Y') . '-01-01'; break;
                        }
                        if (isset($start)) {
                            return $query->where($this->options['filter_field'], '>=', $start);
                        }
                    }
                })
                ->whereNotNull($this->options['group_by_field'])
                ->get()
                ->groupBy(function ($entry) {
                    if ($this->options['report_type'] == 'group_by_string') {
                        return $entry->{$this->options['group_by_field']};
                    }
                    else if ($entry->{$this->options['group_by_field']} instanceof \Carbon\Carbon) {
                        return $entry->{$this->options['group_by_field']}
                            ->format(self::GROUP_PERIODS[$this->options['group_by_period']]);
                    } else {
                        return \Carbon\Carbon::createFromFormat($this->options['group_by_field_format'] ?? 'Y-m-d H:i:s',
                            $entry->{$this->options['group_by_field']})
                            ->format(self::GROUP_PERIODS[$this->options['group_by_period']]);
                    }
                })
                ->map(function ($entries) {
                    return $entries->{$this->options['aggregate_function'] ?? 'count'}($this->options['aggregate_field'] ?? '');
                });
        } catch (\Error $ex) {
            throw new \Exception('Laravel Charts error: ' . $ex->getMessage());
        }
    }

    private function validateOptions(array $options)
    {
        $rules = [
            'chart_title' => 'required',
            'report_type' => 'required|in:group_by_date,group_by_string',
            'model' => 'required|bail',
            'group_by_field' => 'required|bail',
            'group_by_period' => 'in:day,week,month,year|bail',
            'aggregate_function' => 'in:count,sum,avg|bail',
            'chart_type' => 'required|in:line,bar,pie|bail',
            'filter_days' => 'integer',
            'filter_period' => 'in:week,month,year',
        ];

        $messages = [
            'required' => 'please specify :attribute option',
            'report_type.in' => 'report_type option should contain one of these values - group_by_date/group_by_string',
            'group_by_period.in' => 'group_by option should contain one of these values - day/week/month/year',
            'aggregate_function.in' => 'number_function option should contain one of these values - count/sum/avg',
            'chart_type.in' => 'chart_type option should contain one of these values - line/bar/pie',
            'filter_period.in' => 'filter_period option should contain one of these values - week/month/year',
        ];

        $attributes = [
            'chart_title' => 'chart_title',
            'report_type' => 'report_type',
            'group_by_field' => 'group_by_field',
            'group_by_period' => 'group_by_period',
            'aggregate_function' => 'aggregate_function',
            'chart_type' => 'chart_type',
            'filter_days' => 'filter_days',
            'filter_period' => 'filter_period',
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

    public function renderChartJsLibrary()
    {
        return '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>';
    }

}
