@props(['text' => 'Loading...', 'size' => 'md'])

@php
    $dotSize = match($size) {
        'sm' => 'h-1.5 w-1.5',
        'lg' => 'h-3 w-3',
        default => 'h-2 w-2',
    };
    $textSize = match($size) {
        'sm' => 'text-xs',
        'lg' => 'text-base',
        default => 'text-sm',
    };
@endphp

<div class="flex flex-col items-center gap-2">
    <div class="flex items-center gap-1">
        <span class="{{ $dotSize }} rounded-full bg-cyan-500" style="animation: dot-bounce 1.4s infinite ease-in-out both; animation-delay: -0.32s;"></span>
        <span class="{{ $dotSize }} rounded-full bg-cyan-500" style="animation: dot-bounce 1.4s infinite ease-in-out both; animation-delay: -0.16s;"></span>
        <span class="{{ $dotSize }} rounded-full bg-cyan-500" style="animation: dot-bounce 1.4s infinite ease-in-out both;"></span>
    </div>
    @if($text)
        <span class="{{ $textSize }} font-medium text-gray-400 dark:text-gray-500">{{ $text }}</span>
    @endif
</div>
