    <div class="modal fade" id="categoryEditModal-{{ $category->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('tenant.categories.update', $category) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="category-name-{{ $category->id }}">{{ __('Name') }}</label>
                            <input type="text" name="name" id="category-name-{{ $category->id }}"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $category->name) }}" required>
                            @error('name')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="category-parent-{{ $category->id }}">{{ __('Parent Category') }}</label>
                            <select name="parent_id" id="category-parent-{{ $category->id }}"
                                class="form-select singl-select-2 @error('parent_id') is-invalid @enderror">
                                <option value="">{{ __('None') }}</option>
                                @foreach ($parents as $parent)
                                    @if ($parent->id !== $category->id)
                                        <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) === $parent->id)>
                                            {{ $parent->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('parent_id')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="category-status-{{ $category->id }}">{{ __('Status') }}</label>
                            <select name="status" id="category-status-{{ $category->id }}"
                                class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', $category->status->value) === $status->value)>
                                        {{ ucfirst($status->value) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
