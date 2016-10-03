<?php
/**
 * Created by PhpStorm.
 * User: Linas
 * Date: 2016-04-19
 * Time: 16:49
 */
 
 if($_GET['code']!='boGRl6A18tlT4GsWQlpP'){
	 header('Location: /');
 }

ini_set('memory_limit', '512M');
umask(0);

error_reporting(E_ALL);
@ini_set('display_errors',1);

require_once(dirname(__DIR__).'/config/config.inc.php');

/**
 * Get all available products
 *
 * @param integer $id_lang Language id
 * @param integer $start Start number
 * @param integer $limit Number of products to return
 * @param string $order_by Field for ordering
 * @param string $order_way Way for ordering (ASC or DESC)
 * @return array Products details
 */

$products = Product::getProducts($context->language->id, 0, 999999999, 'name', 'ASC', false, true);
$link = new Link();

$context->employee = 0;

$products_for_csv = array();
$first_product = array();
$i = 0;
$currency = Currency::getCurrency($context->currency);
$categories = array();
foreach($products as $product){
    $i++;
    if(count($first_product)==0 && $product['on_sale']==1) {
        $first_product = $product;
    }
    $id_image = Product::getCover($product['id_product']);
    $image = new Image($id_image['id_image']);
    $item_name = str_replace(array("'", "\"", "&quot;"), "'", $product['name'] );
    $item_description = str_replace(array("'", "\"", "&quot;"), "'", str_replace("\n", " ", strip_tags($product['description'] ) ) );
    $price = number_format($product['price'], 2)." ".$currency['iso_code'];

    $specific_price_output = null;
    $price_static = Product::getPriceStatic((int)intval($product['id_product']), true, null, 2, null, false, true, 1, false, null, 0, null, $specific_price_output, true, true, null, true);

    $categories[] = $product['id_category_default'];

    $sale_price = number_format($price_static,2)." ".$currency['iso_code'];
    $products_for_csv[] = array(
        'ID' => $product['reference'],
        'ID2' => $product['id_product'],
        'item_title' => $item_name,
        'final_url' => $link->getProductLink($product['id_product']),
        //'image_url' => _PS_BASE_URL_._THEME_PROD_DIR_.$image->getExistingImgPath().".jpg"
        'image_url' => 'http://'.$link->getImageLink($product['link_rewrite'],$id_image['id_image']),
        'item_subtitle' => $item_name,
        'item_description' => $item_description,
        'item_category'=>$product['id_category_default'],
        'price'=>$price,
        'sale_price'=>$sale_price,
        'contextual_keywords'=>$product['meta_keywords'],
        'item_address'=>'',
    );
}


$categories = Category::getCategoryInformations($categories);

$header_keys = array();

$new_array = array();

foreach(array_keys($products_for_csv[0]) as $key){
    $header_keys[] = str_replace("_"," ",ucfirst($key));
}

$new_array[] = $header_keys;


foreach($products_for_csv as $product_for_csv){
    $product_for_csv['item_category'] = $categories[$product_for_csv['item_category']]['name'];
    $new_array[] = $product_for_csv;
}

date_default_timezone_set('Europe/Vilnius');

if(isset($_GET['format']) && $_GET['format']=='csv'){
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=\"".$_SERVER['HTTP_HOST']."_feed_".date('Y-m-d_H:i:s').".csv\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    $out = fopen("php://output", 'w');

    foreach($new_array as $row){
        fputcsv($out, $row, ",");
    }
    fclose($out);


    exit();
}

require_once('PHPExcel.php');

$doc = new PHPExcel();
$doc->setActiveSheetIndex(0);
$doc->getActiveSheet()->fromArray($new_array, null, 'A1');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$_SERVER['HTTP_HOST'].'_feed_'.date('Y-m-d_H:i:s').'.xls');
header('Cache-Control: max-age=0');

// Do your stuff here
$writer = PHPExcel_IOFactory::createWriter($doc, 'Excel5');

$writer->save('php://output');

exit();