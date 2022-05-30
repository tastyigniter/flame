<div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">@lang($title)</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
        </div>
        <div class="modal-body text-center">
            <span class="fa-stack fa-3x text-muted">
                <i class="fa-solid fa-circle fa-stack-2x"></i>
                <i class="fa-solid fa-arrow-up fa-stack-1x fa-inverse"></i>
            </span>
            <p class="lead mt-4">{{$message}}</p>
        </div>
        <div class="modal-footer progress-indicator-container">
            <button
                type="button"
                class="btn btn-link"
                data-bs-dismiss="modal"
            >@lang('igniter::admin.button_close')</button>
            @isset($language->code)
                <button
                    type="button"
                    id="apply-updates"
                    class="btn btn-primary"
                    data-progress-indicator="@lang('igniter::admin.text_loading')"
                >@lang('igniter::system.languages.button_apply_update')</button>
            @endisset
        </div>
    </div>
    {!! form_close() !!}
</div>
