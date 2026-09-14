<?php

use App\Enums\GeniePostStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Genie's record of a Mixpost post it is producing from an idea.
     *
     * The draft itself is created in `mixpost_posts` before the run starts and holds the content;
     * this row only tracks the production: which idea, which run wrote last, where Genie stands
     * (`status`, `error`) and the lineage of its images (`images`: one entry per slot with the
     * generated workspace file and the `mixpost_media` row it was copied to). Whether an image is
     * still in the post is read from the post's original version, never from here. It replaces
     * nothing in the legacy pre-posts tables, which are pending deletion.
     */
    public function up(): void
    {
        Schema::create('genie_posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workspace_id')->constrained('mixpost_workspaces')->cascadeOnDelete();
            $table->foreignId('idea_id')->nullable()->constrained('genie_ideas')->nullOnDelete();
            $table->foreignId('run_id')->nullable()->constrained('genie_ai_runs')->nullOnDelete();
            $table->foreignId('post_id')->constrained('mixpost_posts')->cascadeOnDelete();
            $table->tinyInteger('post_format')->nullable();
            $table->json('images')->nullable();
            $table->tinyInteger('status')->default(GeniePostStatus::GENERATING->value);
            $table->string('error')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index('idea_id');
            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genie_posts');
    }
};
