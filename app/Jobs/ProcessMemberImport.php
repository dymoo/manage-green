<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MemberImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Tenant;
use Exception;

class ProcessMemberImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file_path;
    protected $tenant_id;
    protected $column_map;

    /**
     * Create a new job instance.
     */
    public function __construct(string $file_path, int $tenant_id, array $column_map)
    {
        $this->file_path = $file_path;
        $this->tenant_id = $tenant_id;
        $this->column_map = $column_map;
    }

    public function getTenantId(): int
    {
        return $this->tenant_id;
    }

    public function getColumnMap(): array
    {
        return $this->column_map;
    }

    public function getFilePath(): string
    {
        return $this->file_path;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Retrieve the tenant - necessary if using tenant-specific connections or settings
        $tenant = Tenant::find($this->tenant_id);
        if (!$tenant) {
            Log::error("ProcessMemberImport: Tenant not found with ID {$this->tenant_id}. Aborting job.");
            return;
        }

        // Switch to tenant context if necessary (depends on tenancy package)
        // Example: $tenant->run(function () { ... });
        // Or set the connection manually if needed:
        // config(['database.default' => $tenant->database_connection_name]);

        try {
            Log::info("Starting member import for tenant {$this->tenant_id} from file {$this->file_path}");

            // Use the MemberImport class, passing the tenant_id and column_map
            Excel::import(new MemberImport($this->tenant_id, $this->column_map), $this->file_path, 'local'); // Assuming file is on 'local' disk

            Log::info("Finished member import successfully for tenant {$this->tenant_id}.");

            // Optional: Clean up the uploaded file after successful import
            // Storage::disk('local')->delete($this->file_path);

            // Optional: Notify the user who initiated the import
            // Notification::send($this->user, new ImportCompletedNotification());

        } catch (Exception $e) {
            Log::error("Member import failed for tenant {$this->tenant_id}. File: {$this->file_path}. Error: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            // Optional: Notify the user about the failure
            // Notification::send($this->user, new ImportFailedNotification($e->getMessage()));

            // Ensure the file is not deleted on failure for potential inspection
        }
    }
}
