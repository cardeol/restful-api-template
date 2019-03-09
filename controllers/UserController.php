<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;
use Carbon\Carbon;


$app->add(function ($request, $response, $next) { // jsonp

    $basePath = $request->getUri()->getBasePath();
    $route_parts = explode("/" , $request->getUri()->getPath());

    $route = $route_parts[1];
    
    $only_users = [
        'restricted',
    ];

    $only_admin = [
        'admin'
    ];
    
    if(in_array($route , $only_users)) {
         if(!Helper::hasRole($request)) {
            return Helper::invalidCredentials($response);
         }
    };
    
    if(in_array($route, $only_admin)) {            
        if($userid != 1) { // not admin
            Session::setMessage("Not an admin");
            return $response->withStatus(302)->withHeader('Location', SITEURL.'main');
        }         
    }
    
    $callback = $_GET['callback'] ?? false;

    if($callback) $response->getBody()->write($callback."(");
    $response = $next($request, $response);
    if($callback) $response->getBody()->write(")");
    
    return $response;
});



$app->get('/users', function (Request $request, Response $response) {
    $response->getBody()->write(User::all());
});

$app->get('/users/{id}', function (Request $request, Response $response) {
    try {
        $id = $request->getAttribute("id");
        $user = User::where("active",1)->find($id);
        if($user) {
            $response->getBody()->write($user);        
            return;    
        }
        Helper::ResponseError($response, "Invalid Data");
    } catch (Exception $e) {
        
    }
});


// The route to get a secured data.
$app->get('/restricted', function (Request $request, Response $response) {
    
    $decoded = Helper::userInfo($request);

    $r = array(
        "message" => "Welcome",
        "result" => "ok",
        "decoded" => $decoded
    );

    $response = $response->write(json_encode($r));
    return $response;

});



$app->post('/users/forgotpassword', function (Request $request, Response $response) {

    $postdata = $request->getParsedBody();
    $email = $postdata['email'] ?? "";

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return Helper::ResponseError($response, "Invalid email");
    }

    $user = User::where("email", $email)
                ->where("active", 1);

    if($user->count() == 0) {
        return Helper::ResponseError($response, "Invalid Data");
    }   

    $hash = Helper::genActivationCode();

    User::where("email", $email)->update(['activation_code' => $hash, ]);
    
    Helper::SendResetPassword($email, $hash);

    Helper::Response($response, "You will receive an email soon with the link to reset your password.");

});

$app->post('/users/resetpassword', function (Request $request, Response $response) {

    $postdata = $request->getParsedBody();
    $email = $postdata['email'] ?? "";
    $password = $postdata['password'] ?? "";
    $code = $postdata['code'] ?? "";

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return Helper::ResponseError($response, "Invalid email");
    }

    if(strlen($password)<5) {
        return Helper::ResponseError($response, "Password is too short, please use a stronger password.");   
    }

    if($code == "") {
        return Helper::ResponseError($response, "Invalid Data1");      
    }

    $user = User::where("email", $email)->where("activation_code", $code);

    if($user->count() == 0) {
        return Helper::ResponseError($response, "Invalid Data2");
    }   


    User::where("email", $email)->update([ 
        "active" => 1, 
        "password" => Helper::mdhash($password),
        "activation_code" => ""
    ]);

    
    Helper::Response($response, "Password has been changed, Please login with your new credentials.");
});

$app->post('/users/verify', function (Request $request, Response $response) {
    
    $postdata = $request->getParsedBody();


    $email = $postdata['email'] ?? "";
    $code = $postdata['code'] ?? "";

    
    if(empty($email) || empty($code)) {
        return Helper::ResponseError($response, "Invalid Data");
    }

    $user = User::where("email", $email)
            ->where("active", 0)
            ->where("activation_code", $code);

    $n = $user->count();
    $u = $user->first();

    if($n == 0) {
         // User::where("email", $email)->where("activation_code", $code)->update('active', 1);
        return Helper::ResponseError($response, "This confirmation code is already used");
        
    }

    $user->update(['active' => '1', 'activation_code' => '']);

    return Helper::Response($response, "User has been verified");    
    
});


$app->post('/users/add', function (Request $request, Response $response) {
    try {
        

        /*
            $scope.data = {
                termsagree: false,
                username: "test",
                email: "test@gmail.com",
                first: "testname",
                last: "testsurname",
                bday_month: 1,
                bday_day: "21",        
                bday_year: "1980",    
                password: "12345",        
                rpassword: "12345"
            };
        */

        $postdata = $request->getParsedBody();

        $final = array();
        $data = array();
        $fields = array( "username", "email", "first", "last", "phone",
                         "bday_month", "bday_day", "bday_year", "password", "gender");
        foreach($fields as $f) {            
            if(!isset($postdata[$f])) {
                return Helper::ResponseError($response, "Invalid Data ($f)");
            }
            $data[$f] = isset($postdata[$f]) ? trim($postdata[$f]) : "";
        }

        // validate date
        $bday = mktime(0,0,0,intval($data['bday_month'])+1,intval($data['bday_day']),intval($data['bday_year']));
        if(intval(date("d",$bday))!=intval($data['bday_day'])) {
            return Helper::ResponseError($response,"Invalid date: ".date("d-M-Y", $bday));
        }
        $year = intval(date("Y",$bday));
        
        if($year> intval(date("Y")-10) || $year < 1900) {
            return Helper::Response($response, "Invalid Year: ".$year);
        };

        // name
        if( empty($data['first']) 
            || empty($data['last']) 
            || strlen($data['first'])<3
            || strlen($data['last'])<3
            ) {
            return Helper::ResponseError($response, "Invalid name");
        }

        // gender
        if($data['gender']!="M" && $data['gender']!="F") {
            return Helper::ResponseError($response, "Invalid gender");
        }

        //email
        if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return Helper::ResponseError($response, "Invalid email");
        }

        if(strlen($data['password'])<5) {
            return Helper::ResponseError($response, "Password is too short");
        };

        if(!preg_match("#[0-9]+#",$data['password'])) {
            return Helper::ResponseError($response, "Password Must Contain At Least 1 digit");
        }

        if(!preg_match("#[a-zA-Z]+#",$data['password'])) {
            $passwordErr = "Password Must Contain At Least 1 Letter";
        }

        // database validations
        $u = User::where("login", $data['username'])->first();
        if($u) {
            return Helper::ResponseError($response, "This username has been registered already");   
        }

        $u = User::where("email", $data['email'])->first();
        if($u) {
            return Helper::ResponseError($response, "This email has been registered already");   
        }

        $activation_code = Helper::mdhash(time().mt_rand(0,100000));

        $user = new User;
        $user->birthday = date('Y-m-d H:i:s',$bday);
        $user->name     = $data['first']." ".$data['last'];
        $user->phone    = $data['phone'];
        $user->email    = $data['email'];
        $user->login    = $data['username'];
        $user->gender   = $data['gender'];
        $user->active   = 0;
        $user->password = Helper::mdhash($data['password']);
        $user->activation_code = $activation_code;

        
        if($user->save()) {
            // send verification email
            Helper::SendVerificationEmail($data['email'], $activation_code);

            return Helper::Response($response, "Thank you for registering. You will receive a confirmation email soon. Please check your inbox.");


        } else {
            return Helper::ResponseError($response, "Failed to add a new user");
        }
    } catch (Exception $e) {
        return Helper::ResponseError($response, $e->getMessage());
    }

});

$app->put('/users/update/:id', function (Request $request, Response $response) {
    try {
        $id = $request->getAttribute("id");
        $user = User::find($id);
        $user->name = $app->request()->post('name');
        $user->phone = $app->request()->post('phone');
        $user->email = $app->request()->post('email');


        if($user->save()) {
            $response->getBody()->write(array( "message" => "Successfully updated"));
        } else {
            $response->getBody()->write(array( "message" => "Successfully updated"));
        }   
    } catch (Exception $e) {
        echo '{"error":{"text":'. 'Unable to get the web service. ' . $e->getMessage() .'}}';
    }
    
});

$app->delete('/users/:id', function (Request $request, Response $response) {

    $id = $request->getAttribute("id");
    $user = User::find($id);

    if($user->delete()) {
        echo '{"message":"Successfully delete user"}';
    } else {
         echo '{"message":"Failed to delete user"}';
    }
});



/**
 * @SWG\Get(
 *     path="/api/authenticate",
 *     @SWG\Response(response="200", description="An example resource")
 * )
 */
$app->post('/authenticate', function(Request $request, Response $response) {

    $data = $request->getParsedBody();

    $email = strtolower(trim($data['email']));
    $password = $data['password'];
    
    $current_user = User::where("email",$email)
                ->where("active", 1)
                ->where("password", Helper::mdhash($password))
                ->first();

    if($current_user !== null) {
        $userid = intval($current_user['id']);
        $token = Token::where("userid", $userid)
                ->whereDate('date_expiration','>',  Carbon::today()->toDateString())
                ->first();
         
        $jwt = "";
        if($token===null) {            
            $old_token = Token::where("userid",$userid)->first();            
            if($old_token!==null) {
                $old_token->delete();
            }
            $payload = Helper::generatePayload($current_user);            
            $jwt = JWT::encode($payload, HKEY);
            $token = new Token;
            $token->userid = $userid;
            $token->value = $jwt;
            $token['date_expiration'] = $payload['exp'];
            try {
                $token->save();
            } catch(Exception $e) {
                $this->logger->addInfo($e->getMessage());
                die($e->getMessage());
            }            
        } else {
            $jwt = $token->value;
        };        
        $output = array(
            "login" => $current_user['login'],
            "token" => $jwt,
            "exp" => Carbon::parse($token['date_expiration'])->timestamp
        );            

        $response->getBody()->write(json_encode($output));
    } else {
        return Helper::invalidCredentials($response);
    }

    return $response;

});

