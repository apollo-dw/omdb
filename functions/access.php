<?php
	function isIdEditRequestAdmin($needle) {
    $haystack = [12704035, //fsjallink
				 9558549, //moonpoint 
				 8088092, // shizume
				 290128, //onosakihito
				 4335785, //maxus
				 15243233, //kurboh
				 11220416, //wither
				 266596, //kevincela
				 14102976]; // hivie
	
    foreach ($haystack as $item) {
        if ($item === $needle) {
            return true;
        }
    }
	
    return false;
	}
?>