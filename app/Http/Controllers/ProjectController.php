<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        return response('案件一覧ページは Step 2 で作成します。');
    }

    public function create()
    {
        return response('案件登録ページは Step 3 で作成します。');
    }

    public function store(Request $request)
    {
        return response('案件保存処理は Step 5 で作成します。');
    }
}