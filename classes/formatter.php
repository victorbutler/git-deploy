<?php

class Formatter {

	/**
	 * Diffs the given timestamp with the current time to
	 * deduce how long as it was from now.
	 * Modified from http://snipplr.com/view/4912/
	 * @param   int   timestamp
	 * @return  string
	 */
	public static function relative_time($timestamp) {
		$diff = time() - $timestamp;
		if ($diff<60)
			return $diff . " second" . ($diff > 1 ? 's' : '') . " ago";
		$diff = round($diff/60);
		if ($diff<60)
			return $diff . " minute" . ($diff > 1 ? 's' : '') . " ago";
		$diff = round($diff/60);
		if ($diff<24)
			return $diff . " hour" . ($diff > 1 ? 's' : '') . " ago";
		$diff = round($diff/24);
		if ($diff<7)
			return $diff . " day" . ($diff > 1 ? 's' : '') . " ago";
		$diff = round($diff/7);
		if ($diff<4)
			return $diff . " week" . ($diff > 1 ? 's' : '') . " ago";
		return date("n/j/Y", $timestamp);
	}

}