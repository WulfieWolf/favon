@foreach ($values as $key => $value)
    @php
        $fieldName = isset($prefix) && $prefix !== '' ? $prefix.'['.$key.']' : (string) $key;
    @endphp
    @if (is_array($value))
        @include('partials.query-hidden-fields', ['values' => $value, 'prefix' => $fieldName])
    @elseif ($value !== null && $value !== '')
        <input type="hidden" name="{{ $fieldName }}" value="{{ $value }}">
    @endif
@endforeach
