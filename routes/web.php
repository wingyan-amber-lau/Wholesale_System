<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/*
* Author: Amber Wing Yan Lau
* Description: Web Wholesale System
* Developed in 2019-2020
*/

Auth::routes(['register' => false]);
//page that need login
Route::group(['middleware' => 'auth'], function() {
//Route::group(array('before' => 'auth'), function() {
    Route::get('/',  'OrdersController@index');
    Route::get('order', 'OrdersController@index');
    Route::get('order/{invoice_code}', 'InvoicesController@show');
    Route::get('searchInvoice', function () {
        return view('search.searchInvoice',['districts' => App\District::all()]);
    });
    Route::get('searchReceipt', function () {
        return view('search.searchReceipt');
    });

    Route::get('receipt', 'ReceiptsController@index');
    Route::get('receipt/{receipt_code}', 'ReceiptsController@show');

    Route::get('inventory', function () {
        return view('pages.inventory');
    });

    Route::get('stat', function () {
        return view('pages.stat');
    });

    Route::get('settings', function () {
        return view('pages.settings');
    });
    Route::get('customerSettings', 'CustomersController@index');
    Route::get('districtSettings', 'DistrictsController@index');
    Route::get('categorySettings', 'CategoriesController@index');
    Route::get('productSettings', 'ProductsController@index');
    Route::get('supplierSettings', 'SuppliersController@index');
    Route::get('invoiceSettings', 'InvoicesController@Index');

    Route::get('invoiceResult', 'InvoicesController@getSearchResult');
    Route::get('receiptResult', 'ReceiptsController@getSearchResult');
    Route::get('printInvoice/{check_all}/{invoice_list?}/{invoice_code?}/{invoice_date?}/{delivery_date?}/{customer_code?}/{customer_name?}/{district_code?}/{car_no?}', 'InvoicesController@print');

    Route::get('checklist','ChecklistsController@getByDate');
    Route::get('preparationlist',function () {
        return view('pages.preparationlist',['districts' => App\District::all(),'delivery_date' => date('Y-m-d')]);
    });

    Route::get('dailySettlement','SettlementsController@getDailyByDate');
    Route::get('monthlyStatement','SettlementsController@showMonthlyStatementPage');

    Route::get('phoneList','CustomersController@getPhoneList');

    Route::get('product_code_autocomplete', 'ProductsController@autocomplete');
    Route::get('customer_code_autocomplete', 'CustomersController@autocomplete');
    Route::get('phone_autocomplete', 'CustomersController@phoneAutocomplete');
    Route::get('supplier_code_autocomplete', 'SuppliersController@autocomplete');
    

});

Route::post('getCustomerByID', 'CustomersController@getByID');
Route::post('saveCustomer', 'CustomersController@save');
Route::post('getCategoryByID', 'CategoriesController@getByID');
Route::post('getCategoryValueByCategoryID', 'CategoriesController@getCategoryValueByCategoryID');
Route::post('getCategoryValueByID', 'CategoriesController@getCategoryValueByID');
Route::post('saveCategory', 'CategoriesController@save');
Route::post('saveCategoryOrder', 'CategoriesController@saveCategoryOrder');
Route::post('saveCategoryValue', 'CategoriesController@saveCategoryValue');
Route::post('getDistrictByID', 'DistrictsController@getByID');
Route::post('getCustomerDeliveryOrderByDistrictID', 'CustomersController@getDeliveryOrderByDistrictID');
Route::post('saveDistrict', 'DistrictsController@save');
Route::post('saveCustomerDeliveryOrder', 'CustomersController@saveDeliveryOrder');
Route::post('getProductByID', 'ProductsController@getByID');
Route::post('saveProduct', 'ProductsController@save');
Route::post('getSupplierByID', 'SuppliersController@getByID');
Route::post('saveSupplier', 'SuppliersController@save');
Route::post('saveInvoiceSetting', 'InvoicesController@saveSetting');

Route::post('getcust', 'CustomersController@getCustomer');
Route::post('getprod', 'ProductsController@getProduct');
Route::post('saveinvoice', 'InvoicesController@save');
Route::post('nextinvoice', 'InvoicesController@getNext');
Route::post('previnvoice', 'InvoicesController@getPrev');
Route::post('voidInvoice', 'InvoicesController@void');
Route::post('getprodlastorderdate', 'ProductsController@getProductLast5Order');
Route::post('getDeliveryDate','InvoicesController@getDeliveryDateFromRequest');
Route::post('checkDuplicateProductOnSameDeliveryDate','ProductsController@checkDuplicateProductOnSameDeliveryDate');

Route::post('invoiceResult', 'InvoicesController@getSearchResult');

Route::post('getSupplier', 'SuppliersController@getSupplier');
Route::post('saveReceipt', 'ReceiptsController@save');
Route::post('nextReceipt', 'ReceiptsController@getNext');
Route::post('prevReceipt', 'ReceiptsController@getPrev');
//Route::post('voidReceipt', 'ReceiptsController@void');
Route::post('receiptResult', 'ReceiptsController@getSearchResult');



Route::post('checklist','ChecklistsController@getByDate');
Route::post('checklistChangeStatus','ChecklistsController@changeStatus');
Route::post('preparationlist','ProductsController@genPreparationList');

Route::post('updateDailySettlement','SettlementsController@updateDailySettlement');
Route::post('monthlyStatement','SettlementsController@monthlyStatements');





//testing routes
Route::get('getcustomer', 'CustomersController@index');
Route::get('getproduct', 'ProductsController@index');
Route::get('existInvoice', 'InvoicesController@exist');
Route::get('getDailyMaxInvoiceNumber', 'InvoicesController@getDailyMaxInvoiceNumber');
Route::get('getNext', 'InvoicesController@getNext');
Route::get('getPrev', 'InvoicesController@getPrev');
Route::get('insertinvoice', 'InvoicesController@insert');
Route::get('getDeliveryDate', 'InvoicesController@getDeliveryDate');
Route::get('hello', function(){
	echo greeting('John');
});
Route::get('generate-pdf','HomeController@generatePDF');
