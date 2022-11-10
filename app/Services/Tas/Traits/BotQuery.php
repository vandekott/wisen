<?php

namespace App\Services\Tas\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait BotQuery
{
    /**
     * Запрос к TAS
     * @param string $http_method get/post
     * @param string $class madelineproto class
     * @param string|null $method madelineproto method
     * @param array $params params array
     * @return mixed
     */
    public function query(string $http_method, string $class, string $method = null, array $params = []): mixed
    {
        $query_url = sprintf(
            "http://%s:%s/api/%s/%s%s",
            config('tas.host'),
            config('tas.port'),
            $this->session_name,
            $class,
            (!$method) ? '' : '.' . $method
        );

        if ($http_method == 'post') {
            $response = Http::post($query_url, [ 'data' => $params ]);
        } else {
            $response = Http::get($query_url, $params);
        }

        Log::info("TAS Agent {$this->session_name}: {$query_url} - {$response->status()}");

        return $response->json();
    }

}
