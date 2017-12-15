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
<div class="col-sm-8 col-sm-offset-0">

	<table>
	<tr>
		<th>ID</th>
		<th>NAME</th>
		<th>TYPE</th>
		<th>RESOLVED</th>
		<th>TICKET</th>
		<th>CALLED_ONCALL</th>
		<th>CALLED_SUP</th>
		<th>LAST_OPENED</th>
		<th>CREATED_AT</th>
		<th>UPDATED_AT</th>
		<th>DELETED_AT</th>
	</tr>
    @foreach ($incidents as $incident)
		<tr>
			<td>{{ $incident->id }}</td>
			<td>{{ $incident->name }}</td>
			<td>{{ $incident->type }}</td>
			<td>{{ $incident->resolved }}</td>
			<td>{{ $incident->ticket }}</td>
			<td>{{ $incident->called_oncall }}</td>
			<td>{{ $incident->called_sup }}</td>
			<td>{{ $incident->last_opened }}</td>
			<td>{{ $incident->created_at }}</td>
			<td>{{ $incident->updated_at }}</td>
			<td>{{ $incident->deleted_at }}</td>
		</tr>
    @endforeach
	</table>
</div>
</body>
</html>