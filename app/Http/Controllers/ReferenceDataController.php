<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use File;
use Storage;

class ReferenceDataController extends Controller
{
    //

    private $file_list = array();



    public function __construct()
    {
    	$this->file_list = $this->getFileList();
    }

    public function printFileList()
    {
    	$file_list = $this->file_list ;
    	return view('reference_data.file_list')->with(compact('file_list'));

    }

    public function viewFile($file_name,$skip_header=true)
    {
    	$data[0]= $file_name;
    	$file_name = $file_name.".csv";
    	$file_name=public_path()."/salesforce/".$file_name;
        $file = fopen($file_name,"r");
        $result = array();
        $count = 1;

        while(! feof($file))
        {
            $row = fgetcsv($file);
            if($count>0  && $skip_header== true)
            {
                $key = $row[0];
                //$result[$count] = $row;

                array_push($result,$row);
            }
            $count++;
        }

        $data[1] = $result;

        return view('reference_data.file_view')->with(compact('data'));
    }

    public function editFile($file_name,$row_id)
    {
    	echo "<form action='../../save' method='POST'>";
    	echo "<input type='hidden' name='file_name' value=$file_name />";
    	$file_name = $file_name.".csv";
    	$file_name=public_path()."/salesforce/".$file_name;
        $file = fopen($file_name,"r");
        $result = array();
        $new_file = array();

        while(! feof($file))
        {
            $row = fgetcsv($file);
            array_push($result,$row);
        }
        
        
         echo "<input type='hidden' name='row_id' value=$row_id />";
         echo "<input type='hidden' name='_token' value=".csrf_token().">";
        for($i=0 ; $i < count($result); $i++)
        {
        	if($i==$row_id)
        	{
        		for($j=0;$j < count($result[$i]); $j++)
        		{
        			echo "<input type='text' name=field_$j value='".$result[$i][$j]."''>";
        		}
        		//return $result[$i];
        	}
        }

        echo "<input type='submit'/>";
        echo "</form>";



       // return 0;

    }

    public function saveFile(Request $request)
    {
    	//echo count($_POST);
    	$updated_record = array();
    	$file_name = $request->input('file_name');
    	$row_id = $request->input('row_id');
    	$new_array = array();

    	foreach($_POST as $key => $value)
    	{
    		if(strncmp($key,'field_',6) == 0)
    		{
    			//echo "$key";
    			array_push($updated_record,$value);
    		}
    	}

    	$file_name = $file_name.".csv";
    	$file_name=public_path()."/salesforce/".$file_name;
        $file = fopen($file_name,"r");
        $result = array();
        $new_array = array();

        while(! feof($file))
        {
            $row = fgetcsv($file);
            if(is_array($row) && count($row) >1)
            	array_push($result,$row);
        }

        for($i=0 ; $i < count($result); $i++)
        {
        	if($i==$row_id)
        	{
        		//do nothing
        	}
        	else
        	{
        		array_push($new_array, $result[$i]);
        	}
        }

        fclose($file);
        rename($file_name, $file_name."_old");

        array_push($new_array, $updated_record); 

		$file = fopen($file_name,"w");
		foreach ($new_array as $line)
		{
		  fputcsv($file,$line);
		}
		fclose($file);

    	var_dump( $new_array);
    }



    private function getFileList()
    {
    	$file_list = array();
    	$file_list[0] = 'area_list';
    	$file_list[1] = 'bedroom_list';
    	$file_list[2] = 'city_list';
    	$file_list[3] = 'facing_list';
    	$file_list[4] = 'floor_list';
    	$file_list[5] = 'property_type_list';
    	$file_list[6] = 'property_sub_type_list';
    	$file_list[7] = 'property_type_sub_typelist';

    	return $file_list;
    }


}
