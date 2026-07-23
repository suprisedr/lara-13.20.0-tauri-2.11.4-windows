@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-violet-500 focus:ring-violet-500 rounded-md shadow-sm']) }}>
