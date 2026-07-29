<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Hospital\HospitalServiceType;
use App\Models\Hospital\HospitalPayment;
use App\Models\Hospital\HospitalServiceRequest;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hospital duplicate services cleanup command
Artisan::command('hospital:remove-duplicates', function () {
    $this->info('Finding duplicate hospital services...');

    $services = HospitalServiceType::all()->groupBy(function($service) {
        return strtolower(trim($service->name));
    });

    $duplicatesFound = 0;
    $recordsMerged = 0;

    foreach ($services as $name => $groupedServices) {
        if ($groupedServices->count() > 1) {
            $duplicatesFound++;
            $this->warn("Found {$groupedServices->count()} duplicates for: {$name}");

            $keepService = $groupedServices->sortBy('id')->first();
            $deleteServices = $groupedServices->sortBy('id')->skip(1);

            $this->info("Keeping: ID {$keepService->id} - {$keepService->name} (₦{$keepService->amount})");

            foreach ($deleteServices as $deleteService) {
                $paymentCount = HospitalPayment::where('service_type_id', $deleteService->id)->count();
                if ($paymentCount > 0) {
                    HospitalPayment::where('service_type_id', $deleteService->id)
                        ->update(['service_type_id' => $keepService->id]);
                    $this->info("  - Updated {$paymentCount} payment records");
                }

                $requestCount = HospitalServiceRequest::where('service_type_id', $deleteService->id)->count();
                if ($requestCount > 0) {
                    HospitalServiceRequest::where('service_type_id', $deleteService->id)
                        ->update(['service_type_id' => $keepService->id]);
                    $this->info("  - Updated {$requestCount} service request records");
                }

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
})->purpose('Remove duplicate hospital services');
