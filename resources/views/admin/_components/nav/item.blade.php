@props(['code', 'children' => []])
@php
    $isActive = (bool)AdminMenu::isActiveNavItem($code);
@endphp

<li {{ $attributes->class(['active' => $isActive]) }}>
    {{ $slot }}

    @if($children)
        <x-igniter.admin::nav
            class="nav collapse {{ $isActive ? ' show' : '' }}"
            aria-expanded="{{ $isActive ? 'true' : 'false' }}"
        >
            @foreach($children as $childCode => $childItem)
                @if(isset($childItem['child']) && empty($childItem['child']))
                    @continue;
                @endif
                    <x-igniter.admin::nav.item
                        :code="$childCode"
                        class="nav-item w-100"
                    >
                        <x-igniter.admin::nav.item-link
                            class="nav-link {{ $childItem['class'] ?? '' }}"
                        href="{{ $childItem['href'] ?? '#' }}"
                    >
                        <span>{{ $childItem['title'] }}</span>
                        </x-igniter.admin::nav.item-link>
                    </x-igniter.admin::nav.item>
            @endforeach
        </x-igniter.admin::nav>
    @endif
</li>
