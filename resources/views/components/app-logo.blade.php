@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="ResiCloud" {{ $attributes }}>
        <x-slot name="logo">

                  <img src="{{ asset('images/logo.png') }}" alt="Resicloud Logo" class="size-8 rounded-md object-cover">
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="ResiCloud" {{ $attributes }}>
        <x-slot name="logo">
         <img src="{{ asset('images/logo.png') }}" alt="Resicloud Logo" class="size-8 rounded-md object-cover">
        </x-slot>
    </flux:brand>
@endif
