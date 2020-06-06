<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Events</title>

    <!-- CSS -->
    <!-- BOOTSTRAP -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <style>
		table {
			font-family: arial, sans-serif;
			border-collapse: collapse;
			width: 100%;
		}

		td, th {
			border: 1px solid #dddddd;
			text-align: left;
			padding: 4px;
		}

		tr:nth-child(even) {
			background-color: #dddddd;
		}
    </style>
</head>
<body class="container">
<div class="col-sm-8 col-sm-offset-2">

Event TOTAL: {{ $events_count }}<br>
Event ALERT: {{ $events_alert }} <br>
Event RESOLVE: {{$events_resolved }} <br>
<br>
State TOTAL: {{ $states_count }}<br>
State Unassigned: {{ $unassignedstates }}<br>

<br>
Incident TOTAL: {{ $incidents }}<br>
Incident SITE:	{{ $siteincidents }}<br>
Incident DEVICE: {{ $deviceincidents }}<br>


</div>
</body>
</html>