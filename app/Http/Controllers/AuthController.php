<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Validator;
use Hash;
use DB;
use DateTime;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
        public $successStatus = 200;

        public function __construct() {
            date_default_timezone_set('Asia/Kolkata');

        }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
        public function login(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required'
            ]);
            if ($validator->fails()) {
                $errors  = json_decode($validator->errors());
                $email   =isset($errors->email[0])? $errors->email[0] : '';
                $password=isset($errors->password[0])? $errors->password[0] : '';

                if($email){
                  $msg = $email;
                }else if($password){
                  $msg = $password;
                }
                
                return response()->json(['message' =>$msg,'data'=>[],'statusCode'=>422,'success'=>'error'],422);
            }
            $checkexist=User::where('email', '=', $request->email)->exists();
            $verify=User::where('email', '=', $request->email)->where('verify_at',1)->exists();
            if($checkexist==false)
            {
              return response()->json(['message' => "User not found.",'success' => 'error','statusCode' => 401,'data'=>[]], $this-> successStatus);
            }
            else
            {
                if (! $token = auth()->attempt($validator->validated())) {
                    return response()->json(['message' => "Incorrect Password.",'success' => 'error','statusCode' => 401,'data'=>[]], $this-> successStatus);
                }
            }
            if (! $token = auth()->attempt($validator->validated())) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            if($verify==false)
            {
                return response()->json(['message' => "please verify the otp.",'success' => 'error','statusCode' => 401,'data'=>[]], $this-> successStatus);
            }
            else
            {
                return response()->json(['message'=>"User logged in successfully", 'statusCode' => $this-> successStatus,'data'=> $this->createNewToken($token),'success' => 'success'], $this-> successStatus); 
            }
            

        }
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
        public function register(Request $req)
        {
            $validator = Validator::make($req->all(), [ 
                    'name'    => 'required',
                    'email'   => 'required|email|unique:users|regex:/(.+)@(.+)\.(.+)/i',
                    'password' => 'required|min:6'
                ]);

                if ($validator->fails()) { 
                    $errors  = json_decode($validator->errors());
                    $name   =isset($errors->name[0])? $errors->name[0] : '';
                    $email  =isset($errors->email[0])? $errors->email[0] : '';
                    $password=isset($errors->password[0])? $errors->password[0] : '';

                    if($name){
                        $msg = $name;
                    }else if($email){
                        $msg = $email;
                    }else if($password){
                        $msg = $password;
                    }
                    return response()->json(['message'=>$msg,'success' => 'error','statusCode'=>401,'data'=>[]], $this-> successStatus);
                 }
                $otp      = random_int(100000, 999999);
                $register       = new User();
                $register->name = $req->name;
                $register->email= $req->email;
                $register->password = Hash::make($req->password);
                $register->otp=$otp;
                $register->save();
                $blade='email.verify_otp'; 
                $details = [
                  'type' => 'otp_verification',
                  'email' => $req->email,
                  'otp' => $otp,
                  'subject' => 'OTP verification',
                  'name'=>$req->name
                ];
                verify_email($req->email,$otp,$subject="Verify an otp",$req->name, $blade,$details);
            
                return response()->json(['message'=>"New user registerd successfully. Please verify your otp send it via an email.", 'statusCode' => $this-> successStatus,'data'=>$register, 'otp'=>"",'success' => 'success'], $this-> successStatus); 
        }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
        public function logout() {
            auth()->logout();
            return response()->json(['message'=>"User successfully signed out", 'statusCode' => $this-> successStatus,'success' => 'success'], $this-> successStatus); 
        }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
        public function refresh() {
            return $this->createNewToken(auth()->refresh());
        }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
        public function userProfile(Request $req)
        {
           if(auth()->user())
            {
                $token        = $req->bearerToken();                
                $id       = Auth::user()->id;
                $responseData = User::where('id',$id)->first();  
                $message      ="Result fetched  successfully";
                return response()->json(['message'=>$message, 'statusCode' => $this-> successStatus,'data'=>$responseData,'success' => 'success'], $this-> successStatus);         
            }
            return response()->json(['message'=>"User does't exist", 'statusCode' => 400,'success' => 'error'], $this-> successStatus);
        }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
        protected function createNewToken($token)
        {
            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'user' => auth()->user()
            ]);
        }

         public function verifyOtp(Request $request)
        {
            $validator = Validator::make($request->all(), [
            'email'=>'required|email|regex:/(.+)@(.+)\.(.+)/i', 
            'otp' => 'required',
            ]);

            if ($validator->fails()) { 
                $errors = json_decode($validator->errors());
                $otp    =isset($errors->otp[0])? $errors->otp[0] : '';
                $email=isset($errors->email[0])? $errors->email[0] : '';

                if($email){
                  $msg = $email;
                }else if($otp){
                  $msg = $otp;
                }
                return response()->json(['message'=>$msg,'success' => 'error','data'=>[],'statusCode'=>401], $this-> successStatus);
            }

            $enteredOtp   = $request->input('otp');
            $checkexist   = DB::table('users')->where('otp', $enteredOtp)->where('email',$request->email)->exists();
            $time         = User::where('otp',$enteredOtp)->orWhere('email',$request->email)->first();
            $send         = isset($time->created_at) ? $time->created_at : '';
            $start        = new DateTime($send);

            $current_time = new DateTime();
            $diff_time    = $start->modify('+10 minutes');
            // dd($diff_time);

            if($checkexist==true)
            {
                if($diff_time > $current_time)
                {                                       
                    User::where('otp', $enteredOtp)->update(['verify_at' => 1]);
                    return response()->json(['message'=>"An otp verified by successfully.", 'statusCode' => $this-> successStatus,'data'=>"", 'success' => 'success'], $this-> successStatus); 
                }
                else
                {
                     $message="OTP has expired";
                   
                     return response()->json(['message'=>$message,'success' => 'error','data'=>[],'statusCode'=>401], $this-> successStatus); 

                }
            }
            else{
                $error="Incorrect otp";
                return response()->json(['message'=>$error,'success' => 'error','data'=>[],'statusCode'=>401],$this-> successStatus);
            }
        }

        public function update_user(Request $req) 
        {
            $validator = Validator::make($req->all(), [
                'name'=> 'required',
                'email'=>'required|email',
                'password' => 'required|min:6'
            ]);
            if ($validator->fails()) {
                $errors  = json_decode($validator->errors());
                $name=isset($errors->name[0])? $errors->name[0] : '';
                $email=isset($errors->email[0])? $errors->email[0] : '';
                $password=isset($errors->password[0])? $errors->password[0] : '';

                 if($name){
                  $msg = $name;
                }
                else if($email){
                  $msg = $email;
                }
                else if($password){
                  $msg = $password;
                }
                
                return response()->json(['message' =>$validator->errors(),'data'=>[],'statusCode'=>422,'success'=>'error'],422);
            }
            
            User::where('id',auth()->user()->id)->update([
                    'name'      => $req->name,
                    'email'     => $req->email,
                    'password'   => Hash::make($req->password)
                    ]);

            $user=User::where('id', auth()->user()->id)->first();

            $message="User updated successfully.";
            return response()->json(['message'=>$message, 'statusCode' => $this-> successStatus,'data'=>$user,'success' => 'success'], $this-> successStatus);

        }   
}