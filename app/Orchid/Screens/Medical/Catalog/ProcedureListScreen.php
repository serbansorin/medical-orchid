<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Catalog;

use App\Models\Procedure;
use App\Orchid\Layouts\Medical\ProcedureListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class ProcedureListScreen extends Screen
{
    public function query(): iterable
    {
        $filter = (array) request()->get('filter', []);

        $query = Procedure::with(['patient', 'doctor'])
            ->when(isset($filter['procedure_name']) && $filter['procedure_name'] !== '',
                fn ($q) => $q->where('procedure_name', 'ilike', '%'.$filter['procedure_name'].'%'))
            ->when(isset($filter['status']) && $filter['status'] !== '',
                fn ($q) => $q->where('status', $filter['status']))
            ->orderByDesc('id');

        return ['procedures' => $query->paginate(25)];
    }

    public function name(): ?string
    {
        return __('catalogs.procedures_title');
    }

    public function description(): ?string
    {
        return __('catalogs.procedures_description');
    }

    public function permission(): ?iterable
    {
        // No dedicated view_any_procedure slug; clinical read gate.
        return ['view_any_medical_record'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('catalogs.procedure_create'))
                ->icon('bs.plus-circle')
                ->route('platform.catalogs.procedures.create'),
        ];
    }

    public function layout(): iterable
    {
        return [ProcedureListLayout::class];
    }
}
