<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public function boot()
    {
        // Schema::defaultStringLength(191);
        $this->app->bind(\App\ViewModels\ISmsModel::class, \App\ViewModels\SmsModel::class);

        // Share heavy view data via a view composer to avoid running DB queries directly in Blade
        View::composer('backend.layout.main', function ($view) {
            try {
                // Use Cache to avoid repeated DB hits on every request. Short TTL (minutes).
                $view->with('lims_user_list', Cache::remember('lims_user_list', 5 * 60, function () {
                    if (!Schema::hasTable('users')) {
                        return collect();
                    }
                    return DB::table('users')->where('is_active', true)->get();
                }));

                $view->with('lims_warehouse_list', Cache::remember('lims_warehouse_list', 5 * 60, function () {
                    if (!Schema::hasTable('warehouses')) {
                        return collect();
                    }
                    return DB::table('warehouses')->where('is_active', true)->get();
                }));

                $view->with('lims_biller_list', Cache::remember('lims_biller_list', 5 * 60, function () {
                    if (!Schema::hasTable('billers')) {
                        return collect();
                    }
                    return DB::table('billers')->where('is_active', true)->get();
                }));

                $view->with('lims_customer_list', Cache::remember('lims_customer_list', 5 * 60, function () {
                    if (!Schema::hasTable('customers')) {
                        return collect();
                    }
                    return DB::table('customers')->where('is_active', true)->get();
                }));

                $view->with('lims_customer_group_list', Cache::remember('lims_customer_group_list', 5 * 60, function () {
                    if (!Schema::hasTable('customer_groups')) {
                        return collect();
                    }
                    return DB::table('customer_groups')->where('is_active', true)->get();
                }));

                $view->with('lims_supplier_list', Cache::remember('lims_supplier_list', 5 * 60, function () {
                    if (!Schema::hasTable('suppliers')) {
                        return collect();
                    }
                    return DB::table('suppliers')->where('is_active', true)->get();
                }));

                $view->with('lims_expense_category_list', Cache::remember('lims_expense_category_list', 5 * 60, function () {
                    if (!Schema::hasTable('expense_categories')) {
                        return collect();
                    }
                    return DB::table('expense_categories')->where('is_active', true)->get();
                }));

                // Accounts use Eloquent model in original code; keep as-is but cache
                $view->with('lims_account_list', Cache::remember('lims_account_list', 5 * 60, function () {
                    if (!Schema::hasTable('accounts')) {
                        return collect();
                    }
                    return \App\Models\Account::where('is_active', true)->get();
                }));
            } catch (\Exception $e) {
                // If DB/schema isn't ready (e.g. during migrations), return empty collections to prevent errors
                Log::warning('View composer for backend.layout.main failed: ' . $e->getMessage());
                $view->with('lims_user_list', collect())
                    ->with('lims_warehouse_list', collect())
                    ->with('lims_biller_list', collect())
                    ->with('lims_customer_list', collect())
                    ->with('lims_customer_group_list', collect())
                    ->with('lims_supplier_list', collect())
                    ->with('lims_expense_category_list', collect())
                    ->with('lims_account_list', collect());
            }
        });
    }
}
