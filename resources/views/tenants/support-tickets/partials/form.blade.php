<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="row">
        <div class="col-md-12 mb-3">
            <label class="form-label" for="title">{{ __('Title') }}</label>
            <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                value="{{ old('title', $supportTicket?->title ?? '') }}" required>
            @error('title')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-12 mb-3">
            <label class="form-label" for="description">{{ __('Description') }}</label>
            <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror"
                required>{{ old('description', $supportTicket?->description ?? '') }}</textarea>
            @error('description')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-12 mb-3">
            <label class="form-label" for="images">{{ __('Attachments (PNG, max 3)') }}</label>
            <input type="file" name="images[]" id="images" accept="image/png" multiple
                class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror">
            <small class="text-muted">{{ __('You can upload up to 3 PNG images.') }}</small>
            @error('images')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
            @error('images.*')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
    </div>

    @if (! empty($supportTicket?->image_paths))
        <div class="mb-3">
            <div class="fw-semibold mb-2">{{ __('Current Attachments') }}</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($supportTicket->image_paths as $path)
                    @if (is_string($path) && $path !== '')
                        <a href="{{ asset('storage/' . $path) }}" target="_blank" rel="noopener">
                            <img src="{{ asset('storage/' . $path) }}" alt="{{ __('Attachment') }}"
                                class="rounded border" style="width: 96px; height: 96px; object-fit: cover;">
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <div class="mb-3">
        <div class="fw-semibold mb-2">{{ __('New Image Preview') }}</div>
        <div id="ticket-image-preview" class="d-flex flex-wrap gap-2"></div>
    </div>

    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
</form>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const imageInput = document.getElementById('images');
            const previewContainer = document.getElementById('ticket-image-preview');

            if (!(imageInput instanceof HTMLInputElement) || !(previewContainer instanceof HTMLDivElement)) {
                return;
            }

            let selectedFiles = [];

            const syncFileInput = () => {
                const dataTransfer = new DataTransfer();
                selectedFiles.forEach((file) => dataTransfer.items.add(file));
                imageInput.files = dataTransfer.files;
            };

            const renderPreview = () => {
                previewContainer.innerHTML = '';

                selectedFiles.forEach((file, index) => {
                    if (file.type !== 'image/png') {
                        return;
                    }

                    const wrapper = document.createElement('div');
                    wrapper.className = 'position-relative';

                    const image = document.createElement('img');
                    image.className = 'rounded border';
                    image.style.width = '96px';
                    image.style.height = '96px';
                    image.style.objectFit = 'cover';
                    image.alt = 'Preview';
                    image.src = URL.createObjectURL(file);

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'btn btn-danger btn-sm position-absolute top-0 end-0 p-0';
                    removeButton.style.width = '22px';
                    removeButton.style.height = '22px';
                    removeButton.style.lineHeight = '20px';
                    removeButton.style.transform = 'translate(35%, -35%)';
                    removeButton.textContent = 'x';
                    removeButton.setAttribute('aria-label', 'Remove image');
                    removeButton.addEventListener('click', () => {
                        selectedFiles = selectedFiles.filter((_, selectedIndex) => selectedIndex !== index);
                        syncFileInput();
                        renderPreview();
                    });

                    wrapper.appendChild(image);
                    wrapper.appendChild(removeButton);
                    previewContainer.appendChild(wrapper);
                });
            };

            imageInput.addEventListener('change', () => {
                const files = imageInput.files ? Array.from(imageInput.files) : [];

                if (files.length > 3) {
                    imageInput.setCustomValidity('You can upload a maximum of 3 images.');
                    imageInput.reportValidity();

                    return;
                }

                imageInput.setCustomValidity('');
                selectedFiles = files;
                syncFileInput();
                renderPreview();
            });
        });
    </script>
@endpush
