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
		<th>SYSID</th>
		<th>TYPE</th>
		<th>TITLE</th>
		<th>DESCRIPTION</th>
		<th>LEVEL</th>
		<th>RESOLVED</th>
		<th>CREATED_AT</th>
		<th>UPDATED_AT</th>
		<th>DELETED_AT</th>
	</tr>
    @foreach ($tickets as $ticket)
		<tr>
			<td>{{ $ticket->id }}</td>
			<td>{{ $ticket->sysid }}</td>
			<td>{{ $ticket->type }}</td>
			<td>{{ $ticket->title }}</td>
			<td>{{ $ticket->description }}</td>
			<td>{{ $ticket->level }}</td>
			<td>{{ $ticket->resolved }}</td>
			<td>{{ $ticket->created_at }}</td>
			<td>{{ $ticket->updated_at }}</td>
			<td>{{ $ticket->deleted_at }}</td>
		</tr>
    @endforeach
	</table>
</div>
</body>
</html>