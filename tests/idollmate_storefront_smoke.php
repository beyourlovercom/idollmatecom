<?php
/** Isolated presentation-only test, no WordPress/DB/network. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
$GLOBALS['actions'] = [];
$GLOBALS['filters'] = [];
$GLOBALS['view'] = '';
$GLOBALS['is_admin'] = false;
$GLOBALS['loaded_styles'] = [];
function add_action($name,$cb,$priority=10):void{$GLOBALS['actions'][$name][]=$cb;}
function add_filter($name,$cb,$priority=10):void{$GLOBALS['filters'][$name][]=$cb;}
function is_admin():bool{return $GLOBALS['is_admin'];}
function is_product():bool{return $GLOBALS['view']==='product';}
function is_shop():bool{return $GLOBALS['view']==='shop';}
function is_product_category():bool{return $GLOBALS['view']==='category';}
function is_product_tag():bool{return $GLOBALS['view']==='tag';}
function is_cart():bool{return $GLOBALS['view']==='cart';}
function is_checkout():bool{return $GLOBALS['view']==='checkout';}
function plugins_url(string $path,string $file):string{return '/plugins/idollmate-storefront/'.$path;}
function wp_enqueue_style($handle,$url,$deps,$version):void{$GLOBALS['loaded_styles'][]=[$handle,$url,$deps,$version];}
function check(bool $valid,string $name):void{if(!$valid){fwrite(STDERR,"FAILED $name\n");exit(1);}echo "PASS $name\n";}
require dirname(__DIR__).'/wordpress/plugins/idollmate-storefront/idollmate-storefront.php';
check(count($GLOBALS['actions']['wp_enqueue_scripts']??[])===1,'Style hook registered once');
check(count($GLOBALS['filters']['body_class']??[])===1,'Body style marker registered');
foreach(['product','shop','category','tag','cart','checkout'] as $name){
 $GLOBALS['view']=$name;$GLOBALS['loaded_styles']=[];
 check(idm_storefront_is_woo_view(),"Woo page recognized: $name");
 check(in_array('idm-storefront-ui',idm_storefront_body_class(['test']),true),"Woo body class: $name");
 idm_storefront_enqueue();
 check(count($GLOBALS['loaded_styles'])===1 && $GLOBALS['loaded_styles'][0][0]==='idollmate-storefront',"Enqueue only on Woo page: $name");
}
$GLOBALS['view']='page';$GLOBALS['loaded_styles']=[];
idm_storefront_enqueue();
check(!$GLOBALS['loaded_styles'],'No CSS enqueued on ordinary pages');
check(!in_array('idm-storefront-ui',idm_storefront_body_class([]),true),'No body class on ordinary pages');
$GLOBALS['is_admin']=true;$GLOBALS['view']='product';$GLOBALS['loaded_styles']=[];
idm_storefront_enqueue();
check(!$GLOBALS['loaded_styles'],'No styling in wp-admin');
$css=file_get_contents(dirname(__DIR__).'/wordpress/plugins/idollmate-storefront/assets/storefront.css');
check(str_contains($css,'body.idm-storefront-ui') && str_contains($css,'@media (max-width: 768px)'), 'Scoped mobile CSS present');
check(!preg_match('/(?:url\s*\(|@import|https?:\/\/)/i',$css),'No remote or downloaded images or fonts');
echo "ALL ISOLATED STOREFRONT SMOKE TESTS PASSED\n";
