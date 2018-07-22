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
use App\SyncProcess;

class StagingServerController extends Controller
{
    private $staging_server;

    public function __construct()
    {
        $this->staging_server = new StagingServer();
       
    }

    public function insertProperty(Request $request)
    {
        $this->sync_crm_service = new Services\SyncCRMService();
        $new_result = explode("\x0D",$request);
        /*File::put("dump.txt2",$new_result);
        File::put("dump.txt1",$request);
        File::put("dump.txt3",$new_result[10]);*/
        $response = $new_result[10];
        $clean_response = str_ireplace(['soapenv:', 'sf:'], '', $response);
        //File::put("dump.txt4",$clean_response);
        $clean_response = implode("\n", array_filter(explode("\n", $clean_response)));
        //File::put("dump.txt5",$clean_response);
        $xml = simplexml_load_string($clean_response);
        //File::put("dump.txt6",json_encode($xml));
        
       $this->staging_server->request_input = json_encode($xml);
       $count = StagingServer::where('request_status','P')->where('request_input',$this->staging_server->request_input)->count();
       $this->staging_server->request_status = 'P';
       if($count <= 0)
       {
           $this->staging_server->save();
       }
       
       //$this->staging_server->save();
       $requests =  StagingServer::all(array('request_id'));    
       //File::put("dump.txt7",json_encode($requests));

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
         $msg = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:out="http://soap.sforce.com/2005/09/outbound">'.
                   '<soapenv:Header/>'.
		      '<soapenv:Body>'.
			'<out:notificationsResponse>'.
			           '<out:Ack>true</out:Ack>'.
			'</out:notificationsResponse>'.
                	'</soapenv:Body>'.
                '</soapenv:Envelope>';
        return $msg;
   }

   public function getStatistics($count)
   {
      $sync_process = SyncProcess::where('total_records_processed','>',0)
                                 ->orderBy('created_at','desc')
                                 ->take($count)
                                 ->get();
      return view('reports.statistics')->with(compact('sync_process'));
   }

   public function getReport($sync_process_id)
   {
      return $sync_process_id;
   }

}
