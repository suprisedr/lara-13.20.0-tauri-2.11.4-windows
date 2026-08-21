<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentActionHistory extends Model
{
    protected $table = 'agent_action_history';

    protected $fillable = [
        'agent_type',
        'entity_type',
        'entity_id',
        'last_prompt',
        'last_response',
    ];
}
