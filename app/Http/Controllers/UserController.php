<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    Public function index()
    {
        $users = User::first();
        return view('admin.users.index', [
            'users' => $users
        ]);
    }
}
