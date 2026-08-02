<?php

namespace App\Providers;

use App\Services\Hospital\HospitalPermissions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Optimize model relationships - eager load by default
        Model::preventLazyLoading(false);

        // The {consultation} route param resolves to HospitalMedicalRecord.
        Route::bind('consultation', function ($value) {
            return \App\Models\Hospital\HospitalMedicalRecord::findOrFail($value);
        });

        Response::macro('download_csv', function ($data, $filename, $headers = []) {
            $csv = implode(',', $headers) . "\n";
            foreach ($data as $row) {
                $csv .= implode(',', array_map(function ($item) {
                    return '"' . str_replace('"', '""', $item) . '"';
                }, $row)) . "\n";
            }

            return Response::make($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        });

        // @permission('patients.create') ... @endpermission
        // Renders the block only if the current user has the named permission.
        Blade::directive('permission', function (string $expression) {
            return "<?php if (\\App\\Services\\Hospital\\HospitalPermissions::allows({$expression})): ?>";
        });
        Blade::directive('endpermission', function () {
            return '<?php endif; ?>';
        });

        // @anypermission(['patients.view','pharmacy.view']) ... @endanypermission
        // Renders the block if the user has any of the listed permissions.
        Blade::directive('anypermission', function (string $expression) {
            return "<?php if (\\App\\Services\\Hospital\\HospitalPermissions::allowsAny([{$expression}])): ?>";
        });
        Blade::directive('endanypermission', function () {
            return '<?php endif; ?>';
        });
    }
}
