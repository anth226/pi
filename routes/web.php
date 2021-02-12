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

Route::get('/', function () {
	if(Auth::check()){
		return redirect('/dashboard');
	}
	else {
		return view( 'welcome' );
	}
});


Auth::routes(['register' => false, 'verify' => true]);
//Auth::routes(['verify' => true]);

Route::get('/test', 'TestController@index')->name('test');
//


//Ajax

Route::post('/send-invoice-email', 'SendEmailController@sendInvoiceEmail', ['middleware' => 'csrf']);
Route::post('/send-generatedinvoice-email', 'SendEmailController@sendGeneratedInvoiceEmail', ['middleware' => 'csrf']);
Route::post('/invoices/update/{id}', 'InvoicesController@update' ,  ['middleware' => 'csrf']);
Route::post('/generate-invoice', 'InvoiceGeneratorController@store' ,  ['middleware' => 'csrf']);
//


Route::resource('/roles','RoleController');
Route::resource('/users','UserController');
Route::resource('/levels','SalespeopleLevelsController', ['only' => ['store', 'create', 'update', 'edit', 'index']]);
Route::resource('/customers','CustomersController', ['only' => ['show']]);
Route::resource('/customers-invoices','CustomerInvoiceController', ['only' => ['store', 'create']]);
Route::resource('/salespeople','SalespeopleController');
Route::resource('/invoices','InvoicesController', ['only' => ['show', 'index']]);

Route::resource('/invoice-generator', 'InvoiceGeneratorController');

Route::get('/datatables.data', 'CustomerInvoiceController@anyData');
Route::get('/invoicesdatatables.data', 'InvoicesController@anyData');

Route::get('/spersondatatables.data', 'SalespeopleController@anyData');
Route::post('/spersonpayment', 'SalespeopleController@setPaid');
Route::post('/setpaid', 'SalespeopleController@setPaid');

Route::get('/reports/salespeople', 'SalespeopleReportsController@index');
Route::get('/spreportsdatatables.data', 'SalespeopleReportsController@anyData');

Route::get('/dashboard', 'InvoicesController@index');

Route::get('/email-templates', 'TemplatesController@index');
Route::get('/email-templates/templates/', 'TemplatesController@index')->name('templateList');
Route::get('/email-templates/templates/new', 'TemplatesController@select')->name('selectNewTemplate');
Route::get('/email-templates/templates/new/{type}/{name}/{skeleton}', 'TemplatesController@new')->name('newTemplate');
Route::get('/email-templates/templates/edit/{templatename}', 'TemplatesController@view')->name('viewTemplate');
Route::post('/email-templates/templates/new', 'TemplatesController@create')->name('createNewTemplate');
Route::post('/email-templates/templates/delete', 'TemplatesController@delete')->name('deleteTemplate');
Route::post('/email-templates/templates/update', 'TemplatesController@update')->name('updateTemplate');
Route::post('/email-templates/templates/preview', 'TemplatesController@previewTemplateMarkdownView')->name('previewTemplateMarkdownView');

Route::get('/email-templates/mailables/', 'MailablesController@index')->name('mailableList');
Route::get('/email-templates/mailables/view/{name}', 'MailablesController@viewMailable')->name('viewMailable');
Route::get('/email-templates/mailables/edit/template/{name}', 'MailablesController@editMailable')->name('editMailable');
Route::post('/email-templates/mailables/parse/template', 'MailablesController@parseTemplate')->name('parseTemplate');
Route::post('/email-templates/mailables/preview/template', 'MailablesController@previewMarkdownView')->name('previewMarkdownView');
Route::get('/email-templates/mailables/preview/template/previewerror', 'MailablesController@templatePreviewError')->name('templatePreviewError');
Route::get('/email-templates/mailables/preview/{name}', 'MailablesController@previewMailable')->name('previewMailable');
Route::get('/email-templates/mailables/new', 'MailablesController@createMailable')->name('createMailable');
Route::post('/email-templates/mailables/new', 'MailablesController@generateMailable')->name('generateMailable');
Route::post('/email-templates/mailables/delete', 'MailablesController@delete')->name('deleteMailable');

Route::get('/pdfview/{id}','InvoicesController@showPdf');
Route::get('/pdfdownload/{id}','InvoicesController@downloadPdf');
Route::get('/pdfviewforgeneratedinvoices/{id}','InvoiceGeneratorController@showPdf');
Route::get('/pdfdownloadforgeneratedinvoices/{id}','InvoiceGeneratorController@downloadPdf');
Route::get('/pdfdownloads/{title}','InvoiceGeneratorController@showFile');
Route::get('/testview/{id}','InvoicesController@testview');







