<?php

namespace App\Http\Controllers\API;

use App\KmClasses\Curl\Shopify;
use App\Projects;
use App\Tags;

class LeadsShopifyController extends LeadsController
{
	protected function saveTags($input){
		$res = array();
		if(!empty($input['source'])){
			$tag_id = Tags::getFieldId($input['source']);
			if($tag_id){
				$res[$tag_id] = $tag_id;
				$this->saveTag($tag_id, $input);
			}
			if(!empty($input['product_id']) && $input['source'] == 'clickmart'){
				$shopify_tags = $this->getTagsFromShopify($input['product_id'], $input['source']);
				if(!empty($shopify_tags) && count($shopify_tags)){
					foreach($shopify_tags as $t){
						$tag_id = Tags::getFieldId($t);
						if($tag_id){
							$res[$tag_id] = $tag_id;
							$this->saveTag($tag_id, $input);
						}
					}
				}
			}
		}
		if(!empty($input['tags'])){
			$tags = explode(',', $input['tags']);
			if(!empty($tags) && is_array($tags) && count($tags)){
				foreach($tags as $t){
					$t = trim(substr($t,0, 99));
					if(!empty($t)){
						$tag_id = Tags::getFieldId($t);
						if($tag_id){
							$res[$tag_id] = $tag_id;
							$this->saveTag($tag_id, $input);
						}
					}
				}
			}
		}
		return $res;
	}

	public function getTagsFromShopify($product_id, $source){
		$tags = [];
		$products =  explode(',', $product_id);
		if($products && count($products)){
			$project = Projects::where('slug', $source)->first();
			if($project && $project->count()) {
				$config  = array(
					'ShopUrl'  => $project->shopify_url,
					'ApiKey'   => $project->api_key,
					'Password' => $project->api_pass,
					'Token'    => $project->token
				);
				$shopify = new Shopify( $config );
				if ( $shopify ) {
					foreach ( $products as $p ) {
						$p = $p * 1;
						$tags_array = $this->getProductTags($shopify, $p);
						if($tags_array && count($tags_array)){
							foreach($tags_array as $tag) {
								$tags[] = $source . '_' . $tag;
							}
						}
					}
					if(!empty($tags)){
						$tags = array_unique($tags);
					}
				}
			}
		}
		return $tags;
	}


	public function getProductTags(Shopify $shopify, $product_id, $looking_for = ['baby'], $default_tag = 'novelties'){
		$product = $shopify->getOneItem('products', $product_id, 'fields=tags');
		if(!empty($product) && !empty($product->product)){
			if(!empty($product->product->tags)){
				$tags = $product->product->tags;
				if($tags){
					$tags_array = explode(',', $tags);
					if($tags_array && count($tags_array)){
						$tags_res = [];
						foreach($tags_array as $tag){
							$tag = str_replace(' ', '_', strtolower(trim($tag)));
							foreach($looking_for as $lf) {
								if ( $tag && $tag == $lf ) {
									$tags_res[] = $lf;
								}
							}
						}
						if(!empty($tags_res) && count($tags_res)){
							return $tags_res;
						}
						return [$default_tag];
					}
					else{
						return [$default_tag];
					}
				}
				else{
					return [$default_tag];
				}
			}
			else{
				return [$default_tag];
			}
		}
		return false;
	}
}
