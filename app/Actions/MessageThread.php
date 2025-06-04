<?php

namespace App\Actions;

use App\Enums\RuleType;
use App\Contracts\GenieDataContract;
use App\Models\Rule;
use App\Models\Thread;
use Illuminate\Support\Facades\App;
use Inovector\Mixpost\Models\Workspace;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class MessageThread
{

    /**
     * @param Workspace $workspace
     * @param Rule $rule
     * @return mixed
     */
    public function handle(Thread $thread)
    {
        try {

            $data = App::make(GenieDataContract::class, [$thread->rule->rule_type->name]);

            $response = OpenAI::threads()->runs()->create(
                $thread->id,
                $this->actionData->get($thread),
            );

            Log::info('OPENAI - update thread - ' . $thread->id);
            Log::info('Rule ID - ' . $rule->rule_type->value);

            if ($thread->id && $thread->object === 'thread') {
                return Thread::create([
                    'thread_provider_id' => $thread->id,
                    'workspace_id' => $workspace->id,
                    'rule_type' => $rule->rule_type->value,
                ]);
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return [];
    }

}
