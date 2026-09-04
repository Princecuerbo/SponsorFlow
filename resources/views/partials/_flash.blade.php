@if (session('success'))
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-start gap-2 rounded-3" role="alert">
        <i class="bi bi-check-circle-fill mt-1"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start gap-2 rounded-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('warning'))
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-start gap-2 rounded-3" role="alert">
        <i class="bi bi-info-circle-fill mt-1"></i>
        <div>{{ session('warning') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any() && !request()->is('login*', 'register*', 'admin/login*', 'staff/login*'))
    <div class="alert alert-danger border-0 shadow-sm rounded-3">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-exclamation-octagon-fill"></i>
            <strong>Please fix the following:</strong>
        </div>
        <ul class="mb-0 ps-4 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
