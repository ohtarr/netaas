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
		<th>RESOLVED</th>
		<th>PROCESSED</th>
		<th>INCIDENT_ID</th>
		<th>CREATED_AT</th>
		<th>UPDATED_AT</th>
		<th>DELETED_AT</th>
	</tr>
    @foreach ($states as $state)
		<tr>
			<td>{{ $state->id }}</td>
			<td>{{ $state->name }}</td>
			<td>{{ $state->type }}</td>
			<td>{{ $state->resolved }}</td>
			<td>{{ $state->processed }}</td>
			<td>{{ $state->incident_id }}</td>
			<td>{{ $state->created_at }}</td>
			<td>{{ $state->updated_at }}</td>
			<td>{{ $state->deletedat }}</td>
		</tr>
    @endforeach
	</table>
</div>
</body>
</html>