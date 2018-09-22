<?php

  function printRecord($record)
  {
      //echo "<pre>";var_dump($record);echo "</pre>";
      if(isset($record->sObject))
      {
        //echo "Deal done";
        $record = $record->sObject;

        foreach($record as $key => $value)
        {
          echo "<b>$key : </b>$value <br/>";
        }
      }  

      return 1;
  }

?>

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
          <td>Actions</td>
    		</tr>
    	</thead>
	@foreach ($records as $record)
		<tr>
			<td>{{$record->request_id}} </td>
			<?php


				$request = json_decode($record->request_input);
        //var_dump($request);
        if(isset($request->Body->notifications->Notification))
        {
           echo "<td>";
           $content = "";
           if(is_array($request->Body->notifications->Notification))
           {
              foreach ($request->Body->notifications->Notification as $one_record)
              {
                  echo "<br/>";
                  $content .= printRecord($one_record);
              }
           }
           else
           {
              $content .= printRecord($request->Body->notifications->Notification);

           }
				   /*if(isset($request->Body->notifications->Notification->sObject))
            {
                $request = $request->Body->notifications->Notification->sObject;
            }
            else
            {
                $request = $request->Body->notifications->Notification;
            }*/
          //$request = json_encode($request);
          echo "</td>";
        }



				//$request->name = "<a href='https://ap4.salesforce.com/'".$request;
			?>
			<!--<td><pre>{{print_r($request)}}</pre></td>-->
			<td>{{date("d-m-Y h:i:s A",strtotime($record->created_at))}} </td>
			<td>{{$record->request_status}} </td>
			<td style="width: 300px;"><?php 
              echo $record->error_description ;
              if(trim($record->error_description) != '')
              {
                echo "<br/>Detailed Explanation: <br/>".$record->decoded_string;
              }
          ?>
            
      </td>
      <td>
          @if($record->website_property_id !='')
            <a href="https://www.buniyad.com/residential-property/a-a/a/{{$record->website_property_id}}.html" target="_blank">View On Website</a>
          @endif
      </td>
		</tr>
	@endforeach
	</table>
</div>
<script>

  <!--  $(document).ready(function () {
        $("#report_table").freezeHeader();
    })-->
</script>
</html>