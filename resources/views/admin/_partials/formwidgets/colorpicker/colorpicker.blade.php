<div
    class="input-group control-colorpicker dropend"
    data-control="colorpicker"
>
    <label class="input-group-text border-none p-0">
        <input
            id="{{ $this->getId('input') }}"
            class="form-control form-control-color p-0"
            type="color"
            name="{{ $name }}"
            value="{{ $value }}"
            title="Choose your color"
            {!! ($this->disabled || $this->previewMode) ? 'disabled="disabled"' : '' !!}
            {!! ($this->readOnly) ? 'readonly="readonly"' : '' !!}
        />
    </label>
    <button
        class="btn btn-outline-secondary dropdown-toggle"
        type="button"
        data-bs-toggle="dropdown"
        aria-expanded="false"
    ></button>
    <ul class="dropdown-menu dropdown-menu-end">
        @foreach($availableColors as $color)
            <li>
                <button
                    class="dropdown-item mb-2"
                    type="button"
                    data-swatches-color="{{$color}}"
                    style="background-color: {{$color}};"
                ></button>
            </li>
        @endforeach
    </ul>
</div>
