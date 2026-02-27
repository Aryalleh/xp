<?php

namespace App\Services;

use App\Models\Node;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Pool;

class NodeService
{
    public static function sync($action, $data)
    {
        $nodes = Node::where('status', 'active')->get();

        if ($nodes->isEmpty()) {
            return;
        }

        try {
            $responses = Http::pool(function (Pool $pool) use ($nodes, $action, $data) {
                foreach ($nodes as $node) {
                    $dataWithToken = $data;
                    $dataWithToken['token'] = $node->token;
                    $url = "http://{$node->ip}:{$node->port}/api/{$action}";

                    $pool->as($node->name)->timeout(2)->post($url, $dataWithToken);
                }
            });

            // Log failures
            foreach ($responses as $name => $response) {
                if ($response instanceof \Exception) {
                    Log::error("Failed to sync {$action} to node {$name}: " . $response->getMessage());
                } elseif ($response->failed()) {
                    Log::error("Failed to sync {$action} to node {$name}: Status " . $response->status());
                }
            }

        } catch (\Exception $e) {
            Log::error("Critical error in NodeService sync: " . $e->getMessage());
        }
    }
}
