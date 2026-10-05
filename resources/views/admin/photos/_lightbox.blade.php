<dialog data-admin-photo-dialog class="m-auto max-h-[90vh] w-[min(92vw,1100px)] rounded-xl border border-zinc-700 bg-zinc-950 p-0 text-white shadow-2xl backdrop:bg-black/75">
    <div class="relative p-3 sm:p-5">
        <button type="button" data-admin-photo-dialog-close aria-label="{{ __('admin.photos.close_image') }}" class="absolute right-4 top-4 z-10 grid size-9 place-items-center rounded-full bg-black/70 text-xl text-white hover:bg-black">×</button>
        <img data-admin-photo-dialog-image alt="" class="mx-auto max-h-[78vh] max-w-full rounded-lg object-contain">
        <div data-admin-photo-dialog-caption class="mt-3 min-h-5 text-center text-sm text-zinc-300"></div>
    </div>
</dialog>

@push('scripts')
<script>
(() => {
    const bindAdminPhotoLightbox = () => {
        const dialog = document.querySelector('[data-admin-photo-dialog]');
        const image = dialog?.querySelector('[data-admin-photo-dialog-image]');
        const caption = dialog?.querySelector('[data-admin-photo-dialog-caption]');
        const closeButton = dialog?.querySelector('[data-admin-photo-dialog-close]');
        if (!dialog || !image || !caption || dialog.dataset.bound) return;

        dialog.dataset.bound = '1';

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-admin-photo-lightbox]');
            if (!trigger) return;
            image.src = trigger.dataset.photoSrc || '';
            image.alt = trigger.dataset.photoAlt || '';
            caption.textContent = trigger.dataset.photoCaption || '';
            dialog.showModal();
        });

        const close = () => dialog.close();
        closeButton?.addEventListener('click', close);
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) close();
        });
        dialog.addEventListener('close', () => {
            image.removeAttribute('src');
            caption.textContent = '';
        });
    };

    document.addEventListener('DOMContentLoaded', bindAdminPhotoLightbox, { once: true });
    document.addEventListener('livewire:navigated', bindAdminPhotoLightbox);
})();
</script>
@endpush
