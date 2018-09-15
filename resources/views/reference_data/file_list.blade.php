<?php

$count=0;

?>


<html>

<head>
  <title>File List</title>
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
    			<td>S No.</td>
    			<td>File Name</td>	
    		</tr>
    	</thead>
	@foreach ($file_list as $file_record)
		<tr>
			<td><?php echo ++$count; ?> </td>
			<td><a href="../file/view/{{$file_record}}" target="_blank">{{$file_record}}</a></td>
		</tr>
	@endforeach
	</table>

<script>

    $(document).ready(function () {
        $("#report_table").freezeHeader();
    })
</script>
</html>