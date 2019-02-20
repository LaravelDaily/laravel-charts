## Laravel Charts

Package to generate Chart.js charts directly from Laravel/Blade, without interacting with JavaScript.

__Notice__: Package is in early development, generating simple cases, so it's likely that syntax/parameters will change with future versions, use with cautions.

---

## Simple Usage

![Laravel Charts - Users by Months](https://laraveldaily.com/wp-content/uploads/2019/02/Screen-Shot-2019-02-18-at-2.37.09-PM.png)

If you want to generate a chart above, grouping __users__ records by the month of __created_at__ value, here's the code.

__Controller__:

```
use LaravelDaily\LaravelCharts\Classes\LaravelChart;

// ...

$chart_options = [
    'chart_title' => 'Users by months',
    'report_type' => 'group_by_date',
    'model' => 'App\User',
    'group_by_field' => 'created_at',
    'group_by_period' => 'month',
    'chart_type' => 'bar',
];
$chart1 = new LaravelChart($chart_options);

return view('home', compact('chart1'));
```

__View File__

```
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">

                    <h1>{{ $chart1->options['chart_title'] }}</h1>
                    {!! $chart1->renderHtml() !!}

                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
{!! $chart1->renderChartJsLibrary() !!}
{!! $chart1->renderJs() !!}
@endsection

```

---

## Installation

```
composer require laraveldaily/laravel-charts
```

No additional configuration or other parameters yet.

---

## Usage

You need to create `LaravelChart` object in your Controller, passing array of options.

```
$chart = new LaravelChart($options);
```

Then pass it to the View, as a variable:

```
return view('dashboard', compact('chart'));
```

---

## Available Reports and Options

Currently package support two types of charts/reports: 

- `group_by_date` - amount of records from the same table, grouped by time period - day/week/month/year;
- `group_by_string` - amount of records from the same table, grouped by any string field, like `name`

__Example with all options__

```
$chart_options = [
    'chart_title' => 'Transactions by dates',
    'chart_type' => 'line',
    'report_type' => 'group_by_date',
    'model' => 'App\Transaction',

    'group_by_field' => 'transaction_date',
    'group_by_period' => 'day',

    'aggregate_function' => 'sum',
    'aggregate_field' => 'amount',
    
    'filter_field' => 'transaction_date',
    'filter_days' => 30, // show only transactions for last 30 days
    'filter_period' => 'week', // show only transactions for this week
];
```

- `chart_title` (required) - just a text title that will be shown as legend;
- `chart_type` (required) - possible values: "line", "bar", "pie";
- `report_type` (required) - see above, can be `group_by_date` or `group_by_string`;
- `model` (required) - name of Eloquent model, where to take the data from;
- `group_by_field` (required) - name of database field that will be user in `group_by` clause;
- `group_by_period` (optional, only for `group_by_date` report type) - possible values are "day", "week", "month", "year";
- `aggregate_function` (optional) - you can view not only amount of records, but also their `SUM()` or `AVG()`. Possible values: "count" (default), "avg", "sum".
- `aggregate_field` (optional) - see `aggregate_function` above, the name of the field to use in `SUM()` or `AVG()` functions. Irrelevant for `COUNT()`.
- `filter_field` (optional) - show only data filtered by that datetime field (see below)
- `filter_days` (optional) - see `filter_field` above - show only last `filter_days` days of that field. Example, last __30__ days by `created_at` field.
- `filter_period` (optional) - another way to filter by field, show only record from last __week__ / __month__ / __year__. Possible values are "week", "month", "year".

__Notice__: currently package is in early version, so these parameters may change in the future.

---

## Rendering Charts in Blade

After you passed `$chart` variable, into Blade, you can render it, by doing __three__ actions:

__Action 1. Render HTML__. 

Wherever in your Blade, call this:

```
{!! $chart1->renderHtml() !!}
```

It will generate something like this:

```
<canvas id="myChart"></canvas>
```

__Action 2. Render JavaScript Library__

Package is using Chart.js library, so we need to initialize it somewhere in scripts section:

```
{!! $chart1->renderChartJsLibrary() !!}
```

It will generate something like this:

```
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
```

__Action 3. Render JavaScript of Specific Chart__

After Chart.js is loaded, launch this:

```
{!! $chart1->renderJs() !!}
```

---

## Using Multiple Charts

You can show multiple charts on the same page, initialize them separately.

__Controller__:

```
public function index()
{
    $chart_options = [
        'chart_title' => 'Users by months',
        'report_type' => 'group_by_date',
        'model' => 'App\User',
        'group_by_field' => 'created_at',
        'group_by_period' => 'month',
        'chart_type' => 'bar',
        'filter_field' => 'created_at',
        'filter_days' => 30, // show only last 30 days
    ];

    $chart1 = new LaravelChart($chart_options);


    $chart_options = [
        'chart_title' => 'Users by names',
        'report_type' => 'group_by_string',
        'model' => 'App\User',
        'group_by_field' => 'name',
        'chart_type' => 'pie',
        'filter_field' => 'created_at',
        'filter_period' => 'month', // show users only registered this month
    ];

    $chart2 = new LaravelChart($chart_options);

    $chart_options = [
        'chart_title' => 'Transactions by dates',
        'report_type' => 'group_by_date',
        'model' => 'App\Transaction',
        'group_by_field' => 'transaction_date',
        'group_by_period' => 'day',
        'aggregate_function' => 'sum',
        'aggregate_field' => 'amount',
        'chart_type' => 'line',
    ];

    $chart3 = new LaravelChart($chart_options);

    return view('home', compact('chart1', 'chart2', 'chart3'));
}
```

__View__:

```
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">

                    <h1>{{ $chart1->options['chart_title'] }}</h1>
                    {!! $chart1->renderHtml() !!}

                <hr />

                    <h1>{{ $chart2->options['chart_title'] }}</h1>
                    {!! $chart2->renderHtml() !!}

                    <hr />

                    <h1>{{ $chart3->options['chart_title'] }}</h1>
                    {!! $chart3->renderHtml() !!}

                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
{!! $chart1->renderChartJsLibrary() !!}

{!! $chart1->renderJs() !!}
{!! $chart2->renderJs() !!}
{!! $chart3->renderJs() !!}
@endsection
```

![Laravel Charts - Users by Months](https://laraveldaily.com/wp-content/uploads/2019/02/Screen-Shot-2019-02-18-at-2.37.09-PM.png)

![Laravel Charts - Users by Names](https://laraveldaily.com/wp-content/uploads/2019/02/Screen-Shot-2019-02-18-at-2.36.50-PM.png)

![Laravel Charts - Transactions by Dates](https://laraveldaily.com/wp-content/uploads/2019/02/Screen-Shot-2019-02-18-at-2.37.27-PM.png)

---

## License
The MIT License (MIT). Please see [License File](license.md) for more information.

---

## More from our LaravelDaily Team

- Read our [Daily Blog with Laravel Tutorials](https://laraveldaily.com)
- FREE E-book: [50 Laravel Quick Tips (and counting)](https://laraveldaily.com/free-e-book-40-laravel-quick-tips-and-counting/)
- Check out our adminpanel generator QuickAdminPanel: [Laravel version](https://quickadminpanel.com) and [Vue.js version](https://vue.quickadminpanel.com)
- Subscribe to our [YouTube channel Laravel Business](https://www.youtube.com/channel/UCTuplgOBi6tJIlesIboymGA)
- Enroll in our [Laravel Online Courses](https://laraveldaily.teachable.com/)