<?php

namespace App\Http\Controllers;

use App\Model\DataClient;
use App\Tools\Common;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,DataClient $dataClient)
    {
        $dataClient->name = $request->input('name');
        $dataClient->phone = $request->input('phone');
        $dataClient->qq = $request->input('qq');
        $dataClient->wechat = $request->input('wechat');
        $dataClient->email = $request->input('email');
        $dataClient->uuid = Common::getUuid();

        $res  = $dataClient->save();

        if ($res) {
            return response()->json(['ServerNo' => 'SN200', 'ServerTime' => time(), 'ResultData' => '添加成功']);
        } else {
            return response()->json(['ServerNo' => 'SN400', 'ServerTime' => time(), 'ResultData' => '添加失败']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
