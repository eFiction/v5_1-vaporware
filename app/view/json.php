<?php

namespace View;
class JSON extends Base {
	public function finish() {
		header('Content-Type: application/json');

		$this->data = json_encode($this->data);
		$this->data = preg_replace_callback(
								'/\{ICON:([\w-]+)\}/s',
								function ($icon)
								{
									return addslashes( Iconset::instance()->{$icon[1]} );
								}
								, $this->data
							);
		return $this->data;
	}
}
