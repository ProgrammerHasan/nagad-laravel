<?php

/**
 * Created by PhpStorm
 * User: ProgrammerHasan
 * web:https://mehedihasan.dev
 * web:https://programmerhasan.com
 * Date: 20-02-2021
 * Time: 10:00 PM
 */
namespace App\Services;
use App\Utility\NagadUtility;

class NagadService
{

    /**
     * @var string
     */
    private $nagadHost;

    public function __construct()
    {
         // sandbox
        //$this->nagadHost = "http://sandbox.mynagad.com:10080/";
        // live
        $this->nagadHost = "https://api.mynagad.com/";
    }

    public function getSession()
    {
        $DateTime = Date('YmdHis');
        $MerchantID = 'nagad.merchant_id';
        $invoice_no = 'Inv'.Date('YmdH').rand(1000, 10000);
        $merchantCallbackURL = 'nagad.callback_url';

        $SensitiveData = [
            'merchantId' => $MerchantID,
            'datetime' => $DateTime,
            'orderId' => $invoice_no,
            'challenge' => NagadUtility::generateRandomString()
        ];

        $PostData = array(
            'accountNumber' => 'nagad.merchant_number', //optional
            'dateTime' => $DateTime,
            'sensitiveData' => NagadUtility::EncryptDataWithPublicKey(json_encode($SensitiveData)),
            'signature' => NagadUtility::SignatureGenerate(json_encode($SensitiveData))
        );

        $ur = $this->nagadHost."api/dfs/check-out/initialize/" . $MerchantID . "/" . $invoice_no;
        $resultData = NagadUtility::HttpPostMethod($ur,$PostData);

        if (isset($resultData['sensitiveData']) && isset($resultData['signature'])) {
            if ($resultData['sensitiveData'] != "" && $resultData['signature'] != "") {

                $plainResponse = json_decode(NagadUtility::DecryptDataWithPrivateKey($resultData['sensitiveData']), true);

                if (isset($plainResponse['paymentReferenceId']) && isset($plainResponse['challenge'])) {

                    $paymentReferenceId = $plainResponse['paymentReferenceId'];
                    $randomServer = $plainResponse['challenge'];

                    $SensitiveDataOrder = array(
                        'merchantId' => $MerchantID,
                        'orderId' => $invoice_no,
                        'currencyCode' => '050',
                        'amount' => $this->amount,
                        'challenge' => $randomServer
                    );


                    // $merchantAdditionalInfo = '{"no_of_seat": "1", "Service_Charge":"20"}';
                    if($this->tnx !== ''){
                        $this->merchantAdditionalInfo['tnx_id'] =  $this->tnx;
                    }
                    // echo $merchantAdditionalInfo;
                    // exit();

                    $postDataOrder = array(
                        'sensitiveData' => NagadUtility::EncryptDataWithPublicKey(json_encode($SensitiveDataOrder)),
                        'signature' => NagadUtility::SignatureGenerate(json_encode($SensitiveDataOrder)),
                        'merchantCallbackURL' => $merchantCallbackURL,
                        'additionalMerchantInfo' => (object)$this->merchantAdditionalInfo
                    );

                    // echo json_encode($PostDataOrder);
                    // exit();

                    $OrderSubmitUrl = $this->nagadHost."api/dfs/check-out/complete/" . $paymentReferenceId;
                    $Result_Data_Order = NagadUtility::HttpPostMethod($OrderSubmitUrl, $postDataOrder);
                    try {
                        if ($Result_Data_Order['status'] == "Success") {
                            $url = ($Result_Data_Order['callBackUrl']);
                            return redirect($url);
                            //echo "<script>window.open('$url', '_self')</script>";
                        }
                        else {
                            echo json_encode($Result_Data_Order);
                        }
                    } catch (\Exception $e) {
                        dd($Result_Data_Order);
                    }

                } else {
                    echo json_encode($plainResponse);
                }
            }
        }

    }

    public function verify($requestAll){
        $Query_String = explode("&", explode("?", $_SERVER['REQUEST_URI'])[1]);
        $payment_ref_id = substr($Query_String[2], 15);
        $url = $this->nagadHost."api/dfs/verify/payment/" . $payment_ref_id;
        $json = NagadUtility::HttpGet($url);
        return $json;
    }

}