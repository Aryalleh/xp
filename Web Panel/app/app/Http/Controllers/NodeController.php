<?php

namespace App\Http\Controllers;

use App\Models\Node;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NodeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admins');
    }

    public function index()
    {
        $nodes = Node::orderBy('created_at', 'desc')->paginate(10);
        return view('nodes.index', compact('nodes'));
    }

    public function create()
    {
        return view('nodes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ip' => 'required|ipv4',
            'port' => 'required|numeric|min:1|max:65535',
            'token' => 'required|string',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        Node::create($request->all());

        return redirect()->route('nodes.index')->with('success', 'Node created successfully.');
    }

    public function edit($id)
    {
        $node = Node::findOrFail($id);
        return view('nodes.edit', compact('node'));
    }

    public function update(Request $request, $id)
    {
        $node = Node::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'ip' => 'required|ipv4',
            'port' => 'required|numeric|min:1|max:65535',
            'token' => 'required|string',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $node->update($request->all());

        return redirect()->route('nodes.index')->with('success', 'Node updated successfully.');
    }

    public function destroy($id)
    {
        $node = Node::findOrFail($id);
        $node->delete();
        return redirect()->route('nodes.index')->with('success', 'Node deleted successfully.');
    }

    public function testConnection($id)
    {
        $node = Node::findOrFail($id);
        try {
            $response = Http::timeout(5)->get("http://{$node->ip}:{$node->port}/api/{$node->token}/listuser");

            if ($response->successful()) {
                return back()->with('success', 'Connection successful! API returned 200 OK.');
            } else {
                return back()->with('error', 'Connection failed. Status Code: ' . $response->status());
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Connection error: ' . $e->getMessage());
        }
    }
}
