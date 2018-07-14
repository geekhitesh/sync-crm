<?php

namespace App\Http\Services;
use File;
use \Cache;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use DB;

Class SyncCRMService{

    private $availability_name;
    private $property_type;
    private $property_sub_type;
    private $city;
    private $area;
    private $request_type;
    private $share_to_website;
    private $city_id;
    private $area_id;
    private $no_of_bedrooms;
    private $no_of_bathrooms;
    private $saleable_area;
    private $property_filter_data;
    private $area_list;
    private $property_type_list;
    private $property_sub_type_list;
    private $city_list;
    private $request_status;
    private $mapped_property_type_sub_type_list;
    private $mapped_city_list;
    private $mapped_area_list;
    private $mapped_property_type_list;
    private $mapped_property_sub_type_list;
    private $stats;
    private $error_description;

    public function __construct()
    {
       $this->postPropertyFilterData();
       $this->mapped_city_list              = json_decode($this->convertCSVToJSON('city_list.csv'),TRUE);
       $this->mapped_area_list              = json_decode($this->convertCSVToJSON('area_list.csv'),TRUE);
       $this->mapped_property_type_list     = json_decode($this->convertCSVToJSON('property_type_list.csv'),TRUE);
       $this->mapped_property_sub_type_list = json_decode($this->convertCSVToJSON('property_sub_type_list.csv'),TRUE);
       $this->mapped_property_type_sub_type_list = json_decode($this->convertCSVToJSON('property_type_sub_type_list.csv'),TRUE);
       //$this->debug($this->mapped_area_list);
       //$this->debug($this->mapped_property_sub_type_list);
       $this->stats = array();
       $this->stats['insert']['success']= 0;
       $this->stats['delete']['success'] = 0;
       $this->stats['update']['success'] = 0;
       $this->stats['insert']['failed']= 0;
       $this->stats['delete']['failed'] = 0;
       $this->stats['update']['failed'] = 0;

    }

    private function postPropertyFilterData()
    {
       $client = new \GuzzleHttp\Client();
       $response = $client->request('GET','http://www.buniyad.com/postPropertyFilterData');
       $this->property_filter_data = json_decode($response->getBody(),true);
       $this->property_type_list = $this->property_filter_data['propertyTypes'];
       $this->property_sub_type_list = $this->property_filter_data['propertySubTypes'];
       $this->city_list = $this->property_filter_data['cities'];
       $this->convertAttributeListToKeys();
       //$this->debug($this->property_type_list);
       //$this->debug($this->city_list);
       //$this->debug($this->property_sub_type_list);
    }

    private function convertAttributeListToKeys()
    {
        $city_array = array();
        $area_array = array();
        $property_type_array = array();
        $property_sub_type_array = array();

        foreach($this->city_list as $city)
        {
          $city_array[$city['cityName']] = $city; 
        }
        
        $this->city_list = $city_array;
        
        foreach($this->property_type_list as $property_type)
        {
            $property_type_array[$property_type['val']] = $property_type;
        } 
        $this->property_type_list= $property_type_array;

        foreach($this->property_sub_type_list as $property_sub_type)
        {
            $property_sub_type_array[$property_sub_type['val']] = $property_sub_type;
        }
        $this->property_sub_type_list= $property_sub_type_array;
    }

    private function convertCSVToJSON($file_name,$skip_header = true)
    {
        $file_name="salesforce/".$file_name;
        $file = fopen($file_name,"r");
        $result = array();
        $count = 0;

        while(! feof($file))
        {
            $row = fgetcsv($file);
            if($count>0  && $skip_header== true)
            {
                $key = $row[0];
                $result[$key] = $row;
            }
            $count++;
        }

        return json_encode($result);
    }

    private function getAreaList($city_id)
    {
       $endpoint = "http://www.buniyad.com/propertyListingLocalitiesData?cityID=".$city_id;
       $client = new \GuzzleHttp\Client();
       $response = $client->request('GET',$endpoint);
       $area_list = json_decode($response->getBody(),true);
       $area_list = $area_list['localities'];
       $area_array =array();
       //$this->area_list[$city_id] = json_decode($response->getBody(),true);
       //$this->debug($area_list);
       foreach ($area_list as $area)
       {
          $area_array[str_ireplace("Sector ","",$area['areaName'])] = $area;  

       }
       $this->area_list[$city_id] = $area_array;
       //$this->debug($this->area_list);
    }

    function is_assoc($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public function syncProperties($records)
    {
        //$this->debug($this->mapped_city_list);
        foreach($records as $record)
        {
            $decoded_string = "";
            $this->request_status = "C";
            $this->error_description = "";
            //echo $record->request_input."<br/>";
            $request = json_decode($record->request_input,TRUE); 
            $associative_array = $this->is_assoc($request['Body']['notifications']['Notification']);
            if(isset($request['Body']['notifications']['Notification']))
            {
                if($associative_array != 1 )
                {
                   $requests = $request['Body']['notifications']['Notification'];
                   foreach ($requests as $request)
                   {
                      //print_r($request);
                      $request =  $request['sObject']; 

                      /******************************************************
                      *   Call this function to parse the request.
                      *   Extract the Property Ffields from original request.
                      *******************************************************/

                      $this->parseRequest($request);



                      /******************************************************
                      *   Call this function to map values of salesforce fields 
                      *   to the R-Square Fields. This is the most important function. 
                      *******************************************************/

                      $decoded_string .=  $this->attributeMapper();

                      
                      /******************************************************
                      *   On the basis of transaction / request type,  
                      *   call the corresponding function. 
                      *******************************************************/

                      if($this->request_type = "INSERT")
                      {
                          $this->insertProperty();
                      }
                      else if($this->request_type = "UPDATE")
                      {
                         $this->updateProperty();
                      }
                   }
                }
                else
                {
                    $request = $request['Body']['notifications']['Notification']['sObject'];
                    $this->parseRequest($request);
                    $decoded_string .= $this->attributeMapper();
                    if($this->request_type == "INSERT")
                    {
                        $this->insertProperty();
                    }
                    else if($this->request_type == "UPDATE")
                    {
                        $this->updateProperty();
                    }

                }

            }
           $record->decoded_string = $decoded_string;
           $record->request_status = $this->request_status;
           $record->error_description = $this->error_description;
           $record->save(); 
        }         
        $this->debug($this->stats);
    }

    private function parseRequest($request)
    {

       $this->availability_name = $request['Name'];
       $this->city = $request['RealtyForce__City__c'];
       if(isset($request['RealtyForce__Location__c']))
       { 
           $this->area = $request['RealtyForce__Location__c']; 
       }
       else
       {
           $this->area = "";
       }

       $this->share_to_website = $request['Share_To_Buniyad_Website__c'];

       if(isset($request['RealtyForce__Picklist__c']))
       {
           $this->property_type = $request['RealtyForce__Picklist__c'];
       }
       else
       {
           $this->property_type = "";
       }

       if(isset($request['RealtyForce__Category_Type__c']))
       {
           $this->property_sub_type = $request['RealtyForce__Category_Type__c'];
       }
       else
       {
           $this->property_sub_type = "";
       }

       if(isset($request['RealtyForce__No_Of_Bedrooms__c']))
       {
           $this->no_of_bedrooms = $request['RealtyForce__No_Of_Bedrooms__c'];
       } 
       else
       {
           $this->no_of_bedrooms = 0;
       } 

       if(isset($request['RealtyForce__Covered_Area__c']))
       {
           $this->saleable_area = $request['RealtyForce__Covered_Area__c'];
       }
       else
       {
           $this->saleable_area = 0;
       }
          
       $records = DB::select('select count(1) as total_count from v_rr_property where comments = ?',[$this->availability_name]); 
       if($records[0]->total_count <=0)
       {
           $this->request_type = 'INSERT';
       }
       else
       {
           $this->request_type = 'UPDATE';
       }
    }
  
    private function attributeMapper()
    {
      $echo_string = "";
      $status = true;
       if(isset($this->mapped_city_list[$this->city]))
       {

          $this->city = $this->mapped_city_list[$this->city][1]; /* [1] value will always correspond to r-square city name  */ 
          if($this->city != "NA")
          {
            $this->city_id = $this->city_list[$this->city]['cityID'];  
            //echo $this->city_id."<br/>"; 
            //$this->getAreaList($this->city_id);
            //$this->area = str_replace("Sector","",$this->area);

            if(! isset($this->area_list[$this->city_id]))
              {
                   $this->getAreaList($this->city_id);
                   //print_r($this->area_list);
            }

            if (isset($this->mapped_area_list[$this->area][1]))
            {
                $this->area = $this->mapped_area_list[$this->area][1];
            
                if($this->area != "NA")
                {
                   $this->area_id = $this->area_list[$this->city_id][$this->area]['areaID'];
                }
                else
                {
                    $this->error_description .= "File Mapping: Area <".$this->area_id."> not found in R-Square<br/>";
                    $status = false;
                }
                $echo_string .= "<br/>City Name:".$this->city."; City Id:".$this->city_id."; Area Name:".$this->area. "; Area Id:".$this->area_id;
            }
            else if(isset($this->area_list[$this->city_id][$this->area]['areaName']))
            {
              $this->debug("Area not found in mapping file but is directly found in r-square");
              $this->area_id = $this->area_list[$this->city_id][$this->area]['areaID'];
              $echo_string .= "<br/>City Name:".$this->city."; City Id:".$this->city_id."; Area Name:".$this->area. "; Area Id:".$this->area_id;
            }
            else
            {
                $this->debug("City:".$this->city_id);
                //$this->debug("City: ".$this->area_list[$this->city_id]);
                //$this->debug($this->area_list);
                //$this->debug("Area: ".$this->area);
                $status = false;
                $this->error_description .= "Direct Mapping: Area <".$this->area."> not found in R-Square.<br/>";
            }    
          }
          else
          {
             $status = false;
             $this->error_description .= "File Mapping: City <".$this->city."> not found in R-Square.<br/>";
          }

          /***********************************************************************************
          *   We will always derive property type on the basis of property sub type.
          *   If Property Sub type received from salesforce is empty
          *   Then, Property type should be checked in mapped_property_type_sub_type_list
          *   ELSE Property sub tyoe should be checked in mapped_property_type_sub_type_list 
          *************************************************************************************
          *   Important to note: Property sub type will never come empty from salesforce.
          *   This is just for precaution purpose. Always Else part should get hit.
          *************************************************************************************
          * Property sub type will be the base to find the property subtype as well.
          * i.e if property_sub_type = 'Farmhouse' then property_type = 'residential'
          * This mapping is present in property_type_sub_type_list.csv file
          *************************************************************************************/

          if($this->property_sub_type == "")
          {
               
              if(isset($this->mapped_property_type_sub_type_list[$this->property_type][1]))
              {
                  $this->property_type = $this->mapped_property_type_sub_type_list[$this->property_type][1];
              }
              else
              {
                  $status = false;
                   $this->error_description .= "File Mapping: Property Type <".$this->property_type."> not found in R-Square.<br/>";
              }
              
              if(isset($this->mapped_property_type_sub_type_list[$this->property_type][2]))
              {
                  $this->property_sub_type = $this->mapped_property_type_sub_type_list[$this->property_type][2];
              }
              else
              {
                  $status = false;
                   $this->error_description .= "File Mapping: Property Sub Type <".$this->property_sub_type."> not found in R-Square.<br/>";
              }

          }
          else
          {
              if(isset($this->mapped_property_type_sub_type_list[$this->property_sub_type][1]))
              {
                  $this->property_type = $this->mapped_property_type_sub_type_list[$this->property_sub_type][1]; 
              }
              else
              {
                  $status = false;
                   $this->error_description .= "File Mapping: Property Type <".$this->property_type."> not found in R-Square.<br/>";
              }
              if(isset($this->mapped_property_type_sub_type_list[$this->property_sub_type][2]))
              {
                  $this->property_sub_type = $this->mapped_property_type_sub_type_list[$this->property_sub_type][2];
              }
              else
              {
                  $status = false;
                  $this->error_description .= "File Mapping: Property Sub Type <".$this->property_sub_type."> not found in R-Square.<br/>";
              }
          }

          //$this->property_type = $this->mapped_property_type_list[$this->property_type][1];
          $echo_string .= "; Property Type:".$this->property_type;
          $echo_string .= "; Property Sub Type:".$this->property_sub_type; 
          if(isset($this->property_type_list[$this->property_type]['rowID']))
          {
             $this->property_type = $this->property_type_list[$this->property_type]['rowID'];
          }
          else
          {
              $status = false;
          }
          $echo_string .= "; Property Type ID:".$this->property_type;
          //$this->property_sub_type = $this->mapped_property_sub_type_list[$this->property_sub_type][1]; 
          if(isset($this->property_sub_type_list[$this->property_sub_type]['rowID']))
          {
             $this->property_sub_type = $this->property_sub_type_list[$this->property_sub_type]['rowID'];
          }
          else
          {
              $status = false;
          }

          $echo_string .= "; Property Sub Type ID:".$this->property_sub_type;
          $echo_string .= "; Avaiability Id:".$this->availability_name;
          $echo_string .= "; Share To Website:".$this->share_to_website;
          $echo_string .= "; Request Type:".$this->request_type;
          //$this->stats['insert']['success']++;
       }
       else
       {
          $status = false;
       }
        if($status==false)
        {
            $echo_string .= "; Parsing Status: Failed";
            $this->stats[strtolower($this->request_type)]['failed']++;
            $this->request_status = 'E';
            $this->debug($this->error_description);
        }
        else
        {
             $echo_string .= "; Parsing Status: Passed";
            
        }
       $this->debug("<hr/>".$echo_string); 
       return $echo_string;       
    }

 
    private function insertProperty()
    {
        $website_id = '201502061022_WS_1';
        $client = new \GuzzleHttp\Client(); 
        //$query_String = "name=Salesforce Admin&email=crmsupport@buniyad.com&phone=9927701230&txnType=Buy&type=residential&subType=Apartment&city=385818457719445047&area=467876444116787557&comments=Property-1234";
        $endpoint       = "http://www.buniyad.com/postPropertyData?".
                        "txnType=Buy".
                        "&type=".$this->property_type.
                        "&subType=".$this->property_sub_type.
                        "&city=".$this->city_id.
                        "&area=".$this->area_id.
                        "&size=".$this->saleable_area.
                        "&bedrooms=".$this->no_of_bedrooms. 
                        "&comments=".$this->availability_name.
                        "&website_id=".$website_id.
                        "&phone=9927701230".
                        "&email=crm@buniyad.com"; 

    /***** Commenting the code to push data in r-square until completion of testing of area ********/

    /*  $response = $client->request('GET',$endpoint);
        $result = $response->getBody(); */
        $result = "success";
        if($result == "success")
        {
           $this->stats['insert']['success']++;
           if($this->share_to_website == true)
           {
               $this->shareToWebsite('Yes',$this->availability_name);
           }
           else
           {
               $this->shareToWebsite('No',$this->availability_name);
           }
        }
        else
        {
           $this->stats['insert']['failed']++;
        } 
       // echo "<br/>$endpoint"; 
    }

    private function updateProperty()
    {
        $this->stats['update']['success']++;
    }

    private function shareToWebsite($share,$availability_name)
    {
        $result = DB::update("update v_rr_property set push_to_website=? where Comments like 'P-%' and Comments=?",[$share,$availability_name]);
    } 


    public function debug($val)
    {
        if( env('DEBUG'))
        {
          echo "<br/>";
          if(is_array($val))
            var_dump($val);
          else
            echo "<br/>$val";

        }
    }  

} 
