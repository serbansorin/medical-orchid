<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Billing\Product;

use App\Models\Product;
use App\Orchid\Layouts\Billing\ProductListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class ProductListScreen extends Screen
{
    public function query(): iterable
    {
        $request = request();
        $qb = Product::query();

        if ($type = $request->query('f_type')) {
            $qb->where('type', $type);
        }

        if ($request->query('f_active') === '1') {
            $qb->where('available', true);
        }

        if ($search = trim((string) $request->query('f_search', ''))) {
            $qb->where(function ($q) use ($search): void {
                $q->where('name', 'ilike', '%'.$search.'%')
                    ->orWhere('product_code', 'ilike', '%'.$search.'%')
                    ->orWhere('sku', 'ilike', '%'.$search.'%');
            });
        }

        return [
            'products' => $qb->orderByDesc('id')->paginate(25),
        ];
    }

    public function name(): ?string
    {
        return __('products.list.title');
    }

    public function description(): ?string
    {
        return __('products.list.description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_product', 'view_any_service'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('products.list.create'))
                ->icon('bs.plus-circle')
                ->route('platform.products.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Select::make('f_type')
                    ->title(__('products.filter.type'))
                    ->options([
                        '' => __('products.filter.all'),
                        Product::TYPE_SERVICE => __('products.type.service'),
                        Product::TYPE_PHYSICAL => __('products.type.physical'),
                    ])
                    ->value(request()->query('f_type', ''))
                    ->empty(),

                CheckBox::make('f_active')
                    ->title(__('products.filter.active'))
                    ->value(1)
                    ->checked((bool) request()->query('f_active')),

                Input::make('f_search')
                    ->title(__('products.filter.search'))
                    ->placeholder(__('products.filter.search'))
                    ->value(request()->query('f_search', '')),

                Button::make(__('products.filter.apply'))
                    ->method('applyFilters'),
            ]),

            ProductListLayout::class,
        ];
    }

    public function applyFilters(Request $request): mixed
    {
        $params = array_filter([
            'f_type' => $request->input('f_type'),
            'f_active' => $request->input('f_active'),
            'f_search' => $request->input('f_search'),
        ]);

        return redirect()->route('platform.products', $params);
    }
}
