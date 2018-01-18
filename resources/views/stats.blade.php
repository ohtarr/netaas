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

Event TOTAL: {{ $events->count()}}<br>
Event ALERT: {{ $events->where("resolved",0)->count()}} <br>
Event RESOLVE: {{$events->where("resolved",1)->count()}} <br>
<br>
State TOTAL: {{ $states->count()}}<br>
State Unassigned: {{ $unassignedstates->count()}}<br>

<br>
Incident TOTAL: {{ $incidents->count()}}<br>
Incident SITE:	{{ $siteincidents->count()}}<br>
Incident DEVICE: {{ $deviceincidents->count()}}<br>


</div>
</body>
</html>