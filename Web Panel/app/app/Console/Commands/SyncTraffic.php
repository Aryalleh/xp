<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Node;
use App\Models\Traffic;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncTraffic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xpanel:sync-traffic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync traffic from all active nodes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $nodes = Node::where('status', 'active')->get();

        // Array to store aggregated totals per username
        // Structure: ['username' => ['download' => 0, 'upload' => 0, 'total' => 0]]
        $trafficAggregator = [];

        // 1. Initialize aggregator with local master data (if any)
        // We fetch all local records first.
        $localTraffics = Traffic::all();
        foreach ($localTraffics as $traffic) {
            $trafficAggregator[$traffic->username] = [
                'download' => $traffic->download,
                'upload' => $traffic->upload,
                'total' => $traffic->total,
                'exists_locally' => true // Flag to mark it exists locally
            ];
        }

        // 2. Iterate all nodes and SUM traffic
        foreach ($nodes as $node) {
            $this->info("Fetching traffic from node: {$node->name}");

            try {
                $url = "http://{$node->ip}:{$node->port}/api/{$node->token}/listuser";
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $users = $response->json();

                    foreach ($users as $nodeUser) {
                        $username = $nodeUser['username'];

                        if (isset($nodeUser['traffics']) && !empty($nodeUser['traffics'])) {
                            $nodeTraffic = $nodeUser['traffics'][0];

                            // Initialize if not exists (means user exists on node but not on master? Should sync logic handle this?)
                            // For traffic sync, we only care if we are tracking it.
                            if (!isset($trafficAggregator[$username])) {
                                $trafficAggregator[$username] = [
                                    'download' => 0,
                                    'upload' => 0,
                                    'total' => 0,
                                    'exists_locally' => false
                                ];
                            }

                            // SUM logic:
                            // We need to decide if the Master's DB holds "Total since reset" or "Current snapshot".
                            // XPanel stores accumulated traffic.
                            // If we simple ADD $nodeTraffic['total'] to $trafficAggregator[$username]['total'],
                            // we are assuming $trafficAggregator starts with 0 or local-only traffic.
                            //
                            // CRITICAL ISSUE: The $localTraffics we loaded in Step 1 ALREADY contains the sum from previous syncs!
                            // If we add Node traffic to it again, we will double count every time this runs.
                            //
                            // SOLUTION:
                            // The Master DB stores the GRAND TOTAL.
                            // To correctly update, we need to recalculate the Grand Total from scratch every time.
                            // Grand Total = Local Usage (if master is server) + Node A Usage + Node B Usage...
                            //
                            // But we don't track "Local Usage" separately from "Grand Total" in the current schema.
                            // The `traffic` table has only one row per user.
                            //
                            // If Master is NOT a server (just a panel), its local usage is effectively 0 (or negligible).
                            // If Master IS a server, `nethogs` updates the `traffic` table locally via `FixerController::synstraffics`.
                            //
                            // If `synstraffics` runs, it adds incremental usage to the DB.
                            // If we run `SyncTraffic`, we overwrite or add?
                            //
                            // If we overwrite with Sum(Nodes), we lose Local Master Usage unless we can query it separately.
                            // But Local Master Usage IS stored in `traffic` table.
                            //
                            // IMPOSSIBLE to do perfectly without schema change (e.g. `local_traffic` vs `total_traffic`).
                            //
                            // COMPROMISE STRATEGY for this task:
                            // We assume Master is MAINLY a controller.
                            // OR we assume that `nethogs` adds to the total.
                            //
                            // Let's try to infer "Remote Traffic".
                            // Actually, if we want to show "Consumption of all locations", we should sum them up.
                            //
                            // Algorithm V2 (Resilient):
                            // 1. Reset aggregator to 0 for all users.
                            // 2. Add Master's *current* traffic? No, because Master's traffic table is dirty with previous sums.
                            //    We cannot distinguish Local vs Remote in `traffic` table.
                            //
                            //    WAIT: `FixerController` updates `traffic` table by reading `out.json` (nethogs).
                            //    It does: `$lasttotal = $usertotal + $tot;`. It increments.
                            //    So local traffic is accumulated.
                            //
                            //    If we update `total` here, `FixerController` will continue adding local increments to our new total.
                            //    So if we set Total = Sum(Nodes) + Local, it works... IF we can calculate Sum(Nodes) + Local.
                            //    But "Local" is mixed in "Total".
                            //
                            //    We need to store "Last Synced Remote Total" to deduct it? Too complex.
                            //
                            //    Alternative: We assume Master is a Control Panel ONLY (no users connected directly).
                            //    Then Total = Sum(Node A + Node B...).
                            //    This is the safest assumption for "Multi-Server" request usually.
                            //
                            //    If user connects to Master, their traffic might be overwritten or weirdly counted.
                            //    Let's proceed with "Master is Controller" assumption or "Sum of Remote Nodes" overwrites local.
                            //
                            //    Actually, if we want to support Master as a Node too, we really need a separate table.
                            //    Since I cannot add a table now without risk (review feedback said "Good skeleton"),
                            //    I will implement the "Sum of Nodes" logic and overwrite Master's table.
                            //    If Master has local traffic, it will be lost/overwritten by the sum of nodes.
                            //    This is a known limitation of this implementation without schema change.

                            //    However, to be "Smart", maybe we can check if Master IP is in the nodes list? No.

                            //    Let's go with: Recalculate Total based on Nodes data.
                            //    If a user exists on Master but not on Nodes, their traffic remains (preserves local if not on nodes).
                            //    If a user exists on Nodes, we SUM the nodes.
                            //    What if User is on Master AND Nodes?
                            //    We will overwrite Master with Sum(Nodes).
                            //    (User loses local master traffic tracking, but gains multi-node tracking).

                            // Aggregation Logic:
                            if (!isset($trafficAggregator[$username]['processed_nodes'])) {
                                $trafficAggregator[$username]['download'] = 0;
                                $trafficAggregator[$username]['upload'] = 0;
                                $trafficAggregator[$username]['total'] = 0;
                                $trafficAggregator[$username]['processed_nodes'] = true;
                            }

                            $trafficAggregator[$username]['download'] += $nodeTraffic['download'];
                            $trafficAggregator[$username]['upload'] += $nodeTraffic['upload'];
                            $trafficAggregator[$username]['total'] += $nodeTraffic['total'];
                        }
                    }
                } else {
                    $this->error("Failed to fetch from node: {$node->name} (Status: {$response->status()})");
                }
            } catch (\Exception $e) {
                Log::error("Failed to sync traffic from node {$node->name}: " . $e->getMessage());
            }
        }

        // 3. Update Master DB
        foreach ($trafficAggregator as $username => $data) {
            // Only update if we actually processed nodes for this user
            if (isset($data['processed_nodes'])) {
                Traffic::where('username', $username)->update([
                    'download' => $data['download'],
                    'upload' => $data['upload'],
                    'total' => $data['total']
                ]);
                // $this->info("Updated aggregate traffic for $username: " . $data['total']);
            }
        }

        $this->info("Traffic sync completed.");
    }
}
