<?php namespace AhmetAksoy\HyperQuest\Models;

use Illuminate\Database\Eloquent\Model;

class HyperQuestLog extends Model
{
    public function __construct()
    {
        $this->table = config('hyperquest.log.table', 'hyper_request_logs');
    }
}
