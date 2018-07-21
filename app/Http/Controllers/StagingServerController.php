<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\StagingServer;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use File;
use DB;
use App\Http\Services;

class StagingServerController extends Controller
{
    private $staging_server;

    public function __construct()
    {
        $this->staging_server = new StagingServer();
        $this->sync_crm_service = new Services\SyncCRMService();
    }

    public function insertProperty(Request $request)
    {
        $new_result = explode("\x0D",$request);
        File::put("dump.txt2",$new_result);
        File::put("dump.txt1",$request);
        File::put("dump.txt3",$new_result[10]);
        $response = $new_result[10];
        $clean_response = str_ireplace(['soapenv:', 'sf:'], '', $response);
        File::put("dump.txt4",$clean_response);
        $clean_response = implode("\n", array_filter(explode("\n", $clean_response)));
        File::put("dump.txt5",$clean_response);
        $xml = simplexml_load_string($clean_response);
        File::put("dump.txt6",json_encode($xml));
        
       $this->staging_server->request_input = json_encode($xml);
       $count = StagingServer::where('request_status','P')->where('request_input',$this->staging_server->request_input)->count();
       $this->staging_server->request_status = 'P';
       if($count <= 0)
       {
           $this->staging_server->save();
       }
       
       //$this->staging_server->save();
       $requests =  StagingServer::all(array('request_id'));    
       File::put("dump.txt7",json_encode($requests));

       $response = $this->dummyResponse();
       //header("Content-type: text/xml;charset=utf-8");
       return response($response)->header('Content-Type', 'text/xml; charset=utf-8');
       //return $response;

    }

    public function syncCRM(Request $request)
    {
       //$records =  StagingServer::all(array('request_id','request_input','request_status'));
       $records =  StagingServer::where('request_status','=','P')->get();

       $effected_rows = $this->sync_crm_service->syncProperties($records); 
   } 


   public function dummyResponse()
   {
        /* $msg = '<?xml version="1.0" encoding="UTF-8"?>'.
                    '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"'.
                    ' xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'.
                        '<soapenv:Body>'.
                          '<element name="notificationsResponse">'.
                              '<Ack>true</Ack>'.
                          '</element>'.
                        '</soapenv:Body>
                    </soapenv:Envelope>'; */


         $msg = '<?xml version="1.0" encoding="UTF-8"?> <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:out="http://soap.sforce.com/2005/09/outbound">'.
                  '<soapenv:Header/>'.
                  '<soapenv:Body>'.
                    '<out:notificationsResponse>'.
                      '<out:Ack>?</out:Ack>'.
                    '</out:notificationsResponse>'.
                  '</soapenv:Body>'.
                '</soapenv:Envelope>';     

        return $msg;
   }

}
