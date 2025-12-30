<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\User;
use Image;
use Storage;
use DateTime;


class HomeFreeController extends Controller
{
    function bahiaPadelHome(){
       return View('bahia_padel.home.index'); 
    }
    
    function bahiaPadelAdmin() {
        return View('bahia_padel.admin.index'); 
    }
    
}
