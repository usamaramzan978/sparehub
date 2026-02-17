@props([
    'id' => 'deleteModal',
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" data-delete-title>{{ __('Delete') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" data-delete-message>Are you sure you want to delete <strong data-delete-name>this
                        item</strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" data-delete-form>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('.js-delete-modal').forEach((button) => {
                button.addEventListener('click', () => {
                    const modalId = button.getAttribute('data-bs-target');
                    if (!modalId) {
                        return;
                    }

                    const modal = document.querySelector(modalId);
                    if (!modal) {
                        return;
                    }

                    const form = modal.querySelector('[data-delete-form]');
                    const name = modal.querySelector('[data-delete-name]');
                    const title = modal.querySelector('[data-delete-title]');
                    const message = modal.querySelector('[data-delete-message]');

                    if (form) {
                        form.setAttribute('action', button.dataset.action || '');
                    }
                    if (name) {
                        name.textContent = button.dataset.name || 'this item';
                    }
                    if (title) {
                        title.textContent = button.dataset.title || 'Delete';
                    }
                    if (message) {
                        message.textContent = button.dataset.message ||
                            'Are you sure you want to delete this item?';
                    }
                });
            });
        </script>
    @endpush
@endonce
