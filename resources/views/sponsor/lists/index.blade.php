@extends('layouts.app')

@section('content')
<h3 class="fw-bold mb-4">Sponsor Review & Document Upload Portal</h3>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Forwarded Applicant Lists for Approval</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive mb-4">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>List / Program</th>
                        <th>Total Students</th>
                        <th>Submitted Status</th>
                        <th>Upload Confirmation File</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Batch 01 - STEM Grant</td>
                        <td>50 Students</td>
                        <td><span class="badge bg-warning text-dark">Pending Confirmation</span></td>
                        <td>
                            <input type="file" class="form-control form-control-sm">
                        </td>
                        <td><button class="btn btn-sm btn-primary">Confirm Beneficiaries</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection