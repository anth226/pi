<?php

namespace App\Http\Controllers\API;

use App\Console\Commands\ProcessingShopify;
use App\Projects;
use App\ShopifyOrders;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
//use Illuminate\Support\Facades\Storage;

class OrdersWebhookController extends BaseController
{
	public function index()
	{

	}

	public function store(Request $request)
	{
//		return $this->saveOrder($request);
	}

	protected function saveOrder(Request $request){
//		Storage::put('file1.txt', json_encode($request->header()).$request->getContent());
		$headers = $request->header();
		$input = json_decode($request->getContent());

		if($headers && !empty($headers['x-shopify-shop-domain'])){
			$shopify_domen = $headers['x-shopify-shop-domain'][0];
			$project = Projects::where('shopify_url', $shopify_domen)->first();
			if($project && $input){
				$shop = $project->slug;
				$tags = $shop;
				if(!empty($headers['x-shopify-topic']) && !empty($headers['x-shopify-topic'][0])){
					if($headers['x-shopify-topic'][0] == 'orders/paid'){
						$tags .= ','.$shop.'_paidorder';
					}
					else {
						$tags .= ',' . $shop . '_' . str_replace( '/', '_', $headers['x-shopify-topic'][0] );
					}
				}
				if($input->id) {
					$res = ShopifyOrders::where('order_id', $input->id)->count();
					if(!$res) {
						$ps = new ProcessingShopify();
						$result = $ps->processOrder( $input, $shop, $tags );
						if($result){
							ShopifyOrders::create(['order_id' => $input->id]);
						}
					}
				}
			}
		}
		return $this->sendResponse([], '');
	}


}
