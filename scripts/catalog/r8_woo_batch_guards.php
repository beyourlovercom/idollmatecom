<?php
/**
 * Pure safety guards for iDollmate R8-C; no WordPress/WooCommerce side effects.
 * Functions are used by the guarded adapter and tested in isolation in CI.
 */
function idm_r8_assert_live_baseline($baseline, $current, $batch_id, $read_batch_tag) {
    if (!is_array($baseline) || !is_array($current)
        || !isset($baseline['products']) || !is_array($baseline['products'])
        || !isset($current['products']) || !is_array($current['products'])) {
        throw new RuntimeException('R8-C malformed baseline/live snapshot');
    }
    $original_ids = array();
    foreach ($baseline['products'] as $row) {
        if (!isset($row['id']) || !is_int($row['id']) || isset($original_ids[$row['id']])) {
            throw new RuntimeException('R8-C invalid/duplicate baseline ID');
        }
        $original_ids[$row['id']] = true;
    }
    $preserved = array();
    $seen = array();
    $added_ids = array();
    foreach ($current['products'] as $row) {
        if (!isset($row['id']) || !is_int($row['id']) || isset($seen[$row['id']])) {
            throw new RuntimeException('R8-C malformed/duplicate live ID');
        }
        $id = $row['id'];
        $seen[$id] = true;
        if (isset($original_ids[$id])) {
            $preserved[] = $row;
            continue;
        }
        if (call_user_func($read_batch_tag, $id) !== $batch_id) {
            throw new RuntimeException('R8-C unrelated product added; rollback requires review');
        }
        $added_ids[] = $id;
    }
    $without_batch = $current;
    $without_batch['products'] = $preserved;
    if ($without_batch !== $baseline) {
        throw new RuntimeException('R8-C baseline products/terms changed; rollback requires review');
    }
    return $added_ids;
}

function idm_r8_assert_batch_journal_ids($actual_ids, $journal) {
    if (!is_array($actual_ids) || !is_array($journal)
        || !in_array($journal['state'] ?? null, array('in_progress', 'applied'), true)
        || !is_array($journal['ids'] ?? null)) {
        throw new RuntimeException('R8-C invalid batch journal state');
    }
    $expected = array_map('intval', $journal['ids']);
    $actual = array_map('intval', $actual_ids);
    sort($expected, SORT_NUMERIC);
    sort($actual, SORT_NUMERIC);
    if (!$actual || $expected !== $actual
        || count($actual) !== count(array_unique($actual))
        || count($expected) !== count(array_unique($expected))) {
        throw new RuntimeException('R8-C batch IDs differ from journal; manual review');
    }
    return true;
}
