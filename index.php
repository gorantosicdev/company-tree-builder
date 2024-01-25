<?php

class Travel {
// Enter your code here
}

class Company {
// Enter your code here
}

class TestScript {
  public function execute() {
    $start = microtime(true);
    // Enter your code here
    // echo json_encode($result);
    echo 'Total time: ' . (microtime(true) - $start);
  }
}

(new TestScript())->execute();