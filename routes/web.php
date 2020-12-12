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

Route::post('/send-invoice-email', 'SendEmailController@sendInvoiceEmail');
Route::post('/invoices/update/{id}', 'InvoicesController@update');
//


Route::resource('/roles','RoleController');
Route::resource('/users','UserController');
Route::resource('/customers','CustomersController', ['only' => ['show']]);
Route::resource('/customers-invoices','CustomerInvoiceController', ['only' => ['index', 'store', 'create']]);
Route::resource('/salespeople','SalespeopleController');
Route::resource('/invoices','InvoicesController', ['only' => ['show']]);

Route::get('/datatables.data', 'CustomerInvoiceController@anyData');
Route::get('/dashboard', 'CustomerInvoiceController@index');
Route::get('/dashboard', 'CustomerInvoiceController@index');



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
Route::get('/testview/{id}','InvoicesController@testview');







