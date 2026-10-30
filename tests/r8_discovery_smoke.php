<?php
/** R8-D/E isolated no-DB / no-WordPress runtime safety checks. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
$GLOBALS['idm_hooks']=array('actions'=>array(),'filters'=>array());
$GLOBALS['idm_admin']=false;$GLOBALS['idm_archive']='shop';$GLOBALS['idm_checks']=0;
function add_action($name,$fn,$prio=10):void{$GLOBALS['idm_hooks']['actions'][$name][]=$fn;}
function add_filter($name,$fn,$prio=10):void{$GLOBALS['idm_hooks']['filters'][$name][]=$fn;}
function is_admin():bool{return $GLOBALS['idm_admin'];}
function is_shop():bool{return $GLOBALS['idm_archive']==='shop';}
function is_product_category():bool{return $GLOBALS['idm_archive']==='category';}
function get_query_var($name,$default=null){return $default;}
function wc_get_page_permalink($name):string{return 'https://dev.idollmate.com/shop/';}
function esc_url($v):string{return htmlspecialchars($v,ENT_QUOTES);}
function current_user_can($cap):bool{return false;}
function test($result,$name):void{
    if(!$result){fwrite(STDERR,"FAIL $name\n");exit(1);}
    $GLOBALS['idm_checks']++;echo "PASS $name\n";
}
require dirname(__DIR__).'/wordpress/plugins/idollmate-discovery/idollmate-discovery.php';

test(IDM_DISCOVERY_VERSION==='0.1.2','Plugin version');
test(count($GLOBALS['idm_hooks']['actions']['woocommerce_product_query']??[])===1,'Woo product query hook');
test(count($GLOBALS['idm_hooks']['actions']['woocommerce_before_shop_loop']??[])===1,'Native Woo filter UI hook');
test(count($GLOBALS['idm_hooks']['actions']['admin_menu']??[])===1,'Admin-only QA entry');
test(count($GLOBALS['idm_hooks']['actions']['woocommerce_single_product_summary']??[])===1,'Woo product details hook');
test(idm_discovery_material_from_raw(null)==='','Absent material no filter');
test(idm_discovery_material_from_raw('')==='','Blank material no filter');
test(idm_discovery_material_from_raw('tpe')==='tpe','TPE accepted');
test(idm_discovery_material_from_raw('silicone')==='silicone','Silicone accepted');
test(idm_discovery_material_from_raw('TPE')==='__invalid__','Noncanonical case rejected');
test(idm_discovery_material_from_raw(array('tpe'))==='__invalid__','Array injection rejected');
test(idm_discovery_material_from_raw('silicone<script>')==='__invalid__','XSS-like input rejected');
test(idm_discovery_material_from_raw(str_repeat('x',100))==='__invalid__','Long values rejected');
test(idm_discovery_category_slug('mini-dolls')==='mini-dolls','Approved category');
test(idm_discovery_category_slug('full-body-dolls')==='full-body-dolls','Approved full body');
test(idm_discovery_category_slug('another')==='','Unapproved category rejected');
$seed=array(array('taxonomy'=>'product_visibility','terms'=>array('exclude-from-catalog')));
test(idm_discovery_apply_material_tax_query($seed,'')===$seed,'No material preserves Woo terms');
$t=idm_discovery_apply_material_tax_query($seed,'tpe');
test(count($t)===2 && $t[0]===$seed[0] && $t[1]['terms']===array('tpe'),'Valid material preserves Woo visibility');
$i=idm_discovery_apply_material_tax_query($seed,'__invalid__');
test(count($i)===2 && $i[1]['terms']===array('idm-no-such-material'),'Invalid material fails closed');
class FakeQuery {
    public array $fields=array();
    public function is_main_query():bool{return true;}
    public function get($key){return $this->fields[$key]??null;}
    public function set($key,$value):void{$this->fields[$key]=$value;}
}
$q=new FakeQuery();
$_GET['idm_material']='silicone';
idm_discovery_product_query($q);
test($q->get('tax_query')[0]['terms']===array('silicone'),'Shop material Woo query applied');
$p=idm_discovery_pagination(array('add_args'=>array('orderby'=>'price')));
test(($p['add_args']['orderby']??'')==='price' && ($p['add_args']['idm_material']??'')==='silicone','Pagination retains filter & sort');
$r=idm_discovery_robots(array('nofollow'=>true));
test(isset($r['noindex']) && $r['nofollow']===true,'Filtered archive noindex preserves global directives');
ob_start();idm_discovery_canonical();$canonical=ob_get_clean();
test(str_contains($canonical,'https://dev.idollmate.com/shop/') && !str_contains($canonical,'idm_material'),'Canonical removes filter');
$GLOBALS['idm_admin']=true;$q=new FakeQuery();
idm_discovery_product_query($q);
test($q->fields===array(),'Admin Woo query untouched');
$GLOBALS['idm_admin']=false;$GLOBALS['idm_archive']='other';$q=new FakeQuery();
idm_discovery_product_query($q);
test($q->fields===array(),'Nonarchive query untouched');
$p=idm_discovery_pagination(array());
test($p===array(),'Nonarchive pagination untouched');
$r=idm_discovery_robots(array());
test($r===array(),'Nonarchive robots untouched');
ob_start();idm_discovery_canonical();$str=ob_get_clean();
test($str==='','Nonarchive canonical untouched');
$css=file_get_contents(dirname(__DIR__).'/wordpress/plugins/idollmate-discovery/assets/discovery.css');
test(str_contains($css,'.idm-discovery') && str_contains($css,'@media(max-width:720px)'),'Responsive scoped styling');
test(!preg_match('/(?:@import|url\s*\(|https?:\/\/)/i',$css),'No external asset fetches');
test(count($GLOBALS['idm_hooks']['filters']['query_loop_block_query_vars']??[])===1,
     'Woo Product Collection block query hook registered');
test(count($GLOBALS['idm_hooks']['filters']['render_block_woocommerce/product-collection']??[])===1,
     'Woo Product Collection filter UI hook registered');
$GLOBALS['idm_archive']='shop';
$block=(object)array('context'=>array('query'=>array('isProductCollectionBlock'=>true,'inherit'=>true)));
$_GET['idm_material']='tpe';
$block_query=idm_discovery_block_query_vars(array('post_type'=>'product','tax_query'=>$seed),$block,1);
test(count($block_query['tax_query'])===2
    && $block_query['tax_query'][0]===$seed[0]
    && $block_query['tax_query'][1]['terms']===array('tpe'),'Block query facet preserves visibility');
$block_query=idm_discovery_block_query_vars(array('post_type'=>'product'),(object)array('context'=>array('query'=>array('isProductCollectionBlock'=>true,'inherit'=>false))),1);
test(!isset($block_query['tax_query']),'Noninherited Product Collection unaffected');
$GLOBALS['idm_archive']='other';
$block_query=idm_discovery_block_query_vars(array('post_type'=>'product'),$block,1);
test(!isset($block_query['tax_query']),'Nonarchive collection unaffected');
$out=idm_discovery_block_frontend('SAFE_BLOCK',array('blockName'=>'woocommerce/product-collection','attrs'=>array('query'=>array('inherit'=>true))),$block);
test($out==='SAFE_BLOCK','Nonarchive block output untouched');

// Cross-environment QA fixture: these IDs intentionally differ from historic Dev 32–35.
class FakeQABatchProduct {
    public function __construct(
        public string $sku, public string $type, public string $status,
        public string $visibility, public string $batch, public int $parent=0
    ) {}
    public function get_sku():string{return $this->sku;}
    public function get_meta($key,$single=true):string{return $key==='_idm_r8_batch_id'?$this->batch:'';}
    public function get_status():string{return $this->status;}
    public function get_catalog_visibility():string{return $this->visibility;}
    public function is_type($type):bool{return $this->type===$type;}
    public function get_parent_id():int{return $this->parent;}
}
function wc_get_product_id_by_sku($sku):int{return $GLOBALS['qa_sku_ids'][$sku]??0;}
function wc_get_product($id){return $GLOBALS['qa_products'][$id]??false;}
$batch=IDM_DISCOVERY_BATCH;
$GLOBALS['qa_sku_ids']=array(
    'IDM-R8-EXAMPLE-SIMPLE'=>132, 'IDM-R8-EXAMPLE-PARENT'=>133,
    'IDM-R8-EXAMPLE-TPE'=>134, 'IDM-R8-EXAMPLE-SIL'=>135
);
$GLOBALS['qa_products']=array(
    132=>new FakeQABatchProduct('IDM-R8-EXAMPLE-SIMPLE','simple','draft','hidden',$batch),
    133=>new FakeQABatchProduct('IDM-R8-EXAMPLE-PARENT','variable','draft','hidden',$batch),
    134=>new FakeQABatchProduct('IDM-R8-EXAMPLE-TPE','variation','publish','visible',$batch,133),
    135=>new FakeQABatchProduct('IDM-R8-EXAMPLE-SIL','variation','publish','visible',$batch,133)
);
$journal=array('state'=>'applied','created_count'=>4,'ids'=>array(132,133,134,135));
test(idm_discovery_qa_batch_ids($journal,$batch)===array('simple'=>132,'parent'=>133,'tpe'=>134,'sil'=>135),
    'Non-Dev Local IDs accepted by exact SKU and journal');
$wrong=$journal;$wrong['ids']=array(32,33,34,35);
test(idm_discovery_qa_batch_ids($wrong,$batch)===array(),'Historic Dev IDs reject on Local');
$wrong=$journal;$wrong['created_count']=3;
test(idm_discovery_qa_batch_ids($wrong,$batch)===array(),'Incomplete batch journal fails closed');
$GLOBALS['qa_products'][135]->parent=999;
test(idm_discovery_qa_batch_ids($journal,$batch)===array(),'Mismatched variation parent fails closed');
$GLOBALS['qa_products'][135]->parent=133;
$GLOBALS['qa_products'][132]->status='publish';
test(idm_discovery_qa_batch_ids($journal,$batch)===array(),'Published parent fixture rejected');
$GLOBALS['qa_products'][132]->status='draft';
$GLOBALS['qa_products'][134]->batch='other';
test(idm_discovery_qa_batch_ids($journal,$batch)===array(),'Mismatched batch ownership rejected');
$GLOBALS['qa_products'][134]->batch=$batch;
unset($GLOBALS['qa_sku_ids']['IDM-R8-EXAMPLE-SIL']);
test(idm_discovery_qa_batch_ids($journal,$batch)===array(),'Missing variation SKU rejected');

echo 'R8_DISCOVERY_ISOLATED_TESTS='.$GLOBALS['idm_checks'].' PASS'.PHP_EOL;
