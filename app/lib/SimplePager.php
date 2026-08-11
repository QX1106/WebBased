<?php

// Reusable pager: runs a COUNT query + a LIMIT-ed query for the current page.
class SimplePager {
    public $result, $count, $item_count, $page, $page_count, $limit;

    public function __construct($pdo, $query, $params, $limit, $page) {
        $this->limit = $limit;
        $this->page = max(1, (int)$page);

        $count_stm = $pdo->prepare("SELECT COUNT(*) FROM ($query) AS t");
        $count_stm->execute($params);
        $this->item_count = (int)$count_stm->fetchColumn();
        $this->page_count = max(1, (int)ceil($this->item_count / $limit));
        $this->page = min($this->page, $this->page_count);

        $offset = ($this->page - 1) * $limit;
        $stm = $pdo->prepare("$query LIMIT $offset, $limit");
        $stm->execute($params);
        $this->result = $stm->fetchAll();
        $this->count = count($this->result);
    }

    public function links($href = '') {
        $html = "<div class='pager'>";
        for ($i = 1; $i <= $this->page_count; $i++) {
            $class = ($i == $this->page) ? 'active' : '';
            $html .= "<a class='$class' href='" . htmlspecialchars("?page=$i$href", ENT_QUOTES) . "'>$i</a>";
        }
        $html .= "</div>";
        return $html;
    }
}
