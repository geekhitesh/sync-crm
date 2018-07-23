<?php

$total = 0;
$insert_success = 0;
$insert_failed=0;
$update_success=0;
$update_failed=0;
$delete_success=0;
$delete_failed=0;

foreach($sync_process as $process)
{
	$total           += $process->total_records_processed;
	$insert_success  += $process->insert_success_count;
	$insert_failed   += $process->insert_failed_count;
	$update_success  += $process->update_success_count;
	$update_failed   += $process->update_failed_count;
	$delete_success  += $process->delete_success_count;
	$delete_failed   += $process->delete_failed_count;
}

?>

<html>

<head>
  <title>Sync Process - Statistics</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script src="{{ URL::asset('jquery.freezeheader.js') }}"></script>
</head>

    <table id="report_table" class="table table-bordered table-striped">
    	<thead class="alert alert-info">
    		<tr>
    			<td>Sync Id</td>
    			<td>Sync Date</td>
   				<td>Total Records</td>
   				<td>Insert Success</td>
   				<td>Insert Failure</td>
   				<td>Update Success</td>
   				<td>Update Failure</td>
   				<td>Delete Success</td>
   				<td>Delete Failure</td>		
    		</tr>
    	</thead>
	@foreach ($sync_process as $process)
		<tr>
			<td><a href="../report/{{$process->id}}" target="_blank">{{$process->id}}</a></td>
			<td>{{date("d-m-Y",strtotime($process->created_at))}}</td>
			<td>{{$process->total_records_processed}}</td>
			<td>{{$process->insert_success_count}} </td>
			<td>{{$process->insert_failed_count}} </td>
			<td>{{$process->update_success_count}} </td>
			<td>{{$process->update_failed_count}} </td>
			<td>{{$process->delete_success_count}}</td>
			<td>{{$process->delete_failed_count}}</td>
		</tr>
	@endforeach

    	<tfoot class="alert alert-info">
    		<tr>
				<td></td>
				<td></td>
				<td>{{$total}}</td>
				<td>{{$insert_success}} </td>
				<td>{{$insert_failed}} </td>
				<td>{{$update_success}} </td>
				<td>{{$update_failed}} </td>
				<td>{{$delete_success}}</td>
				<td>{{$delete_failed}}</td>	
    		</tr>
    	</tfoot>

	</table>

<script>

    $(document).ready(function () {
        $("#report_table").freezeHeader();
    })
</script>
</html>