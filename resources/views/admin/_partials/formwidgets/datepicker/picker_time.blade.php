<div
    class="input-group"
    data-control="clockpicker"
    data-autoclose="true">
    <input
        type="time"
        name="{{ $field->getName() }}"
        id="{{ $this->getId('time') }}"
        class="form-control timepicker-input"
        autocomplete="off"
        value="{{ $value ? $value->format('H:i') : null }}"
        pattern="[0-9]{2}:[0-9]{2}"
        {!! $field->getAttributes() !!}
        @if($this->previewMode) readonly="readonly" @endif
    />
    <span class="input-group-text"><i class="fa fa-clock-o"></i></span>
</div>
