<?php

namespace App\Http\Controllers;

use App\Services\NagadService;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Utility\NagadUtility;
use App\Order;
use App\BusinessSetting;
use App\Seller;
use App\CustomerPackage;
use App\SellerPackage;
use Session;

class NagadController{


    public function session()
    {
        $nagadService = new NagadService;
        $nagadService->getSession();

    }

    public function verify(Request $request){
        $nagadService = new NagadService;
        $json = $nagadService->verify($request->all());
        if(json_decode($json)->status == 'Success'){
            // return your checkout completed page and then order/checkout status update

        }
        //Payment Failed
        return redirect()->route('failed');
    }

}
