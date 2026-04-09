<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatWorkflowAction extends Model
{
    protected $connection = 'shared';
    protected $table = 'sc_chat_workflow_actions';

    const TYPE_SEND_MESSAGE = 'send_message';
    const TYPE_SEND_TEMPLATE = 'send_template';
    const TYPE_ASSIGN_AGENT = 'assign_agent';
    const TYPE_ASSIGN_TEAM = 'assign_team';
    const TYPE_CHANGE_STATUS = 'change_status';
    const TYPE_ADD_TAG = 'add_tag';
    const TYPE_ENABLE_AI = 'enable_ai';
    const TYPE_DISABLE_AI = 'disable_ai';
    const TYPE_SET_AI_PROMPT = 'set_ai_prompt';
    const TYPE_WAIT = 'wait';
    const TYPE_NOTIFY_AGENT = 'notify_agent';

    const TYPES = [
        self::TYPE_SEND_MESSAGE => 'Send Message',
        self::TYPE_SEND_TEMPLATE => 'Send Template',
        self::TYPE_ASSIGN_AGENT => 'Assign Agent',
        self::TYPE_ASSIGN_TEAM => 'Assign Team',
        self::TYPE_CHANGE_STATUS => 'Change Status',
        self::TYPE_ADD_TAG => 'Add Tag',
        self::TYPE_ENABLE_AI => 'Enable AI',
        self::TYPE_DISABLE_AI => 'Disable AI',
        self::TYPE_SET_AI_PROMPT => 'Set AI Prompt',
        self::TYPE_WAIT => 'Wait / Delay',
        self::TYPE_NOTIFY_AGENT => 'Notify Agent',
    ];

    protected $fillable = [
        'workflow_id', 'sort_order', 'action_type', 'action_config',
    ];

    protected $casts = [
        'action_config' => 'array',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ChatWorkflow::class, 'workflow_id');
    }
}
