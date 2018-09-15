<?php

namespace App\Http\Services;
use File;
use \Cache;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use DB;
use AreaUnit;
use App\SyncProcess;

Class SyncCRMService{

    private $availability_name;

    private $property_type;
    private $property_sub_type;
    private $property_type_id;
    private $property_sub_type_id;

    private $request_type;
    private $share_to_website;

    private $city;
    private $area;
    private $city_id;
    private $area_id;

    private $no_of_bedrooms;
    private $no_of_bathrooms;
    private $saleable_area;
    private $saleable_area_in_sqft;
    private $property_filter_data;
    private $floor;
    private $facing;

    private $area_list;
    private $property_type_list;
    private $property_sub_type_list;
    private $city_list;


    private $mapped_property_type_sub_type_list;
    private $mapped_city_list;
    private $mapped_area_list;
    private $mapped_property_type_list;
    private $mapped_property_sub_type_list;
    private $mapped_floor_list;
    private $mapped_facing_list;

    private $stats;
    private $error_description;
    private $price;
    private $saleable_area_unit;
    private $sync_process;
    private $request_status;
    private $availability_status;
    private $website_property_id;
    private $property_status;
    private $transaction_type;


  
    public function __construct()
    {
       $this->postPropertyFilterData();
       $this->mapped_city_list                    = json_decode($this->convertCSVToJSON('city_list.csv'),TRUE);
       $this->mapped_area_list                    = json_decode($this->convertCSVToJSON('area_list.csv'),TRUE);
       $this->mapped_area_unit_list               = json_decode($this->convertCSVToJSON('area_unit_list.csv'),TRUE);
       $this->mapped_property_type_list           = json_decode($this->convertCSVToJSON('property_type_list.csv'),TRUE);
       $this->mapped_property_sub_type_list       = json_decode($this->convertCSVToJSON('property_sub_type_list.csv'),TRUE);
       $this->mapped_property_type_sub_type_list  = json_decode($this->convertCSVToJSON('property_type_sub_type_list.csv'),TRUE);
       $this->mapped_floor_list                   = json_decode($this->convertCSVToJSON('floor_list.csv'),TRUE);
       $this->mapped_facing_list                  = json_decode($this->convertCSVToJSON('facing_list.csv'),TRUE);
       //$this->debug($this->mapped_area_list);
       //$this->debug($this->mapped_property_sub_type_list);
       $this->stats = array();
       $this->stats['insert']['success']= 0;
       $this->stats['delete']['success'] = 0;
       $this->stats['update']['success'] = 0;
       $this->stats['insert']['failed']= 0;
       $this->stats['delete']['failed'] = 0;
       $this->stats['update']['failed'] = 0;

       $this->sync_process = new SyncProcess();

       $this->sync_process->save();

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
        $file_name=public_path()."/salesforce/".$file_name;
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

    private function is_assoc($arr)
    {
        if(is_array($arr))
          return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public function syncProperties($records)
    {
        //sleep(20);
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
                      $this->debug($request);
                      $this->parseRequest($request);

                      /******************************************************
                      *   Call this function to validate the request.
                      *
                      *******************************************************/
                      $this->validateRequest();

                      /******************************************************
                      *   Call this function to map values of salesforce fields 
                      *   to the R-Square Fields. This is the most important function. 
                      *******************************************************/


                      $decoded_string .=  $this->attributeMapper();

                      
                      /******************************************************
                      *   On the basis of transaction / request type,  
                      *   call the corresponding function. 
                      *******************************************************/

                      if($this->request_type == "INSERT" && $this->request_status <> 'E')
                      {
                          $this->insertProperty();
                      }
                      else if($this->request_type == "UPDATE" && $this->request_status <> 'E')
                      {
                         $this->updateProperty();
                      }
                   }
                }
                else
                {
                    $request = $request['Body']['notifications']['Notification']['sObject'];
                    $this->debug($request);
                    $this->parseRequest($request);
                    $decoded_string .= $this->attributeMapper();

                    if(env('EXEC_MODE')=='test')
                    {
                       $this->request_type= "INSERT";
                    }
                    
                    $this->validateRequest();
                    if($this->request_type == "INSERT" && $this->request_status <> 'E')
                    {
                        $this->insertProperty();
                    }
                    else if($this->request_type == "UPDATE" && $this->request_status <> 'E')
                    {
                        $this->updateProperty();
                    }

                }

                if($this->request_status =='E')
                {
                   $this->debug($this->error_description);
                }
            }

            $record->decoded_string = $decoded_string;
            $record->request_status = $this->request_status;
            $record->error_description = $this->error_description;
            $record->sync_process_id = $this->sync_process->id;
            if($this->share_to_website == 'Yes')
            {
              $record->website_property_id = $this->website_property_id;
            }
            else
            {
               $record->website_property_id  = '';
            }
           $record->save(); 
        }         
        //$this->debug($this->stats);
        $this->displayStatistics();
    }

    private function parseRequest($request)
    {
       $this->saleable_area = 0;
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



        //RealtyForce__Transaction_Type__c

       if(isset($request['RealtyForce__Transaction_Type__c']))
       {
           $this->transaction_type = $request['RealtyForce__Transaction_Type__c'];
       }
       else
       {
           $this->transaction_type = "";
       }

       if(isset($request['RealtyForce__Status__c']))
       {
           $this->property_status = $request['RealtyForce__Status__c'];
       }
       else
       {
           $this->property_status = "";
       }

        if($this->share_to_website == "true" && $this->property_status =='Active') 
        {
            $this->share_to_website = 'Yes';
        }
        else 
        {
            $this->share_to_website = 'No';
        }       

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

       if(isset($request['RealtyForce__Plot_Area__c']))
       {
           $this->saleable_area = $request['RealtyForce__Plot_Area__c'];
       }

       if(isset($request['RealtyForce__Covered_Area__c']))
       {
           
           $this->saleable_area = $request['RealtyForce__Covered_Area__c'];
       }


       if(isset($request['RealtyForce__Max_Covered_Area__c']))
       {
           $this->saleable_area = $request['RealtyForce__Max_Covered_Area__c'];
       }

       if(isset($request['RealtyForce__Min_Covered_Area__c']))
       {
           $this->saleable_area = $request['RealtyForce__Min_Covered_Area__c'];
       }  

        //convert saleable area in .0
        $this->saleable_area = intval($this->saleable_area);

       if(isset($request['RealtyForce__Budget_Price__c']))
       {
           $this->price = $request['RealtyForce__Budget_Price__c'];
       }
       else
       {
           $this->price = 0;
       }

       if(isset($request['RealtyForce__Floor1__c']))
       {
           $this->floor = $request['RealtyForce__Floor1__c'];
       }
       else
       {
          $this->floor= 0;
       }

       if(isset($request['RealtyForce__Facing__c']))
       {
           $this->facing = $request['RealtyForce__Facing__c'];
       }
       else
       {
          $this->facing= '';
       }       

       if(isset($request['RealtyForce__Project_Area__c']))
       {
           $this->saleable_area_unit = $request['RealtyForce__Project_Area__c'];
       }
       else
       {
           $this->saleable_area_unit = '';
       }  

       //RealtyForce__Availability_Status__c

       if(isset($request['RealtyForce__Availability_Status__c']))
       {
           $this->availability_status = $request['RealtyForce__Availability_Status__c'];
       }
       else
       {
           $this->availability_status = 'Pending';
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
          $city = $this->city; // preserve the value of salesforce city before overriding it.
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
                $area = $this->area; // preserve salesforce-area before overriding it.
                $this->area = $this->mapped_area_list[$this->area][1];
            
                if($this->area != "NA")
                {
                   $this->area_id = $this->area_list[$this->city_id][$this->area]['areaID'];
                }
                else
                {
                    $this->error_description .= "File Mapping: Area <".$area."> not found in R-Square<br/>";
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
             $this->error_description .= "File Mapping: City <".$city."> not found in R-Square.<br/>";
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
                  $property_type = $this->property_type; // preserve value of salesforce-property_type before overriding it.
                  $this->property_type = $this->mapped_property_type_sub_type_list[$this->property_sub_type][1];
                  if($this->property_type=="NA")
                  {
                    $this->error_description .= "File Mapping: Property Type <".$property_type."> not found in R-Square.<br/>";
                  } 
              }
              else
              {
                  $status = false;
                   $this->error_description .= "File Mapping: Property Type <".$this->property_type."> not found in R-Square.<br/>";
              }
              if(isset($this->mapped_property_type_sub_type_list[$this->property_sub_type][2]))
              {
                  $property_sub_type = $this->property_sub_type; // preserve value of salesforce-property_sub_type before overriding it.
                  $this->property_sub_type = $this->mapped_property_type_sub_type_list[$this->property_sub_type][2];
                  if($this->property_sub_type=="NA")
                  {
                    $this->error_description .= "File Mapping: Property Sub Type <".$property_sub_type."> not found in R-Square.<br/>";
                  } 
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
             $this->property_type_id = $this->property_type_list[$this->property_type]['rowID'];
          }
          else
          {
              $status = false;
          }
          $echo_string .= "; Property Type ID:".$this->property_type_id;
          //$this->property_sub_type = $this->mapped_property_sub_type_list[$this->property_sub_type][1]; 
          if(isset($this->property_sub_type_list[$this->property_sub_type]['rowID']))
          {
             $this->property_sub_type_id = $this->property_sub_type_list[$this->property_sub_type]['rowID'];
          }
          else
          {
              $status = false;
          }


          if(isset($this->mapped_area_unit_list[$this->saleable_area_unit]))
          {
              $this->saleable_area_unit = $this->mapped_area_unit_list[$this->saleable_area_unit][1];
              $this->saleable_area_in_sqft = AreaUnit::convertToSqft($this->saleable_area_unit,$this->saleable_area);
              $echo_string .= ";Area in salesforce: ".$this->saleable_area." ".$this->saleable_area_unit." and Converted Area: ".$this->saleable_area_in_sqft." Sq Ft";
          }
          else
          {
              $status = false;
              $this->error_description .= "File Mapping: Area Unit <".$this->saleable_area_unit."> not found in R-Square.<br/>";        
          }

          if(isset($this->mapped_floor_list[$this->floor]))
          {
             $this->floor = $this->mapped_floor_list[$this->floor][1];
          }
          else
          {
              if(trim($this->floor) != 0)
              {
                  $status = false;
                  $this->error_description .= "Floor <".$this->floor."> not found in R-Square.Property is created in R-Square.<br/>";   
              }   
          }

          if(isset($this->mapped_facing_list[$this->facing]))
          {
             $this->facing = $this->mapped_facing_list[$this->facing][1];
          }
          else
          {
              if(trim($this->facing) != '')
              {
                $status = false;
                 $this->error_description .= "File Mapping: Facing <".$this->facing."> not found in R-Square.<br/>";      
              }
             
          }          

         /* if($this->share_to_website == "true" && $this->property_status =='Active') {
              $this->share_to_website = 'Yes';
          }
          else {
              $this->share_to_website = 'No';
          } */



          $echo_string .= "; Property Sub Type ID:".$this->property_sub_type_id;
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
        $website_id = env('WEBSITE_ID');
        $client = new \GuzzleHttp\Client(); 
        //$query_String = "name=Salesforce Admin&email=crmsupport@buniyad.com&phone=9927701230&txnType=Buy&type=residential&subType=Apartment&city=385818457719445047&area=467876444116787557&comments=Property-1234";
        $endpoint       = "http://www.buniyad.com/postPropertyData?".
                        "txnType=Buy".
                        "&type=".$this->property_type_id.
                        "&subType=".$this->property_sub_type_id.
                        "&city=".$this->city_id.
                        "&area=".$this->area_id.
                        "&size=".$this->saleable_area.
                        "&bedrooms=".$this->no_of_bedrooms. 
                        "&comments=".$this->availability_name.
                        "&price=".$this->price.
                        "&website_id=".$website_id.
                        "&phone=9927701230".
                        "&email=crm@buniyad.com"; 

    /***** Commenting the code to push data in r-square until completion of testing of area ********/
        if(env('EXEC_MODE')=='test')
        {
            $result = "success";
        }
        else
        {
          $response = $client->request('GET',$endpoint);
          $result = $response->getBody();
        }
        if($result == "success")
        {
           $this->stats['insert']['success']++;
          /* if($this->share_to_website == true)
           {
               $this->shareToWebsite('Yes',$this->availability_name);
           }
           else
           {
               $this->shareToWebsite('No',$this->availability_name);
           } */

           $this->debug('Insert Sucessful: Performing Post Insert');
           $this->postInsertProcessing();
        }
        else
        {
           $this->stats['insert']['failed']++;
        } 
       // echo "<br/>$endpoint"; 
    }

    private function updateProperty()
    {

        $native_total_area = $this->saleable_area;
        $total_area = $this->saleable_area_in_sqft;
        $abr_nta = $this->saleable_area_unit;
        $unit_of_nta = AreaUnit::unitFormula($this->saleable_area_unit);

        $vrr_property_details = $this->getVRRPropertyDetails($this->availability_name);
        $property_name = $this->getPropertyName($vrr_property_details);
        $prp_website_title = $this->getPropertyTitle($vrr_property_details);
        $share_to_website = $this->share_to_website;
        $this->website_property_id = $vrr_property_details->row_id;


        /****************************************************************************
        * We are not updating all the fields as of now. Following fields are updated
        * 1) Property Size
        * 2) Property Price
        * 3) Share To Website
        * 4) Floor
        * 5) Facing
        * 6) No Of Bedrooms
        *
        ****************************************************************************
        * Future Enhancements
        * Add Property Content directly
        *****************************************************************************/

        $result = DB::update("UPDATE v_rr_property 
                              SET    push_to_website=?,
                                     total_area=?,
                                     native_total_area = ?,
                                     unit_of_nta = ?,
                                     abr_nta = ?,
                                     property_name = ?,
                                     prp_website_title = ?,
                                     cost =?,
                                     floor = ?,
                                     facing = ?,
                                     no_of_bedroom = ?
                              WHERE  Comments like 'P-%' and Comments=?",[$share_to_website,
                                                                          $total_area,
                                                                          $native_total_area,
                                                                          $unit_of_nta,
                                                                          $abr_nta,
                                                                          $property_name,
                                                                          $prp_website_title,
                                                                          $this->price,
                                                                          $this->floor,
                                                                          $this->facing,
                                                                          $this->no_of_bedrooms,
                                                                          $this->availability_name]
                            );

        $this->stats['update']['success']++;
    }

    private function shareToWebsite($share,$availability_name)
    {
        $result = DB::update("update v_rr_property set push_to_website=? where Comments like 'P-%' and Comments=?",[$share,$availability_name]);
    } 

    /**********************************************************************
    *This function handles the tasks needs to be done after insertion of 
    * property in R-Square.
    * 1) Update Amenities
    * 2) Update Area
    * 3) Update Price
    * 4) 
    ***********************************************************************/
    private function postInsertProcessing()
    {
        $native_total_area = $this->saleable_area;
        $abr_nta = $this->saleable_area_unit;
        $unit_of_nta = AreaUnit::unitFormula($this->saleable_area_unit);
        $vrr_property_details = $this->getVRRPropertyDetails($this->availability_name);
        $property_name = $this->getPropertyName($vrr_property_details);
        $prp_website_title = $this->getPropertyTitle($vrr_property_details);
        $share_to_website = $this->share_to_website;
        $floor = $this->floor;
        $facing = $this->facing;
        $this->website_property_id = $vrr_property_details->row_id;


        $result = DB::update("UPDATE v_rr_property 
                              SET    push_to_website=?,
                                     native_total_area = ?,
                                     unit_of_nta = ?,
                                     abr_nta = ?,
                                     property_name = ?,
                                     prp_website_title = ?,
                                     floor = ?,
                                     facing = ?
                              WHERE  Comments like 'P-%' and Comments=?",[$share_to_website,
                                                                          $native_total_area,
                                                                          $unit_of_nta,
                                                                          $abr_nta,
                                                                          $property_name,
                                                                          $prp_website_title,
                                                                          $floor,
                                                                          $facing,
                                                                          $this->availability_name]
                            );
    }


    public function debug($val)
    {
        if( env('DEBUG'))
        {
          echo "<br/>";
          if(is_array($val))
          {
              echo "<pre>";
              print_r($val);
              echo "</pre>";
          }
          else if(is_object($val))
          {
              echo "<pre>";
              var_dump($val);
              echo "</pre>";
          }
          else
            echo "<br/>$val";
        }
    }  


    private function getPropertyName($property_record)
    {
      // $property_name = "Office Space 1474.0 Sq Ft"
      //"Industrial Building/Factory 550.0 Sq Mt";

      //$property_name = $this->property_sub_type." ".$this->saleable_area;
     // $this->debug($property_record);
      $property_name = $property_record->property_name;
      $total_area = $property_record->native_total_area;
      $price_to_replace = $this->readablePrice($property_record->cost);
      $price_replace_with = $this->readablePrice($this->price);
      $current_abr_nta = $property_record->abr_nta;
      $property_name = str_replace($total_area, $this->saleable_area, $property_name);
      $property_name = str_replace($current_abr_nta, $this->saleable_area_unit, $property_name);

      return $property_name;
    }

    private function getPropertyTitle($property_record)
    {
      //Office Space of 1474.0 Sq Ft.  for sale, INR 1.77 Cr at Central Noida, Noida
      //Industrial Building/Factory of 550.0 Sq Mt.  at Surajpur Site 4, Greater Noida
      //$property_title = $this->property_sub_type." of ".$this->saleable_area." at ".$this->area.", ".$this->city;
      $total_area = $property_record->native_total_area;
      $property_title = $property_record->prp_website_title;
      $property_title = str_replace($total_area, $this->saleable_area, $property_title);
      $current_abr_nta = $property_record->abr_nta;
      $property_title = str_replace($current_abr_nta, $this->saleable_area_unit, $property_title);
      $price_to_replace = $this->readablePrice($property_record->cost);
      $price_replace_with = $this->readablePrice($this->price);
      $property_title = str_replace($price_to_replace, $price_replace_with, $property_title);

      return $property_title;
    }


    private function readablePrice($price)
    {

          if($price >=1000 && $price <= 99999)
          {
            $revised_price = $price / 1000;
            return "$revised_price Th";
          }
          else if($price >=100000 && $price <= 9999999)
          {
            $revised_price = $price / 100000;
            return "$revised_price Lac";
          }
          else if($price >= 10000000)
          {
            $revised_price = $price / 10000000;
            return "$revised_price Cr";
          }
    }

    private function getVRRPropertyDetails()
    {
     
        $records = DB::select('select row_id,abr_nta,native_total_area,total_area,property_name,prp_website_title,cost from v_rr_property where comments = ?',[$this->availability_name]); 
        return $records[0];
    }

    private function displayStatistics()
    {
      echo "<hr/>*************************************************Statistics of Sync Process***********************************************************************<hr/>";
      echo "<br/><b>Insert Successful:</b>".$this->stats['insert']['success'];
      echo "<br/><b>Insert Failed:</b>".$this->stats['insert']['failed'];
      echo "<br/><b>Update Successful:</b>".$this->stats['update']['success'];
      echo "<br/><b>Update Failed:</b>".$this->stats['update']['failed'];
      echo "<br/><b>Delete Successful:</b>".$this->stats['delete']['success'];
      echo "<br/><b>Delete Failed:</b>".$this->stats['delete']['failed'];
      $total_records = $this->stats['insert']['success'] + 
                       $this->stats['insert']['failed'] + 
                       $this->stats['update']['success'] +
                       $this->stats['update']['failed'] +
                       $this->stats['delete']['success'] +
                       $this->stats['delete']['failed'];
      echo "<br/><b>Total Records Processed:</b> ".$total_records;                 
      echo "<hr/>";


      $this->sync_process->insert_success_count = $this->stats['insert']['success'];
      $this->sync_process->update_success_count = $this->stats['update']['success'];
      $this->sync_process->delete_success_count = $this->stats['delete']['success'];
      $this->sync_process->delete_failed_count = $this->stats['delete']['failed'];
      $this->sync_process->update_failed_count = $this->stats['update']['failed'];
      $this->sync_process->insert_failed_count = $this->stats['insert']['failed'];
      $this->sync_process->total_records_processed = $total_records;

      $this->sync_process->save();
    }

    private function validateRequest()
    {
        // TODO

      if($this->availability_status == 'Pending')
      {
        $this->request_status= 'E'; //Error out since availability is pending.
        $this->error_description .='Availability '.$this->availability_name." is not in Approved Status.";
        //$this->stats[strtolower($this->request_type)]['failed']++;
      }

      if($this->property_status != 'Active' && $this->request_type == 'INSERT')
      {
        $this->request_status = 'E';
        $this->error_description .='Availability '.$this->availability_name." is not in Active Status.";
        //$this->stats[strtolower($this->request_type)]['failed']++;
      }

      //transaction_type
      if($this->transaction_type != 'Sale')
      {
        $this->request_status = 'E';
        $this->error_description .='Availability '.$this->availability_name." is not for sale. Rent and Rented-Out Property will be considered in next phase.";
        //$this->stats[strtolower($this->request_type)]['failed']++;
      }

      if($this->request_status == 'E)
      {
        $this->stats[strtolower($this->request_type)]['failed']++;
      }

    }

} 
