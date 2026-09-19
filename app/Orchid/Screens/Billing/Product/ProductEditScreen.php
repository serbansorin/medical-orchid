<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Billing\Product;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Organization;
use App\Models\Product;
use App\Services\Catalog\CatalogPricingService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\RadioButtons;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ProductEditScreen extends Screen
{
    public ?Product $product = null;

    public function __construct(private readonly CatalogPricingService $pricing) {}

    public function query(): iterable
    {
        $id = request()->route('product');
        $product = $id ? Product::query()->findOrFail($id) : new Product;
        $this->product = $product;

        $level = 'organization';
        if ($product->doctor_id) {
            $level = 'doctor';
        } elseif ($product->clinic_id) {
            $level = 'clinic';
        }

        $effective = null;
        if ($product->exists) {
            $effective = $this->pricing->forProduct($product, $product->clinic_id, $product->doctor_id);
        }

        return [
            'product' => $product,
            'owner_level' => $level,
            'effective' => $effective,
        ];
    }

    public function name(): ?string
    {
        return $this->product?->exists
            ? __('products.form.edit_title', ['name' => $this->product->name])
            : __('products.form.create_title');
    }

    public function permission(): ?iterable
    {
        return ['view_any_product', 'view_any_service'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('products.form.save'))->icon('bs.check-circle')->method('save'),
            Link::make(__('invoices.detail.back'))->icon('bs.arrow-left')->route('platform.products'),
        ];
    }

    public function layout(): iterable
    {
        $rows = [
            RadioButtons::make('owner_level')
                ->title(__('products.form.owner_level'))
                ->options([
                    'organization' => __('products.level.organization'),
                    'clinic' => __('products.level.clinic'),
                    'doctor' => __('products.level.doctor'),
                ])
                ->value(request()->query('owner_level', 'organization')),

            Select::make('product.organization_id')
                ->title(__('products.form.organization'))
                ->fromModel(Organization::class, 'name')
                ->empty(),

            Select::make('product.clinic_id')
                ->title(__('products.form.clinic'))
                ->fromModel(Clinic::class, 'name')
                ->empty(),

            Select::make('product.doctor_id')
                ->title(__('products.form.doctor'))
                ->fromModel(Doctor::class, 'first_name')
                ->empty(),

            Input::make('product.name')
                ->title(__('products.form.name'))
                ->required(),

            Input::make('product.product_code')
                ->title(__('products.form.code')),

            Input::make('product.sku')
                ->title(__('products.form.sku')),

            Input::make('product.category')
                ->title(__('products.form.category'))
                ->help('service: appointment, procedure, lab_test, video_consultation, in_person · physical: medication, supply, equipment, supplement'),

            Select::make('product.type')
                ->title(__('products.form.type'))
                ->options([
                    Product::TYPE_SERVICE => __('products.type.service'),
                    Product::TYPE_PHYSICAL => __('products.type.physical'),
                ])
                ->empty(),

            Input::make('product.price')
                ->title(__('products.form.price'))
                ->type('number')
                ->required(),

            Input::make('product.duration_minutes')
                ->title(__('products.form.duration'))
                ->type('number'),

            Input::make('product.stock_quantity')
                ->title(__('products.form.stock'))
                ->type('number'),

            CheckBox::make('product.available')
                ->title(__('products.form.active'))
                ->sendTrueOrFalse(),

            CheckBox::make('product.requires_payment')
                ->title(__('products.form.requires_payment'))
                ->sendTrueOrFalse(),

            CheckBox::make('product.requires_prescription')
                ->title(__('products.form.requires_prescription'))
                ->sendTrueOrFalse(),

            TextArea::make('product.description')
                ->title(__('products.form.description'))
                ->rows(3),
        ];

        $layouts = [
            Layout::rows($rows),
        ];

        if ($this->product?->exists) {
            $layouts[] = Layout::view('orchid.billing.product-inheritance');
        }

        return $layouts;
    }

    public function save(Request $request): mixed
    {
        $data = $request->validate([
            'product.organization_id' => 'nullable|exists:organizations,id',
            'product.clinic_id' => 'nullable|exists:clinics,id',
            'product.doctor_id' => 'nullable|exists:doctors,id',
            'product.name' => 'required|string|max:255',
            'product.product_code' => 'nullable|string|max:100',
            'product.sku' => 'nullable|string|max:100',
            'product.category' => 'nullable|string|max:100',
            'product.type' => 'nullable|in:'.Product::TYPE_SERVICE.','.Product::TYPE_PHYSICAL,
            'product.price' => 'required|numeric|min:0',
            'product.duration_minutes' => 'nullable|integer|min:0',
            'product.stock_quantity' => 'nullable|integer|min:0',
            'product.available' => 'nullable|boolean',
            'product.requires_payment' => 'nullable|boolean',
            'product.requires_prescription' => 'nullable|boolean',
            'product.description' => 'nullable|string',
        ])['product'];

        $level = (string) $request->input('owner_level', 'organization');

        if ($level === 'clinic') {
            $data['doctor_id'] = null;
        } elseif ($level === 'doctor') {
            $data['clinic_id'] = $data['clinic_id'] ?? null;
        } else {
            $data['clinic_id'] = null;
            $data['doctor_id'] = null;
        }

        $id = request()->route('product');

        $product = $id ? Product::query()->findOrFail($id) : new Product;
        $product->fill($data);
        $product->save();

        Toast::success(__('products.form.saved'));

        return redirect()->route('platform.products.edit', $product->id);
    }
}
