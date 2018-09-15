<?php

	$count = 0;
	$result = $data[1];
	$file_name = $data[0];
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
    			<td>S No.</td>
   				<?php 

   				    $total = count($result[0]);

   				    for($i=0 ; $i < $total; $i++)
   				    {
   				    	echo "<td>".$result[0][$i]."</td>";
   				    }


   				?>
    		</tr>
    	</thead>
	<?php
		//var_dump($result);
		for($i=1; $i < count($result); $i++)
		{
			echo "<tr>";
			echo "<td><a href='../edit/".$file_name."/$i'> $i</a></td>";
		    for($j=0;$j < count($result[0]);$j++)
		    {
		    	echo "<td>".$result[$i][$j]."</td>";
		    }
			echo "</tr>";
		}

	?>

	</table>

<script>

    $(document).ready(function () {
        $("#report_table").freezeHeader();
    })
</script>
</html>