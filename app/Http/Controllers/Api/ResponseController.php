<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ResponseController extends Controller
{
    //Response with data and token
    public static function sendResponseTokenWithData($status, $message, $token, $data)
    {
        $response = [
            'status' => $status,
            'message' => $message,
            'token' => $token,
            'data' => $data
        ];
        return response()->json($response);
    }

    //Response with message
    public static function sendResponseMessage($status, $message)
    {
        $response =[
            'status' => $status,
            'message' => $message
        ];
        return response()->json($response);
    }

    //Response with message with url
    public static function sendResponseMessageWithUrl($status, $message, $url)
    {
        $response =[
            'status' => $status,
            'message' => $message,
            'url' => $url
        ];
        return response()->json($response);
    }

    //Response with message simple data
    public static function sendResponseData($status=false, $message=null, $data=null)
    {
        if ($message==null){
            $message=($status)?'Action perfomrmed successfully':'Unable to perform this action';
        }
        $response = [
            'status'  => $status,
            'message' => $message,
            'data' => $data
        ];
        return response()->json($response);
    }

    //Response with message simple data
    public static function sendResponseToken($status, $message, $token)
    {
        $response = [
            'status'  => $status,
            'message' => $message,
            'token' => $token
        ];
        return response()->json($response);
    }
}
