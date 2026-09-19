@extends('platform::dashboard')

{{--
    Medical-Orchid override of platform::layouts.base
    The vendor file double-translates: __($name). Screens already return translated
    strings; on case-insensitive filesystems (macOS) a title like "Patients" resolves
    to the lang GROUP file (patients.php) and __() returns an array -> TypeError.
--}}
@section('title', (string) $name)
@section('description', (string) $description)
@section('controller', $controller)

@section('navbar')
    @foreach($commandBar as $command)
        <li>
            {!! $command !!}
        </li>
    @endforeach
@endsection

@section('content')
    <div id="modals-container">
        @stack('modals-container')
    </div>

    <form id="post-form"
          class="mb-md-4 h-100"
          method="post"
          enctype="multipart/form-data"
          data-controller="form"
          data-form-need-prevents-form-abandonment-value="{{ var_export($needPreventsAbandonment) }}"
          data-form-failed-validation-message-value="{{ $formValidateMessage }}"
          data-action="keypress->form#disableKey
                      turbo:before-fetch-request@document->form#confirmCancel
                      beforeunload@window->form#confirmCancel
                      change->form#changed
                      form#submit"
          novalidate
    >
        {!! $layouts !!}
        @csrf
        @include('platform::partials.confirm')
    </form>

    <div data-controller="filter">
        <form id="filters" autocomplete="off"
              data-action="filter#submit"
              data-form-need-prevents-form-abandonment-value="false"
        ></form>
    </div>

    @includeWhen(isset($state), 'platform::partials.state')
@endsection
