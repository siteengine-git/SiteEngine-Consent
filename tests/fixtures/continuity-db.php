<?php
/** Isolated SQL boundary model. Not an actual MySQL engine test. */
class Receipt_Fixture_DB {
    public $prefix = 'fixture_', $last_error = '', $insert_id = 0;
    public $rows = [], $heads = [], $links = [], $lock = true, $fail_select = false, $fail_insert = false, $fail_readback = false;
    public $releases = 0, $engine = 'InnoDB', $fail_head = false, $fail_commit = false, $queries = [];
    public $legacy_links = [], $legacy_columns = ['receipt_row_id','receipt_id','chain_id','revision','intent_hash','receipt_digest','integrity'], $legacy_metadata_error = false;
    private $snapshot;
    public function prepare($sql, ...$args) { return [$sql, $args]; }
    public function get_var($q) {
        $this->last_error = '';
        if (strpos($q[0], 'ENGINE') !== false) return $this->engine;
        if (strpos($q[0], 'RELEASE_LOCK') !== false) { $this->releases++; return '1'; }
        if (strpos($q[0], 'SELECT chain_id') !== false) {
            $rows = strpos($q[0], 'se_consent_event_links') !== false ? $this->links : $this->legacy_links;
            foreach ($rows as $row) if ($row['chain_id'] === $q[1][0]) return $row['chain_id'];
            return null;
        }
        return $this->lock ? '1' : null;
    }
    public function get_col($q) {
        $this->last_error = $this->legacy_metadata_error ? 'fixture metadata unavailable' : '';
        return $this->legacy_metadata_error ? null : $this->legacy_columns;
    }
    public function query($q) {
        $this->queries[] = $q;
        if ($q === 'START TRANSACTION') $this->snapshot = [$this->rows, $this->heads, $this->links];
        if ($q === 'COMMIT' && $this->fail_commit) return false;
        if ($q === 'ROLLBACK' && $this->snapshot) [$this->rows, $this->heads, $this->links] = $this->snapshot;
        return 1;
    }
    public function get_results($q, $format) {
        $this->last_error = $this->fail_select ? 'fixture unavailable' : '';
        if ($this->fail_select) return null;
        if (strpos($q[0], 'SELECT receipt_row_id,') !== false) {
            $rows = strpos($q[0], 'se_consent_event_links') !== false ? $this->links : $this->legacy_links;
            return array_slice(array_map(function ($r) { return array_intersect_key($r, array_flip(['receipt_row_id','receipt_id','chain_id','revision','intent_hash','receipt_digest','integrity'])); }, array_values(array_filter($rows, function ($r) use ($q) { return $r['receipt_row_id'] == $q[1][0]; }))), 0, 2);
        }
        return array_slice(array_values(array_filter($this->rows, function ($r) use ($q) {
            return $r['receipt_id'] === $q[1][0] || (strpos($q[0], 'OR id') !== false && $r['id'] == $q[1][1]);
        })), 0, 2);
    }
    public function get_row($q, $format) {
        $this->last_error = $this->fail_select ? 'fixture unavailable' : '';
        if ($this->fail_select) return null;
        if (strpos($q[0], 'se_consent_chains') !== false) return $this->heads[$q[1][0]] ?? null;
        if (strpos($q[0], 'se_consent_event_links') !== false || strpos($q[0], 'se_consent_events') !== false) {
            if (strpos($q[0], 'WHERE chain_id') !== false) {
                foreach ($this->links as $link) if ($link['chain_id'] === $q[1][0]) return $link;
                return null;
            }
            return $this->links[$q[1][0]] ?? null;
        }
        return $this->fail_readback ? null : ($this->rows[$q[1][0]] ?? null);
    }
    public function insert($table, $row) {
        if ($this->fail_insert) return false;
        if (strpos($table, 'se_consent_event_links') !== false) { $this->links[$row['receipt_row_id']] = $row; return 1; }
        $this->insert_id++; $row['id'] = $this->insert_id; $this->rows[$this->insert_id] = $row; return 1;
    }
    public function replace($table, $row) {
        if ($this->fail_head) return false;
        $this->heads[$row['chain_id']] = $row; return 1;
    }
}
