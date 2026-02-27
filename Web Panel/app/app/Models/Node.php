<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip',
        'port',
        'token',
        'status',
        'description',
    ];

    /**
     * Get the API URL for this node.
     *
     * @return string
     */
    public function getApiUrlAttribute()
    {
        return "http://{$this->ip}:{$this->port}/api/{$this->token}/";
    }
}
