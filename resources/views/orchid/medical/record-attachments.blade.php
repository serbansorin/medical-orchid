@if($attachments->isEmpty())
    <div class="alert alert-default">{{ __('records.no_files') }}</div>
@else
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>{{ __('records.existing_files') }}</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($attachments as $file)
                    <tr>
                        <td>{{ $file->original_name }} <span class="text-muted">({{ number_format($file->size / 1024, 1) }} KB)</span></td>
                        <td class="text-end">
                            <form method="POST" action="{{ request()->url() }}/deleteAttachment" class="d-inline">
                                @csrf
                                <input type="hidden" name="attachment_id" value="{{ $file->id }}">
                                <input type="hidden" name="record_id" value="{{ $record->id }}">
                                <button type="submit" class="btn btn-sm btn-link text-danger" data-msg="{{ __('Delete?') }}" onclick="return confirm(this.dataset.msg)">
                                    <x-orchid-icon path="bs.trash" />
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
