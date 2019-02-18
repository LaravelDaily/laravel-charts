<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
<script>
  var ctx = document.getElementById("{{ $options['chart_name'] ?? 'myChart' }}");
  var myChart = new Chart(ctx, {
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
