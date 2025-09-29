        <ul id="side-main-menu" class="side-menu list-unstyled d-print-none">
            <li><a href="{{ url('/dashboard') }}"> <i
                        class="dripicons-meter"></i><span>{{ __('file.dashboard') }}</span></a></li>
            <?php
            // Cache permission names once to avoid repeated collection scans which slow rendering.
            $permission_names = isset($permission_names) ? $permission_names : $role_has_permissions_list->pluck('name')->toArray();
            $index_permission_active = in_array('products-index', $permission_names);
            $category_permission_active = in_array('category', $permission_names);
            $print_barcode_active = in_array('print_barcode', $permission_names);
            $stock_count_active = in_array('stock_count', $permission_names);
            $inbound_active = in_array('inbound-index', $permission_names);
            $outbound_active = in_array('outbound-index', $permission_names);
            ?>
            @if (
                $category_permission_active ||
                    $index_permission_active ||
                    $print_barcode_active ||
                    $stock_count_active ||
                    $adjustment_active)
                <li><a href="#product" aria-expanded="false" data-toggle="collapse"> <i
                            class="dripicons-list"></i><span>{{ __('file.product') }}</span><span></a>
                    <ul id="product" class="collapse list-unstyled ">
                        @if ($category_permission_active)
                            <li id="category-menu"><a href="{{ route('category.index') }}">{{ __('file.category') }}</a>
                            </li>
                        @endif
                        @if ($index_permission_active)
                            <li id="product-list-menu"><a
                                    href="{{ route('products.index') }}">{{ __('file.product_list') }}</a></li>
                            <?php
                            $add_permission_active = $role_has_permissions_list->where('name', 'products-add')->first();
                            ?>
                            @if ($add_permission_active)
                                <li id="product-create-menu"><a
                                        href="{{ route('products.create') }}">{{ __('file.add_product') }}</a></li>
                            @endif
                        @endif
                        @if ($print_barcode_active)
                            <li id="printBarcode-menu"><a
                                    href="{{ route('product.printBarcode') }}">{{ __('file.print_barcode') }}</a></li>
                        @endif
                        @if ($inbound_active)
                            <li id="inbound-list-menu"><a
                                    href="{{ route('adjustment.index', ['type' => 'inbound']) }}">{{ trans('Inbound List') }}</a>
                            </li>
                        @endif
                        @if ($outbound_active)
                            <li id="outbound-list-menu"><a
                                    href="{{ route('adjustment.index', ['type' => 'outbound']) }}">{{ trans('Outbound List') }}</a>
                            </li>
                        @endif
                        @if ($stock_count_active)
                            <li id="stock-count-menu"><a
                                    href="{{ route('stock-count.index') }}">{{ trans('file.Stock Count') }}</a></li>
                        @endif
                    </ul>
                </li>
            @endif
            <?php
            $index_permission_active = in_array('purchases-index', $permission_names);
            ?>
            @if ($index_permission_active)
                <!-- <li><a href="#purchase" aria-expanded="false" data-toggle="collapse"> <i class="dripicons-card"></i><span>{{ trans('file.Purchase') }}</span></a>
            <ul id="purchase" class="collapse list-unstyled ">
                <li id="purchase-list-menu"><a href="{{ route('purchases.index') }}">{{ trans('file.Purchase List') }}</a></li>
                <?php
                $add_permission_active = $role_has_permissions_list->where('name', 'purchases-add')->first();
                ?>
                @if ($add_permission_active)
<li id="purchase-create-menu"><a href="{{ route('purchases.create') }}">{{ trans('file.Add Purchase') }}</a></li>
                <li id="purchase-import-menu"><a href="{{ url('purchases/purchase_by_csv') }}">{{ trans('file.Import Purchase By CSV') }}</a></li>
@endif
            </ul>
            </li> -->
            @endif
            <?php
            $sale_index_permission_active = in_array('sales-index', $permission_names);
            $coupon_permission_active = in_array('coupon', $permission_names);
            $sale_add_permission_active = in_array('sales-add', $permission_names);
            $sale_import_permission_active = in_array('sales-import', $permission_names);
            $sale_tracking_code_permission_active = in_array('sales-tracking', $permission_names);
            ?>
            @if ($sale_index_permission_active || $coupon_permission_active)
                <li><a href="#sale" aria-expanded="false" data-toggle="collapse"> <i
                            class="dripicons-cart"></i><span>{{ trans('file.Order') }}</span></a>
                    <ul id="sale" class="collapse list-unstyled ">
                        @if ($sale_add_permission_active)
                            <li id="sale-create-menu"><a
                                    href="{{ route('sales.create') }}">{{ trans('Add Order') }}</a></li>
                        @endif
                        @if ($sale_import_permission_active)
                            <li id="sale-import-menu"><a
                                    href="{{ url('sales/sale_by_csv') }}">{{ trans('Import Orders ') }}</a></li>
                        @endif
                        @if ($sale_index_permission_active)
                            <li id="sale-list-menu"><a href="{{ route('sales.index') }}">{{ trans('Order List') }}</a>
                            </li>
                        @endif
                        @if ($sale_tracking_code_permission_active)
                            <li id="sale-tracking-menu"><a
                                    href="{{ url('sales/tracking') }}">{{ trans('Import Tracking Code') }}</a></li>
                        @endif
                    </ul>
                </li>
            @endif
            <?php
            $index_permission_active = in_array('expenses-index', $permission_names);
            $income_index_permission_active = in_array('incomes-index', $permission_names);
            ?>
            @if ($index_permission_active)
                <!-- <li><a href="#expense" aria-expanded="false" data-toggle="collapse"> <i class="dripicons-wallet"></i><span>{{ trans('file.Expense') }}</span></a>
            <ul id="expense" class="collapse list-unstyled ">
                <li id="exp-cat-menu"><a href="{{ route('expense_categories.index') }}">{{ trans('file.Expense Category') }}</a></li>
                <li id="exp-list-menu"><a href="{{ route('expenses.index') }}">{{ trans('file.Expense List') }}</a></li>
                <?php
                $add_permission_active = $role_has_permissions_list->where('name', 'expenses-add')->first();
                ?>
                @if ($add_permission_active)
<li><a id="add-expense" href=""> {{ trans('file.Add Expense') }}</a></li>
@endif
            </ul>
            </li> -->
            @endif
            @if ($income_index_permission_active)
                <li><a href="#income" aria-expanded="false" data-toggle="collapse"> <i
                            class="dripicons-rocket"></i><span>{{ trans('file.Income') }}</span></a>
                    <ul id="income" class="collapse list-unstyled ">
                        <li id="income-cat-menu"><a
                                href="{{ route('income_categories.index') }}">{{ trans('file.Income Category') }}</a>
                        </li>
                        <li id="income-list-menu"><a
                                href="{{ route('incomes.index') }}">{{ trans('file.Income List') }}</a></li>
                        <?php
                        $income_add_permission_active = $role_has_permissions_list->where('name', 'incomes-add')->first();
                        ?>
                        @if ($income_add_permission_active)
                            <li><a id="add-income" href=""> {{ trans('file.Add Income') }}</a></li>
                        @endif
                    </ul>
                </li>
            @endif
            <?php
            $index_permission_active = in_array('quotes-index', $permission_names);
            ?>
            @if ($index_permission_active)
                <!-- <li><a href="#quotation" aria-expanded="false" data-toggle="collapse"> <i class="dripicons-document"></i><span>{{ trans('file.Quotation') }}</span><span></a>
            <ul id="quotation" class="collapse list-unstyled ">
                <li id="quotation-list-menu"><a href="{{ route('quotations.index') }}">{{ trans('file.Quotation List') }}</a></li>
                <?php
                $add_permission_active = $role_has_permissions_list->where('name', 'quotes-add')->first();
                ?>
                @if ($add_permission_active)
<li id="quotation-create-menu"><a href="{{ route('quotations.create') }}">{{ trans('file.Add Quotation') }}</a></li>
@endif
            </ul>
            </li> -->
            @endif
            <?php
            $index_permission_active = in_array('transfers-index', $permission_names);
            ?>
            @if ($index_permission_active)
                <li><a href="#transfer" aria-expanded="false" data-toggle="collapse"> <i
                            class="dripicons-export"></i><span>{{ trans('file.Transfer') }}</span></a>
                    <ul id="transfer" class="collapse list-unstyled ">
                        <li id="transfer-list-menu"><a
                                href="{{ route('transfers.index') }}">{{ trans('file.Transfer List') }}</a></li>
                        <?php
                        $add_permission_active = $role_has_permissions_list->where('name', 'transfers-add')->first();
                        ?>
                        @if ($add_permission_active)
                            <li id="transfer-create-menu"><a
                                    href="{{ route('transfers.create') }}">{{ trans('file.Add Transfer') }}</a></li>
                            <li id="transfer-import-menu"><a
                                    href="{{ url('transfers/transfer_by_csv') }}">{{ trans('file.Import Transfer By CSV') }}</a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            <?php
            $sale_return_index_permission_active = in_array('sale-report', $permission_names);
            ?>
            @if ($sale_return_index_permission_active)
                <li id="sale-return-menu"><a href="{{ route('return-sale.index') }}"><i
                            class="dripicons-return"></i><span>{{ trans('Reported Order') }}</span></a></li>
            @endif
            <?php
            $index_permission_active = in_array('account-index', $permission_names);
            
            $money_transfer_permission_active = in_array('money-transfer', $permission_names);
            
            $balance_sheet_permission_active = in_array('balance-sheet', $permission_names);
            
            $account_statement_permission_active = in_array('account-statement', $permission_names);
            
            ?>
            @if (
                $index_permission_active ||
                    $balance_sheet_permission_active ||
                    $account_statement_permission_active ||
                    $money_transfer_permission_active)
                <li class=""><a href="#account" aria-expanded="false" data-toggle="collapse"> <i
                            class="dripicons-briefcase"></i><span>{{ trans('file.Accounting') }}</span></a>
                    <ul id="account" class="collapse list-unstyled ">
                        @if ($index_permission_active)
                            <li id="account-list-menu"><a
                                    href="{{ route('accounts.index') }}">{{ trans('file.Account List') }}</a></li>
                            <li><a id="add-account" href="">{{ trans('file.Add Account') }}</a></li>
                        @endif
                        @if ($money_transfer_permission_active)
                            <li id="money-transfer-menu"><a
                                    href="{{ route('money-transfers.index') }}">{{ trans('file.Money Transfer') }}</a>
                            </li>
                        @endif
                        @if ($balance_sheet_permission_active)
                            <li id="balance-sheet-menu"><a
                                    href="{{ route('accounts.balancesheet') }}">{{ trans('file.Balance Sheet') }}</a>
                            </li>
                        @endif
                        @if ($account_statement_permission_active)
                            <li id="account-statement-menu"><a id="account-statement"
                                    href="">{{ trans('file.Account Statement') }}</a></li>
                        @endif
                    </ul>
                </li>
            @endif
            <?php
            $department_active = in_array('department', $permission_names);
            
            $index_employee_active = in_array('employees-index', $permission_names);
            
            $attendance_active = in_array('attendance', $permission_names);
            
            $payroll_active = in_array('payroll', $permission_names);
            
            $holiday_active = in_array('holiday', $permission_names);
            ?>

            @if ($department_active || $index_employee_active || $attendance_active || $payroll_active || $holiday_active)
                <li class=""><a href="#hrm" aria-expanded="false" data-toggle="collapse"> <i
                            class="dripicons-user-group"></i><span>HRM</span></a>
                    <ul id="hrm" class="collapse list-unstyled ">
                        @if ($department_active)
                            <li id="dept-menu"><a
                                    href="{{ route('departments.index') }}">{{ trans('file.Department') }}</a></li>
                        @endif
                        @if ($index_employee_active)
                            <li id="employee-menu"><a
                                    href="{{ route('employees.index') }}">{{ trans('file.Employee') }}</a></li>
                        @endif
                        @if ($attendance_active)
                            <li id="attendance-menu"><a
                                    href="{{ route('attendance.index') }}">{{ trans('file.Attendance') }}</a></li>
                        @endif
                        @if ($payroll_active)
                            <li id="payroll-menu"><a
                                    href="{{ route('payroll.index') }}">{{ trans('file.Payroll') }}</a></li>
                        @endif
                        @if ($holiday_active)
                            <li id="holiday-menu"><a
                                    href="{{ route('holidays.index') }}">{{ trans('file.Holiday') }}</a></li>
                        @endif
                    </ul>
                </li>
            @endif
            <?php
            
            $user_index_permission_active = in_array('users-index', $permission_names);
            
            $customer_index_permission_active = in_array('customers-index', $permission_names);
            
            $biller_index_permission_active = in_array('billers-index', $permission_names);
            
            $supplier_index_permission_active = in_array('suppliers-index', $permission_names);
            
            ?>
            @if (
                $user_index_permission_active ||
                    $customer_index_permission_active ||
                    $biller_index_permission_active ||
                    $supplier_index_permission_active)
                <li><a href="#people" aria-expanded="false" data-toggle="collapse"> <i
                            class="dripicons-user"></i><span>{{ trans('file.People') }}</span></a>
                    <ul id="people" class="collapse list-unstyled ">

                        @if ($user_index_permission_active)
                            <li id="user-list-menu"><a
                                    href="{{ route('user.index') }}">{{ trans('file.User List') }}</a></li>
                            <?php
                            $user_add_permission_active = $role_has_permissions_list->where('name', 'users-add')->first();
                            ?>
                            @if ($user_add_permission_active)
                                <li id="user-create-menu"><a
                                        href="{{ route('user.create') }}">{{ trans('file.Add User') }}</a></li>
                            @endif
                        @endif

                        @if ($customer_index_permission_active)
                            <li id="customer-list-menu"><a
                                    href="{{ route('customer.index') }}">{{ trans('file.Customer List') }}</a></li>
                            <?php
                            $customer_add_permission_active = $role_has_permissions_list->where('name', 'customers-add')->first();
                            ?>
                            @if ($customer_add_permission_active)
                                <li id="customer-create-menu"><a
                                        href="{{ route('customer.create') }}">{{ trans('file.Add Customer') }}</a>
                                </li>
                            @endif
                        @endif

                        @if ($biller_index_permission_active)
                            <li id="biller-list-menu"><a
                                    href="{{ route('biller.index') }}">{{ trans('file.Biller List') }}</a></li>
                            <?php
                            $biller_add_permission_active = $role_has_permissions_list->where('name', 'billers-add')->first();
                            ?>
                            @if ($biller_add_permission_active)
                                <li id="biller-create-menu"><a
                                        href="{{ route('biller.create') }}">{{ trans('file.Add Biller') }}</a></li>
                            @endif
                        @endif

                        @if ($supplier_index_permission_active)
                            <li id="supplier-list-menu"><a
                                    href="{{ route('supplier.index') }}">{{ trans('file.Supplier List') }}</a></li>
                            <?php
                            $supplier_add_permission_active = $role_has_permissions_list->where('name', 'suppliers-add')->first();
                            ?>
                            @if ($supplier_add_permission_active)
                                <li id="supplier-create-menu"><a
                                        href="{{ route('supplier.create') }}">{{ trans('file.Add Supplier') }}</a>
                                </li>
                            @endif
                        @endif
                    </ul>
                </li>
            @endif

            <?php
            
            $profit_loss_active = in_array('profit-loss', $permission_names);
            $best_seller_active = in_array('best-seller', $permission_names);
            $warehouse_report_active = in_array('warehouse-report', $permission_names);
            $warehouse_stock_report_active = in_array('warehouse-stock-report', $permission_names);
            $product_report_active = in_array('product-report', $permission_names);
            $daily_sale_active = in_array('daily-sale', $permission_names);
            $monthly_sale_active = in_array('monthly-sale', $permission_names);
            $daily_purchase_active = in_array('daily-purchase', $permission_names);
            $monthly_purchase_active = in_array('monthly-purchase', $permission_names);
            $purchase_report_active = in_array('purchase-report', $permission_names);
            
            $sale_report_chart_active = in_array('sale-report-chart', $permission_names);
            
            $payment_report_active = in_array('payment-report', $permission_names);
            
            $product_expiry_report_active = in_array('product-expiry-report', $permission_names);
            
            $product_qty_alert_active = in_array('product-qty-alert', $permission_names);
            
            $dso_report_active = in_array('dso-report', $permission_names);
            
            $user_report_active = in_array('user-report', $permission_names);
            
            $biller_report_active = in_array('biller-report', $permission_names);
            
            $customer_report_active = in_array('customer-report', $permission_names);
            
            $supplier_report_active = in_array('supplier-report', $permission_names);
            
            $due_report_active = in_array('due-report', $permission_names);
            
            $supplier_due_report_active = in_array('supplier-due-report', $permission_names);
            
            ?>
            @if (
                $profit_loss_active ||
                    $best_seller_active ||
                    $warehouse_report_active ||
                    $warehouse_stock_report_active ||
                    $product_report_active ||
                    $daily_sale_active ||
                    $monthly_sale_active ||
                    $daily_purchase_active ||
                    $monthly_purchase_active ||
                    $purchase_report_active ||
                    $sale_report_chart_active ||
                    $payment_report_active ||
                    $product_expiry_report_active ||
                    $product_qty_alert_active ||
                    $dso_report_active ||
                    $user_report_active ||
                    $biller_report_active ||
                    $customer_report_active ||
                    $supplier_report_active ||
                    $due_report_active ||
                    $supplier_due_report_active)
                <li><a href="#report" aria-expanded="false" data-toggle="collapse"> <i
                            class="dripicons-document-remove"></i><span>{{ trans('file.Reports') }}</span></a>
                    <ul id="report" class="collapse list-unstyled ">
                        @if ($profit_loss_active)
                            <li id="profit-loss-report-menu">
                                {!! Form::open(['route' => 'report.profitLoss', 'method' => 'post', 'id' => 'profitLoss-report-form']) !!}
                                <input type="hidden" name="start_date" value="{{ date('Y-m') . '-' . '01' }}" />
                                <input type="hidden" name="end_date" value="{{ date('Y-m-d') }}" />
                                <a id="profitLoss-link" href="">{{ trans('file.Summary Report') }}</a>
                                {!! Form::close() !!}
                            </li>
                        @endif
                        @if ($best_seller_active)
                            <li id="best-seller-report-menu">
                                <a href="{{ url('report/best_seller') }}">{{ trans('file.Best Seller') }}</a>
                            </li>
                        @endif
                        @if ($product_report_active)
                            <li id="product-report-menu">
                                {!! Form::open(['route' => 'report.product', 'method' => 'get', 'id' => 'product-report-form']) !!}
                                <input type="hidden" name="start_date" value="{{ date('Y-m') . '-' . '01' }}" />
                                <input type="hidden" name="end_date" value="{{ date('Y-m-d') }}" />
                                <input type="hidden" name="warehouse_id" value="0" />
                                <a id="report-link" href="">{{ trans('file.Product Report') }}</a>
                                {!! Form::close() !!}
                            </li>
                        @endif
                        @if ($daily_sale_active)
                            <li id="daily-sale-report-menu">
                                <a
                                    href="{{ url('report/daily_sale/' . date('Y') . '/' . date('m')) }}">{{ trans('file.Daily Sale') }}</a>
                            </li>
                        @endif
                        @if ($monthly_sale_active)
                            <li id="monthly-sale-report-menu">
                                <a
                                    href="{{ url('report/monthly_sale/' . date('Y')) }}">{{ trans('file.Monthly Sale') }}</a>
                            </li>
                        @endif
                        @if ($daily_purchase_active)
                            <li id="daily-purchase-report-menu">
                                <a
                                    href="{{ url('report/daily_purchase/' . date('Y') . '/' . date('m')) }}">{{ trans('file.Daily Purchase') }}</a>
                            </li>
                        @endif
                        @if ($monthly_purchase_active)
                            <li id="monthly-purchase-report-menu">
                                <a
                                    href="{{ url('report/monthly_purchase/' . date('Y')) }}">{{ trans('file.Monthly Purchase') }}</a>
                            </li>
                        @endif
                        @if ($sale_report_chart_active)
                            <li id="sale-report-chart-menu">
                                {!! Form::open(['route' => 'report.saleChart', 'method' => 'post', 'id' => 'sale-report-chart-form']) !!}
                                <input type="hidden" name="start_date" value="{{ date('Y-m') . '-' . '01' }}" />
                                <input type="hidden" name="end_date" value="{{ date('Y-m-d') }}" />
                                <input type="hidden" name="warehouse_id" value="0" />
                                <input type="hidden" name="time_period" value="weekly" />
                                <a id="sale-report-chart-link"
                                    href="">{{ trans('file.Sale Report Chart') }}</a>
                                {!! Form::close() !!}
                            </li>
                        @endif
                        @if ($payment_report_active)
                            <li id="payment-report-menu">
                                {!! Form::open(['route' => 'report.paymentByDate', 'method' => 'post', 'id' => 'payment-report-form']) !!}
                                <input type="hidden" name="start_date" value="{{ date('Y-m') . '-' . '01' }}" />
                                <input type="hidden" name="end_date" value="{{ date('Y-m-d') }}" />
                                <a id="payment-report-link" href="">{{ trans('file.Payment Report') }}</a>
                                {!! Form::close() !!}
                            </li>
                        @endif
                        @if ($purchase_report_active)
                            <li id="purchase-report-menu">
                                {!! Form::open(['route' => 'report.purchase', 'method' => 'post', 'id' => 'purchase-report-form']) !!}
                                <input type="hidden" name="start_date" value="{{ date('Y-m') . '-' . '01' }}" />
                                <input type="hidden" name="end_date" value="{{ date('Y-m-d') }}" />
                                <input type="hidden" name="warehouse_id" value="0" />
                                <a id="purchase-report-link" href="">{{ trans('file.Purchase Report') }}</a>
                                {!! Form::close() !!}
                            </li>
                        @endif
                        @if ($customer_report_active)
                            <li id="customer-report-menu">
                                <a id="customer-report-link" href="">{{ trans('file.Customer Report') }}</a>
                            </li>
                        @endif
                        @if ($customer_report_active)
                            <li id="customer-report-menu">
                                <a id="customer-group-report-link"
                                    href="">{{ trans('file.Customer Group Report') }}</a>
                            </li>
                        @endif
                        @if ($due_report_active)
                            <li id="due-report-menu">
                                {!! Form::open(['route' => 'report.customerDueByDate', 'method' => 'post', 'id' => 'customer-due-report-form']) !!}
                                <input type="hidden" name="start_date"
                                    value="{{ date('Y-m-d', strtotime('-1 year')) }}" />
                                <input type="hidden" name="end_date" value="{{ date('Y-m-d') }}" />
                                <a id="due-report-link" href="">{{ trans('file.Customer Due Report') }}</a>
                                {!! Form::close() !!}
                            </li>
                        @endif
                        @if ($supplier_report_active)
                            <li id="supplier-report-menu">
                                {!! Form::open(['route' => 'report.supplier', 'method' => 'post', 'id' => 'supplier-due-report-form']) !!}
                                <a id="supplier-report-link" href="">{{ trans('file.Supplier Report') }}</a>
                                {!! Form::close() !!}
                            </li>
                        @endif
                        @if ($supplier_due_report_active)
                            <!-- <li id="supplier-due-report-menu">
                    {!! Form::open(['route' => 'report.supplierDueByDate', 'method' => 'post', 'id' => 'supplier-due-report-form']) !!}
                    <input type="hidden" name="start_date" value="{{ date('Y-m-d', strtotime('-1 year')) }}" />
                    <input type="hidden" name="end_date" value="{{ date('Y-m-d') }}" />
                    <a id="supplier-due-report-link" href="">{{ trans('file.Supplier Due Report') }}</a>
                    {!! Form::close() !!}
                </li> -->
                        @endif
                        @if ($warehouse_report_active)
                            <li id="warehouse-report-menu">
                                <a id="warehouse-report-link"
                                    href="">{{ trans('file.Warehouse Report') }}</a>
                            </li>
                        @endif
                        @if ($warehouse_stock_report_active)
                            <li id="warehouse-stock-report-menu">
                                <a
                                    href="{{ route('report.warehouseStock') }}">{{ trans('file.Warehouse Stock Chart') }}</a>
                            </li>
                        @endif
                        @if ($product_expiry_report_active)
                            <li id="productExpiry-report-menu">
                                <a
                                    href="{{ route('report.productExpiry') }}">{{ trans('file.Product Expiry Report') }}</a>
                            </li>
                        @endif
                        @if ($product_qty_alert_active)
                            <li id="qtyAlert-report-menu">
                                <a
                                    href="{{ route('report.qtyAlert') }}">{{ trans('file.Product Quantity Alert') }}</a>
                            </li>
                        @endif
                        @if ($dso_report_active)
                            <li id="daily-sale-objective-menu">
                                <a
                                    href="{{ route('report.dailySaleObjective') }}">{{ trans('file.Daily Sale Objective Report') }}</a>
                            </li>
                        @endif
                        @if ($user_report_active)
                            <li id="user-report-menu">
                                <a id="user-report-link" href="">{{ trans('file.User Report') }}</a>
                            </li>
                        @endif
                        @if ($biller_report_active)
                            <li id="biller-report-menu">
                                <a id="biller-report-link" href="">{{ trans('file.Biller Report') }}</a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            <li><a href="#setting" aria-expanded="false" data-toggle="collapse"> <i
                        class="dripicons-gear"></i><span>{{ trans('file.settings') }}</span></a>
                <ul id="setting" class="collapse list-unstyled ">
                    <?php
                    $all_notification_permission_active = $role_has_permissions_list->where('name', 'all_notification')->first();
                    
                    $send_notification_permission_active = $role_has_permissions_list->where('name', 'send_notification')->first();
                    
                    $warehouse_permission_active = $role_has_permissions_list->where('name', 'warehouse')->first();
                    
                    $customer_group_permission_active = $role_has_permissions_list->where('name', 'customer_group')->first();
                    
                    $brand_permission_active = $role_has_permissions_list->where('name', 'brand')->first();
                    
                    $unit_permission_active = $role_has_permissions_list->where('name', 'unit')->first();
                    
                    $currency_permission_active = $role_has_permissions_list->where('name', 'currency')->first();
                    
                    $tax_permission_active = $role_has_permissions_list->where('name', 'tax')->first();
                    
                    $general_setting_permission_active = $role_has_permissions_list->where('name', 'general_setting')->first();
                    
                    $backup_database_permission_active = $role_has_permissions_list->where('name', 'backup_database')->first();
                    
                    $mail_setting_permission_active = $role_has_permissions_list->where('name', 'mail_setting')->first();
                    
                    $sms_setting_permission_active = $role_has_permissions_list->where('name', 'sms_setting')->first();
                    
                    $create_sms_permission_active = $role_has_permissions_list->where('name', 'create_sms')->first();
                    
                    $pos_setting_permission_active = $role_has_permissions_list->where('name', 'pos_setting')->first();
                    
                    $hrm_setting_permission_active = $role_has_permissions_list->where('name', 'hrm_setting')->first();
                    
                    $reward_point_setting_permission_active = $role_has_permissions_list->where('name', 'reward_point_setting')->first();
                    
                    $discount_plan_permission_active = $role_has_permissions_list->where('name', 'discount_plan')->first();
                    
                    $discount_permission_active = $role_has_permissions_list->where('name', 'discount')->first();
                    
                    $custom_field_permission_active = $role_has_permissions_list->where('name', 'custom_field')->first();
                    ?>
                    @if ($role->id <= 2)
                        <li id="role-menu"><a
                                href="{{ route('role.index') }}">{{ trans('file.Role Permission') }}</a></li>
                        <li><a href="{{ route('smstemplates.index') }}">{{ trans('file.SMS Template') }}</a></li>
                        @if ($custom_field_permission_active)
                            <li id="custom-field-list-menu"><a
                                    href="{{ route('custom-fields.index') }}">{{ trans('file.Custom Field List') }}</a>
                            </li>
                        @endif
                    @endif
                    @if ($discount_plan_permission_active)
                        <li id="discount-plan-list-menu"><a
                                href="{{ route('discount-plans.index') }}">{{ trans('file.Discount Plan') }}</a>
                        </li>
                    @endif
                    @if ($discount_permission_active)
                        <li id="discount-list-menu"><a
                                href="{{ route('discounts.index') }}">{{ trans('file.Discount') }}</a></li>
                    @endif
                    @if ($all_notification_permission_active)
                        <li id="notification-list-menu">
                            <a href="{{ route('notifications.index') }}">{{ trans('file.All Notification') }}</a>
                        </li>
                    @endif
                    @if ($send_notification_permission_active)
                        <li id="notification-menu">
                            <a href="" id="send-notification">{{ trans('file.Send Notification') }}</a>
                        </li>
                    @endif
                    @if ($warehouse_permission_active)
                        <li id="warehouse-menu"><a
                                href="{{ route('warehouse.index') }}">{{ trans('file.Warehouse') }}</a></li>
                    @endif

                    @if ($customer_group_permission_active)
                        <li id="customer-group-menu"><a
                                href="{{ route('customer_group.index') }}">{{ trans('file.Customer Group') }}</a>
                        </li>
                    @endif
                    @if ($brand_permission_active)
                        <li id="brand-menu"><a href="{{ route('brand.index') }}">{{ trans('file.Brand') }}</a>
                        </li>
                    @endif
                    @if ($unit_permission_active)
                        <li id="unit-menu"><a href="{{ route('unit.index') }}">{{ trans('file.Unit') }}</a></li>
                    @endif
                    @if ($currency_permission_active)
                        <li id="currency-menu"><a
                                href="{{ route('currency.index') }}">{{ trans('file.Currency') }}</a></li>
                    @endif
                    @if ($tax_permission_active)
                        <li id="tax-menu"><a href="{{ route('tax.index') }}">{{ trans('file.Tax') }}</a></li>
                    @endif
                    <li id="user-menu"><a
                            href="{{ route('user.profile', ['id' => Auth::id()]) }}">{{ trans('file.User Profile') }}</a>
                    </li>
                    @if ($create_sms_permission_active)
                        <li id="create-sms-menu"><a
                                href="{{ route('setting.createSms') }}">{{ trans('file.Create SMS') }}</a></li>
                    @endif
                    @if ($backup_database_permission_active)
                        <li><a href="{{ route('setting.backup') }}">{{ trans('file.Backup Database') }}</a></li>
                    @endif
                    @if ($general_setting_permission_active)
                        <li id="general-setting-menu"><a
                                href="{{ route('setting.general') }}">{{ trans('file.General Setting') }}</a></li>
                    @endif
                    @if ($mail_setting_permission_active)
                        <li id="mail-setting-menu"><a
                                href="{{ route('setting.mail') }}">{{ trans('file.Mail Setting') }}</a></li>
                    @endif
                    @if ($reward_point_setting_permission_active)
                        <li id="reward-point-setting-menu"><a
                                href="{{ route('setting.rewardPoint') }}">{{ trans('file.Reward Point Setting') }}</a>
                        </li>
                    @endif
                    @if ($sms_setting_permission_active)
                        <li id="sms-setting-menu"><a
                                href="{{ route('setting.sms') }}">{{ trans('file.SMS Setting') }}</a></li>
                    @endif

                    @if ($pos_setting_permission_active)
                    @endif
                    @if ($hrm_setting_permission_active)
                        <li id="hrm-setting-menu"><a href="{{ route('setting.hrm') }}">
                                {{ trans('file.HRM Setting') }}</a></li>
                    @endif

                    <li id="languages"><a href="{{ url('languages/') }}"> {{ trans('file.Languages') }}</a></li>
                </ul>
            </li>
        </ul>
