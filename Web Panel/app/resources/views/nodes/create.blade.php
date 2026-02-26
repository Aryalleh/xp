@extends('layouts.master')
@section('title', 'Create Node')
@section('content')
<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10">Create Node</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Node Details</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('nodes.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="name">Node Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="ip">IP Address</label>
                                    <input type="text" class="form-control" name="ip" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="port">Port</label>
                                    <input type="number" class="form-control" name="port" value="80" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="token">API Token (from Node Settings)</label>
                                    <input type="text" class="form-control" name="token" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-12 form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control" name="description"></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Create Node</button>
                            <a href="{{ route('nodes.index') }}" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
