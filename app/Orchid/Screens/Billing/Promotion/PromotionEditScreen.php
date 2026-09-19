<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Billing\Promotion;

use App\Enums\DiscountType;
use App\Models\Pharmacy;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PromotionEditScreen extends Screen
{
    public ?Promotion $promotion = null;

    public function query(): iterable
    {
        $id = request()->route('promotion');
        $promotion = $id ? Promotion::query()->findOrFail($id) : new Promotion;
        $this->promotion = $promotion;

        $discountOptions = [];
        foreach (DiscountType::cases() as $case) {
            $discountOptions[$case->value] = $case->label();
        }

        return [
            'promotion' => $promotion,
            'discountOptions' => $discountOptions,
        ];
    }

    public function name(): ?string
    {
        return $this->promotion?->exists
            ? __('promotions.promotion_form.edit_title', ['name' => $this->promotion->title])
            : __('promotions.promotion_form.create_title');
    }

    public function permission(): ?iterable
    {
        return ['view_any_promotion', 'view_any_loyalty_program'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('promotions.promotion_form.save'))->icon('bs.check-circle')->method('save'),
            Link::make(__('invoices.detail.back'))->icon('bs.arrow-left')->route('platform.promotions'),
        ];
    }

    public function layout(): iterable
    {
        // NOTE: real table columns are used (title, discount_type, ...).
        // The copied model also lists name/max_discount_amount/promo_code,
        // which do NOT exist in the promotions table — reported, not used.
        $discountOptions = [];
        foreach (DiscountType::cases() as $case) {
            $discountOptions[$case->value] = $case->label();
        }

        return [
            Layout::rows([
                Select::make('promotion.pharmacy_id')
                    ->title(__('promotions.promotion_form.pharmacy'))
                    ->fromModel(Pharmacy::class, 'name')
                    ->empty(),

                Input::make('promotion.title')
                    ->title(__('promotions.promotion_form.title'))
                    ->required(),

                TextArea::make('promotion.description')
                    ->title(__('promotions.promotion_form.description'))
                    ->rows(3),

                Select::make('promotion.discount_type')
                    ->title(__('promotions.promotion_form.discount_type'))
                    ->options($discountOptions)
                    ->empty(),

                Input::make('promotion.discount_value')
                    ->title(__('promotions.promotion_form.discount_value'))
                    ->type('number'),

                Input::make('promotion.min_purchase_amount')
                    ->title(__('promotions.promotion_form.min_purchase'))
                    ->type('number'),

                DateTimer::make('promotion.starts_at')
                    ->title(__('promotions.promotion_form.starts_at'))
                    ->format('Y-m-d H:i')
                    ->enableTime(),

                DateTimer::make('promotion.ends_at')
                    ->title(__('promotions.promotion_form.ends_at'))
                    ->format('Y-m-d H:i')
                    ->enableTime(),

                Input::make('promotion.usage_limit')
                    ->title(__('promotions.promotion_form.usage_limit'))
                    ->type('number'),

                CheckBox::make('promotion.is_active')
                    ->title(__('promotions.promotion_form.active'))
                    ->sendTrueOrFalse(),
            ]),
        ];
    }

    public function save(Request $request): mixed
    {
        $data = $request->validate([
            'promotion.pharmacy_id' => 'nullable|exists:pharmacies,id',
            'promotion.title' => 'required|string|max:255',
            'promotion.description' => 'nullable|string',
            'promotion.discount_type' => 'nullable|string|max:50',
            'promotion.discount_value' => 'nullable|numeric|min:0',
            'promotion.min_purchase_amount' => 'nullable|numeric|min:0',
            'promotion.starts_at' => 'nullable|date',
            'promotion.ends_at' => 'nullable|date|after_or_equal:promotion.starts_at',
            'promotion.usage_limit' => 'nullable|integer|min:1',
            'promotion.is_active' => 'nullable|boolean',
        ])['promotion'];

        $id = request()->route('promotion');

        $promotion = $id ? Promotion::query()->findOrFail($id) : new Promotion;
        $promotion->fill($data);
        $promotion->save();

        Toast::success(__('promotions.promotion_form.saved'));

        return redirect()->route('platform.promotions.edit', $promotion->id);
    }
}
