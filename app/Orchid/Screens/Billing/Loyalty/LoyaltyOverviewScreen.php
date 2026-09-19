<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Billing\Loyalty;

use App\Models\LoyaltyProgram;
use App\Orchid\Layouts\Billing\LoyaltyPointsLayout;
use App\Orchid\Layouts\Billing\LoyaltyProgramListLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

/**
 * Read-mostly loyalty overview (designs/promotion-loyalty.md):
 * programs table + points ledger table (real loyalty_points columns:
 * loyalty_program_id / points_balance / total_points_earned /
 * total_points_redeemed / current_tier — the copied LoyaltyPoint model
 * uses different names; reported, not used).
 *
 * Manual adjustment is org-admin only with a mandatory reason. The table
 * has no reason/audit column and additive-only rules forbid new tables, so
 * the reason is written to the application log (audit trail).
 */
class LoyaltyOverviewScreen extends Screen
{
    public function query(): iterable
    {
        $balances = DB::table('loyalty_points as lp')
            ->leftJoin('patients as p', 'p.id', '=', 'lp.patient_id')
            ->leftJoin('loyalty_programs as prog', 'prog.id', '=', 'lp.loyalty_program_id')
            ->select(
                'lp.id',
                'lp.patient_id',
                'lp.loyalty_program_id',
                'lp.points_balance',
                'lp.total_points_earned',
                'lp.total_points_redeemed',
                'lp.current_tier',
                'p.first_name as patient_first',
                'p.last_name as patient_last',
                'prog.name as program_name'
            )
            ->orderByDesc('lp.id')
            ->paginate(25);

        return [
            'programs' => LoyaltyProgram::query()->orderByDesc('id')->paginate(25),
            'balances' => $balances,
            'canAdjust' => (bool) request()->user()?->hasAccess('access_admin_panel'),
        ];
    }

    public function name(): ?string
    {
        return __('promotions.programs.title');
    }

    public function description(): ?string
    {
        return __('promotions.programs.description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_loyalty_program', 'view_any_promotion'];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        $layouts = [
            LoyaltyProgramListLayout::class,
            LoyaltyPointsLayout::class,
        ];

        if ((bool) request()->user()?->hasAccess('access_admin_panel')) {
            $layouts[] = Layout::rows([
                Input::make('adjust.balance_id')
                    ->title('loyalty_points #id')
                    ->type('number')
                    ->required(),
                Input::make('adjust.delta')
                    ->title(__('promotions.points.delta'))
                    ->type('number')
                    ->required(),
                Input::make('adjust.reason')
                    ->title(__('promotions.points.reason'))
                    ->required(),
                Button::make(__('promotions.points.adjust'))->method('adjustBalance'),
            ]);
        }

        return $layouts;
    }

    public function adjustBalance(Request $request): void
    {
        if (! $request->user()?->hasAccess('access_admin_panel')) {
            Toast::error(__('promotions.points.title'));

            return;
        }

        $data = $request->validate([
            'adjust.balance_id' => 'required|integer|exists:loyalty_points,id',
            'adjust.delta' => 'required|integer|not_in:0',
            'adjust.reason' => 'required|string|max:500',
        ])['adjust'];

        DB::transaction(function () use ($data, $request): void {
            $row = DB::table('loyalty_points')->where('id', $data['balance_id'])->lockForUpdate()->first();

            if (! $row) {
                return;
            }

            $newBalance = (int) $row->points_balance + (int) $data['delta'];

            if ($newBalance < 0) {
                throw new \InvalidArgumentException('Balance cannot go negative.');
            }

            DB::table('loyalty_points')->where('id', $row->id)->update([
                'points_balance' => $newBalance,
                'total_points_earned' => (int) $row->total_points_earned + max(0, (int) $data['delta']),
                'total_points_redeemed' => (int) $row->total_points_redeemed + max(0, -(int) $data['delta']),
                'updated_at' => now(),
            ]);

            Log::info('Loyalty balance adjusted', [
                'balance_id' => $row->id,
                'patient_id' => $row->patient_id,
                'delta' => (int) $data['delta'],
                'reason' => $data['reason'],
                'by' => $request->user()?->id,
            ]);
        });

        Toast::success(__('promotions.points.adjusted'));
    }
}
