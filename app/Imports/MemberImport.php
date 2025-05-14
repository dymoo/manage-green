<?php

namespace App\Imports;

use App\Models\Member;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class MemberImport implements ToModel, WithHeadingRow, WithChunkReading, ShouldQueue
{
    protected $tenant_id;
    protected $column_map;
    protected $member_role;

    public function __construct(int $tenant_id, array $column_map)
    {
        $this->tenant_id = $tenant_id;
        $this->column_map = $column_map;
        $this->member_role = Role::where('name', 'member')
                                  ->where('tenant_id', $this->tenant_id)
                                  ->first();
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Standardize row keys to lowercase for consistent mapping access
        $row_data = array_change_key_case($row, CASE_LOWER);

        // --- Column Mapping Logic ---
        // Get data using the column map provided during initialization.
        // Keys in $this->column_map should be the *target* database field names (e.g., 'name', 'email').
        // Values in $this->column_map should be the *CSV header* names provided by the user.
        $mapped_data = [];
        foreach ($this->column_map as $db_field => $csv_header) {
            $csv_header_lower = strtolower($csv_header); // Match against lowercase row keys
            if (isset($row_data[$csv_header_lower])) {
                $mapped_data[$db_field] = $row_data[$csv_header_lower];
            } else {
                // Log if a mapped column is expected but not found in the row
                 Log::debug("Mapped column '{$csv_header}' not found in row for field '{$db_field}'. Row: " . json_encode($row_data));
                 $mapped_data[$db_field] = null;
            }
        }

        // --- Essential Data Validation ---
        $email = $mapped_data['email'] ?? null;
        $first_name = $mapped_data['name'] ?? null; // Assuming 'name' map corresponds to first name
        $last_name = $mapped_data['last_name_s'] ?? null; // Assuming 'last_name_s' map corresponds to last name

        if (empty($email)) {
            Log::warning("Skipping row due to missing mapped email. Row: " . json_encode($row_data));
            return null; // Essential: Cannot import without email
        }

        $full_name = trim($first_name . ' ' . $last_name);
        if (empty($full_name)) {
             // Allow import if email exists but name is missing, log it.
             Log::info("Importing user '{$email}' with missing name components. Row: " . json_encode($row_data));
             $full_name = $email; // Use email as name as a fallback
        }


        // --- Find or Prepare User ---
        $user = User::where('email', $email)
                    ->where('tenant_id', $this->tenant_id) // Ensure we check within the correct tenant
                    ->first();

        $fob_id = $mapped_data['#'] ?? null; // Using '#' as mapped key based on example-import.csv
        $registered_at_string = $mapped_data['registered'] ?? null; // Using 'registered' as mapped key


        if ($user) {
            // --- Update Existing User ---
            Log::info("Updating existing user '{$email}' for tenant {$this->tenant_id}.");
            $user->fill([
                'name'             => $full_name,
                // Do not update email or password typically during import unless intended
                'fob_id'           => $fob_id ?? $user->fob_id, // Update if provided, else keep existing
                'registered_at'    => $this->parseDate($registered_at_string) ?? $user->registered_at, // Update if provided & valid
                // Add other updatable fields here based on $mapped_data
                // 'gender'        => $mapped_data['gender'] ?? $user->gender,
                // 'age'           => $mapped_data['age'] ?? $user->age,
                // 'member_type'   => $mapped_data['type'] ?? $user->member_type, // Needs 'member_type' column
                // 'member_group'  => $mapped_data['group'] ?? $user->member_group, // Needs 'member_group' column
                // 'expiry_date'   => $this->parseDate($mapped_data['expiry'] ?? null) ?? $user->expiry_date, // Needs 'expiry_date' column
                // 'has_dni_scan'  => ($mapped_data['dni_scan'] ?? 'No') === 'Yes', // Needs 'has_dni_scan' boolean column
                 // Note: Decide on update strategy for status, roles etc.
            ]);

            // Only save if changes were actually made
            if ($user->isDirty()) {
                $user->save();
            }

            // Return null because ToModel expects a *new* model instance.
            // Updates are handled directly here. We don't want laravel-excel
            // to try and save this existing model instance again.
            return null;

        } else {
            // --- Create New User ---
            Log::info("Creating new user '{$email}' for tenant {$this->tenant_id}.");
            $newUser = new User([
                'name'             => $full_name,
                'email'            => $email,
                'password'         => Hash::make(str()->random(12)), // Generate random password
                'email_verified_at'=> now(), // Assuming imported users are pre-verified
                'fob_id'           => $fob_id,
                'registered_at'    => $this->parseDate($registered_at_string),
                'tenant_id'        => $this->tenant_id, // Assign user to the current tenant
                'member_status'    => 'active', // Default status for imported members
                 // Add other creatable fields here based on $mapped_data
                // 'gender'        => $mapped_data['gender'] ?? null,
                // 'age'           => $mapped_data['age'] ?? null,
                // 'member_type'   => $mapped_data['type'] ?? null,
                // 'member_group'  => $mapped_data['group'] ?? null,
                // 'expiry_date'   => $this->parseDate($mapped_data['expiry'] ?? null),
                // 'has_dni_scan'  => ($mapped_data['dni_scan'] ?? 'No') === 'Yes',
            ]);

            // Save before assigning roles
            $newUser->save();

            // Assign the 'member' role if found
            if ($this->member_role) {
                $newUser->assignRole($this->member_role);
            } else {
                 Log::warning("Could not find 'member' role for tenant {$this->tenant_id}. User '{$email}' created without role.");
            }

            // Return the newly created user instance for laravel-excel
            return $newUser;
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }

    private function parseDate(?string $date_string): ?\Illuminate\Support\Carbon
    {
        if (empty($date_string)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::createFromFormat('d-m-Y', $date_string) ?? \Illuminate\Support\Carbon::parse($date_string);
        } catch (\Exception $e) {
            Log::warning("Could not parse date: '{$date_string}'. Error: " . $e->getMessage());
            return null;
        }
    }
}
