<div class="container">
    <div class="row">
        @push('scripts')
            <p>This is a stack</p>
        @endpush

        @push('scripts')
            <p>This is a stack</p>
        @endpush

        @auth('admin')
            <p>This is a logged admin</p>
        @endauth

        @partialIf('scriprts')

        @component('account')
    </div>
</div>
