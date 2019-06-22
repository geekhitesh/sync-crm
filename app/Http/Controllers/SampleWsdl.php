<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use SoapClient;

class SampleWsdl extends Controller
{
    //

	public function index() {
		$client = new SoapClient("salesforce.wsdl");
		var_dump($client);
	}
}
