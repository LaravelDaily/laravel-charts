<?php

namespace LaravelDaily\LaravelCharts\Classes;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LaravelChart
{

    public $options     = [];
    private $datasets   = [];

    /**
     * Group Periods
     */
    const GROUP_PERIODS = [
        'day'   => 'Y-m-d',
        'week'  => 'Y-W',
        'month' => 'Y-m',
        'year'  => 'Y',
    ];

    /**
     * LaravelChart constructor.
     * @param $chart_options
     * @throws \Exception
     */
    public function __construct()
    {
        foreach (func_get_args() as $arg) {
            $this->options = $arg;
            $this->options['chart_name'] = strtolower(Str::slug($arg['chart_title'], '_'));
            $this->datasets[] = $this->prepareData();
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function prepareData()
    {
        $this->validateOptions($this->options);

        try {
            if (!class_exists($this->options['model'])) {
                return [];
            }

            $dataset = [];
            $conditions = $this->options['conditions'] ??
                [['name' => '', 'condition' => "", 'color' => '', 'fill' => '']];

            foreach ($conditions as $condition) {
                if (isset($this->options['top_results']) && !is_int($this->options['top_results'])) {
                    throw new \Exception('Top results value should be integer');
                }

                $query = $this->options['model']::when(isset($this->options['filter_field']), function ($query) {

                    if (isset($this->options['filter_days'])) {
                        return $query->where(
                            $this->options['filter_field'],
                            '>=',
                            now()->subDays($this->options['filter_days'])->format($this->options['date_format_filter_days'] ?? 'Y-m-d')
                        );
                    } else if (isset($this->options['filter_period'])) {
                        switch ($this->options['filter_period']) {
                            case 'week':
                                $start = date('Y-m-d', strtotime('last Monday'));
                                break;
                            case 'month':
                                $start = date('Y-m') . '-01';
                                break;
                            case 'year':
                                $start = date('Y') . '-01-01';
                                break;
                        }
                        if (isset($start)) {
                            return $query->where($this->options['filter_field'], '>=', $start);
                        }
                    }
                    if (isset($this->options['range_date_start']) && isset($this->options['range_date_end'])) {
                        return $query->whereBetween(
                            $this->options['filter_field'],
                            [$this->options['range_date_start'], $this->options['range_date_end']]
                        );
                    }
                });

                if (isset($this->options['where_raw']) && $this->options['where_raw'] != '') {
                    $query->whereRaw($this->options['where_raw']);
                }

                if ($this->options['chart_type'] == 'line' && $condition['condition'] != '') {
                    $query->whereRaw($condition['condition']);
                }

                if ($this->options['report_type'] == 'group_by_relationship') {
                    $query->with($this->options['relationship_name']);
                }

                if (isset($this->options['with_trashed']) && $this->options['with_trashed']) {
                    $query->withTrashed();
                }

                if (isset($this->options['only_trashed']) && $this->options['only_trashed']) {
                    $query->onlyTrashed();
                }

                if (isset($this->options['withoutGlobalScopes']) && $this->options['withoutGlobalScopes']) {
                    $scopesToExclude = is_array($this->options['withoutGlobalScopes'])
                        ? $this->options['withoutGlobalScopes']
                        : null;

                    $collection = $query->withoutGlobalScopes($scopesToExclude)->get();
                } else {
                    $collection = $query->get();
                }

                if ($this->options['report_type'] != 'group_by_relationship') {
                    $collection->where($this->options['group_by_field'], '!=', '');
                }

                if (count($collection)) {
                    $data = $collection
                        ->sortBy($this->options['group_by_field'])
                        ->groupBy(function ($entry) {
                            if ($this->options['report_type'] == 'group_by_string') {
                                return $entry->{$this->options['group_by_field']};
                            } else if ($this->options['report_type'] == 'group_by_relationship') {
                                if ($entry->{$this->options['relationship_name']}) {
                                    return $entry->{$this->options['relationship_name']}->{$this->options['group_by_field']};
                                } else {
                                    return '';
                                }
                            } else if ($entry->{$this->options['group_by_field']} instanceof \Carbon\Carbon) {
                                return $entry->{$this->options['group_by_field']}
                                    ->format($this->options['date_format'] ?? self::GROUP_PERIODS[$this->options['group_by_period']]);
                            } else {
                                if ($entry->{$this->options['group_by_field']} && isset($this->options['group_by_field_format'])) {
                                    return \Carbon\Carbon::createFromFormat(
                                        $this->options['group_by_field_format'],
                                        $entry->{$this->options['group_by_field']}
                                    )
                                        ->format($this->options['date_format'] ?? self::GROUP_PERIODS[$this->options['group_by_period']]);
                                } else if ($entry->{$this->options['group_by_field']}) {
                                    return \Carbon\Carbon::createFromFormat(
                                        'Y-m-d H:i:s',
                                        $entry->{$this->options['group_by_field']}
                                    )
                                        ->format($this->options['date_format'] ?? self::GROUP_PERIODS[$this->options['group_by_period']]);
                                } else {
                                    return '';
                                }
                            }
                        })
                        ->map(function ($entries) {
                            if (isset($this->options['field_distinct'])) {
                                $entries = $entries->unique($this->options['field_distinct']);
                            }
                            $aggregate = $entries->{$this->options['aggregate_function'] ?? 'count'}($this->options['aggregate_field'] ?? '');
                            if (@$this->options['aggregate_transform']) {
                                $aggregate = $this->options['aggregate_transform']($aggregate);
                            }
                            return $aggregate;
                        })
                        ->when(isset($this->options['top_results']), function ($coll) {
                            return $coll
                                ->sortDesc()
                                ->take($this->options['top_results'])
                                ->sortKeys();
                        });
                } else {
                    $data = collect([]);
                }


                if (
                    (isset($this->options['date_format']) || isset($this->options['group_by_period'])) &&
                    isset($this->options['filter_days']) &&
                    @$this->options['show_blank_data']
                ) {
                    $newData = collect([]);
                    $format = $this->options['date_format'] ?? self::GROUP_PERIODS[$this->options['group_by_period']];

                    CarbonPeriod::since(now()->subDays($this->options['filter_days']))
                        ->until(now())
                        ->forEach(function (Carbon $date) use ($data, &$newData, $format) {
                            $key = $date->format($format);
                            $newData->put($key, $data[$key] ?? 0);
                        });

                    $data = $newData;
                }

                if (@$this->options['continuous_time']) {
                    $dates = $data->keys();
                    $interval = $this->options['group_by_period'] ?? 'day';
                    $newArr = [];
                    if (!is_null($dates->first()) or !is_null($dates->last())) {
                        if ($dates->first() === $dates->last()) {
                            $firstDate = Carbon::createFromDate(($dates->first()))->addDays(-14);
                            $lastDate = Carbon::createFromDate(($dates->last()))->addDays(14);
                        }

                        $period = CarbonPeriod::since($firstDate ?? $dates->first())->$interval()->until($lastDate ?? $dates->last())
                            ->filter(function (Carbon $date) use ($data, &$newArr) {
                                $key = $date->format($this->options['date_format'] ?? 'Y-m-d');
                                $newArr[$key] = $data[$key] ?? 0;
                            })
                            ->toArray();
                        $data = $newArr;
                    }
                }

                $dataset = [
                    'name' => $this->options['name'] ?? $this->options['chart_title'], 
                    'color' => $condition['color'], 
                    'chart_color' => $this->options['chart_color'] ?? '', 
                    'fill' => $condition['fill'], 
                    'data' => $data, 
                    'hidden' => $this->options['hidden'] ?? false,
                    'stacked' => $this->options['stacked'] ?? false,
                ];
            }
            
            if(!empty($this->options['labels'])) {
                foreach($this->options['labels'] as $key => $val) {
                    if(array_key_exists($key, $data->toArray())) {
                        $data[$val] = $data[$key];
                        unset($data[$key]);
                    }
                }
            }

            return $dataset;
        } catch (\Error $ex) {
            throw new \Exception('Laravel Charts error: ' . $ex->getMessage());
        }
    }

    /**
     * @param array $options
     * @throws \Exception
     */
    private function validateOptions(array $options)
    {
        $rules = [
            'chart_title'           => 'required',
            'report_type'           => 'required|in:group_by_date,group_by_string,group_by_relationship',
            'model'                 => 'required|bail',
            'group_by_field'        => 'required|bail',
            'group_by_period'       => 'in:day,week,month,year|bail',
            'aggregate_function'    => 'in:count,sum,avg|bail',
            'chart_type'            => 'required|in:line,bar,pie|bail',
            'filter_days'           => 'integer',
            'filter_period'         => 'in:week,month,year',
            'hidden'                => 'boolean',
            'stacked'               => 'boolean',
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
            'chart_title'           => 'chart_title',
            'report_type'           => 'report_type',
            'group_by_field'        => 'group_by_field',
            'group_by_period'       => 'group_by_period',
            'aggregate_function'    => 'aggregate_function',
            'chart_type'            => 'chart_type',
            'filter_days'           => 'filter_days',
            'filter_period'         => 'filter_period',
            'field_distinct'        => 'field_distinct',
            'hidden'                => 'hidden',
            'stacked'               => 'stacked',
        ];

        $validator = Validator::make($options, $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw new \Exception('Laravel Charts options validator: ' . $validator->errors()->first());
        }
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function renderHtml()
    {
        return view('laravelchart::html', ['options' => $this->options]);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function renderJs()
    {
        return view('laravelchart::javascript', ['options' => $this->options, 'datasets' => $this->datasets]);
    }

    /**
     * @return string
     */
    public function renderChartJsLibrary()
    {
        return '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>';
    }
    
    /**
     * @return array
     */
    public function getDatasets() {
        return $this->datasets;
    }
}
