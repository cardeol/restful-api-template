<?php

use \Illuminate\Database\Eloquent\Model as Model;

class Token extends \Illuminate\Database\Eloquent\Model  {

     protected $dates = [
        'date_expiration'
     ];
    
}

?>