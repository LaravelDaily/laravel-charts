<script>
  var ctx = document.getElementById("{{ $options['chart_name'] ?? 'myChart' }}");
  var {{ $options['chart_name'] ?? 'myChart' }} = new Chart(ctx, {
    type: '{{ $options['chart_type'] ?? 'line' }}',
    data: {
      labels: [
          @foreach ($data as $group => $result)
            "{{ $group }}",
          @endforeach
      ],

      datasets: [{
        label: '{{ $options['chart_title'] }}',
        data: [
            @foreach ($data as $group => $result)
            {!! $result !!},
            @endforeach
        ],
        @if ($options['chart_type'] == 'pie')
        backgroundColor: [
            @foreach ($data as $group => $result)
                'rgba({{ rand(0,255) }}, {{ rand(0,255) }}, {{ rand(0,255) }}, 0.2)',
            @endforeach
        ],
        @endif
        borderWidth: 1
      }]
    },
    options: {
      scales: {
        xAxes: [],
        yAxes: [{
          ticks: {
            beginAtZero:true
          }
        }]
      }
    }
  });
</script>
