<?php

namespace LaravelDaily\LaravelCharts\Classes;

class LaravelChart {

    private $options = [];
    private $data = [];

    public function __construct($chart_options)
    {
        $this->options = $chart_options;
        $this->data = $this->prepareData();
    }

    private function prepareData()
    {
        return $this->options['model']::orderBy($this->options['date_field'])->get()
            ->groupBy(function ($entry) {
                if ($entry->created_at instanceof \Carbon\Carbon) {
                    return $entry->{$this->options['date_field']}->format($this->options['group_by']);
                } else {
                    return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $entry->{$this->options['date_field']})->format('Y-m');
                }
            })
            ->map(function ($entries) {
                return $entries->{$this->options['number_function']}($this->options['number_field']);
            });
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