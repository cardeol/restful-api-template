<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;

class Helper {

    public static function mdhash($data) {
        $salt = HKEY;
        if($data === null) $data = "";
        return md5($data.$salt);
    }

    public static function generatePayload($user, $roles = null) {
        if($roles === null) $roles = array( 'user' );
        $payload = array(
            "iss"     => SITE_URL,
            "iat"     => time(),
            "exp"     => time() + (3600 * 24 * 15),
            "context" => [
                "user" => [
                    "user_login" => $user['login'],
                    "user_id"    => $user['id'],
                    "roles"      => $roles
                ]
            ]
        );
        return $payload;        
    }

    public static function genActivationCode() {
        return md5(time().mt_rand(0,100000));
    }

    public static function hasRole($request, $role = "user") {
        $jwt = $request->getHeaders();
        try {
            if(!isset($jwt['HTTP_AUTHORIZATION'])) {
                return false;
            }
            $auth = $jwt['HTTP_AUTHORIZATION'][0].'';
            $auth = str_replace("Bearer ","",$auth);
            $decoded = JWT::decode($auth, HKEY, array('HS256'));
            if(isset($decoded->context->user->roles)) {
                $roles = $decoded->context->user->roles;
                if(in_array($role, $roles)) return true;                
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    public static function invalidCredentials($response) {
        $response = $response->withStatus(401);
        return self::ResponseError($response, "Invalid Credentials");
    }

     public static function userInfo($request) {
        $jwt = $request->getHeaders();
        try {
            if(!isset($jwt['HTTP_AUTHORIZATION'])) {
                return false;
            }
            $auth = $jwt['HTTP_AUTHORIZATION'][0].'';
            $auth = str_replace("Bearer ","",$auth);
            $decoded = JWT::decode($auth, HKEY, array('HS256'));
            if(isset($decoded->context)) {
                return $decoded;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    public static function Response($response, $data) {
        // header("Access-Control-Allow-Origin: *");

        $r = array(
                "error" => false,
                "data" => $data
        );
        $response
            ->withHeader('Content-type', 'application/json')
            ->write(json_encode($r));
    }

    public static function ResponseError($response, $message) {
        $r = array(
            "error" => true,
            "message" => $message
        );
        return $response->write(json_encode($r));
    }

    public static function SendVerificationEmail($email, $hash) {
        
        $to      = $email; // Send email to our user        
        $env = ENVIRONMENT;        

        $domain = $_SERVER['SERVER_NAME'];
        $subject = 'Signup | Verification'; // Give the email a subject 

        $url = SITE_URL."/verify/$email/$hash";

        if(ENVIRONMENT == "DEV") {
            $to = DEFAULT_EMAIL;
            $url = SITE_URL."/app/verify/$email/$hash";
        };

        $message = '
         
        Thanks for signing up!
        Your account has been created, you can login with the following credentials after you have activated your account by pressing the url below.
                    
        Please click this link to activate your account:
        '.$url.'
         
        '.SITE_NAME.'
         
        '; // Our message above including the link
                             
        $headers = 'From: '.SITE_NAME.' <noreply@'.SITE_DOMAIN.'>' . "\r\n"; // Set from headers
        mail($to, $subject, $message, $headers); // Send our email
    }

    public static function SendResetPassword($email, $hash) {
        
        $to      = $email; // Send email to our user        
        $env = ENVIRONMENT;        

        $domain = $_SERVER['SERVER_NAME'];
        $subject = 'Password Reset';
        
        $url = SITE_URL."/resetpassword/$email/$hash";

        if(ENVIRONMENT == "DEV") {
            $to = DEFAULT_EMAIL;
            $url = SITE_URL."/app/resetpassword/$email/$hash";
        };

        $message = '


        Hi '.$email.',

        To reset your '.SITE_NAME.' account password please click on this link.

        '.$url.'

        If you have previously requested to change your password, only the link contained in this e-mail is valid.

        If you did not request an account update, please contact us as soon as possible.

        '.SITE_NAME.'
         
        ';
                             
        $headers = 'From: '.SITE_NAME.' <noreply@'.SITE_DOMAIN.'>' . "\r\n"; // Set from headers
        mail($to, $subject, $message, $headers); // Send our email
    }
    
}

?>