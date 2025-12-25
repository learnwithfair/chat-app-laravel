<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait ApiResponse
{
    public function success($data, $message = null, $code = 200, $pagination = false)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'code'    => $code,
        ];

        if ($pagination) {
            $meta = [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'prev_page_url' => $data->previousPageUrl(),
                'next_page_url' => $data->nextPageUrl(),
            ];
            $response['meta'] = $meta;
        }


        return response()->json($response, $code);
    }

    public function error($data, $message = null, $code = 500)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => $data,
            'code' => $code
        ], $code);
    }
}
