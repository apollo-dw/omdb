<?php
	function isIdEditRequestAdmin($needle) {
    $haystack = [9558549, //fsjallink
				 8088092, // shizume
				 11220416, //wither
				 2688581]; //luscent
	
    foreach ($haystack as $item) {
        if ($item === $needle) {
            return true;
        }
    }
	
    return false;
	}
?>