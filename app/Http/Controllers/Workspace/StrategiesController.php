<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Requests\Workspace\Strategy\ApproveStrategy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

/**
 * Generation, review and presentation of a strategy live in StrategyPresentationController;
 * this only records the customer's approval from the presentation page.
 */
class StrategiesController extends Controller
{
    public function approve(ApproveStrategy $approveStrategy): RedirectResponse
    {
        if (! $approveStrategy->handle()) {
            return redirect()->back()->with('error', __('genie.strategy_not_approvable'));
        }

        return redirect()->back()->with('success', __('genie.strategy_approved'));
    }
}
