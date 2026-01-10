@if (session()->has('success'))
<div wire:ignore.self class="row my-3">
    <div class="col-12">
        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            <strong>Success - </strong> {{ session('success') }}
        </div>
    </div>
</div>
@endif

@if (session()->has('warning'))
<div wire:ignore.self class="row my-3">
    <div class="col-12">
        <div class="alert alert-warning alert-dismissible bg-warning text-dark border-0 fade show" role="alert">
            <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="alert" aria-label="Close"></button>
            <strong>Warning - </strong> {{ session('warning') }}
        </div>
    </div>
</div>
@endif

@if (session()->has('danger'))
<div wire:ignore.self class="row my-3">
    <div class="col-12">
        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            {{ session('danger') }}
        </div>
    </div>
</div>
@endif

@if (session()->has('error'))
<div wire:ignore.self class="row my-3">
    <div class="col-12">
        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            <strong>Error - </strong> {{ session('error') }}
        </div>
    </div>
</div>
@endif