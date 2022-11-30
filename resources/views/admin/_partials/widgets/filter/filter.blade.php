<div
    class="list-filter {{ $cssClasses }}"
    data-store-name="{{ $cookieStoreName }}"
>
    <div id="{{ $filterId }}" class="dropdown">
        <button
            id="{{ $filterId }}-button"
            type="button"
            class="btn btn-secondary dropdown-toggle position-absolute invisible end-0"
            data-bs-toggle="dropdown"
            data-bs-auto-close="false"
            data-bs-reference=".list-table"
            aria-expanded="false"
        ></button>
        <div class="dropdown-menu dropdown-menu-end p-3 col-md-12 col-lg-3">
            <form
                id="{{ $filterId }}-form"
                class="form-inline"
                accept-charset="utf-8"
                data-request="{{ $onSubmitHandler }}"
                role="form"
                data-control="filter-form"
            >
                @csrf
                <div class="d-flex flex-column">
                    @if($search)
                        <div class="col pb-sm-3">
                            <div class="filter-search">{!! $search !!}</div>
                        </div>
                    @endif

                    @if(count($scopes))
                        {!! $this->makePartial('filter/filter_scopes') !!}
                    @endif
                </div>

                <div class="d-flex justify-content-between">
                    <button
                        class="btn btn-primary"
                        type="submit"
                        data-attach-loading
                    >@lang('igniter::admin.text_apply')</button>
                    <button
                        class="btn btn-link"
                        type="button"
                        data-request="{{ $onClearHandler }}"
                        data-attach-loading
                    >@lang('igniter::admin.text_clear')</button>
                </div>
            </form>
        </div>
    </div>
</div>
