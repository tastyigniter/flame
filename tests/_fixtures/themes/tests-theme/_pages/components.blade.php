---
title: 'main::lang.contact.title'
layout: default
permalink: /components
---
<div class="container">
    <div class="row">
        @push('scripts')
            <p>This is a stack</p>
        @endpush

        @push('scripts')
            <p>This is a stack</p>
        @endpush

        @adminauth()
        <p>This is a logged admin</p>
        @endadminauth

        @mainauth()
        <p>This is a logged customer</p>
        @endmainauth

        @partialIf('scriprts')

        @component('account')
    </div>
</div>