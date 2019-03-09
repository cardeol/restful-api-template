<?php

use \Illuminate\Database\Eloquent\Model as Model;

class User extends \Illuminate\Database\Eloquent\Model  {

    protected $hidden = array('password', 'token', 'activation_code');

     protected $dates = [
        'birthdate'
     ];
    
}

?>