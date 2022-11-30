<div class="input-group">
    <input
        type="datetime-local"
        id="{{ $this->getId('datetime') }}"
        class="form-control datepicker-input"
        name="{{ $field->getName() }}"
        value="{{ $value ? $value->format('Y-m-d\TH:i') : null }}"
        data-control="datepicker"
        data-datepicker-value
        @if($startDate) min="{{ $startDate }}" @endif
        @if($endDate) max="{{ $endDate }}" @endif
        pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}"
        {!! $field->getAttributes() !!}
        @if($this->previewMode) readonly="readonly" @endif
        autocomplete="off"
    />
    <span class="input-group-text"><i class="fa fa-calendar-o"></i></span>
</div>
