<?php

namespace App\Enums;

enum WorkspaceFileType: int
{
    case BRIEFING = 1;
    case STRATEGY = 2;

    /**
     * An image a run step generated from its message.
     */
    case STEP_IMAGE = 3;
}
