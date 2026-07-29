<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hospital\HospitalServiceType;
use App\Models\Hospital\HospitalPayment;
use App\Models\Hospital\HospitalServiceRequest;

class RemoveDuplicateServices extends Command
{
    protected $signature = 'hospital:remove-duplicates';
    protected $description = 'Remove duplicate hospital services and merge data';

    public function handle()
    {
        $this->info('Finding duplicate hospital services...');

        // Get all services grouped by name
        $services = HospitalServiceType::all()->groupBy(function($service) {
            return strtolower(trim($service->name));
        });

        $duplicatesFound = 0;
        $recordsMerged = 0;

        foreach ($services as $name => $groupedServices) {
            if ($groupedServices->count() > 1) {
                $duplicatesFound++;
                $this->warn("Found {$groupedServices->count()} duplicates for: {$name}");

                // Keep the first one (oldest)
                $keepService = $groupedServices->sortBy('id')->first();
                $deleteServices = $groupedServices->sortBy('id')->skip(1);

                $this->info("Keeping: ID {$keepService->id} - {$keepService->name} (₦{$keepService->amount})");

                // Update related records to point to the kept service
                foreach ($deleteServices as $deleteService) {
                    $this->warn("  Merging ID {$deleteService->id} into {$keepService->id}");

                    // Update payments
                    $paymentCount = HospitalPayment::where('service_type_id', $deleteService->id)->count();
                    if ($paymentCount > 0) {
                        HospitalPayment::where('service_type_id', $deleteService->id)
                            ->update(['service_type_id' => $keepService->id]);
                        $this->info("  - Updated {$paymentCount} payment records");
                    }

                    // Update service requests
                    $requestCount = HospitalServiceRequest::where('service_type_id', $deleteService->id)->count();
                    if ($requestCount > 0) {
                        HospitalServiceRequest::where('service_type_id', $deleteService->id)
                            ->update(['service_type_id' => $keepService->id]);
                        $this->info("  - Updated {$requestCount} service request records");
                    }

                    // Delete the duplicate
                    $deleteService->delete();
                    $recordsMerged++;
                }
            }
        }

        if ($duplicatesFound === 0) {
            $this->info('No duplicate services found!');
        } else {
            $this->info("Completed! Found {$duplicatesFound} duplicate groups, merged {$recordsMerged} records.");
        }

        return 0;
    }
}
