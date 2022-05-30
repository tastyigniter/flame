@props(['navItems'])
@if(AdminAuth::isLogged())
    <aside {{ $attributes->merge(['class' => 'sidebar', 'role' => 'navigation'])}}>
        <div class="">
            {{ $slot }}
        </div>
        <div id="navSidebar" class="nav-sidebar">
            <x-igniter.admin::nav
                id="side-nav-menu"
                class="nav flex-column"
            >
                @foreach($navItems as $code => $item)
                    @if(isset($item['child']) && empty($item['child']))
                        @continue;
                    @endif
                    <x-igniter.admin::nav.item
                        :code="$code"
                        :children="$item['child'] ?? []"
                        class="nav-item"
                    >
                        <x-igniter.admin::nav.item-link
                            class="nav-link {{ !empty($item['child']) ? 'has-arrow' : '' }} {{ $item['class'] ?? '' }}"
                            href="{{ $item['href'] ?? '#' }}"
                        >
                            <i class="fa {{ $item['icon'] }} fa-fw"></i>
                            <span>{{ $item['title'] }}</span>
                        </x-igniter.admin::nav.item-link>
                    </x-igniter.admin::nav.item>
                @endforeach
            </x-igniter.admin::nav>
        </div>
    </aside>
@endif
