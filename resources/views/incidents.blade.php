<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Incidents</title>

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

	<table>
	<tr>
		<th>ID</th>
		<th>NAME</th>
		<th>TYPE</th>
	</tr>
    @foreach ($incidents as $incident)
		<tr>
			<td>{{ $incident->id }}</td>
			<td>{{ $incident->name }}</td>
			<td>{{ $incident->type }}</td>
		</tr>
    @endforeach
	</table>
</div>
</body>
</html>