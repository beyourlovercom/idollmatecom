<?php
/**
 * R8-C rollback guard isolated tests. CLI only, pure arrays: no Woo/DB access.
 */
require_once __DIR__.'/../scripts/catalog/r8_woo_batch_guards.php';

$pass = 0;
function check($condition, $name) {
    global $pass;
    if (!$condition) { throw new RuntimeException('FAIL '.$name); }
    $pass++;
    echo 'PASS '.$name.PHP_EOL;
}
function blocked(callable $fn, $name) {
    global $pass;
    try { $fn(); } catch (RuntimeException $e) {
        $pass++;
        echo 'PASS '.$name.' (blocked)'.PHP_EOL;
        return;
    }
    throw new RuntimeException('FAIL '.$name.' was allowed');
}
$baseline = array(
    'site'=>'https://dev.idollmate.com',
    'blog_public'=>'0',
    'coming_soon'=>'yes',
    'products'=>array(
        array('id'=>13,'sku'=>'IDM-R7-QA','slug'=>'r7-simple','status'=>'draft',
              'integrity_sha256'=>hash('sha256','price=599|qty=2|asset=0')),
        array('id'=>15,'sku'=>'IDM-R7-VAR','slug'=>'r7-variant','status'=>'draft',
              'integrity_sha256'=>hash('sha256','price=999|qty=0|asset=0'))
    ),
    'categories'=>array('uncategorized','full-body-dolls'),
    'brands'=>array(),
    'attributes'=>array('pa_material'=>array('tpe','silicone'))
);
$current = $baseline;
$current['products'][] = array('id'=>201,'sku'=>'IDM-R8-1','slug'=>'r8-1','status'=>'draft');
$current['products'][] = array('id'=>202,'sku'=>'IDM-R8-2','slug'=>'r8-2','status'=>'draft');
$owner = static function ($id) { return in_array($id,array(201,202),true)?'r8-aaa':'foreign'; };
$ids = idm_r8_assert_live_baseline($baseline,$current,'r8-aaa',$owner);
check($ids===array(201,202),'OWN_BATCH_ADDITIONS_ACCEPTED');
check(idm_r8_assert_batch_journal_ids(array(202,201),
      array('state'=>'applied','ids'=>array(201,202))),'JOURNAL_ORDER_INDEPENDENT');
check(idm_r8_assert_batch_journal_ids(array(201,202),
      array('state'=>'in_progress','ids'=>array(201,202))),'CRASHED_IN_PROGRESS_RECOVERY');
blocked(function() use($baseline,$current) {
    idm_r8_assert_live_baseline($baseline,$current,'r8-aaa',
        static function($id) {return 'foreign';});
},'FOREIGN_BATCH_PRODUCT');
blocked(function() use($baseline,$current,$owner) {
    $other=$current;
    $other['products'][]=array('id'=>203,'sku'=>'OTHER','slug'=>'new','status'=>'draft');
    idm_r8_assert_live_baseline($baseline,$other,'r8-aaa',$owner);
},'UNRELATED_NEW_PRODUCT');
blocked(function() use($baseline,$current,$owner) {
    $other=$current;
    $other['products'][0]['slug']='altered-r7-slug';
    idm_r8_assert_live_baseline($baseline,$other,'r8-aaa',$owner);
},'BASELINE_PRODUCT_EDIT');
blocked(function() use($baseline,$current,$owner) {
    $old=$baseline;
    unset($old['products'][0]['integrity_sha256']);
    idm_r8_assert_live_baseline($old,$current,'r8-aaa',$owner);
},'LEGACY_WEAK_BASELINE_REJECTED');
blocked(function() use($baseline,$current,$owner) {
    $other=$current;
    $other['products'][0]['integrity_sha256']=hash('sha256','price=1|qty=2|asset=0');
    idm_r8_assert_live_baseline($baseline,$other,'r8-aaa',$owner);
},'BASELINE_PRICE_OR_STOCK_DIGEST_MUTATION');
blocked(function() use($baseline,$current,$owner) {
    $other=$current;
    $other['products'][1]['integrity_sha256']=hash('sha256','changed-media-or-content');
    idm_r8_assert_live_baseline($baseline,$other,'r8-aaa',$owner);
},'BASELINE_MEDIA_OR_CONTENT_DIGEST_MUTATION');
blocked(function() use($baseline,$current,$owner) {
    $other=$current;
    array_shift($other['products']);
    idm_r8_assert_live_baseline($baseline,$other,'r8-aaa',$owner);
},'BASELINE_PRODUCT_REMOVED');
blocked(function() use($baseline,$current,$owner) {
    $other=$current;
    $other['categories'][]='added-category';
    idm_r8_assert_live_baseline($baseline,$other,'r8-aaa',$owner);
},'CATEGORY_MUTATION');
blocked(function() use($baseline,$current,$owner) {
    $other=$current;
    $other['attributes']['pa_material'][]='hybrid';
    idm_r8_assert_live_baseline($baseline,$other,'r8-aaa',$owner);
},'ATTRIBUTE_MUTATION');
blocked(function() use($baseline,$current,$owner) {
    $other=$current;
    $other['products'][]=$other['products'][2];
    idm_r8_assert_live_baseline($baseline,$other,'r8-aaa',$owner);
},'DUPLICATE_LIVE_PRODUCT_ID');
blocked(function() use($baseline,$current,$owner) {
    $other=$baseline;
    $other['site']='https://idollmate.com';
    idm_r8_assert_live_baseline($baseline,$other,'r8-aaa',$owner);
},'PRODUCTION_ORIGIN_CHANGE');
blocked(function() {
    idm_r8_assert_batch_journal_ids(array(201),array('state'=>'applied','ids'=>array(201,202)));
},'MISSING_TAGGED_ID');
blocked(function() {
    idm_r8_assert_batch_journal_ids(array(201,202,203),array('state'=>'applied','ids'=>array(201,202)));
},'FOREIGN_TAGGED_ID');
blocked(function() {
    idm_r8_assert_batch_journal_ids(array(201,201),array('state'=>'applied','ids'=>array(201,201)));
},'DUPLICATE_IDS');
blocked(function() {
    idm_r8_assert_batch_journal_ids(array(201),array('state'=>'rolled_back','ids'=>array(201)));
},'ROLLED_BACK_STATE_REJECTS_NEW_DELETE');
blocked(function() {
    idm_r8_assert_batch_journal_ids(array(),array('state'=>'applied','ids'=>array()));
},'EMPTY_JOURNAL_REJECTS_DELETE');
$completed = array('state'=>'applied', 'ids'=>array(201,202), 'created_count'=>2);
check(idm_r8_assert_applied_batch_replay($completed,array(202,201),array(201,202),2),
      'REPEATED_APPLY_MATCHED_IS_READ_ONLY');
blocked(function() use($completed) {
    $pending=$completed; $pending['pending_sku']='IDM-R8-EXAMPLE-TPE';
    idm_r8_assert_applied_batch_replay($pending,array(201,202),array(201,202),2);
}, 'PENDING_SKU_REPLAY_DENIED');
blocked(function() use($completed) {
    $in_progress=$completed; $in_progress['state']='in_progress';
    idm_r8_assert_applied_batch_replay($in_progress,array(201,202),array(201,202),2);
}, 'IN_PROGRESS_REPLAY_DENIED');
blocked(function() use($completed) {
    idm_r8_assert_applied_batch_replay($completed,array(201,202),array(201,202,203),2);
}, 'FOREIGN_LIVE_ADDITION_REPLAY_DENIED');
blocked(function() use($completed) {
    $incomplete=$completed; $incomplete['created_count']=1;
    idm_r8_assert_applied_batch_replay($incomplete,array(201,202),array(201,202),2);
}, 'COUNT_DRIFT_REPLAY_DENIED');
blocked(function() use($completed) {
    idm_r8_assert_applied_batch_replay($completed,array(201),array(201,202),2);
}, 'MISSING_TAGGED_REPLAY_DENIED');
blocked(function() use($completed) {
    idm_r8_assert_applied_batch_replay($completed,array(201,202),array(201,202),4);
}, 'PARTIAL_BATCH_CANNOT_PASS_FOUR_ROW_CANARY');
blocked(function() use($completed) {
    idm_r8_assert_applied_batch_replay($completed,array(201,202),array(201,202),0);
}, 'INVALID_EXPECTED_COUNT_REJECTED');
blocked(function() use($completed) {
    idm_r8_assert_applied_batch_replay($completed,array(201,202),array(201,202),26);
}, 'OVERLIMIT_EXPECTED_COUNT_REJECTED');
echo 'R8_PHP_GUARD_TESTS='.$pass.' PASS'.PHP_EOL;
