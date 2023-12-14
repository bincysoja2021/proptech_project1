<?php
use Illuminate\Support\Facades\DB;

if(!function_exists('verify_email'))
{
    function verify_email($email_id,$otp,$subject,$name,$blade,$details)
    {
        try
        {
            
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://reviewtreasures.com/mail_send/index.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
            'mailer' => 'smtp',
            'mail_host' => 'smtp.gmail.com',
            'mail_port'=>587,
            'user_name'=>'online@naseemalrabeeh.com', //Mail USer Name
            'password'=>'ghbpigjiunnjrgzw',  //MAIl APP Key
            'mail_encription'=>'tls',  //ENcription SSL,TLS
            'from_address'=>'online@naseemalrabeeh.com',
            'from_name'=>'Promptech',
            'to_name'=>$name,
            'to_address'=>$email_id,
            'subject'=>$subject,
            'html_content'=>View($blade, compact('details'))->render(),
        
            ),
            ));
             
            $response = curl_exec($curl);
            $err      = curl_error($curl);

            curl_close($curl);
            if ($err) {
              return response()->json(['message'=>$err,'success' => 'error','data'=>[],'statusCode'=>401], 401);
            } else {

            return $response;
            }
        }
        catch (\Exception $e)
        {
           $msg=$e->getMessage();
           return response()->json(['message'=>$msg,'success' => 'error','data'=>[],'statusCode'=>401], 401);
        }

    }
}



?>