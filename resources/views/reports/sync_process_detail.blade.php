<html>

<head>
  <title> Sync Process  - Report</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script src="{{ URL::asset('jquery.freezeheader.js') }}"></script>
</head>

<div class="container-fluid">
    <table id="report_table" class="table table-condensed table-bordered table-striped">
    	<thead class="alert alert-info">
    		<tr>
    			<td>Request Id</td>
    			<td>Request Details</td>
   				<td>Sync Date</td>
   				<td>Request Status</td>
   				<td>Error</td>	
    		</tr>
    	</thead>
	@foreach ($records as $record)
		<tr>
			<td>{{$record->request_id}} </td>
			<?php
				$request = json_decode($record->request_input);
				$request = $request->Body->notifications->Notification;
				//$request->name = "<a href='https://ap4.salesforce.com/'".$request;
			?>
			<td><pre>{{print_r($request)}} </pre></td>
			<td>{{date("d-m-Y",strtotime($record->created_at))}} </td>
			<td>{{$record->request_status}} </td>
			<td><?php echo $record->error_description ?></td>
		</tr>
	@endforeach
	</table>
</div>
<script>

    $(document).ready(function () {
        $("#report_table").freezeHeader();
    })
</script>
</html>