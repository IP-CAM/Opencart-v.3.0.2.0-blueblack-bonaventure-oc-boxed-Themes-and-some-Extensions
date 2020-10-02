<?php
class ControllerCommonSearch extends Controller {

			public function items() {
            $this->load->model('catalog/product');
            $this->load->model('catalog/category');
            $this->load->model('tool/image');

			$data = array();
            if (isset($this->request->get['search'])) {
                $search = trim($this->request->get['search']);
            } else {
                $search = '';
            }
			if (isset($this->request->get['include_cats'])) {
                $includeCats = $this->request->get['include_cats'];
            } else {
                $includeCats = false;
            }
            if ($search != '') {
				$this->load->model('extension/module/brilliant_search');
				$matchedIds = $this->model_extension_module_brilliant_search->getSimilarItems($this->config->get('config_language_id'), $search, true);
                $filter_data = array(
                    'filter_name' => $search,
                    'start' => 0,
                    'limit' => $this->config->get('module_brilliant_search_ajax_max_results_cats'),
					'category_ids' => $matchedIds['category_ids']
                );
				$matchedIds = $this->model_extension_module_brilliant_search->getSimilarItems($this->config->get('config_language_id'), $search);				
				if($this->config->get('module_brilliant_search_include_categories') == 1){
        	$categories = $this->model_extension_module_brilliant_search->getCategoriesDetailed($filter_data);
				}else{
					$categories = array();
				}

				$remaining = $this->config->get('module_brilliant_search_ajax_max_results_total') - count($categories);
				if ($remaining > 0) {
					$filter_data = array(
						'filter_name' => $search,
						'start' => 0,
						'limit' => ($remaining > $this->config->get('module_brilliant_search_ajax_max_results_prods') ? $this->config->get('module_brilliant_search_ajax_max_results_prods') : $remaining),
						'product_ids' => $matchedIds['product_ids']
					);

					$products = $this->model_catalog_product->getProducts($filter_data);
				} else {
					$products   = array();
				}
            } else {
                $categories = array();
                $products   = array();
            }

            $data['categories'] = array();
            foreach ($categories as $category) {
                if ($category['image']) {
                    $image = $this->model_tool_image->resize($category['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
                } else {
                    $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
                }
                $data['categories'][] = array (
                    'category_id' => $category['category_id'],
                    'thumb'       => $image,
                    'name'        => $category['name'],
                    'href'        => $this->url->link('product/category', 'path=' . $category['category_id'])
                );
            }

            $data['products'] = array();
            foreach ($products as $product) {
                if ($product['image']) {
                    $image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
                } else {
                    $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
                }

                if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $price = false;
                }

                if ((float)$product['special']) {
                    $special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $special = false;
                }
                $data['products'][] = array (
                    'product_id'  => $product['product_id'],
                    'thumb'       => $image,
                    'name'        => $product['name'],
                    'price'       => $price,
                    'special'     => $special,
                    'href'        => $this->url->link('product/product', 'product_id=' . $product['product_id'])
                );
            }
            header('Content-Type: application/json');
            header('Content-Type: text/html; charset=utf-8');
            echo json_encode($data);
	}
	public function index() {
		$this->load->language('common/search');

		$data['text_search'] = $this->language->get('text_search');

		if (isset($this->request->get['search'])) {
			$data['search'] = $this->request->get['search'];
		} else {
			$data['search'] = '';
		}

		$data['search_action'] = $this->url->link('common/search/items', '', 'SSL');
                $data['text_categories'] = $this->language->get('text_categories');
                $data['text_products'] = $this->language->get('text_products');
                $data['ajax_type_delay'] = $this->config->get('module_brilliant_search_ajax_type_delay');
                $data['ajax_max_results_total'] = $this->config->get('module_brilliant_search_ajax_max_results_total');
                $data['include_categories'] = $this->config->get('module_brilliant_search_include_categories');

		return $this->load->view('common/search', $data);
	}
}