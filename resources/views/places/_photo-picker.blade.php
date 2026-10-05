<div
    data-photo-picker
    data-photo-max="{{ $photoPickerMax }}"
    class="rounded-lg border border-dashed border-zinc-300 p-3 dark:border-zinc-700"
>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <div class="text-sm font-semibold">{{ __('photos.picker.title') }} <span class="font-normal text-zinc-400">({{ __('photos.picker.optional_limit') }})</span></div>
            <div class="mt-0.5 text-xs text-zinc-500">{!! __('photos.picker.selected', ['count' => '<span data-photo-count>0</span>', 'maximum' => $photoPickerMax]) !!}</div>
        </div>
        <button type="button" data-photo-open class="rounded-md bg-zinc-100 px-3 py-2 text-sm font-medium hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700">
            {{ __('photos.picker.add') }}
        </button>
    </div>

    <input
        id="{{ $photoPickerId }}"
        data-photo-input
        type="file"
        name="photos[]"
        accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
        multiple
        @if($photoPickerRequired ?? false) required @endif
        class="sr-only"
    >

    <div data-photo-previews class="mt-3 hidden flex-wrap gap-3"></div>
    <div data-photo-error class="mt-2 hidden rounded-md bg-red-50 px-3 py-2 text-xs text-red-700 dark:bg-red-950/30 dark:text-red-300"></div>

    <div class="mt-3">
        <x-photo-upload-rules variant="place" :modal-id="'photo-upload-rules-'.$photoPickerId" />
    </div>

    <p class="mt-3 text-xs leading-5 text-zinc-500">
        {{ __('photos.picker.help') }}
    </p>
</div>
