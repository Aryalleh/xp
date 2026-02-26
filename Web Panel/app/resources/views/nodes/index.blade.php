@extends('layouts.master')
@section('title', 'Node Management')
@section('content')
<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10">Node Management</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Nodes</h5>
                        <div class="card-header-right">
                            <a href="{{ route('nodes.create') }}" class="btn btn-primary btn-sm">Add New Node</a>
                        </div>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>IP Address</th>
                                        <th>Port</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($nodes as $node)
                                    <tr>
                                        <td>{{ $node->name }}</td>
                                        <td>{{ $node->ip }}</td>
                                        <td>{{ $node->port }}</td>
                                        <td>
                                            @if($node->status == 'active')
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('nodes.test', $node->id) }}" class="btn btn-icon btn-info btn-sm" title="Test Connection">
                                                <i class="ti ti-wifi"></i>
                                            </a>
                                            <a href="{{ route('nodes.edit', $node->id) }}" class="btn btn-icon btn-warning btn-sm" title="Edit">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                            <form action="{{ route('nodes.destroy', $node->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-icon btn-danger btn-sm" onclick="return confirm('Are you sure?')" title="Delete">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            {{ $nodes->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
