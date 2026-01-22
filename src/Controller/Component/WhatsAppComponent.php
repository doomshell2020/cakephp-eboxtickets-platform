<?php

namespace App\Controller\Component;

use Cake\Controller\Component;

class WhatsAppComponent extends Component
{
    public function whatsappmsg($to = null, $message = null)
    {
        $instanceId = 'instance18306';
        $token = 't7vzx58iaz7tw3qy';
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.ultramsg.com/$instanceId/messages/chat",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "token=$token&to=$to&body=$message&priority=10&referenceId=",
            CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            //   echo "cURL Error #:" . $err; //die;
        } else {
            // echo $response; //die;
        }
    }
}
