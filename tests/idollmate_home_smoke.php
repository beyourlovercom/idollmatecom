<?php
/** Isolated, offline homepage shortcode smoke test. No WordPress/HTTP/DB. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
$GLOBALS['hooks'] = [];
$GLOBALS['shortcodes'] = [];
$GLOBALS['products'] = [];
$GLOBALS['categories'] = [];
function add_action($name, $callback, $priority=10) { $GLOBALS['hooks'][$name][]=$callback; }
function add_shortcode($name, $callback) { $GLOBALS['shortcodes'][$name]=$callback; }
function esc_attr__(string $text,string $domain=''):string{return htmlspecialchars($text,ENT_QUOTES,'UTF-8');}
function esc_html__(string $text,string $domain=''):string{return htmlspecialchars($text,ENT_QUOTES,'UTF-8');}
function esc_html(string $text):string{return htmlspecialchars($text,ENT_QUOTES,'UTF-8');}
function esc_url(string $text):string{return htmlspecialchars($text,ENT_QUOTES,'UTF-8');}
function wc_get_page_permalink(string $page):string{return 'https://dev.idollmate.com/shop/';}
function wc_get_products(array $args):array{return $GLOBALS['products'];}
function get_terms(array $args):array{return $GLOBALS['categories'];}
function get_term_link($obj):string{return 'https://dev.idollmate.com/product-category/'.urlencode($obj->slug).'/';}
function is_wp_error($x):bool{return false;}
function do_shortcode(string $code):string {return '<ul class="products"><li>Owned product</li></ul>';}
function is_singular():bool{return true;}
class WP_Post { public $post_content='[idollmate_home]'; }
function get_post():WP_Post{return new WP_Post();}
function has_shortcode(string $content,string $code):bool{return strpos($content,'['.$code)!==false;}
function plugins_url(string $path,string $file):string{return '/plugins/idollmate-home/'.$path;}
function wp_enqueue_style(string $name,string $url,array $deps,string $version):void{$GLOBALS['enqueued']=$name;}
function home_url(string $path):string{return 'https://dev.idollmate.com'.$path;}
function check(bool $ok,string $label):void{if(!$ok){fwrite(STDERR,"FAIL $label\n");exit(1);}echo "PASS $label\n";}
require dirname(__DIR__).'/wordpress/plugins/idollmate-home/idollmate-home.php';
foreach($GLOBALS['hooks']['init']??[] as $fn)$fn();
check(isset($GLOBALS['shortcodes']['idollmate_home']),'Shortcode registered');
$html=idm_render_home();
check(str_contains($html,'aria-labelledby="idm-home-heading"'),'Accessible hero heading');
check(str_contains($html,'Products will appear here'),'Empty catalog is honest');
check(!str_contains($html,'Owned product'),'Empty catalog not populated with fake products');
check(str_contains($html,'https://dev.idollmate.com/shop/'),'Woo shop route reused');
$GLOBALS['products']=[1];
$GLOBALS['categories']=[(object)['name'=>'Original Materials','slug'=>'materials']];
$html=idm_render_home();
check(str_contains($html,'Original Materials'),'Woo taxonomy category rendered');
check(str_contains($html,'Owned product'),'Published Woo product shortcode rendered');
foreach($GLOBALS['hooks']['wp_enqueue_scripts']??[] as $fn)$fn();
check(($GLOBALS['enqueued']??'')==='idollmate-home','Scoped stylesheet enqueued for shortcode page');
$css=dirname(__DIR__).'/wordpress/plugins/idollmate-home/assets/home.css';
check(is_file($css)&&str_contains(file_get_contents($css),'@media (max-width: 540px)'),'Mobile responsive CSS included');
check(!str_contains($html,'yourdoll.com'),'No competitor site references in storefront HTML');
echo "ALL ISOLATED HOME SMOKE TESTS PASSED\n";
