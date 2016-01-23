<?php

namespace View;
class JSON extends Base {
	public function finish() {
		header('Content-Type: application/json');
		return json_encode($this->data);
	}
}
